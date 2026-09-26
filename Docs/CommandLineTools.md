# Command-Line Tools

`usr/lib/ggcms/cli/` is not a folder of scripts. It is a second application
built on the same conventions as the web engine, sharing its idioms, its
args-hash calling style, and its trait composition.

It contains 23 classes, 22 traits and 22 entry points. Almost none of it was
being run when this document was written. **A diagnostic that is not on a timer
does not exist** — see [Operations.md](Operations.md) for the crontab that
fixes that.

## Shape

An entry point is a thin shell. It defines paths, loads the standard libraries,
instantiates one class, and calls one method:

```php
#!/usr/bin/php
<?php
    require('/var/www/ggcms_cli_directories.php');
    require('/var/www/ggcms_install_directories.php');
    require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
    require(GGCMS_CLI_DIR . 'system/StandardCLIFunctions.php');
    require(GGCMS_CLI_DIR . 'classes/Storage/FreeSpace.php');

    $free_space = new FreeSpace(['argv'=>$argv]);
    $free_space->checkFreeSpace();
?>
```

All the behaviour is in the class, and all the shared behaviour is in traits.
`DomainChecker` composes fourteen of them:

```php
use Apache; use Base64; use CLIAccess; use DataArrays; use DBAccess;
use DBTest; use Directories; use DNSRecords; use ErrorCLI; use FileSystem;
use GlobalsTrait; use SSL; use VersionNumber; use ReverseDNSNotation;
use DomainValidation;
```

Interactive prompts go through `CLIAccess::abstractConfirmDialogue()`, which
takes the same args hash as everything else:

```php
$this->abstractConfirmDialogue([
    'message'=>'Choose Source Code Backup Type --',
    'prompt'=>'Backup Code -- (a)ll, (b)asics, (d)ependencies, (r)oot:',
    'index'=>1,
    'internal_key'=>'backup',
    'valid_answers'=>['a', 'b', 'd', 'r'],
]);
```

## The tools

### Domain health — `scripts/internal/domain/`

| Script | Does |
|---|---|
| `check_domain.php` | Full health check of a domain across five layers (below) |
| `check_domain_records.php` | Validates DNS records against what they should be |
| `view_domain_records.php` | Lists the DNS records currently published |
| `list_domain.php` | Lists installed domains |
| `install_domain.php` | Stands up a new domain: directories, config, database |

`check_domain.php` is the most valuable tool in the repository and the least
used. Through `DomainChecker` and the `SSL` trait it verifies:

1. **Certificates.** PEM structure of `cert`, `chain`, `fullchain` and
   `privkey` — markers, base64 validity, non-empty keys.
2. **Renewal configuration.** Parses `/etc/letsencrypt/renewal/<domain>.conf`,
   checks every expected key is present and well-formed, and confirms the
   cert, privkey, chain and fullchain files **actually exist on disk**.
3. **Apache.** Parses the `:80` and `:443` vhosts as XML and checks
   `ServerName`, `ServerAlias`, `DocumentRoot`, `ErrorLog`, `CustomLog`,
   `SSLCertificateFile`, `SSLCertificateKeyFile` and `Include` all point at
   real files and directories.
4. **Filesystem.** Domain directories, web-serving directories, stat
   directories.
5. **Database.** Build check, field check, table check, admin-user check.

Had this been on a cron, the August 2026 outage would have been an email in
July 2024.

### Storage — `scripts/public/storage/`

| Script | Does |
|---|---|
| `check_free_space.php` | Disk headroom, via the `FreeSpace` class |
| `archive_stats.php` | Tars completed months of visitor statistics off the root disk |

The disk filling is what took the production host down. Run this daily.

#### `archive_stats.php`

```bash
archive_stats.php                       # dry: what it would archive
archive_stats.php --apply
archive_stats.php --month=2023-Oct --apply
archive_stats.php --domain=wordweight.com --keep=6 --apply
```

Nothing had ever pruned `/var/log/ggcms`. No logrotate rule mentions ggcms and
nothing in the crontab touched it, so on 7 September 2026 it held 341.8 MB of
statistics across nineteen months, the oldest from August 2022 -- on a 25 GB
root disk with 8 GB free, on a host that the disk filling took down in 2024.
The database dumps have had `purge_database_archives.php` for exactly this
reason; statistics never got the equivalent.

It groups `<domain>/stats/YYYY-Mon.txt` and `YYYY-Mon_memory.txt` by month,
tars each completed month to `/mnt/nyc01`, proves the tarball reads back, and
only then removes the originals.

**Three refusals.**

*It never touches the current month.* That file is being appended to by every
request. The current month is computed with `date('o-M')` -- the same
expression `UserTracking` names it with, rather than something merely
equivalent. Note the `o`: that is the ISO week-numbering year, and it differs
from `Y` for a few days each January.

*It keeps recent months.* Statistics get read by looking at them, and a month
that must be untarred first will not be looked at.

*It verifies before it deletes.* Every file is read back out of the tarball
with `tar xzOf` and compared by SHA-256 against the original, and a month with
a single mismatch keeps all of its files. `tar tzf` would only prove the names
are listed, which is the half of the question that was never in doubt.

**`--keep` is a length of time, not a number of files**, and the first version
got that wrong in a way this host made obvious. It kept the last N *entries* of
the list -- identical to a calendar cutoff only when every month is present.
This host has no statistics at all for 2024 or 2025, so "the last three months
on disk" meant 2023-Sep, 2023-Oct and 2026-Aug, and it carefully preserved a
file from three years earlier. `2023-Oct` is 159 MB, the largest there is.
Fixing it took the reclaimed total from 14.9 MB to 209.5 MB.

The cutoff is anchored to the first of the month, because
`strtotime('-1 month')` from the 31st lands in the month after the one
intended -- PHP rolls forward rather than clamping.

`--month` is applied *after* eligibility, never instead of it, so naming the
current month or one inside `--keep` selects nothing rather than overriding the
rule that protects it.

