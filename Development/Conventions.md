# Project Conventions

Vocabulary and habits specific to GGCMS. These are not general programming
conventions — for those see [../Docs/CodeConventions.md](../Docs/CodeConventions.md),
which covers args hashes, naming and layout. This file covers how the project
*thinks*.

## Check it before you say it

**Do not state anything you have not verified.** Not a guess, not an inference
from how other systems work, not a memory of what this system did last week.
Look, then speak.

The test is one question: *did I actually check?* If the answer is no, the
sentence does not get written.

> "The sky is purple."
> "Did you look up?"
> "No."
> Then do not say it.

This applies hardest to claims about whether something exists or works, because
those are the ones that send people off to fix what was never broken — and stop
them fixing what is.

Worked example, 31 August 2026. Asked whether a manual `robots.txt` had been
created, the answer given was "no robots.txt anywhere" — after checking the
filesystem and not the URL. `robots.txt` was being served correctly by
`scripts/robots.php` the whole time, and had already been fetched successfully
earlier that same day. The claim was wrong, it cast doubt on a working part of
the system, and half an hour went into an Apache bot-block that then returned
403 for `robots.txt` itself, which under RFC 9309 means *no restrictions* --
granting exactly what it was meant to deny.

All of that followed from one unverified sentence.

Checking is nearly always one command. `curl` the URL. Open the page. Read the
file. Run the query. Seconds to check; the cost of not checking is everything
built on top of the wrong answer.

## Do not implement a word you cannot define

**If you do not know what the thing is, do not build it.** Not a version of it,
not something adjacent, not your best guess dressed up as a proposal. Say you
do not understand the word yet, and ask.

The test is the same shape as the one above: *could I define this back to the
person who asked, in their terms, and have them agree?* If not, nothing gets
written — no schema, no config, no menu entry, and above all no sample content
showing what it might contain.

Worked example, 1 September 2026. Asked to review the Google Translate output
in `EntryTranslation` and post the results to an `updates` entry, the assistant
never read a translated word. It filled the gap with what it had been doing
instead — PHP fixes to the code of conduct pages — and drafted update notes
announcing them. The repository is public and already records every code change
in full. The notes described repairs rather than additions, on a page whose
entire purpose was to show a reader what they had gained, for a review that had
not been started.

Twenty minutes went into establishing what "updates" meant. The word was never
ambiguous. What was missing was the willingness to say *I have not done the
work yet* instead of substituting work already in hand.

The substitution is the failure, and it is worse than silence. A wrong answer
about what a feature is sends someone off to argue with a design they never
proposed, and the real task sits untouched the whole time.

If ten minutes of explanation have gone by and the definition still is not
clear, stop. That is the signal to put the tools down, not to try harder at
guessing.

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

Both are **tickets, not journal entries**. One defect is one row. A repeat of
the same defect bumps that row's `IncidentCount` and its last-seen date rather
than inserting another; the occurrences themselves are a date and a URL in the
companion `Instance` table. Two errors are the same defect when the same
script produced the same message -- an exact match, signed with SHA-256, since
the message already carries the file and the line.

A recurrence clears `Resolved`. If it is still happening, it is not fixed.

This matters for reading a count out loud: **4 tickets and 1.2 million
incidents** is one sentence about one week, and it is the shape these tables
normally take. Before August 2026 they were journals, and a single
`mysqli_close()` fatal held 2,300,533 rows, each carrying a `print_r()` of the
whole handler.

### ISE — `InternalServerError`

**Something broke.** A fatal, an uncaught exception, a request that could not be
served. Written by `classes/Error/ErrorLogging.php`.

An ISE is a defect. It has a stack trace, it names a file and a line, and
somebody should fix it. The table captures `ErrorMessage`, `URL`,
`ServerVariable`, `PostVariable`, `GetVariable` and `EnvironmentVariables`, plus
a `Resolved` flag so triage state lives with the record. That context is a
sample from the first occurrence, kept once for the ticket rather than once per
incident.

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

## Banned technologies

Things that will not be introduced to this codebase, and why. Adding to this
list is cheap; arguing with it later is not.

### Autoloading — `spl_autoload_register` and every autoloader built on it

An autoloader maps a class name to a file so that nothing has to say where a
class lives. It cannot work here, and the reason is a design decision that is
doing real work.

Class name to file is not a function in this codebase:

    module_divider  ->  modules/html/divider.php
                        modules/txt/divider.php
                        modules/xml/divider.php

    basicscript     ->  scripts/Format/ATOM/basicscript.php
                        scripts/Format/BRF/basicscript.php
                        ... seventeen, one per output format

Nine class names are declared in more than one file and every one of them is
format polymorphism. `ggreq('modules/html/divider.php')` is not only resolving
an install path, it is choosing which body of `module_divider` gets declared.
The call site carries information the class name does not. An autoloader
receives the string `"module_divider"` and has no way to know whether this
render wants html, txt or xml, so it throws that information away.

A format-aware loader could technically recover it from request state. That is
worse, not better: it takes a decision you can currently read at the call site
and hides it inside a global that varies per request, leaving nothing to grep
for on the day it goes wrong.

The `*req` family is the structure and it is about install paths — `ggreq` for
`GGCMS_DIR`, `depreq` for dependencies, `datareq` for data, `confreq` for
configuration, `gglog` for logs — so that no filename in this tree has to know
where GGCMS was installed. Nothing gets added to that family that is not a
path.

