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
