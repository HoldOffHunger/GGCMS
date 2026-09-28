# Styling

How pages get their look today, where that is going, and the rules for moving
from one to the other. Status on 28 September 2026: **planned, nothing built**.
The first site to move has an approved mockup. That site's own design lives in
the private configuration repository, as all site-specific material does.

## Where it stands

Each page links one generated stylesheet, `/css/<script>/<action>.css`, for
example `/css/view/display.css`. No such file exists. The request reaches
`Format/CSS.php`, which always runs `scripts/style.php`. Because
`OneCSSFilePerPage()` returns 1, style.php reads a manifest,
`templates/<host or default>/<script>/<action>_css.php`. The manifest lists
atomic class "files" such as `width/width-90percent.css`, and style.php prints one rule for each through
`Display_Attributes_css_*()`, after a hard-coded prelude.

The markup carries those atomic classes directly (`horizontal-center
width-90percent border-2px background-color-gray13`). The shared modules in
`src/modules/html/` also carry **134 inline `style="…"` attributes across 27
files**. An inline style beats any stylesheet, so no stylesheet alone can
restyle a page.

Two other costs:

- The generated stylesheet took 0.6 to 1.4 seconds to render uncached, which is
  why the page cache now stores CSS as well (see `PageCache::CacheableFormats()`).
- Four jQuery UI stylesheets load on every page, from
  `ClientSideIncludes::DisplayDefaultIncludes()`, whether the page uses jQuery UI or not.

## Where it is going

**Static stylesheets, written by hand in modern CSS, built by a PHP command.**
No Sass, no Node, no framework: that is the project's rule, and it costs
nothing now. Custom properties, native nesting, `@layer`, `:has()`, container
queries, `clamp()` and `color-mix()` have all worked in every current browser
since 2023 or earlier. ("CSS4" is not a real version. This is what people
mean when they say it.)

### Layers

One stylesheet per site, in this cascade order:

```css
@layer reset, legacy, base, components, site;
```

| Layer | Holds | Lives in |
|---|---|---|
| `reset` | box-sizing, margins, media defaults | public repo |
| `legacy` | the frozen atomic classes, so unconverted markup still renders | public repo, generated once |
| `base` | tokens with neutral defaults, typography, links, forms, focus | public repo |
| `components` | one file per module, named after it: `entry-likes.css` styles `entry-likes.php` | public repo |
| `site` | the site's tokens, fonts, header art and any site-only components | private repo, `templates/<site>/theme.css` |

`legacy` sits low on purpose. While a page is half-converted, a component rule
beats a leftover `border-2px` without any `!important`.

### Tokens

Components never name a colour, a font or a size directly. They use custom
properties (`--paper`, `--ink`, `--accent`, `--serif`, `--sans`, the type scale
and the spacing scale). `base` gives every token a neutral default, so a site
with no theme still looks clean and consistent; a site's `theme.css` redefines
them. Dark mode redefines the same tokens under `prefers-color-scheme: dark`
and under `:root[data-theme="dark"]`, and every colour needs a value in both.

### Build

A command-line script, planned as `cli/scripts/internal/css/build_css.php`,
joins the layers for each site in order. It strips comments and writes
`/css/build/<host>.<hash>.css`, where the hash is taken from the content. It
also writes a manifest mapping each host to its file.
`ClientSideIncludes` reads the manifest and prints the one `<link>`.

- The hash is what makes Cloudflare's four-hour `max-age` harmless: a changed
  stylesheet has a new URL.
- The HTML page cache stores the `<link>`, so new CSS needs re-rendered pages.
  A deploy already flushes the page cache, so `deploy.sh` runs the build
  first.
- Never hand-edit anything under `/css/build/`. The build script is its only
  writer.
- The script fails if a component class name is also a legacy class name.

### Fonts

Self-hosted `woff2` under `/fonts/`, open-licence faces only, with
`font-display: swap` and a preload for the faces above the fold. **No calls to
Google Fonts or any other font host.** The readers of these sites have good
reason not to want their visits reported to a third party.

### Night reading

The page cache serves identical HTML to everyone, so a reader's theme choice
cannot come from the server, and a theme cookie must not be added to the
page-cache cookie list. Instead, a small inline script at the top of `<head>`
reads `localStorage` and sets `data-theme` before the first paint, and the
toggle writes it. This replaces `?invertedcolors=1` for the web page. The
inverted-colours format can stay as a format.

### Phones

At least a third of human visitors are on phones or tablets
(`human_stats.php --report=devices`). Every component is built
mobile-first and must work at 360 px with no sideways scroll. The separate
`?mobilefriendly=1` edition stays as a format, but the ordinary page no longer
needs it.

## Rules while converting

- **No inline `style` in a module or template.** Add a class, and put the rule
  in the module's component file.
- **No CSS in template files.** Site CSS goes in the site's `theme.css`.
- **Class names:** lowercase and hyphenated, component first: `record-card`,
  `record-card-title`. This matches the existing names (`list-item-row-text`).
  Never reuse a legacy class name; the build checks.
- **Module arguments stay `that` plus behaviour switches.** A visual variant a
  caller needs is a switch (`'variant'=>'compact'`), never a style value passed in.
- **Converting a module changes every site at once**, because the module and
  its component CSS are shared. That is the point, but check the page on a
  site with no theme as well as the one being themed.
- **Match the numbers to the data.** A count shown on a page comes from the
  record (`child_record_stats`, `getFormats()`), never from a literal.

## Order of work

1. **Pipeline with no visible change.** The build script, `legacy.css` generated from
   the union of every `_css.php` manifest, fonts, and the `<link>` switched to
   the built file. Prove it with the Fumiko crawl: the same pages, with only the
   stylesheet URL changed.
2. **Tokens and base, one site at a time.** A site opts in by having a
   `theme.css`. The others keep the legacy look until their turn.
3. **The reading page's modules.** `entry-header`, `breadcrumbs`, `auth`,
   `entry-likes`, `entry-share`, `alternateformats`, `entry-textbody`,
   `entry-navigation`, `entry-comments` and `entry-controls`.
4. **The homepage's modules.** `entry-index-header`, `record-totals` (the
   stat boxes), `entry-newest` and `entry-children-grandchildren`.
5. **Retire `style.php`** and the `_css.php` manifests once nothing links
   `/css/<script>/<action>.css`, and drop jQuery UI's CSS from pages that do
   not use it.

After each stage that reaches a live site, re-run `human_stats.php` for that
site against the baseline recorded before the work began.
