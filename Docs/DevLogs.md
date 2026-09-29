# Development Log

A day-by-day record of what changed in the engine and why, newest first.

Commit messages explain a single change to whoever is reading that diff. This
explains the day to whoever was not there: what was found, what it turned out
to cost, and what was decided. Where a number appears it was measured on the
live installation rather than estimated.

## 2026, September 29

- The page cache writes each page to a temporary file and renames it into
  place. When the write itself failed, the temporary file was left behind:
  45,105 of them had built up between 16 and 26 September, all but two
  empty, 32,123 of them RevoltLib's. A failed write now removes its file,
  as a failed rename already did, and the leftovers are gone.

- About pages fetch the site's figures, as the front page does, so the
  number boxes can appear there too. RevoltLib's is the first to show them.

- Every EarthFluent language page listed none of its lessons. A section
  with more than 400 children is shown by index(), which never counted its
  records, so a template reading those counts saw none and hid the list --
  along with the language's saying and instructions. index() now counts
  last, as display() does; Copyleft License's and WordWeight's section
  pages and the default index read the same counts and show theirs again.

- modify_entry.php, asked to create an entry, read back the parent's Edit
  form and posted it as a Save, so the new child carried off the parent's
  quote, description and picture. A Save now reads the Add form, empty but
  for its defaults, and an Update the Edit form. Saving no longer needs
  Imagick when there are no pictures, either: modify.php made an Imagick
  object on every save and never used it.

- The translation review tool skips a record whose kind is structural or
  whose proposal is a note in parentheses. Applied, the Spanish review would
  have put "(needs investigating, not translating)" on three pages as the
  word.

- EarthFluent's first wave of Spanish corrections is live: 176 words, from
  the turkey that was Turquía to the copy that was dupdo. It is announced
  on the site's new Updates page, and the front page carries a line about
  the latest update. Marking the review store shipped wrote bare line feeds
  into a file of carriage returns; it now keeps each line's own ending.

- EarthFluent's lessons, words, languages and every-lesson pages take the
  new look -- a postcard from where the language is spoken at the head of
  each, the words on cards -- and the lesson template's HTML goes from 4,489
  lines to about 480. Restyling them turned up that most of what a lesson
  does had quietly stopped working:

  - The Listen buttons chose a voice for the language and then spoke a
    fresh utterance with neither voice nor language, so every Spanish,
    Hindi or Korean word was read in the browser's English voice.
  - google-cse.js opened with google.load('search'), Google's retired Web
    Search API. It threw, the Programmable Search element below it never
    loaded, and so no lesson showed a word's pictures and no quiz had
    anything to ask about. Without the dead call both work again.
  - Pronunciation compared what recognition heard with the lesson word
    exactly, capitals and full stop included, so "Estupendo." never
    matched estupendo.
  - Record relations default siblings to off, and EarthFluent had no file
    turning them on: every lesson had lost its previous and next and its
    quizzes over several lessons. Once on, GetSiblings ordered the ten
    either side by ListTitleSortKey, which every lesson leaves empty, so
    "previous" named a lesson from elsewhere in the course; the title now
    breaks the tie.
  - A page with a title template -- EarthFluent's word pages, RevoltSource's
    quote pages -- had "1" on the end of its title, the value of the
    template's require printed after it.

- MasereelGroup's three largest scans, 11,000 pixels wide and 12.2 MB
  between them, had never been shrunk: shrink_images.php measured each
  file with identify, which decoded all 140 million pixels, met the host's
  ImageMagick limit and called them unreadable -- then reported that every
  image fitted. identify now reads only the header. They are 330 KB, the
  originals are on archive.org and in the image backup.

- MasereelGroup takes the new look, set as a woodcut: black ink on cream,
  one red, poster type. Its three text templates were copies of the old
  default page and are now the reading page, which gains a pictures_first
  switch so a book leads with its print. Building it turned up that every
  excerpt dropped its underscores, breaking the addresses of the scans on
  archive.org, and that the front page's figures read "0 days to read it
  all" and "about 0 printed pages" on an archive of 4,375 words.

## 2026, September 28

- The front page's numbers are drawn at random, four at a time, from every
  figure the record supports -- texts, words, formats, days to read it all,
  and the year the site opened, taken from the master record's own date.
  The page is served from the cache, so the choice is made in the browser
  for each visit rather than frozen at the last warm. Two figures were
  considered and left out: an account count (46 on RevoltLib, which reads
  as a quiet site rather than a trusted one) and pages served since 2016,
  which no surviving log can support. The reader beacon will support
  readers and pages read per month once it has a month of data.

- Tag pages, browse pages and a person's works list take the new look. Every
  reading page links to a dozen tags, so the tag page is where a reader goes
  next, and it was still the oldest page on the site: boxed headers, no site
  bar. It has a page head naming the tag and how many entries carry it, the
  word's dictionary definition on a catalogue card, and the entries as cards
  whose tags show how many share each. The pager printed a link to every page
  -- 190 of them for RevoltLib's anarchism collection -- and now shows the
  first and last pages and two either side of this one.

