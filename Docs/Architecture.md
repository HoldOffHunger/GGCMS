# Architecture

How a request becomes a page.

## The premise

Almost every PHP application in the world is a set of files that the web server
hands out. Ask for `/about.php` and Apache finds `about.php` and runs it. The
URL is a path on disk.

GGCMS refuses that. Apache is configured so that **no request may reach a PHP
file directly**. `var/www/html/.htaccess` is the whole of it:

```apache
RewriteEngine On
RewriteBase /

RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [QSA,NC,L]
```

If the request does not name a real file on disk, it becomes `index.php`. There
is exactly one entry point, and it is nineteen lines long:

```php
require('/var/www/ggcms_install_directories.php');
require(GGCMS_DIR . 'classes/System/GlobalFunctions.php');
require(GGCMS_DIR . 'classes/StandardLibraries.php');
$handler = new Handler();
$handler->HandleRequest();
```

The consequence is that the URL is no longer a path. It is an argument. The
handler is free to give it whatever meaning the CMS wants, and it does.

## One engine, seventeen sites

There is one copy of the engine. Seventeen domains run on it, and the only
things that differ between them are **the database and the template files**.
Nothing else. Every vhost on the host points at the same `DocumentRoot`, every
request enters the same `index.php`, and every class, script, format and trait
is shared by all of them.

This is a deliberate design decision rather than an accident of hosting, and
the reasoning is the author's:

> The only thing that differs between websites is databases and template
> files. Absolutely nothing else. This has a positive, knock-on effect. Does
> website x need a ToS? Okay, cool, now everyone gets a ToS. It's supposed to
> be a system where everything reinforces everything else. If each website is
> a business or a non-profit site like revoltlib.com, then doing business
> helps non-profit and doing non-profit helps business -- the code itself
> makes the system synergistic automatically and irrevocably.

The practical consequence for anyone writing code here: **a feature is never
built for one site.** Terms of service, OPDS output, the language system, the
page cache, the PDF renderer -- each was wanted by one domain and arrived at
all seventeen the moment it existed. Work spent on the anarchist archive is
work spent on the vocabulary tools, and the reverse. Short of a template, the
engine has no way to say "only for this site", and that limitation is the
point rather than a gap.

The cost is the same sentence read backwards, and it belongs here because it
is felt on the bad days: **the blast radius is also seventeen.** A fatal in
shared code is not one site down. On 31 August 2026 one Debian package's
Apache alias silently captured `/javascript/` for every domain at once, and a
single PHP 8 removal inside a vendored PDF library ended PDF output everywhere
simultaneously. Neither could have been a one-site problem, because there is
no such thing here.

That trade is accepted knowingly. Seventeen sites share every improvement, so
they share every regression, and the defence is not isolation but the ordinary
disciplines: measure before and after, keep the error log honest, and treat
anything written in shared code as running on all of it.

## The URL is a walk through the entry graph

`Handler::Construct_ObjectsAndScripts()` does the whole of routing in
seventeen lines:

```php
$this->object_list = explode('/', ltrim($_SERVER['REDIRECT_URL'], '/'));
$this->desired_script = array_pop($this->object_list);
// ...
$this->object_code   = $this->object_list[count - 1];
$this->object_parent = $this->object_list[count - 2];
```

The last segment names the **script**. Everything before it is a list of
**entry codes**, chained together by assignment records in the database.

So `/a/b/c/modify.php` and `/a/b/c/d/e/f/g/h/i/j/k/modify.php` run the same
`modify` script. They differ only in the context they carry: the nearest entry
(`object_code`) and its parent (`object_parent`), with the full chain preserved
in `object_list`. Depth is free. The path is a route through content, not
through the filesystem.

If no script is named, the default is `view.php`
(`Construct_ScriptName_SetScriptNameDefault`). `index.html` is treated as no
script at all.

**Consequence for anyone adding features:** the legal URL space is effectively
infinite, and most of it resolves to nothing. Any code that writes something to
disk keyed by request path, such as a cache, a log, or a generated file, must
write only for requests that resolved to real content. Otherwise a crawler
probing nonsense paths will fill the disk. `Handler` records this as
`$this->error_404`.

## Format is a dimension, not a template

