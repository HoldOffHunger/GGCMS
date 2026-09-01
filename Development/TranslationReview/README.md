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
| `lang` | Language code, matching `EntryTranslation.Language` |
| `english` | The English source title, for the reader of this file |
| `current` | What the row holds now. Update only if this still matches |
| `proposed` | What it should say |
| `kind` | See below |
| `reason` | Why, in a sentence a non-linguist can follow |
| `source` | A citation anyone can check |
| `status` | `proposed`, or `shipped YYYY-MM-DD` |

## `kind` values

* `wrong-word` — a different thing entirely. The costliest kind.
* `wrong-sense` — right area, wrong sense of the English word.
* `untranslated` — the English was passed through unchanged.
* `orthography` — accent, spelling or casing.
* `register` — understood, but not what a speaker would say.
* `regional` — correct somewhere, wrong for the intended audience.

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
