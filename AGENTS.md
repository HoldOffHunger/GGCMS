# Notes for AI assistants working on GGCMS

Read this first. It is short on purpose; the detail is in `Docs/`.

## What this is

Green Gluon CMS: a multi-tenant PHP content management system serving
seventeen live websites from one codebase, one front controller, and one
`Handler` class. The lineage runs back to 2006. There is no framework
underneath it — no Laravel, no Symfony, no autoloader, no namespaces. Do not
reach for framework idioms; they will not fit and they are not wanted.

The author is a solo developer who has written every line. The conventions are
consistent because one person chose them. **Match the surrounding code rather
than improving it.**

## The four things that will trip you up

**1. The URL is not a file path.** Apache sends every request that does not
name a real file to `index.php`. Path segments are *entry codes* chained by
assignment records in the database, and the last segment names the script. So
`/a/b/c/modify.php` and `/a/b/c/d/e/f/g/h/i/j/k/modify.php` run the same
script. The legal URL space is effectively infinite and most of it resolves to
nothing — never write a file keyed by request path without first checking the
request resolved to real content.

**2. Everything renders by `print`.** No stage of the render pipeline returns a
string. Output goes straight to stdout as it is produced. This constrains what
you can do in the render path, and it is why the page cache works the way it
does.

**3. `error_reporting(0)` is set in `index.php`.** Nothing is displayed. A
silently broken page looks exactly like a working one. If you are debugging,
turn it up first, and never conclude "it works" from the absence of an error.

**4. One `$args` hash per function.** Not positional parameters. See
`Docs/CodeConventions.md` before writing anything.

## Where things are

| | |
|---|---|
| `usr/lib/ggcms/src/classes/` | The engine |
| `usr/lib/ggcms/src/scripts/` | Page scripts (`view`, `modify`, `search`, `sitemap`, …) |
| `usr/lib/ggcms/src/templates/default/` | The default template set; each site's own lives in the private repository |
| `usr/lib/ggcms/cli/` | A whole second application: diagnostics and maintenance |
| `etc/ggcms/clonefrom/` | Shared configuration defaults; per-domain configuration lives in the private repository |
| `var/www/html/` | Document root: `.htaccess`, `index.php`, shared assets |
| `tests/` | PHPUnit tests; never deployed |

The tree mirrors a deployed host's filesystem. Deployment is a copy to `/`.

**This repository is public and names no site.** Every site's templates and
configuration live in the private configuration repository, checked out on
the host at `/opt/ggcms-config`; `bin/deploy.sh` syncs both. A site template edited here is
gitignored and never deployed -- on 26 September nine fixes were found
stranded that way for three weeks.

## Documentation

| Read this | When |
|---|---|
| [Docs/Architecture.md](Docs/Architecture.md) | Before changing request handling, routing or rendering |
| [Docs/CodeConventions.md](Docs/CodeConventions.md) | Before writing any code at all |
| [Docs/CommandLineTools.md](Docs/CommandLineTools.md) | Before writing a diagnostic — one may already exist |
| [Docs/Operations.md](Docs/Operations.md) | Anything touching the live host |
| [Docs/PageCache.md](Docs/PageCache.md) | Anything touching caching or invalidation |
| [Docs/Installation.md](Docs/Installation.md) | Standing up a host |
| [Development/Principles.md](Development/Principles.md) | Before deciding *where* to fix something |
| [Development/Conventions.md](Development/Conventions.md) | Project vocabulary — ISE, ISI, and what already exists |
| [Docs/Triage.md](Docs/Triage.md) | Known-open issues, with the evidence |
| [Docs/Testing.md](Docs/Testing.md) | Before changing a tested function, and after changing any |
| [Docs/Styling.md](Docs/Styling.md) | Before touching a stylesheet, `style.php`, or any markup's classes |

## Checking on production

When asked how production is doing, or before starting work that touches it,
read the scheduled checks first -- they have already done the slow part.
Everything below is on the host and read-only.

| Read | Healthy looks like |
|---|---|
| `tail -20 /var/log/ggcms/cron/disk.log` | Nothing since the last timestamp, or every volume `OK`.  `WARN` is under 15% free; `FAIL` under 8% or 1 GB |
| `tail -5 /var/log/ggcms/cron/backup.log` | `SUMMARY  19 site(s):  19 OK` from 03:15 UTC |
| `tail -5 /var/log/ggcms/cron/confirm.log` | `All 17 sites pass.` from 06:30 UTC |
| `tail -30 /var/log/ggcms/cron/errors.log` | Open 500 counts per site; compare with the day before |
| `/var/log/ggcms/cron/schema.log` (Mondays) | No `missing` rows; `unknown` rows are explained history |
| `server_error_detail.php <domain>` | The newest open 500s with their messages; `--id=N` for one whole |
| `human_stats.php` | People per site, this week against last |
| `traffic_stats.php --report=summary` | Page-cache hit rate and renders per second for the last hour |

A ticket last seen before the latest deploy is probably fixed; mark it
resolved rather than chasing it.  Say what you found in the conversation, in
the first sentence, whether or not it is bad news.

## Rules

**Never commit a secret.** Database credentials live on the live host as
`mysqli.default_*` directives in `/etc/php/8.5/{apache2,cli}/conf.d/99-ggcms-database.ini`,
beside the stock `php.ini`.  Neither is ever committed. Site content under `/srv/ggcms/` and everything in `/var/log/` are
likewise out. Check `.gitignore` before adding a path.

**Do not bulk-edit `BT:` comments.** There are 101 of them. They are the
author's markers and several record decisions rather than defects.

**Assume the capability already exists.** Twenty years of one developer means
that for almost any "how do I do X in this system?", several methods are already
written. One session lost an hour grepping a 7 GB log for what
`server_error_counts.php` returns in a single command, correctly attributed per
domain. Check `cli/`, `src/traits/` and `StandardLibraries.php` first. See
[Development/Conventions.md](Development/Conventions.md).

**Read errors from the database, not the log.** Apache's log is `combined` and
does not record which of the seventeen sites served a request; the
`InternalServerError` (ISE) and `InternalServerIssue` (ISI) tables do. ISE means
something broke; ISI means something was merely odd. The distinction is project
vocabulary — use it.

**Check `cli/` before building a tool.** There is already a disk-space checker,
a DNS record checker, a full domain health checker, database backup and size
tools, and error and 404 reporting. The historical failure mode on this project
is not missing tools — it is tools that exist and are not scheduled.

**Diagnostics must filter.** Output gets read by humans and by models. Default
to the narrowest useful scope and take plenty of filter arguments; an
unfiltered sweep is tens of thousands of rows and helps nobody.

**Report findings in conversation, not in a document.** Writing a problem into
a markdown file is not telling anyone about it. Say it out loud, in the first
sentence, in words a person reads.

**Nothing on the production host talks to git.** Pushing this repository
publishes code; it does not deploy anything.
