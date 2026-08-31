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

### 500 errors, by family

Read these from the **database**, not the Apache log — the log is `combined`
and does not record which site served a request. See
[../Development/Conventions.md](../Development/Conventions.md) on ISE and ISI.

```bash
php8.1 -c /etc/php/8.0/apache2/php.ini   /usr/lib/ggcms/cli/scripts/internal/errors/server_error_counts.php
```

Per-domain counts as at 31 August 2026, 00:00 UTC:

```
earthfluent      1520      copyleftlicense    85
masereelgroup      41      anarchistcode       8
holdoffhunger       6      listkeywords        5
ouruprising         5      pronouncethat       3
```

**earthfluent is three-quarters of all errors**, and every row is from the
evening of 30 August — its file cache had been masking the crash the same way
revoltlib's was, and it surfaced once traffic patterns shifted.

Families, largest first:

| count | error | location |
|---|---|---|
| 1056 | `count(): Argument #1 must be Countable\|array, null given` | `templates/earthfluent/view/display_grandchildof_EarthFluent.com.php:785` |
| 674 | `mysqli_close(): must be of type mysqli, null given` | `DBAccess.php:158` |
| 398 | `mysqli object is already closed` | `DBAccess.php:371` |
| 452 | `EntryTranslation_enabled() on null` | `ORM.php:2079` — **fixed, zero since 20:10** |
| 89 | `GetDefinitionsCount() on null` | `templates/wordweight/view/display_index.php:122` |
| 57 | `Failed opening required 'unifont/ttfonts.php'` | `Format/RTF.php:42`, `TEX.php:59`, `SGML.php:46`, `OPDS.php:120` |

Notes on each:

* The **earthfluent `count()`** family is the largest live bug and was *rising*
  at 294 per half-hour when last measured — probably because the site became
  reachable again, so more requests now get far enough to hit it. Likely the
  same root as the `EntryTranslation_enabled` crash: the template counts child
  records that came back null.
* The two **`mysqli`** families are one bug wearing two masks — see below.
* The **`unifont/ttfonts.php`** family is a missing font file for the fpdf
  library, affecting every document format. Probably a deployment gap rather
  than a code defect. It is why `view.pdf` URLs appear in the 500s.

### `ping.php` is an SSRF and local-file disclosure primitive

`ping::Curl()` passes its `url` parameter directly to cURL with no protocol,
host, resolved-address or port policy. cURL may therefore fetch loopback and
private-network services and, when the installed protocol set permits it, local
files through `file://`. The complete response is returned to the requester and
written under a relative `curl/` directory; the script constructs a predictable
public URL for that backup. Sensitive responses can consequently persist in a
web-addressable location.

The current `AdminOnly()` bypass makes this reachable by any authenticated user.
Fixing that bypass is necessary but not sufficient: the action is also a GET
request without CSRF protection, and an administrator's browser remains an
unsafe network proxy.

If this diagnostic is retained, allow only `http` and `https`, restrict ports,
resolve the destination and reject loopback, link-local, private, reserved and
metadata addresses (including IPv6), and apply the policy again after every
redirect or disable redirects. Set tight connection, total-time and response-
size limits. Do not persist response bodies beneath the document root; use
random non-public names and an authenticated download action if retention is
actually needed. Test alternate numeric IP forms, DNS answers, IPv6, redirects,
`file://`, oversized responses and timeouts.

### `AdminOnly()` grants access to logged-in non-admin users

`Authentication::Authenticate()` currently grants every authenticated user
access to scripts that declare `AdminOnly() === TRUE`. The test at line 33
combines the requirement and the user's status:

```php
if($this->script->script->AdminOnly() && $this->user_session['UserAdmin.id']) {
    // admin path
} else {
    $this->access_granted = 1;
}
```

For a logged-in non-admin, `AdminOnly()` is true but `UserAdmin.id` is false,
so the combined condition is false and the `else` grants access. Six scripts
are exposed: `master-c.php`, `dbstatus.php`, `ping.php`, `systemstatus.php`,
`userstatus.php` and `warroom.php`. Their actions include database maintenance
and cloning, host and PHP introspection, comment and suggestion moderation, and
viewing or resolving ISE records.

Separate the question "does this script require an administrator?" from "is
this user an administrator?" A secure script requiring login must deny access
when `AdminOnly()` is true and `UserAdmin.id` is absent. Add focused tests for
anonymous, ordinary authenticated, administrator, and non-admin-only access;
the existing test suite has no authentication coverage.

