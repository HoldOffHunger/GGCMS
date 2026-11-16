# EarthFluent's conjugation system

A handoff for whoever picks this up next. Written by Claude (Fable) with Ben on 16 November 2026. It says what exists,
how to run it, how to add a language, what is live, and what is not. Ben does not read this file: anything he needs to
know, say to him in chat.

## What it is

EarthFluent teaches 13 languages. Each course has ten lesson types; two of them, **Patterns** and **Sentence Building**,
are built on a **spine** of eighteen jobs every language does (`spine.json`): who is doing it, when, still going or
finished, telling someone to do it, and so on. Every course has one hand-written lesson per job (`grid-<language>.json`,
Part 1 of that job).

The conjugation system makes everything after Part 1 from dictionary data, with no model writing any form:

1. `make_forms.py` reads a verb's table out of the Wiktionary dump and names each form by the spine's **features**
   (`who=i;when=now`), not by grammar terms. Output: `forms-<language>.json`.
2. `make_forms_import.py` hangs those forms on the course's own verb entries as `WordForm` rows (`FormText` is the form,
   `BaseWord` the dictionary word, `Features` what it says, `Source` `wiktionary`). The verb's page then shows a table.
3. `make_form_lessons.py` turns forms into lessons in the grid's shape, from a **recipe** per job. Output:
   `lessons-<language>.json`. `check_grid.py` checks it; `make_grid_import.py --lessons` makes the import file.
4. `make_lessons_update.py` and `make_forms_update.py` correct lessons and forms already on the site (the importer can
   make a record, not change one). `ship/corrections_run.sh` runs what they write, on Fumiko or prod.

**Everything about a language lives in one file: `forms-recipes-<language>.json`.** Its `about` field says what that
language needed that the others did not. Read the `about` of the language nearest the one you are adding before you start.

## The features vocabulary

- `who`: `i`, `you`, `she`, `we`, `you-all`, `they`
- `when`: `now`, `before`, `later`
- `going=yes`: going on (Spanish `estoy hablando`); with `when=before` it is the "was doing" form (`hablaba`)
- `written=yes` with `when=before`: the story past (German `ging`, Dutch `ging`)
- `done=yes`: the finished verb of a pair (Russian `прочитал`), borrowed from the verb's partner
- `command=yes`, with `who`, and optionally `polite=yes` or `not=yes`
- `form`: `infinitive`, `going` (gerund), `done` (participle), `helper` and `partner` (bookkeeping, never imported)

The verb page's table (private repo, `templates/earthfluent/view/display_greatgreatgrandchildof_EarthFluent.com.php`)
has a fixed list of columns keyed by these strings and shows only the columns a verb has. A new feature combination
needs a new line there or it will not be shown.

## What a recipe file can say

