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
