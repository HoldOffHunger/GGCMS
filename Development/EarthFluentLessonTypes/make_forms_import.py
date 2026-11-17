"""Turns forms-<language>.json into an import file that hangs each verb's forms on the verb's own entry, as WordForm rows.

    python make_forms_import.py spanish <words.tsv> <output directory>

words.tsv is the course's words as the database has them, one a line: the entry's id, a tab, its translation.

    select w.id, x.Title from Entry w
    join EntryTranslation x on x.Entryid = w.id and x.Language = 'es'
    join Assignment wa on wa.Childid = w.id      -- the lesson
    join Assignment la on la.Childid = wa.Parentid
    join Entry t on t.id = la.Parentid and t.Code = 'verbs'
    join Assignment ta on ta.Childid = t.id
    join Entry c on c.id = ta.Parentid and c.Code = 'spanish'

A word whose translation is a verb in the forms file gets every form: FormText holds the form, BaseWord the
dictionary word, Features what the form says. A word that is not there (a noun filed under Verbs, a phrase) gets
nothing and is counted. Writes forms-<language>.tsv for import_entries.php; preview it first.
"""
import csv
import json
import re
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
HEADER = ["Entry_id", "WordForm_Language", "WordForm_BaseWord", "WordForm_FormText", "WordForm_Features", "WordForm_Source"]


def main() -> int:
    language, words, out = sys.argv[1], Path(sys.argv[2]), Path(sys.argv[3])
    forms = json.loads((HERE / f"forms-{language}.json").read_text(encoding="utf-8"))
    code, verbs = forms["translation_language"], forms["verbs"]
    recipes_file = HERE / f"forms-recipes-{language}.json"
    recipes = json.loads(recipes_file.read_text(encoding="utf-8")) if recipes_file.exists() else {}
    light = recipes.get("light_verbs", {})
    rows, entries, without = [HEADER], 0, 0
    for line in words.read_text(encoding="utf-8").splitlines():
        entry, _, translation = line.partition("\t")
        base = translation.strip().lower()
        front, _, last = base.rpartition(" ")
        if base in verbs:
            own, front = verbs[base], ""
        elif front and last in light.get("verbs", []) and front.split(" ")[0] not in light.get("not_after", []) and last in verbs:
            own = verbs[last]  # Hindi's बर्बाद करना changes as करना does, the word in front staying put: बर्बाद करता हूँ
        else:
            without += 1
            continue
        entries += 1
        for form in own:
            if form["features"] == {"form": "helper"} or form["features"].get("internal") == "yes":
                continue  # what other forms are built from (the helper a verb takes, Korean's 갈 and 가고) is in them, not shown
            features = ";".join(f"{key}={value}" for key, value in form["features"].items())
            text = form.get("shown", form["form"])
            if front:  # the light verb's reading is not the whole compound's, so it comes off
                text = front + " " + re.sub(r"\s*\([^)]*\)$", "", text)
            rows.append([entry, code, base, text, features, "wiktionary"])
    with open(out / f"forms-{language}.tsv", "w", encoding="utf-8", newline="") as file:
        csv.writer(file, delimiter="\t", lineterminator="\n", quoting=csv.QUOTE_MINIMAL).writerows(rows)
    print(f"{language}: {entries} entries get their forms, {len(rows) - 1} forms; {without} words are not verbs in the forms file")
    return 0


if __name__ == "__main__":
    sys.exit(main())
