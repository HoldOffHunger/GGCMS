# Encoding Conventions

Every boundary this engine crosses is an encoding boundary. Content arrives as
form input, is stored in MySQL, is read back into PHP, and leaves as HTML, RSS,
Atom, RDF, OPDS, EPUB, DAISY, TEX, BRF, PDF, CSV or JSON — sixteen output
formats, each with its own grammar and its own idea of what a reserved
character is. Most of the corruption bugs this project has had were a
disagreement between two of those layers rather than a defect inside one.

This document is the set of rules that keep those layers agreeing.

## The system charset is UTF-8

UTF-8 in, UTF-8 stored, UTF-8 out. **Do not hardcode a charset string at a call
site** — take it from whatever the current system-charset accessor is, so there
is one place to change and one place to be wrong.

Note the standing contradiction recorded in [Triage.md](Triage.md): the schema's
tables are `utf8mb3`, which is three-byte, while `Installation.md` requires
`utf8mb4`. Until that is resolved, the declared charset and the stored charset
are not the same thing.

> **The `src/classes/Charset/` subsystem is mid-decommissioning as of 31 August
> 2026.** The sections below marked *(Charset subsystem)* describe how it works
> today and should be re-checked, or deleted, once that lands. Everything else
> in this document is about the encoding job itself and survives whatever
> replaces those classes.

## A dump is an encoding boundary too

`mysqldump` transcodes to the connection charset on the way out, so the flags
on a backup decide what survives it. Until 1 September 2026 every dump went
through `--default-character-set=latin1 --skip-set-charset`, which is lossy
against a `utf8mb3` schema and silent about it.

MySQL's `latin1` is really cp1252, which is why it looked fine for so long --
en dashes, em dashes and curly quotes are all inside cp1252 and came back
correctly. Everything beyond it did not. Measured on revoltlib the day it was
found: 35 titles, 9 text bodies and 1 description would restore as question
marks.

Both dump sites and the clone import now name `utf8mb4` -- `Backup.php`,
and `rebuildCloneFromSource()` and `importCloneFromSource()` in
`DomainInstaller.php`.

**Dumps taken before that date are cp1252 files with no `SET NAMES` in them.**
They must be restored the way they were written, with
`mysql --default-character-set=latin1`. Nothing in the filename tells you
which kind you are holding, so check the date.

## Know which of the two encodings you need

The engine has both halves of every encoding job, and they are not
interchangeable.

| Job | PHP | JavaScript |
|---|---|---|
| Whole URL | `rawurlencode()` | `fixedEncodeURI()` |
| One query value | `urlencode()` / `rawurlencode()` | `fixedEncodeURIComponent()` |
| Into HTML text | `htmlentities()` via `HTMLEntities` | — |
| Out of HTML | `html_entity_decode()` | — |
| Selective, mask-driven | `mb_encode_numericentity()` via `UTF8Characters` | — |

### PHP's URL encoders are safe. JavaScript's are not.

This asymmetry catches people, and it matters here because
`classes/API/SocialMedia.php` and `javascript/social-share-media.js` do the
*same job* on the *same sixteen parameters*.

`urlencode()` and `rawurlencode()` both percent-encode everything that is not
alphanumeric or `-_.` (`rawurlencode()` also spares `~`). That includes the RFC
3986 sub-delimiters `! ' ( ) *`. **PHP is fine.**

`encodeURIComponent()` and `encodeURI()` do **not** encode `! ' ( ) *`, and
`escape()` should never be used at all. Use the MDN replacements — see
[../Development/Conventions.md](../Development/Conventions.md) for the rule and
the function body, and [Triage.md](Triage.md) for where the codebase currently
breaks it.

The one difference to keep straight on the PHP side: `urlencode()` encodes a
space as `+`, `rawurlencode()` as `%20`. `+` is correct in an
`application/x-www-form-urlencoded` query string and **wrong in a path
segment**, where it is a literal plus. The existing 169 `urlencode()` calls are
query values, which is the case it is right for. Reach for `rawurlencode()` the
moment a value goes into a path.

## Encode at the boundary, in the direction of travel

Store content in its canonical form — UTF-8, unescaped — and encode on the way
out, for the grammar it is going into. Do not store pre-escaped content, because
the escape is only correct for one destination and this engine has sixteen.

`Format/BRF.php` is the clean example of the principle at its limit:

```php
	$braille_input = iconv('UTF-8', 'ASCII//TRANSLIT', $braille_input);
```

Braille has no representation for most of Unicode, so the conversion is
deliberately lossy and happens at the point of output, in the class that owns
that format. Nothing upstream is damaged by it.

See [Triage.md](Triage.md) — *Portable formats do not escape content for their
destination grammars* — for where this rule is currently not held.

## Convert by mask, not by blanket *(Charset subsystem)*

`CleanseInput_UTF8()` uses `mb_encode_numericentity()` with a hand-built bit
mask rather than a blanket `htmlentities()` call, so the conversion can be
selective about what it touches:

* Control characters `0x00`–`0x1F` — converted
* `"` `&` `<` `>` — converted, since these are markup
* `0x7F`–`0xBB` — **deliberately not converted**, so fractions and inverted
  question marks stay as characters
* `0xC0`–`0xFFFF` — converted

That third rule is the reason the mask exists. A blanket conversion would turn
readable punctuation into entity soup; this keeps the text legible and escapes
only what could be read as markup.