- The redesign's first stage. Every page now links one stylesheet built for
  its site from cascade layers -- reset, base, legacy, components, site --
  instead of the one style.php generated per page, and has a doctype: none
  ever had, so every browser drew every page in quirks mode. style.php's
  atomic classes are frozen into the legacy layer, so markup not yet
  converted looks as it did. The shared modules print semantic markup now:
  a site bar on every page, page heads, breadcrumbs, a reading sheet at a
  comfortable measure, the formats grouped by what they are for, catalogue
  records with a citation to copy, entry cards, a next-and-previous that
  asks to be read, a proper comment form. Night reading is a site's switch.
  The two typefaces are self-hosted, so no page reports a visit to a font
  host. The first site themed is described in the configuration repository.

- The old navigation's "last entry" was the farthest one back, not the one
  just before: the younger siblings run in reading order, and it took the
  first. Previous is now the last of them.

- The query recorder printed every query, with its backtrace, at the foot of
  every page an administrator loaded. It is behind `?showqueries=1` now.

- Nobody could sign in. Google is the only way in, and Google now refuses to
  start `platform.js`, the library every sign-in page loaded: its button drew,
  then failed with "idpiframe_initialization_failed". Revoltlib had no client
  configured at all. Sign-in moves to Google Identity Services, whose tokens
  the engine's own verifier already checked, so only the button changed, and
  revoltlib has a client of its own. Google's script now loads only on the
  sign-in and sign-out pages; on every page it would tell Google about every
  reader's visit.

- The scripts that write were read, most exposed first. Downvoting had been a
  500 for everyone, and an anonymous vote another; the vote actions now refuse
  without a user and say Success only when the database agrees. The war room's
  single-comment view printed a visitor's comment as HTML -- a script posted as
  a comment would have run in the administrator's session on moderation -- and
  its single-suggestion view never showed the suggestion at all. Both escape
  what a visitor wrote, and both show it.

- `transfer.php`, which moves an entry and everything beneath it, was open to
  any Google sign-in: it never said it was admin-only. What stopped it was a
  bug that also stopped every administrator -- each transfer died on its
  reservation backup before moving anything. It is admin-only first, then
  mended; it works for the first time, reserves the old path, and refuses a
  move that would hang a branch from its own descendant.

- Two more scripts were open to anyone signed in the way `transfer.php` was:
  `formmaker.php`, which was never finished, and `languageutils.php`, a tool
  for tidying the misspelling dictionaries. Both are admin-only now. A new lint
  test fails for any script that requires a login without being admin-only,
  unless it is named as a page meant for readers, with the reason -- today
  `user-panel`, `chapterify` and `modify`. A new script has to be decided,
  rather than open by default.

- A scanner found that `/.env.production%c0%ae/` was a 500 on earthfluent, and
  the same was true on every site of any path with bytes that are not UTF-8,
  or with an emoji anywhere in the path or query string. The tables are
  utf8mb3, and MySQL throws rather than compare or store what they cannot
  hold; the error recorder failed the same way, so nothing was ever recorded.
  Those paths are 404s now and the records are written, with U+FFFD where the
  unholdable characters were. The last of it -- an emoji in a parameter that
  is looked up -- waits on converting the tables to utf8mb4, which is measured
  in Triage and not yet decided.

- The emoji half followed: every query now runs through one place that
  catches MySQL's refusal, so a lookup for an emoji finds nothing and a save
  that holds one fails politely instead of taking the page down. Storing
  emoji at all still waits on the utf8mb4 conversion.

- The weekly human count had revoltlib up 300% and earthfluent up 1,663%.
  Nearly all of it was two new scripted-browser farms, one page per address
  on a 1920x1080 screen: Linux Chrome that presses a key first, and Windows
  Chrome in en-US claiming an Asian timezone, arriving from nowhere.
  `human_stats.php` leaves both out now. Revoltlib's real week is about 220
  people, up a fifth; earthfluent's remainder is the Windows farm again in
  Western timezones, which cannot be told from real readers view by view,
  so it stays counted.



- `modify.php` let any signed-in reader delete any entry on any site -- one
  GET did it -- because its Delete asked who may see an entry, and asked
  before loading it. Only an administrator, or a reader withdrawing their own
  still-unpublished submission, may delete now.

- A reader's suggested edit -- filed as an unpublished copy for an
  administrator -- was a 500 every time, and the crash turned out to be
  protecting the site: past it, the copy's save would have deleted the live
  entry's image files. It is filed cleanly now, and the original is left
  exactly as it was.