| Key | Means |
|---|---|
| `slots` | One per form to keep: `features`, and `tags` (all must be on the dump's form) with optional `without`, `nth` (take the nth match), `shown` (how a table shows it, `"no {}"`), `inside` (what `shown` adds goes inside a split verb), `split` (a two-word form the dump gives whole: first word is the helper) |
| `compound` slot | A two-word form built here: `helper` features, `part` features, optional `verb` (same helper for every verb, `ir`), `join` (`aan het`), `agree` (the part changes with the helper verb: `["-a", "/a"]` replaces the last letter and shows `andato/a`), `alone_for_itself` |
| `partner` slot | Borrow this form from the verb's partner (needs `partner_tags`) |
| `skip_tags`, `skip_prefixes` | Forms of the dump to ignore |
| `all_tables` | Read every table of the entry, not only the first (German, whose perfect and future are in later tables) |
| `helper_tags`, `helper_prefers`, `helper_of`, `helper_default`, `helper_verbs` | Which helper a verb takes: from the dump, or a default with exceptions (Dutch) |
| `built`, `patterns`, `separable_verbs` | A language whose verb never changes: each form is `pattern` around the dictionary word, with `reading` (pinyin) and `sandhi`; the dump is asked only whether it is a verb and for its sound (Chinese) |
| `strip_inner_accents` + `accented_monosyllables`, `strip_marks` | Remove the dump's stress marks (Italian, Russian) |
| `class_letters` | How many final letters sort a verb into a class (Italian 3) |
| `elide` | Little words that lose their vowel (`je` → `j'`) |
| `endings` | Regular endings by tense and class (`"*"` for every verb). The board lights the longest one a form ends in; `""` in a list means having no ending is regular |
| `recipes` | Per job: `strategy`, `description` (`{1}`..`{4}` are the rows), `own_way`, optional `skip`, and four `rows`: `english` template, `features`, optional `endings`, `wrap`, `capital`, `words`, `from`, `mark_part`, `says` |
| `middle`, `splits` | Sentences added when a stem changed, or the verb splits in two |
| `verbs.list` | `[word, base, she-form, past, ing, flags...]`. Flags: `stative` (skipped by recipes with `"skip": "stative"`, no number spent), `no-<job>` (lesson taken down, **its part number stays spent**), `splits`. A row can also say `mark_form` (light the verb itself) and its `words` can be `{"text", "role"}` (`了` as a particle) |

**Part numbers are positions in `verbs.list`.** Never reorder or delete a verb in a language that is live: append, or
flag it `no-<job>`. Otherwise every later lesson's title and address shifts.

## Running it

The dumps are in `E:\ModelWorkshop\dictionaries\<language>\kaikki.org-dictionary-<Language>.jsonl`. Working files and
logs are in `D:\ef-types-ship\` (scratch disk); the scripts worth keeping are copied to `ship/` beside this file.

```
# 1. the verbs the course teaches (Fumiko's database, read only)
wsl -u root bash ship/verblist2.sh german de      -> D:\ef-types-ship\forms\verbs-german.txt
# 2. forms, then lessons
python make_forms.py german E:\ModelWorkshop\dictionaries --verbs=D:\ef-types-ship\forms\verbs-german.txt
python make_form_lessons.py german
python check_grid.py                               # must say clean for every file
# 3. import files
wsl -u root bash ship/words.sh /mnt/d/ef-types-ship/forms/words-german.tsv german de
python make_forms_import.py german D:\ef-types-ship\forms\words-german.tsv D:\ef-types-ship\forms
python make_grid_import.py D:\ef-types-ship\forms --lessons german
# 4. Fumiko: preview, apply, look at the boards and a verb's table
wsl -u root bash ship/lang_fumiko.sh german de gehen --apply
# 5. prod, only when Ben says to ship: copy the tsv files and words-german.tsv to /mnt/nyc01/ef-types-ship/forms/, then
bash prodlang.sh german de        # guards, previews, applies with --keep-cache, re-renders the changed pages
```

After any generator change, regenerate the languages already done and confirm their files are byte for byte unchanged
(`git status` shows nothing but what you meant). That check caught three mistakes.

### Rules that cost something to learn

- **`import_entries.php --apply` on prod must have `--keep-cache`**, then the changed pages re-rendered one at a time
  with `warm_page_cache.php --domain=earthfluent.com --url=<path> --format=html`. Without it the importer empties all
  ~200,000 cached pages and the host's one core falls over. `prodlang.sh` does this.
- **Never re-import an old `lessons-<language>.tsv` on prod.** A lesson retired with `no-command` would come back.
  Regenerate first; for forms alone use `ship/formsonly.sh`.
- **Fumiko's ids and prod's can differ.** Anything that updates or deletes finds its own rows by SQL on each database
  (`make_lessons_update.py`, `ship/lessons_update_run.sh`). `prodlang.sh` refuses to run if prod's verb entries are not
  the list Fumiko made; when it does, regenerate `words-<language>.tsv` and the forms file and try again.
- **A cached page keeps the stylesheet it was rendered with** (the CSS file name has a hash). A style fix reaches a page
  only when that page is re-rendered.
- **The board finds an `ending` mark as the last place its letters occur** in the row, and any other mark as the first
  whole word after the previous mark. A mark that would land in the wrong place must be left off.
- Write edit scripts to a file and run them. A `\b`, `\25` or `\.` typed through a shell heredoc into Python has
  silently become a control character three times in this work.
- Fumiko is WSL (`wsl -u root -e bash -l <script>`); its MySQL and Apache do not start by themselves after a reboot
  (`service mysql start`, `service apache2 start`). The local site is `https://localhost:8443` with `Host: earthfluent.com`.

## Where each language stands (16 November 2026)

| Language | Recipe | On Fumiko | On prod | Notes |
|---|---|---|---|---|
| Spanish | yes | yes | yes | |
| French | yes | yes | yes | No "going" job (French has no such form) |
| Italian | yes | yes | yes | |
| Portuguese | yes | yes | yes | **Brazilian**, as the course is. Ben wants a separate European course later; do not bend this one |
| German | yes | yes | yes | Seven split verbs (`ankommen`). No "going" job |
| Dutch | yes | yes | yes | Seven split verbs. Helper is `hebben` unless `helper_of` or `helper_of_endings` says |
| Russian | yes | yes | yes | Tables show the partner's forms as "finished" columns. No aspect-pair lessons yet |
| Polish | yes | yes | yes | As Russian; the dump names fewer partners |
| Chinese | yes | yes | no | `built`: forms are patterns around the verb (`了`, `在`, `会`), with pinyin from the dump. No who-by-when grid: the page lists the forms |
| Japanese, Korean, Hindi, Indonesian | no | no | no | Their dumps have not been looked at. Chinese's `built` mode may suit Indonesian; Japanese, Korean and Hindi do change their verbs |

Everything above was shipped to prod on 16 November 2026 (by Opus, from Fable's batch): the wrong-course address fix,
Russian and Polish, the corrected Spanish and French verbs' tables, the German and Dutch split-verb lessons, and the
German and Dutch helper and command corrections. Nothing is waiting.

## Checking the work

- **`check_grid.py`** is rules only: marks must be in their rows, parts unique, strategies from the spine.
- **The lesson check worker** (`E:\AIWorkers\earthfluent_pilot\`, pile `lessons-<code>`) reads each pair by blind
  back-translation. The forms are the dictionary's, so what it finds is English that does not say what the form says.
  It found three real things in Spanish and French (the before of "know" is "found out"; `pedir` is "ask for"; French
  `sortir` takes `être`) and many false alarms where a form is also another word (`camino`, `juego`). The Italian,
  Portuguese, German and Dutch runs (tasks 141 to 144) found three more, all fixed in `d4cb727`; Italian and Portuguese
  were clean. Expect it to flag every Dutch and German spoken past (`zij heeft gezien`) as the wrong tense: it is not.
- **The part-of-speech worker** (`E:\AIWorkers\earthfluent_pos\`) found words filed under the wrong lesson type; 2,743
  corrections went live on 15 Nov. Its rule is unreliable for Chinese, Japanese, Korean, Hindi and Indonesian.

## Known gaps, in the order I would take them

1. **The five remaining languages.** Start by reading what each dump holds for a verb, as was done for the others.
2. **Aspect-pair lessons for Russian and Polish.** The forms are there (`done=yes`). What is missing is English for the
   finished verb, which differs verb by verb ("read it through", "set off"); it needs a per-verb field, not a template.
3. **Only four of eighteen jobs generate lessons** (who, when, going, command). Others that could: saying no, asking a
   question, want-can-must.
4. **Verbs said with a reflexive word** (`se lever`, `jubilarse`, `uczyć się`) are skipped everywhere.
5. **Endings are matched per tense, not per person**, so an irregular form that happens to end in another person's
   regular ending is lit (`foi` lights `i`). Mostly harmless; the lesson usually says the verb goes its own way.
6. **The split-verb lessons leave the ending unlit** wherever the board would put it in the wrong word.
7. **`/nouns-animals-part-1/`**, a bare code with no course, still answers with whichever course comes first. That is
   the reserved-code lookup, not the one fixed here.
