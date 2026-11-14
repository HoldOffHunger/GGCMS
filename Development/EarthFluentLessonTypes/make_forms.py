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
from pathlib import Path

HERE = Path(__file__).resolve().parent


def elide(recipes: dict, text: str) -> str:
    """je ai is j'ai: a recipe file names the little words that lose their vowel, and the letters they lose it before"""
    for whole, short in recipes.get("elide", {}).get("words", {}).items():
        text = re.sub(re.escape(whole) + "(?=[" + recipes["elide"]["before"] + "])", short, text)
    return text


def table(entry: dict, skip: set) -> list:
    """The entry's first conjugation table: a second one is the reflexive verb's, with its pronouns attached."""
    forms, tables = [], 0
    for form in entry.get("forms", []):
        tags = set(form.get("tags", []))
        if "table-tags" in tags:
            tables += 1
            if tables > 1:
                break
        if form.get("source") != "conjugation" or tags & skip:
            continue
        forms.append((form["form"], tags))
    return forms


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
            forms = table(entry, skip)
            if not forms:
                continue  # a form-of entry, or an entry with no table: the real one comes later in the dump
            found = []
            if "helper_tags" in recipes:  # which helper the verb takes for its two-word forms: "avoir + past participle"
                named = [form["form"] for form in entry.get("forms", []) if set(recipes["helper_tags"]) <= set(form.get("tags", []))]
                if named:
                    # A verb of motion lists both, "avoir" and "être + past participle": avoir when it has an object (j'ai
                    # sorti la poubelle), être when it is the going itself (je suis sorti), which is the sense a course
                    # teaches first. So a helper named in recipes["helper_prefers"] wins wherever the verb can take it.
                    helpers = [text.split(" + ")[0] for text in named]
                    chosen = next((helper for helper in recipes.get("helper_prefers", []) if helper in helpers), helpers[0])
                    found.append({"form": chosen, "features": {"form": "helper"}})
            for slot in recipes["slots"]:
                if "compound" in slot:
                    continue  # built below, once the helper verbs are read too
                need, without = set(slot["tags"]), set(slot.get("without", []))
                match = [text for text, tags in forms if need <= tags and not tags & without]
                if match:
                    found.append({"form": match[0], "features": slot["features"]})
                    if "shown" in slot:  # the form as a table shows it: the dictionary lists tengas, and nobody says it without its no
                        found[-1]["shown"] = elide(recipes, slot["shown"].format(match[0]))
                else:
                    missing.append((word, slot["features"]))
            verbs[word] = found
    for word, found in verbs.items():
        for slot in recipes["slots"]:
            if "compound" in slot:
                made = compound(verbs, found, slot["compound"])
                if made:
                    found.append({**made, "features": slot["features"]})
                else:
                    missing.append((word, slot["features"]))
    return verbs, missing


def compound(verbs: dict, found: list, how: dict) -> dict | None:
    """A two-word form: the helper verb's own form, then the verb's part. ai parlé; suis allé, which a woman writes allée."""
    def of(forms: list, features: dict) -> str:
        return next((form["form"] for form in forms if form["features"] == features), "")
    helper_verb = of(found, {"form": "helper"})
    helper, part = of(verbs.get(helper_verb, []), how["helper"]), of(found, how["part"])
    if not helper or not part:
        return None
    suffix, shown = how.get("agree", {}).get(helper_verb, ["", ""])
    made = {"form": f"{helper} {part}{suffix}", "helper": helper, "part": part + suffix}
    if shown:
        made["shown"] = f"{helper} {part}{shown}"
    return made


def main() -> int:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    language, folder = sys.argv[1], Path(sys.argv[2])
    recipes = json.loads((HERE / f"forms-recipes-{language}.json").read_text(encoding="utf-8"))
    wanted = {verb[0] for verb in recipes["verbs"]["list"]} | helpers(recipes) | set(recipes.get("helper_verbs", []))
    for argument in sys.argv[3:]:
        if argument.startswith("--verbs="):
            wanted |= {line.strip().lower() for line in Path(argument[8:]).read_text(encoding="utf-8").splitlines() if line.strip()}
    verbs, missing = read(recipes, folder / language / recipes["dump"], wanted)
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
