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


def is_romanized(text: str) -> bool:
    """Latin letters, with or without marks: gae, jāūṅgā, not गए"""
    return any(char.isalpha() for char in text) and not any("ऀ" <= char <= "ൿ" for char in text)


def is_kana(text: str) -> bool:
    """Written only in hiragana or katakana: たべます, not 食べます and not tabemasu"""
    return bool(text) and all("\u3040" <= char <= "\u30ff" or char == "ー" for char in text)


def derived(text: str, suffix: list) -> str | None:
    """食べます with ["ます", "ました"] is 食べました; ["", "います"] adds to the end. None where the form does not end so."""
    old, new = suffix
    return text[:len(text) - len(old)] + new if text.endswith(old) else None


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


FOURTH = set("àèìòùǜ")


def pinyin_of(entry: dict) -> str:
    """The Mandarin pinyin the dump gives first: 'chī (chi¹)' is chī. Standard is not always tagged (上升), and the dialects
    are Mandarin too, so a reading with tone numbers in it (Chengdu's ci², Cantonese's gong¹) is not the one."""
    for sound in entry.get("sounds", []):
        tags, said = set(sound.get("tags", [])), sound.get("zh_pron", "")
        reading = said.split(" (")[0].split(",")[0].split("／")[0].strip()
        if {"Mandarin", "Pinyin"} <= tags and reading and not any(char.isdigit() for char in reading)                 and not tags & {"Sichuanese", "Chengdu", "Xi'an", "Nanjing", "Dungan", "Wiktionary-specific"}                 and all(ord(char) < 0x2E80 for char in reading):
            return reading
    return ""


def is_verb(entry: dict) -> bool:
    """A verb entry, or a character entry whose senses are verbs: the dump files 吃 as a character, glossed 'to eat'"""
    if entry.get("pos") == "verb":
        return True
    return entry.get("pos") == "character" and any(gloss.startswith("to ") for sense in entry.get("senses", [])
                                                     for gloss in sense.get("glosses", [])[:1])


def scan_built(dump: Path, targets: set) -> dict:
    """What the dump says of each target word: whether it is a verb, its pinyin, and where a simplified spelling redirects"""
    found = {}
    with dump.open(encoding="utf-8") as lines:
        for line in lines:
            if '"pos": "verb"' not in line and '"pos": "character"' not in line and '"pos": "soft-redirect"' not in line:
                continue
            entry = json.loads(line)
            word = entry.get("word")
            if word not in targets:
                continue
            if entry.get("pos") == "soft-redirect" or entry.get("redirects"):  # 听 is a character entry that only redirects to 聽
                found.setdefault(word, {}).setdefault("redirect", (entry.get("redirects") or [""])[0])
                continue
            known = found.setdefault(word, {})
            if is_verb(entry) and not known.get("verb"):
                known["verb"], known["pinyin"] = True, pinyin_of(entry) or known.get("pinyin", "")
            elif not known.get("pinyin"):
                known["pinyin"] = pinyin_of(entry)
    return found


def root_candidates(word: str) -> list:
    """What meN- may have been put in front of: menulis is tulis, memakai pakai, mengirim kirim, menyimpan simpan,
    membuat buat, mengambil ambil, melihat lihat, memperbaiki perbaiki. A vowel after the prefix may have lost a
    consonant to it (t, p, k, s) or never had one, so both are offered."""
    vowels, found = "aeiou", []
    for prefix, kept, lost in (("memper", "per", ""), ("meny", "", "s"), ("meng", "", "k"), ("mem", "", "p"),
                               ("men", "", "t"), ("me", "", ""), ("menge", "", "")):  # menge- (mengecat) is rare: last
        if not word.startswith(prefix) or len(word) <= len(prefix) + 1:
            continue
        rest = word[len(prefix):]
        if rest[0] in vowels:
            found += [kept + lost + rest] if lost else []
            found += [kept + rest] if prefix in ("meng", "memper", "me") or not lost else []
        elif prefix != "meny":
            found.append(kept + rest)
    return [text for text in dict.fromkeys(found) if len(text) > 2]


def scan_roots(dump: Path, wanted: set) -> dict:
    """Indonesian's menulis is tulis with meN- in front, and a command is the root on its own (Tulis!). The dump's own
    word comes first where it gives one (membuat is 'active of buat'); otherwise the root is the candidate the dump knows
    best: one that is a verb itself, then one whose stem without -kan or -i is a verb (dengarkan), then any word at all."""
    said, verbs, words = {}, set(), set()
    with dump.open(encoding="utf-8") as lines:
        for line in lines:
            entry = json.loads(line)
            word = entry.get("word", "")
            words.add(word)
            if entry.get("pos") != "verb":
                continue
            verbs.add(word)
            if word in wanted:
                for sense in entry.get("senses", []):
                    match = re.match(r"^active of (\S+)$", (sense.get("glosses") or [""])[0])
                    if match:
                        said.setdefault(word, match.group(1))
    roots = {}
    for word in wanted:
        if word in said:
            roots[word] = said[word]
            continue
        if word.startswith("memper"):  # memperdebatkan is perdebatkan: its stem perdebat is no word of its own
            roots[word] = word[len("mem"):]
            continue
        def known(text: str) -> int:
            stems = [text[:-len(end)] for end in ("kan", "i") if text.endswith(end)]
            return 3 if text in verbs else 2 if any(stem in verbs for stem in stems) else 1 if text in words or any(stem in words for stem in stems) else 0
        ranked = sorted(root_candidates(word), key=known, reverse=True)
        if ranked and known(ranked[0]):
            roots[word] = ranked[0]
    return roots


