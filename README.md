# Green Gluon CMS

A multi-tenant content management system in PHP, serving seventeen live
websites from a single codebase and a single front controller.

GGCMS began in 2006 as the Bakunin Cannabis Engine and was rebranded in 2013.
The code in this repository is the second revamp of that lineage.

## What makes it unusual

**One entry point, always.** Most PHP applications put their scripts on the
server and let the web server hand them out. GGCMS does the opposite: Apache is
told that no request may ever reach a PHP file directly. Every request that
does not name a real file on disk is rewritten to a single `index.php`, which
constructs a `Handler`, and the `Handler` decides what the request meant.

```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [QSA,NC,L]
```

**The URL path is data, not a file path.** Because the handler does the
routing, a path is free to mean whatever the CMS wants it to mean. In GGCMS it
is a walk through the entry graph: `/a/b/c/modify.php` and
`/a/b/c/d/e/f/g/h/i/j/k/modify.php` run exactly the same script, with `a`, `b`,
`c` and the rest read as entry codes chained together by assignment records.
The trailing script name selects behaviour; everything before it selects
context.

**Format is a dimension, not a template.** The same content is rendered by a
family of format classes — `HTML`, `RSS`, `ATOM`, `JSON`, `XML`, `CSV`, `TXT`,
`PDF`, `EPub`, `RTF`, `SGML`, `TEX`, `OPDS`, `DAISY`, `BRF` (braille) — chosen
from the request's extension. A script writes its content once and can be
served as any of them.

**Sites share an engine and differ by configuration.** Per-domain behaviour
lives in `/etc/ggcms/<domain>/` and per-domain presentation in
`usr/lib/ggcms/src/templates/<site>/`. A site can also override a single method
of a shared script by name — `Handler` looks for `<action>_<host>` before
falling back to `<action>`. Anything a site does not define, it inherits from
`clonefrom`.

## Repository layout

The tree mirrors the filesystem of a deployed host, so deployment is a copy
to `/`.

| Path | Installed to | Contents |
|---|---|---|
| `usr/lib/ggcms/src/` | `/usr/lib/ggcms/src/` | The engine: classes, scripts, templates, modules |
| `usr/lib/ggcms/cli/` | `/usr/lib/ggcms/cli/` | Command-line diagnostics and maintenance tools |
| `usr/lib/ggcms/dep/` | `/usr/lib/ggcms/dep/` | Vendored dependencies (see note below) |
| `usr/lib/ggcms/tests/` | — | PHPUnit unit and integration tests |
| `etc/ggcms/` | `/etc/ggcms/` | Per-domain configuration and language scripts |
| `var/www/html/` | `/var/www/html/` | Document root: `.htaccess`, `index.php`, shared css/image/javascript |
| `var/www/*.php` | `/var/www/` | Install-path definitions read by every entry point |

Not in this repository, by design: `/srv/ggcms/<domain>/` (uploaded site
content, gigabytes of it), `/var/log/ggcms/`, and `php.ini` — which on a live
host carries the database credentials in its `mysqli.default_*` directives.

Dependencies are vendored rather than fetched at deploy time. Roughly half of
`dep/` is Composer-managed and half is hand-maintained (`fpdf`, `tfpdf`,
`braille-handler`, `rtf-generator`, `arr2textTable`, `php-html-to-pdf`), and
the target host is a 1 GB droplet on which running Composer is a poor use of
what little memory there is.

## Documentation

* [Installation](Docs/Installation.md) — standing up a host from bare Linux
* [Architecture](Docs/Architecture.md) — the request lifecycle in detail
* [Operations](Docs/Operations.md) — routine maintenance, logging, monitoring
* [Page Cache](Docs/PageCache.md) — the static cache design and its invalidation

## Sites

anarchistcode.com · copyleftlicense.com · earthfluent.com · holdoffhunger.com ·
listkeywords.com · masereelgroup.com · ouruprising.com · pronouncethat.com ·
removeblanklines.com · removeduplicatelines.com · removespacing.com ·
revoltlib.com · revoltlink.com · revoltsource.com · sortwords.com ·
wordweight.com · yallhearingthis.com

## Licence

See [LICENSE](LICENSE).