### Session-token generation lossily treats bcrypt output as hexadecimal

`Authentication::GenerateCookieToken_Secure()` creates a bcrypt string, then
calls `Base::ConvertBase()` with `startingbase=>'Hexadecimal'`. A bcrypt string
contains its `$2y$...` framing and random characters from the bcrypt alphabet,
most of which are not hexadecimal. `ConvertBase()` looks up every character in
the declared source alphabet without validation; characters outside `0-9a-f`
produce undefined-key notices and append no bits. The returned token is only a
lossy encoding of the accidental hexadecimal-looking subsequence, not an
encoding of the complete bcrypt output.

Bearer tokens do not need password hashing or user-derived input. Generate at
least 32 cryptographically random bytes directly with `random_bytes()`, encode
them reversibly as hex or base64url, and fail closed if generation fails. Avoid
the custom general-purpose base converter here. Add tests for fixed decoded
length, allowed encoding characters, uniqueness across a large sample and
successful database/cookie round trips. Existing tokens should be invalidated
when the new representation is deployed.

### Authentication tokens do not meaningfully expire

`Authentication::CheckCurrentAuthentication()` intends to limit a session by
`LastAccess`, but its raw predicate is:

```sql
UserSession.LastAccess < DATE_ADD(UserSession.LastAccess, INTERVAL 160 HOUR)
```

That compares the timestamp with itself plus 160 hours and is true for every
ordinary non-null value. `ErrorLogging::displayErrorToAdmin()` repeats the same
mistake with four hours, so it also accepts any matching admin token regardless
of age.

The browser-side lifetime is similarly longer than its caller asks for.
`Cookie::SetCookie()` chooses the ten-year `PermanentCookieExpirationTime()`
whenever a cookie is secure, because its temporary branch requires both
`!$secure` and `!$permanent`. Authentication correctly passes `secure=>TRUE`
but does not request permanence; the condition makes it permanent anyway.

`ReAuthenticate()` rotates an active token after fifteen minutes, which limits
an old token once its legitimate owner returns. It does not expire an inactive
session: absent logout or later replacement, the database row and browser
cookie can remain usable for ten years.

Choose the intended idle and/or absolute lifetime explicitly, compare the
current time with `LastAccess` rather than comparing `LastAccess` with itself,
and make cookie security independent of cookie persistence. Test active,
expired, refreshed and logged-out tokens in both ordinary authentication and
admin error display.

### Like/dislike actions disagree across JavaScript, dispatch and database layers

The live `like-dislike.js` client posts all four actions to `view.json`, but the
HTML/JSON action dispatcher invokes the selected method with no arguments.
`view::upvote()` and `view::undoupvote()` accept none; `view::downvote($args)`
and `view::undodownvote($args)` require one and then pass it to
`SetUserAndEntry()`, which itself accepts none. Downvote and undo-downvote
therefore raise `ArgumentCountError` instead of recording the click.

The two currently callable actions have quieter correctness failures.
`SetUserAndEntry()` returns false when authentication or entry resolution fails,
but every vote action ignores that result and returns `Success => 1` anyway.
Undo also dereferences a missing vote rather than treating it as idempotent or
reporting absence. On insertion, `SetUserLike()` indexes
`CreateRecord(...)[0]`, although `DBAccess::CreateRecord()` already returns the
created row; the insert can succeed while the method discards its result.

Make all four public action signatures match the zero-argument dispatcher,
stop immediately when `SetUserAndEntry()` fails, define undo-without-a-vote as
an explicit idempotent success or failure, and consume `CreateRecord()`'s row
directly. Return success only after the database operation succeeds. Add tests
for authenticated and anonymous requests, missing entries, first votes, vote
changes, repeated undo and all four action names.

The client also reuses one global `XMLHttpRequest` object for every click. A
second rapid click can replace or abort the first in-flight request while the
optimistic counters have already changed. Create one request per operation and
reconcile the displayed state from the server response.

### Administrative state changes have no CSRF protection and use GET

GGCMS has no CSRF token or nonce mechanism. `session.referer_check` does not
protect it because authentication uses GGCMS's own `AuthenticationToken` cookie,
not PHP sessions. `Domain::ValidateReferringWebsite()` is a referral blacklist:
it deliberately accepts ordinary external referrers and therefore does not
prove that an authenticated user intended a request.

