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

The disk filling is what took the production host down. Run this daily.

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

### Errors and issues — `scripts/internal/errors/`, `scripts/internal/issues/`

| Script | Does |
|---|---|
| `server_errors.php` | Lists logged 500s |
| `server_error_counts.php` | Counts them, grouped |
| `issues_404.php` | Lists logged 404s |
| `server_issue_counts.php` | Counts logged issues |
| `clear_server_errors.php` | Clears the error log |
| `clear_server_error_by_url.php` | Clears errors for one URL |
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

## Notes for anyone adding a tool

* Keep the entry point thin. Paths, requires, one instantiation, one call.
* Put shared capability in a trait, not a base class. That is the existing
  pattern and the traits compose cleanly.
* **Filter aggressively and default to the narrowest useful scope.** These
  tools are read by people and by AI assistants; a full unfiltered sweep is
  tens of thousands of rows and useful to neither. Take plenty of arguments.
* Anything worth writing is worth scheduling. Add it to the crontab in
  [Operations.md](Operations.md) in the same change.