def read_built(recipes: dict, dump: Path, wanted: set) -> tuple[dict, list]:
    """A language whose verbs never change: each form is a pattern around the dictionary word (了 after it, 在 in front),
    and the dump is asked only whether the word is a verb, and for its pinyin. The pinyin goes into the form as a table
    shows it (吃了 (chī le)); the Play button speaks what is outside the brackets."""
    found = scan_built(dump, wanted)
    targets = {info["redirect"] for info in found.values() if info.get("redirect")} - set(found)
    if targets:  # 睡觉 redirects to 睡覺, which holds the senses and the sound
        found.update(scan_built(dump, targets))
    verbs, missing = {}, []
    separable = set(recipes.get("separable_verbs", []))
    roots = scan_roots(dump, wanted) if recipes.get("roots") else {}
    for word in sorted(wanted):
        info = found.get(word, {})
        if info.get("redirect") and not info.get("verb"):  # the traditional entry's sound, not 听's own as another word (yǐn)
            info = found.get(info["redirect"], {})
        if not info.get("verb"):
            continue
        pinyin = info.get("pinyin", "") if not recipes.get("no_reading") else ""
        forms = []
        # only a meN- verb sheds its front for a command: bertindak and duduk stay as they are
        how = recipes.get("roots", {})
        root = (how.get("of", {}).get(word) or (word if word in how.get("keep", []) else roots.get(word, ""))
                if word.startswith(tuple(how.get("prefixes", []))) else word)
        for slot in recipes["patterns"]:
            if slot.get("not_separable") and word in separable:
                continue  # 唱过歌, not 唱歌过: the particle goes inside, and the pinyin cannot be split there by rule
            if "{root}" in slot["pattern"] and not root:
                missing.append((word, slot["features"]))
                continue
            form = {"form": slot["pattern"].format(word, root=root), "features": slot["features"]}
            if pinyin:
                reading = pinyin
                if slot.get("sandhi") and next((char for char in pinyin if char in FOURTH | set("āáǎēéěīíǐōóǒūúǔǖǘǚ")), "") in FOURTH:
                    form["shown"] = f'{form["form"]} ({slot["sandhi"].format(reading)})'  # 不 is bú before a fourth tone
                else:
                    form["shown"] = f'{form["form"]} ({slot["reading"].format(reading)})'
            forms.append(form)
        if not pinyin and not recipes.get("no_reading"):
            missing.append((word, {"pinyin": "none in the dump"}))
        verbs[word] = forms
    return verbs, missing


def rekanji(forms: list, kanji: str, kana: str) -> list:
    """Forms read from a kana entry, given back their kanji: のみます is 飲みます, with のみます as its reading. The kanji
    stands for the part of the kana before the ending the two words share (飲む and のむ share む, so 飲 is の). A form that
    does not begin with that part (来る's こない) is left in kana."""
    shared = 0
    while shared < min(len(kanji), len(kana)) and kanji[-1 - shared] == kana[-1 - shared]:
        shared += 1
    kanji_part, kana_part = kanji[:len(kanji) - shared], kana[:len(kana) - shared]
    given = []
    for form in forms:
        form = dict(form)
        text = form["form"]
        if kana_part and kanji_part and text.startswith(kana_part) and form["features"] not in ({"form": "helper"}, {"form": "partner"}):
            form["form"] = kanji_part + text[len(kana_part):]
            form["shown"] = f'{form["form"]} ({text})'
        given.append(form)
    return given


