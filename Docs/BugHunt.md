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
| `Domain::IsReferringWebsiteSelf()` | Read, tested | A lookalike on another TLD (revoltlib.xyz) counted as the site and skipped the referral blocklist; nothing had ever tested that a referrer is smitten | `a2d4e08`, `DomainTest` |
| `classes/Format/Image.php`, `Handler::handleSrvLocalFiles()` | Read, tested, crawled | Every .webp and .jfif on the sites (410) came back empty; a query string did the same to any image; .html/.svg/.php under /image/ served as what they are; /srv files had no Content-Type; missing images were an empty 200 | this commit, `ImageTest`, `HandlerTest::testSrvLocalFile()` |
| `Handler.php` split, first part | Refactored, crawled | `HandlerRedirects` takes the 26 redirect-and-repair functions, 998 lines; Handler goes from 2,552 lines to about 1,570. Logic only: every property stays on Handler | 405 pages and 44 mangled URLs, byte for byte and Location for Location; `HandlerRedirectsTest` |
| `Handler.php` split, second part | Refactored, tested, crawled | `HandlerScript` takes the 8 script-stage functions (script, file, class, format, extension, location); the permalink trio joins `HandlerRedirects`. Handler is about 1,240 lines. The stage had no tests: now 4, including one that fails if the format and lower-case-format switches ever drift apart | 405 pages and 44 mangled URLs unchanged; `HandlerScriptTest` |
| `scripts/transfer.php` | Fixed, tested, verified on Fumiko | Not admin-only: any sign-in could move any entry, stopped only by a crash that also stopped every administrator's move. Admin-only first, then the wiring; cycles refused | `transfer-entry` fixture, 7 cases |
| War room comment and suggestion views | Fixed, tested | `viewComment` printed a visitor's stored comment raw through the generic list -- a script posted as a comment ran in the administrator's session on moderation. `viewSuggestion` built its list and never displayed it. Comments are stored as sent (input cleansing only drops NUL); the public pages already `strip_tags()` them | `warroom-escaping` fixture, 4 cases |
| `scripts/suggest.php` | Read | Writes only with a signed-in user and an entry, as it should | -- |
| `scripts/view.php` votes | Fixed, tested, verified live | Downvote was an `ArgumentCountError` for everyone; an anonymous vote reached an insert with no user, a 500; every action said Success regardless | `view-votes` fixture, 9 cases |
| Login-only scripts | Fixed, tested | `formmaker.php` (unfinished) and `languageutils.php` (a dictionary tool) were open to any sign-in, like `transfer.php` was; `formmaker::list()` also crashed on every call. Both admin-only. `user-panel.php` reads only the reader's own rows; `chapterify.php` splits text in the browser and posts each chapter to `modify.php`, open to readers by design | `ScriptAccessTest`: every login script is admin-only or named, with a reason |
| Request text MySQL cannot hold | Fixed, tested, crawled | Malformed UTF-8 in a path, or an emoji in a path or query string, was a 500 on every site, and unrecordable: the Code lookup and both loggers handed MySQL what a utf8mb3 column cannot hold. Codes the column cannot hold now name nothing; the loggers scrub to U+FFFD. The fallback log carries the trace it lacked. An emoji in a parameter that reaches a lookup is left to the utf8mb4 conversion -- see Triage | `ORMTest`, `LogRedactionTest::testStorableValues()`, 449 pages unchanged |
| `Handler.php` split, last three parts | Refactored, tested, crawled | `HandlerFiles` (images, /srv files), `HandlerEntryPath` (does the path walk the graph; repairing it) and `HandlerContent` (rendering, the 404). Handler is about 770 lines: properties, the constructor, the `HandleRequest()` conductor, gatekeeping and the end of the request | 405 pages and 44 mangled URLs unchanged; images and /srv against real files; `HandlerFilesTest` |
| Pasted-link repair, nginx and engine together | Fixed, tested, verified live | nginx slashes every dotless path first, so `/people)` arrived as `/people)/` and 404ed; and a repair answered in place never went back through nginx, so `/people).` was repaired to slashless `/people` and 404ed. Both now reach the page, canonical `/people/` | `5af488e`, `2af51b7`, `HandlerRedirectsTest` |
| Raw SQL, engine-wide | Traced | Nothing. Every string-built query (44 `RunQuery` calls, the ORM's `order_by`, `LIMIT`s, table and field names) was traced to its source: fixed strings, `(int)` casts, `?` placeholders or allowlists. No request value reaches SQL unbound. `dbstatus`'s image deleter builds SQL from file names, but it is inert | -- |
| `Handler::ValidateSecurity()` | Read | Sets `session.referer_check`, deprecated in PHP 8.5 and meaningless here -- GGCMS never starts a PHP session. Left for Ben: it carries his comment | -- |
| `tests/regression/rendering-bug-hunt` | Repaired | 48 fixture runs failing under PHP 8.5, all fixture-side | `1652cd0`, 509/509 under 8.5 and 8.1 |

## Not yet read

Everything else. Next, in order: the rest of `classes/Networking` (`Handler`,
`Domain`, `Cookie`, `UserTracking`), `classes/Database/DBAccess.php`, then the
scripts that write -- `modify`, `user-panel`, `view` actions -- then `traits`,
`modules`, the default templates, `cli`, and last `Language` and the data
tables in `Database`.
