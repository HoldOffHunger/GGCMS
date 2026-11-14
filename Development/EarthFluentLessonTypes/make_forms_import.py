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
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
HEADER = ["Entry_id", "WordForm_Language", "WordForm_BaseWord", "WordForm_FormText", "WordForm_Features", "WordForm_Source"]


def main() -> int:
    language, words, out = sys.argv[1], Path(sys.argv[2]), Path(sys.argv[3])
    forms = json.loads((HERE / f"forms-{language}.json").read_text(encoding="utf-8"))
    code, verbs = forms["translation_language"], forms["verbs"]
    rows, entries, without = [HEADER], 0, 0
    for line in words.read_text(encoding="utf-8").splitlines():
        entry, _, translation = line.partition("\t")
        base = translation.strip().lower()
        if base not in verbs:
            without += 1
            continue
        entries += 1
        for form in verbs[base]:
            features = ";".join(f"{key}={value}" for key, value in form["features"].items())
            rows.append([entry, code, base, form.get("shown", form["form"]), features, "wiktionary"])
    with open(out / f"forms-{language}.tsv", "w", encoding="utf-8", newline="") as file:
        csv.writer(file, delimiter="\t", lineterminator="\n", quoting=csv.QUOTE_MINIMAL).writerows(rows)
    print(f"{language}: {entries} entries get their forms, {len(rows) - 1} forms; {without} words are not verbs in the forms file")
    return 0


if __name__ == "__main__":
    sys.exit(main())
