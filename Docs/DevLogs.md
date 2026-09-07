# Development Log

A day-by-day record of what changed in the engine and why, newest first.

Commit messages explain a single change to whoever is reading that diff. This
explains the day to whoever was not there: what was found, what it turned out
to cost, and what was decided. Where a number appears it was measured on the
live installation rather than estimated.

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