**Where the space was.** Statistics were 341.8 MB of a 1.3 GB `/var/log/ggcms`;
the other 720 MB was `revoltlib.com/sql/`, two 360 MB database dumps the nightly
backup wrote to the root disk. Those now go to
`/mnt/nyc01/ggcms_sql_backups/<domain>/<backup|archive>/` -- see
`BackupTrait::databaseDumpRoot()`, which is the single answer to where dumps
live, and was five separate literals across four classes before it.

Between the two changes `/var/log/ggcms` went from 1.3 GB to 144 MB on
7 September 2026, and the root disk from 8.1 GB free to 9.1 GB.

#### `human_stats.php`

```
human_stats.php
human_stats.php --domain=revoltlib.com
human_stats.php --domain=revoltlib.com --report=landings,referrers --days=30
human_stats.php --domain=revoltlib.com --report=hours --month=2026-Sep
human_stats.php --domain=revoltlib.com --page=/bakunin/ --device=phone
human_stats.php --domain=revoltlib.com --report=visitors --top=5
human_stats.php --domain=revoltlib.com --visitor=dc58fcd1 --days=30
```

Analytics for people rather than requests. It reads
`<domain>/stats/YYYY-Mon_humans.txt`, which `humanbeacon.js` writes one line to
per page view -- and only after the reader first scrolls, types, points or
touches, which scrapers almost never do. The request log cannot answer these
questions: most of what it counts is scrapers, and a cached page never reaches
PHP to be counted at all.

With no arguments it prints one row per site for the last seven days against
the seven before. `--domain` opens that site's reports.

**A visitor** is the address, screen, language and timezone together, shown as
an eight-letter id; there is no cookie behind a beacon. **A visit** is one
visitor's run of views with no gap over thirty minutes, and belongs to the
period it started in, so none is cut in half at a boundary.

| Report | What it answers |
|---|---|
| `summary` | views, visitors, visits, pages per visit, bounce, returning, visit length, arrivals by kind, against the previous period |
| `days` | each day, with a bar |
| `pages`, `sections` | what is read, with landings and exit rate; sections group by first path segment |
| `landings` | which pages bring people in, how many come from search, and how far those visits go |
| `flows` | the page-to-page steps readers actually take |
| `referrers` | where visits come from, classed search, social, link or direct |
| `devices` | phone, tablet, desktop, and screen sizes |
| `languages`, `timezones` | browser language; timezone as a location proxy, with no address lookup |
| `hours` | hour and weekday on the reader's own clock, from the timezone the beacon sends |
| `depth`, `loyalty` | pages per visit, visit length, visits per visitor, days active, return within 7 days |
| `engagement` | which interaction woke the beacon, and how soon |
| `visitors`, `visitor` | the most active ids; one id's visits page by page |

`--report=all` prints every report but `visitor`. Periods are `--days=N`
(default 7), `--month=2026-Sep`, or `--all`. Filters keep whole visits:
`--page=/PREFIX/` keeps visits that read anything under it, `--referrer` matches
the site a visit arrived from, and `--device`, `--language` and `--timezone`
match the visit's first view. `--top` caps every list, 10 by default.

Crawlers that name themselves are left out unless `--bots` is given: a user
agent saying bot, spider, crawler or headless. On the beacon's first day,
Applebot, Baiduspider's renderer and a fleet of Windows Chromes on Tencent
Cloud addresses all ran the script and scrolled, which is why scroll no longer
wakes it. Lines from before the agent was logged have none and are always
kept.

The scripted-browser farm is left out unless `--farm` is given. From the
beacon's first day most counted "readers" shared one exact fingerprint -- a
1920x1080 screen, `zh-CN`, `Asia/Shanghai`, a new Chinese address per view, one
page and gone -- and faked the wheel and pointer events the beacon waits for,
so no interaction test catches them. On 14 September 2026 that was 348 of
wordweight's 349 views. Only the exact triple is matched (`isFarm()`), so a
reader in Shanghai on another screen, or anyone reporting UTC, is kept. The
one-row-per-site table shows how many were left out in `Farm out`.

Two readings to know. A pile of `under 50ms` in `engagement` is a script
dispatching events, not a person, and is the first place to look if the
numbers ever seem too good. And a referrer on the site itself counts as
direct: the reader was here more than thirty minutes earlier and came back
through an open tab.

Every `_humans.txt` file on disk is read and filtered by timestamp, not chosen
by filename, because `date('o-M')` names the first days of some Januaries with
the previous year. Read-only, so there is nothing to schedule.

#### `traffic_stats.php`

```
traffic_stats.php
traffic_stats.php --report=hosts,crawlers --hours=3
traffic_stats.php --host=revoltlib --misses --report=paths --top=20
traffic_stats.php --agent=Baiduspider --report=summary,minutes
traffic_stats.php --status=404 --report=paths --hours=6
```

The request-side companion to `human_stats.php`: what the front end served and
to whom. It reads nginx's access log, which sees everything -- page-cache hits
nginx answers from disk, refusals, and the renders it hands to Apache. `up=-` in
the log means nginx answered alone; anything else reached the engine, which is
the expensive part on a one-core host.

Narrow by default: the last hour, `summary` only, ten rows. It binary-searches
the log for the start of the window instead of reading from the top, so an
hour of a hundred-megabyte log costs well under a second. If the window starts
before the current log does it reads `access.log.1` too, and the summary says
where its data actually begins.

| Report | What it answers |
|---|---|
| `summary` | requests by outcome, share of pages served from the cache, engine renders per second, how many renders carried a query string and so could never be cached |
| `hosts` | each site's requests, cache share, renders, refusals and 404s |
| `crawlers` | self-declared crawlers still being served, by the renders they cost -- the candidates for the refusal list |
| `agents` | every user agent, by renders and refusals |
| `paths` | the paths costing the most renders, query strings grouped |
| `statuses`, `minutes` | status codes; requests and renders per minute |

`--minutes=N` or `--hours=N` sets the window. `--host` and `--agent` match text
anywhere, `--path` a prefix, `--status` an exact code; `--misses` keeps only
requests that reached the engine and `--pages` drops static files and the
beacon. `--log` reads another file, which is how it runs on a workstation
against a copy of the host's log. Read-only.