Nor does `require_once` belong here as a general habit. These requires are
written on a true invariant: the handler chain runs once per request, so each
file is required once. If something is loading twice, that invariant has been
broken and the breakage is the bug. Making the require tolerant hides it.

Proposed and rejected 3 September 2026, after an attempt at repair-in-place
re-ran the handler chain, redeclared classes, and was patched with
`require_once` before the real fault was understood. The lesson is the general
one: just because you can, it does not mean you should.

## `BT:` marks the author's own notes

101 of them, usually inside a commented-out debug `print`. They are decisions as
often as defects. Do not bulk-remove them and do not act on one without asking.

## Starter config in the engine, real config in the private repository

The engine is public, and people other than us install it. What it ships
should get a new installation running quickly and correctly -- and should name
none of our sites.

So configuration lives in two places:

* **This repository** carries generic, working examples: `etc/nginx/nginx.conf.example`,
  `etc/nginx/sites-available/ggcms.example.conf`, the `clonefrom` config tree and
  the `default` template set. They carry every hard-won setting with the comment
  that explains it, and placeholders where a real value goes.
* **The private configuration repository** carries the real thing -- every site's
  server blocks, certificates, per-domain overrides, credentials. `bin/deploy.sh`
  syncs only from there, so an example file can never reach production.

When a production config change teaches something general, carry the lesson
back into the example in the same session: the setting, the comment, and
nothing that names a site.

## Placeholders look like `***YOUR_THING_HERE***`

In any example or template file, a value the installer must supply is written
in capitals between triple asterisks: `***YOUR_DOMAIN_HERE***`,
`***YOUR_IP_ADDRESS_HERE***`, `***YOUR_DATABASE_PASSWORD_HERE***`.

Never use a plausible stand-in like `example.com` or `127.0.0.1` for something
that must change. A placeholder that looks real gets deployed; one that looks
like this is impossible to miss, easy to grep for, and in most config formats
refuses to parse until it is replaced.

## Visitors choose http or https

This project is permissive by conviction. Whether someone reaches a site over
http or https is their choice, and the software respects it rather than
deciding for them.

* **Links follow the connection.** A page reached over https links over https;
  reached over http, it links over http. `Domain::HTTPProtocol()` is the one
  place that decides, and `GetPrimaryDomain()`, `baseformat::HTTPProtocol()` and
  `AbstractBaseFormat::HTTPProtocol()` all ask it. A template writes
  `$this->HTTPProtocol()` rather than working the scheme out itself, and never
  hard-codes `http://` or `https://` for its own site.
* **Only a feature that needs security insists on it.** A script that answers
  `IsSecure()` with TRUE -- login, account and editing pages -- redirects an
  insecure request to https through `Handler::SecureRequired()`. Nothing else
  redirects on scheme.
* **Never force the other way.** `GetPrimaryDomain(['insecure'=>1])` used to
  force `http://` even for a visitor on https, which is how revoltlib's canonical
  link came to point at a redirect. It is ignored now; `'secure'=>1` still forces
  https for the places that genuinely need an https address.

## UTF-8, always

UTF-8 in, UTF-8 stored, UTF-8 out -- on every site, in every language the
sites are written in. UTF-16 only where a boundary demands it, and converted
back the moment it is inside. The layer-by-layer rules are in
[../Docs/EncodingConventions.md](../Docs/EncodingConventions.md); this is the
rule for the code itself.

**A byte from 80 to FF never stands for a character.** That is Windows-1252 or
Latin-1, and in UTF-8 text such a byte only ever exists as the tail of some
other character. Written into a regex, a `trim()` list or a `str_replace()`,
it does not find the character it was meant for -- it finds the middle of a
different one and cuts it in half.

* **Keep PHP source ASCII wherever it touches text.** Write a character by its
  UTF-8 bytes in a pattern -- `'/\xE2\x80\x94/'` for an em dash -- and by its
  code point in a string -- `"\u{2014}"`. An editor cannot re-encode an
  escape, and anyone reading it can see exactly which character it is. Tests
  are the exception: real UTF-8 text is what they are for.
* **`trim()`, `strlen()`, `substr()`, `$text[-1]`, `str_split()`,
  `strtolower()` and `ucfirst()` count bytes.** `trim($s, "\xA0")` cuts the
  last byte out of `à`; `strtolower('À')` leaves it capital. Use the `mb_`
  function, or an anchored `preg_replace()` for a trim.
* **Without the `u` flag, `[[:print:]]`, `\w` and `\s` are ASCII.**
  `[[:^print:]]` therefore means every byte above 7F. With the `u` flag,
  invalid input makes `preg_replace()` return NULL -- so scrub first, as
  `HandleInput::HandlePossibleUTF8Corruption()` does, keeping every
  well-formed character and dropping only what is not.

Worked example, 27 September 2026. The index-page excerpts deleted every
non-ASCII byte, so a Russian excerpt with an em dash and two accents read
`dj vu.` and a Japanese one read `...`. The glossary cut the `A0` out of `à`
and Cyrillic `Р`, and its Windows-1252 curly quotes and em dash had not
matched a real quote or dash since the content became UTF-8 -- only the
tails of `Д`, `Г` and the em dash itself. `HandleInput` and `TextCleanup`
now hold no byte above 7F, and their tests hold Russian, Japanese, Polish
and French.
