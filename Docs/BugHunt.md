# Bug Hunt

A file-by-file pass over the whole engine, begun 27 September 2026. This is
the ledger: what has been read, what was found, and where the proof is. The
day-by-day story is in [DevLogs.md](DevLogs.md); open issues that are not yet
fixed live in [Triage.md](Triage.md).

## The rule

A finding counts when a test fails on the code before the fix and passes after
-- PHPUnit under `tests/`, or a fixture under
`usr/lib/ggcms/tests/regression/rendering-bug-hunt/` where a whole script is
needed. Each fix is its own commit, and its message names the test. Behaviour
that touches rendering is also crawled on Fumiko: 405 pages before and after,
byte for byte, under PHP 8.5.

Order is by exposure: what reads a request first, then what writes, then the
rest. The Language and Database folders are mostly data -- dictionaries and
MySQL's error table -- and are read last.

## Ledger

| Area | State | Found and fixed | Proof |
|---|---|---|---|
| `classes/Math` | Read, tested | 0 and 1 prime; gcd of floats and negatives; random string with no set; string limits | `5ae4d09`, `NumberTheoryTest`, `RandomTest` |
| `classes/Charset` | Read | Nothing | Probed under PHP 8.5 |
| `classes/Security/Authentication.php` | Read, tested | Cookie token kept only hex-looking bcrypt characters; no login limits; unsalted SHA-256 passwords; blank username signed in with the published seed | `80c1d61`, `3b5127a`, `a09523f`, `AuthenticationTest` |
| `classes/Security/HandleInput.php` | Read | Live paths clean and tested. `FormatTitleOuput()` and `CleanseInput_Filename()` cut UTF-8 by the byte and pass a NUL, but nothing calls either | `HandleInputTest` |
| `classes/Database/EscapeMySQL.php` | Read, tested | Datetime escaper passed anything containing NOW or DATE (uncalled) | `cfa2dd6`, `EscapeMySQLTest` |
| `classes/Networking/Query.php` | Read, tested | A POSTed 0 or empty value was replaced by the query string | `3610b30`, `QueryTest` |
| `classes/Networking/Curl.php`, `scripts/ping.php` | Read, tested | Fetched `file://` and private addresses into the public docroot | `0a26c05`, `6064dee`, `CurlTest` |
| `usersessionid` fields, both repositories | Removed | Session token printed into page markup | `f405639`, config `4ece50f`, crawl |
| `scripts/dbstatus.php` | Read | `KillDisconnectedImages()` left inert on purpose -- see Triage | -- |
| `classes/Networking/Domain.php` | Read | Nothing new. `SERVER_NAME` is the visitor's Host, but the configuration `require()` already checks `IsHostName()` | `DomainTest` |
| `Handler::HandleRequest_Content()` | Probed | Serves `readfile()`/`require()` from a path built from `SCRIPT_URL`. On Fumiko, Apache refuses every traversal: `../`, `%2e%2e`, double-encoded, `%2f`, and junk Host headers all end at 400 or 404 | Probe, 27 September |
| `classes/Networking/UserTracking.php` | Read | Nothing. Beacon fields are printable ASCII capped at 512 bytes | -- |
| Raw SQL, engine-wide | Traced | Nothing. Every string-built query (44 `RunQuery` calls, the ORM's `order_by`, `LIMIT`s, table and field names) was traced to its source: fixed strings, `(int)` casts, `?` placeholders or allowlists. No request value reaches SQL unbound. `dbstatus`'s image deleter builds SQL from file names, but it is inert | -- |
| `Handler::ValidateSecurity()` | Read | Sets `session.referer_check`, deprecated in PHP 8.5 and meaningless here -- GGCMS never starts a PHP session. Left for Ben: it carries his comment | -- |
| `tests/regression/rendering-bug-hunt` | Repaired | 48 fixture runs failing under PHP 8.5, all fixture-side | `1652cd0`, 509/509 under 8.5 and 8.1 |

## Not yet read

Everything else. Next, in order: the rest of `classes/Networking` (`Handler`,
`Domain`, `Cookie`, `UserTracking`), `classes/Database/DBAccess.php`, then the
scripts that write -- `modify`, `user-panel`, `view` actions -- then `traits`,
`modules`, the default templates, `cli`, and last `Language` and the data
tables in `Database`.