- `Handler.php` begins to split along its stages. Measured over the 405-page
  crawl, everything before rendering costs about 80 KB and 1 ms of a median
  7.8 ms request -- opcache holds the code -- so the split is for clarity, not
  memory. The first piece, `classes/Networking/Handler/HandlerRedirects.php`,
  takes the 26 redirect-and-repair functions, two fifths of the file and the
  part that keeps growing. It keeps no state: every property is still
  Handler's, reached through `$this->handler`. The crawl plus 44 deliberately
  mangled URLs came out the same, status, Location and body.

- The second piece, `HandlerScript`, works out which script, file, class,
  format and extension answer a request; the permalink redirects joined
  `HandlerRedirects`. Handler is down to about 1,240 lines from 2,552. The
  script stage runs on every request and had never been tested; it has four
  tests now.

- The last three stages followed: `HandlerFiles`, `HandlerEntryPath` and
  `HandlerContent`. Handler is about 770 lines -- its properties, the
  constructor that builds the world, `HandleRequest()` conducting the stages
  in order, and the end of the request -- with each stage beside it in
  `classes/Networking/Handler/`. Every move was made by the tokenizer and
  proved by the crawl, 44 mangled URLs, and images and files served from
  disk, all unchanged.

- A link pasted out of prose -- `revoltlib.com/people)` -- 404ed on the host,
  though the engine has repaired exactly that for years. nginx gives every
  dotless path a trailing slash first, so the engine saw `/people)/`; and a
  repair answered in place never went back through nginx for its slash. Both
  are handled, and every such form now reaches the page, canonical to it.

## 2026, September 27

- The hosts moved from PHP 8.1 to 8.5.11 at 20:03 UTC. 8.1 had come from
  Ubuntu's own archive since the release upgrade to 22.04 commented the PHP
  PPA out, so nothing newer had been on offer. The PPA is back for jammy and
  only the fourteen `php8.5-*` packages went in; 8.1 is still installed, and
  going back is two switches (Installation.md, "Changing PHP version").

- It was proved first on Fumiko, Ben's Windows desktop, now a WSL copy of
  production laid out by `bin/local_sync.sh` and loaded with the previous
  night's dumps of all nineteen databases. PHP 8.5 was built there from
  php.net's source, because the packaged builds will not execute under WSL 1.
  405 pages across the seventeen sites were fetched from 8.1 and from 8.5, with
  `ORDER BY RAND()` and `shuffle()` seeded so that two runs could be compared
  byte for byte. Before the day's changes, 8.5 put hundreds of `E_STRICT`
  deprecation notices into the top of every page: the error handler named the
  constant, and `AbstractGlobals.php` had display switched on for every
  request. After them, 8.5 matched 8.1 on every page but the two carrying
  copyleftlicense's archive statistics, whose "last updated" time had
  refreshed between runs; and on 8.1, all 405 matched the code as it stood
  that morning. Crawled again on the host after the switch,
  the same URLs gave 390 pages and 15 redirects, no 500s, and no fatal in any
  site's error table.

- Nearly a thousand properties, in 218 classes and traits, had only ever been
  created by assigning to them -- deprecated since 8.2, an Error in PHP 9. They
  are declared now, scripts opt in to the dynamic record names SimpleORM gives
  them, and a lint test fails on any new one. About 170 constructors stopped
  returning values (deprecated in 8.6), and the handful of calls 8.3 to 8.5
  deprecated were replaced.

- Google sign-in no longer carries Google's 2016 client library: 4,869 files,
  some of whose fallbacks call functions PHP 8 removed. The token is checked
  directly against Google's published certificates, and now must carry a
  verified email address, which the library never required.

- The morning's backup check had flagged five small sites' dumps as a quarter
  smaller than the night before. It was the redaction of the twenty-sixth: the
  error tables kept their rows but lost the request dumps, 80 to 98 per cent
  of their bytes. Every table's row count held or grew.

- PHPUnit moved from 10.5 to 13.3 now the hosts are past 8.4. The suite -- 167
  tests and the lint pair -- runs on Fumiko, never on a host.

- A pilot had local Ollama coder models, Qwen 2.5 at 7B and 14B, hunt bugs in
  the Math and Charset classes, each claim proved or thrown out by running
  the model's own PHPUnit test. Of 42 claims across four runs, one described a
  real bug: `IsThisNumberPrime()` called 0 and 1 prime. A read of the same
  code found five, fixed now: that one; `FindGreatestCommonDivisor()`, which
  answered 1 for gcd(6.0, 4) and recursed until PHP died on a negative; a
  random string asked for with no character set, a `ValueError`; and list
  limits given as strings, which were ignored. None was reachable from a page
  today. The tooling stays on Fumiko, outside the repository; the models are
  not worth scaling.