It found its first use on the day it was written: on 14 September 2026 only 8%
of pages came from the cache, 73% of renders carried a query string, and
`crawlers` put Claude-SearchBot and Amzn-SearchBot at the top with 18,000 and
4,000 renders in three hours -- both now refused.

### Installation — `scripts/public/install/`

| Script | Does |
|---|---|
| `check_install.php` | Verifies every requirement in Installation.md; changes nothing |
| `install_ggcms.php` | Builds the layout and offers the packaging steps; refuses the judgement calls |

`check_install.php` is the preflight and the postflight both. Run alone it
answers *is this host ready*; `install_ggcms.php` runs it before and after and
it answers *did that work*.

The installer draws a hard line. It will create directories, set owners, build
the generated-document cache, enable `rewrite`, install missing PHP extensions
and purge `javascript-common` — everything idempotent, and nothing invasive
without showing you the command and taking a yes. It refuses database
credentials, `AllowOverride`, MPM sizing, swap and DNS, because those depend on
the box and a wrong guess is worse than no guess. It names each one instead.

### Database — `scripts/public/sql/`

| Script | Does |
|---|---|
| `backup_database.php` | Dumps databases |
| `purge_database_archives.php` | Prunes old dumps so backups do not become the disk problem |
| `show_table_sizes.php` | Table sizes, for spotting growth |
| `list_databases.php` | Lists databases |
| `mysql_connect.php` | Connection test |
| `check_schema.php` | Confirms the tables exist, match the spine, and hold nothing the config forbids fetching |
| `syncdown.php` | Copies production databases down to a workstation's MySQL -- see below |

`check_schema.php` runs three checks and prints only what is wrong:

```bash
check_schema.php                        # every database, every check
check_schema.php --database=revoltlib   # one site
check_schema.php --check=enabled        # one check, every site
check_schema.php --all                  # print what passed as well
```

| Check | Compares |
|---|---|
| `tables` | every table in `clonefrom` exists here, and nothing unrecognised does |
| `spine` | `id` is an auto-incrementing primary key and the two date columns are last — see [Database.md](Database.md) |
| `enabled` | row counts against `child_types/enabled.php` |

The third is the one worth scheduling. A child table can hold thousands of
rows the ORM is never asked to fetch, because that type is switched off in
config, and nothing anywhere reported the disagreement — it took reading an
error log sideways to discover thirteen sites in exactly that state.

`clonefrom` is the reference for the first two checks rather than a list kept
inside the tool, so adding a table to the schema does not require remembering
that this file exists.

#### `syncdown.php`

A workstation that renders pages or tests changes is only as good as its copy
of the data, and the copy drifts -- content changes, and so does the schema.
On 13 September 2026 a warm on a workstation failed on thirteen pages for a
column its four-day-old copy lacked; the live host lacked it too, but nobody
could tell that without comparing. This refreshes the copy from the host on
demand and compares the schemas as it goes.

It runs on the workstation, from a checkout, and needs no installation and no
configuration repository: ssh access to the host and a local MySQL.

```bash
php syncdown.php --host=***YOUR_SSH_USER***@***YOUR_PRODUCTION_HOST_HERE*** \
    --dumps=/path/to/dumps --mysql=/path/to/mysql --local-port=3306
php syncdown.php ... --database=alldictionaries --apply
php syncdown.php ... --database=masereelgroup --tables=Entry,Description --apply
```

Dry by default: it lists every database on the host with its size and what it
would do. With `--apply`, for each database:

1. `mysqldump` runs on the host with the settings `backup_all_databases.php`
   uses, streamed over ssh into `<dumps>/<db>.sql.gz.partial`
2. the dump must end with mysqldump's `Dump completed` footer, or it is
   thrown away and the previous dump and the local copy are left alone
3. only then is it renamed to `<db>.sql.gz`, one dump kept per database
4. it is imported, which replaces every table the dump carries; a table that
   exists only locally is left in place
5. the host's and the copy's column lists are compared, table names folded to
   lower case because a Windows MySQL lowercases them, and any drift is printed

`alldictionaries` has no site configuration and is synced like any other
database; without it a copy cannot render a definition.

**`--tables` syncs a few tables rather than the database.** A test on a
workstation that mangled `Entry` and `Description` is repaired by syncing those
two; every other local table is left exactly as it was. The dump goes to its own
file, `<db>--tables--Entry-Description.sql.gz`, so the one full dump kept per
database is never replaced by a partial one, and routines and events -- which
belong to the database, not to a table -- stay out of it. Names must be plain
identifiers and anything else is refused before a connection opens.

| Argument | Default | Does |
|---|---|---|
| `--host=` | `GGCMS_SYNC_HOST` | ssh destination |
| `--dumps=` | `GGCMS_SYNC_DUMPS` | where the one dump per database is kept |
| `--database=` | all | only databases whose name contains this |
| `--mysql=` | `mysql` | local client binary |
| `--local-host=` / `--local-port=` / `--local-user=` | `127.0.0.1` / `3306` / `root` | the local server |
| `--ssh=` | `ssh` | ssh binary |
| `--apply` | off | actually dump and import |

A local password comes from `MYSQL_PWD`, which the mysql client reads itself,
never from an argument visible in the process list. Every external command is
started with an argument array rather than a shell string: on Windows
`escapeshellarg()` replaces double quotes with spaces, which quietly mangles a
command meant for the remote shell.

### Errors and issues
 — `scripts/internal/errors/`, `scripts/internal/issues/`

| Script | Does |
|---|---|
| `server_errors.php` | Lists logged 500s |
| `server_error_counts.php` | Counts them, grouped |
| `server_error_detail.php` | Newest open 500s with ids and messages; `--id=N` prints one ticket's message and trace |
| `issues_404.php` | Lists logged 404s |
| `server_issue_counts.php` | Counts logged issues |
| `clear_server_errors.php` | Clears the error log |
| `clear_server_error_by_url.php` | Clears errors for one URL |
| `scrub_server_errors.php` | Clears request data from ISE and ISI rows created before a date; dry unless `--apply` |
| `convert_ise_and_isi_tables.php` | One-off: rolls the ISE and ISI tables up into counted tickets |

