# Translation Review

The EarthFluent translations in `EntryTranslation` were produced with Google
Translate. This directory is the working store for reviewing them.

One file per language, named by its language code — `es.txt`, `it.txt`, and so
on. Each file is a flat list of records, blank-line separated, `key: value` per
line. Plain text on purpose: greppable, diffable, and readable cold in six
months by someone with no memory of this session.

## Why a store rather than direct edits

The database changes ship in **waves** — a batch of corrections applied, and the
matching `/updates/` entry published at the same moment. Reviewing runs ahead of
shipping and accumulates here until there is enough for a wave. A record sits at
`status: proposed` until its wave goes out, then becomes `status: shipped` with
the date.

Target is no more than roughly 200 corrections per wave.

## Record fields

| Field | Meaning |
|---|---|
| `id` | `EntryTranslation.id` — the row to update |
| `entryid` | `EntryTranslation.Entryid`, for cross-checking the source word |
| `permalink` | `Assignment.id` — the identifier to quote and to link by |
| `lesson` | The lesson the word sits in. This is what fixes its sense |
| `lang` | Language code, matching `EntryTranslation.Language` |
| `english` | The English source title, for the reader of this file |
| `current` | What the row holds now. Update only if this still matches |
| `proposed` | What it should say |
| `kind` | See below |
| `reason` | Why, in a sentence a non-linguist can follow |
| `source` | A citation anyone can check |
| `displaced` | Optional. A real form the correction removes — see below |
| `status` | `proposed`, or `shipped YYYY-MM-DD` |

## Why the permalink and not the entry id

An entry can hang under more than one parent, so it can be reached at more than
one URL. The entry id says *which word*; it does not say which of those contexts
you are looking at. `Assignment.id` does, which is why the permalink is the
assignment rather than the entry, and why `?id=` takes one.

`Handler::PermalinkRedirect()` resolves it: given the assignment, it rebuilds
the full path and redirects there. So any record here can be opened with

```
https://www.earthfluent.com/?id=<permalink>
```

which is how a reviewer checks a word in place rather than in a text file.

An entry with three parents has three assignments and three permalinks. Record
the one whose context the correction was judged in.

## `kind` values

* `wrong-word` — a different thing entirely. The costliest kind.
* `wrong-sense` — right area, wrong sense of the English word.
* `untranslated` — the English was passed through unchanged.
* `orthography` — accent, spelling or casing.
* `register` — understood, but not what a speaker would say.
* `regional` — correct somewhere, wrong for the intended audience.
* `false-friend` — looks like the English word and means something else.
* `wrong-class` — right root, wrong part of speech. A verb where the list wants
  a noun, or a noun where it wants a verb. Records written before this kind
  existed used `wrong-word` for the same fault; they want reclassifying one day
  and are not wrong meanwhile.
* `corrupt` — not a word in any language. Mangled somewhere in transit.
* `structural` — not a translation fault at all. The row is attached to the
  wrong entry, or duplicated, or its English source is not a word. Needs the
  database looking at rather than a dictionary.
* `wrong-form` — right verb, wrong grammatical form: an imperative or a
  conjugation where the list wants an infinitive.

## `displaced` — keep what the correction throws away

A `wrong-form` correction replaces one real Spanish word with another. `ven` is
not wrong Spanish; it is the imperative of *venir*, correct in its own right and
merely wrong for a list of infinitives.

Discarding it loses something the site does not otherwise have. EarthFluent
teaches infinitives and stops there, so every conjugated form Google handed over
by accident is a form nobody has recorded anywhere else. If conjugation is ever
taught, these are seed data rather than a blank table.

Format is the form, a pipe, and what it actually is:

```
displaced: ven | 2sg informal imperative of venir
```

Only for corrections that remove a genuine form. A `wrong-word` fix throws away
nothing worth keeping — `reloj` for *watch* is a wristwatch, not a verb form,
and it goes in the bin.

## Sources

Every correction carries one. A reader seeing a change with a citation behind it
believes the next one; a change with nothing behind it is just another
assertion. Dictionaries of record are preferred — the *Diccionario de la lengua
española* for Spanish, and the equivalent authority for each other language.

## Before shipping a wave