- Following `ConvertBase()`'s callers led to the sign-in cookie, already in
  Triage: its token was a bcrypt string read as hexadecimal, keeping only the
  hex-looking characters. A thousand of them measured 11 characters at the
  median and 4 at the fewest, all beginning `8i`. Tokens are now sixty
  characters from `random_int()`, from a generator that was already written
  and never switched on. Sessions from before the change are re-issued a new
  token on their next visit, or lapse after 160 idle hours.

- Triage's authentication entries were checked against the code. Three listed
  as open had been fixed weeks ago -- admin-only scripts open to any reader,
  sessions that never lapsed, and first-time Google sign-in -- and one marked
  repaired was still filed as open. They say so now, and PHPUnit covers
  authentication for the first time: every kind of page against every kind of
  visitor, and the 160-hour lapse, each failing on the code before its fix.
  Still open: the token printed into `usersessionid` fields, no limit on login
  attempts, and unsalted SHA-256 passwords.

- The first of those is closed. Seventeen files printed the session token into
  a hidden `usersessionid` field that nothing read; with the cookie `HttpOnly`,
  page markup was the one place an injected script could take a session from.
  A crawl of 405 pages before and after found 26 changed, by that field alone.
  Fumiko's crawl needs `Listen 8445` put back after every `local_sync.sh`,
  which copies production's `ports.conf` over it.

- Logins are limited. Three wrong passwords for an account from one address in
  a day hold that address back from that account; ten from anywhere lock the
  account; both lapse as the failures age out, and each limit crossed records
  an ISI. It needs a new `LoginAttempt` table on every site, made from
  `clonefrom`, before it is deployed. On Fumiko, through the real ORM, three
  wrong passwords were kept, the fourth was refused uncounted, and the one ISI
  carried the account and address with the password redacted.

- Passwords move from one pass of unsalted SHA-256 to `password_hash()`, in a
  new `User.PasswordHash` column that must be added on every site before
  deploying. Each account upgrades itself the next time its owner signs in.
  On the way, a worse hole: every account Google sign-in made had no username
  and the same password, SHA-256 of a seed the public repository prints, and a
  blank username was a valid login. It is refused now, and Google accounts get
  no password. No account on the current seed existed yet.

- The rendering bug hunt's own runner, `tests/regression/rendering-bug-hunt/run.php`,
  had 48 of its fixture runs failing under PHP 8.5 before any of today's work.
  None was an engine fault: the fixtures fail on any deprecation, and their
  stand-in classes created undeclared properties. All 509 cases pass now,
  under 8.5 and 8.1.

- A file-by-file bug hunt over the whole engine began, with its ledger in
  `Docs/BugHunt.md`. The first evening closed Triage's `ping.php` entry --
  it fetched `file://` and private addresses into the public document root --
  and its GET-over-POST precedence entry, and made the unused datetime escaper
  safe. `dbstatus::KillDisconnectedImages()` was left inert on purpose: fixing
  its path would arm an unreviewed file deleter.

- Every `.webp` and `.jfif` image on the sites -- 410 of them -- had been
  coming back as an empty page: the MIME table `Image.php` served from had
  never heard of either. It serves from its own list of image types now, by
  the path alone, and nothing that can carry a script. The files beside the
  images get a real Content-Type. Fumiko's databases were brought to
  production's schema; until then `users.php` exports 500ed there on the new
  `PasswordHash` column.

## 2026, September 26

- Every error and issue row on every site had been storing the request it came
  from whole: `print_r()` of `$_SERVER`, `$_POST` and `$_GET`, and for errors the
  entire handler. So a failed login kept its plaintext password, cookies and
  session ids went in with the server array, and every error on all seventeen
  sites carried the global password seed. Both loggers now write through one
  redaction trait -- sensitive keys masked at any depth and in any case, the
  server array cut to fifteen diagnostic fields, sensitive query values masked
  in every stored URL -- and the handler dump is replaced by a few chosen facts
  and the real stack trace, which the dump had been long enough to cut off.
  `scrub_server_errors.php` then cleared 21,551 old rows and masked 65 URLs;
  a recount found no password, cookie or seed left in either table. The
  nightly database backups still hold the old rows until they age out.

- revoltlib's `news.rss` and `news.atom` had never rendered. When every news
  entry already has its own images, one lookup sent MySQL an empty `IN()`, and
  the old front controller printed the exception inside a 200 -- so FeedBurner
  had been fetching a 170-byte error message for as long as anyone can tell.
  Making the front controller answer 500 is what exposed it; the fix was the
  same empty-list guard its neighbouring function already had. The feed is
  144 KB and a hundred items.

- `sitemap.json`, `.rss`, `.atom` and `.csv` answered 500 on every site, first
  for a bullet and a non-breaking space only HTML, TXT and braille defined, then
  for a text module that exists for html, txt and xml alone. There is no such
  sitemap to serve, so those formats are now not found.

