"""Brings lessons that are already on the site into line with a regenerated lessons-<language>.json.

    python make_lessons_update.py spanish <the lessons file as the site has it> <output directory>

make_grid_import.py makes lessons; it cannot change one, because a row with no id is a new entry. This compares the
lessons file the site was imported from (usually `git show <commit>:.../lessons-spanish.json > old.json`) with the one
beside this script, and writes lessons-update-<language>.sql: a query that finds, in whichever database it is run
against, the ids of what differs. Fumiko's ids and prod's are not promised to match, so each is asked for its own.

    mysql -N -B earthfluent < lessons-update-spanish.sql > rows
    (printf '<the header below>\\n'; cat rows) > lessons-update-spanish.tsv
    import_entries.php earthfluent.com --file=lessons-update-spanish.tsv --user=ADMIN --limit=0 [--apply --keep-cache]

What it carries: a word's English, its translation, a lit piece that now says something else (habe to bin), a lesson's
description, the row a word was built from, and a lesson that is gone (its words are deleted, then the lesson). A row
that lost lit pieces from its end (я буду находить to я найду: the helper word goes) has them deleted by a second file,
pieces-delete-<language>.sql, imported with --table=WordPiece, because the importer deletes one table's records per
file. Any other row that gained or lost a lit piece is not carried: it says so and is left out.

    (printf 'Entry_id\\tWordPiece_id\\tDelete\\n'; mysql -N -B earthfluent < pieces-delete-russian.sql) > pieces-delete-russian.tsv
    import_entries.php earthfluent.com --file=pieces-delete-russian.tsv --table=WordPiece --user=ADMIN --limit=0 [--apply --keep-cache]
"""
import json
import sys
from pathlib import Path

from make_grid_import import TYPES, slug

HERE = Path(__file__).resolve().parent
HEADER = ["Entry_id", "Entry_Title", "EntryTranslation_id", "EntryTranslation_Title", "Description_id", "Description_Description",
          "WordPiece_id", "WordPiece_Piece", "WordForm_id", "WordForm_BuiltFromEntryid", "Delete"]


def q(text: str) -> str:
    return "'" + text.replace("\\", "\\\\").replace("'", "''") + "'"


def select(**fields) -> str:
    return ", ".join(fields.get(name, "''") for name in HEADER)


