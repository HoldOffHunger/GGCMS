# Triage

Known-open issues, in priority order, with the evidence behind each. Written at
the end of the 30 August 2026 session so the next one starts from knowledge
rather than rediscovery.

Every item here was observed, not inferred. Where a number appears, it was
measured on the production host.

## What was fixed on 30 August 2026

Recorded because several of these masked each other, and knowing the order they
came apart in explains the state of the system.

| | before | after |
|---|---|---|
| disk | 100% full | 88% |
| certificates | expired 13 months | 16 of 18 valid |
| load average | 98 | ~1.8 |
| apache workers | 101 (thrashing) | 45 |
| swap | none | 2 GB |
| request arrival rate | 64.1/sec | 11.6/sec |
| a 269-byte CSS file | 12.9 s | **0.058 s** |
| revoltlib.com homepage | 13–16 s, "0 texts" | 0.29 s warm, 12,736 texts |

The causal chain, which is worth understanding before touching anything:

1. UFW logged every blocked packet; `kern.log` reached 3.4 GB, the journal
   3.1 GB, Apache's logs 6.6 GB. **The disk filled** in about July 2024.
2. `certbot` could not create a temp directory, so **renewal failed silently
   every twelve hours for two years** and every certificate expired.
3. Separately, a redirect loop (below) generated ~95% of all traffic, which
   saturated Apache's accept queue, which made *every* request — including
   static files — take 13 seconds.
4. Separately again, an unfinished refactor from February 2024 meant
   revoltlib.com had reported "0 texts" for two and a half years.

None of these were visible from outside, because `index.php` sets
`error_reporting(0)`.

## Open — real, worth doing soon

### ~6% of responses are 500s

`168` of a 3,000-request sample. Previously invisible under a 95% redirect
rate; now readable. No diagnosis yet.

```bash
tail -3000 /var/log/apache2/access.log | awk '$9==500 {print $7}' | sort | uniq -c | sort -rn | head
```

### 2.3 million `mysqli_close()` fatals

```
PHP Fatal error: Uncaught TypeError: mysqli_close(): Argument #1 ($mysql)
must be of type mysqli, null given in DBAccess.php:158
  #1 Handler.php(100): DBAccess->DBEnd()
  #2 Handler->__destruct()
```

`Handler::__destruct()` calls `$this->db_access->DBEnd()` unconditionally. When
the connection failed or construction did not reach `Construct_DBAccess`, the
destructor throws — and **masks the original error**. Counted 2,300,533 in one
rotated log. Predates the 30 August session by years.

### earthfluent.com

Two faults, possibly one cause. Its certificate is still expired (the renewal
run was interrupted), and it serves a **334-byte** response with a `200` status
where a real page is 30–200 KB. It has a `child_types/` folder, so it may carry
a sibling of the `$abstractglobals` bug fixed on revoltlib.

## Open — known and contained

### `HTTP_HOST` interpolated into a `Location` header

`Handler::SecureRedirect()` builds `'https://' . $_SERVER['HTTP_HOST'] . …`.
`HTTP_HOST` is client-supplied. That is an open-redirect vector, independent of
the loop that was fixed. Deliberately not bundled into that fix.

### `AbstractGlobals` cannot load a domain override

Two bugs that mask each other in `buildAbstractGlobals_ChildTypes()`, and the
same pair in `buildAbstractGlobals_Formats_LinkTo()`:

1. It reads `$primary_domain_lowercased`, an **undefined local**. It is assigned
   in a *different* function. Compare line 29, which correctly uses
   `$this->handler->domain->primary_domain_lowercased`. Confirmed at runtime:
   the variable logs as `<<UNSET>>`, so the domain path resolves to
   `/child_types/enabled.php` and never matches.
2. The override branch calls `confreq($shared_default_formats_linkto_location)`
   — the **shared** file — instead of the domain file. So
   `AbstractGlobals_ChildTypes_enabled_override` is never defined.

Fixing (1) alone exposes (2) and fatals on a missing class. Fixing both changes
which child types are enabled on three sites (revoltlib, revoltlink,
revoltsource), so it is a behavioural change, not a bug fix.

This is the unfinished half of the config migration: moving settings out of the
single `defaultglobals` class in `/etc/ggcms/<domain>.php` into per-domain
folders, loaded per script.

### `sitemap.php` is disabled

`scripts/sitemap.php` opens `display()` with `print ''; return FALSE;`, leaving
~820 lines unreachable. Every sitemap request falls through to the 404 path.

### `DBFileCache` write and read disagree about shape

`ReadCache()` only reassembles elements that are arrays carrying an `id` key.
`GetRecordChildrenCount()` writes a bare integer. The read therefore always
returns `[]`. The correctness fix is in (the count now falls through to SQL),
but **that cache remains inert** — it writes entries nothing can ever read.

Evidence: `ggcms_ChildRecordCount/` holds 287 files, all written between 26 and
29 February 2024, and nothing since. The cache froze itself on day four,
because `WriteCache` sits below the guard that started returning early.

### Generated stylesheets (resolved 30 August 2026)

