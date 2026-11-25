# EarthFluent: grammar lessons, lesson types, and the worker jobs

Written 12 November 2026.  A plan, not a record: nothing here is built yet.
Counts are from `earthfluent-2026-11-10-evening.sql.gz`.

## What is already there

Thirteen courses, about 14,900 lessons and 60,500 words.  Every lesson title
already starts with its type:

| Prefix | Lessons per full course |
|---|---|
| Nouns | 495 |
| Verbs | 138 |
| Adjectives | 143 (139 in six courses) |
| Entertainment | 29 |
| Culture | 110 (104 to 110) |
| Phrases | 437 (none in nl, pl, pt, ru, ko; 146 in de) |

So the lesson-type level is not invented, it is lifted out of the titles.

A handful of entries sit at lesson level that are not lessons: single
phrases under Spanish, Italian and German ("Por favor entra.", "Is this the
bus to Barcelona?"), four capitals under Russian, and an "Updates" entry
under Indonesian.  The restructure should file or report each one by id.

## Lesson types (level b of language/lesson-type/lesson/word)

Existing, moved as they are, no content change:

1. Nouns
2. Verbs
3. Adjectives
4. Phrases
5. Culture
6. Entertainment

New:

7. **Sounds and Script.**  The slot every language has and no two fill the
   same way: tones for Chinese, kana for Japanese, stress and accent marks
   for Spanish, Cyrillic for Russian.
8. **Patterns.**  The word changes: conjugation, plurals, agreement.  Built
   on the verbs, nouns and adjectives the course already teaches.
9. **Sentence Building.**  The order and the small words: questions,
   negation, where the verb goes.  The explanation.
10. **Sentences in Use.**  The examples, drawn from Phrases.

## The spine: one list of jobs a language must do

Each is a lesson in every course.  What differs is the strategy, one of:
ending, helper word, particle, word order, tone, nothing.  "Nothing" is
still a lesson, a short one.

Who is doing it.  Now, before, later.  Still going or finished.  Not.
Yes-or-no question.  Who, what, where question.  Telling someone to do it.
Want to, can, must.  One or many.  The and a.  Mine and yours.  This and
that.  Describing a thing.  Where it is.  More and most.  Counting.
How polite.  And, but, because.

Eighteen jobs by thirteen languages is 234 cells.  That grid is judgment
work and small: it is for Claude with Ben, not for a local model.

## A lesson row is still x => y

A Patterns row is a pair with three more facts: the base word (by its
Entry id), the features (who, when), and the form.  "we go => vamos" is
ir + we + now.  A Sentence Building row is a phrase pair (by its Entry id)
plus which word answers which, and each word's role.

The lesson board draws from those facts.  It is stepped by the learner and
never plays on its own.

Where these facts are stored is the open decision: child tables beside
EntryTranslation (a form table, a word-alignment table, a concept tag).
The jobs below write `.jsonl` in those tables' field names, so the tables
are named before the jobs are written.

## Worker jobs

All follow the house rules: one item at a time, mechanical verdicts first,
the model only for what is left, every result a suspect, nothing edits a
site.  gemma4:12b judges; mistral:7b does not.

### 1. earthfluent_concepts: tag each phrase with the jobs it shows

- Target: one, the master (Italian's branch), since a phrase's English is
  the same in every course.  About 1,750 phrases.
- Mechanical first: ends in "?", holds not/n't/never, starts with a
  question word, a bare verb first (command).
- Model: when (now, before, later), want/can/must, and anything the
  mechanical pass left with no tag.  Asked twice, the second time blind;
  disagreement is `check: doubtful`.
- Results: `concepts/master.concepts.txt`, and a `.jsonl` keyed by the
  phrase's master Entry id.
- Size: about 30 s a phrase when the model is asked, so two nights at most.
- Needs: nothing.  Can start now.

### 2. earthfluent_pairs: find sentences that differ by one thing

- No model.  From job 1's tags, list pairs of phrases in the same lesson
  family whose English differs by one tag: "This is the bus to
  Barcelona." and "Is this the bus to Barcelona?".
- Results: `pairs/master.pairs.txt`, each pair by its two Entry ids and the
  one tag that differs.
- These pairs are the Sentence Building rows.  Where a job on the spine
  has fewer than five pairs, that is a gap for a person to write.
- Needs: job 1.

### 3. earthfluent_forms: the forms of each verb the course teaches

- Targets: the thirteen languages.  About 550 verbs each.
- Mechanical first: read the forms from the kaikki Wiktionary dump for the
  course's own translation of the verb.  `dictionary_index.py` must keep
  the dump's `forms` and their tags; check whether today's
  `dictionary-<lang>.sqlite` did, and re-index if not.
- Model: only to pick the entry when the headword has more than one verb
  sense, judged against the lesson as triage does.
- A verb the dump lacks is `absent`, not invented.
- Results: `forms/<lang>.forms.txt` and a `.jsonl`: verb Entry id,
  features, form, source.
- Needs: that language's triage confirmed and shipped, since a wrong verb
  gives a right table for the wrong word.

### 4. earthfluent_sounds: how each word is said

- Targets: zh, ja, ko, hi, ru first (the scripts an English reader cannot
  sound out), then the rest.
- Mechanical: pinyin with tone marks, kana reading, romanization, from the
  same dump's pronunciation fields.  No model.
- Results: `sounds/<lang>.sounds.txt` and a `.jsonl` by word Entry id.
- Needs: triage confirmed for that language.

### 5. earthfluent_align: which word answers which

- Targets: the thirteen languages, but only the phrases job 2 chose, not
  all 1,750.  At ten pairs a job that is about 360 phrases a language.
- Mechanical first: a target word the dictionary gives for exactly one
  English word in the sentence is aligned without the model.
- Model: the rest, as JSON, each target word given once.  A check with no
  model: every target word used once and none invented.  Fail is
  `check: doubtful`.
- Chinese and Japanese are split into words first; say which splitter in
  the results.
- Size: 360 x 13 at about 30 s is about 39 hours, five nights on one card.
- Needs: jobs 1 and 2, and that language's triage.

### 6. knowledge_block: the teaching shelf

- Not a new kind.  A new entry in knowledge_block's `data/blocks.json`
  for `E:\Media\Books To Read\Languages\Linguistics Learning`.
- The shelf is about how languages are learned and taught.  It will not
  say how Polish marks the past; it will say what order to teach in and
  why.  Use it to check the lesson order, not to fill the grid.

## Order

1. The importer moves an existing entry (see below).
2. The lesson-type level: ten type entries a course, lessons moved under
   them by id.
3. The 234-cell grid.
4. Tables for forms, alignment, concept tags.
5. Jobs 1 and 2, then the lesson board on Spanish, Chinese and Japanese.
6. Jobs 3, 4, 5 as each language's triage ships.

## The importer and moving

Built 12 November 2026, not yet run against a database.  A row with an
`Entry_id` and any of `Assignment_Parentid`, `Entry_ParentPath` or
`Entry_ParentIdentifier` moves that entry beneath the parent named, with
the guards transfer::transferentry() has: no entry of the same code under
the new parent, not under itself or its own descendants, and the old path
reserved first.  It also refuses an entry filed in more than one place,
and leaves alone one that is already there.

BackupEntryCodeReservation() reserves the entry's full path, its bare
code, and each direct child's full path, but it takes the children from
`$this->children`.  transfer::transferentry() does not appear to fill
that, so a lesson moved through `transfer.php` may keep its own old
address and lose its words'.  importerrow::ImportMoveEntry() reads the
children itself before reserving.  A lesson's words are its direct
children, so a lesson moved by import keeps every old lesson and word
address: about 90,000 reservation rows for all thirteen courses.
