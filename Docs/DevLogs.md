# Development Log

A day-by-day record of what changed in the engine and why, newest first.

Commit messages explain a single change to whoever is reading that diff. This
explains the day to whoever was not there: what was found, what it turned out
to cost, and what was decided. Where a number appears it was measured on the
live installation rather than estimated.

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
