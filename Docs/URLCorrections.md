# URL corrections

Everything `Networking/Handler.php` does to a URL that is not quite right, in
the order it does it.

There are two mechanisms and the difference between them matters more than any
individual rule:

- **Repair in place.** The request is rewritten in memory and answered. The
  visitor gets the page they asked for and pays no round trip. The original is
  kept so nothing is lost.
- **Redirect.** The visitor is sent a 302 and asks again. Correct, but it costs
  a round trip, and under prefork it costs a worker slot twice.

Repair is preferred wherever the correct target can be known without a database
lookup. The redirect list below is not a list of things done right or wrong; it
is a list of candidates.

## Repaired in place

### Absolute-form request URI

`RequestPath()`

RFC 7230 permits a proxy to send `GET http://host/path HTTP/1.1`, which puts the
whole URL in `REQUEST_URI` rather than a path. Concatenating that onto the
domain produced `http://www.example.comhttp://www.example.com/path`; the client
followed it and every hop prefixed the domain again. Each hop was a brand-new
URL, so nothing could ever be cached and every one cost a full render.

**95% of all traffic to this host was that loop.**

`RequestPath()` returns a path whichever form arrived, and is what every later
handler must test against. `handleMultipleSlashesRedirect` is the cautionary
tale: it tested `REQUEST_URI`, an absolute-form URI always contains `//` inside
`http://`, so it rebuilt the identical URL and returned a 302 to the address
already being requested, forever.

### Repeated query-string separators

`Construct_RepairQueryString()`

A URL may carry only one `?`. Every later one is a separator that should have
been `&`, because something appended a parameter to a URL that already had a
query string:

```
view.pdf?mobilefriendly=1?mobilefriendly=1?action=browse
```

PHP fills `$_GET` from the raw query string before this class runs, so without
repair the script receives `$_GET['mobilefriendly'] = '1?mobilefriendly=1?action=browse'`
and answers with a 500. Because `QUERY_STRING` is everything after the *first*
`?`, any `?` still inside it is by definition a mis-typed separator -- the test
is exact rather than heuristic.

The original parse is kept in `$GLOBALS['_ORIGINALGET']` and on
`$this->original_get`. **This is the pattern to follow when converting anything
below from redirect to repair:** rewrite the superglobal, keep the original
beside it under an `_ORIGINAL` name.

## Redirected, in dispatch order

| Handler | Trigger | Target |
|---|---|---|
| `SecureRedirect` | `SecureRequired()` and the request is not HTTPS | same URL over https |
| `handleMailTo` | mailto in the path | -- |
| `handleGitRedirect` | first segment is `git` or `.git` | `version->GetOpenSourceURL()` |
| `handleLinuxUserRedirect` | path begins `/~` | `/` |
| `handleMultipleSlashesRedirect` | `//` anywhere in the *normalised path* | slashes collapsed |
| `handleForceCanonicalLinkRedirect` | site configures a canonical directory and the script is not in it | canonical directory + script |
| `handleUndesirableParameters` | parameters the site does not accept | stripped |
| `handleCopyPasteErrorRedirect` | path begins `/ftp` or `/http` | `/` |
| `handleBadLinkRedirect` | trailing `. ) ] } ' "` or a two-character pair of them | punctuation stripped |
| `handleImageRedirect` | -- | **commented out of the chain** |

`handleForceCanonicalLinkRedirect` refuses on any method but GET, because a 302
would discard the request body. Any handler converted to repair-in-place stops
having that problem.

`handleBadLinkRedirect` guards itself with `?stopredirect` so it cannot loop.

## Redirected after the content lookup fails

These run only when the URL did not resolve, and unlike the list above they
require database work to find the target.

| Handler | What it tries |
|---|---|
| `handleReservedCodeRedirect` | the full code, the code without its extension, the full reserved code, the last segment |
| `handleMatchingCodeRedirect` | the fullest code, the near-fullest, the last segment, the second-to-last -- each through `handleCodeRedirect`, which looks up `Entry.Code` and builds a permalink |
| `handleMisplacedScriptRedirect` | a script name that appears somewhere other than the end |
| `handleScriptRedirect` | a script that declares `redirect_script`, `redirect_action` or `redirect_query` |
| `CheckPermalinkRedirect` | a permalink id, through `PermalinkRedirect` and `BuildRedirect` |

## The rule for what to add

Two separate decisions, and conflating them is the mistake:

**Server side, repair always.** Including permalinks. Rewrite `$_GET`,
`$_SERVER`, `$_POST` and whatever else the request carries, keeping each
original under an `_ORIGINAL` name, and answer the request. Nobody should pay a
round trip for a mistake the server can see through.

**Address bar, rewrite only a typo.** A permalink is a legitimate, deliberately
short URL and a reader who shares one has shared something correct -- leave it
alone. But `site.com/http://site.com`, a trailing bracket, a doubled `?`, a
`/~user` path: those are mistakes, and quietly correcting the address bar means
the reader bookmarks and shares the URL that will actually be fast next time.

The distinction is not "which handler" but "was the input a mistake or a
choice."
