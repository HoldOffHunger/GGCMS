"""Reads a language's verb tables out of its Wiktionary dump and writes forms-<language>.json: for each verb, each
form the course cares about, named by the spine's features (who, when, going, command...) rather than by grammar terms.

    python make_forms.py spanish <folder holding the dump> [--verbs=<file, one dictionary form per line>]

The verbs are the ones in forms-recipes-<language>.json, plus any in --verbs (the verbs the course already teaches).
forms-recipes-<language>.json says which of the dump's tags make which features: that file is the only place the
language's grammar is spelled out. A slot the dump has no form for is left out and counted, never guessed.
A "compound" slot is two words: the form of the verb's helper (avoir or être, as the dump says) and one of its own.

The dump is kaikki.org's extract of the English Wiktionary. Nothing here calls a model.
"""
import json
import re
import sys
import unicodedata
from pathlib import Path

HERE = Path(__file__).resolve().parent


def elide(recipes: dict, text: str) -> str:
    """je ai is j'ai: a recipe file names the little words that lose their vowel, and the letters they lose it before"""
    for whole, short in recipes.get("elide", {}).get("words", {}).items():
        text = re.sub(re.escape(whole) + "(?=[" + recipes["elide"]["before"] + "])", short, text)
    return text


def plain(recipes: dict, text: str) -> str:
    """The form as it is written. The Italian dictionary marks where the stress falls (pàrlo, andàre, hò), which nobody
    writes. Only an accent on the last letter of a word of two beats or more is spelling (parlerò, andrà), and on the
    few one-beat words the recipe lists (è, dà, può). A recipe asks for this with strip_inner_accents."""
    for mark in recipes.get("strip_marks", []):  # Russian's dictionary marks the stress with a combining accent: чита́ю
        text = text.replace(mark, "")
    if not recipes.get("strip_inner_accents"):
        return text
    bare = lambda word: unicodedata.normalize("NFC", "".join(
        char for char in unicodedata.normalize("NFD", word) if unicodedata.category(char) != "Mn"))
    words = []
    for word in text.split(" "):
        beats = len(re.findall("[aeiou]+", bare(word).lower()))
        keeps = word in recipes.get("accented_monosyllables", []) or (beats > 1 and word[-1:] not in "'’")
        words.append(bare(word[:-1]) + word[-1:] if keeps else bare(word))
    return " ".join(words)


def table(entry: dict, skip: set, every: bool = False, prefixes: tuple = ()) -> list:
    """The entry's first conjugation table: in Spanish a second one is the reflexive verb's, with its pronouns attached.
    German's later tables are the same verb's two-word forms (bin gegangen, werde gehen), so its recipe asks for every table."""
    forms, tables = [], 0
    for form in entry.get("forms", []):
        tags = set(form.get("tags", []))
        if "table-tags" in tags:
            tables += 1
            if tables > 1 and not every:
                break
        if form.get("source") != "conjugation" or tags & skip:
            continue
        if form["form"].startswith(prefixes):  # "-" where a verb has no such form, "-(e)m czytał", "niech czytam"
            continue
        forms.append((form["form"], tags))
    return forms


def agreed(part: str, change: str) -> str:
    """allé + "e" is allée; andato + "-a" is andata: a change that starts with a hyphen replaces the last letter"""
    return part[:-1] + change[1:] if change.startswith("-") else part + change


