# Code Conventions

GGCMS has been written by one person since 2006. It is unusually consistent as
a result, and the consistency is load-bearing: match it, and new code is
invisible; ignore it, and new code advertises itself.

This document is descriptive, not aspirational. Everything here is what the
code already does.

## Args hashes are the calling convention

Functions take **one** parameter, `$args`, an associative array. They unpack it
by name on the first lines of the body.

```php
public function GetRecords($args) {
    $record_select     = $args['select'];
    $record_type       = $args['type'];
    $record_definition = $args['definition'];
    $record_limit      = $args['limit'];
    $order_by          = $args['orderby'];
    // ...
}
```

Callers pass a literal array, one key per line, trailing comma:

```php
$record_where_args = [
    'recorddescription'=>$record_description,
    'recordwhere'=>$record_definition,
    'delimiter'=>' AND ',
];
$record_where_results = $this->GetRecordWhere($record_where_args);
```

No spaces around `=>`. Keys are lowercase and unspaced —
`'scriptformatlower'`, `'dbaccessobject'`, `'objectparent'` — **not**
`'script_format_lower'`. Local variables, by contrast, are `snake_case`. The
two namespaces are deliberately different so an args key never reads as a
variable.

Why this rather than positional parameters: every call site is
self-documenting, arguments can be added anywhere without touching signatures,
and an args hash can be built up, passed down and extended through a call
chain. `Handler::getArgs()` and
`HandleRequest_Content_Format_InstantiateFormatObject_Args()` are that pattern
at full extension — a thirty-key hash assembled once and handed to a
constructor.

Returns follow the same idea. A function with more than one thing to say
returns a hash:

```php
return [
    'pem_key'=>$cert_pem_key,
    'begin_cert'=>$begin_cert,
    'end_cert'=>$end_cert,
];
```

## Naming

**Methods in the web engine are `PascalCase`.** `HandleRequest()`,
`GetRecordDescription()`, `ValidateSecurity()`.

**Methods in the CLI application are `camelCase`.** `checkDomain()`,
`validateSSLCertPem()`, `extractCertPemContents()`. The two halves of the
system differ here; follow whichever half you are in rather than trying to
unify them.

**Underscores namespace a method into its parent.** A long operation is split
into steps whose names spell out the tree:

```
StartHTML
StartHTML_Head
StartHTML_Head_Start
StartHTML_Head_Title
HandleRequest_Content_Format_InstantiateFormatObject_PartialArgs
```

This is how the codebase does call hierarchy. The name is the outline.

**Constructor steps are `Construct_*`.** `Handler::__construct()` is
twenty-four of them in dependency order, and reads as a table of contents.

**`TRUE`, `FALSE` and `NULL` are uppercase.** Predicates and side-effecting
methods `return TRUE;` at the end rather than falling off.

## Layout

* **Tabs, not spaces.** One tab per level, and the class body itself is
  indented one tab inside `<?php`.
* **`?>` closing tags are present** at the end of every file.
* Section dividers are a comment and a rule, indented to the code:

```php
        // Construct ~ Requires
        // -----------------------------------------------
```

## Domains are written in reverse-DNS order

Most significant part first, always, wherever a domain becomes a name:

```
holdoffhunger.com            ->  com.holdoffhunger
revoltlib.com                ->  com.revoltlib
news.example.co.uk           ->  uk.co.example.news
```

`ReverseDomainName()` in the `ReverseDNSNotation` trait does the conversion,
and it is the only thing that should. Config directories under `etc/ggcms/`
are named this way, and so is anything else keyed by domain.

It sorts usefully -- every domain on a shared parent lands together, and a site
and its subdomains sit in one run rather than scattered across the alphabet by
their leftmost label. It is the same reason Java packages and Android
application IDs are written this way.

A forward-named directory is unreachable, not merely untidy: the loaders build
their paths through `ReverseDomainName()` and will never look for it.
`etc/ggcms/holdoffhunger.com/` sat beside `etc/ggcms/com.holdoffhunger/` with a
byte-identical copy of one config file until September 2026, doing nothing.