The dumps these were reviewed against are from **2021**. Re-check `current`
against the live row before applying, and skip any record where it no longer
matches — that row has been edited since and wants reviewing again rather than
overwriting.

## Mechanical sweeps

Two checks that read the whole corpus faster than a person can, and both want a
person after them.

**Untranslated rows.** Compare each Spanish title against its English source,
case-insensitively. 212 of 5,371 Spanish rows are identical — 3.9%. Most of
those are *correct*: Spanish genuinely uses `animal`, `virus`, `actor`, `doctor`,
`error`, `capital`, `idea`, `hospital`, `taxi`, `crisis`, `drama`. Perhaps thirty
to forty are real failures, and the check finds them in one pass instead of an
afternoon. It cannot decide which is which, so it filters rather than judges.

**Missing accents.** Nine `-cion` words against 263 correct `-ción` — 3.3% of
that class. Not a general fault, but a clustered one: six of the nine sit in a
single stretch of ids, which reads as one bad import batch rather than a habit.
Worth checking other accent classes the same way before assuming the corpus is
sound.

Both scripts are throwaway and live in the scratch directory rather than here.
The counts are what matter, and they are recorded above so the next pass knows
whether anything moved.

## The corpus, measured

All 5,371 Spanish rows, 1 September 2026, against the 2021 dump.

| Check | Count | Share |
|---|---|---|
| Capitalised where the English was not | 146 | 2.7% |
| Carrying a leading article (`el respeto`) | 58 | 1.1% |
| Identical to the English source | 212 | 3.9% |
| `-cion` missing its accent | 9 of 272 | 3.3% |
| Empty | 0 | — |
| Rows whose `Entryid` has no `Entry` | 4 | 0.1% |
| Spanish words standing for 2+ English words | 299 | — |

Capitalisation and leading articles are **one sweep each**, not 204 judgements.
English title casing does not belong in Spanish, and a vocabulary entry is the
word rather than the phrase. Both can be done mechanically and verified by
eye afterwards.

The orphans are rows 49–52 — *Delfín*, *Lobo*, *Oso panda*, *Pelícano* — pointing
at entry ids 75–78, which do not exist. 77 such rows across all languages, so
roughly nineteen entries were deleted and their translations left behind.

### The corpus repairs itself in places

274 English words are held by more than one entry, so the same word is taught in
more than one lesson. Of those, **264 pairs agree** on their Spanish and **10
disagree** — and in every one of the ten, one half is correct and the other was
never translated:

```
lean    apoyarse | lean        essay   essay | ensayo
brush   cepillo  | brush       brown   brown | marrón
sound   sonar    | sound       yellow  yellow | Amarillo
grave   tumba    | grave       pink    pink | Rosado
```

Those need no dictionary. The right answer is already in the table, on the twin
row, and a script can copy it across with more confidence than a translator
could supply it.

It also means a correction is **per word, not per row**. Fixing `Turkey → Pavo`
at row 44 leaves the other *turkey*, at entry 50265, saying whatever it says. Any
wave should look for twins before it writes.

### Where the failures cluster

The ten untranslated twins sit between ids 9813 and 10350. Six of the nine
missing `-ción` accents sit between 9015 and 9293. Neither is a habit spread
through the corpus; both are batches. Whatever ran over those ranges did
something different from whatever ran over the rest, and finding out what would
be worth more than fixing them one at a time.

## The lesson is the sense

The same English spelling appears in more than one lesson, and it is never the
same word twice:

```
turkey   Nouns - Animals          |  Culture - Countries
press    Verbs - Activity         |  Nouns - Objects
break    Verbs - Activity         |  Nouns - Concepts
orange   Culture - Food and Drink |  Adjectives - Colors
```

The bird and the country. The verb and the noun. The fruit and the colour. So a
row is judged against its lesson, and every record carries it.

`Turquía` is **wrong** at entry 68 and **right** at entry 50265, and the only
thing that says so is the lesson name. A correction applied to both would break
the good one, which is why corrections are per row and always will be.

The other thirteen entries sharing a word are not duplicates either. They are
one entry per language — the Spanish row hangs on one of them, the Italian on
another. That is the multilingual structure rather than a fault.