Several administrative mutations are exposed as ordinary links. The war room
renders GET URLs for `resolveError`, `acceptComment`, `rejectComment`,
`acceptSuggestion` and `rejectSuggestion`; each action updates a client
database. A cross-site top-level navigation can carry a default/Lax cookie, so
another site can cause a logged-in administrator's browser to perform these
operations without being able to read the response.

State-changing actions should reject GET, require POST, and verify an
unpredictable token bound to the authenticated session. Apply the check in one
shared action boundary rather than independently in templates, and test missing,
wrong and correct tokens. Audit the other secure scripts for GET mutations once
the shared mechanism exists.

### ISE and ISI records persist unredacted credentials and tokens

Both failure paths serialize request data directly into database records.
`ErrorLogging::logInternalServerError()` stores `print_r($_SERVER)`,
`print_r($_POST)`, `print_r($_GET)` and the entire recursive handler object.
`IssueLogging::recordIssue()` stores the same request arrays and recursively
prints its own object graph. No redaction or sensitive-key filter exists.

Those structures can contain plaintext login passwords, Google ID tokens, the
hidden `usersessionid` bearer token, `AuthenticationToken` and other cookies,
authorization headers, complete query strings, the Google client secret and the
global password seed. An error or issue record can therefore become a durable
secondary credential store. The full object dumps also make the records much
larger and less stable than a selected diagnostic context.

Build one recursive, case-insensitive redaction boundary shared by ISE and ISI
logging. Start from an allowlist of useful server fields; explicitly exclude
cookies and authorization headers; replace sensitive request values such as
`password`, `token`, `secret`, `authorization`, `cookie` and session identifiers
with a fixed marker. Store a small deliberate diagnostic context instead of a
recursive application object. Tests should submit nested and differently cased
sensitive keys and assert that neither their names' values nor known secret
sentinels occur anywhere in the serialized record.

Treat existing rows as potentially sensitive. Before broadly displaying,
exporting or sharing them, audit access and retention, identify any live
credentials present without reproducing them in logs, rotate affected secrets,
and purge or redact historical payloads through a reviewed migration.

### Live authentication tokens are copied into dead DOM fields

GGCMS renders the current `UserSession.CookieToken` into a hidden input named
`usersessionid` in the shared comments module and sixteen site templates. A
repository-wide search finds no PHP or JavaScript consumer of `usersessionid`:
the field is write-only legacy output, not part of form authentication.

The value is the bearer credential used by the `AuthenticationToken` cookie.
Putting it in page markup gives every script running in the page direct access
to the token and also places it in copied DOM, browser tooling and any captured
HTML. Independently, `Cookie::CookieHTTPOnlyOption()` returns `FALSE`, so the
cookie itself is readable by JavaScript as well. Any successful script injection
can therefore steal a session rather than merely act inside the current page.

Remove all `usersessionid` fields and verify that comment, suggestion and other
affected forms still submit successfully. Then set the authentication cookie
`HttpOnly`, choose an explicit `SameSite` policy compatible with the intended
cross-site behavior, and keep `Secure` enabled. Test login, token refresh,
logout and the affected forms; do not treat `HttpOnly` alone as sufficient while
the same token remains present in HTML.

### Image upload accepts active content and its validation skips records

`modify::SetRecordFromQuery()` accepts the browser-supplied upload name and a
user-editable `image_FileName`; no server-side file signature or decoded-image
allowlist is checked before `move_uploaded_file()` places the bytes under the
per-domain `/www/image/` tree. Imagick sees the file only after the move. The
shared `/srv` file handler later returns arbitrary local-file bytes with
`print(file_get_contents(...))` and does not set a type derived from verified
content, force an attachment disposition or emit `X-Content-Type-Options:
nosniff`. An authenticated contributor can therefore publish active HTML or
script-capable content at a trusted site origin.

The intended validation is itself miswired. `ValidateRecordForSaving_Image()`
increments `$i` inside a `for` loop that also increments it, never assigns the
current `$image`, passes that undefined value to `ValidateSingleImage()`, and
therefore processes only every other file slot. The save loop later processes
every image. Existing filename and collision checks are not reliably applied to
the records being written, but even a repaired loop has no content policy.

Before moving anything into durable storage, require `UPLOAD_ERR_OK`, verify
`is_uploaded_file()`, enforce a size and decoded-pixel limit, identify the type
from server-observed bytes, decode it successfully, and allow only the formats
the application intentionally supports. Generate the storage filename and
extension from trusted data rather than accepting a path from either browser
field. Prefer re-encoding raster images to a fresh file; treat SVG as active
content unless it is rigorously sanitized and served from an isolated origin.
Check every filesystem and Imagick result, delete temporary/partial output on
failure, and serve media with an explicit allowlisted content type plus
`nosniff`. Add multi-file tests so every index is validated.