## The separate doors policy

The author's name for it, and his explanation:

> If a factory has one door, for instruction and argument, the workers on the
> other side might be confused. But if there are two doors, one for instruction
> and one for argument, the workers will NEVER get confused.
>
> Because the problem is: if a worker sees an argument, and THINKS it's an
> instruction -- that is an injection.

That last line is the whole of it. An injection is not a special kind of
attack; it is a worker taking an argument for an instruction, which is only
possible where the two arrived through the same door.

**A value never travels through the instruction door.** The query is the
instruction; the values go beside it as arguments and MySQL puts them together
itself. In practice that means every value is a `?`:

```php
$sql = 'UPDATE Comment SET Approved = TRUE WHERE id = ?';

$client_db->RunQuery([
    'sql'=>$sql,
    'args'=>[$id],
]);
```

and never `'... WHERE id = ' . $id`, **even when `$id` has already been cast to
an integer.** The cast makes that line safe; it does not make the next one
safe, and the whole value of a policy is that it holds without anyone having to
check.

Two ways in, both already built: `DBAccess::GetRecordWhere()` assembles the `?`
list and the bind string from an `$args` definition, and `RunQuery()` takes
`'sql'` and `'args'` directly when a query is written by hand.

### Identifiers are the exception, and they are not values

MySQL cannot bind a table or column name, so those are concatenated -- there is
no other way. The rule for them is that they may only come from a list the code
owns, never from a request. `warroom.php` is the pattern: it matches the
requested table against `$acceptable_tables` and concatenates the *matched*
name, not the submitted one.

### Where this stands

`warroom.php` was converted in September 2026 -- seven queries that
interpolated an already-int-cast `$id`.

Concatenation sites remain in `ORM.php`, `DBAccess.php`, `dbstatus.php`,
`SimpleORM.php`, `ORMSearchURL.php`, `ORMSearch.php`, `DBAdmin.php` and
`modules/html/entry-list.php`. Not all of them are values -- many are
identifiers or fixed clauses, which are fine. They have not been audited one by
one.

## Anathema

Technologies that are not to appear in this codebase, with the reason, so that
nobody has to re-litigate them.

### Magic quotes

**Never.** Not `get_magic_quotes_gpc()`, not `get_magic_quotes_runtime()`, not
`addslashes()` standing in for them, and nothing that assumes input arrives
pre-escaped.

Magic quotes auto-backslashed every GET, POST and cookie value on the way in.
It corrupted data that was never going near a database, it produced doubled
escaping when combined with real escaping, and it did not prevent injection,
which was the only thing it was for. PHP deprecated it in 5.3, removed it in
**5.4 (2012)**, kept the two accessors as stubs returning false, and deleted
those in **8.0**.

The correct answer is the one the engine already uses: **bound parameters.**
`DBAccess::GetRecordWhere()` builds the `?` list and the type string,
`FillArraysFromDB()` binds the values. A value never becomes part of a query
string. Anything that reaches for escaping instead is solving a problem that
binding has already solved.

Two entries for the removed accessors survived in the `Script/PHP.php`
introspection registry until September 2026, where master-c rendered them as
buttons and pressing one produced a 500. They are gone.

### Defensive re-running

**Never.** Not `if(!$thing) { do_thing(); }` guarding work that was supposed to
have happened already, and not a "conditional start", an "ensure", or a "make
sure it is open" helper of any shape.

If line 10 does the work and line 20 asks whether line 10 ran, exactly one of
two things is true. Either line 10 runs, and the guard is dead weight that
every reader after you has to think about. Or line 10 does not run, and the
guard is hiding a defect that is still sitting there. The question at line 20
is the wrong question in both cases: find out why line 10 did not run, and fix
that.

