"""Turns each grid-<language>.json into an import file for import_entries.php: one lesson a cell, four words a lesson.

    python make_grid_import.py <output directory> [language ...]

Writes grid-<language>.tsv. Each lesson is filed under its language's Patterns or Sentence Building type, which the
restructure made, and sorts ahead of the type's other lessons in the spine's order. Each word carries what the lesson
board draws from:

    WordPiece   one row for each piece of the translation that does the job: its place, its text, and its role
                (the cell's first strategy, unless the mark names its own)
    WordForm    the word it is a form of and what the form says, and the row it was built from. That row is named
                by its Entry_Identifier, as "identifier:<name>", because its id does not exist until the import runs.

A word's first row makes the entry, and a further row with the same Entry_Identifier adds each piece.

Run each file as a preview first:
    import_entries.php earthfluent.com --file=grid-spanish.tsv --user=ADMIN --limit=0
"""
import csv
import json
import re
import sys
import unicodedata
from pathlib import Path

HERE = Path(__file__).resolve().parent
TYPES = {"patterns": "Patterns", "sentence-building": "Sentence Building"}
HEADER = [
    "Entry_Identifier", "Entry_Title", "Entry_Code", "Entry_ListTitleSortKey", "Entry_ParentPath", "Entry_ParentIdentifier",
    "Description_Description", "Description_Language", "EntryTranslation_Title", "EntryTranslation_Language",
    "WordForm_BuiltFromEntryid", "WordForm_Language", "WordForm_BaseWord", "WordForm_Features", "WordForm_Source",
    "WordPiece_Language", "WordPiece_Position", "WordPiece_Piece", "WordPiece_Role",
]


def slug(title: str) -> str:
    plain = unicodedata.normalize("NFKD", title).encode("ascii", "ignore").decode("ascii")
    return re.sub(r"[^a-z0-9]+", "-", plain.lower()).strip("-")


def row(**fields) -> list:
    return [fields.get(name, "") for name in HEADER]


def pieces(cell: dict, marks: list) -> list[tuple[str, str]]:
    # A mark is its text, or {"text": ..., "role": ...} where one cell uses two strategies
    return [(mark["text"], mark["role"]) if isinstance(mark, dict) else (mark, cell["strategy"][0]) for mark in marks]


def rows_for(grid: dict, spine: dict) -> list:
    language, code = grid["language"], grid["translation_language"]
    jobs = {job["code"]: (order, job) for order, job in enumerate(spine["jobs"], 1)}
    rows = [HEADER]
    for cell in grid["cells"]:
        order, job = jobs[cell["job"]]
        title = f'{TYPES[job["type"]]} - {job["name"]}, Part 1'
        lesson = f'grid-{cell["job"]}'
        rows.append(row(Entry_Identifier=lesson, Entry_Title=title, Entry_Code=slug(title), Entry_ListTitleSortKey="%02d" % order,
                        Entry_ParentPath=f'/{language}/{job["type"]}/', Description_Description=cell["description"], Description_Language="en"))
        for number, word in enumerate(cell["rows"], 1):
            name = f"{lesson}-{number}"
            form = {}
            if "from" in word or "base" in word:
                features = ";".join(f"{key}={value}" for key, value in word.get("features", {}).items())
                form = dict(WordForm_BuiltFromEntryid=f'identifier:{lesson}-{word["from"]}' if "from" in word else "",
                            WordForm_Language=code, WordForm_BaseWord=word.get("base", ""), WordForm_Features=features, WordForm_Source="grid")
            rows.append(row(Entry_Identifier=name, Entry_Title=word["english"], Entry_ParentIdentifier=lesson, Entry_ListTitleSortKey="%02d" % number,
                            EntryTranslation_Title=word["foreign"], EntryTranslation_Language=code, **form))
            for position, (text, role) in enumerate(pieces(cell, word.get("mark", [])), 1):
                rows.append(row(Entry_Identifier=name, WordPiece_Language=code, WordPiece_Position=str(position), WordPiece_Piece=text, WordPiece_Role=role))
    return rows


def main() -> None:
    out = Path(sys.argv[1])
    spine = json.loads((HERE / "spine.json").read_text(encoding="utf-8"))
    names = sys.argv[2:] or [path.stem.removeprefix("grid-") for path in sorted(HERE.glob("grid-*.json"))]
    for name in names:
        grid = json.loads((HERE / f"grid-{name}.json").read_text(encoding="utf-8"))
        rows = rows_for(grid, spine)
        with open(out / f"grid-{name}.tsv", "w", encoding="utf-8", newline="") as file:
            csv.writer(file, delimiter="\t", lineterminator="\n", quoting=csv.QUOTE_MINIMAL).writerows(rows)
        lessons = sum(1 for line in rows[1:] if line[4])
        made = sum(1 for line in rows[1:] if line[8])
        print("%-10s %2d lessons  %3d words  %3d pieces" % (name, lessons, made, sum(1 for line in rows[1:] if line[17])))


if __name__ == "__main__":
    main()