- GPT's rendering bug hunt from 8 to 14 September was reviewed and landed with
  its 499 regression cases. Among its catches: login sessions never expired,
  because the expiry compared `LastAccess` with itself plus 160 hours. Two of
  its choices were changed. Feeds escaped stored text as it stood, but titles
  already carry references -- revoltlib's "Ain&#x27;t No PC Gonna Fix it Baby"
  -- so display text now decodes before it escapes, while URLs and serialised
  documents are only escaped. And a failed session lookup threw on every HTTPS
  request carrying the cookie; a reader now sees the page logged out and the
  failure is filed as an issue.

- earthfluent served 17% of its pages from cache against wordweight's recovery,
  and 89% of its renders carried a query string. Every format alternate had
  carried the whole query along, so a Japanese or mobile page advertised
  `view.xml?language=ja` and `view.xml?mobilefriendly=1`: 2,001 XML renders a
  day at over three seconds each. Alternates now drop parameters that only
  change presentation. robots.txt disallows those parameters, since its format
  rules end in `$` and any query slipped past them -- and it now allows
  `/sitemap.xml`, which its own `/*.xml$` rule had been refusing while the
  Sitemap line pointed crawlers at it.

- Each site's identity now names a `DefaultLanguage()` and the
  `SupportedLanguages()` it offers, English and all twelve by default. A request
  for the default language, or one the site does not offer, is the plain page,
  so a site that says `['en']` has one cacheable page per entry instead of
  thirteen.

- The public engine checkout had been carrying gitignored copies of every
  site's templates and configuration, and sessions edited them believing they
  were live. Nine fixes from 3 and 4 September were stranded there -- word games
  printing words raw into JavaScript, birth and death dates carried from one row
  into the next -- and were merged into the private repository before the
  copies were removed.

- wordweight's word pages each carried the same thousand random-word links,
  191 KB of a 222 KB page, and the 30 GB of cache they made filled the volume.
  The list is now `randomwords.json`, fetched by the page, and a word page is
  about 30 KB.

## 2026, September 7

- Every 500 in the day's log bar two came from one vulnerability scanner
  walking `/admin/phpinfo.php`, `/beta/phpinfo.php` and twenty-three siblings
  against wordweight.com. `display_wordweight()` reached for `$this->dictionary`
  and nothing on that path ever constructs it; the dictionary lives on the
  handler, as the tag lookup forty lines above and the earthfluent template
  both already had it. Twenty-five fatals in ten seconds, and the fix was a
  missing `handler->` and the guard its neighbour was already using.
  `Construct_Dictionaries()` is conditional, so absence is legitimate and the
  answer is a 404.

- The braille exporter merged each converted paragraph into its accumulated
  output with `array_merge` inside the paragraph loop, copying the whole array
  once per paragraph. On a book-length chapter that exhausted the 128 MB limit
  — the other two 500s. `convertSentence` beneath it was already appending
  correctly, so it was one line.

- Five image tools added under `scripts/internal/images/`. The images are the
  largest thing on this host and the only large thing with no second copy:
  `/srv/ggcms` is 6.0 GB, revoltlib's image directory is 5.3 GB of it, 957
  files hold 4.08 GB, and the database backups contain `Image` rows, which are
  filenames and dimensions and not one byte of any picture. On this day those
  files served 4.6 GB of traffic in thirteen hours on one vCPU.

- Two decisions inside the quality search were measured rather than assumed,
  and the first answer was wrong both times. PHASH looked like the obvious
  fidelity metric and is non-monotonic against a real scan — distance 0.03 at
  quality 95 but 4.36 at 75 — so a search would have accepted 60 and rejected
  90; it answers "is this the same picture", which is a different question.
  Then, when `compare` exhausted memory on a 36-megapixel file, comparing
  downscaled copies was the obvious fix and flattened quality 85 through 65
  into 1.8 dB of range, which cannot choose between them. A full-resolution
  1200px centre crop separates the same range by 3.4 dB, and taken in the read
  specification rather than as an operator it costs 48 MB of peak RSS where a
  full decode could not complete at all.

- ImageMagick's `policy.xml` caps area at 128 megapixels here, and two of
  masereelgroup's woodcut scans are 142 and 145. They come back instantly with
  "cache resources exhausted" and 11 MB of RSS, which is a refusal and not an
  exhaustion; a 109-megapixel scan clears that cap and then wants 870 MB
  decoded against a 256 MiB memory limit. The limits are what stop one
  `convert` taking a gigabyte on a box with two, so the tools report those
  files and leave them alone, and they name which limit was hit rather than
  reporting all four failure modes as one.

- The compressor refuses to run without a backup, refuses to change dimensions,
  and refuses to compress a file twice. The last is the one that would have
  gone unnoticed: a second pass sees a quality-80 file, searches below it and
  re-encodes, and JPEG generation loss is cumulative and invisible one step at
  a time. A ledger keyed by the hash of what was written makes a restored or
  edited file eligible again and an untouched one not.

