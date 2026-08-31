# Project Conventions

Vocabulary and habits specific to GGCMS. These are not general programming
conventions — for those see [../Docs/CodeConventions.md](../Docs/CodeConventions.md),
which covers args hashes, naming and layout. This file covers how the project
*thinks*.

## Look for the existing method first

**Assume it already exists.** Twenty years of a single developer means that for
almost any question of the form "how do I do X in this system?", several methods
already exist that should be useful.

This is not a nicety. On 30 August 2026 a session spent an hour grepping a 7 GB
Apache log for information that `server_error_counts.php` returns in one
command, correctly attributed per domain, because the assistant did not check
whether the capability already existed.

Before writing anything, check:

| | |
|---|---|
| `usr/lib/ggcms/cli/` | 23 classes, 22 traits, 22 entry points of diagnostics and maintenance |
| `usr/lib/ggcms/src/traits/` | Shared script behaviour — `SimpleORM`, `SimpleForms`, `SimpleErrors`, `DBFunctions` |
| `usr/lib/ggcms/src/classes/` | The engine, by subsystem |
| `classes/StandardLibraries.php` | The master list of everything loaded on a request |

The historical failure mode on this project has never been missing tools. It is
tools that exist, work, and were never scheduled or never found.

### The CLI tools are documentation

They are not only utilities. Each one is an executable statement of how part of
the system is *supposed* to work, checked against reality — which makes them the
most reliable description of the project's quirks that exists. Prose drifts;
these do not, because they run.

Read them the way you would read a manual for third-party software with very
specific behaviour:

* **`classes/Domain/DomainChecker.php`** is the specification of a healthy
  domain, in five layers: certificate PEM structure, Let's Encrypt renewal
  config, the `:80` and `:443` vhost keys that must exist and point at real
  files, the filesystem layout, and the database build. Nothing else states
  those invariants in one place.
* **`traits/SSL.php`** documents the format of
  `/etc/letsencrypt/renewal/<domain>.conf` and which of its keys matter, plus
  the exact vhost directives GGCMS expects.
* **`classes/Database/DBCacheEnabler.php`** and the `db_cache` scripts reveal
  that the row cache records *known-emptiness* as a fact — a "blanks" file — 
  which is not obvious from `DBFileCache.php` and matters enormously when
  diagnosing a page that renders structurally correct but empty.
* **`traits/CLIAccess.php`** documents the argv convention every tool follows:
  `argv[1]` is the domain, `argv[2]` is the confirmation, so anything can run
  unattended.

When you need to understand a subsystem, read its CLI tool before its class.

## Know which layer you are in

Before writing anything, know whether you are in `classes/`, `scripts/`,
`modules/` or `templates/`, because the rules differ and they are strict. SQL
belongs in `classes/` and nowhere else; a script is "move x to y"; a template
presents data it was handed; a module takes the handler and nothing else.