def main() -> int:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    language, old_path, out = sys.argv[1], Path(sys.argv[2]), Path(sys.argv[3])
    jobs = {job["code"]: job for job in json.loads((HERE / "spine.json").read_text(encoding="utf-8"))["jobs"]}
    old = {(cell["job"], cell["part"]): cell for cell in json.loads(old_path.read_text(encoding="utf-8"))["cells"]}
    new = {(cell["job"], cell["part"]): cell for cell in json.loads((HERE / f"lessons-{language}.json").read_text(encoding="utf-8"))["cells"]}
    selects, drops, skipped = [], [], []
    counts = {"english": 0, "translation": 0, "description": 0, "pieces": 0, "pieces gone": 0, "built from": 0, "lessons gone": 0}
    for key, was in old.items():
        job = jobs[key[0]]
        code = slug(f'{TYPES[job["type"]]} - {job["name"]}, Part {key[1]}')
        lesson = ("(SELECT l.id FROM Entry l JOIN Assignment a ON a.Childid = l.id JOIN Entry t ON t.id = a.Parentid AND t.Code = %s "
                  "JOIN Assignment ta ON ta.Childid = t.id JOIN Entry k ON k.id = ta.Parentid AND k.Code = %s WHERE l.Code = %s)"
                  % (q(job["type"]), q(language), q(code)))
        words = f"FROM Entry w JOIN Assignment wa ON wa.Childid = w.id WHERE wa.Parentid = {lesson}"
        now = new.get(key)
        if now is None:
            selects.append(f"SELECT {select(Entry_id='w.id', Delete=q('Y'))} {words}")
            selects.append(f"SELECT {select(Entry_id='l.id', Delete=q('Y'))} FROM Entry l WHERE l.id = {lesson}")
            counts["lessons gone"] += 1
            continue
        if was["description"] != now["description"]:
            selects.append(f"SELECT {select(Entry_id='d.Entryid', Description_id='d.id', Description_Description=q(now['description']))} "
                           f"FROM Description d WHERE d.Entryid = {lesson}")
            counts["description"] += 1
        for number, (before, after) in enumerate(zip(was["rows"], now["rows"]), 1):
            word = f"{words} AND w.ListTitleSortKey = '{number:02d}'"
            pieces_before = [mark["text"] if isinstance(mark, dict) else mark for mark in before.get("mark", [])]
            pieces_after = [mark["text"] if isinstance(mark, dict) else mark for mark in after.get("mark", [])]
            if len(pieces_before) != len(pieces_after):
                if len(pieces_after) > len(pieces_before) or pieces_before[:len(pieces_after)] != pieces_after:
                    skipped.append(f"{code}, row {number}: it has a different number of lit pieces, which this does not carry")
                    continue
                for position in range(len(pieces_after) + 1, len(pieces_before) + 1):  # the pieces it lost from its end
                    drops.append(f"SELECT p.Entryid, p.id, 'Y' FROM WordPiece p WHERE p.Position = {position} AND p.Entryid = (SELECT w.id {word})")
                    counts["pieces gone"] += 1
            if before.get("from") != after.get("from"):  # нашёл is not built from нахожу: it is its partner's
                built = (q("0") if "from" not in after else
                         f"(SELECT b.id FROM Entry b JOIN Assignment ba ON ba.Childid = b.id WHERE ba.Parentid = {lesson} "
                         f"AND b.ListTitleSortKey = '{after['from']:02d}')")
                selects.append(f"SELECT {select(Entry_id='f.Entryid', WordForm_id='f.id', WordForm_BuiltFromEntryid=built)} "
                               f"FROM WordForm f WHERE f.Entryid = (SELECT w.id {word})")
                counts["built from"] += 1
            for position, (piece_before, piece_after) in enumerate(zip(pieces_before, pieces_after), 1):
                if piece_before != piece_after:  # habe to bin: the same piece, at the same place, saying something else
                    selects.append(f"SELECT {select(Entry_id='p.Entryid', WordPiece_id='p.id', WordPiece_Piece=q(piece_after))} "
                                   f"FROM WordPiece p WHERE p.Position = {position} AND p.Entryid = (SELECT w.id {word})")
                    counts["pieces"] += 1
            if before["english"] != after["english"]:
                selects.append(f"SELECT {select(Entry_id='w.id', Entry_Title=q(after['english']))} {word}")
                counts["english"] += 1
            if before["foreign"] != after["foreign"]:
                selects.append(f"SELECT {select(Entry_id='x.Entryid', EntryTranslation_id='x.id', EntryTranslation_Title=q(after['foreign']))} "
                               f"FROM EntryTranslation x WHERE x.Entryid = (SELECT w.id {word})")
                counts["translation"] += 1
    added = sorted(set(new) - set(old))
    path = out / f"lessons-update-{language}.sql"
    path.write_text("SET NAMES utf8mb4;\n" + "\nUNION ALL\n".join(selects) + ";\n" if selects else "", encoding="utf-8", newline="\n")
    (out / f"pieces-delete-{language}.sql").write_text("SET NAMES utf8mb4;\n" + "\nUNION ALL\n".join(drops) + ";\n" if drops else "",
                                                       encoding="utf-8", newline="\n")
    print(f"{language}: " + ", ".join(f"{name} {count}" for name, count in counts.items()) + f"; {len(selects)} queries")
    for line in skipped:
        print("   left out: " + line)
    if added:
        print(f"   {len(added)} lessons are new and are make_grid_import.py's to make: {added[:6]}")
    print("header: " + "\\t".join(HEADER))
    return 0


if __name__ == "__main__":
    sys.exit(main())
