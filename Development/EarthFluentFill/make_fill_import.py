"""EarthFluent's missing lessons: one import file per course, for import_entries.php.

Every course should teach the lessons Italian's (the master's) does, with the same words. The AI Workers' fill job
(E:\\AIWorkers\\earthfluent_fill) listed each master word a course lacks, translated it, and wrote it to
fill/<lang>.fill.txt; moves.tsv lists words a course has but files in another lesson than the master does. This turns
them into import rows, placed by what the database holds now (tree.tsv, from tree.sql), so it can be run against
Fumiko's copy and then again against prod's, whose new ids differ:

    mysql -N -B --default-character-set=utf8mb4 earthfluent < tree.sql > tree.tsv
    python make_fill_import.py tree.tsv <output directory> [language code ...]

A course is only written once its review file (review/<lang>.tsv) says "# read: all" on a line of its own: Claude
read every record, and the file lists only those that changed, as master entry, verdict (fix or drop), translation,
and why. Every other record in the fill file is confirmed as the model gave it.

What it writes for a course:
    - a lesson the course lacks, made under its type with the master's title, code and description (the description's
      closing "in Italian!" said in the course's language), and its words beneath it
    - a word a lesson lacks, filed under that lesson by its path, with the master word's current title and code
    - a move: the course's own entry for the word, filed under the master's lesson by Entry_id
A word already in its lesson (by English title, loosely) is left alone, so a second run makes nothing twice.

Run each file as a preview first:
    import_entries.php earthfluent.com --file=fill-korean.tsv --user=ADMIN --limit=0
"""
import collections
import csv
import re
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
FILL = Path(r"E:\AIWorkers\earthfluent_fill\fill")
MASTER = "italian"
COURSES = {"es": "spanish", "fr": "french", "it": "italian", "de": "german", "ja": "japanese", "zh": "chinese",
           "hi": "hindi", "id": "indonesian", "nl": "dutch", "pl": "polish", "pt": "portuguese", "ru": "russian",
           "ko": "korean"}
TYPES = {"Nouns": "nouns", "Verbs": "verbs", "Adjectives": "adjectives", "Phrases": "phrases", "Culture": "culture",
         "Entertainment": "entertainment"}
HEADER = ["Entry_Identifier", "Entry_id", "Entry_Title", "Entry_Code", "Entry_ParentPath", "Entry_ParentIdentifier",
          "Description_Description", "Description_Language", "EntryTranslation_Title", "EntryTranslation_Language"]


def plain(title: str) -> str:
    # as the fill job compares them: case, spacing and the "caf退" mangling don't make a different word
    return re.sub(r"\s+", " ", title.replace("退", "é")).strip().casefold()


def row(**fields) -> list:
    return [fields.get(name, "") for name in HEADER]


class Tree:
    """tree.tsv: course, type code, lesson id, lesson title, lesson code, lesson description, word id, title, code, translations."""

    def __init__(self, path: Path):
        self.lessons = collections.defaultdict(dict)  # course -> {plain lesson title: lesson}
        self.words = {}  # word id -> (title, code), for the master's words
        for line in open(path, encoding="utf-8"):
            course, kind, lesson_id, title, code, description, word_id, word, word_code, _ = line.rstrip("\n").split("\t")
            lesson = self.lessons[course].setdefault(plain(title), {
                "id": lesson_id, "title": title, "code": code, "type": kind, "description": description,
                "words": {}, "codes": set()})
            if word_id:
                lesson["words"][plain(word)] = int(word_id)
                lesson["codes"].add(word_code)
                self.words[int(word_id)] = (word, word_code)

    def lesson(self, course: str, title: str):
        return self.lessons[course].get(plain(title))


def records(lang: str) -> list[dict]:
    text = (FILL / f"{lang}.fill.txt").read_text(encoding="utf-8")
    found = []
    for block in text.split("\n\n"):
        fields = dict(line.split(": ", 1) for line in block.splitlines() if ": " in line and not line.startswith("#"))
        if "master entry" in fields:
            found.append(fields)
    return found


def review(lang: str):
    path = HERE / "review" / f"{lang}.tsv"
    if not path.exists():
        return None
    verdicts = {}
    lines = open(path, encoding="utf-8").read().splitlines()
    if "# read: all" not in lines:
        return None  # its doubtful records may be read, but not yet the rest
    for line in lines:
        if line.strip() and not line.startswith("#"):
            entry, verdict, translation, why = (line.rstrip("\n").split("\t") + ["", ""])[:4]
            if verdict not in ("fix", "drop"):
                raise SystemExit(f"{path}: entry {entry} has verdict {verdict!r}; only fix and drop are listed")
            verdicts[int(entry)] = (verdict, translation.strip())
    return verdicts


