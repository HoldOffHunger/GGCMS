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