**Two things to know before editing the mask.** It stops at `0xFFFF`, so
anything above the Basic Multilingual Plane is outside every declared range —
the same boundary as the `utf8mb3` problem, and it will want revisiting at the
same time. And `CleanseInput_UTF8NoEntities_Formatting()` is the variant for
callers passing `convertentities => false`; a change to one usually wants the
same change to the other.

## Entity flags: `ENT_COMPAT | ENT_HTML401` *(Charset subsystem)*

```php
	public function CleanseInput_HTMLEntities_Flags() {
		return ENT_COMPAT | ENT_HTML401;
	}
```

`ENT_COMPAT` converts double quotes and **leaves single quotes alone**. That is
a real consequence, not a detail: a value escaped this way is safe inside
`attr="…"` and unsafe inside `attr='…'`. Use double quotes in generated
attributes, or pass `ENT_QUOTES` explicitly for that call site.

`ENT_HTML401` is the document type the flag set targets. If any output moves to
XML or XHTML grammar, it wants `ENT_XML1` or `ENT_XHTML` instead — the entity
sets genuinely differ, and `&apos;` is valid in XML but not in HTML 4.

## Multi-byte strings need the `mb_` functions

For UTF-8 content, the byte-oriented string functions cut characters in half.
The codebase already reaches for the multi-byte forms — 19 `mb_substr`, 12
`mb_convert_case`, 11 `mb_strtolower` — and new code should match:

| Instead of | Use |
|---|---|
| `substr()` | `mb_substr($s, 0, $n, 'UTF-8')` |
| `strlen()` | `mb_strlen()` |
| `strtolower()` / `ucfirst()` | `mb_strtolower()` / `mb_convert_case()` |
| `preg_replace()` on user text | `mb_ereg_replace()`, or add the `/u` modifier |
| `htmlspecialchars()` | `htmlspecialchars($s, ENT_QUOTES, 'UTF-8')` |

`mb_convert_encoding()` is also what makes `DOMDocument` behave —
`scripts/modify.php:2214` converts to `HTML-ENTITIES` before `loadHTML()`,
because `loadHTML()` otherwise assumes Latin-1 and mangles anything above
`0x7F`.

**`utf8_encode()` is not a multi-byte function.** It converts Latin-1 to UTF-8
and does nothing useful to text that is already UTF-8. It is deprecated as of
PHP 8.2. One call survives, at
`src/templates/default/spellchecker/display.php:374`.

## Regular expressions over text want Unicode classes

Use Unicode property escapes rather than ASCII ranges. `[^\p{L}]` strips
non-letters while keeping accented characters; `[a-zA-Z]` silently discards
half of every non-English language this engine serves. Add the `/u` modifier so
PCRE treats the subject as UTF-8.

For filtering invisible and invalid characters, the useful classes are
`\p{Cc}` (control), `\p{Cn}` (unassigned) and `\p{Cs}` (surrogate). PHP has no
`\P` negation inside a character class, so negate with `^`:

```php
	$text = preg_replace('/[^\PCc^\PCn^\PCs]/u', '', $text);
```

These classes are portable — the same expressions work in JavaScript, Perl,
Python and .NET, which matters in a codebase that generates content for several
of them.

## Binary is stored as bytes and read as hex

Security-bearing values are `binary(x)` columns, written with `UNHEX()` and read
with `HEX()`. `EscapeMySQL::GetRecordFullSelectStatement()` wraps `binary`
columns in `HEX()` automatically when it builds a select, so a new binary column
becomes readable without extra work. See [Database.md](Database.md).

Do not run binary values through any of the text encoders above. They are not
text, and a charset conversion will destroy them silently.

## Byte-order marks are a signature, not a character

A BOM (`EF BB BF`) is a hint about a stream's encoding, per RFC 3629. It is not
part of the data, and code that treats it as data is the bug. Strip it on input
rather than accommodating it downstream.

Worth knowing when diagnosing: the Linux `file` command has **no** BOM handling
at all, so it reports `charset=unknown` for perfectly good BOM-marked UTF-8. To
check a file by hand:

```bash
hexdump -n 3 -C <path>     # ef bb bf means BOM-marked UTF-8
```

## A file marked wrongly does not need converting

If a file's *data* is valid UTF-8 but the file is *marked* Latin-1, running a
conversion over it corrupts it — the bytes are already correct. What is wrong is
the label. Move the same bytes into a file already marked UTF-8; do not
transcode. `iconv -f utf8 -t utf8` will not do this, because it attempts a
conversion rather than a re-labelling.

The same distinction applies to the database: a `utf8mb3` table holding valid
data needs `CONVERT TO CHARACTER SET`, but a *connection* charset that disagrees
with the column charset needs the connection fixed, not the data rewritten.

## Where the encoding code lives

| | |
|---|---|
| `src/classes/Charset/UTF8Characters.php` | System charset, `mb_encode_numericentity` masks — *mid-decommissioning* |
| `src/classes/Charset/HTMLEntities.php` | `htmlentities()` wrapper and its flags — *mid-decommissioning* |
| `src/classes/Database/EscapeMySQL.php` | Query escaping, `HEX()` wrapping of binary columns |
| `src/classes/API/SocialMedia.php` | PHP share-URL construction, `urlencode()` |
| `var/www/html/javascript/social-share-media.js` | The JavaScript twin of the above |
| `src/classes/Format/` | Per-format output escaping, one class per grammar |
| `src/classes/Language/AmericanBritishSpellings.php` | Orthography conversion — a different kind of encoding, but the same discipline: convert at the boundary, store canonical |
| `cli/traits/Base64.php` | Base64 for the CLI tools |