def read(recipes: dict, dump: Path, wanted: set) -> tuple[dict, list]:
    skip = set(recipes["skip_tags"])
    verbs, missing, redirects = {}, [], {}
    with dump.open(encoding="utf-8") as lines:
        for line in lines:
            if '"pos": "verb"' not in line:
                if recipes.get("follow_redirects") and '"pos": "soft-redirect"' in line:  # 飲む is only a pointer to のむ
                    entry = json.loads(line)
                    if entry.get("word") in wanted and entry.get("redirects"):
                        redirects.setdefault(entry["word"], entry["redirects"][0])
                continue
            entry = json.loads(line)
            word = entry.get("word")
            if word not in wanted and recipes.get("suru") and word + "する" in wanted:
                word += "する"  # 勉強する is filed under 勉強, whose table is of 勉強する
            if word not in wanted or word in verbs or entry.get("pos") != "verb":
                continue
            forms = [(plain(recipes, text), tags)
                     for text, tags in table(entry, skip, recipes.get("all_tables", False), tuple(recipes.get("skip_prefixes", [])) or ("\0",))]
            if not forms:
                continue  # a form-of entry, or an entry with no table: the real one comes later in the dump
            found = []
            # Korean lists each conjugated form with its romanization, which a table shows as the reading: 가요 (gayo)
            roman = {}
            for item in entry.get("forms", []):
                if item.get("source") == "conjugation" and item.get("roman"):
                    roman.setdefault(plain(recipes, item["form"]), item["roman"])
            if recipes.get("dictionary_is_word"):  # the table has no dictionary form: the entry's own word is it (가다)
                spelled = next((item["form"] for item in entry.get("forms", []) if "romanization" in item.get("tags", [])), "")
                found.append({"form": word, "features": {"form": "infinitive"}, "reading": spelled})
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
                if "compound" in slot or "partner" in slot or "derive" in slot:
                    continue  # built below, once the helper verbs, the partners and the forms they come from are read
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
                    if recipes.get("reading_kana"):  # Japanese lists each form three times: 食べます, たべます, tabemasu
                        found[-1]["reading"] = next((text for text in match if is_kana(text)), "")
                    elif recipes.get("reading_roman"):  # Hindi gives some forms' romanization as a form of its own: गए, then gae
                        found[-1]["reading"] = roman.get(match[0], "") or next((text for text in match if is_romanized(text)), "")
                    if slot.get("split") and " " in match[0]:  # a two-word form the dictionary gives whole: bin gegangen
                        found[-1]["helper"], found[-1]["part"] = match[0].split(" ", 1)
                    if slot.get("single_word"):  # Hindi gives खाऊँगा and खाया हूँगा the same tags: the one-word form is the future
                        single = [text for text in match if " " not in text and not text.isascii()]
                        if single:
                            found[-1]["form"] = single[0]
                            if recipes.get("reading_roman"):
                                found[-1]["reading"] = roman.get(single[0], "")
                    if "shown" in slot:  # the form as a table shows it: the dictionary lists tengas, and nobody says it without its no
                        first, _, rest = match[0].partition(" ")
                        # a verb that splits in two keeps its second half for the end: kommen Sie an, not kommen an Sie
                        said = slot["shown"].format(first) + " " + rest if rest and slot.get("inside") else slot["shown"].format(match[0])
                        found[-1]["shown"] = elide(recipes, said)
                else:
                    missing.append((word, slot["features"]))
            for slot in recipes["slots"]:
                if "derive" in slot:  # a form made from another by its ending: 食べます to 食べました, 食べて to 食べています
                    source = next((form for form in found if form["features"] == slot["derive"]["from"]), None)
                    made = derived(source["form"], slot["derive"]["suffix"]) if source else None
                    if made:
                        found.append({"form": made, "features": slot["features"]})
                        if slot["derive"].get("second_word_helps") and " " in made:
                            # जाता हूँ and जा रहा हूँ: the first word carries the ending, the second is the helper the board lights
                            words = made.split(" ")
                            found[-1]["part"], found[-1]["helper"] = words[0], words[1]
                        if source.get("reading"):  # a romanized reading changes by its own letters: sipsida to seyo
                            found[-1]["reading"] = derived(source["reading"], slot["derive"].get("reading_suffix", slot["derive"]["suffix"])) or ""
                    else:
                        missing.append((word, slot["features"]))
            for form in found:  # a recipe's modern spelling for the dump's older one: Hindi's खायी and दिये are खाई and दिए
                for spelled, modern, said, said_now in recipes.get("respell", []):
                    form["form"] = re.sub(spelled, modern, form["form"])
                    if form.get("reading"):
                        form["reading"] = re.sub(said, said_now, form["reading"])
                    for key in ("part", "helper", "shown"):
                        if key in form:
                            form[key] = re.sub(spelled, modern, form[key])
            ergative = recipes.get("ergative", {})
            if word in ergative.get("verbs", []) or word.rpartition(" ")[2] in ergative.get("verbs", []) and " " in word:
                # Hindi's खाना takes ने on the one doing it in the past, and the verb then matches what was done, not who
                # did it: मैंने खाया and उसने खाया alike. So its past is one form, not a column that changes by who.
                past = [form for form in found if form["features"].get("when") == ergative["when"] and "who" in form["features"]
                        and len(form["features"]) == 2]
                if past:
                    first = next((form for form in past if form["features"]["who"] == "i"), past[0])
                    found = [form for form in found if form not in past]
                    found.append({**first, "features": dict(ergative["features"])})
            for form in found:  # the reading, beside the form as a table shows it: 食べます (たべます). The Play button leaves it out.
                reading = form.pop("reading", "")
                if reading and reading != form["form"] and "shown" not in form:
                    form["shown"] = f'{form["form"]} ({reading})'
            verbs[word] = found
    unread = {target for word, target in redirects.items() if word not in verbs}
    if unread:
        read_too, _ = read({**recipes, "follow_redirects": False}, dump, unread)
        for word, target in redirects.items():
            if word not in verbs and target in read_too:
                verbs[word] = rekanji(read_too[target], word, target)
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
    if recipes.get("built"):
        verbs, missing = read_built(recipes, dump, wanted)
    else:
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