There is a second cost beyond the untruth. A guard like this performs the work
from wherever it happens to be called, which is every call site rather than the
one place the design chose — so an expensive operation runs an unknown number
of times, and nobody can say how many.

The instance, September 2026. `Handler::Construct_DBAccess` had its `DBStart()`
commented out, so no connection was ever opened where the handler builds
`DBAccess`. Instead of restoring that one line, `DBStartConditional()` and
`IsLinkOpen()` were written and called from eleven places across `DBAccess`,
`Dictionary` and the page cache warmer, each reopening a connection on demand.
That inverted the lifecycle the engine is built on — the handler opens, the
request works, `__destruct` closes — and the symptom being chased, `mysqli
object is already closed`, was itself produced by the missing start. Restoring
the commented-out line made all eleven unnecessary.

### Frameworks, autoloaders, namespaces

See [../AGENTS.md](../AGENTS.md). There is no framework underneath this and
none is wanted; the rule is recorded there rather than duplicated here.

## Arrays are `[]`, never `array()`

Short array syntax everywhere, in declarations and in `$args` hashes alike.
The engine, the CLI and the page scripts are already written this way.

`usr/lib/ggcms/dep/` is exempt. Vendor code is not ours to restyle, and a
local edit there is lost the next time the dependency is replaced.

The admin templates are the unconverted pocket -- 2,354 `array(`
constructors survive outside `dep/`, 1,947 of them under
`templates/default/systemstatus`. That is a stalled migration, not a second
convention.

**Convert a call site when you are already editing it. Do not sweep.** The
reason is on the record: the last sweep is what left
`ViewMySQLProcedureStatusTable.php` opening an array with `[` and closing it
with `);`, a parse error in a file nothing loads, undetected until a bracket
sweep found it in September 2026. A conversion you are not testing is a
conversion that can sit broken for years.

## Loading

There is no namespacing and no autoloader. Files are required explicitly.

* `ggreq('classes/Format/HTML.php')` requires relative to `GGCMS_DIR`.
* `depreq(...)` requires relative to `GGCMS_DEP_DIR`.
* `classes/StandardLibraries.php` holds the master list of everything loaded on
  a normal request, grouped by subsystem with divider comments. **Add new
  classes there** or they will not exist.

Path constants come from `/var/www/ggcms_install_directories.php`:
`GGCMS_DIR`, `GGCMS_DEP_DIR`, `GGCMS_LOG_DIR`, `GGCMS_DATA_DIR`,
`GGCMS_CONFIG_DIR`, `GGCMS_DOC_ROOT`, plus `GGCMS_CLI_DIR` for CLI entry
points.

## Traits carry shared behaviour

Composition, not inheritance, is the default for cross-cutting capability.
`DomainChecker` uses fourteen traits. `Handler` uses `ReverseDNSNotation`.
Format classes extend `AbstractBaseFormat` because they genuinely are a family;
everything else composes.

## `BT:` is the author's marker

There are 101 of them. `BT:` prefixes an inline note, usually inside a
commented-out debug `print`, and usually flags something known-imperfect:

```php
$this->version_object = $version;	# BT: DELETE THIS!
return $response;		# BT: FIXME ?  use $desired_action var pls; NO!
```

Treat `BT:` as a TODO written by someone who knew exactly what they meant and
expected to be the only reader. **Do not bulk-remove them**, and do not act on
one without asking — several record decisions rather than defects.

Commented-out debug prints are left in place deliberately. They are cheap to
re-enable and they mark the places where debugging was once needed.

## Errors

`index.php` sets `error_reporting(0)` and `display_errors` off. Nothing shouts.
A broken page and a working page are indistinguishable from outside, and the
`ErrorLogging` / `IssueLogging` classes plus the CLI error tools are the only
evidence you get. Bear that in mind when reading a bug report, and when
deciding whether something needs a log line.

## British spelling

Prose in this repository uses British spelling. The codebase itself carries a
British/American spelling conversion system, so do not "correct" either
direction on sight.