### GET overrides false-valued POST parameters

`Query::Construct_Parameters()` deliberately loads POST data before GET data,
but `Construct_Parameters_GETData()` decides whether a key already exists with:

```php
if(!$this->parameter_data[$key])
```

That tests truthiness, not presence. If POST supplies `0`, `'0'`, an empty
string or an empty array, a query-string parameter with the same name replaces
it. This violates the apparent POST-over-GET precedence and can change submitted
flags or empty-field semantics according to the URL carrying the form request.

Use `array_key_exists($key, $this->parameter_data)` for precedence. Add tests
for missing keys and for POST values `0`, `'0'`, `''`, `FALSE`, empty arrays and
ordinary strings, each paired with a conflicting GET value. Keep value
validation contextual at the consumer; this fix concerns source precedence, not
generic sanitization.

### Login attempts have no application-level throttling

`Authentication::Login()` performs the account lookup and immediately returns
success or failure. The repository contains no failed-attempt counter, source or
account rate limiter, retry delay, temporary lockout or challenge mechanism.
The displayed failure is generic, which is good, but an attacker can still make
unbounded online guesses as fast as the web and database tiers permit.

Apply rate limits at both the deployment edge and an application boundary that
cannot be bypassed by changing hostnames or client databases. Key cautiously on
a combination of normalized account identifier and trusted client address;
return a generic response, add escalating delays or temporary limits, and avoid
permanent lockouts that enable denial of service. Record security telemetry
without storing submitted passwords. Test distributed attempts, IPv4/IPv6
normalization, successful-reset behavior and limits shared across domains.
Verify current reverse-proxy or WAF policy before deployment, since no upstream
login-specific limit is documented in this repository.

### User passwords use unsalted single-pass SHA-256

`Authentication::Login()` computes `hash('sha256', $password)` and asks MySQL
to compare its 32 decoded bytes directly with `User.Password`. The schema fixes
that column at `BINARY(32)`. There is no per-user salt, deliberately expensive
password hash, `password_verify()` call or rehash path. A copy of any client
database therefore permits high-speed offline guessing, with equal passwords
producing equal stored values across every user and client database.

This needs a staged migration rather than a local substitution. Expand the
column to store a modern encoded `password_hash()` result, verify new-format
hashes with `password_verify()`, and opportunistically replace a legacy SHA-256
value after a successful legacy login. New passwords must use the modern format
from the start. Preserve an explicit distinction for Google-only accounts: they
currently receive SHA-256 of the global `passwordseed`, and should not become
ordinary password-login accounts accidentally during migration.

Test legacy login and upgrade, modern login, wrong passwords, copied admin
accounts and Google-created accounts before removing the legacy branch. Do not
log either plaintext passwords or stored password material during migration.

### First-time Google login passes the empty lookup to `Login_Successful()`

`Google::AuthenticateOrDisauthenticateWithGoogle()` correctly creates a `User`
when no account exists for the verified Google email, and `CreateRecord()`
returns that newly inserted row. The branch stores it in
`authentication->user_account`, but the next line calls:

```php
Login_Successful(['useraccount'=>[$user_account]])
```

At that point `$user_account` is still the empty result from the lookup, so the
extra brackets produce a nested empty array. `Login_Successful()` expects row
zero to contain the new user's `id`; without it, session creation cannot attach
the authentication token to the account. Existing Google users take the other
branch and are unaffected.

Pass `[$user_creation_results]`, matching the assignment immediately above it,
and cover first login and returning login separately. Also assert that first
login creates exactly one user and one usable `UserSession`, since merely seeing
the `newuser` response flag does not prove authentication succeeded.

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

### Preserve the original `mysqli` construction failure as an ISE

The connection-closing fix stops `Handler::__destruct()` from replacing a
database failure with `mysqli_close(NULL)`, but `DBAccess::DBStart()` still
catches the exception from `new mysqli(...)`, discards it, and immediately
reads `$this->db_link->connect_errno`. If construction threw, there is no link
to inspect. The useful failure is therefore still replaced one level earlier,
and the fallback connection may never be attempted.

When `mysqli` construction fails, preserve the original failure as an ISE with
its exception class, numeric code, SQLSTATE, message and trace, plus the
database, host and port that were attempted and whether the fallback was tried.
Never store the password or other credentials.