def moves(lang: str) -> list[tuple[str, str, str]]:
    # (master lesson, english, the lesson the course files it in now)
    found = []
    for line in open(FILL / "moves.tsv", encoding="utf-8"):
        parts = line.rstrip("\n").split("\t")
        if not line.startswith("#") and parts[0] == lang:
            found.append((parts[1], parts[3], parts[5]))
    return found


def course_rows(lang: str, tree: Tree, verdicts: dict, problems: list) -> tuple[list, collections.Counter]:
    course = COURSES[lang]
    language = course.capitalize()
    rows, made, counts = [HEADER], {}, collections.Counter()

    def lesson_parent(title: str) -> dict | None:
        # where a word for this master lesson goes: the course's lesson, or one this file makes
        have = tree.lesson(course, title)
        if have:
            return dict(Entry_ParentPath=f'/{course}/{have["type"]}/{have["code"]}/')
        if plain(title) not in made:
            # the master's lesson, or where the master has a hole of its own (Phrases - Travel, Part 29), the first
            # course that has it
            source = next(((other, tree.lesson(other, title)) for other in [MASTER, *COURSES.values()] if tree.lesson(other, title)), None)
            kind = TYPES.get(title.split(" - ")[0])
            if not source or not kind:
                problems.append(f"{lang}: no course has a lesson {title!r} to copy, or it has no type")
                return None
            other, master = source
            name = f"fill-lesson-{len(made) + 1}"
            description = re.sub(rf" in {other.capitalize()}!$", f" in {language}!", master["description"])
            rows.append(row(Entry_Identifier=name, Entry_Title=master["title"], Entry_Code=master["code"],
                            Entry_ParentPath=f"/{course}/{kind}/", Description_Description=description,
                            Description_Language="en" if description else ""))
            made[plain(title)] = name
            counts["lessons"] += 1
        return dict(Entry_ParentIdentifier=made[plain(title)])

    def present(title: str, english: str) -> bool:
        have = tree.lesson(course, title)
        return bool(have) and plain(english) in have["words"]

    # A word the course already has in another lesson is moved there, never translated a second time: the fill job
    # listed three Dutch words (nutrient, health, healthy) as both
    moving = {(plain(lesson), plain(english)) for lesson, english, _ in moves(lang)}
    for number, record in enumerate(records(lang), 1):
        entry = int(record["master entry"])
        if (plain(record["lesson"]), plain(record["english"])) in moving:
            counts["moved instead"] += 1
            continue
        verdict, translation = verdicts.get(entry, ("keep", record.get("proposed", "").strip()))
        if verdict == "drop":
            counts["dropped"] += 1
            continue
        if entry not in tree.words:
            problems.append(f"{lang}: master entry {entry} ({record['english']}) is no longer in Italian's tree")
            continue
        title, code = tree.words[entry]
        if present(record["lesson"], title):
            counts["already there"] += 1
            continue
        if not translation:
            problems.append(f"{lang}: master entry {entry} ({title}) has no translation")
            continue
        have = tree.lesson(course, record["lesson"])
        if have and code in have["codes"]:
            problems.append(f"{lang}: {record['lesson']} already has a word coded {code!r}; {title} left out")
            continue
        parent = lesson_parent(record["lesson"])
        if parent is None:
            continue
        rows.append(row(Entry_Identifier=f"fill-word-{number}", Entry_Title=title, Entry_Code=code,
                        EntryTranslation_Title=translation, EntryTranslation_Language=lang, **parent))
        counts["fixed" if verdict == "fix" else "words"] += 1

    for master_lesson, english, now_in in moves(lang):
        if present(master_lesson, english):
            counts["already there"] += 1
            continue
        source = tree.lesson(course, now_in)
        word_id = source and source["words"].get(plain(english))
        if not word_id:
            problems.append(f"{lang}: no {english!r} in {now_in!r} to move")
            continue
        parent = lesson_parent(master_lesson)
        if parent is None:
            continue
        rows.append(row(Entry_id=str(word_id), **parent))
        counts["moved"] += 1
    return rows, counts


def main() -> None:
    tree, out = Tree(Path(sys.argv[1])), Path(sys.argv[2])
    langs = sys.argv[3:] or list(COURSES)
    problems = []
    for lang in langs:
        verdicts = review(lang)
        if verdicts is None:
            print(f"{COURSES[lang]:<11} not reviewed yet (no review/{lang}.tsv): skipped")
            continue
        rows, counts = course_rows(lang, tree, verdicts, problems)
        with open(out / f"fill-{COURSES[lang]}.tsv", "w", encoding="utf-8", newline="") as file:
            csv.writer(file, delimiter="\t", lineterminator="\n", quoting=csv.QUOTE_MINIMAL).writerows(rows)
        print(f"{COURSES[lang]:<11} " + "  ".join(f"{n} {k}" for k, n in sorted(counts.items())))
    print(f"problems: {len(problems)}")
    for problem in problems:
        print("   " + problem)


if __name__ == "__main__":
    main()
