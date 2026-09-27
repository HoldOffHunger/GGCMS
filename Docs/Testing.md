# Testing

GGCMS has unit tests, run by PHPUnit.  They need no database, no network and
no running site: they load the engine the way a CLI tool does from a checkout
and call its functions directly.

## Running them

PHPUnit is a development dependency.  Install it once per checkout:

```bash
composer install
```

It goes into `vendor/`, which is gitignored and sits outside
`usr/lib/ggcms/` -- `bin/deploy.sh` copies that tree to the host with
`--delete`, so nothing under `tests/` or `vendor/` is ever deployed.

The hosts run PHP 8.5, and `composer.json` pins the platform to it.  That
brings PHPUnit 13, which needs PHP 8.4.1 or later, so run the suite with the
PHP the hosts run -- `php8.5`, or on Fumiko `/opt/php85/bin/php` -- rather than
an older `php` that happens to be first on the path.  The tests are run on a
development machine, never on a host: Fumiko, Ben's WSL copy of production
laid out by `bin/local_sync.sh`.

The bootstrap is `cli/system/RepoDirectories.php`, the same one every CLI tool
uses from a checkout, so the same two variables are required:

```bash
export GGCMS_CONFIG_REPO=/path/to/GGCMS_Unhireable
export GGCMS_WORK_DIR=/path/to/scratch

vendor/bin/phpunit                        # src and cli suites
vendor/bin/phpunit --testsuite lint       # php -l and declared properties, both repositories
vendor/bin/phpunit --filter PageCache     # one class, or Class::method
vendor/bin/phpunit "$GGCMS_CONFIG_REPO/tests"   # the private repository's own suite
```

Scratch files -- the page cache tests write real pages -- go under
`$GGCMS_WORK_DIR/data/tests/` and nowhere else.

## Suites

| Suite | Where | What |
|---|---|---|
| `src` | `tests/src/` | The engine: classes and traits under `usr/lib/ggcms/src/` |
| `cli` | `tests/cli/` | The CLI application's classes and traits |
| `lint` | `tests/lint/` | `php -l` over every PHP file in both repositories, and a check that every property a class assigns is declared (PHP 9 makes the rest an Error).  Not in the default run; about fifteen seconds |
| config | `tests/` in the private repository | The shape of `etc/ggcms/` and the template sets.  Site-specific, so it cannot live here |

## Conventions

**The test tree mirrors the source tree.**  The tests for
`usr/lib/ggcms/src/classes/Math/Base.php` are in
`tests/src/classes/Math/BaseTest.php`, and the class is `BaseTest`.

**One test method per engine function**, named `test` plus the function's
name: `Base::ConvertBase()` is tested by `BaseTest::testConvertBase()`.  A
behaviour worth its own name gets a suffix --
`testConvertBaseKeepsTrailingBits()` -- and still begins with the function it
tests.  A method may make any number of assertions.

**Every tested function names its tests.**  At the top of the function's pod:

```php
			// ConvertBase()
			// Tests: BaseTest::testConvertBase(), BaseTest::testConvertBaseKeepsTrailingBits()
			// Test file: tests/src/classes/Math/BaseTest.php
			/*
				...the existing pod...
			*/
		public function ConvertBase($args) {
```

When you add a test, add or extend the header.  When you rename a function,
rename its test.

**Extend `GGCMSTestCase`**, not PHPUnit's `TestCase`.  It saves and restores
`$_SERVER`, `$_GET`, `$_POST` and `$_COOKIE` around every test, and adds:

| Helper | For |
|---|---|
| `requireEngine(['file'=>...])` | A class `StandardLibraries.php` does not load |
| `requireCLI(['file'=>...])` | A CLI class or trait |
| `newWithoutConstructor(['class'=>...])` | `Handler`, `Domain` -- anything whose constructor wants a database or a live request |
| `scratchDirectory()` | A per-class directory under `$GGCMS_WORK_DIR/data/tests/` |

A trait is tested through a small subject class declared at the top of the
test file (`class LogRedactionTestSubject { use LogRedaction; }`).  A CLI
trait must be `require_once`d above that class, not in `setUp()`, because the
class is declared when the file is loaded.

Assertions are PHPUnit's own, with positional arguments.  That is the one
place `$args` hashes give way: it is PHPUnit's calling convention, not ours.

## Warnings and deprecations

The engine reads undefined keys as `NULL` throughout and was written to.
Under PHPUnit those surface as warnings and deprecations in the summary line,
not failures.  They are worth reading and are not, by themselves, bugs.

What is always a failure is an uncaught `Error` -- a `TypeError`, a
`ValueError` -- because on the web that is a 500 that `error_reporting(0)`
hides.  Pin such a crash with a test before fixing it.

## Code that can only run once per process

A class loaded with plain `ggreq()` inside a method -- as
`Domain::ValidateExternalReferralSite()` loads `InvalidReferralDomains` --
can be loaded once per request and so once per process.  Run such a test in
its own process with `#[RunInSeparateProcess]`; see `DomainTest`.
