# Styling

How pages get their look, and the rules for changing it. The first site to be
redesigned is described, with its brief and its measurements, in the private
configuration repository's `Development/Redesign.md`; this file names no site.

## How a page is styled

Every page links one stylesheet, built for its site:
`/css/build/<site>.<hash>.css`. `ClientSideIncludes::BuiltStylesheet()` finds it
in `css/build/stylesheets.json`. A site with no build of its own gets
`default`. With no manifest at all, on a host where the builder has never run,
the page links style.php's generated stylesheet as it used to, so nothing ever
goes unstyled.

The build is five cascade layers, lowest first:

```css
@layer reset, base, legacy, components, site;
```

| Layer | Holds | Source |
|---|---|---|
| `reset` | box sizing, margins, media defaults | `src/css/reset.css` |
| `base` | tokens with neutral defaults, the plain elements | `src/css/tokens.css`, `base.css` |
| `legacy` | every atomic class any `_css.php` manifest names, frozen | `src/css/legacy.css`, generated |
| `components` | the modules' own styles | `src/css/components/*.css` |
| `site` | the site's theme | `templates/<site>/theme.css`, private repository |

`legacy` sits above `base` because an atomic class written on an element is a
deliberate instruction and should beat a plain element rule. It sits below
`components`, so a converted module's own rules win over any leftover
`border-2px` without `!important`.

Pages have a doctype now. Until September 2026 none did, so every browser
drew every page in quirks mode.

## Building

`cli/scripts/internal/style/build_stylesheets.php` (class `StylesheetBuilder`)
joins the layers per site, strips comments, and names each file by its
content's hash, so Cloudflare's four-hour `max-age` can never serve a stale
one. `deploy.sh` and `local_sync.sh` both run it. Old builds are kept for a
month, because a page in the page cache names the stylesheet it was rendered
with.

`legacy.css` is written once by `build_legacy_css.php`, which runs style.php's
own methods over the union of every manifest on every site. Run it again only
if a manifest gains a class. style.php itself can go once no cached page still
links `/css/<script>/<action>.css`.

Never hand-edit anything under `/css/build/`.

## Tokens and themes

Components name tokens, never values: `--ground`, `--paper`, `--ink`,
`--muted`, `--rule`, `--accent` and its variants, `--band` (the dark site bar,
masthead and footer), `--serif`, `--sans`, the type scale and `--gutter`. The
engine's defaults are a quiet, neutral library look. A site's `theme.css`
redefines them.

**Night reading** is a site's choice: `NightReading()` in its globals (default
off) draws the toggle, loads `javascript/night-reading.js` and prints the
first-paint script that applies a reader's saved choice. The engine defines no
dark palette. A theme that switches night reading on must define its dark
tokens under both `@media (prefers-color-scheme: dark)` (guarded by
`:root:not([data-theme="light"])`) and `:root[data-theme="dark"]`. On a page
still carrying the old grey boxes, a dark palette would put light text on light
backgrounds, which is why the default stays light.

`SiteSections()` in the globals names the site's sections for the site bar
and the masthead.

## Fonts

Archivo (a grotesque with a width axis) and Literata (made for reading on
screens), self-hosted in `/fonts/` under the SIL Open Font License, with the
licences beside them. They are split by Unicode range as Google Fonts serves
them. **No page calls Google Fonts or any other font host.**

## The modules and their markup

Modules print semantic markup with component classes: `.site-bar`,
`.page-head`, `.crumbs`, `.block` with `.block-title`, `.actions`, `.prose`,
`.entry-card`, `.author-card`, `.chips`, `.record` and so on. Every module
keeps its old public methods, so every template on every site still works;
only what they print changed. The ids scripts depend on are kept:
like-dislike.js's `thumbs-up-button-container`, `total-likes`,
`#google_token_id` and `#userid`; text-audio.js's `play-text-as-audio`,
`voice-selection`, `start-on` and `.text-to-play-as-audio`; the comment form's
ids; and the permalink's.

New modules compose pages rather than duplicate them:

| Module | Prints |
|---|---|
| `site-bar.php` | the dark bar on every page, once, from the page headers |
| `masthead.php` | a front page's head, from the master record |
| `reading-page.php` | a text on a paper sheet with a sidebar; a template's HTML branch becomes three lines |
| `entry-record.php` | an entry's catalogue record and its citation |
| `index-sections.php` | a collection index's random sections: pictures, tags, quotes, descriptions, texts, dates, likes |
| `browse-bar.php` | over a page of results: which of them are shown, and how many a page |
| `entry-list-navigation.php` | the pager: Previous, Next, the first and last pages and two either side of this one |
| `tag-header.php` | a tag's page head, and its dictionary definitions on catalogue cards |

`entry-child.php` extends `entry-child-legacy.php`, which is the module as it
was, kept so the conversion proofs in the private repository's
`Development/entrychild/` still compare byte for byte: they set
`module_entrychild::$legacy_markup`. Delete the legacy file when the last loop
is converted.

Components live in `src/css/components/`, one file per area of the page,
numbered so their order is explicit: `01-layout`, `02-elements`, `10-site-bar`,
`20-page-head`, `30-reading`, `40-entries`, `45-browse`, `50-keep-reading`,
`60-discussion`, `70-catalogue`, `80-home`.

## Rules

- **No inline `style` in a module.** Add a class and put the rule in a
  component file. The one exception is an element a script shows and hides,
  and even then prefer the `hidden` attribute.
- **No CSS in template files.** Site CSS goes in the site's `theme.css`.
- **Class names are lowercase and hyphenated, component first** (`record`,
  `entry-card-title`), and never an existing legacy class name.
- **Module arguments are `that` plus behaviour switches.** Headings a template
  words in its own voice are passed in; nothing derivable from `$this` is.
- **`ggreq()` is a plain `require`.** A template must `ggreq` a module before
  any module it calls has `require_once`d it, or the class is declared twice.
- **Legacy wrappers are styled with `:has()`,** as the row most templates
  print under the header is (`div:has(> .crumbs)`), rather than by editing
  every template.
- **Converting a module changes every site.** Look at a site with no theme as
  well as the one being themed; `local_sync.sh` and the local stack make that
  quick.
- **Counts come from the record** (`child_record_stats`, `getFormats()`), never
  from a literal.

## What is left

- The pasted sections and child loops still in other sites' templates, as
  `index-sections.php` and `entry-child.php` replace them.
- `module_header`, the generic header box many templates print directly.
- A theme for each other site, then night reading for each.
- Retiring style.php and the `_css.php` manifests.

After each stage that reaches a live site, re-run `human_stats.php` for that
site against the baseline recorded before the work began.