Because `index.php` sets `error_reporting(0)`, these logs are frequently the
**only** evidence that anything is wrong. A silently broken page and a working
page look identical from outside.

Both queues are **counted tickets**, not journals. One defect is one row, with
an `IncidentCount` and a first-seen and last-seen date; the individual
occurrences are dates and URLs in `InternalServerErrorInstance` and
`InternalServerIssueInstance`. So a count of 4 tickets and 1,204,000 incidents
is the ordinary shape of a bad week, and the warroom's ticket page lists the
fifty most recent occurrences underneath the defect.

`convert_ise_and_isi_tables.php` converts a host that predates that. It renames
the old table aside, builds the new one beside it and imports the roll-up, so
nothing is destroyed and the conversion is reversible by a rename until you
drop the old table yourself -- it prints the command. Takes one domain, or
`all`; do one small site and read the numbers before `all`.

### Database file cache — `scripts/internal/db_cache/`

| Script | Does |
|---|---|
| `enable_db_cache.php` | Enables row-level file caching for a record type |
| `clear_db_cache.php` | Flushes it |
| `check_db_blanks_cache.php` | Checks the blanks file |
| `clear_db_blanks_cache.php` | Clears blanks |
| `clear_db_blanks_file_cache.php` | Clears the blanks file cache |

This is the pre-existing **row-level** cache (`classes/Database/DBFileCache.php`),
gated on `$globals->useDBFileCache()`, distinct from the whole-page cache in
[PageCache.md](PageCache.md). The two are complementary: the page cache stops
requests reaching PHP, the row cache speeds up the ones that do.

### Source — `scripts/internal/source/`

| Script | Does |
|---|---|
| `backup_code.php` | Tars the codebase — all, basics, dependencies, or root |

Note this is a tarball, not a git operation. Nothing on the production host
talks to git; see [Deployment.md](Deployment.md).

### Entries — `scripts/internal/entries/`

| Script | Does |
|---|---|
| `apply_translation_review.php` | Applies reviewed translation corrections to `EntryTranslation` |
| `modify_entry.php` | Drives `modify.php` from a shell — creates and edits entries |

The only tool here that writes content, and the only one that must never be
scheduled. It changes what visitors read.

```bash
apply_translation_review.php earthfluent.com --lang=es --limit=20
apply_translation_review.php earthfluent.com --lang=es --limit=20 --apply
```

**Dry by default.** Without `--apply` it prints the corrections it would make
and touches nothing. Run it dry, read the list, then run it again.

| Argument | Default | Does |
|---|---|---|
| *(first)* | asked for | The domain, as every tool here takes it |
| `--lang=` | `es` | Which language file to read |
| `--limit=` | `20` | How many records to attempt |
| `--store=` | `Development/TranslationReview/<lang>.txt` | Where the review file is |
| `--apply` | off | Actually write |

`--store` exists because `Development/` is in the repository and not in the
deployed tree — deployment copies `usr/`, `etc/` and `var/` to `/`, and the
review files never travel. Point it at a checkout, or at a file you have copied
across.

Two things it refuses to do. A record whose `current` value no longer matches
the live row is **skipped and reported**, never overwritten — that row was
edited after it was reviewed, and the edit is newer evidence than the review.
And a record already marked `shipped` is skipped, so running it twice is safe.

After a successful `--apply` it marks those records `shipped` in the store, and
flushes the page cache for the domain. It passes the domain to `FlushDomain()`
explicitly, because the engine's own path reads `$_SERVER['HTTP_HOST']`, which
does not exist in a shell — see [PageCache.md](PageCache.md).

#### `modify_entry.php`

```bash
modify_entry.php earthfluent.com --path=/ --field=Title=Updates --field=Code=updates
modify_entry.php earthfluent.com --path=/ --field=Title=Updates --field=Code=updates --apply
```

Dry by default, like everything else here.

It reimplements nothing. `modify.php` is 3,565 lines of rules that exist in no
other file — how an entry and its assignment are created together, how child
records attach, and how a date before the year 1000 is stored at all when MySQL
`DATETIME` cannot hold one. That encoding lives at `modify.php:2515` and is
decoded in `traits/GGCMSDateFormat.php`; a second implementation of it would go
wrong eventually, and silently.

So this makes the shell look like a request instead. It populates `$_SERVER` and
`$_POST`, constructs a `Handler` the way `index.php` does, and calls
`HandleRequest()`. The ordinary path runs, and every quirk comes with it.

`--path` is the entry, exactly as it is in a browser. The last segment of a URL
names the script and everything before it is the entry graph, so `--path=/a/b/c/`
edits the entry at `/a/b/c/` and needs nothing else to identify it.

**On authentication.** `modify.php` is `IsSecure` and `RequiresLogin`, so
`Authenticate()` wants a session this process does not have. Rather than store a
password on the host, the tool sets `access` directly — see
`EntryModifier::grantAccess()`, which carries the reasoning. In short: whoever
runs this already has a shell, and a shell is more power than any web login. The
login gate stops remote visitors; it is not a second lock on someone already
inside.

| Argument | Default | Does |
|---|---|---|
| *(first)* | asked for | The domain, as every tool here takes it |
| `--path=` | asked for | The entry, or for a creation its **parent** |
| `--user=` | none | `ADMIN`, or a user id. Required to save |
| `--field=` | none | `Name=Value`, repeatable. `Name[]=` for array fields |
| `--clear=` | none | Drop a record type before applying fields |
| `--action=` | `Save` | The verb posted to `modify.php` |
| `--dump-form` | off | Print the entry's form as JSON and stop |
| `--apply` | off | Actually write |

**Run it under plain `php`.** Since the 22.04 upgrade that is Ubuntu's own 8.1,
with `intl`, `zip`, `mysqli` and, from 21 September 2026, `imagick`. The tool
loads the whole engine, so a missing extension is a fatal rather than a warning
-- or worse: without `imagick` a Save wrote the Entry row, died part-way through
the image fields, and left an entry with no Assignment and so no path.
`/usr/bin/php8.0` is a leftover of the old PPA that nothing updates; do not
use it.

