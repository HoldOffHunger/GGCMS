# Database

The schema, the constraints it runs under, and what to do when adding to it.

The base schema for a new site is `usr/lib/ggcms/cli/sql/clonefrom.sql` — 32
tables, InnoDB. Every live site is a clone of it, and
`DomainChecker::checkClonefrom()` compares each host's field descriptions back
against the live `clonefrom` database, so drift between a site and the base
schema is detectable rather than theoretical.

## `clonefrom` is a live database, not just a file

There is a real `clonefrom` database on the host alongside the seventeen sites,
and it exists so that a missing table can be restored from it in one statement:

```sql
CREATE TABLE earthfluent.EntryCodeReservation LIKE clonefrom.EntryCodeReservation;
```

`LIKE` copies the column definitions and every index, and creates the table
empty. That is what you want for a table a site never had — no rows to
reconcile, and no chance of typing the schema out slightly differently from the
one `check_schema.php` will compare it against.

This is the repair for anything `check_schema.php` reports as *missing —
present in clonefrom*. Measured 1 September 2026, that was
`EntryCodeReservation` on earthfluent, `PrimaryHostRecord` on sortwords and
wordweight, and `Form` and `FormQuestion` across nine sites.

It is not the repair for the other two things that report catches. A column
order that disagrees with `clonefrom`, and a `child_types` flag that leaves rows
unfetched, are both judgements rather than omissions. See
[CommandLineTools.md](CommandLineTools.md).

For the tools that operate on databases — backup, purge, table sizes,
connection test, file cache — see
[CommandLineTools.md](CommandLineTools.md), which documents them all. This
document is about the schema itself.

## Every table has the same spine

