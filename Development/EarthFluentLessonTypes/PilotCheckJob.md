# Pilot check: a job for the AI Workers

A job spec in the Foreman's terms (`E:\AIWorkers\README.md`, "Foreman"). It checks the pilot lessons for
EarthFluent's four new lesson types before any of them is imported. Like every worker, it finds problems and
reports them. It never edits a pilot file or a site.

## Why this is its own job

The translation triage (`earthfluent/`) judges one word against its lesson, with a dictionary entry as evidence.
The pilot lessons are different in three ways, and each needs a check the triage doesn't make:

- **Most pairs are sentences or forms**, not dictionary words: `Idę na targ.`, `rumah saya`, `갔어요`. A dictionary
  entry settles little. A blind back-translation settles more.
- **The description makes a claim about the language**: "Put czy in front of a statement and it becomes a
  yes-or-no question. Nothing else moves." If the claim is false, the lesson teaches something false however good
  its four pairs are.
- **The four pairs are there to show the claim.** A pair can be a correct translation and still not show what the
  description says it shows.

Claude wrote all 208 lessons on 12 November 2026 without a checker. Russian, Polish, Hindi, Indonesian and Korean
were written with the least certainty.

## The pile

- **Source:** `E:\GGCMS\Development\EarthFluentLessonTypes\pilot-<language>.json`, read-only. Thirteen files.
- **Target:** a language code, the file's `translation_language`: `es fr it pt de nl ru pl hi id ko ja zh`.
- **Items per target: 80.** Sixteen lesson items and sixty-four pair items. 1,040 across all thirteen.
- A file's `status` line states its register ("Informal ty throughout; a male speaker where the word shows it").
  Every prompt for that file carries it, so the model doesn't mark an informal form wrong for being informal.
- An item's key is a hash of its own content: the lesson's title and description for a lesson item, plus the pair
  for a pair item. A pilot file that changes gets its changed items rechecked and keeps the rest.

## Three checks

### 1. Mechanical (no model, runs first, whole file at once)

- Sixteen lessons, four of each type; four pairs in each; the title starts with its type's name and ends ", Part N".
- No pair is empty.
- A translation that is the same text as its English is listed, not failed. Four are, and all four are right:
  Dutch `man` (twice), French `Paris` and Italian `gnocchi`.
- The translation is in the language's script (Cyrillic for `ru`; Devanagari for `hi`; Hangul for `ko`; kana or
  kanji for `ja`; Han for `zh`; Latin for the rest).
- Every foreign form the description quotes appears in one of the lesson's four translations. "Spot what changes:
  иду, идёшь, идёт, идём" needs all four among the pairs. As built, the rule covers the five languages not written
  in Latin letters, where a quoted form can be told from the English around it. For the other eight, the lesson
  check below is what catches it.
- An English sentence that ends in `.`, `?` or `!` has a translation that ends in the language's own mark.
- The same English given two different translations within one file is listed, not failed. Japanese and Korean
  each give "I go" with and without the word for I, on purpose.
- A single-word pair whose English is already a word in the course is compared with the course's own translation,
  read from the dump as the triage's `dump_reader.py` does. A difference is listed with both.

A mechanical failure is a record with `checked by: rules`. It needs no confirmation by a model.

### 2. The pair (one item per pair)

Two calls, as `earthfluent_fill/fill_check.py` does:

1. **Blind back-translation.** The model sees only the translation and the language, and gives the English.
2. **Judgement.** The model sees the English, the translation, its own back-translation, the lesson's title and
   the file's register line, and answers `ok` or `suspect`, with `kind`, `proposed` and `reason`.