**Saving needs somebody to save as.** Pass `--user=ADMIN` for the administrator
account or `--user=<id>` for a particular one. Without it the tool refuses,
because `modify.php` will not save for nobody — and that is correct.

##### Creating an entry

`--path` names the **parent**. The base is the parent's rendered Edit form, but a
`Save` carrying no entry id creates a child rather than updating what it read,
and the parent is left alone:

```bash
php modify_entry.php masereelgroup.com --path=/books/ --user=ADMIN \
  --field='Title=Arc Lamps' --field='Code=arc-lamps' --field='Publish=1'
```

That adds `/books/arc-lamps/` beneath `/books/` and does not touch the Books
entry itself. It is worth confirming that afterwards rather than assuming it:
the parent's `LastModificationDate` should be exactly what it was.

##### Array fields need their brackets

A name ending in `[]` is an array field, and most of the interesting ones are.
The textbody is `Text[]`, and `textbody_Source[]` and `textbody_Language[]`
carry the matching positions:

```bash
php modify_entry.php masereelgroup.com \
  --path=/books/arc-lamps/ --user=ADMIN \
  --field='Title=Original, French Scans' \
  --field='Code=original-french-scans' \
  --field='Publish=1' \
  --field='Text[]=<p>See https://archive.org/details/LampesAArc</p>' \
  --field='textbody_Source[]=' \
  --field='textbody_Language[]=en'
```

Write `Text=` instead of `Text[]=` and the field posts as a scalar, which
`modify.php` never reads. Nothing is written, and the tool still reports success
— because nothing went wrong, the field simply was not there.

**The dry run is how you catch it.** An array field prints as `[n value(s)]` and
a scalar prints its value bare. Read that list before adding `--apply`:

```
  Title                  Original, French Scans
  Text                   [1 value(s)] <p>See https://archive.org/details/...
```

A successful save clears the page cache for every page the change touches and
names the files it removed — see [PageCache.md](PageCache.md).

### Images — `scripts/internal/images/`

| Script | Does |
|---|---|
| `scan_images.php` | Reconciles the image tree against the `Image` table; changes nothing |
| `check_image_compression.php` | Runs the quality search and reports what re-encoding would save; writes nothing |
| `compress_images.php` | Re-encodes JPEGs in place |
| `shrink_images.php` | Scales named files down to a ceiling and rewrites their stored dimensions |
| `backup_images.php` | Copies the image tree to the mounted volume, with a manifest |
| `restore_images.php` | Puts back what differs from a backup |
| `repair_image_filenames.php` | Corrects rows that name a file under the wrong name |
| `check_orphan_images.php` | Classifies files no `Image` row names; reads only |
| `check_image_references.php` | Checks `Image::N` markup against the images each entry has |
| `export_image_candidates.php` | Packages the files worth compressing, for another machine |
| `compress_export_locally.php` | Compresses such a batch; runs anywhere with cores |
| `import_compressed_images.php` | Re-checks returned files and installs the ones that pass |

The images are the largest thing on this host and the only large thing with no
other copy of it. `/srv/ggcms` is 6.0 GB, of which revoltlib's image directory
is 5.3 GB, and 957 files hold 4.08 GB of that — one 38 MB Flickr original among
them. On 7 September 2026 those files served 4.6 GB of traffic in thirteen
hours, on one vCPU shared by seventeen sites.

**The database backups do not contain any images.** They contain `Image` rows,
which are filenames and dimensions. Every one of these tools is built around
that fact.

#### Three facts about the tree

**The path is derived, not stored.** `Image.FileDirectory` holds four
characters and the URL splits them into four directories, so `cmot` is
`/image/c/m/o/t/`. Nothing stores the full path, so a tool that wants to find a
file has to know the rule.

**One row is three files.** `FileName` is the original, `StandardFileName` the
mid-size, `IconFileName` the thumbnail, and on revoltlib all 2,496 rows carry
all three. Any count that treats a row as a file is wrong by a factor of three.

**The stored dimensions are the rendered dimensions.** `PixelWidth` and its
siblings are printed straight into the `img` tag. So these tools may re-encode
a file and may never resize one — a resize makes every stored dimension a lie
and the page keeps rendering at the old size against a smaller image. The
compressor measures the file it just wrote and throws the result away if the
dimensions moved.

#### `scan_images.php`

```bash
scan_images.php revoltlib.com                      # weight, orphans, missing
scan_images.php revoltlib.com --check=orphans      # one check
scan_images.php revoltlib.com --min-size=5M        # only the heavy files
scan_images.php revoltlib.com --check=dimensions --limit=200
```

| Check | Finds |
|---|---|
| `weight` | where the bytes are, by variant and by individual file |
| `orphans` | files on disk that no `Image` row names |
| `missing` | rows whose file is not on disk |
| `dimensions` | stored dimensions against the actual image |

`dimensions` is not in the default run because it shells out to `identify` once
per file against a tree of nine thousand. Ask for it by name and it honours
`--limit`, which defaults to the fifty largest.

The reconciliation is exact rather than heuristic: every filename the database
expects is derived from `FileDirectory` and the three filename columns, so a
file is an orphan because no row names it, not because its name failed to match
a pattern.

An orphan is not necessarily rubbish. Older sites have stage variants and
hand-placed files predating the `Image` table, so the tool counts them and says
nothing about what to do with them.

#### `check_image_compression.php` and the quality search

```bash
check_image_compression.php revoltlib.com --min-size=2M --limit=20
check_image_compression.php revoltlib.com --target=43     # stricter
```

The idea is the one `jpeg-recompress` uses: re-encode at several qualities,
measure each against the original, and keep the smallest file that still clears
a fidelity threshold. A flat woodcut settles far lower than a noisy photograph
and neither has to be guessed at. `jpeg-recompress` is not packaged for Ubuntu
20.04 and building it pulls in mozjpeg, so the search is implemented here on
ImageMagick, which is already installed.

