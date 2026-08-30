# Page Cache

A cached page is an ordinary file on disk. When one exists, Apache serves it
and **PHP never starts**.

That is the whole design, and it is worth being precise about why it is not
merely "a fast path through GGCMS". There is no path through GGCMS at all. No
`Handler`, no twenty-four `Construct_*` calls, no database connection, none of
the queries. Apache reads a file and returns it, the way it would return a
stylesheet.

## Why this, and why now

Measured on the production host, one page render:

```
sendto    1711 calls     MySQL queries out
recvfrom  1237 calls     results back
openat    4437 calls     DB file cache reads
CPU time  0.06 seconds
```

About 1,700 database round trips for a single page. The droplet is in `nyc1`;
the managed database is in `nyc3`. Each round trip costs roughly 5 ms, so the
page spends around nine seconds doing nothing but waiting. A worker sampled
mid-request was in state `S` with `wchan` `poll_schedule_timeout` for its
entire life, and burned 0.06 seconds of CPU.

The engine is not slow. It waits. The cache removes the waiting rather than
shortening it.

The existing `DBFileCache` (see [CommandLineTools.md](CommandLineTools.md)) is
a **row-level** cache and is already absorbing a great deal — those 4,437
`openat` calls are it working. The two are complementary: the page cache stops
requests reaching PHP, and the row cache speeds up the ones that do.

## No URLs change

The obvious implementation is to write `/a/b/c/page.html` and repoint every
link at it. Do not do that. It would mean editing generated links across
seventeen sites, and every inbound link and search result pointing at the old
URLs would die.

There is also a trap: you cannot write the cache file at the request path when
the request path ends in `.php`, because `mod_php` would see the extension and
**execute** the cached HTML as a PHP script.

So the cache is a parallel tree, consulted by two rewrite rules placed ahead of
the front controller:

```apache
RewriteCond %{REQUEST_METHOD} =GET
RewriteCond %{QUERY_STRING} ^$
RewriteCond %{HTTP_COOKIE} !(loggedin|AuthenticationToken) [NC]
RewriteCond %{DOCUMENT_ROOT}/_cache/%{HTTP_HOST}%{REQUEST_URI}.html -f
RewriteRule ^(.*)$ /_cache/%{HTTP_HOST}%{REQUEST_URI}.html [L]
```

Hit, and Apache serves the file. Miss, and it falls through to the existing
`!-f` rule, `index.php` renders as it always has, and the finished page is
written on the way out. Every URL the site has ever published keeps working.

## Where it lives

```
/mnt/nyc01/ggcms_cache/pages/<host>/<path>.html
/mnt/nyc01/ggcms_cache/pages/<host>/<path>/index.html    (paths ending in /)
```

On the mounted volume, not the root filesystem. Root has around 3 GB spare;
the volume has 44 GB. Roughly 60,000 unique URLs at ~26 KB each is about
1.6 GB, which root cannot take and the volume will not notice.

`/var/www/html/_cache` is a **symlink** to that directory. A symlink rather
than an Apache `Alias` because `Alias` is not permitted in `.htaccess` and
would otherwise mean editing seventeen vhosts.

`PageCache::CacheLocation()` and the two rewrite rules compute the same two
filename forms independently. **Change one and you must change the other**, or
pages will be written where nothing looks for them.

## What gets cached

Conservative by construction. Every condition must pass:

| Condition | Why |
|---|---|
| `GET` | Nothing with side effects |
| Empty query string | A parameter makes the response specific |
| No `loggedin` / `AuthenticationToken` cookie | Anonymous visitors only |
| Not a 404 | The URL space is infinite; caching misses fills the disk |
| Format is `HTML` | Start narrow |
| Script is `view.php` | 99.99% of traffic; `modify.php` must never be cached |
| `isSecure()` is false | Those pages set no-store and re-authenticate |
| HTTP 200 | |
| No `Set-Cookie` or `Location` header | A cookie makes the response personal |
| Output ≥ 2048 bytes and contains `</html>` | See below |

