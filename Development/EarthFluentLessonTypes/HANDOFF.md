# Handoff: EarthFluent's lesson overhaul

For the next assistant (Opus) taking this over from Claude (Fable). Written 16 November 2026. Read this first, then
`ConjugationSystem.md` beside it, which is the detailed manual. Ben does not read these files; talk to him in chat.

## The project in five lines

1. EarthFluent's 13 courses were restructured from a flat list of lessons into **language / lesson type / lesson / word**
   (ten types per course). Live since 13 Nov.
2. Two types, Patterns and Sentence Building, are a **spine** of eighteen jobs every language does, one hand-written
   lesson per job per course, shown on a **chalkboard** that lights the pieces of each word (`grid-<language>.json`).
3. A **conjugation system** makes verb tables and further lessons from the Wiktionary dumps, a recipe file per language.
   Live for Spanish, French, Italian, Portuguese (Brazilian), German and Dutch.
4. **Local AI workers** check the work and found real errors; their picks are confirmed by an Opus session before
   anything changes. About 2,740 words filed under the wrong part of speech were corrected on 15 Nov.
5. The server was hardened on 14 Nov against a crawl that kept taking it down.

## State right now

**Live on earthfluent.com:** everything above, plus pressable verb tables (each form speaks when pressed).

**Finished on Fumiko, committed locally, NOT pushed, NOT deployed, NOT on prod:**

| What | Where | Commits |
|---|---|---|
| Wrong-course address fix (`/french/...` no longer answers with the Spanish lesson) | GGCMS engine, `HandlerRedirects.php` | GGCMS |
| Russian and Polish: forms, tables, lessons | `forms-*`, `lessons-*`, recipes | GGCMS |
| German and Dutch split-verb lessons (`ankommen`) | recipes, `lessons-german.json`, `lessons-dutch.json` | GGCMS |
| Forms for the Spanish and French verbs corrected on 15 Nov | `forms-spanish.json`, `forms-french.json` | GGCMS |
| "Before, finished" and "Later, finished" table columns | private repo template | private repo |
| Lesson-check targets for German and Dutch | `E:\AppHub\AIWorkers\earthfluent_pilot\worker.json` | AppHub |

Run `git log origin/main..main --oneline` in `E:\GGCMS`, `E:\GreenGluonCMS_Unhireable` and `E:\AppHub` for the exact
list. The steps to ship it are in `ConjugationSystem.md` under "To ship what is waiting". **Ask Ben before pushing,
deploying or importing to prod, each time.** A general "do what you want" from earlier does not cover a new ship.

**Queued for the workers, not yet read:** Foreman tasks 141 to 144 (lesson checks for Italian, Portuguese, German,
Dutch). Results land in `E:\AIWorkers\earthfluent_pilot\pilot\lessons-<code>.pilot.txt`. Most flags are false alarms
where a form is also another word; read for English that does not say what the form says.

## What to do next, in order

1. **Ship the waiting batch**, when Ben says so. Follow the steps in `ConjugationSystem.md`; rehearse nothing new, it
   has all run on Fumiko.
2. **Read tasks 141 to 144** when they finish, and correct what is real with `make_lessons_update.py`.
3. **The five remaining languages** (Chinese, Japanese, Korean, Hindi, Indonesian). Begin by printing what each dump
   holds for two or three verbs, as every earlier language began. Expect particles and helper words, not endings.
4. **Aspect-pair lessons for Russian and Polish**, which need English written per verb for the finished form.
5. Smaller gaps are listed at the end of `ConjugationSystem.md`.

Ben has also decided, for later and not started: a separate **European Portuguese** course beside the Brazilian one,
and probably the same for Spanish. Do not start either unasked.

## Where things are

| Thing | Place |
|---|---|
| Generators, recipes, grids, forms, lessons, this file | `E:\GGCMS\Development\EarthFluentLessonTypes\` (public repo) |
| Ship scripts kept | `ship\` beside this file |
| Working files, logs, import files | `D:\ef-types-ship\` (scratch disk: nothing there is the only copy of anything that matters) |
| Site templates, stylesheet, nginx config | `E:\GreenGluonCMS_Unhireable\` (private repo). Never stage its `etc/ggcms/clonefrom/site/identity.php` |
| Workers | `E:\AIWorkers\` (a junction into `E:\AppHub\AIWorkers`; commit in `E:\AppHub`) |
| Wiktionary dumps | `E:\ModelWorkshop\dictionaries\<language>\` |
| Part-of-speech decisions and their evidence | `D:\ef-types-ship\pos\` |
| Prod host, deploy and cache rules | the project memory files (`prod-push-warm-swap`, `import-keep-cache`) |
| Fumiko's quirks | memory file `fumiko-php85-wsl1` |

## Working with Ben

His standing instructions are in `C:\Users\Makhno\.claude\CLAUDE.md` and apply in full. The ones that mattered most here:

- **Say it in chat, first sentence, in plain words.** If it looks like a bug, say "this looks like a bug."
- **One short question** when a decision is his. He only speaks English: put language questions to him as questions
  about the English ("check" under Objects: a bank cheque or an inspection?), or decide by the lesson's category.
- **Reading worker results is Opus work** by his rule; that is you.
- **Own a mistake in one clause and fix it.** Three of mine in this work: an importer option that did not do what its
  report said, a worker that swamped the graphics card, and a mangled character in a stylesheet. Each is in
  `ConjugationSystem.md` as a rule so it need not happen twice.
- Commit to `main`; push only when he says. British spelling, imperative subject, a body that says why.

## Things that are not what they seem

- **Fumiko after a reboot** has no MySQL or Apache running; start both before trusting an empty result.
- **A query string changes how a missing address is repaired**, so test addresses without one.
- **A cached page keeps its old stylesheet** until re-rendered.
- **The Foreman** (the workers' page) was fixed on 14 Nov to stop waiting on every card's progress; it has to be
  restarted by Ben to pick the fix up, and adding many tasks no longer slows it once it has.
- **`earthfluent_before_ship`** on prod was overwritten on 14 Nov by another session's swap. The undo for the 13 Nov
  restructure survives only as `before-ship-20261113-041011.sql.gz`. Do not drop or overwrite either without Ben.