The file extension on the script selects a **format class**, via
`Construct_ScriptFormat_DetermineScriptFormat()`. A long switch maps every
extension anyone has ever guessed at (`php`, `asp`, `aspx`, `cgi`, `jspx`,
`rb`, `py`, `do`, `axd`, the empty string) onto `HTML`, and the real format
extensions onto their own classes:

`HTML` - `RSS` - `ATOM` - `RDF` - `JSON` - `XML` - `SGML` - `CSV` - `TXT` -
`PDF` - `EPub` - `RTF` - `TEX` - `OPDS` - `DAISY` - `BRF` (braille) - `CSS` -
`Image` - `Language`

Every format extends `AbstractBaseFormat`. A script therefore writes its
content once and is servable in any of them: `/some/entry/view.php` and
`/some/entry/view.rss` and `/some/entry/view.epub` run the same script and
differ only in the format object wrapped around it.

## The request lifecycle

### 1. Construction

`Handler::__construct()` is a flat sequence of twenty-four `Construct_*` calls,
in dependency order. It reads as a list because it is one:

```
LocalHostHandling          ValidateSecurity           SetErrorLogging
SetDevelopmentVersion      Cleanser                   Domain
Time                       Cookie                     Query
Language                   Action                     ObjectsAndScripts
ScriptName                 ScriptFileAndExtension     ScriptClassname
ScriptFormat               Globals                    ProductionSite
DBAccess                   Dictionaries               PresetAuthentication
CheckPermalinkRedirect     ScriptLocation             SocialMedia
```

By the end of it the handler knows the domain, the language, the entry chain,
the script, the format, and who is asking.

### 2. Redirects

`HandleRequest()` runs a gauntlet of redirect handlers before serving
anything: HTTPS upgrade, referral validation, `mailto:` paths, git-probe paths,
`~user` paths, doubled slashes, junk query parameters, copy-paste mangling,
known bad links. Each returns TRUE to abort the request.

### 3. Serving

`HandleRequest_ServeContent()` tries, in order:

1. **Image 404 handling.** Anything under `/image/` is handled by the `Image`
   format, which knows how to fail gracefully.
2. **Local files under `/srv`.** Per-domain content, served directly.
3. **`HandleRequest_Content()`.** The real path, below.
4. **404**, with a cascade of last-chance redirects (reserved codes, matching
   codes, script names, misplaced scripts) before `Error404` is finally
   displayed and logged.

`HandleRequest_Content()` resolves the script in two places, and the fallback
is the multi-tenancy mechanism:

```php
$client_location = GGCMS_DIR . $this->domain->primary_domain_lowercased . $_SERVER['SCRIPT_URL'];
$shared_location = GGCMS_DIR . 'clonefrom.com' . $_SERVER['SCRIPT_URL'];
```

A site provides what it wants to differ; everything else falls through to
`clonefrom`. There is a second, finer-grained override at the method level.
`HTML::Display()` looks for `<action>_<host>` before `<action>`, so a single
site can replace one behaviour of a shared script without forking it.

### 4. Rendering

```php
$display_results = $this->script->$desired_action();   // gather data
$this->html_data = $this->script->GetHTMLFormatData(); // page metadata
$this->StartHTML();                                    // doctype, head, nav
$this->script->DisplayTemplates();                     // body
$this->FinishHTML();                                   // footer, close
```

**Everything is emitted with `print`.** No stage returns a string; output goes
straight to stdout as it is generated. This is worth knowing before changing
anything in the render path, and it is also why a whole-page cache is cheap to
add. See [PageCache.md](PageCache.md).

### 5. Teardown

`HandleRequest_EndRequest()` runs admin tools if an admin asked for them, dumps
MySQL query debugging if the connection was upgraded, and records user
statistics. `Handler::__destruct()` closes the database.

## Assignments, and the reserved id 0

`ORM.php` is the jewel of the engine. It is what turns `/a/b/c/d` into entries
with codes `a`, `b`, `c` and `d`, joined by **assignment records**, and gathers
their child records alongside them.

An assignment is deliberately minimal — `Parentid`, `Childid`, and dates. That
minimalism is the point: a relationship between two entries is a *record*, not
a column on either of them, so relationships can be added, removed and
reordered without touching the entries themselves. They can also point at
things that do not exist as ordinary content, which is where the reserved id
comes in.