Full description in
[../Docs/Architecture.md](../Docs/Architecture.md#layers-and-where-code-belongs).

## ISE and ISI

Two distinct records, two distinct questions, and the difference is load-bearing
vocabulary. Use these terms in conversation; they mean something precise here.

### ISE — `InternalServerError`

**Something broke.** A fatal, an uncaught exception, a request that could not be
served. Written by `classes/Error/ErrorLogging.php`.

An ISE is a defect. It has a stack trace, it names a file and a line, and
somebody should fix it. The table captures `ErrorMessage`, `URL`,
`ServerVariable`, `PostVariable`, `GetVariable` and `EnvironmentVariables`, plus
a `Resolved` flag so triage state lives with the record.

An ISE is not limited to a PHP fatal, an uncaught exception or a caught
exception. Any significant operation required to serve or maintain the system
that reports failure is an ISE, including failures reported silently.

This includes commands and extension APIs such as `mysqli` that may signal
failure through a return value, status property, error code or error object
rather than by throwing. Those failure channels must be checked explicitly and
the operation's useful particulars preserved in the ISE.

Do not turn best-effort trivia into ISE noise. A small presentation helper such
as `ucfirst()` producing no useful result is not equivalent to a database
connection, file write, deployment command, cache flush or other operation whose
failure prevents correct service or risks losing state. Consequence is the
boundary.

Read them with:

```bash
cli/scripts/internal/errors/server_errors.php
cli/scripts/internal/errors/server_error_counts.php
```

### ISI — `InternalServerIssue`

**Something is odd.** Not a failure — an *anomaly*. The question an ISI answers
is "I wonder if that will ever be a problem?" Written by
`classes/Error/IssueLogging.php`.

Corrupt UTF-8 in incoming data is the archetype: nothing crashed, the request
was served, but something arrived that should not have. A 404 is logged as an
ISI for the same reason — one is noise, ten thousand for the same path is a
broken link somewhere on the internet worth knowing about.

```bash
cli/scripts/internal/issues/server_issue_counts.php
cli/scripts/internal/errors/issues_404.php
```

**The distinction matters when deciding where to log something.** If the request
still produced a correct response, it is an ISI. If it did not, it is an ISE.
Logging an anomaly as an error trains everyone to ignore errors; logging a
failure as an anomaly hides it.

## Prefer the database record to the log file

Apache's log is `combined`, not `vhost_combined`, so it does **not** record which
of the seventeen sites served a request. The ISE and ISI tables do, because they
live in each site's own database.

They also survive log rotation, carry full error messages rather than truncated
lines, capture the request environment, and have a `Resolved` flag. When
diagnosing behaviour, reach for the tables first and the log second.

Note that CLI PHP currently lacks `mysqli`, so these tools need:

```bash
php8.1 -c /etc/php/8.0/apache2/php.ini <script> <domain> y
```

Most CLI tools take `argv[1]` as the domain and `argv[2]` as the confirmation,
so they run unattended. See [../Docs/Triage.md](../Docs/Triage.md) for the
underlying `mysqli` gap.

## Repair, don't refuse

Covered in full in [Principles.md](Principles.md). Summarised here because it
governs where a fix belongs: malformed input whose intent is unambiguous gets
repaired on arrival rather than redirected or rejected. Be liberal about form,
never about authority.

## Encode URLs that travel inside other URLs

This project puts URLs inside URLs constantly — every social share link is
`someservice.com/share?url=<one of our pages>` — and `Handler` spends its life
repairing query strings that arrived malformed. Both halves of that make
encoding a project concern rather than a general one.

**A URL used as the value of a `GET` parameter must be percent-encoded.** `?`
becomes `%3F`, `#` becomes `%23`, `&` becomes `%26`, `/` becomes `%2F`. Without
it the receiving server cannot tell where its own query string ends and ours
begins — the same ambiguity `Construct_RepairQueryString()` exists to clean up
at the other end.

**URL-encoding is not HTML-encoding.** `&amp;` where `%26` was meant is a
silent and common failure, and it produces a link that looks correct in source
view.

**In JavaScript, do not call `encodeURIComponent()` or `encodeURI()` directly,
and never call `escape()`.** None of them encode the RFC 3986 sub-delimiters
`! ' ( ) *`. Use the MDN replacements — `fixedEncodeURI()` for a whole URL,
`fixedEncodeURIComponent()` for a piece of one.

PHP's `urlencode()` and `rawurlencode()` do not have this defect and need no
wrapper. That asymmetry matters here because `classes/API/SocialMedia.php` and
`javascript/social-share-media.js` do the same job on the same sixteen
parameters in the two different languages.

Full detail, including the mask-driven entity conversion, the `mb_` function
table and the format-boundary rule, is in
[../Docs/EncodingConventions.md](../Docs/EncodingConventions.md). Summarised
here because it governs *where* a fix belongs: encode on the way out, for the
grammar being written into, and never store pre-escaped content.

`var/www/html/javascript/social-share-media.js` currently calls
`encodeURIComponent()` directly sixteen times and `font-wars.js` once — noted
31 August 2026, see [../Docs/Triage.md](../Docs/Triage.md).

## `BT:` marks the author's own notes

101 of them, usually inside a commented-out debug `print`. They are decisions as
often as defects. Do not bulk-remove them and do not act on one without asking.
