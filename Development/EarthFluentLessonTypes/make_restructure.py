"""EarthFluent lesson types: one import file per course, for import_entries.php.

Each file makes the ten lesson types under its language, with the line the
language page prints on the type's card and the order the cards come in, then
moves every existing lesson beneath its type by Entry_id.  A lesson's type is
the part of its title before " - ".  Anything at lesson level without one is a
stray: listed on stdout, left where it is.

A pilot file (pilot-<language>.json, beside this script) adds that course's
first lessons for the four new types.

    mysql -N earthfluent -e "<the query below>" > lessons.tsv
    python make_restructure.py lessons.tsv <output directory> [--no-pilot]

--no-pilot leaves the pilot lessons out, for a run that ships before they are checked.

    select l.Code, e.id, e.Title
    from Entry l
    join Assignment la on la.Childid = l.id and la.Parentid = 1
    join Assignment a on a.Parentid = l.id
    join Entry e on e.id = a.Childid

Run each file as a preview first:
    import_entries.php earthfluent.com --file=restructure-spanish.tsv --user=ADMIN --limit=0
"""
import collections, csv, json, os, re, sys, unicodedata

LESSONS, OUT = sys.argv[1], sys.argv[2]
PILOT = '--no-pilot' not in sys.argv[3:]
HERE = os.path.dirname(os.path.abspath(__file__))

LANGUAGES = {
    'spanish': 'Spanish', 'french': 'French', 'italian': 'Italian', 'german': 'German',
    'japanese': 'Japanese', 'chinese': 'Chinese', 'hindi': 'Hindi', 'indonesian': 'Indonesian',
    'dutch': 'Dutch', 'polish': 'Polish', 'portuguese': 'Portuguese', 'russian': 'Russian',
    'korean': 'Korean',
}

# In learning order.  The description is Ben's approved wording of 12 November 2026.
TYPES = [
    ('sounds-and-script', 'Sounds and Script', 'How {Language} sounds and how it is written: the letters, the marks, and the music of it.'),
    ('nouns', 'Nouns', 'The names of things: people, objects, concepts and ideas.'),
    ('verbs', 'Verbs', 'What people do: moving, making, thinking and feeling.'),
    ('adjectives', 'Adjectives', 'What things are like: how they look, how good they are, and how many.'),
    ('patterns', 'Patterns', 'How {Language} words change: who is doing it, when, and how many.'),
    ('phrases', 'Phrases', 'Whole things to say, ready to use: at the table, on the road, at work, among friends.'),
    ('sentence-building', 'Sentence Building', 'How a {Language} sentence is put together, one piece at a time.'),
    ('sentences-in-use', 'Sentences in Use', 'Sentences side by side, so you can spot what changed.'),
    ('culture', 'Culture', 'Capitals, currencies, peoples and languages of the world, said in {Language}.'),
    ('entertainment', 'Entertainment', 'Film, music, games and sport: the fun words.'),
]
TYPE_BY_TITLE = {title: code for code, title, _ in TYPES}

HEADER = [
    'Entry_Identifier', 'Entry_id', 'Entry_Title', 'Entry_Code', 'Entry_ListTitleSortKey',
    'Entry_ParentPath', 'Entry_ParentIdentifier',
    'Description_Description', 'Description_Language',
    'EntryTranslation_Title', 'EntryTranslation_Language',
]

def slug(title):
    plain = unicodedata.normalize('NFKD', title).encode('ascii', 'ignore').decode('ascii')
    return re.sub(r'[^a-z0-9]+', '-', plain.lower()).strip('-')

def row(**fields):
    return [fields.get(name, '') for name in HEADER]

courses = collections.defaultdict(list)
for line in open(LESSONS, encoding='utf-8'):
    parts = line.rstrip('\n').split('\t')
    if len(parts) == 3 and parts[0] in LANGUAGES:
        courses[parts[0]].append(parts)

strays = []
for language in sorted(courses):
    rows = [HEADER]

    for order, (code, title, description) in enumerate(TYPES, 1):
        rows.append(row(
            Entry_Identifier='type-' + code, Entry_Title=title, Entry_Code=code,
            Entry_ListTitleSortKey='%02d' % order, Entry_ParentPath='/' + language + '/',
            Description_Description=description.replace('{Language}', LANGUAGES[language]),
            Description_Language='en',
        ))

    moved = collections.Counter()
    for _, entry_id, title in courses[language]:
        code = TYPE_BY_TITLE.get(title.split(' - ')[0]) if ' - ' in title else None
        if title in TYPE_BY_TITLE:
            continue        # a type already made by an earlier run
        if code is None:
            strays.append((language, entry_id, title))
            continue
        rows.append(row(Entry_id=entry_id, Entry_ParentIdentifier='type-' + code))
        moved[code] += 1

    pilot_path = os.path.join(HERE, 'pilot-' + language + '.json')
    new_lessons = new_words = 0
    if PILOT and os.path.exists(pilot_path):
        pilot = json.load(open(pilot_path, encoding='utf-8'))
        # A pilot lesson the grid has taken over is the grid's to import (make_grid_import.py), not made twice
        grid_path = os.path.join(HERE, 'grid-' + language + '.json')
        taken = {cell.get('replaces') for cell in json.load(open(grid_path, encoding='utf-8'))['cells']} if os.path.exists(grid_path) else set()
        for number, lesson in enumerate(pilot['lessons'], 1):
            if lesson['title'] in taken:
                continue
            name = 'pilot-%d' % number
            # The code is given: left to itself the importer builds one from the title and
            # the description together, and the existing lessons' codes are their titles alone.
            rows.append(row(
                Entry_Identifier=name, Entry_Title=lesson['title'], Entry_Code=slug(lesson['title']),
                Entry_ParentIdentifier='type-' + lesson['type'],
                Description_Description=lesson['description'], Description_Language='en',
            ))
            new_lessons += 1
            for word_number, (english, foreign) in enumerate(lesson['words'], 1):
                rows.append(row(
                    # in the order written: I, you, she, we -- or the statement before its question
                    Entry_Identifier='%s-word-%d' % (name, word_number), Entry_Title=english, Entry_ParentIdentifier=name,
                    Entry_ListTitleSortKey='%02d' % word_number,
                    EntryTranslation_Title=foreign, EntryTranslation_Language=pilot['translation_language'],
                ))
                new_words += 1

    path = os.path.join(OUT, 'restructure-' + language + '.tsv')
    with open(path, 'w', encoding='utf-8', newline='') as out:
        csv.writer(out, delimiter='\t', lineterminator='\n', quoting=csv.QUOTE_MINIMAL).writerows(rows)

    print('%-11s %5d moved  %2d new lessons  %3d new words' % (language, sum(moved.values()), new_lessons, new_words))

print('strays, left where they are: %d' % len(strays))
for stray in strays:
    print('   %s  Entry %s  %s' % stray)