### `0` is permanently reserved and means "the host"

**There will never be an entry with `id = 0`.** This is a deliberate, permanent
reservation, not a placeholder awaiting a better idea. It has never caused a
problem and it is not to be removed.

It carries meaning in *both* directions, and they are different:

| Assignment | Meaning |
|---|---|
| `Parentid = <entry>`, `Childid = 0` | The entry is attached to the host root — `host.com/<entry>` |
| `Parentid = 0`, `Childid = <entry>` | The entry is a **subdomain** — `<entry>.host.com` |

The first is how a top-level entry declares itself reachable directly under the
domain, with no parent above it. Nothing in the entry table says "this one is
top-level"; the assignment record does.

The second is **not implemented yet**, and is one of the more elegant unbuilt
things in the system: subdomains fall out of the existing model for free,
requiring no new tables, no new routing, and no new concepts — only the
inverted sentinel. See [Triage.md](Triage.md).

### Why assignments beat a parent column

Because a relationship is its own record, an assignment can be prepended or
appended to structures that live outside this database entirely — another
system's content, an external service, a different site. The entry does not
have to know it has been placed somewhere, and the same entry can appear in
several places without duplication.

That is the property to protect when changing anything here: **an assignment is
a statement about a relationship, not a property of either side.**

### Transferring a branch is one relationship update

Modern filesystems already have a similar property: renaming a directory on the
same filesystem is ordinarily a metadata operation that relinks it beneath a
new parent without moving every descendant. GGCMS applies that idea one layer
higher. Placement is a database relationship in the content model itself,
independent of any directory layout used by the host.

In GGCMS the visible path is assembled from assignment records. To make `b`
and everything beneath it appear under `x` instead of `a`, change only the
assignment that places `b`:

```text
before: a -> b -> c -> d
after:  x -> b -> c -> d
```

The entry records do not move. The assignment from `a` to `b` becomes an
assignment from `x` to `b`; the assignments from `b` to `c`, from `c` to `d`,
and every relationship below them remain untouched.

`scripts/transfer.php` exposes this as an authenticated administrative action.
It checks that the target parent does not already have a child with the same
entry code, then updates the selected `Assignment.Parentid` through DBAccess.
No descendant count changes the amount of work required for the transfer.

The assignment update itself remains independent of the number of descendants.
Current cache invalidation is separate work: every database write marks the
domain dirty, and shutdown removes that domain's whole page-cache tree. A large
cache can therefore dominate the observed transfer time even though the
relationship change remains one record update. See [PageCache.md](PageCache.md).

`EntryCodeReservation` makes relocation forgiving without permanently owning
an old path. The normal content resolver always runs first, so a new live entry
at that path outplaces the reservation. Only when ordinary resolution fails
does `Handler::handleReservedCodeRedirect()` try the remembered full, shortened
and extension-stripped code forms and redirect through the reservation's
current assignment. History rescues an otherwise broken or imperfect link, but
never prevents the namespace from being used again.

This is one of the central consequences of the assignment model: content
identity is independent of placement, and placement is a controllable,
transferable relationship rather than a directory full of files.

### Child records

Alongside the entry chain, the ORM gathers each entry's child records —
comments, images, links, quotes, tags, text bodies, event dates and the rest.
Which types are gathered is per-domain configuration, because only some sites
have comments and only some have images. See
`etc/ggcms/<domain>/child_types/enabled.php` and the layer notes below.

## Layers, and where code belongs

Four folders, four jobs, in strict order. The rule is one-directional:
templates are served by scripts, scripts are served by classes. Nothing reaches
back up.

### `classes/` — the brains

The deep business logic. **All SQL lives here**, in `classes/Database/`, and
nowhere else. If a query needs writing, it belongs in this layer.

### `scripts/` — the top-level operations

The things an entry might want to *do*: `view`, `modify`, `search`, `news`,
`contact`, `tos`, `feed`, `sitemap`. Some make no sense in an entry context at
all — `dbstatus`, `systemstatus`, `install` — and that is fine; they are still
top-level operations.

**Zero MySQL in a script.** A script is "move x to y". It asks the classes for
data and hands the result onward. A query appearing in `scripts/` is a layering
violation, not a shortcut.

