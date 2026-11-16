"""Brings the verb forms already on the site into line with a regenerated forms-<language>.json.

    python make_forms_update.py german <the forms file as the site has it> <output directory>

make_forms_import.py adds forms; it cannot change one. This compares the forms file the site was imported from (usually
`git show <commit>:.../forms-german.json > old.json`) with the one beside this script, and writes
forms-update-<language>.sql: a query that finds, in whichever database it is run against, each WordForm row whose form
changed, by its verb, what it says, and the form it holds now. Fumiko's ids and prod's differ, so each finds its own.

    (printf 'Entry_id\\tWordForm_id\\tWordForm_FormText\\n'; mysql -N -B earthfluent < forms-update-german.sql) > forms-update-german.tsv
    import_entries.php earthfluent.com --file=forms-update-german.tsv --user=ADMIN --limit=0 [--apply --keep-cache]

A form that is new, or gone, is not carried: new ones are make_forms_import.py's, and none has been removed so far.
"""
import json
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent


def q(text: str) -> str:
    return "'" + text.replace("\\", "\\\\").replace("'", "''") + "'"


def shown(form: dict) -> str:
    return form.get("shown", form["form"])


def features(form: dict) -> str:
    return ";".join(f"{key}={value}" for key, value in form["features"].items())


def main() -> int:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    language, old_path, out = sys.argv[1], Path(sys.argv[2]), Path(sys.argv[3])
    old = json.loads(old_path.read_text(encoding="utf-8"))["verbs"]
    data = json.loads((HERE / f"forms-{language}.json").read_text(encoding="utf-8"))
    code, new = data["translation_language"], data["verbs"]
    selects, verbs = [], set()
    for verb, forms in new.items():
        was = {features(form): shown(form) for form in old.get(verb, [])}
        for form in forms:
            if form["features"] in ({"form": "helper"}, {"form": "partner"}):
                continue  # bookkeeping, never imported
            before = was.get(features(form))
            if before is not None and before != shown(form):
                selects.append(f"SELECT Entryid, id, {q(shown(form))} FROM WordForm WHERE Language = {q(code)} AND Source = 'wiktionary' "
                               f"AND BaseWord = {q(verb)} AND Features = {q(features(form))} AND FormText = {q(before)}")
                verbs.add(verb)
    path = out / f"forms-update-{language}.sql"
    path.write_text("SET NAMES utf8mb4;\n" + "\nUNION ALL\n".join(selects) + ";\n" if selects else "", encoding="utf-8", newline="\n")
    print(f"{language}: {len(selects)} forms changed, in {len(verbs)} verbs")
    return 0


if __name__ == "__main__":
    sys.exit(main())