There is a storage paradox to solve deliberately: ordinary ISE persistence
uses `DBAccess::CreateRecord()`, but this ISE exists precisely because that
database connection could not be created. If the fallback connection succeeds,
the normal `InternalServerError` record can be written through it. If no
connection succeeds, the failure needs a durable non-database fallback that can
be recovered into the ISE table later; attempting the ordinary logging path
again would only recurse into the same fault.

### `transfer.php` uses undefined locals around a live assignment update

The transfer model is sound: moving a whole branch requires changing only the
one assignment that places its root. The current admin script has two concrete
implementation defects around that update.

* `transferentry()` stores the parent search result in
  `$this->new_parent_results`, then tests and copies the undefined local
  `$record_results` at lines 74-77.
* It calls `BackupEntryCodeReservation()` with the undefined locals `$entry`
  and `$backup_record` at line 93. The method expects real entry arrays and
  dereferences both while writing reservation records.

The first loses the intended result and error reporting. The second can emit
warnings and pass null identity data into the reservation backup immediately
before the assignment update. The update itself is independent and may still
succeed, leaving the transfer apparently complete while its old-path
reservations are absent or malformed.

Fix the local wiring without changing the one-assignment transfer model, and
verify both a successful move and a same-code conflict before closing this.

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

### `fwrite()` on a non-resource — cause fixed, guard still wanted

```
Uncaught TypeError: fwrite(): Argument #1 ($stream) must be of type resource
```

**Cause, and it was self-inflicted on 30 August 2026.** The document formats
cache their output under `GGCMS_DIR . 'data/<format>/<host>/'`, which is
`/usr/lib/ggcms/src/data/`. That directory is not in the repository, and
`bin/deploy.sh` ran `rsync --delete` against `/usr/lib/ggcms/`. The first deploy
removed it, and every RTF, TEX, SGML, OPDS, PDF and EPub request afterwards
failed on `fopen()` returning false.

Timestamps confirm it: zero such errors before 19:29 that evening, 226 on
revoltlib after. `deploy.sh` now excludes `src/data/` from `--delete` and
recreates it, so this is self-healing on the next deploy.

**Still open: none of the format classes check `fopen`.**

```
CSV.php  EPub.php  PDF.php  RTF.php  SGML.php  TEX.php
```

Eight `fwrite` calls between them, zero guarded — the pattern is always

```php
$file_handle = fopen($location, 'w+');
fwrite($file_handle, $output);
fclose($file_handle);
```

The right shape is a single `WriteGeneratedFile()` on `AbstractBaseFormat`,
next to `SetSourceFileLocation()` and `SetOutputFileLocation()` which already
live there, checking the handle and returning FALSE rather than throwing. Six
files change; do it in one pass rather than per-format, and do it where someone
can watch — this is the same "fix it once in the shared place" case as the
`dictionary` args bug.

### Unguarded `count()` on child-record arrays in the format classes

A child-record array is `NULL`, not an empty array, when the entry has none of
that type. `count(NULL)` is fatal in PHP 8, so any document-format request for
an entry lacking that child type dies.

**The correct idiom already exists in the codebase**, in `RDF.php`:

```php
$tag_count = $this->script->record_to_use['tag'] ? count($this->script->record_to_use['tag']) : 0;
```

`OPDS.php:119` is fixed (it was the one observed firing). Twenty-two sites
remain unguarded:

| File | Lines |
|---|---|
| `DAISY.php` | 117, 151, 215, 301, 328 |
| `EPub.php` | 113, 146 |
| `OPDS.php` | 182, 199 |
| `RDF.php` | 300, 317 |
| `TEX.php` | 130, 146 |

Mechanical, but worth doing in one supervised pass rather than by regex —
these are `.daisy`, `.epub`, `.opds`, `.rdf` and `.tex` paths that are awkward
to exercise, and a typo would not show up until someone requested that format.

Low volume in practice (single figures per fifteen minutes), because these
formats are rarely requested.

### Subdomains via inverted assignment (unbuilt, wanted)

`Parentid = 0, Childid = <entry>` would mean `<entry>.host.com`, mirroring the
existing `Parentid = <entry>, Childid = 0` that means "attached to the host
root". The model already supports it; nothing is built. Requires no new tables
and no new routing concepts. See
[Architecture.md](Architecture.md#assignments-and-the-reserved-id-0).

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
