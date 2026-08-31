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

### Page cache — `scripts/public/cache/`

| Script | Does |
|---|---|
| `warm_cache.php` | Requests the most-asked-for pages so the cache is built before a reader waits for it |

```bash
warm_cache.php --domain=revoltlib.com              # top 50 uncached pages
warm_cache.php --domain=revoltlib.com --limit=200  # more of them
warm_cache.php --domain=revoltlib.com --quiet      # totals only, for cron
```

It reads the **access log** rather than the sitemap. A sitemap is every URL
that exists, tens of thousands of them, in an order that means nothing; the log
is what people asked for, and asking twice is the only evidence a page matters.
Already-cached URLs are skipped unless `--force`. Requests go to `127.0.0.1`
with the `Host` header set, so warming costs no TLS handshake and cannot be
affected by DNS.

`--domain` is optional once the vhosts log in `vhost_combined`, which names the
site at the start of every line. Under the older `combined` format a line
cannot be attributed to a site at all, and `--domain` is then required --
without it the warmer has nothing to go on and says so. Both forms are handled,
because a rotation straddling the change leaves both in one file. See
[Operations.md](Operations.md) for the switch.

The real prize is calling this from `deploy.sh` after the flush, so a deploy
stops costing half an hour of eight-second pages.

### Errors and issues — `scripts/internal/errors/`, `scripts/internal/issues/`

| Script | Does |
|---|---|
| `server_errors.php` | Lists logged 500s |
| `server_error_counts.php` | Counts them, grouped |
| `issues_404.php` | Lists logged 404s |
| `server_issue_counts.php` | Counts logged issues |
| `clear_server_errors.php` | Clears the error log |
| `clear_server_error_by_url.php` | Clears errors for one URL |

Because `index.php` sets `error_reporting(0)`, these logs are frequently the
**only** evidence that anything is wrong. A silently broken page and a working
page look identical from outside.

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

## Notes for anyone adding a tool

* Keep the entry point thin. Paths, requires, one instantiation, one call.
* Put shared capability in a trait, not a base class. That is the existing
  pattern and the traits compose cleanly.
* **Filter aggressively and default to the narrowest useful scope.** These
  tools are read by people and by AI assistants; a full unfiltered sweep is
  tens of thousands of rows and useful to neither. Take plenty of arguments.
* Anything worth writing is worth scheduling. Add it to the crontab in
  [Operations.md](Operations.md) in the same change.