def read(recipes: dict, dump: Path, wanted: set) -> tuple[dict, list]:
    skip = set(recipes["skip_tags"])
    verbs, missing = {}, []
    with dump.open(encoding="utf-8") as lines:
        for line in lines:
            if '"pos": "verb"' not in line:
                continue
            entry = json.loads(line)
            word = entry.get("word")
            if word not in wanted or word in verbs or entry.get("pos") != "verb":
                continue
            forms = [(plain(recipes, text), tags)
                     for text, tags in table(entry, skip, recipes.get("all_tables", False), tuple(recipes.get("skip_prefixes", [])) or ("\0",))]
            if not forms:
                continue  # a form-of entry, or an entry with no table: the real one comes later in the dump
            found = []
            if "partner_tags" in recipes:  # the verb's other half: читать is doing it, прочитать is having done it
                named = [plain(recipes, form["form"]) for form in entry.get("forms", [])
                         if form.get("source") != "conjugation" and set(recipes["partner_tags"]) <= set(form.get("tags", []))]
                if named:
                    found.append({"form": named[0], "features": {"form": "partner"}})
            if "helper_tags" in recipes:  # which helper the verb takes for its two-word forms: "avoir + past participle"
                named = [form["form"] for form in entry.get("forms", []) if set(recipes["helper_tags"]) <= set(form.get("tags", []))]
                if named:
                    # A verb of motion lists both, "avoir" and "être + past participle": avoir when it has an object (j'ai
                    # sorti la poubelle), être when it is the going itself (je suis sorti), which is the sense a course
                    # teaches first. So a helper named in recipes["helper_prefers"] wins wherever the verb can take it, unless
                    # recipes["helper_of"] names the verb: disparaître can take être, and nobody now says il est disparu.
                    helpers = [plain(recipes, text.split(" + ")[0]) for text in named]
                    preferred = next((helper for helper in recipes.get("helper_prefers", []) if helper in helpers), helpers[0])
                    chosen = recipes.get("helper_of", {}).get(word) or preferred
                    found.append({"form": chosen, "features": {"form": "helper"}})
            if "helper_default" in recipes:  # a dictionary that does not say which helper a verb takes: the recipe lists the few that differ
                # weggaan is gaan with a front part, and takes gaan's helper: helper_of_endings says so for a whole family at once
                by_ending = next((helper for ending, helper in recipes.get("helper_of_endings", {}).items() if word.endswith(ending)), None)
                found.append({"form": recipes.get("helper_of", {}).get(word) or by_ending or recipes["helper_default"], "features": {"form": "helper"}})
            for slot in recipes["slots"]:
                if "compound" in slot or "partner" in slot:
                    continue  # built below, once the helper verbs and the partners are read too
                need, without = set(slot["tags"]), set(slot.get("without", []))
                match = [text for text, tags in forms if need <= tags and not tags & without][slot.get("nth", 0):]
                # a verb with two tables gives two answers: aufstehen is habe aufgestanden in one and bin aufgestanden in the other
                # only for the verbs the recipe names: anfangen and bekommen have a sein table too, and take haben
                preferred = [text for text in match if text.split(" ")[0] in slot.get("prefer_first", [])]                     if word in recipes.get("prefer_first_verbs", []) else []
                if preferred:
                    match = preferred + [text for text in match if text not in preferred]
                # arbeit is no command: after a t or a d the short form needs its e back (arbeite, beende, finde)
                if match and match[0][-1:] in slot.get("lengthen_after", []) and match[0] + "e" in match:
                    match = [match[0] + "e"] + match
                if match and not any(form["features"] == slot["features"] for form in found):  # a second slot for the same thing is its fallback
                    found.append({"form": match[0], "features": slot["features"]})
                    if slot.get("split") and " " in match[0]:  # a two-word form the dictionary gives whole: bin gegangen
                        found[-1]["helper"], found[-1]["part"] = match[0].split(" ", 1)
                    if "shown" in slot:  # the form as a table shows it: the dictionary lists tengas, and nobody says it without its no
                        first, _, rest = match[0].partition(" ")
                        # a verb that splits in two keeps its second half for the end: kommen Sie an, not kommen an Sie
                        said = slot["shown"].format(first) + " " + rest if rest and slot.get("inside") else slot["shown"].format(match[0])
                        found[-1]["shown"] = elide(recipes, said)
                else:
                    missing.append((word, slot["features"]))
            verbs[word] = found
    for word, found in verbs.items():
        for slot in recipes["slots"]:
            if "partner" in slot:  # a form borrowed from the verb's other half, where the dictionary names one and has its table
                partner = next((form["form"] for form in found if form["features"] == {"form": "partner"}), "")
                borrowed = next((form["form"] for form in verbs.get(partner, []) if form["features"] == slot["partner"]), "")
                if borrowed:
                    found.append({"form": borrowed, "features": slot["features"]})
            if "compound" in slot:
                made = compound(recipes, verbs, found, slot["compound"])
                if made:
                    found.append({**made, "features": slot["features"]})
                else:
                    missing.append((word, slot["features"]))
    return verbs, missing