```sql
CREATE TABLE `Entry` (
  `id` int NOT NULL AUTO_INCREMENT,
  ...
  `OriginalCreationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastModificationDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `Title` (`Title`),
  KEY `Code` (`Code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
```

Four things are invariant across all 32 tables, and new tables match them:

* **`id int NOT NULL AUTO_INCREMENT`, `PRIMARY KEY (id)`.** No exceptions, no
  composite primary keys, no natural keys. A relational row has its own unique
  key by definition, and the ORM assumes `id` everywhere.
* **`OriginalCreationDate` and `LastModificationDate`, both `datetime`,** as the
  last two columns. One field for date-and-time, never a date column plus a time
  column — a `datetime` indexes better for the range comparisons these actually
  get used for.
* **`NOT NULL DEFAULT` on effectively everything.** 12 nullable columns exist in
  the entire schema. The default is `''` for strings, `'0'` for flags, and
  `'0000-00-00 00:00:00'` for dates. This is deliberate and it interacts with
  the SQL mode below — read that before you add a nullable column.
* **A `KEY` on every column joined or filtered on,** which in practice means
  every `*id` foreign key and every column the ORM sorts by.

## The SQL mode is set per session, and it is load-bearing

`DBAccess::DBStart()` runs, when `SetSQLModePerSession()` is on:

```sql
SET SESSION sql_mode = 'ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'
```

That clears MySQL's defaults, and the two exclusions are annotated in the source
with the reasons:

* **Never enable `STRICT_TRANS_TABLES`.** `TEXT` columns cannot carry a
  `DEFAULT`, so under strict mode every insert touching a `TEXT` column fails.
  There are 12 such columns.
* **Never enable `ONLY_FULL_GROUP_BY`.** The ORM's generated `GROUP BY` clauses
  do not name every selected column.

Clearing the mode also drops `NO_ZERO_DATE` and `NO_ZERO_IN_DATE`, which is what
keeps the `'0000-00-00 00:00:00'` defaults legal. All three effects are load
bearing together: **the schema and the session mode are one design, and neither
half survives being changed alone.**

The fragility worth knowing: this is a *session* setting applied by application
code. A managed host that overrides `sql_mode` globally, or a code path that
opens a connection without going through `DBStart()`, gets MySQL's defaults and
starts failing inserts. On DigitalOcean the `cnf` files are emulated and do not
reach the database — the mode is set with `doctl databases sql-mode set <UUID> ""`,
and the UUID comes from `doctl databases list`.

## Types

What the schema uses now:

| | count | |
|---|---|---|
| `int` | 85 | ids, foreign keys, counts, anything summed |
| `datetime` | 68 | all dates |
| `varchar(255)` | 43 | the default string |
| `varchar(32)` | 10 | language codes |
| `tinyint(1)` | 10 | booleans |
| `text` | 10 | body content |
| `varchar(39)` | 2 | IP addresses — exactly the IPv6 maximum |
| `mediumtext` | 2 | |
| `binary(32)` | 1 | a token |

Guidance for new columns, consistent with the above:

* **Ids and anything you will `SUM()`, `MAX()` or `COUNT()`: `int`.** Aggregate
  functions do not work on `VARCHAR`, and an index over integers is the fastest
  thing MySQL does.
* **Booleans: `tinyint(1)`.** Note this is what `BOOLEAN` is an alias for, and
  that it really stores 0–255 — validate rather than trusting the type.
* **Anything security-bearing: `binary(x)`.** Read it with `SELECT HEX(field)`
  and match it with `WHERE field = UNHEX('...')`. `EscapeMySQL` already wraps
  `binary` columns in `HEX()` automatically when building a full select, so a
  new `binary` column becomes readable without further work.
* **Text over 1,000 characters: `text`; over 65,535: `mediumtext`; over
  16,777,215: `longtext`.** Remember the strict-mode constraint above — a `TEXT`
  column is why the session mode is what it is.
* **Names, if ever added: `varchar(255)`, called `GivenName` and `FamilyName`.**
  Not first/last. UTF-8 takes up to four bytes a character, and not every
  culture orders names the same way or splits them at all.
* **URLs: `varchar(2000)`.** Standards permit far longer; browsers do not.
* **Email addresses: `varchar(256)`** — the RFC 2821 / RFC 5321 path limit.
  Note that an address may legitimately contain more than one `@` (quoted
  strings under RFC 2822, backslash quoting under RFC 5321), so never validate
  on "exactly one at-sign".

String widths in the schema have drifted — `varchar(510)`, `512`, `1023`,
`1024`, `2048` all appear once or twice. Prefer `255` unless there is a reason,
and if there is a reason, prefer the next power of two.

**`int(11)` and `tinyint(1)`: the number in parentheses is display width, not
storage size.** It has no bearing on the range stored. `tinyint` is one byte
(−128…127 signed, 0…255 unsigned) regardless of what is in the brackets.

## Column order matters

Not stylistically — physically. Rows chain across memory blocks, and a column in
the second block costs an extra fetch that a column in the first does not. The
ordering that follows from that:

1. Primary key
2. Foreign keys
3. Frequently searched columns
4. Frequently updated columns
5. Nullable columns last, least-used of those last of all
6. Blobs in their own table with few other columns

The existing tables broadly follow this, with the two date columns as a
deliberate tail. `Comment` is the clearest exception — its `text` body sits
mid-table.

## Joining

**Choose by cardinality, not by taste.**

* **One-to-one → `JOIN`.** One query, one row, correct.
* **One-to-many → separate `SELECT`s, assembled in PHP.**

The reason is that joining across two one-to-many relations multiplies rows
rather than adding them. One entry with 2 tags and 2 comments returns 4 rows;
4 tags and 4 comments returns 16, not 8. Every field of the parent is duplicated
into every row. That costs memory on the database server, memory in the PHP that
de-duplicates it, and bandwidth between the two — and this engine runs against a
database in another region, where round trips are already the dominant cost. See
[PageCache.md](PageCache.md) for what that latency does at 1,700 queries a page.

The pattern is `SELECT ... WHERE Parentid IN (1,2,3)`, then group the results by
parent in code.

## Indexes cannot rescue a leading wildcard

`LIKE 'Patrick%'` uses an index. `LIKE '%consec%'` cannot, and adding one will
not help. If a query needs an infix match, the answer is a schema change — a
lookup table with an indexed type column — or `FULLTEXT`, not a new index on the
existing column.

Key lengths are counted in **bytes**, not characters: 1,536 for InnoDB on an
8 KB page, 768 on 4 KB. A multi-byte character eats several of those. Index a
prefix (`KEY sometextkey (SomeText(255))`) where a whole column will not fit.

## `SELECT *` is not used

`EscapeMySQL::GetRecordFullSelectStatement()` exists precisely to expand a
select into an explicit, aliased column list built from the record description —
partly so `binary` columns get their `HEX()` wrapper, partly because two joined
tables that both have `id` make `SELECT *` ambiguous and MySQL picks one. Name
the columns.

## Schema questions answer themselves

`INFORMATION_SCHEMA` holds collation, defaults, ordinal position and column
names, so most "what is this database actually doing?" questions are one query
rather than a guess:

```sql
SELECT COLLATION_NAME, COLUMN_DEFAULT, DATA_TYPE, ORDINAL_POSITION
FROM information_schema.columns
WHERE TABLE_SCHEMA = '<database>' AND TABLE_NAME = '<table>';
```

This is also how `DomainChecker` compares a host against `clonefrom`.

## Errors

`DBAccess::GetError()` and the `MySQLErrorCode` class exist to turn a bare error
number into something readable. Note the open issue in
[Triage.md](Triage.md) — *SQL execution failures are silently returned as
ordinary empty results* — which means a failed query and an empty table are
currently indistinguishable to calling code. Until that is fixed, check
`GetError()` explicitly when a query returning nothing would be surprising.