`style.php` renders through the CSS format class, so `/css/view/display.css`
is **PHP output, not a file** — it does not exist on disk. Every page view
therefore paid a full PHP boot for its stylesheet: 0.64–1.42 s, against 0.06 s
for the cached HTML itself. Once the redirect loop was fixed, this became the
dominant cost of a page load.

Now cached. The cached file keeps the request's own extension
(`display.css.css`) because a `.html` suffix would make Apache serve a
stylesheet as `text/html` and browsers would discard it.

A first pass wrongly concluded `style.php` was unused, by grepping the access
log for `style.` — which never matches, because the URL is `/css/view/…`. The
correct test is whether the file exists on disk:

```bash
ls /var/www/html/css/view/display.css   # No such file or directory
```

The longer-term plan remains compiled CSS sheets rather than generating them
per request.

### Doubled query separators (resolved 30 August 2026)

URLs like

    view.pdf?mobilefriendly=1?mobilefriendly=1?action=browse

were a steady source of 500s. A URL carries one `?`; every later one should
have been `&`.

`Handler::Construct_RepairQueryString()` now repairs the request rather than
redirecting it: the visitor gets the page they asked for and pays no round trip
for a malformed link. `QUERY_STRING` is everything after the *first* `?`, so
any `?` remaining inside it is by definition a mis-typed separator — the test
is exact, not heuristic. `$_GET` is re-parsed from the repaired string, and the
original parse is preserved on `$GLOBALS['_ORIGINALGET']` and
`$this->original_get`.

**Still open:** something is *generating* these links. The handler repairing
them on the way in is correct and is what the class is for, but the source is
in link construction, not routing. Find what appends `?mobilefriendly=1`
without checking whether a query string already exists.

## Open — housekeeping, and the reason all of this happened

### Nothing is scheduled

This is the root cause of the whole incident. `cli/` contains a disk-space
checker, a DNS record checker, a full domain health checker, database backup
and size tools, and error and 404 reporting. **None of them was on a cron.**
`check_free_space.php` has existed since 2022 and would have caught the disk
filling in July 2024.

The crontab is written out in [Operations.md](Operations.md). Install it.

### CLI PHP has no `mysqli`

Every database tool in `cli/` fails immediately:

```
PHP Fatal error: Call to undefined function mysqli_connect()
```

The CLI SAPI and the Apache SAPI load different `php.ini` files and different
extensions. Apache runs PHP **8.0**; the `php` on `$PATH` is a different version
without `mysqli`. Until this is fixed, no scheduled database diagnostic can run
— which makes the crontab above only half-useful.

Workaround used during the session:
`php8.1 -c /etc/php/8.0/apache2/php.ini -r '…'`

### Log rotation still broken

Rotation last ran 5 May 2024. `access.log.1` reached 5.7 GB and `error.log.1`
1.4 GB. This is what refilled the disk to 88% during the session. Caps for UFW
logging, journald and Apache are in [Operations.md](Operations.md) and are
**not yet applied**.

### abstractcon.com needs decommissioning

DNS is already gone (`certbot` reports `NXDOMAIN` for both A and AAAA), but the
vhost and the renewal entry remain, so it fails renewal forever. Runbook in
[Operations.md](Operations.md). `certbot delete --cert-name abstractcon.com`.

### A 4.3 MB header image

`/image/background/header/…jpg` is 4,290,413 bytes. With the page itself now
served in ~0.06 s, one background image is roughly 300× the weight of the HTML
and is the dominant cost of a page load. ImageMagick is already installed.

## Wanted, not yet built

* **Cache CLI tools** — view, clear and *stat* both caches per domain. The
  session lost roughly an hour to not being able to ask "how many row-cache
  entries does revoltlib have, and how old are they?" The answer was 63,234
  entries from February 2024, still being served.
* **nginx in front of Apache.** Prefork Apache dedicates a process per
  connection, capping concurrency at `MaxRequestWorkers`. nginx would serve the
  page cache directly off disk from an event loop and proxy only misses back.
  No longer urgent — arrival rate fell 82% once the redirect loop stopped — but
  it is the real ceiling.
* **Canonical-path normalisation.** Rather than redirecting malformed URLs,
  normalise once at the top of `Handler` and let routing, cache reads and cache
  writes all use the canonical form. `RequestPath()` is the seed of this. Note
  that caching *both* the malformed and canonical URLs would recreate the
  unbounded-unique-URL problem that caused the outage; cache the canonical form
  only, and emit `<link rel="canonical">`.

## Method note

Four wrong diagnoses were reached during this session by reasoning from the
code instead of measuring: an empty `EntryTranslation` table, blanks-poisoning
in the file cache, a null `child_types`, and `%{DOCUMENT_ROOT}` semantics. Each
was disproved in minutes by one measurement.

What worked, every time, was making the system state the fault itself —
`strace -c` on a live render, `LogLevel rewrite:trace3` for a single request,
`curl -x` to reproduce a proxy's absolute-form request, and temporary
`error_log()` probes behind an opt-in query parameter.

When a fix is deployed, **measure the specific number it should move.** Twice
during this session a fix was correct, believed complete, and changed nothing,
because a second instance of the same bug sat upstream of it.