Because the URL is data rather than a path, *any* script is reachable from
*any* entry context. Most sites have a single `/tos.php`; here
`/a/b/c/tos.php` works, and arrives with `a`, `b` and `c` as context. That is a
feature of the routing, not an accident of it.

### `templates/` — presentation only

Handed a bucket of data by the script, and responsible for what displays and
what HTML surrounds it. **This layer is deliberately the most junior-friendly
place in the codebase.** Someone should be able to change how a page looks
without knowing anything about the ORM, the format classes, or the database.

If a template needs to *ask* for something, the data should have been handed to
it. That is a signal the script above it is incomplete.

### `modules/` — small reusable logic sections

A module is a piece of logic that could have been written inline in a template,
extracted so it can be reused. The goal is that a template reads as a short list
of steps:

```
navigation, intro, main section, contacts and sales, outro
```

**House rule for modules: one argument, usually the handler.** "Here is the
handler; it already holds every piece of data you could possibly need." No long
positional argument lists, no bespoke parameter sets per module.

`src/modules/` is being retired *into* `templates/`. Older modules predate the
one-argument rule, carry large argument lists, and are cruft. Follow the newer
convention when writing one; prefer moving an old one into its template over
maintaining it.

### `traits/` — shared behaviour between scripts

`src/traits/scripts/` holds logic common to more than one script, so that (for
example) "load comments" is written once rather than in both `view` and `user`.
Composition rather than a base class, which keeps scripts flat and independent.

`SimpleORM`, `SimpleForms`, `SimpleErrors`, `SimpleLookupLists`, `DBFunctions`
and `SimpleORMSiteMap` are the main ones, and a script declares what it needs at
the top of the class.

### Summary

| Layer | Job | May contain SQL |
|---|---|---|
| `classes/` | Deep business logic, the ORM, the engine | **Yes** — and only here |
| `scripts/` | Top-level operations; "move x to y" | **No** |
| `modules/` | Reusable logic sections; take the handler and nothing else | No |
| `templates/` | Presentation of data already handed to them | No |

## Data access

Everything reaches MySQL through `classes/Database/DBAccess.php`. Reads go
through `GetRecords()`, which assembles SELECT / JOIN / WHERE / LIMIT from an
args hash and a table description. Writes go through exactly four methods:

* `CreateRecord()`
* `UpdateRecord()`
* `DeleteRecords()`
* `DeleteOtherRecords()`

Those four are the only places in the system where data changes. The thirty-odd
`SaveRecordFromQuery_*` methods in `modify.php` all bottom out there. Any hook
that needs to know "did anything change" belongs at those four points and
nowhere else.

`DBAccessUpgraded` is swapped in for administrators and adds query logging.
`DBFileCache` is an existing row-level file cache, gated on
`$globals->useDBFileCache()`, storing results under
`/mnt/nyc01/ggcms_cache/mysql_db_file_cache/<reverse.domain>/`.

## Configuration and presentation

| What | Where |
|---|---|
| Per-domain config, language scripts | `/etc/ggcms/<domain>/` and `/etc/ggcms/com.<site>/` |
| Shared defaults inherited by all sites | `/etc/ggcms/clonefrom/` |
| Per-site templates | `usr/lib/ggcms/src/templates/<site>/` |
| Shared scripts | `usr/lib/ggcms/src/scripts/` |
| Format-specific script bases | `usr/lib/ggcms/src/scripts/Format/<FORMAT>/` |

Note the two naming schemes in `/etc/ggcms/`: reverse-DNS (`com.revoltlib`) and
plain (`holdoffhunger.com`). The `ReverseDNSNotation` trait converts between
them.

## Things to know before changing anything

* **`index.php` sets `error_reporting(0)`.** Nothing is displayed and little is
  logged. A silently broken page looks identical to a working one. Turn it up
  before debugging, and remember it is off when reading a bug report.
* **`__destruct` calls `$this->db_access->DBEnd()` unconditionally.** If
  construction fails before `Construct_DBAccess`, destruction fails too, and
  the real error is masked by the second one.
* **Args hashes are the calling convention.** See
  [CodeConventions.md](CodeConventions.md).

## Rendering audit ledger

See [RenderingBugHunt.md](RenderingBugHunt.md) for traced template/module call
paths, verified local repairs, legacy contracts and unresolved questions.