- `ByteDisplay::formatBytes` exploded its number against its own width,
  `print_r`'d the pieces and returned the argument unchanged. So
  `check_free_space.php` has printed a raw byte count and a stray `Array` dump
  since it was written, which is a poor showing for the one tool meant to
  notice the disk filling.

- **Found and not yet fixed.** The command-line PHP is 8.1 and Apache's is 8.0,
  and they carry separate mysqli credentials.
  `/etc/php/8.1/mods-available/mysqli.ini` still names
  `db-mysql-nyc3-52995`, the cluster destroyed on 6 September, so every CLI
  tool that connects through mysqli rather than shelling out to the `mysql`
  client hangs until it times out — `check_schema.php` among them. The
  hostname still resolves, which is why it hangs rather than failing.
  `refresh_db_host.sh` reads the database host from that same CLI ini, so the
  hourly pin it maintains is for the destroyed cluster; `/etc/hosts` pins
  167.172.24.115 for it, and the live cluster is not pinned at all. The script
  is also not in root's crontab, so it has not run since 5 September.

- The command-line PHP is 8.1 and Apache's is 8.0, and they carry separate
  mysqli credentials in different files. `/etc/php/8.1/mods-available/mysqli.ini`
  still named the cluster destroyed on 6 September, whose hostname still
  resolves -- so every CLI tool connecting through mysqli hung until it timed
  out rather than failing, `check_schema.php` among them. Repointed; it now
  reports clean in seconds. Still outstanding: `/etc/hosts` pins the destroyed
  cluster and the live one is not pinned at all, and `refresh_db_host.sh` reads
  the host from that same CLI ini, so the pin it maintains is for a cluster
  that does not exist. It is also not in root's crontab.

- Fifty-one rows on revoltlib named their three files without the `<Entryid>-`
  prefix the files on disk carry. Every picture present, none of them
  reachable: the page builds the name from the row, and that URL returns 200
  with an empty `text/html` body, the same broken-image signature as the
  malformed `/image//` fallback. Repaired after checking that all three
  variants agreed and that the file's dimensions matched the row -- matching on
  a filename alone is a guess, matching on filename and dimensions is evidence.
  Missing files went from 171 to 18, the remainder being six rows whose files
  are genuinely gone.

- `Image::3` is a one-based index into the entry's own image list, not an Image
  id, and a reference past the end of that list is replaced with an empty
  string. Four such on revoltlib in fourteen hundred entries carrying the
  markup, none of them findable any other way.

- Of revoltlib's 1,680 files that no Image row names, 155 are the real pictures
  whose rows name them wrongly and 964 are icons and standards sharing their
  base's fate. Only 561 are genuinely unreferenced. The raw count invites
  exactly the wrong conclusion, which is why the orphan checker classifies and
  has no delete flag.

- The compression projection was wrong and reported 1.4 GB for revoltlib. It
  divided the saving by the bytes of the files that improved and applied that
  rate to everything, from a sample where seven of twelve had refused -- a
  number reached by pretending the sample was the five that worked. Corrected
  to divide by everything tested: 558 MB at the strict setting, 850 MB at
  40 dB, where eight of twelve improve.

- Moved the compression off the droplet. Sixteen hours of niced CPU there
  against ten minutes of transfer, measured: 10 MB/s down, 8.7 MB/s up. The
  desktop is a processor and not an authority, so the import re-checks a
  backup exists, the live file still matches its manifest hash, the returned
  file matches its recorded hash, the dimensions have not moved and the result
  is smaller. Both hash checks were tested by corrupting a batch on purpose.

- Two decisions inside the search were measured rather than assumed and both
  first answers were wrong -- PHASH is non-monotonic against a real scan, and
  downscaling for the comparison flattens quality 85 through 65 into 1.8 dB.
  A full-resolution centre crop separates the same range by 3.4 dB. Given
  cores, five windows are measured and the worst kept, because averaging would
  let a calm sky pay for a ruined face.

- **Not upgrading ImageMagick on the droplet.** The engine uses the imagick
  extension at `modify.php:2774` and `SimpleImages.php:79`, compiled against
  the exact library that would be replaced, and 20.04 offers no ImageMagick 7
  -- so it is a source build, after which the image-upload path is linked
  against a library that arrived an hour ago. It would also buy nothing: no
  metric ever runs server-side.

- A slice-replacement silently deleted a method from a trait and `php -l`
  reported no syntax errors, because a call to a missing method is a runtime
  error. The regression run caught it. A small static check now greps every
  `$this->method()` against the methods defined in the class and the traits it
  actually composes -- the first version of that check was handed every trait
  regardless, and duly resolved a call the class could not make.