**The metric is PSNR, and that was measured rather than assumed.** This
ImageMagick has no SSIM — 6.9.10 offers AE, Fuzz, MAE, MEPP, MSE, NCC, PAE,
PHASH, PSNR and RMSE. PHASH was tried first and is unusable: against a 38 MB
scan its distance ran 0.03 at quality 95, 1.43 at 90, 0.17 at 80, 4.36 at 75
and 0.33 at 60. Not merely noisy but non-monotonic, so a search would have
called quality 60 acceptable and quality 90 not. A perceptual hash answers "is
this the same picture", which is a different question.

**The comparison is a full-resolution crop, not a downscale**, and that was
also measured. `compare` holds both images decoded and this host has 2 GB; a
7360x4912 scan is 36 megapixels and the first calibration died with "cache
resources exhausted" on every quality step. Downscaling both sides fixed the
memory and ruined the signal — across quality 85 down to 65 the whole PSNR
range was 1.8 dB. Comparing a 1200px centre crop at full resolution separates
the same range by 3.4 dB, because it keeps every artifact at the size it will
actually be stored.

The crop is taken in the read specification — `convert 'file.jpg[1200x1200+X+Y]'`
— which never materialises the whole image. On the 36-megapixel scan that is
48 MB of peak RSS and 2.2 seconds, against a full decode that could not
complete at all.

Measured on one 3024x4032 scan, which is where the 41.0 dB default comes from:

| Quality | Bytes | PSNR |
|---|---|---|
| 95 | 2,925,161 | 52.88 |
| 90 | 2,313,263 | 43.34 |
| 85 | 1,838,903 | 41.88 |
| 80 | 1,521,203 | 41.20 |
| 75 | 1,315,074 | 40.30 |
| 70 | 1,207,991 | 39.95 |
| 65 | 1,108,977 | 39.42 |
| 60 | 1,017,026 | 38.75 |
| 50 | 901,415 | 37.94 |

**Some files are simply refused, and the tools say so.** ImageMagick's
`policy.xml` on this host caps area at 128 megapixels, with memory at 256 MiB,
map at 512 MiB and disk at 1 GiB. Two of masereelgroup's woodcut scans are 142
and 145 megapixels and come back instantly with `cache resources exhausted` and
11 MB of RSS — a refusal, not an exhaustion. A 109-megapixel scan clears the
area cap and then wants roughly 870 MB decoded, which exhausts the rest.

Those limits are not the enemy. They are what stops one `convert` claiming more
than a gigabyte on a box with two, while Apache serves seventeen sites. So the
tools report the file and leave it alone rather than raising the policy, and
they distinguish `over-imagemagick-area-policy`, `over-imagemagick-limits`,
`out-of-disk` and `encoder-failed`, because reporting all four as one status
sends somebody hunting a bug that is not there. Check with:

```bash
identify -list resource
```

The search is a binary search, about six encodes per file rather than the forty
a linear sweep would take. It never encodes above the source's own quality —
raising a quality-60 file to 82 makes a larger file that has recovered nothing
— and it discards a result that came out no smaller.

The checker and the compressor share one encode function, so the sizes reported
are the sizes that would be installed, not an estimate of them.

The projected total for files beyond the sample is labelled an extrapolation
because it is one. Files are tested largest first and large files compress
proportionally better, so the measured rate is the optimistic end.

#### `compress_images.php`

```bash
compress_images.php revoltlib.com --min-size=2M            # dry
compress_images.php revoltlib.com --min-size=2M --apply
```

The only tool here that changes what visitors are served. Dry by default, and
built around three refusals.

**It refuses to run without a backup.** `--apply` does nothing unless a backup
exists for the domain, because a bad run with no backup is a permanent loss of
somebody's archival scans. The message names the command that fixes it.

**It refuses to change dimensions.** Nothing passes `-resize`, but intent is
not a guarantee: the written file is measured before it replaces the original,
and a result whose dimensions moved is thrown away and reported.

**It refuses to compress the same file twice.** This is the one that would have
gone unnoticed. A second run over an already-compressed tree sees a quality-80
file, searches below it, finds 72 acceptable and re-encodes — and JPEG
generation loss is cumulative and invisible one step at a time. So every file
written is recorded in a ledger with the hash of what was written, and a file
whose hash still matches its entry is skipped. Restore or edit the file and the
hash stops matching and it becomes eligible again, which is right in both
directions. `--force` overrides this and should not be routine.

The ledger is `compressed.tsv` in the domain's backup directory.

Writes are atomic within the filesystem: the encode goes to a temporary file
beside the original and is renamed over it, never copied over it.

**Never schedule this one.** It changes what visitors see, like
`modify_entry.php`.

#### `shrink_images.php`

```bash
shrink_images.php masereelgroup.com                 # dry: every named file over 1000x1000
shrink_images.php masereelgroup.com --max=1000 --apply
```

The one image tool that resizes. A site that keeps its archival scans
elsewhere -- masereelgroup.com links every book to archive.org -- has no reason
to serve a 12,000-pixel original, and on 16 September 2026 fourteen of them were
46.7 MB of its 54.1 MB tree. They became 2.2 MB.

It scales every file an `Image` row names, in any variant, down to fit within
`--max` by `--max`, and writes that variant's two dimension columns from the file
as written. That is what the other tools refuse to do and why: the stored
dimensions are the rendered ones, so this tool owns both halves. A result still
over the ceiling, or whose aspect ratio drifted more than a percent, is thrown
away. Orphans are not touched.

`--apply` refuses without a backup, for a stronger reason than the compressor's:
a backup is the only full-resolution copy left on the host. Take one with
`backup_images.php DOMAIN --min-size=0 --apply` first.

The `UPDATE` bypasses the ORM, so it invalidates the `Image` row cache itself.
Flush the domain's pages after, not before.

#### `backup_images.php` and `restore_images.php`

```bash
backup_images.php revoltlib.com --min-size=1M --apply
restore_images.php revoltlib.com --list
restore_images.php revoltlib.com                      # what has changed
restore_images.php revoltlib.com --apply              # put it back
restore_images.php revoltlib.com --file=c/c/6/k/627-x.jpg --apply
```

A backup is a directory of files, not a tarball. A tarball is smaller and
tidier and it is the wrong choice: restoring one file out of a 5 GB tar means
reading most of the tar, and the overwhelmingly likely restore is the one file
that came out wrong, not all nine thousand.