def compound(recipes: dict, verbs: dict, found: list, how: dict) -> dict | None:
    """A two-word form: a helper verb's own form, then the verb's part. ai parlé; suis allé, which a woman writes allée.
    The helper is the one the dictionary names for this verb, unless the slot names one for every verb: vou falar."""
    def of(forms: list, features: dict) -> str:
        return next((form["form"] for form in forms if form["features"] == features), "")
    helper_verb = how.get("verb") or of(found, {"form": "helper"})
    helper, part = of(verbs.get(helper_verb, []), how["helper"]), of(found, how["part"])
    if not helper or not part:
        return None
    if how.get("alone_for_itself") and verbs.get(helper_verb) is found:
        return {"form": helper}  # let's go is vamos, not vamos ir
    change, shown = how.get("agree", {}).get(helper_verb, ["", ""])
    helper = (helper + " " + how.get("join", "")).strip()  # ben aan het, in front of eten
    made = {"form": f"{helper} {agreed(part, change)}", "helper": helper, "part": agreed(part, change)}
    if shown:
        made["shown"] = f"{helper} {agreed(part, shown)}"
    return made


def main() -> int:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    language, folder = sys.argv[1], Path(sys.argv[2])
    recipes = json.loads((HERE / f"forms-recipes-{language}.json").read_text(encoding="utf-8"))
    wanted = {verb[0] for verb in recipes["verbs"]["list"]} | helpers(recipes) | set(recipes.get("helper_verbs", []))
    for argument in sys.argv[3:]:
        if argument.startswith("--verbs="):
            wanted |= {line.strip().lower() for line in Path(argument[8:]).read_text(encoding="utf-8").splitlines() if line.strip()}
    dump = folder / language / recipes["dump"]
    if "partner_tags" in recipes:  # the partners must be read too, and are only known once their verbs have been
        first, _ = read(recipes, dump, wanted)
        wanted = wanted | {form["form"] for forms in first.values() for form in forms if form["features"] == {"form": "partner"}}
    verbs, missing = read(recipes, dump, wanted)
    out = {"language": language, "translation_language": recipes["translation_language"],
           "source": "English Wiktionary, by way of kaikki.org's extract. Made by make_forms.py: do not edit by hand.",
           "verbs": dict(sorted(verbs.items()))}
    # written out by hand, one form to a line, so a diff stays readable when a later dump changes a form
    head = json.dumps({key: value for key, value in out.items() if key != "verbs"}, ensure_ascii=False, indent="\t")[:-2]
    blocks = []
    for word, forms in out["verbs"].items():
        rows = ",\n".join("\t\t\t" + json.dumps(form, ensure_ascii=False) for form in forms)
        blocks.append("\t\t" + json.dumps(word, ensure_ascii=False) + ": [\n" + rows + "\n\t\t]")
    text = head + ',\n\t"verbs": {\n' + ",\n".join(blocks) + "\n\t}\n}\n"
    (HERE / f"forms-{language}.json").write_text(text, encoding="utf-8", newline="\n")
    print(f"{len(verbs)} verbs, {sum(len(forms) for forms in verbs.values())} forms, of {len(wanted)} asked for")
    absent = sorted(wanted - set(verbs))
    print(f"not in the dump as a verb with a table: {len(absent)}" + (": " + ", ".join(absent[:40]) if absent else ""))
    print(f"slots the dump had no form for: {len(missing)}")
    for word, features in missing[:20]:
        print(f"   {word}: {features}")
    return 0


def helpers(recipes: dict) -> set:
    return {row["helper"]["base"] for recipe in recipes["recipes"].values() for row in recipe["rows"] if "helper" in row}


if __name__ == "__main__":
    sys.exit(main())