- The braille fix from earlier in the day was half a fix, and the log said so
  within an hour of the deploy. Removing an `array_merge` from the paragraph
  loop took away the quadratic copying, which was real, but not the size: the
  failure moved from the merge to the append and went on asking for 16 MB at a
  time to double an array that should never have existed. `formattedOutput`
  built the whole document as one array per word, flattened it with
  `array_column` and imploded that — and `convertWord` returns
  `['text' => $word, 'braille' => ...]`, so every word carried its own source
  text beside its braille for `array_column` to throw away. PHP's per-array
  overhead dwarfs the few bytes of braille in each, and all three structures
  are alive at the peak. Accumulating into a string instead takes a 1.28 MB
  document — the size of *Recipes for Disaster* — from 256 MB peak to 34 MB,
  where the old code could not finish at all under the 128 MB the host gives
  it. Byte-identical output, same SHA-256 in all three modes at both sizes.

## 2026, September 4

- Every row-cache directory owned by root is silently unwritable, and nothing
  anywhere says so. Apache writes the cache as `www-data`; a directory created
  by a command-line script run as root cannot be written into, and the failure
  produces no error, no ticket and no log line — only a directory that never
  fills. revoltlib, the largest of the seventeen sites, had five of its seven
  cache types in that state and holding zero files, while the two that happened
  to be owned correctly held 6,147 and 5,954. Same site, same code, same
  request; ownership was the only difference. Repaired by handing the whole
  tree back to the web server. The deploy's own permissions step was checked
  afterwards and does not recreate the problem, so the culprit is any
  maintenance script run as root.

- The row cache stored one master record per site and wrote it only where the
  site's master entry happened to be numbered one. The cache is asked for it by
  the name `1`, meaning "the single master record of this domain" — but the
  write named the entry-id column as the key to file rows under, and those two
  only agree when the master entry is genuinely id 1. Twelve of the thirteen
  sites checked have exactly that by coincidence and cached correctly, which is
  what hid it. revoltlib's is id 3, and it had never once written the file,
  re-running the query on every render since the row cache was introduced.

- The approved-comment list had never been cached on any site at all, for the
  same reason pointing the other way: the write named no key column, so it fell
  back to filing rows under each comment's own id, while asking for them by the
  entry's id. A comment numbered 2 on entry 14 went into a drawer labelled 2,
  the drawer labelled 14 was never created, and nothing was written.

  That was one setting away from being much worse than a missing cache. A write
  that files nothing falls through to recording the entry as *blank* — known to
  have no rows — and a blank is a cache hit, not a miss. Comments would have
  stopped appearing rather than merely stopped caching. It escaped only because
  blank-lists are enabled per cache type and this type never had one turned on.

- The rule underneath both: a cache write lands only when the values of the key
  column and the values asked for are the same thing. Nothing reports a
  mismatch. The user-id cache, which asks for user ids and files by id, is the
  one that always worked — and having something correct to compare against is
  what made the other two legible.

- Invalidation on write now reaches the approved-comment list and the cached
  usernames, which it had never covered. Approving a comment is the case that
  needed thinking about: it updates the comment by its own id and names no
  entry, so nothing identifies which page just gained a visible comment. Where
  the write names no entry the whole type is dropped, on the same reasoning the
  entry branch already uses — over-deleting costs a render, under-deleting hides
  a moderator's decision indefinitely.

- One missing slash was costing the whole machine. Eight links in wordweight's
  index template were written relative — `href="Abject/view.php"`, no leading
  slash — and that template renders at every level where a word has children.
  So a crawler on `/Squamose/view.php` resolved the next word to
  `/Squamose/Gelada/view.php`, and onward without end: an infinite URL space
  generated by the site's own markup.

  Every step in that chain cost **twenty-eight seconds to answer 404**, measured
  at the backend with the rate limiter bypassed so the figure is pure render.
  Forty workers held for half a minute apiece is the entire explanation of that
  evening — load average seventeen, ssh timing out, DNS failing, three deploys
  refused, and twenty-five second replies on sites that had nothing to do with
  it. wordweight took 3,369 requests in an hour against revoltlib's 96, and it
  is the one site a page cache cannot help: its sitemap holds a single page.

  Fixed at the source with a leading slash, and at the front door with a depth
  rule, because the space already exists in Google's index and will be walked
  for months. Afterwards: load 1.86, wordweight's home page 0.124 seconds
  against 3.6 to 13.8 before.

- The access log was growing four gigabytes a day against 3.8 GB of free disk,
  which no rotation policy can survive — fourteen days of it would not fit on
  the volume twice over. Roughly half was redirects and another 45% not-founds
  from the crawl above. Silenced both at nginx and left everything that
  resolves to a page logged in full, which is the only part any question has
  ever been answered from. Measured after: 253 MB a day, a 94% reduction.