Backups live on `/mnt/nyc01`, never the root disk. Root is 25 GB with about
8 GB free and revoltlib's images alone are 5.3 GB, so a backup written beside
them fills the disk that serves all seventeen sites. The tool checks free space
and refuses rather than half-filling the volume — a backup that stops two
thirds through is worse than none, because the compressor's check for "is there
a backup" would find the directory and believe it.

Every backup carries `manifest.tsv` — relative path, size, mtime and SHA-256.
The hash is computed from the bytes as they are copied, so it costs nothing
beyond a read that was happening anyway.

**Restore restores by comparison.** Every manifest entry is hashed where it now
sits and only the files that differ are copied back. Restoring nine thousand
identical files to fix four would touch every mtime on the tree and make the
log useless. Its dry run is the useful half most of the time: it answers "what
has changed since the backup", which is the question actually being asked after
a compression run.

Neither tool flushes the page cache. Pages already built hold the old image
sizes, so flush the domain after a restore or a compression run.

#### `repair_image_filenames.php`

```bash
repair_image_filenames.php revoltlib.com            # dry
repair_image_filenames.php revoltlib.com --apply
```

Found by `scan_images.php` on its first real run: 51 rows on revoltlib name
their three files without the `<Entryid>-` prefix the actual files carry. The
row says `m/f/i/5/6336636155_e89bdd7638_o.jpg`, the disk holds
`m/f/i/5/437-6336636155_e89bdd7638_o.jpg`, and `Entryid` is 437. Nothing is
lost — every picture is present — but the page builds the name from the row, so
the `img` tag points at nothing.

Live, that URL returns **200 with zero bytes of `text/html`**, which is the same
broken-image signature the malformed `/image//` fallback produced. They appear
twice in a scan: once as `missing` rows and again among the orphans.

It only ever prepends the row's own `Entryid` to the row's own filename. It
never composes a name from a directory listing and never guesses between
candidates.

Two conditions before it writes. **Every variant must agree** — bare name absent
and prefixed name present for original, standard and icon alike; a row that
fits only partly is reported and left alone, because a half-repaired row renders
two images and a hole, which is harder to notice and no better. And **the file's
dimensions must match the ones already on the row**, which is what separates
"the same picture under its proper name" from "some other picture that sorts
nearby". Rows with zero stored dimensions skip that comparison, since absence is
not disagreement.

On revoltlib all 51 passed both, with no partial fits.

**It invalidates the row cache, and the order matters.** The repair writes with
a prepared `UPDATE`, which reaches the database without passing through the ORM,
so none of the engine's own invalidation fires. `DBFileCache.php` describes what
happens next better than any note here could:

> the page cache correctly flushes, the page then re-renders, reads the STALE
> row, and writes a fresh page cache holding the old title. Caching faithfully
> preserves the mistake.

That is not hypothetical. On 7 September 2026 the page cache for revoltlib was
cleared first and the row cache not at all; the pages rebuilt from stale rows
and re-cached the same broken filenames, and it was caught only by fetching a
page afterwards and grepping it for the corrected name. **Rows first, pages
second.** The tool now does the rows itself and says so.

Only `ggcms_EntryChildRecords/Image/<Entryid>` carries these filenames -- the
other six cache types were searched for one of the repaired names and none held
it.

Finding which cached pages are affected needs care, and two attempts at it were
wrong. Every repaired name is a *suffix* of its corrected version --
`9482--Rosa-Luxemburg.jpg` contains `-Rosa-Luxemburg.jpg` -- so a plain
substring search matches the fixed pages as readily as the broken ones, and
reported 635 where the truth was 378. Anchoring the pattern on the path
separator distinguishes `/w/name.jpg` from `/w/424-name.jpg`. And the pattern
list must cover all three variants, not only `FileName`: `<img src>` uses
`StandardFileName`, and icons appear on every listing page, which is why the
real figure was 8,401 pages and not a few hundred.

Updates go one statement per row rather than one statement for the lot: these
are corrected on the evidence of files checked individually, and a single
`UPDATE` spanning fifty-one would land or fail on a `WHERE` clause that repeats
none of that checking.

#### `check_orphan_images.php`

```bash
check_orphan_images.php revoltlib.com
check_orphan_images.php revoltlib.com --class=unreferenced --all
```

`scan_images.php` counts orphans; this answers the question that comes after the
count, because the count alone invites the wrong conclusion. revoltlib has 1,680
files no `Image` row names, holding 396.7 MB, and most have an explanation:

| Class | Files | Bytes | Meaning |
|---|---|---|---|
| `prefix-twin` | 155 | 125.5 MB | the real file; its row names it wrongly |
| `variant-of-orphan` | 964 | 65.4 MB | icon or standard of another orphan |
| `text-referenced` | 0 | 0 B | named in body text; a page links it |
| `unreferenced` | 561 | 205.8 MB | nothing anywhere names it |

So of 1,680 "orphans", 155 are pictures that must not be touched and 964 are
variants that share their base's fate. Only the last class is worth looking at
by hand, and even that is not a delete instruction.

**It reads and reports. It deletes nothing and moves nothing.** These are scans
of artwork and photographs of the dead, there is no second copy anywhere, and no
classification here is confident enough to justify a delete flag.

`text-referenced` is the check that earns the tool its place: a file can be
linked from an article body with no `Image` row anywhere, and deleting it breaks
that page. The naive form is a `LIKE` for every orphan against every body of
text — 1,680 queries against a `mediumtext` column. Instead the text is narrowed
once by extension, which on revoltlib returns 110 rows, and matching happens in
PHP against those. Same answer, one query. (Those 110 all turned out to be
external URLs at theanarchistlibrary.org and Anarchy Archives, so revoltlib's
count is zero — but the check is the reason a delete could ever be considered.)

#### `check_image_references.php`

```bash
check_image_references.php revoltlib.com
check_image_references.php revoltlib.com --check=unreferenced --all
```

**`Image::3` is not an Image id.** `view.php:735` resolves it as a one-based
index into the entry's own image list:

```php
$number = (int)$dom_piece;
$image = $images[$number - 1];
```