Kinds: `wrong meaning`, `wrong form` (tense, person, gender, number, case), `unnatural` (a native speaker would not
say it), `wrong register` (against the file's own register line), `spelling or script`, `untranslated`.

The jury's sixth instruction holds here too: synonyms are fine. A pair is suspect only when it is clearly wrong.

### 3. The lesson (one item per lesson)

One call. The model sees the title, the description, all four pairs and the register line, and answers two
questions separately:

- **Is the description true of the language?** `true`, `false` or `too strong`. "Too strong" is for a rule stated
  without its exceptions where a learner would soon meet one: "Nothing else moves", "never changes", "always".
- **Do the four pairs show it?** For each pair, `shows it` or `does not`, with a reason.

Then `proposed`: a corrected description, or a replacement pair, only where it marked something.

The description is written for a beginner in plain words, on purpose. The model is told so, and told not to mark a
description down for leaving out grammar terms or for being simple.

## Results

`pilot/<target>.pilot.txt`, in TranslationReview's record format: blank-line separated, `key: value` per line,
every record `status: suspect`. Only items marked go in. A clean item is counted, not written.

```
lang: pl
lesson: Sentence Building - Asking a Question, Part 1
item: description
claim: Put czy in front of a statement and it becomes a yes-or-no question. Nothing else moves.
verdict: too strong
reason: ...
proposed: ...
checked by: <model>, unconfirmed
status: suspect

lang: pl
lesson: Sentences in Use - Going Places, Part 1
item: pair 3
english: Are you going to the party?
current: Idziesz na imprezę?
back: ...
kind: unnatural
proposed: ...
reason: ...
checked by: <model>, unconfirmed
status: suspect
```

**A suspect is not a correction.** Claude reads every record, confirms or sets it aside, and edits the pilot file.
Only then does `make_restructure.py` (run without `--no-pilot`) put the lessons in an import.

## worker.json

```json
{
  "title": "EarthFluent: pilot lesson check",
  "description": "Checks the pilot lessons for the four new lesson types: each pair by blind back-translation, and each lesson's description against the language and its own four pairs. Suspects go to a file for a person to confirm; it never edits the pilot files or the site.",
  "targets": ["ru", "pl", "hi", "id", "ko", "ja", "zh", "de", "nl", "fr", "it", "pt", "es"],
  "command": ["{python}", "run_pilot_check.py", "--lang", "{target}", "--limit", "100000"],
  "until_flag": "--until",
  "stats": ["{python}", "stats.py", "--lang", "{target}"],
  "results": "pilot/{target}.pilot.txt"
}
```

- **Folder:** `E:\AIWorkers\earthfluent_pilot\`. Committed in `E:\AppHub`. Built on 13 November 2026; its README
  section there says how it runs. Tasks 71 to 83 on the Foreman's board, machine Either.
- **Stats:** one JSON line with `done`, `total` (80) and `suspects`.
- **Target order** is least certain first, so the first night's results are the ones most worth reading.

## Machine

**Eowyn**, with **Either** as the fallback. The lesson check asks a model to judge a grammar claim in Russian,
Hindi or Korean, and that is where a bigger model earns its place: her 12 GB card holds one the 1070 Ti can't.
The Foreman's `"eowyn": {"args": {"earthfluent_pilot": ["--model", "...", "--judge", "..."]}}` sets it for her
lane only.

- `--model` does the back-translation and the pair's judgement.
- `--judge` does the lesson check. It may be the same model.
- The pile is small: 80 items a language, 144 calls. At the fill job's pace (29 s for its two calls) that is
  roughly 35 to 45 minutes a language, so all thirteen fit in one night on one machine.

## A second opinion, later

One model's word on a grammar claim is thin. Once the first pass is read, the lesson check alone (208 items) is
worth a second run with a different model, or with the triage's three jurors and their charge
(`earthfluent/jury_charge.txt`). A lesson two models both mark is read first.

## Not this job's business

- Whether the lesson is a good lesson, or in the right order. That is Ben's.
- The sound lessons' claims about pronunciation ("ł sounds like an English w"). A text model can say whether the
  claim is the standard description, and the lesson check asks that. Whether a learner hears it is for a speaker.
- Importing anything.