- The page cache is written pre-compressed and served that way. `gzip` was on
  and `gzip_static` was not, so nginx compressed every cached page on every
  request — twelve thousand files that never change, on one core, against a
  crawl. Pages compress to between 13% and 19% of themselves, so it is
  bandwidth as much as processor. Written in PageCache rather than only in the
  warmer, so a page the server caches for itself is identical to one built
  off-host.

  The hazard that creates is silent and worth knowing: nginx decides whether to
  serve a `.gz` by looking for the `.gz`, not at the page beside it, so a
  compressed copy outliving its page is served for ever. FlushPage removes it
  first and unconditionally.

- Pages can now be rendered somewhere other than the machine that serves them.
  A cache key is a pure function of host and request path, a cached page holds
  nothing naming the machine that built it, and the serving rule is a bare
  file-exists test — so a tree built anywhere is valid anywhere. revoltlib's
  12,215 pages took twelve minutes on a workstation; the same work on the
  server is hours it does not have, and a domain flush had no second half.

  A page built on Windows against a local copy of the database was verified
  byte-identical to what production serves for the same URL, and the site went
  from 1,972 cached pages to 12,162.

- The issue queue got a lister, filtered by type, and it immediately earned
  itself: the Windows-1252 braille fix from the day before had closed the plain
  path completely — zero faults since, confirmed against 300 local renders —
  while 158 faults had fired since deploy and **every one of them was
  `?mode=dotted`**. The characters still missing there are `@ $ = # _ ~ % < + >`,
  plain ASCII punctuation rather than anything to do with encoding. A separate
  fault that the first fix merely uncovered by clearing the noise in front of
  it.

- `IgnoreParents` was never an unfinished feature. It is in the canonical
  schema with its own index and in fifteen of seventeen live databases; only
  revoltlib and earthfluent lacked the column, which is drift rather than
  indecision, and both had been failing that query silently. The runtime option
  it belongs to is fully implemented and takes an entry code, excluding
  associated records whose parent, grandparent or great-grandparent carries it.

- A note for whoever reads the braille handler next: `BRF.php` passes
  `mode=dotted` and the library's own comment says its modes are `binary`,
  `dots` or `ascii`. It works today only because every comparison in that file
  tests for `binary` or `ascii`, so anything else lands in the dots branch by
  position. One `elseif` for `dots` added anywhere in that file and dotted
  braille stops working with no error to explain it.

## 2026, September 3

- Sixty-five per cent of the row cache was worthless. Of 156,671 files,
  101,649 contained the four characters `null` and nothing else — earthfluent
  74% of them, revoltlink 69%, revoltsource 60%. A cached read of many ids
  fails the moment one is missing, so a single `null` file turned an entire
  batch into a miss and sent the query to the database anyway; and because the
  writer declines to overwrite a file that already exists, the `null` was never
  replaced by anything better. Once written, that key could never hit again.

- Clearing a site's row cache left it permanently empty. The write created no
  directories and required them instead, so removing them made every subsequent
  write a silent no-op and every read a miss. The cache stayed switched on,
  reported nothing wrong, and served no purpose. revoltlib was found in exactly
  that state, emptied on 31 August and still empty two days and many thousands
  of renders later.

- The dictionary asked for one file and wrote another. All 113,609 definitions
  store their term in upper case and one lookup path uppercased to match, while
  the other lowercased — so on a case-sensitive filesystem it requested a name
  nothing writes and wrote a name nothing reads. The variable holding those
  words had been called `$lowercased_words` while holding uppercased ones for
  as long as anyone can tell; it is named for its contents now.

- Eight hundred and fifteen requests failed with "mysqli object is already
  closed" in thirty-two minutes, every one of them a 500 on a page that would
  otherwise have rendered. The guard that establishes the connection is open is
  not wrong so much as unable to be right: it answers a question about a link
  that a destructor elsewhere can shut between the answer and the use. The
  throw is now treated as the authoritative answer — do not use this, open a
  new one — once, and then give up, so a genuinely broken connection still
  fails rather than looping. The trace of whoever closed it goes to the error
  log instead of into the response, where it had been printing an internal
  stack trace in front of every visitor who tripped it.

- Two thousand four hundred and seven braille tickets came from about six
  characters, and this pipeline was manufacturing them. Numeric references in
  the Windows-1252 punctuation range cannot be decoded — they address a control
  block with nothing to decode to — so they survive as literal text, and the
  later substitution of the word "and" for every ampersand rewrote them into
  strings like `and#151;`, which reach the converter as eight characters, one
  of them a `#` with no braille glyph. They are translated now, before anything
  downstream can misread them.

- Braille output is cached, and a missing glyph is logged once per render
  rather than once per occurrence, with a cap on how many one render may file.

- A statement setting the session SQL mode was removed from the private
  configuration: the managed database already hands out precisely that mode,
  byte for byte, so the statement changed nothing and cost 9.22 ms of round
  trip to another host to do it. It was the most-executed application statement
  on the server.