So `Image::3` is the third image attached to *this* entry, and the same number
means a different picture on every page. `FullImage::` works the same way.

**A dangling reference is invisible.** The next line is:

```php
if($mobile_friendly || !$image) {
    $dom[$i] = '';
}
```

A reference past the end of the list becomes an empty string — no warning, no
ticket, no gap in the markup. The paragraph closes over where the picture should
have been and reads as though it was never meant to have one. With
`error_reporting(0)` set in `index.php`, a page missing its illustration and a
page never given one are identical from outside. Nothing but this will tell you.

On revoltlib, out of 1,470 entries carrying the markup:

| Entryid | Points at | Has | Title |
|---|---|---|---|
| 22 | 4, 5 | 2 | Peter Arshinov |
| 3792 | 4, 5 | 2 | Anonymous |

plus two entries carrying `Image::1` with no images at all (3091 *Practical
Socialism*, 3671 *Front Material*). Four broken illustrations in fourteen
hundred pages, and none of them reachable any other way.

One implementation note. The pattern is `(?:Full)?Image::([0-9]+)`, one
alternation rather than two passes, because `FullImage::1` contains `Image::1`
as a substring — a regex for `Image::` alone counts every full-size reference
twice and reports the second copy as a phantom. That is also the order
`view.php` runs its two replacements in, for the same reason.

`unreferenced` — images no marker places inline — is **not** in the default run,
because it is not a fault. Templates display an entry's images through the icon
and standard blocks without any markup; placing one in the prose is the
exception. It is reported when asked for, and labelled as information.

#### Compressing somewhere else — the export/import pipeline

The droplet is the wrong machine for this work. Measured on 7 September 2026:

| | |
|---|---|
| Compressing revoltlib's 491 large JPEGs *on the droplet* | ~16 hours of niced CPU, contending with Apache |
| Download from droplet | ~10 MB/s |
| Upload to droplet | ~8.7 MB/s |
| Moving all 3.2 GB down | ~5½ minutes |
| Sending results back | ~4 minutes |

Ten minutes of transfer against sixteen hours of contended CPU, on a box with
one vCPU serving seventeen sites.

```bash
# on the server
export_image_candidates.php revoltlib.com --to=/mnt/nyc01/batch1 --min-size=1M --apply

# fetch it, then on a machine with cores
compress_export_locally.php batch1 --jobs=16

# send it back, then on the server
import_compressed_images.php revoltlib.com --from=/mnt/nyc01/batch1
import_compressed_images.php revoltlib.com --from=/mnt/nyc01/batch1 --apply
```

**The desktop is a processor, not an authority.** It can be a different
machine, a different ImageMagick and a different metric, and the files cross a
network twice. So the import re-checks all five of these and refuses anything
that fails, rather than installing it:

| Check | Because |
|---|---|
| a backup exists | the images are on no other disk |
| the live file still matches the manifest hash | it changed while the batch was away, so the result answers a question about a file that no longer exists |
| the returned file matches its recorded hash | a truncated JPEG is frequently still a valid JPEG, of the top of the picture |
| dimensions match the manifest | a resize makes every stored `PixelWidth` a lie |
| it is actually smaller | a result that is not smaller spends a generation of quality for nothing |

Only then is it renamed into place, and only then does it enter the ledger.

**One implementation of the search, not two.** The whole value of this is that
the number `check_image_compression.php` reports is the number you get, so
`compress_export_locally.php` loads the same `ImageQualitySearch` trait and
passes it different settings. A second implementation written for the other
machine would drift, quietly, and the two would disagree about files nobody
re-tested.

**What the extra compute buys** is not only speed:

- `--regions=5` measures five windows per file and keeps the **worst**, instead
  of trusting the centre crop. Damage is not evenly spread — a portrait with a
  calm centre and detailed edges passes on one window and loses the edges.
  Averaging would let a calm sky pay for a ruined face. The server stays at 1.
- **SSIM**, where ImageMagick 7 offers it. It weighs structural damage, where
  PSNR only sums squared error and cannot tell a smeared face from an evenly
  noisy sky. The tools ask `compare -list metric` and prefer it automatically;
  ImageMagick 6 on the droplet has no SSIM and keeps PSNR.

Because the metric may differ from the server's, every result row records which
metric and threshold produced it, and the import prints both. **41 dB and 0.995
SSIM both mean "almost indistinguishable" and neither can be read as the
other.** Direction matters too — DSSIM is a distance and falls as fidelity
rises; getting that backwards would not error, it would quietly select the
worst quality that still encoded, on every file, with plausible-looking tables.

**On not upgrading ImageMagick on the droplet.** The engine uses the imagick
PHP extension (`modify.php:2774`, `SimpleImages.php:79`) and it is compiled
against the exact library that would be replaced — `ImageMagick 6.9.10-23`.
Ubuntu 20.04 offers no ImageMagick 7, so it is a source build, after which the
extension the image-upload path depends on is linked against a library that
arrived an hour ago. It also buys nothing: the server only needs `identify` for
export and import, and no metric ever runs there.

**Parallelism.** PHP has no `fork` on Windows, so `--jobs=N` re-runs the same
script as N child processes, each taking every Nth file, merging their result
files at the end. Each child is read to completion *before* being waited on — a
child that fills its stdout pipe blocks forever, and `proc_close` on a blocked
child never returns, which looks exactly like a slow encode and is not.

`compress_export_locally.php` is the one entry point here that does not read
`/var/www/ggcms_cli_directories.php`, because it is meant to run on a machine
with no GGCMS installation. It works its paths out from `__DIR__` instead, and
needs no database, no domain and no configuration.

## Notes for anyone adding a tool

* Keep the entry point thin. Paths, requires, one instantiation, one call.
* Put shared capability in a trait, not a base class. That is the existing
  pattern and the traits compose cleanly.
* **Filter aggressively and default to the narrowest useful scope.** These
  tools are read by people and by AI assistants; a full unfiltered sweep is
  tens of thousands of rows and useful to neither. Take plenty of arguments.
* Anything worth writing is worth scheduling. Add it to the crontab in
  [Operations.md](Operations.md) in the same change.