A page wrongly served from cache is a correctness bug that can show one
visitor's view to another. A page wrongly *not* cached costs only what the site
costs today. When in doubt, do not cache.

**The minimum-length check earns its place.** earthfluent.com currently returns
a 334-byte stub with a `200` status. `error_reporting(0)` means it fails
silently and looks identical to a working page from outside. Without that
check, the cache would have frozen a transient failure into a permanent one.

## Writing

`index.php` wraps the request in `ob_start()`, because the render pipeline
prints as it goes and returns nothing — the buffer is the only place a complete
page exists.

The page is flushed to the visitor **before** the cache is written. Nobody
waits on a disk write they gain nothing from.

Writes go to a temporary filename and are then `rename()`d into place.
`rename()` is atomic within a filesystem, so a reader never sees a half-written
page — which matters with twenty workers writing concurrently.

Every failure path returns `FALSE`. A full volume, a lost `mkdir` race or a
read-only mount must make the site slow, never broken.

## Path safety

The cache key is the request URI, and the request URI is entirely
attacker-controlled. `PageCache::SafePath()` rejects:

* anything not matching `[A-Za-z0-9/_.,~-]`
* **any percent sign** — refused rather than decoded, because decoding after
  validation is how `%2e%2e%2f` becomes `../`
* `..` and `//` anywhere
* null bytes
* paths over 512 characters
* paths more than 24 segments deep

That last one is not paranoia. The URL path is a walk through the entry graph,
so its depth is unbounded *by design*, and a crawler wandering the graph would
otherwise create directories forever.

`RemoveDirectory()` deletes recursively, so it `realpath`s its target and
refuses to act on anything that is not underneath the cache root. A bug in a
caller must not be able to turn recursive deletion loose on the filesystem.

## Invalidation

Any database write makes every cached page for that domain suspect.

The four methods in `DBAccess` that are the only places data changes —
`CreateRecord`, `UpdateRecord`, `DeleteRecords`, `DeleteOtherRecords` — each
call `MarkPageCacheDirty()`. The mark is cheap and idempotent; the actual flush
runs **once, at shutdown**, via `register_shutdown_function`.

Two reasons it works that way. One save in `modify.php` performs dozens of
writes, and flushing a domain tree dozens of times would be absurd. And
flushing *before* the writes land would leave a window in which a concurrent
read could re-cache the pre-write page and make the stale copy permanent.

The whole domain is flushed rather than individual pages. Per-page dependency
tracking would be more efficient and can be subtly wrong for years without
anyone noticing. The content database changes rarely; a full flush costs one
cold cache and cannot be wrong.

## Operating it

```bash
du -sh /mnt/nyc01/ggcms_cache/pages/*              # size per domain
find /mnt/nyc01/ggcms_cache/pages -type f | wc -l  # pages cached
rm -rf /mnt/nyc01/ggcms_cache/pages/revoltlib.com  # flush one domain
rm -rf /mnt/nyc01/ggcms_cache/pages/*              # flush everything
```

`bin/deploy.sh` flushes automatically, because changed code makes every cached
page suspect.

To confirm a page is being served from cache rather than rendered, time it
twice. A miss takes seconds; a hit takes milliseconds.

```bash
curl -s -o /dev/null -w '%{time_total}s\n' https://revoltlib.com/
```

## Known gaps

* **`RecordUserStatistics` never runs on a cache hit.** Traffic served from
  cache is invisible to GGCMS's own statistics, and Apache's access log becomes
  the only record. This is a real trade and it is not yet resolved.
* **Only `view.php` in HTML.** RSS, ATOM and the other formats are equally
  cacheable in principle and are not cached today.
* **No cache warming.** The first request for each URL still pays full price.
  With roughly 60,000 unique URLs and a cache that only clears on a database
  write, warming would mostly matter after a deploy.
