# Rendering bug hunt

An incremental audit from rendered output inward. This is a coverage ledger,
not a claim that the system has been audited. Architecture.md defines layer
responsibilities; Triage.md holds the broader backlog.

## 8 September 2026: shared browse entry list

### Traced path and responsibilities

* `src/templates/default/view/browse.php` constructs the date, sort, list and
  navigation modules. It sorts `$this->children` and explicitly passes the result
  to `module_entrylist::Display()`.
* `src/templates/default/view/browseAssociated.php` uses the same sort/list
  modules, passing presentation switches such as `list_author` and `parents`.
* `src/modules/html/entry-sort.php` selects ListTitleSortKey, then ListTitle,
  then Title, and applies natural case-insensitive ordering.
* `src/modules/html/entry-list.php` prints each child and its association labels.
  DisplayChildren accepts either a direct entry or a record wrapping `entry`.
* `src/scripts/view.php::browse()` validates ORM context before loading children,
  association records, statistics and presentation information. Pagination is
  normalised in SetBrowseParameters_PageAndPerPage before navigation renders.
* `src/traits/scripts/SimpleORM.php::SetSimpleChildAssociationRecords()` enriches
  each association under `entry`, using ChosenEntryid and DBAccess::GetRecords;
  GetEntryParents delegates to the ORM class. Empty ChosenEntryid is explicitly
  skipped. Missing target rows still need separate investigation.
* `src/classes/Database/DBAccess.php::GetRecords()` is the record-access boundary;
  `src/classes/Database/ORM.php::GetEntryParents()` owns parent lookup logic.

Paths above are relative to `usr/lib/ggcms/`.

### Contracts and intentional oddities

Rendering prints directly. Do not replace these calls with string-returning
helpers without tracing the format boundary. Association Type `Role` produces
an author label; Type `Writing` produces a source-work label. The latter is
suppressed when list_author is enabled. A writing's optional Subtitle belongs
to that writing, independently of whether a Role record exists.

The existing module takes a `that` script reference and per-call options. This
is an older interface described in Architecture.md, not a new convention to
spread. Module retirement is a separate change; this repair retains the shared
interface. Parent-derived URLs and the hard-coded people/writings branches
remain unchanged pending route-specific evidence.

### Fixed locally: writing subtitle depends on unrelated role

Inside DisplayChildren's writings loop, the condition read
`$role['entry']['Subtitle']` while the body printed
`$writing['entry']['Subtitle']`. With no role, or a role without a subtitle,
valid writing subtitles disappeared. A role variable can also survive earlier
child iterations within the same invocation.

Changed only that condition to inspect the current writing. No data loading,
schema, stored text, URL or author-label behaviour changed.

Validation: local PHP 8.1.34 fixture required the real spacing/list modules and
captured Display output for four Role/Writing subtitle combinations: absent
role/present writing subtitle, empty role subtitle/present writing subtitle,
both present, and present role subtitle/empty writing subtitle. The first two
failed before the patch; all four passed afterwards. PHP lint passed. E_ALL
was enabled with warnings collected; other missing optional keys in the sparse
fixture still generated warnings. No full request, database, browser or
production validation was performed.

### Open observations and next evidence

* Display initialises child_id_hash to an empty array and immediately takes its
  keys; nothing populates it. The subsequent SQL/hydration branch is unreachable
  and violates the documented module boundary. Do not reactivate it: association
  enrichment already occurs upstream. Review its history before deleting it.
* Display selects a local fallback from `$this->that->children`, but passes the
  original args to DisplayChildren. Both inspected browse templates explicitly
  supply children, so this fallback defect was not demonstrated on their path.
  Inventory remaining callers before changing empty-list semantics.
* browse.php creates entrydate but does not pass it to entrylist; DisplayTimeFrame
  then reads time_info outside the conditional assignment. Establish whether
  child dates are wanted on these pages before wiring them in. No blanket
  optional-key cleanup was performed.
* entry-sort unwraps an entry for its title key but uses the outer record's id
  as the tie-breaker. Verify actual wrapper identities and duplicate-title
  fixtures before claiming rows are lost.
* Navigation preserves selected presentation options, but appends several raw
  values. Trace each parameter's validation and final HTML/URL encoding before
  making a security claim or applying a shared encoding change.

Next bounded pass: follow browseAssociated's wrapped records through sorting
and navigation, then compare the news display/docs callers already found by
search. Fan out through demonstrated shared contracts rather than directory
order. Existing export-format findings remain in Triage.md and were not
revalidated by this pass.

## 8 September 2026: associated browsing and pagination

### Verified shape and corrected audit assumptions

`view::browseAssociated()` calls SimpleORM::SetChildRecords(), which selects
ORM::GetRecordAssociated() for this action. That method runs the association
join and calls GetRecordTree_StructureChildRecords(), whose returned records
have the entry id at the top level. The suspected missing wrapper-id sort
collision is therefore not established on this path; no sorter change made.

The news templates require entry-list.php but do not call entrylist->Display.
Their presence in a require search did not establish a rendering dependency.
news/display.php actually calls module_entrychildren::Display_Entries with
newest_entries and OriginalCreationDate. Continue there for the news audit.

### Fixed locally: pagination query values were not encoded

browseAssociated.php passes Param values directly to entry-list-navigation.
The template's item_title whitelist is used only for its heading; the pagination
argument still receives the original parameter. base_format::Param delegates
to Query::Parameter and trims whitespace; Query performs UTF-8 cleansing.
Neither is the pagination URL-encoding boundary.

The module encoded stats_prefix but concatenated ignore_parent, parents,
item_title and list_author without URL encoding. Changed those four values to
urlencode(), matching the existing stats_prefix convention. A delimiter inside
a value must survive as data, not create another query parameter or fragment.
This change does not establish a policy for array-valued input or validate the
meaning of each option; those are separate contracts to trace.

Validation: local PHP 8.1.34 fixture invokes the real module with E_ALL and
warnings treated as exceptions. It extracts the generated next-page href,
parses its query and compares all five option values with their inputs, also
checking page=2 and headless=1. Cases cover an ordinary word, ampersand/hash,
spaces, a double quote and UTF-8 text. Delimiter and quote cases failed before;
all five passed after. PHP lint and CRLF-aware git diff --check passed.
No browser execution, full request or production exploitability claim made.

### Further observations to follow

* browseAssociated.php reads `$this->that->associated_record_stats` for its
  Last Updated tooltip, although it reads `$this->associated_record_stats`
  for the displayed count. The module-style `that` indirection is suspicious
  in template scope. Reproduce with the actual template execution context
  before fixing; do not infer that a failed date parse is a valid epoch date.
* news::docs() calls display() without checking its FALSE return before building
  feed data. Trace format dispatch and failed ORM validation before changing
  this failure contract.

## 8 September 2026: template scope and news action failure

The two observations at the end of the preceding pass are now fixed locally.

### Associated-browse tooltip uses the script's statistics

base_format::DisplayTemplates calls HandleRequires, which requires templates
inside the script object. Thus `$this` in browseAssociated.php is the script;
`that` is the reference used inside separately constructed modules. No `that`
assignment was found in scripts/ or traits/scripts/. The tooltip incorrectly
used `$this->that->associated_record_stats` while the neighbouring count already
used `$this->associated_record_stats`.

Removed the extra `that` from the tooltip's date lookup. This preserves the
existing date formatting and timezone behaviour. It does not define a new
policy for missing or invalid statistics dates.

Validation: executed the exact two date-conversion statements extracted from
the actual template, bound to a fixture script object with associated_record_stats.
E_ALL warnings were exceptions. Before the change it failed on undefined `that`;
afterwards the supplied 2026-09-08 10:11:12 value formatted as
September 08, 2026; 10:11:12. This was an isolated template-block test, not a
complete template render.

### News docs propagates failed display initialisation

news::display returns FALSE immediately when ValidateOrm fails. news::docs
previously ignored this and proceeded into getNewsFeeds, despite both
HTML::Display and AbstractBaseFormat::RunScript checking the action result
before continuing. Added the same early FALSE guard around docs' display call.
A failed action must not build downstream feed data or claim success.

Validation: called the real news::docs method with a minimal view base and
stubbed display/getNewsFeeds dependencies. The failure fixture throws if feed
construction is reached after display returns FALSE: it failed before and
passes afterwards with zero feed calls. The success fixture still returns TRUE,
builds one feed and produces https://example.test/news.rss. E_ALL warnings were
exceptions. Both edited PHP files lint successfully; CRLF-aware diff checking
passes. No database, full format-dispatch, browser or production test was run.

### Continuing coverage

News HTML display is served by entry-children.php, which delegates sorting to
entry-sort and reverses the order when datefield is present. Its newest-entry
loader and association enrichment remain to be audited beyond the source
orientation in this pass. Other representative execution paths (search,
editing/forms and format-specific presentation) remain outstanding; these local
repairs do not constitute whole-system coverage.

## 8 September 2026: news child renderer and empty association batches

### Fixed locally: second writing-subtitle copy

news/display.php calls entry-children::Display_Entries. That module contains
its own Role/Writing rendering loops, including the same wrong `$role` subtitle
condition previously fixed in entry-list. Corrected only the writing condition.
The real module and real sorter were exercised with a minimal script fixture:
absent role, empty role subtitle, populated role subtitle and empty writing
subtitle. The first two failed before; all four passed afterwards. Sparse
fixtures still produce unrelated optional-key warnings, which were collected;
this is not a warning-free whole-page test. PHP lint passed.

### Fixed locally: empty association hydration issues an empty IN query

news::display calls SimpleORM::GetChildRecords, which delegates to
ORM::GetRecordTree_GetEntryChildRecords, then calls
ORM::SetAssociationEntryParentRelatedRecords. Its fillAssociationEntries stage
collects ChosenEntryid values and previously issued `WHERE id IN()` even when
there were no associations in the batch. Added an early return of the original
entries when the collected id array is empty. No query is needed in this case.
The fix belongs in the existing ORM method rather than in the news template.

Validation: real ORM method with a recording database double, E_ALL converted
to exceptions. Empty batch, NULL associations and empty association arrays
previously made an unwanted database call; all now return byte-equivalent
serialisable entry data without a call. A populated batch still makes one
bound query with id 7 and attaches the returned entry. Four cases pass; PHP
lint and CRLF-aware diff checks pass. This verifies query construction and
in-memory behaviour, not MySQL execution. Missing targets and associations with
unset ChosenEntryid remain a separate contract; this guard does not change them.

### Legacy behaviour and outstanding evidence

* News SetNewestChildren still builds SQL in scripts/, contrary to the documented
  layer policy. Its root query joins parent/grandparent assignments and groups
  by their identities; category queries join direct children. Do not casually
  collapse duplicate placements or rewrite the count without real graph fixtures.
* entry-children passes child/short-dates to DisplayTimeFrame, which reads
  time_frame. A separate getTimeFrame helper exists, but no call was found in
  templates/ or that module. No date-display behaviour was restored speculatively.
* The shared sorter led to default search/display.php. search::display returns
  FALSE before initialisation or searching; git blame places this in the initial
  repository snapshot 6266aa9. The rationale was not found in Docs/Development.
  Leave it disabled pending intent evidence. The unreachable search body is not
  a currently exercised rendering path and was not repaired.
* Began the next representative mutation surface at modify/Save.php and Update.php.
  Both templates announce success in their headings; the script separately tracks
  acceptance, preparation, attempt results and save_status. Follow failure renders
  and those status fields before changing presentation. Existing aggregate-write
  transaction concerns are already recorded in Triage.md; no database mutation
  was executed in this pass.

## 8 September 2026: save/update result presentation

### Contract: an action can render successfully after a rejected write

modify::Save and Update return TRUE after validation, preparation or write
failure so their templates can display save_status and formatted errors. FALSE
is reserved on these paths for early context/access failures handled by format
dispatch. Do not replace the final TRUE with the write result: that would hide
the failure explanation behind the outer request-failure handling.

The separate saveaccepted, savepreparedresults and saveattemptresults fields
represent stages. Only saveattemptresults is set TRUE after the success path;
it may remain unset when an earlier stage fails. Both result templates already
use this field to decide whether to show success-only controls.

### Fixed locally: headings and breadcrumbs always announced success

Save.php unconditionally supplied Saved! and Saved Entry to its header and
breadcrumb modules; Update.php likewise supplied Updated! and Updated Entry.
That contradicted failure details printed below them. Both now retain their
existing success labels only when saveattemptresults is non-empty, otherwise
using neutral Save result / Update result headings and matching breadcrumbs.
The detailed save_status remains responsible for explaining the actual outcome.

Validation: execute the actual header/breadcrumb constructor statements extracted
from each template against recording module doubles. Test TRUE, FALSE and unset
saveattemptresults with E_ALL as exceptions. Four failure/unset cases failed
before; all six cases pass afterwards. Both PHP files lint and CRLF-aware diff
checking passes. This tests presentation arguments, not a complete template,
a database mutation or visual browser output.

This does not repair the existing aggregate-write/false-success risks in the
underlying persistence layer documented in Triage.md. The templates accurately
follow the script's reported result; they cannot independently prove a commit.

### Next trace

Continue from the editing form's field values and validation errors into
SimpleForms and shared form modules. Inspect format-specific presentation after
that. No save, update or delete request has been sent to a database during this
audit. All fixes remain local and uncommitted.

## 8 September 2026: edit field values and the shared form renderer

### Fixed locally: zero is content, not an empty value

modify/Edit.php passes stored Description and Text values directly into
module_form::DisplayFormField, alongside other text and hidden fields. The
module escapes ampersands, quotes and angle brackets before rendering, but
then tested the escaped string for truthiness in its textarea, button and
input branches. Both integer 0 and string '0' become the string '0', which PHP
considers false. Textareas and button captions therefore became empty; inputs
lost their value attribute.

Changed those three checks to compare explicitly against the empty string.
Existing escaping and empty-string output are preserved. No stored values,
form names, submission processing or validation rules changed.

Validation: actual module with all optional argument keys supplied, E_ALL
warnings treated as exceptions. Sixteen cases cover textarea, button, text and
hidden controls with string zero, integer zero, empty string and reserved HTML
characters. Eight zero cases failed before; all sixteen pass afterwards. PHP
lint and CRLF-aware diff checking pass. No browser submission or database write
was performed.

### Remaining form contracts

Names and presentation attributes are printed from caller-supplied args;
option labels are printed directly while option values are escaped. These need
caller-specific provenance checks before any encoding change. SimpleForms is
primarily naming/code generation rather than the field renderer; do not assume
its name establishes ownership of HTML controls. Continue through concrete
callers and then the alternate-format presentation path.

## 8 September 2026: advertised LaTeX export and cache output

entry-children advertises module_alternateformats; its getFormats method builds
script-name-based .tex links. TEX::Display runs the script, captures template
input, compares its source sidecar and regenerates when necessary, then serves
the cached file. The converter called StartDocument/EndDocument, which print
wrappers and return TRUE. Those booleans were concatenated around the body,
leaving 1<body>1 in the cache and additional printed wrappers on cold responses.

Fixed the two calls to use DocumentStartSyntax/DocumentEndSyntax, which return
strings. The shared print-based methods retain their existing contract. Updated
the existing Triage entry to distinguish the local repair from deployment.

Validation: fixture runs the real TEX Display and ConvertHTMLToFormat methods
and real shared wrapper helpers, with script/template input, metadata, headers
and paths supplied by a fixture subclass. Cache files are unique temporary
files under the task workspace. Before the fix, cold response, warm response
and cached bytes disagreed and the cache lacked its wrappers. Afterwards all
three are identical, start with documentclass, end with end{document}, and
retain the converted heading and bold body. E_ALL warnings were exceptions;
PHP lint and CRLF-aware diff checks pass. No LaTeX compilation, live HTTP or
production cache validation was performed. Escaping and concurrent publishing
remain separate known issues.

Anthropic will perform code review, per the user's direction. All changes
remain local and uncommitted for that review; no review was dispatched here.

Additional source observation: alternateformats' mobile-mode replacement of
its first item omits the type key consumed by Display and hard-codes view.php.
Trace non-view callers and intended icon selection before repairing that item.

## 8 September 2026: mobile alternate-format navigation

Verified callers include default privacy, terms, codeofconduct and users/exportuser
presentation. The constructor derives filename from handler->script_file, and
getFormats uses it for each advertised format link. However, mobile Display
replaced the first format record with one lacking type and hard-coded view.php.
The render loop consumes type for both its CSS class and icon filename.

Fixed by changing only the first record's text and URL fields, retaining its
existing mobile type. The standard URL now uses filename, consistent with the
other links. This preserves the existing mobile-icon.jpg asset (verified on
disk) and avoids sending privacy/terms/codeofconduct readers to view.php.

Validation: real module, audio disabled, recording warnings under E_ALL. Eight
cases cover view/privacy/terms/codeofconduct in standard and mobile modes,
checking the first href, mobile icon path and absence of warnings. All four
mobile cases failed before; all eight pass after. PHP lint and CRLF-aware diff
checks pass. No browser or full request validation was performed.

The module's general omission of action and query context remains unresolved:
users/exportuser is a concrete caller whose selected user/action may matter.
Do not copy all query parameters indiscriminately into export links; trace the
specific script's identity and access contracts first.

## 8 September 2026: user-export identity selection

users/exportuser.php constructs its profile breadcrumb with urlencode on Username,
or with userid for numeric lookup. Both users::viewuser and exportuser call
SetUser. Query receives PHP's decoded GET/POST values; base_format::Param
retrieves them and trims whitespace. SetUser then incorrectly called urldecode
again, turning literal plus signs into spaces and decoding literal percent
sequences a second time. Removed only that redundant decode, retaining trim
and the existing numeric-ID fallback (including its User #id display label).

Validation: real SetUser with fixture Param data produced by parse_str from
urlencode-generated links and a recording DBAccess double. Ordinary name,
plus sign, literal percent sequence, space and numeric fallback were checked.
Plus/percent cases failed before; all five pass afterwards. E_ALL warnings were
exceptions; PHP lint and CRLF-aware diff checks pass. This tests bound lookup
arguments, not database collation or complete requests.

The advertised alternate-format links still omit action=exportuser and the
selected user; users::display returns FALSE with a login redirect marker rather
than exporting. This confirms a routing-context defect, but generated-file
cache identity is separately known to omit selected user/action (Triage.md).
Do not simply enable working export links without addressing that cache
contract and reviewing the public-data policy. No access-policy change made:
viewuser/exportuser currently inherit public availability and select approved,
non-rejected comments on published entries; likes also join published entries.
The intended export/privacy scope and cache isolation need explicit review.

## 8 September 2026: exported comment/like rows and totals

### Verified wrapper identity; no sorter change

SetLimitedRecordEntries attaches each entry under the original comment/like
record's entry key and retains the outer record id. This is precisely the id
used by entry-sort's tie-breaker. Two comments on the same entry do not collide
merely because their entry titles match. A real-sorter fixture with E_ALL as
exceptions retained both comment bodies and both outer identities. This closes
the wrapper-id concern for this inspected caller; it is not proof for arbitrary
other wrapper producers.

SetLimitedRecordEntries and SetRecordEntries explicitly return [] for an empty
array of records. Their behaviour for database error arrays or NULL was not
validated here; do not infer successful empty results from a failed DB call.

### Open contract: totals cover more than displayed rows

users::SetUserComments selects approved, non-rejected comments joined to
Entry.Publish = 1. ORM::GetUserCommentsCount applies the moderation predicates
but has no Entry join/publication predicate. Similarly SetUserLikesDislikes
joins published entries, while GetUserLikesCount/GetUserDisLikesCount count
activity without that publication filter. The export subsequently removes
non-likes, and displays likes_count (despite the Likes/Dislikes label).

Source consequence: approved comments or likes attached to unpublished entries
can contribute to the displayed total while being absent from exported rows.
Whether the desired total is visible activity or all account activity needs a
caller-wide decision. No shared counter or publication policy changed. This
finding is source-grounded, without a database fixture or production counts.

The template's Disliked branch contains a discarded string literal, but the
export action filters those records out before rendering. It is not a reachable
missing-label fix on the inspected path; no change made simply to tidy dead code.

Continue with public profile pagination and its count/list contract before
reusing these counters elsewhere. The wider review remains incomplete.

## 8 September 2026: profile pagination preserves numeric identity

users::SetUser substitutes the display label User #id after successful numeric
lookup. The browseComments/browseLikes breadcrumbs already use userid when it
was supplied, but both page-link loops always sent user=Username. Following a
numeric profile's next-page link therefore attempted a username lookup for the
synthetic display label.

Fixed both loops to construct the same user-versus-userid query selection as
their existing breadcrumbs. Username values remain URL-encoded. The generated
page-link string is reused by upper and lower navigation, so both locations
receive the correction. Script action, page and perpage are preserved.

Validation: execute the actual pagination blocks extracted from both templates
with numeric and username contexts. Parse the first generated next-page URL and
check identity, action and page. Both numeric cases failed before; all four
pass after (username fixture includes a literal plus sign). E_ALL warnings were
exceptions; both templates lint; CRLF-aware diff check passes. No full template,
browser navigation or database lookup was performed in this fixture.

The count/publication mismatch from the preceding pass also feeds pagination:
SetBrowseParameters chooses comments_count or likes_count. This can advertise
pages beyond the published rows; the shared counting policy remains unresolved.
Requests supplying both user and userid follow existing breadcrumb precedence;
that ambiguous-input contract was not changed here.

## 8 September 2026: profile pagination arithmetic checked

Executed users::SetBrowseParameters and its three real calculation methods
with fixture counts/parameters and E_ALL warnings as exceptions. Seven cases
cover no records, the final partial page, page beyond the end, likes-only counts,
negative page/zero size, custom size and the 200-item size cap. All seven pass:
page selection, effective size, total pages and remaining-row arithmetic agree
with the existing normalisation rules. No arithmetic change made.

Empty results retain the legacy start=1/end=0 representation and total_pages=0;
it represents an empty interval. A presentation change to that wording is not
needed to repair the calculation. Out-of-range pages reset both page and size
to the established defaults. These tests initialise the unused count to zero;
they do not prove absence of legacy unset-property warnings in full requests.

### Coverage checkpoint for continued audit

Completed bounded traces so far: shared browse list, associated browsing,
news list/enrichment, save/update result presentation, shared form values,
alternate-format navigation, TEX cold/warm cache wrappers, user identity and
profile/export comment/like pagination. Local fixes and test scopes are recorded
in the sections above. Anthropic code review remains external to this work.

Still outstanding: representative site-specific template overrides, full
request/error dispatch, additional serializers beyond TEX, and further form
validation/persistence contracts. Known unresolved publication counts, user
export cache identity, missing related targets and legacy date wiring remain
listed above. No whole-system completion claim is supported by these tests.

## 8 September 2026: site-specific people and language overrides

HandleRequires selects host-specific childof templates from object_parent or
parent Code. revoltlib/view/display_childof_people.php explicitly provides
entrydate to module_entrylist and passes that list into entry-associated. This
is evidence that the optional date dependency is used; do not remove it merely
because default browse does not supply it. The people template also supplies
source_content for document formats separately from its normal HTML branch.

The associated module renders a small works list inline, or uses an iframe for
large lists/forced iframe mode. Its iframe producer had the same raw query-value
concatenation as the previously repaired pagination consumer. Applied urlencode
to ignore_parent, parents, item_title and list_author, matching stats_prefix.
Real-module fixtures force iframe mode and parse the resulting src query.
Ordinary values pass before/after; ampersand/hash and quote cases fail before
and pass after. Legacy optional-field warnings were suppressed in this sparse
fixture, so this is a focused output test. PHP lint and diff checks pass.
No actual iframe/browser or database validation performed.

Separately, anarchistcode/view/display.php requests language lookup lists and
falls back to master description/quote data. SimpleLookupLists::getListAndItems
unconditionally returns [] before its legacy DB logic; blame places the early
return in initial snapshot 6266aa9. Preserve that disabled path pending intent
rather than claiming translation lookup currently queries the database. Header
sub_text/sub_title locals are also unassigned in that template; no speculative
replacement text was introduced.

## 8 September 2026: Atom respects failed script actions

Following failed-action handling from HTML and AbstractBaseFormat into the feed
handlers confirmed the existing triage finding: ATOM::Display ignored RunScript's
FALSE result. It proceeded to filename selection, headers and conversion. RSS
already checks that result. Added the same early FALSE return to Atom; separator
initialisation and successful conversion remain unchanged.

Validation uses actual ATOM::Display and AbstractBaseFormat::RunScript with a
fixture script returning TRUE/FALSE. Filename, header and conversion calls are
recorded by doubles. Before the patch the failed action still reached all three
and returned success; after it reaches none, emits no output and returns FALSE.
Success still sets document attributes once and emits the fixture feed once.
E_ALL warnings were exceptions; both cases pass, PHP lint and diff checks pass.
This is dispatch validation, not XML validation or a live HTTP response test.
The existing triage section now marks only this subfinding repaired locally;
feed metadata and escaping findings remain open.

## 8 September 2026: feed root subtitle mapping

ATOM and RSS root-title builders used SubTitle, but the ORM's structured entry
records and both feed item builders use Subtitle. Corrected the two accesses
in each root builder. No XML escaping or other metadata behaviour changed.

Validation executes the actual extracted title-building block from each class
with E_ALL warnings as exceptions. Present and empty Subtitle cases all failed
before on the unavailable SubTitle key; all four now pass and produce Title:
Detail or Title respectively. Both files lint and diff checking passes. This
is a field-contract test, not complete feed/XML validation. Updated the existing
triage note to mark this subfinding locally repaired; other feed defects remain.

Following the same root builder also exposed an RSS self-link concern: it
appends news.rss to the page URL, which can already contain view.php?action=index.
Trace root/nested feed URL construction before changing that separate contract.

## 10 September 2026: nested RSS self-link

RSS::ConvertHTMLToFormat appended news.rss to the HTML channel URL. For a
nested record chain this produced books/view.php?action=indexnews.rss rather
than books/news.rss. news::docs separately builds its feed URL from the entry
path and its HTML link from that path plus view.php?action=index, confirming
the distinction. The RSS self-link now uses base_url. The nested HTML URL
assignment also uses = instead of .= because the local url was uninitialised.
The channel HTML link and existing humanreadable self-link behaviour are retained.

Validation: local PHP 8.1.34 fixture calls the real ConvertHTMLToFormat method
with doubles for template execution, image/content rendering and last-build date.
Six cases cover root, one-level and two-level paths in both humanreadable modes,
checking self-link, unchanged channel HTML link and collected E_ALL warnings.
Before: root cases passed; four nested cases failed with malformed URLs and an
uninitialised-variable warning. After: all six pass with zero warnings. PHP lint
and CRLF-aware git diff --check pass. This is isolated feed-header generation,
not complete XML, database, browser or live HTTP validation. Existing local
subtitle changes were preserved; nothing was committed, pushed or deployed.

Further coverage: RSS self-links still hard-code news.rss for non-news scripts;
trace those callers and their action/query identity before generalising the URL.
Other feed metadata and XML-escaping findings in Triage.md remain outstanding.

## 10 September 2026: OPDS image links are closed

The OPDS image loop opened the original-image link and then the thumbnail link
without closing either. Image-bearing entries therefore produced malformed XML.
Both links now self-close; no image URL or MIME selection behaviour was changed.

Validation: PHP 8.1.34 fixture executes the image-rendering block extracted from
the current OPDS.php, with a domain double and E_ALL warnings as exceptions.
DOMDocument parses the resulting Atom entry and XPath checks that each image
produces two direct sibling links. NULL and empty image lists pass before and
after; one-image and two-image cases fail XML parsing before and pass after.
All four cases now pass. PHP lint and CRLF-aware diff checks pass. This does
not validate the complete feed, OPDS semantic conformance or live HTTP output.
The thumbnail MIME lookup still uses the original filename extension; that is
the next separate, source-confirmed issue to reproduce with differing formats.

## 10 September 2026: OPDS thumbnail MIME selection

The image loop split IconFileName but selected the extension from FileName's
pieces. It now selects from image_icon_extension_pieces for the thumbnail.
Original-image MIME selection and both image URLs are unchanged.

Extended the preceding extracted-block fixture to inspect both link type
attributes against their respective filenames. Mixed PNG-original/JPEG-thumbnail
and JPEG-original/PNG-thumbnail rows failed before and pass after. NULL and
empty lists remain successful. All four fixture cases pass under PHP 8.1.34
with E_ALL warnings as exceptions; DOM XML/sibling checks, PHP lint and
CRLF-aware diff checking pass. MIME mapping was checked against MIMEType.php.
This does not validate remote image bytes or complete feed conformance.

Next serializer trace: RDF.php has multiple-colon child element names and
copies Description into its Quote field. Repairing its XML/RDF representation
requires a coherent namespace/resource contract, not just renaming tags until
an XML parser accepts them. Recheck existing RDF guidance and consumers first.

## 10 September 2026: RDF quote and link field mappings

clonefrom.sql defines Quote.Quote and Link.Language. RDF instead read
quote.Description and link.URL for those two outputs. Corrected the two reads.
A fixture executes each exact output statement extracted from RDF.php with
populated and empty intended values plus distinct wrong-field sentinel values.
All four cases fail before and pass after under PHP 8.1.34 with E_ALL warnings
as exceptions. PHP lint and CRLF-aware diff checks pass. This verifies field
selection only: existing invalid XML qualified names remain in the output.

RDF's structural repair is still outstanding. The repository guidance calls
for a coherent vocabulary, nested resources and XML plus RDF parser validation.
The inspected format registry advertises RDF, but the focused docs/tests/format
base search found no consumer contract establishing replacement property URIs.
Do not describe these mapping fixes as restoring usable RDF serialization.

## 10 September 2026: portable-format failed-action coverage

Executed the real Display methods and real AbstractBaseFormat::RunScript for
ATOM, RSS, XML, TXT, TEX, OPDS, RDF, CSV, BRF, JSON, PDF, RTF, SGML, EPub and
DAISY. Constructors were bypassed; a script double returns FALSE and throws if
document attributes or templates are reached. All 15 cases return FALSE, emit
no output and call the action once with PHP 8.1.34 E_ALL warnings as exceptions.
RSS uses a valid version parameter; humanreadable is disabled in this fixture.
No change was necessary. Handler's format dispatch returns Display's response
at lines 2157-2158 in the inspected source.

This is failed-action dispatch coverage only. It does not exercise constructor
failures, authentication, headers over HTTP, successful conversion, HTML host
overrides, CSS's direct-output path, image routing, or final 404 rendering.
Those paths remain distinct follow-up work; absence of a failure in this matrix
does not establish whole-request correctness. The standalone diagnostic is in
the session temporary directory as ggcms-format-failure-20260910.php.

## 10 September 2026: HTML dispatch and form-delete orientation

Real HTML::Display passes four fixture cases: default versus host override,
each returning TRUE/FALSE. The override suppresses the default call; FALSE
stops before metadata, StartHTML, templates and FinishHTML. TRUE reaches those
steps. Rendering endpoints are doubles, constructors are bypassed, E_ALL warnings
are exceptions under PHP 8.1.34. No change was required. Handler source shows
FALSE content results falling through its reserved/matching/script/misplaced
redirect chain to 404; this final HTTP path was not executed in the fixture.

Form persistence trace: Save validates and prepares all families before invoking
the aggregate writer, whose independent writes continue after failures. Delete
calls DeleteChildRecordsForUpdate, then ignores DeleteEntry and DeleteAssignment
results before claiming success. In the current source, child cleanup has die
calls on false ORM DeleteChildRecords returns (lines 1032-1043); this is an
important qualification to the older triage description of that wrapper.
Next inspect ORM/DBAccess return shapes and template expectations before changing
failure reporting. Transaction/file coordination remains unresolved; no database
mutation or deletion was performed during this audit.

## 10 September 2026: false SQL execution returns an error

FillArraysFromDB ignored execute() returning FALSE and then returned [] when
get_result had no result. Successful DELETE also returns [], so checking that
result for truthiness in modify would incorrectly reject successful deletion.
Added an execution-result check using the existing GetError array convention
with specifictype Execute and a source line, matching prepare/bind failures.

Fixture calls the real DBAccess method and GetError with connection/statement
and row-format doubles. FALSE execution failed before and passes after with an
Execute error and zero get_result calls. Successful write, empty SELECT and
one-row SELECT keep their existing return shapes. All four cases pass under
PHP 8.1.34 with E_ALL warnings as exceptions; PHP lint passes. No MySQL server
or production mutation was used. Throwing mysqli execution is not caught here;
callers ignoring error arrays and transaction/file rollback remain unresolved.
This is foundational error visibility, not a complete delete-success repair.

## 10 September 2026: delete checks entry and assignment results

Delete now checks the existing database error array's line field after entry
and assignment deletion. An entry error stops before assignment deletion and
sets a failure status acknowledging possible prior child deletion. An assignment
error sets an incomplete status. Both clear saveattemptresults and return TRUE
so the action remains handled rather than causing the generic 404 fallback.
Successful empty-array mutation results retain the previous success path.

Fixture executes the actual extracted Delete method with dependency doubles.
Success passed before and after; injected entry/assignment error arrays failed
before and now pass, including call-order and success-flag checks. All three
pass with PHP 8.1.34 E_ALL warnings as exceptions. PHP lint and CRLF-aware diff
checks pass. No records were deleted and no full response/template was rendered.
This handles recognised returned errors, not exceptions, false returns outside
the current DB contract, child cleanup die exits or relational/file rollback.

## 10 September 2026: child cleanup returns failure to the form

Replaced both DeleteChildRecordsForUpdate die exits with FALSE. Update already
handles this return by clearing saveattemptresults, setting a failure status and
formatting errors. Delete now explicitly clears the success flag and reports
possible partial child deletion when cleanup fails, without deleting the entry
or assignment afterwards. This preserves the existing stop-at-failure order.

Before: subprocess fixtures for kept-record and empty-record branches terminated
with Record delete error rather than returning to the caller. After: all four
success/failure cases return expected booleans and query counts, preserving IDs
to keep and skipping later families on failure. Four Delete action cases cover
success plus child/entry/assignment failures. A ninth case executes the extracted
Update method with failed cleanup and verifies its handled failure result and
FormatErrors call without success-link/cache-report work. All pass under PHP
8.1.34 with E_ALL warnings as exceptions; lint and diff checks pass. Dependencies
are doubles; no database records or image files were deleted and no full page
was rendered. Earlier mutations still cannot be rolled back by these changes.

## 10 September 2026: ORM child-delete success shape

Both DeleteChildRecords checks directly read line from FillArraysFromDB's
successful empty array. Changed them to !empty checks, retaining the existing
error discriminator and query order. This avoids undefined-key warnings on
success and lets the second association query be reached under strict diagnostics.

Real ORM method fixture with a recording database double covers Tag success
and failure, Association success and failure at either of its two queries.
Three cases failed before on undefined line; all five now pass with E_ALL
warnings as exceptions. Return values and short-circuit query counts are checked.
PHP 8.1.34 lint and CRLF-aware diff checks pass. SQL execution, file operations
and transaction rollback are not exercised by this fixture. Existing ORM
association-hydration changes were preserved.

## 10 September 2026: RSS and Atom omit absent images

Both renderImage methods dereferenced entry.image[0] unconditionally. Added an
early empty-string return when no first image record exists, so entries without
images do not produce fabricated image/logo/icon URLs. Existing populated-image
rendering remains unchanged.

Real-method fixtures for RSS and Atom cover NULL, empty array and one image.
Four absent-image cases failed before with offset warnings and now return no
metadata; populated cases retain the expected thumbnail URL and parse as XML
fragments. All six pass with PHP 8.1.34 E_ALL warnings as exceptions. Both files
lint and CRLF-aware diff checking passes. Domain is a double; full feeds, remote
image availability, image dimensions and reserved-character encoding are not
validated by these cases. General feed escaping remains open.

## 10 September 2026: empty-feed build date fallback

RSS and Atom compared entry_date even when getEntries returned no children.
Initialise that local from the parent's LastModificationDate before the existing
child comparison. Empty feeds now use the documented-by-code final fallback
without an undefined-variable warning. The existing first-child creation-date
comparison is retained; this does not establish a new aggregate freshness policy.

Eight real-method fixtures cover NULL/empty collections and older/newer first
child dates for both formats, using the real getEntries and initialised optional
properties. Four empty cases failed before; all eight pass afterwards with
PHP 8.1.34 E_ALL warnings as exceptions. Both files lint and CRLF-aware diff
checks pass. Invalid dates, child ordering and newest child modification dates
are outside this bounded check. No live HTTP feed was requested.

## 10 September 2026: reproducible regression handoff

Preserved the 12 session diagnostics under tests/regression/rendering-bug-hunt
(relative to usr/lib/ggcms). Replaced absolute checkout paths with paths derived
from the fixture directory. run.php executes isolated processes and checks exit
status, stderr and expected PASS counts, including detecting a premature die
that exits with code zero. The checkout-based run passes 65 cases under PHP
8.1.34. No hard-coded E:/, user-profile or TEMP references remain in that folder.

Run from the repository root:
php usr/lib/ggcms/tests/regression/rendering-bug-hunt/run.php

The accompanying README states dependencies and coverage limits. These are the
10 September fixtures, not a reconstruction of all 8 September tests and not a
complete system test. Earlier temporary copies can be ignored for review; the
repository copies are now the reproducible diagnostics for these repairs.

## 10 September 2026: link validation counts bytes as characters

ValidateRecordForSaving_Link uses strlen for its 255-character Title and URL
limits. clonefrom.sql declares both fields varchar(255), and encoding guidance
specifies UTF-8. An extracted real-validator fixture checks both fields at
255/256 ASCII and accented characters. ASCII boundaries behave as expected;
255 copies of U+00E9 (510 UTF-8 bytes) are rejected in both fields despite fitting
the declared character limit. All over-limit cases are rejected. This is an
input-validation mismatch, not evidence of a live database insertion failure.

No replacement was made yet. EncodingConventions.md requires the shared charset
accessor rather than a hard-coded encoding at call sites; focused searches found
no mb_strlen or obvious GetCharset/SystemCharset accessor in src. Resolve that
current encoding contract before introducing character counting. URL syntax and
percent-encoded transport length are separate questions; the title case alone
establishes rejection of valid multilingual text. The temporary diagnostic is
ggcms-link-length-20260910.php and intentionally reports two MISMATCH results;
it is not included in the passing regression suite.

## 10 September 2026: link character limits repaired

Located the existing UTF8Characters::SystemCharSet accessor, used by input
cleansing through handler.cleanser. Link validation now calls mb_strlen with
that charset for Title and URL. Both retain the 255-character limit; ASCII
behaviour is unchanged. This fixes the preceding multilingual boundary finding.

Preserved link-length.php in the regression folder and added its eight cases to
run.php. The two 255-accented-character mismatches now pass, as do ASCII and
over-limit cases. The complete saved suite passes 73 cases under PHP 8.1.34;
modify.php lint and diff checking pass. No live database insertion was tested.
Other validators still use byte lengths and need their own schema/contract audit;
this change does not relax URL syntax or change percent-encoding semantics.

## 10 September 2026: EventDate character limits and label checks

EventDate Title and Description are varchar(255), but their validator counted
bytes. Both now use mb_strlen with the existing SystemCharSet accessor. The
birthday/deathday rejection maps now use !empty membership checks so ordinary
labels do not read absent keys. The eight prohibited spellings are unchanged.

Nineteen extracted-validator cases cover both field boundaries with ASCII and
U+00E9, all eight rejected spellings and three accepted labels. Before: both
255-accented-character values were rejected and every case emitted one or two
undefined-key warnings. After: all 19 pass, including error-list expectations
and zero collected E_ALL warnings. Added the fixture to the saved runner; all
92 cases pass with PHP 8.1.34, plus modify.php lint and diff checks. No database
write was performed. Calendar/date-time validity and other field validators
remain outside these character-limit and label checks.

## 11 September 2026: optional EventDate preparation contract

Executed the real extracted PrepareRecordForSaving_EventDate with empty,
all-blank, year-only and BCE input. Empty collection returns [] (falsey to the
aggregate preparation wrapper). An all-blank row returns truthy but retains
EventDate/EventTime rather than producing stored EventDateTime. Year-only 1900
becomes 1900-00-00 00:00:00 with default Publication; -44-03-15 becomes
4044-03-15 00:00:00, consistent with the documented historic-year scheme.
All diagnostic calls ran with E_ALL warnings as exceptions under PHP 8.1.34.

No isolated preparation fix was made: SimpleORM::SaveRecordFromQuery_Base tests
object[0] and falls back to treating an empty array as a single record, then adds
Entryid. Changing preparation to return TRUE with [] would therefore expose the
shared writer's empty-collection contract. Next reproduce that writer path with
a recording DB double, then repair empty optional collections across the actual
preparation/persistence boundary. No database or filesystem writes were made.
The temporary evidence script is ggcms-date-preparation-20260911.php; these are
diagnostic observations, not added passing regression claims.

## 11 September 2026: empty optional collections and date preparation

Reproduced the shared writer issuing CreateRecord for [] after adding Entryid.
SaveRecordFromQuery_Base now returns TRUE immediately for exactly [], preserving
its empty property and unsaved snapshot without database work. Populated return
shapes remain unchanged; inspected callers use the result as a success indicator.
Date preparation now always assigns its filtered collection and returns TRUE,
so empty/all-blank input no longer fails preparation or retains unprepared rows.

Two real-trait writer cases verify zero calls for [] and one for a populated
EventDate row, with no collected E_ALL warnings. Four extracted preparation
cases assert exact output, TRUE return and preserved original snapshot for
empty, blank, year-only and BCE data. Earlier empty writer made one unwanted
call; empty preparation was falsey and blank preparation retained input fields.
Saved both fixtures; all 98 suite cases pass under PHP 8.1.34. Both edited PHP
files lint and diff checks pass. Database operations use recording doubles;
full Save/Update, image-specific post-save processing, NULL/malformed collection
inputs and transaction rollback remain outside these checks.

## 11 September 2026: joined optional-date preparation and writer checks

Added date-pipeline.php, which joins the extracted real EventDate preparation
method to the real SimpleORM shared writer. Five cases cover empty, blank,
year-only, BCE and mixed blank/populated input. The recording database double
receives no calls for empty/blank cases and only the expected prepared dates
for populated cases, with Entryid 7 and no EventDate/EventTime form-only fields.
All five pass with E_ALL warnings as exceptions under PHP 8.1.34. This closes
the previously separate preparation-to-writer fixture gap for these inputs.

The fixture supplies explicit shared-writer noentryid=FALSE and initialises row
identity fields. It does not execute the whole Save action, Query ingestion,
permissions, MySQL persistence or Update cleanup; those remain separate scope.
No new production-code edit was necessary for this joined validation.

## 11 September 2026: entry and assignment delete IDs are bound

DeleteEntry and DeleteAssignment concatenated the entry ID into their SQL,
contrary to CodeConventions' separate-doors rule even for integer IDs. Both now
use one ? placeholder, bind string i and one record value through the existing
FillArraysFromDB interface. Query targets and return values are unchanged.

Four real-ORM fixture cases cover integer and SQL-like input for each method,
asserting fixed SQL, separate bound values and unchanged returned error arrays.
All four failed before and pass afterwards. The complete saved suite passes
107 cases under PHP 8.1.34; ORM lint and diff checks pass. The database is a
recording double: this is query-construction evidence, not a claim that request
input could previously reach these methods unchecked or a live exploit test.
No database records were deleted. Child-delete SQL construction remains a
separate audit item; it was not swept into this two-query repair.

## 11 September 2026: child-delete parent IDs are bound

DeleteChildRecords now binds the parent ID in both association pre-cleanup and
the normal child query. The normal values list is parent ID followed by kept
record IDs; its bind string has the corresponding leading i. Association query
order, record-type selection and failure short-circuiting remain unchanged.

Four real-ORM query fixtures cover Tag/Association with empty/two-ID keep lists.
All failed before and pass after, checking exact main SQL, argument ordering,
bind strings and association pre-query values. Existing failure-order fixtures
also pass. The complete saved suite passes 111 cases under PHP 8.1.34; ORM lint
and diff checks pass. No database deletion was executed. Record-type identifier
provenance and transaction atomicity remain separate from value binding.

## 11 September 2026: generic 404 home-link markup

Error404::Display ended the Home Directory anchor with another opening <a>
instead of </a>. The closing tag is now correct. A missing-entry fixture failed
before the repair because parsing the output produced an extra home anchor;
it passes afterwards. Three additional cases preserve unpublished-entry,
untitled-entry and admin guidance behavior, including escaped script-like titles
and the existing missing-entry redirect script.

The saved suite passes 115 cases under PHP 8.1.34. Error404 PHP lint and the
whitespace diff check pass. These checks capture actual Display output and parse
it with DOMDocument; they do not exercise server dispatch, HTTP status headers,
the redirect in a browser or the entire request pipeline.
## 11 September 2026: joined content-dispatch to 404 validation

Four cases now invoke the real Handler::HandleRequest_ServeContent and
HandleRequest_Error_404 with the real Error404 renderer. Failed content and
unresolved paths produce status 404, one expected issue log and the error body;
a repaired path whose content fails does likewise. Successful content returns
without an error body or log and retains status 200. The fixture also checks
content-call and path-repair-call counts and the before_content transition.

Path resolution, redirect candidates, content rendering and logging storage are
doubles. Construction and database shutdown are bypassed. The HTTP status is
observed through PHP CLI's status API, not an HTTP client. Thus these cases join
the handler's fallback dispatch to error rendering but do not prove production
routing, redirect resolution, database persistence or headers sent by a server.
No production edit was needed. All 119 saved cases pass on PHP 8.1.34; fixture
lint and whitespace checks pass.
## 11 September 2026: UpdateRecord propagates query failures

DBAccess::UpdateRecord discarded failures from resetting the update ID, executing
the UPDATE and reading the update ID. It could continue to GetRecords after a
failed write or access a missing result offset. Each stage now returns its
existing structured query error immediately, using the same line marker as
other DBAccess callers. Successful update/readback behavior remains unchanged.

Three injected failure-stage cases failed before and pass after; the successful
case passes both versions. The fixture invokes real UpdateRecord with query,
schema and cache doubles and checks that no later query/readback follows failure.
All 123 saved cases pass under PHP 8.1.34, with DBAccess lint and whitespace checks
clean. This proves method-level propagation, not complete form error rendering
or MySQL transactions. Earlier writes are not rolled back. CreateRecord and
callers that ignore returned errors remain separate audit work.
## 11 September 2026: CreateRecord preserves insert/readback errors

CreateRecord now returns a structured insert error before inspecting the insert
ID, and returns a structured GetRecords error before indexing its first row.
Previously the latter produced a missing-offset warning and lost the error.
Successful inserted-row return shape and zero-insert-ID behavior are unchanged.

Two failure cases reproduce before and pass after, alongside successful and
zero-ID controls. The diagnostic extracts the exact CreateRecord method into a
namespace so mysqli_insert_id can be replaced with a recording double; schema,
queries and readback are also doubles. This is method-contract evidence, not
live mysqli behavior or evidence that a failed insert retains a stale ID.
All 127 saved cases pass under PHP 8.1.34; DBAccess lint and whitespace checks
pass. Empty successful readback, exceptions, whole-form reporting and rollback
remain outside this repair. No database records were created.
## 11 September 2026: parent entry failures stop dependent writes

SaveRecordFromQuery_Entry now checks structured database errors before assigning
an inserted entry or indexing an updated row. It retains the submitted entry
and unsaved snapshot, records the admin diagnostic and returns FALSE. The Save
and Update aggregate methods now return immediately after reporting an entry
failure, preventing child writes using a parent that did not save successfully.

Both entry failure cases failed before and pass after; both success controls
remain green. Two aggregate-method diagnostics confirm no child methods run on
parent failure and the existing user-facing Entry error is recorded. An initial
aggregate fixture had incomplete entry_unset setup; its final version supplies
both entry identities explicitly. The fixtures extract actual methods and use
database/child-method doubles. All 133 cases pass under PHP 8.1.34, with modify
lint and whitespace checks clean. Full request/form rendering, partial child
failure handling and transactions remain unverified. No real records changed.
## 11 September 2026: shared child-writer result contracts

SaveRecordFromQuery_Base now detects structured errors by their line marker as
well as a nonempty error message, without requiring an error key on successful
rows. Single-row detection checks that index zero exists before inspecting it.
Previously normal database success shapes warned on the absent error key,
single-row inputs warned during shape detection, and errors with an empty
connection message were not recognized as failures.

Twelve real-trait cases cover single/list inputs, insert/update and success,
nonempty-message error or empty-message structured error. Ten failed before;
all pass after. Failure returns FALSE, preserves submitted input and records
both admin and user diagnostics; successful row shapes are unchanged. All 145
saved cases pass under PHP 8.1.34; trait lint and whitespace checks pass. Database
calls are doubles. Optional metadata is explicitly initialized in the fixture;
full form round-tripping, malformed inputs, multi-row partial writes and rollback
remain separate validation scope.
## 11 September 2026: image database failures remain failures

SaveRecordFromQuery_Image no longer replaces a false shared-writer result with
the image array. Each of its three writer calls now returns FALSE immediately
on failure, preventing a later write from overwriting an earlier failure result.
The initial guard also accepts an empty image collection without indexing it.

Empty-image and initial-row-failure cases failed before and pass after using the
extracted actual method and a writer double. Initial failure exits before any
Imagick construction or file operation. The later upload/rename guards have
source-level validation only; those file branches were not executed. All 147
saved cases pass with PHP 8.1.34, modify lint and whitespace checks clean.
Unlink-before-upload ordering, unchecked move/rename/resize failures and rollback
of files changed before a later database failure remain unresolved. No real
images or database records were changed by these diagnostics.
## 11 September 2026: renamed-image metadata failure exercised

Two additional diagnostics execute the exact image-save method in a namespace
with a recording rename function and an inert Imagick stand-in. With two images,
a metadata-write failure after renaming the first original/icon/standard files
returns FALSE before any second-image rename. The success control performs the
six expected renames and three writer calls. Both verify exact file path pairs.
This strengthens the earlier source-only check for the rename metadata guard.

All 149 saved cases pass with PHP 8.1.34, modify lint and whitespace checks clean.
No filesystem rename occurs in these tests. A failed metadata save still leaves
the first image's already-renamed files in place: this validation demonstrates
short-circuiting, not atomicity. Actual rename failures, upload handling and
rollback remain unresolved. Restored adjacent DeleteFiles indentation introduced
by the preceding local whitespace cleanup.
## 11 September 2026: failed file renames stop image processing

The original, icon and standard rename calls now check their return values.
A failure records a user-facing error describing possible partial renaming and
returns FALSE before later file operations or the post-rename metadata save.
Previously all three results were ignored and processing reported success.

Three injected rename-failure cases fail before and pass after; existing
metadata-failure and successful-rename controls still pass. The recording double
checks the operation count, absence of a second database-writer call and one
error. All 152 saved cases pass under PHP 8.1.34; modify lint and whitespace
checks pass. Actual filesystem permissions/errors and PHP warning presentation
are not exercised. The initial database write and earlier successful renames
remain in place on failure; rollback and safe operation ordering are unresolved.
No real files were renamed or records changed by the diagnostics.
## 11 September 2026: invalid replacement uploads preserve existing files

The upload branch now rejects a missing/empty temporary filename or a nonzero
upload error before unlinking existing image files. It records a form error and
returns FALSE. The former check came after deletion, printed an assumed size
error and continued into hashing. It did not handle reported upload errors.

Three cases for empty/missing temporary names and a reported partial-upload
error failed before and pass after. The extracted actual method uses recording
unlink and is_file doubles plus a hash trap; failures return without deletion,
hashing or direct output. All 155 saved cases pass with PHP 8.1.34, modify lint
and whitespace checks clean. No actual files or database rows changed.
The initial database write still precedes this guard. A nonempty temporary path
is not proof of a valid upload; hashing, moving, resizing, operation ordering and
rollback remain unresolved for otherwise valid-looking upload metadata.
## 11 September 2026: failed upload moves stop downstream work

The upload branch now checks move_uploaded_file. Failure records a form error
and returns FALSE before resizing or post-upload metadata writes. Previously
it ignored failure and continued. The error explicitly acknowledges that the
current ordering may already have removed old files.

The failed-move case failed before and passes after; a successful control checks
the move path, both resize calls, saved directory and two writer calls. These
execute the exact extracted method with namespaced filesystem/Imagick/Base and
writer/resize doubles. All 157 cases pass with PHP 8.1.34, modify lint and
whitespace checks clean. No real uploads, files or records were changed. Cleanup
still precedes moving; safely deferring it requires handling equal old/new paths.
Hash/resize failure checks and transactional replacement remain unresolved.
## 11 September 2026: replacement cleanup follows successful metadata save

Old image paths are now cleaned up only after storing the replacement, resizing
and successfully saving replacement metadata. Cleanup skips every replacement
destination and deduplicates old paths. Failed cleanup reports that replacement
succeeded but an old file remains, then returns FALSE. The move-failure message
no longer says that old files may have been deleted by this branch.

Four ordering/path cases failed before and pass after: failed move, failed
metadata save, distinct old/new paths and identical paths. An additional cleanup
failure case confirms the write precedes cleanup and failure is reported. The
fixture checks ordered writer/unlink events with recording doubles; an initial
test variable alias was corrected before taking the before/after results.
All 162 cases pass under PHP 8.1.34; modify lint and whitespace checks are clean.

This removes premature unlinking, not every replacement atomicity risk. The
initial database write remains earlier, existing destinations can be overwritten
by move/resize, and later metadata failure can leave replacement files behind.
Resize failure checks, staging/rollback, physical path aliases and live upload
validation remain unresolved. Tests did not touch real files or database rows.
## 11 September 2026: image-directory failure stops upload paths

UpdateImagesDirectory explicitly returns FALSE when mkdir fails. The upload
caller previously concatenated that result with filenames, producing relative
destination paths. It now records a form error and returns FALSE before moving,
resizing, metadata follow-up or old-file cleanup. A new failure case reproduces
before and passes after, checking that all downstream operation lists are empty.

All 163 saved cases pass under PHP 8.1.34; modify lint and whitespace checks pass.
The directory result is doubled; actual mkdir permissions were not exercised.
Inspection of SimpleImages::resizeAndSaveFile also confirmed it ignores fopen,
Imagick read/scale/write results and returns dimensions regardless of false
results. That helper needs its own failure contract and caller validation;
checking only the current dimensions array would not prove file creation.
## 11 September 2026: resizing has an explicit failure contract

SimpleImages::resizeAndSaveFile opens input read-only (rb), checks input/output
open and Imagick read/scale/write results, returns FALSE on failure or an
ImagickException, and closes opened streams in finally. It returns dimensions
only after the write succeeds. Existing sizing calculations remain unchanged.
The upload caller stops on either failed resize before metadata follow-up and
cleanup. The dbstatus image-repair caller records the failed image and skips
its dimension update; that caller adaptation is source-reviewed and linted.

Nine helper diagnostics cover input/read/scale/output/write failures, an Imagick
exception, and small/landscape/portrait controls, checking stream closure and
read-only input mode. Initial helper tests failed; final tests pass, including
corrected numeric dimension comparisons and an actual portrait control. Two
upload cases verify icon/standard failures stop metadata and old-file cleanup.
All 174 cases pass with PHP 8.1.34; all three changed PHP files lint and diff
whitespace checks pass. Tests use in-memory streams and Imagick doubles, not
real image encoding. The entire dbstatus repair loop was not executed.

Warnings from native fopen, stream close/flush failures, dimension validation,
non-square size bounds, partial output files and replacement staging/rollback
remain outside this repair. No real images or database records were changed.
## 11 September 2026: unreadable upload hashing stops processing

The upload branch now checks hash_file for FALSE before deriving the directory
or moving/resizing files. It reports an unreadable-upload form error and returns
FALSE. A recording-double case failed before and passes after, verifying no
move, resize or old-file deletion and no second writer call. The initial database
write still precedes this check. All 175 saved cases pass with PHP 8.1.34.

A fresh php -m inspection confirms that the available local CLI has no Imagick
extension. Real image encoding cannot be validated with this runtime; the resize
fixtures remain contract tests with doubles. All 25 currently changed tracked
PHP files lint successfully and the whitespace diff check passes. This broad
syntax check includes preserved pre-existing work and is not a claim that those
changes received complete behavioral review. No deployment or real uploads ran.
## 11 September 2026: Atom envelope initializes its image-link URL

ConvertHTMLToFormat used an uninitialized url variable at the root and used
concatenating assignment to that same uninitialized variable for nested paths.
It now initializes the root URL and assigns the completed nested view URL.
The current logo renderer does not consume the link value beyond reading it,
but the caller previously emitted a PHP warning before rendering every envelope.

Six cases failed before and pass after: root, one-level and two-level paths,
each with human-readable mode on/off. The actual envelope is parsed as XML and
its self link and ID checked; image/content renderers and templates are doubled.
All 181 saved cases pass under PHP 8.1.34; Atom lint and whitespace checks pass.
Plain text is used here. XML-special text, populated entries and feed semantics
remain separate coverage; this is not proof that arbitrary Atom feeds validate.
## 11 September 2026: Atom titles preserve XML-special text

Feed title/subtitle and entry title/subtitle/ancestor-title components are now
escaped with htmlspecialchars using ENT_XML1, ENT_QUOTES, ENT_SUBSTITUTE and
the system charset accessor. Plain text remains text, including literal entity
spellings. Previously ampersands broke parsing, tag-like titles became markup,
and entity spellings were silently decoded.

Five full-envelope cases include plain, ampersand, markup-like, literal-entity
and multilingual text. Three failed before; all pass after. The real entry
renderer runs, and XPath verifies exact feed/entry title text and absence of
child markup. Templates, image rendering, description generation and dependencies
are doubled. All 186 cases pass under PHP 8.1.34, with Atom lint and whitespace
checks clean. Author/category/URL/summary escaping and XML-disallowed control
characters remain separate work; these cases do not prove arbitrary feeds valid.
## 11 September 2026: Atom author/category XML boundaries

Feed admin name/email, associated entry author names and feed/entry category
values now use XML escaping with the existing local system charset. Raw quotes
could break category attributes; raw markup/entity text could alter author nodes
or their text. Six parsed-envelope cases exercise plain, ampersand, markup-like,
literal entity, double-quoted and multilingual values. Four failed before and
all pass after. XPath checks exact text/attribute values, no author child markup
and exactly the intended category attributes.

All 192 saved cases pass under PHP 8.1.34; Atom lint and whitespace checks pass.
These are serialization checks, not email syntax or Atom schema validation.
Dependencies and description/image generation are doubled. URL/generator/summary
escaping, XML-disallowed characters and broader serializer work remain open.
## 11 September 2026: Atom summary escaping runs once

Cleanup previously escaped angle brackets and then escaped the ampersands it
had just inserted, leaving visible entity spellings in parsed summary text.
It now performs one htmlspecialchars XML escape after HTML entity decoding.
Decoding explicitly uses the system charset and PHP 8.1's existing HTML401/
quotes/substitution behavior. Tag stripping, whitespace cleanup and ellipses
remain unchanged.

Six cases run the real description-selection, formatting and cleanup methods
and parse their summary fragments: plain text, ampersands, encoded brackets,
quotes/brackets, paragraph markup and multilingual text. Two failed before and
all pass after, checking exact text and absence of child elements. All 198 saved
cases pass under PHP 8.1.34; Atom lint and whitespace checks pass. This does not
cover the fallback summary branch, missing descriptions, control characters,
truncation or a complete live feed request.
## 11 September 2026: absent Atom descriptions reach escaped fallback text

Description selection now guards optional child records and returns an empty
string when neither description nor text-body preview exists. Removed an RSS
0.91 truncation condition from Atom: Atom and its base never initialize the
version_float property that condition read. The generated fallback summary is
XML-escaped as one text string, including the configured grandchild noun.

Five real entry-renderer cases failed before and pass after: missing/null/empty
children, text-body fallback and a 600-character description. They parse exact
summary text, preserve zero chapter counts and forbid child markup. They do not
initialize the obsolete version_float property. All 203 saved cases pass under
PHP 8.1.34; Atom lint and whitespace checks pass. Dependencies are doubled; this
is not a live request or a comprehensive malformed-record audit. Atom URL and
generator output boundaries remain open, as do other serializers.
## 11 September 2026: Atom generator label is XML text

The generator's configured software/version label now uses XML escaping with
the existing system charset. The six existing metadata cases now also assert
the parsed generator text and absence of child markup. Three extended cases
failed before and pass after: ampersand, markup-like and literal-entity labels.
No new case count was added. All 203 saved cases pass under PHP 8.1.34; Atom lint
and whitespace checks pass. This tests serialization with a version-object
double, not configuration ingestion or a live feed request. URL output boundaries
and XML-disallowed control characters remain open.
## 11 September 2026: Atom base and entry URLs escape XML syntax

Base URLs in feed links/ID and entry alternate-link URLs now escape XML-special
characters at output. URL construction itself is unchanged. Four added cases
with ampersand/quote codes fail before and pass after, alongside the six existing
root/nested/readable combinations. The fixture now uses the real entry renderer
and verifies parsed entry URLs as well as feed ID and self URLs.

All 207 saved cases pass under PHP 8.1.34; Atom lint and whitespace checks pass.
This preserves serialized URL text, not RFC URL validity or routing behavior.
Path-segment percent-encoding, image URLs, permalink IDs and configured-domain
validation remain separate concerns. Description and image output are doubled;
no live HTTP request ran.
## 11 September 2026: RSS summary escaping runs once

RSS had the same sequential angle-bracket/ampersand replacement defect as Atom.
Its cleanup now decodes HTML entities with explicit system charset and escapes
XML once, preserving the prior tag/whitespace and ellipsis behavior.

Twelve cases cover six short inputs at RSS 0.91 and 2.0. Four failed before and
all pass after, parsing exact summary text and no child markup through the real
description pipeline. All 219 saved cases pass with PHP 8.1.34; RSS lint and
whitespace checks pass. The tests use fragments and dependency doubles. Long
RSS 0.91 descriptions still truncate serialized bytes, potentially splitting an
entity or multibyte character; that distinct boundary defect remains open.
Missing descriptions, fallback text and other RSS fields remain separate work.
## 11 September 2026: RSS 0.91 truncation preserves characters and entities

RSS 0.91 now measures decoded summary characters and truncates that text with
mb_substr before XML-escaping the shortened result. Previously truncating the
serialized bytes could split an entity or UTF-8 sequence and break XML parsing.
The existing greater-than-500 trigger, 497-character shortened payload and
ellipsis behavior remain; RSS 2.0 does not truncate through this branch.

Eight cases cover 500/501 ASCII characters, an entity at the cut and multibyte
text for both versions. Two fail before and all pass after, checking parsed
text. All 227 saved cases pass on PHP 8.1.34; RSS lint and whitespace checks pass.
The retained policy still appends ellipses to untruncated nonempty descriptions,
so a 500-character source yields 503 displayed characters. That existing policy
was not changed or claimed to meet a formal RSS schema length limit. Full feed
validation and other RSS output boundaries remain open.
## 11 September 2026: RSS missing-description fallback is valid XML

RSS description selection now guards missing/null/empty child records and returns
an empty string when there is no usable description or text preview. Fallback
text is escaped as one XML text value, preserving configured nouns and zero
chapter counts without creating markup.

Five RSS 2.0 item-renderer cases cover missing/null/empty records, text preview
and long description controls. Three fail before and all pass after. Parsed
fragments have exact expected description text and no child elements. All 232
saved cases pass with PHP 8.1.34; RSS lint and whitespace checks pass. Dependencies
are doubled; complete feed/channel rendering and other RSS text fields remain
open. No live request or database mutation was performed.
## 12 September 2026: RSS titles preserve XML-special text

Channel title/subtitle and item title/subtitle/ancestor-title components now
use XML escaping with the system charset. Five complete RSS 2.0 envelope cases
check exact parsed channel/item title text and absence of child markup. Three
failed before and pass after: ampersand, markup-like and literal entity text;
plain/multilingual controls remain green. Images, templates and descriptions
are doubled, while the real item and channel rendering methods run.

All 237 saved cases pass on PHP 8.1.34; RSS lint and whitespace checks pass.
Image titles, channel description, metadata and URL boundaries remain open.
The first fixture-write attempt was rejected by automatic approval review for
an account usage limit and made no file. Work resumed only after the usage tool
confirmed ordinary usage was available again and the absent file was verified.
## 12 September 2026: RSS editor/generator/category text is escaped

Managing-editor and webmaster email/name fields, the software label in both
generator/copyright branches and item categories now use XML escaping with the
system charset. Ten parsed full-envelope cases cover five text variants for
RSS 0.91 and 2.0; six failed before and all pass after. Assertions check exact
metadata text and absence of injected child elements, including category output
only in the version branch that supports it.

All 247 saved cases pass with PHP 8.1.34; RSS lint and whitespace checks pass.
The fixture doubles configuration, templates, descriptions and images. It does
not validate email syntax, RSS schema compatibility or fetch the RSS 0.91 DTD.
Channel descriptions, image fields and URL serialization remain open.
## 12 September 2026: RSS channel description is escaped at output

Source inspection of news::getDescription and the base implementation confirms
they return unescaped strings assembled from entry fields, potentially including
stored markup. RSS now escapes that string in the channel description rather
than inserting it as XML. The parsed text preserves the original markup/entity
spellings; it is not passed through the item-summary stripping pipeline.

The existing five full RSS title cases now also assert exact channel-description
text and no child elements. Three extended cases failed before and pass after.
All 247 saved cases pass under PHP 8.1.34; RSS lint and whitespace checks pass.
The description provider is doubled in this diagnostic. Actual news description
assembly and RSS-client HTML interpretation were source-traced, not live-tested.
Image fields and URL output remain open.
## 12 September 2026: RSS image text and links are escaped

The image renderer now escapes title, description and supplied page-link text
using the system charset. Six fragment cases check plain, ampersand, markup-like,
literal entity, multilingual and query-string link values. Four failed before
and all pass after. XPath verifies exact text, no child markup, and unchanged
filename URL encoding. Existing absent-image cases remain green.

All 253 saved cases pass under PHP 8.1.34; RSS lint and whitespace checks pass.
This covers metadata XML, not image pixels, configured-domain validation or
physical image existence. FileDirectory provenance and remaining channel/item
URL boundaries are still open.
## 12 September 2026: RSS URL output escapes XML syntax

Channel/self/documentation URLs and item/comment URLs now escape XML-special
characters at output without changing URL construction. Ten complete RSS 2.0
cases cover root/nested paths and ampersand/quote codes, each with readable mode
on/off. Four failed before and all pass after. XPath checks parsed links match
the constructed channel, self, docs, item and comments URLs exactly.

All 263 saved cases pass under PHP 8.1.34; RSS lint and whitespace checks pass.
The actual envelope/item renderers run with images/descriptions/templates and
domain dependencies doubled. URI validity and routing for such codes were not
tested; path-segment percent-encoding remains distinct from XML escaping.
GUID/domain provenance and malformed optional data remain outside these cases.
## 12 September 2026: OPDS emits computed entry ID and guards field lookup

OPDS now emits the result of SetID for its entry ID, rather than the title.
It also checks field-allowlist membership without reading absent keys for each
ordinary record field. Two complete-document cases retain the computed ID while
the title changes and preserve the allowed privacy-policy field. Both fail
before, still fail after only the lookup guard, and pass after the ID correction.

All 265 saved cases pass under PHP 8.1.34; OPDS lint and whitespace checks pass.
Construction, author/description generation and dependencies are doubled, while
SetID, SetTitle and document serialization run. Existing SetID's host/filename
scheme is preserved, not claimed to be a valid Atom IRI or globally unique.
Consumers may observe the intended ID replacing title-derived IDs; migration,
OPDS schema validity and metadata escaping remain unresolved.
## 12 September 2026: OPDS metadata escapes XML text and attributes

Feed/entry titles, creator/author names, summary, category label and allowlisted
extra-field values now use XML escaping with the system charset. Five complete
document cases cover plain, ampersand, markup-like, entity-like and quoted text.
Four fail before and all pass after. XPath checks exact parsed values and no
unexpected child elements/attributes; the existing computed-ID checks still pass.

All 270 saved cases pass with PHP 8.1.34; OPDS lint and whitespace checks pass.
Construction, author/description providers and dependencies are doubled. Stored
markup is preserved as text rather than emitted as nested XML. OPDS/Atom schema
validity, ID/date/URL boundaries and reader presentation remain open.
## 12 September 2026: OPDS optional author/description fields are guarded

SetAuthor and SetDescription now guard missing child collections and blank
first-row fields. Missing fields return empty strings without warnings. Existing
source-label formatting and a literal zero description are preserved.

Six direct real-method cases cover missing/null/empty collections, blank rows,
populated rows and zero strings. Two failed before and all pass after. All 276
saved cases pass under PHP 8.1.34; OPDS lint and whitespace checks pass. These
cases bypass construction and supply record arrays, without database or request
execution. Sparse arrays, scalar malformed data and author identity semantics
remain outside the repair.
## 12 September 2026: OPDS acquisition/self URLs preserve XML-special text

OPDS self and acquisition links now XML-escape the constructed base path and
supported-format suffix. Four complete-document cases cover plain paths,
ampersand paths, quoted paths and a format suffix with two query parameters.
Three failed before and all pass after; parsed href values match their intended
constructed strings exactly.

All 280 saved cases pass on PHP 8.1.34; OPDS lint and whitespace checks pass.
Dependencies and author/description generation are doubled. This validates XML
serialization, not URL percent-encoding, route resolution, acquisition semantics
or OPDS schema compliance. Image URLs and remaining ID/date boundaries stay open.
### OPDS image directories and filename encoding — 12 September

OPDS cover and thumbnail links omitted Image.FileDirectory, unlike the existing Atom/RSS image paths. Raw filenames containing ampersands also produced malformed XML. The renderer now expands the stored directory code into path segments and applies rawurlencode to each filename; absent/empty directories retain the root image path.

Extended the existing four opds-images cases to assert exact image hrefs, including nested directories and a filename with spaces and an ampersand. Both populated-image cases failed before the repair and pass afterward. Full local regression suite: 280 cases passing on PHP 8.1.34. These are source-block/DOM checks, not live HTTP image retrieval or OPDS schema validation. Domain serialization and unsupported image extensions remain outside this repair.
### OPDS MIME helper initialization — 12 September

SetMimeTypeAndFormats passed an undefined $args variable to MIMEType. Its constructor requires the handler key; existing OPDS rendering fixtures bypassed this setup. The method now supplies the same explicit handler argument used by HTML/Image and the neighboring Formats constructor.

The new opds-setup diagnostic invokes the real setup method and real MIMEType/Formats classes, with only the loader and base format isolated. Before: Undefined variable $args under strict error reporting, fixture failed. After: both helpers retain the exact handler and return representative MIME/format entries. Full local suite: 281 passing cases, PHP syntax and whitespace checks clean. This validates helper setup, not the complete request constructor, template rendering, or live catalog retrieval. Optional image metadata and remaining serializer/schema issues remain open.
### OPDS absent image association — 12 September

The image collection guard dereferenced the image key before checking its value. An otherwise valid record without that optional association raised Undefined array key "image" under strict reporting. Null coalescing now supplies an empty array for either a missing or null association; populated arrays follow the existing rendering path.

Extended opds-images with an absent-key case: failed before with the undefined-key diagnostic, passes afterward with no image links. Existing null, empty, single and multiple-image cases still pass. Full suite: 282 cases passing; PHP syntax and whitespace checks clean. This proves source-block behavior with an absent association, not its frequency in live ORM/request paths, and does not define behavior for malformed non-array image values.
### XML export uses the CMS charset — 12 September

XML.array_to_xml escaped scalar values using PHP's implicit default charset and HTML defaults. Under default_charset=ISO-8859-1, an invalid UTF-8 byte survived escaping and the resulting document failed DOM parsing. The scalar boundary now uses the shared SystemCharSet accessor with ENT_QUOTES, ENT_XML1 and ENT_SUBSTITUTE, matching the documented encoding contract.

The new xml-text diagnostic runs the actual ConvertHTMLToFormat and recursive array_to_xml methods, supplying nested template data and a charset accessor. Six cases cover reserved characters, multilingual text with a literal entity, malformed bytes, and nested zero values under UTF-8 and ISO-8859-1 defaults. Before: the malformed-byte/non-UTF-8-default case failed; five passed. After: all six pass and full suite totals 288 passing cases. PHP lint and whitespace checks clean after normalizing indentation on the edited legacy line. Template execution, arbitrary element names, XML-disallowed control characters and live downloads remain unverified. SimpleXML is now listed as a diagnostic dependency.
### JSON malformed UTF-8 text no longer empties the download — 12 September

JSON.Display passed raw record data to json_encode without an invalid-UTF-8 policy. One malformed string made encoding return FALSE; print then emitted nothing and returned 1. The export now uses JSON_INVALID_UTF8_SUBSTITUTE, retaining the record while replacing malformed sequences with U+FFFD, consistent with the XML text boundary.

The new json-text diagnostic invokes the real Display method with action/header/template dependencies isolated. Four cases validate plain and multilingual strings plus invalid and truncated UTF-8, including nested text, numeric zero, null and record identity. Before: the two malformed cases produced undecodable empty output and failed. After: all four pass; full suite 292 cases passing, syntax and whitespace checks clean. This is not HTTP/download or database integration validation. Other json_encode failures (cycles, unsupported values, excessive depth, non-finite numbers) and potentially colliding malformed object keys are outside this repair; no blanket successful-serialization claim.
### CSV retains zero-valued entry fields — 12 September

GenerateCSV used a truthiness filter for entry fields, silently omitting integer 0, float 0.0 and string "0" while child fields retained zero. The filter now explicitly retains these zero representations. Existing omission of NULL, empty string and FALSE remains unchanged; broader empty-value export policy is not resolved here.

The new csv-values diagnostic invokes real GenerateCSV/EscapeDataForCSV, parses the produced CSV, and compares complete rows including entry identity and child zero values. Eight cases: three zero representations, positive integer, text, NULL, empty string and FALSE. Before: all three zero cases failed and five controls passed. After: all eight pass; full suite 300 passing cases. PHP syntax and whitespace checks clean. This validates local data conversion, not full HTTP export or spreadsheet import; nested child shapes, quoting edge cases and stream failures remain separate coverage work.
### CSV backslash followed by quote — 12 September

EscapeDataForCSV relied on fputcsv's default backslash escape mode. A field containing a backslash immediately followed by a quote failed a round trip through a CSV reader using doubled-quote escaping. The writer now explicitly supplies comma separator, double-quote enclosure and an empty escape character so enclosure quotes are consistently doubled.

The new csv-quoting diagnostic invokes the real EscapeDataForCSV and parses complete two-row output with fgetcsv's escape parameter explicitly empty. Seven cases cover plain text, comma, quote, CRLF, backslash-quote, trailing backslash and multilingual text. An initial fixture syntax error in the trailing-backslash literal was corrected before reproduction. Before production repair: only the backslash-quote case failed. After: all seven pass; full suite 307 cases passing, PHP syntax and whitespace checks clean. This proves local round-trip preservation for these cases, not interoperability with every spreadsheet/legacy parser or a complete CSV specification audit. Stream error handling remains open.
### User export includes the dislike label — 12 September

The shared portable user-export document appended Liked for LikeOrDislike == 1 but evaluated a standalone Disliked string in the else branch. Consequently text exports showed a blank vote label for non-like records. The else branch now appends Disliked to html_document, preserving the existing vote classification.

The new user-export-vote diagnostic includes the actual complete exportuser template in text mode, with loader and sorting dependencies doubled. Three cases exercise 1, 0 and -1: before repair the like control passed and both else-branch cases failed; afterward all pass. The cases preserve existing classification rather than establish which non-like values the database stores. Full local suite: 310 passing cases; syntax and whitespace checks clean. The shared document fix also feeds other portable branches by source inspection; those branches, real sorting, database retrieval, permissions and live downloads were not exercised here. Entity decoding and optional associations remain separate export coverage work.
### User text export decodes HTML entities once — 12 September

Comments are escaped while constructing the portable HTML document, but text export only stripped tags. The resulting download exposed entity codes instead of original ampersands, quotes and literal markup. The text branch now decodes HTML entities once after stripping document tags, using the CMS charset; decoding after stripping preserves literal markup in escaped comments.

The new actual-template user-export-text diagnostic covers plain text, ampersands, literal tags, a literal entity and quotes. Fixture string-literal syntax was corrected before reproduction. Before production repair: plain text passed and four escaped cases failed. After: all five pass; existing vote fixture also passes with the charset dependency supplied. Full suite 315 cases passing, production PHP lint and whitespace checks clean. Sorting/loading and HTTP handling are doubled or absent. Wrapped output, other portable branches, unescaped title/username fields and charset defaults at upstream HTML escaping remain separate coverage work.
### User export preserves usernames and ancestor titles — 12 September

The portable document inserted Username and both comment/vote ancestor Title values as raw HTML. Literal tags were removed by text conversion, and the preceding entity-decoding repair exposed a further mismatch for raw literal entity text. These three insertion sites now escape HTML with the explicit CMS charset, matching the text conversion boundary.

The new actual-template user-export-labels diagnostic checks the username heading and both ancestor-title occurrences for five values. Before: literal markup and literal entity cases failed; plain, ampersand and quote controls passed. After: all five pass; full local suite 320 cases passing, syntax and whitespace checks clean. Template fixtures isolate sorting, loader and data access. This does not validate live username constraints or all HTML/portable branches. The preceding comment-text repair remains, with its upstream default-charset dependency and wrapped-output behavior still outside these checks.
### Portable user-export comments use the CMS charset — 12 September

The intermediate HTML comment escape still used PHP's default charset. With ISO-8859-1 configured, malformed UTF-8 bytes survived that boundary and remained invalid in the text download. The portable comment insertion now explicitly uses the CMS charset and HTML substitution flags, matching the downstream decode and neighboring label escapes.

New user-export-charset runs the actual template with multilingual text, an ampersand and a malformed byte under UTF-8 and ISO-8859-1 defaults. It checks the expected replacement character and validates the complete output encoding. Before: UTF-8 passed, ISO-8859-1 failed. After: both pass; full suite 322 passing cases. Syntax and whitespace checks clean. This closes the portable comment charset observation from the preceding notes; the separate normal HTML comment branch, wrapped output and complete request/database behavior remain unverified.
### User export request trace and default sorting — 12 September

Current users.exportuser explicitly removes all LikeOrDislike != 1 records before rendering. Therefore the earlier Disliked template repair does not establish live dislike export: that branch is unreachable through this action today. Whether exports should include dislikes is an unresolved content-policy question; the filter was preserved. SetRecordEntries returns [] for an empty record array, and the real sorter handles that empty array without warning. Database failures and null/malformed collection handling are not proven by this trace.

For populated entries, module_entrysort.Sort unconditionally read the optional sort_field key, although exportuser passes only entries. Changed that condition to !empty so omission follows the existing default order without an undefined-key warning. The new entry-sort-default fixture loads the real spacing/sorting modules: missing field failed before, while empty field, explicit Title and no-record controls passed. All four pass after; full suite 326 passing cases, PHP lint and whitespace checks clean. Other optional entry fields, missing associations, and full request integration remain open.
### Sort direct and sparse entries without missing-key warnings — 12 September

The sorting helper initialized its candidate from the direct entry but immediately read an optional entry wrapper without a guard. It also read optional ListTitleSortKey/ListTitle/Title fields unconditionally. Optional wrapper lookup is now guarded and absent sort fields default to an empty string, preserving the existing truthiness-based fallback sequence and ID suffix. The inert empty-key collision condition is guarded too; its legacy comment/body remains.

New entry-sort-shapes loads the real module and checks five shapes: direct fully populated entries, Title-only, ListTitle-only, untitled, and wrapped Title-only records. Before: all five failed strict reporting on missing keys. After: all five pass, preserving natural title order or the ID order for untitled rows. Full suite 331 passing cases, PHP lint and whitespace checks clean. Null/non-array collections, malformed wrapper values, duplicate IDs and zero-title policy are not settled by these checks. No request, database or browser validation was performed.
### Combined user-export template and real sorter validation — 12 September

Added user-export-sorting to remove the sorter double at the template boundary. Its loader requires the actual spacing and entry-sort modules, and it includes the complete user-export template in text mode. Four cases cover empty, comments-only, likes-only and combined collections. Assertions verify username text, counts, absence of phantom record lines, existing reverse-natural record order (Book 10 before Book 2 after array_reverse), and comment ampersand/literal-markup preservation.

All four passed on the current repaired source without another production edit. Full regression suite: 335 passing cases; fixture lint and whitespace checks clean. This supplies integration evidence between the real template and real sorter, not between the request action and the database. Fixtures still provide the user, counts, collection records and charset accessor. Access control, failed database reads, missing related entries and actual HTTP downloads remain outside this coverage.
### Missing user lookups reach the existing fallback cleanly — 12 September

SetUser indexed row zero unconditionally for both username and numeric-ID queries. Empty results raised Undefined array key 0 before the method could finish its existing fallback/not-found flow under strict reporting. Both row reads now coalesce an absent result to NULL. Found-user return data and numeric lookup's User #<id> label remain unchanged.

The new user-lookup fixture extracts the exact SetUser method and supplies a recording database double. Six cases cover no parameters, missing name, missing ID, found name, found ID and missing-name/valid-ID fallback. Before: the three empty-result paths failed; after: all six pass with expected query counts and return values. Full suite 341 passing cases, PHP lint and whitespace checks clean. This is not live database or HTTP/404 validation. Structured query failures remain indistinguishable from no returned row in this method and require separate error-dispatch work; malformed returned rows and lookup-parameter validation are also outside these checks.
### Reproduced user-export query failure crossing into hydration — 12 September

A temporary local probe extracted the current SetUserComments, SetUserLikesDislikes and SetLimitedRecordEntries methods. The recording boundary returned a synthetic DBAccess-shaped failure (type MySQL, error text, line marker) from RunQuery. Calling either user loader with [] reached real SetLimitedRecordEntries and threw TypeError: Cannot access offset of type string on string. The hydration loop treats error-array scalar members as record rows. No database or network operation was performed and no production repair was made in this pass.

Current DBAccess.RunQuery returns FillArraysFromDB directly; GetError builds an associative diagnostic array. Both user loaders pass that result directly into hydration. exportuser also ignores their return values. A repair must stop before hydration, propagate through the action, and distinguish server failure from missing content. AbstractBaseFormat.RunScript maps false actions to false, while Handler.HandleRequest_ServeContent proceeds to its 404/redirect path after a false content result. Merely returning FALSE or substituting [] would therefore leave misleading request behavior or silently incomplete exports.

Next evidence required: trace existing exception/error logging and content-dispatch contracts, then test a server-error outcome through the action/format/handler boundary before changing query-failure propagation. This is a reproduced open defect, not a passing regression or a completed fix. The prior 341-case suite result remains the latest completed run; no suite count changed here.
### Front-controller Throwable boundary — 12 September

The existing request catch handled Exception only and printed its raw message without setting status 500. TypeError from the reproduced user-export hydration defect bypassed it. The request catch now handles Throwable, marks the request failed, clears the current output buffer, sets 500, and invokes the existing ErrorLogging.mylog fatal path when the constructed handler exposes it. Constructor failures without a returned handler and thrown logging failures receive a plain Internal Server Error fallback. Failed requests are explicitly excluded from the subsequent cache-write branch.

request-exceptions extracts the exact request block and replaces only the library require with test dependencies. Five modes cover successful output, Exception, TypeError, constructor exception and logger exception. Before: success passed, four failure cases failed. After: all five pass, verifying status, output and failure flag. Full suite 346 passing cases; production lint and whitespace checks clean. Cache exclusion is source-verified, not exercised by this extracted block. Actual error configuration, admin authorization, database logging, headers and nested output buffers remain unverified. Existing logger backupError/indicateBackupFailure can expose diagnostics and needs separate review; this repair does not assert that every logger output is safe. Export queries still need explicit error checks before hydration; the new boundary makes such thrown request failures a defined 500 path rather than a raw exception or a false-to-404 path.
### Guard export query failures before hydration — 12 September

SetUserComments and SetUserLikesDislikes now detect DBAccess's structured line-marked query failures before either hydration helper. They throw a generic RuntimeException identifying the failed collection, without embedding the SQL/error payload in the exception message. The front-controller Throwable boundary provides the 500 path established in the preceding repair. Successful empty query results remain empty data and do not throw.

user-query-errors extracts the actual two loader methods with query/hydration doubles. Twelve cases cover both loaders, both existing limit branches, and error/empty/populated results. Errors use an empty message with a nonempty line marker. Before: all four error cases reached hydration and failed the stop-before-hydration assertion; eight controls passed. After: all twelve pass; full suite 358 passing cases. PHP lint passed; whitespace warnings on two newly inserted blank lines were corrected. Real database error payloads, action-to-front-controller integration, count-query failures and downstream hydration query failures remain separate checks. Original detailed DB diagnostics are not retained by these generic exceptions; preserving them privately requires a logging design beyond this narrow guard.
### Export query guards reach the request 500 boundary — 12 September

Added export-query-dispatch integration coverage: exact exportuser and query-loader methods, real TXT.Display and AbstractBaseFormat.RunScript, and the exact front-controller request/catch block. Handler construction/routing, database reads, hydration, counts, templates and the logger are test dependencies; the test does not start an HTTP server or query a database.

Three cases pass on the repaired source without another production change. Comment-query failure performs only one query and no hydration/count/template work. Vote-query failure stops after successful comment hydration/count and the second query, before final counts or rendering. Both produce the fixture server-error body with status 500. Successful empty collections perform both query/hydration paths, counts, attributes and template output with status 200. Full suite 361 passing cases; fixture lint and whitespace checks clean. This closes the action/format/catch propagation gap in the preceding notes, but does not prove routing, cache suppression execution, actual logger safety, count/hydration query failures or live HTTP behavior.
### Error-backup failure keeps diagnostics out of public output — 12 September

indicateBackupFailure concatenated the original diagnostic directly into an HTML response when database logging was unavailable or threw. It now prints a generic failure message and sends a JSON record to PHP error_log containing event, host and diagnostic. JSON encoding substitutes malformed UTF-8 and escapes embedded newlines, retaining host attribution in this database-unavailable fallback. Normal ISE database logging remains the primary path.

error-backup-output extracts the exact method into a namespace with error_log intercepted. Three synthetic diagnostics (plain, markup, newline) all leaked before and now pass checks for no diagnostic in response and an exact decoded server-log record. No real diagnostic or credential was used and no real log was written in the test. Full suite 364 passing cases, production lint and whitespace checks clean. Actual server-log destination, retention/access controls, error_log failure, admin diagnostic rendering and broader fatal/shutdown behavior remain unverified. This repairs the public backup-failure output noted during the Throwable-boundary audit, not every error-display path.
### Clean shutdown does not manufacture a logger warning — 12 September

ErrorLogging.shutdownHandler indexed error_get_last() without considering NULL. A request with no last error therefore generated its own Trying to access array offset on value of type null warning. The method now returns TRUE immediately when the last-error result is NULL, before tracing or classification.

error-shutdown loads the real class with registration disabled in a fixture constructor, clears the native PHP last-error state, and invokes the real shutdown method under strict warning handling. Before: the null-offset warning failed the case. After: TRUE with no log calls. Full suite 365 passing cases, production lint and whitespace checks clean. This verifies direct invocation with native clean error state, not process shutdown timing or fatal-error classification; the existing non-null switch remains unchanged.
### Anonymous error display tolerates missing request context — 13 September

The admin error-display check read HTTP_HOST/SERVER_NAME/HTTPS, the authentication cookie and query row zero unconditionally, then read an unset session variable when no cookie existed. Missing context now defaults to empty values, the session is initialized, absent rows coalesce to NULL, and the existing admin marker check uses !empty. Missing context therefore denies detailed display without additional warnings.

error-anonymous invokes the real method with logger registration disabled and database results doubled. Five cases cover no server fields, HTTP, HTTPS without cookie, missing session and nonadmin session. Before: four missing-context cases failed strict reporting; nonadmin passed. After: all five pass with FALSE, no output and expected query counts. Full suite 370 passing cases; production lint and whitespace checks clean. This does not validate authenticated admin access: the existing session-expiry predicate, join-result shape, localhost trust and raw admin diagnostic output remain explicit follow-up concerns. No cookie or session from a real user was read.
### Session expiry compares last access with current time — 13 September

Authentication.CheckCurrentAuthentication and ErrorLogging.displayErrorToAdmin used LastAccess < DATE_ADD(UserSession.LastAccess, INTERVAL N HOUR). GetRecordWhere emits the field/operator/raw expression directly, so ordinary valid timestamps satisfy that comparison regardless of age. Both predicates now require LastAccess > DATE_SUB(NOW(), INTERVAL N HOUR). Existing windows are preserved: 160 hours for authentication, four hours for diagnostics. Expiry at the exact boundary is excluded by the strict comparison.

session-expiry-query invokes both real lookup methods with a recording database double and no returned session. Before: both query-contract assertions failed. After: both constrain the supplied cookie plus the current-time threshold. Full suite 372 passing cases; both production files lint and whitespace checks clean. This validates query construction and source-reasoned SQL semantics, not execution against MySQL, timezone alignment, successful-session hydration, refresh/logout or HTTP behavior. Deployment may reject stale sessions previously accepted by the ineffective predicate. Joined result shape, diagnostic localhost trust and raw admin output remain open; no live sessions or credentials were touched.
### Successful session account initialization — 13 September

CheckCurrentAuthentication read user_session['user'] from the result list before assigning the joined User.id/Username/EmailAddress fields. GetRecords_Select delegates joined scalar aliases to EscapeMySQL.GetRecordFullTableSelectStatement, which emits flat User.field names for these fields. The nonexistent list-level user read is now an explicit empty account initialization; the established three flat account fields and session row remain unchanged.

session-account invokes the real method with no row and a representative joined row. Before: missing session passed, successful session failed strict reporting on undefined key user. After: both pass; full suite 374 passing cases, production lint and whitespace checks clean. Larger account-shape inconsistency remains: Login stores a row list, CheckCurrentAuthentication builds a flat account, RefreshAuthentication passes that flat account to Login_Successful which reads index zero, while user-panel/SimpleForms/SimpleORM consumers expect flat fields. This repair does not claim refresh/login consistency, full MySQL hydration, orphan-session rejection or database-error handling.
### Session refresh adapts the flat account to the login method contract — 13 September

RefreshAuthentication passed the flat account built by CheckCurrentAuthentication into Login_Successful, which expects a row list and immediately reads index zero. Refresh now wraps that flat account in a one-row list at the call boundary, preserving the account property consumed by user-panel callers.

session-refresh runs real RefreshAuthentication and Login_Successful with token generation, database updates and cookie writes doubled. Before: refresh failed on undefined index zero; direct list-shaped control passed. After: both pass, checking the original user identity, old-token-scoped session lookup, single update and cookie call. Full suite 376 passing cases, production lint and whitespace checks clean. No real session/token mutation occurred. Password-login property shape, Google new-user call using the old lookup variable, missing refresh defaults, failed session writes and actual refresh timing remain open. This repairs the proven refresh boundary, not all authentication callers.
### Normal session creation initializes optional state — 13 September

Login_Successful read an omitted refresh argument and, when no existing session was updated, tested an uninitialized user_session_returnable variable. Refresh tests had bypassed both gaps. The refresh checks now use !empty and the returnable session starts as NULL. Existing multi-device/reuse decisions and insert/update data remain unchanged.

session-create invokes the real method with token generation, database and cookie writes doubled. Three cases cover omitted refresh, explicit FALSE, and single-device mode with no prior session. All failed before on missing refresh or uninitialized return state. All pass after, checking one create/cookie call, correct user identity and the expected optional lookup. Full suite 379 passing cases, production lint and whitespace checks clean. No real authentication writes occurred. Failed writes, malformed accounts and concurrent session creation remain open, along with the earlier Google new-user call and account-property consistency observations.
### Failed session writes stop before issuing a cookie — 13 September

Login_Successful accepted CreateRecord's structured failure as a session and proceeded to set a cookie/report Success; update failures were indexed as result rows. Both write results now check the nonempty line marker and throw a generic RuntimeException before indexing, fallback creation or cookie mutation. The existing request Throwable boundary handles such failures.

session-write-errors invokes the real method with database/token/cookie doubles. Four cases cover create/update success/failure, including empty error text with a line marker. Before: both failure cases failed; success controls passed. After: all four pass, verifying no cookie call or handler token change on failure and no fallback insert after a failed update. Full suite 383 passing cases, production lint and whitespace checks clean. No live writes occurred. Empty successful readbacks, lookup failures, partial database writes, cookie transport failure and login-to-HTTP propagation are not established by these checks; generic exceptions do not preserve detailed DB diagnostics.
### Session lookup failure stops login persistence — 13 September

Login_Successful treated the existing-session lookup's structured error array as a populated row list. Added a line-marker check immediately after GetRecords, throwing a generic RuntimeException before indexing or writing. This complements the preceding insert/update guards.

session-lookup-error invokes the real method in single-device and refresh modes with a structured empty-message query failure. Both failed before the guard; both now produce the expected exception with one lookup, zero writes, zero cookie calls and the original handler token retained. Full suite 385 passing cases, production lint and whitespace checks clean. Database and cookie operations are doubled. Actual HTTP propagation, initial credential/session-authentication queries and successful-but-malformed results remain outside these checks. Detailed DB diagnostics are not embedded in the exception.
### Login distinguishes rejected credentials from query errors — 13 September

Login passed hashedpassword while Login_Failure read hashed_password, producing an undefined-key warning on ordinary rejection. The argument now matches. Login also treated a structured database failure as a populated account list and called Login_Successful. A line-marker guard now throws a generic RuntimeException before account assignment or success handling.

login-results invokes the real Login and Login_Failure with a database double and a recording success-method override. Before: rejection failed on the mismatched key, query error incorrectly reached success, and the accepted control passed. After: all three pass; full suite 388 passing cases, production lint and whitespace checks clean. All credential input is synthetic. This validates branch selection, not actual password verification, successful session writes, successful account-property shape or live HTTP responses. Current-session lookup errors and Google login defects remain open.
### Current-session query failures stop before account assignment — 13 September

CheckCurrentAuthentication treated DBAccess's structured error array as a populated session list and attempted row zero. It now checks the line marker immediately after the query and throws a generic RuntimeException before assigning session/account properties. Existing no-row and successful-row behavior remains covered by session-account.

session-auth-error invokes the real method with empty and nonempty error messages plus a nonempty line marker. Both failed the expected-exception/state assertions before and pass afterward, with the initially NULL account/session state retained. Full suite 390 passing cases, production lint and whitespace checks clean. No live authentication data was read. Constructor-to-HTTP failure integration, stale preexisting state, malformed/orphan session rows and Google authentication are not validated here.
### Cookie deletion expiry uses the handler clock — 13 September

Logout_ResetCookie reaches Cookie.DeleteCookieExpirationTime, which referenced an unset Cookie.time property. The other expiry helpers use handler.time. Deletion now uses that same clock while preserving the existing negative/past expiry calculation.

cookie-expiry invokes the real three expiry helpers on a constructor-free Cookie with two supplied clock values. Before: both cases failed strict reporting on the undefined time property. After: deletion matches the intended past timestamp and temporary/permanent controls remain correct. Full suite 392 passing cases, production lint and whitespace checks clean. No setcookie call, browser or session mutation was performed. SetCookie's omitted permanent option/null value/native return handling, constructor cleansing mistakes, and logout's invalid-looking LastAccess value and ignored database failure remain open; this does not establish complete logout success.
### Cookie wrapper honors optional flags and native failure — 13 September

SetCookie read secure/permanent unconditionally, passed NULL into the native string value on deletion, and discarded the native result in favor of TRUE. Optional flags now default FALSE, deletion passes an empty string after selecting its past expiry, and the method returns setcookie's boolean. Existing secure/permanent lifetime selection is preserved.

cookie-set extracts the actual wrapper with native setcookie intercepted and deterministic expiry helpers. Five cases (default, secure, permanent, deletion, native FALSE) failed before and pass after, checking every forwarded argument and the return value. Full suite 397 passing cases; PHP lint passed and new blank-line whitespace was cleaned. No real cookies were emitted. Actual browser/header acceptance and caller response to FALSE remain unverified; notably authentication currently ignores SetCookie's return value, which needs a separate failure-propagation check.
### Login honors cookie-setting failure — 13 September

Login_Successful discarded SetCookie's result and replaced handler.cookie_token/reported Success even when the wrapper returned FALSE. It now throws a generic RuntimeException on FALSE before the in-memory token assignment and success result.

session-cookie-error invokes the real login-success method with successful session creation and TRUE/FALSE cookie results. Before: FALSE still reported success and failed the case; TRUE passed. After: both pass, checking the existing token stays unchanged on failure and the cookie is attempted exactly once. Full suite 399 passing cases, production lint and whitespace checks clean. Writes/cookies are doubled. A database session already exists or has been updated before cookie emission; this repair provides no rollback, and refresh may already have invalidated the old stored token. Browser/header delivery, caller HTTP propagation and logout cookie failure remain unverified.
### Missing cookie reads return NULL without warnings — 13 September

Cookie.GetCookie directly indexed the requested key, although authentication and logout request a token that may be absent. It now coalesces an absent key to NULL, preserving the prior effective missing-value result without a warning.

cookie-read invokes the real method with absent, empty-string, string-zero and ordinary token values on a constructor-free Cookie. Before: only the missing case failed strict reporting; after: all four pass. Full suite 403 passing cases, production lint and whitespace checks clean. Cookie construction/cleansing and full anonymous authentication remain separate integration work; no browser cookies were accessed or changed.
### Cookie constructor passes its prepared cleanser arguments — 13 September

Both constructor cleansing calls referenced undefined cleanse_input_utf8_args rather than the cleanser_args array built immediately above them. They now pass the prepared input arrays. The later assignment from raw $_COOKIE is preserved pending explicit value-compatibility analysis; this is not a claim that cookie cleansing is effective.

cookie-constructor runs the real constructor with an identity cleanser double, checking empty input and two synthetic cookie values. Before: empty input passed, populated input failed on the undefined variable. After: both pass and all key/value calls receive their exact input; full suite 405 passing cases, production lint and whitespace checks clean. The actual cleanser's optional convertentities handling and PHP array-valued cookies remain untested; native browser cookies were not used. Overwriting cleansed values is still an open constructor observation.
### Real cookie cleansing chain tolerates omitted entity flag — 13 September

HandleInput.CleanseInput_EscapeBitVariableChars calls UTF8Characters.CleanseInput_UTF8 without convertentities. That helper read the optional flag directly and raised a warning. It now defaults to FALSE, preserving the prior effective no-entity-conversion branch.

cookie-real-cleanser runs real UTF8 conversion in omitted/FALSE/TRUE modes plus real Cookie construction through real HandleInput and UTF8 methods (HandleInput constructor bypassed to avoid unrelated dependencies). Before: omitted mode and cookie chain failed; explicit controls passed. After: all four pass; full suite 409 passing cases, production lint and whitespace checks clean. Cookie constructor still overwrites cleansed values with raw input, so this establishes a warning-free representative chain rather than effective input sanitization. Array-valued cookies, malformed byte sequences and full browser/request behavior remain open.
### Array-valued cookies preserve raw constructor storage — 14 September

Cookie construction attempted string-only cleansing on every cookie value, then discarded the entire cleansed array by assigning raw $_COOKIE. PHP supports array-valued cookies, so nested values caused a TypeError in mb_encode_numericentity before that final assignment. Removed the discarded cleansing loop; construction now directly stores the same raw cookie array it previously returned for successful inputs.

cookie-array covers a preference array, a nested array containing string zero, and a scalar containing an ampersand with real Cookie, HandleInput and UTF8Characters classes. Before: both array cases failed with the string-only conversion TypeError; the scalar passed. After: all three pass and preserve both the full array and GetCookie result. cookie-constructor now checks stored values rather than calls to the removed cleanser. Full suite 412 passing cases; production PHP lint and git whitespace checks passed.

This supersedes the constructor argument repair above. The cookie case in cookie-real-cleanser no longer traverses the cleanser; its three direct UTF8 flag cases still independently test the omitted/FALSE/TRUE helper behavior. This change preserves raw cookie storage and does not sanitize values or establish scalar validation at authentication sinks. Browser/request behavior and malformed authentication token arrays remain unverified.

### Authentication requires a string session token — 14 September

CheckCurrentAuthentication accepted any truthy token from handler.cookie_token or Cookie.GetCookie, forwarding arrays into the CookieToken query definition. The session lookup now requires a string as well as the existing truthy check. Invalid types follow the existing unauthenticated return path without a database lookup; normal string-token lookup and the existing empty/string-zero behavior are preserved.

session-token-type invokes the actual method with database and cookie doubles. A simple array, a nested array and a valid string are tested independently through the handler and cookie sources. Before: all four array cases failed because they reached the lookup; both valid-string controls passed. After: all six pass, with invalid inputs leaving fresh authentication state unset and valid inputs forwarding the exact token and accepting the supplied session row. Full suite 418 passing cases; Authentication PHP lint and git whitespace checks passed.

This establishes the argument boundary, not a demonstrated SQL exploit or live HTTP result. Other cookie sinks (including admin error diagnostics and logout), previously populated authentication state and database/browser integration remain separate work. No production session, cookie or database was changed.

### Administrative error output escapes diagnostic text — 14 September

ErrorLogging.displayErrorToAdmin printed the error string and print_r stack trace directly inside PRE. Diagnostic text containing HTML could close that element and introduce active markup. Both values are now escaped with ENT_QUOTES, ENT_SUBSTITUTE and ENT_HTML401 at the output boundary; print_r first returns its text so array trace formatting is preserved.

The charset comes from the existing UTF8Characters.SystemCharSet accessor. If Handler has not constructed its cleanser yet, the method uses a fresh UTF8Characters instance: Handler initializes error logging before its cleanser, and StandardLibraries loads UTF8Characters before ErrorLogging. The early-construction dependency is explicitly exercised rather than relying on a fully initialized handler.

error-display-text invokes the real display method for local and authenticated-admin paths with plain text, markup, array traces and malformed bytes. Session lookup is doubled. Before: the two plain controls passed and the six remaining cases failed. After: all eight pass, with DOM checks requiring exactly one PRE containing the expected diagnostic text, replacement characters for invalid bytes, and no script or img elements. Full suite 426 passing cases; production PHP lint and git whitespace checks passed. No browser or live database was used.

This repairs HTML output interpretation, not diagnostic access policy. Localhost trust, array-valued tokens in this separate admin-session lookup, structured lookup failures, deployed browser behavior and logging destination/access remain unresolved. Error content and traces are still intentionally visible to the same authorized display paths as before.

### Administrative diagnostic lookup requires a string token — 14 September

ErrorLogging.displayErrorToAdmin reads AuthenticationToken directly from $_COOKIE, bypassing the normal Authentication token guard. It forwarded any truthy value, including arrays, to the session query. Its separate lookup now also requires a string while preserving the existing truthy check and localhost display policy.

error-token-type exercises the real method with a database double returning an admin row. Before: simple and nested nonempty arrays incorrectly reached that lookup and failed the cases; empty-array and valid-string controls passed. After: all four pass. Invalid arrays cause no lookup, no output and FALSE; the valid string reaches the query unchanged and permits the supplied admin diagnostic. This is an input-boundary test, not evidence that a real database would authenticate an array token or that an exploit occurred. Full suite 430 passing cases; production PHP lint and git whitespace checks passed.

This closes the array-token observation for the diagnostic lookup only. Localhost trust, lookup exceptions/failures, logout token handling and deployed request behavior remain unresolved. Tests performed no live database or browser operations.

### Logout propagates session-update failures — 14 September

Authentication.Logout discarded Logout_ResetDatabase's result, cleared its in-memory session/account and returned the normal prior-session result even when UpdateRecord reported a structured error. It now checks the existing line error marker and throws a generic RuntimeException before clearing that state. Successful empty and row-list update results keep the prior behavior.

logout-write-error exercises the actual Logout and reset methods with cookie/database doubles. Before: the structured-error case failed while the empty and row-list controls passed. After: all three pass; the failure preserves the in-memory session/account and does not return the normal result. Each case checks exactly one cookie and database call. Full suite 433 passing cases; Authentication PHP lint and git whitespace checks passed.

Cookie deletion still precedes the database operation, so failure may leave a live stored token after the browser cookie has been cleared. This change is failure propagation, not atomic logout or rollback. Cookie failure handling, the malformed LastAccess reset value, invalid/missing token input and full request/browser/database behavior remain open. No live sessions or cookies were modified.

### Logout reports cookie deletion failure after revocation attempt — 14 September

Logout_ResetCookie discarded SetCookie's boolean, so Logout could report its normal result after failed cookie deletion. The helper now returns that result. Logout retains it, still attempts database revocation, and after a successful database result clears in-memory session/account state and raises a generic RuntimeException if cookie deletion returned FALSE. The existing structured database failure takes precedence if revocation fails. This avoids skipping revocation just because cookie emission failed.

logout-cookie-error invokes actual Logout/reset methods with successful database and TRUE/FALSE cookie doubles. Before: TRUE passed and FALSE failed by returning normally. After: both pass; both require exactly one database update and cookie call, with account/session state cleared following database success. Full suite 435 passing cases; production PHP lint and git whitespace checks passed.

No native cookie or database operation occurred. Browser delivery cannot be proven by setcookie returning TRUE. Database rollback/atomicity, invalid LastAccess reset value, absent/malformed logout tokens, handler token state and live request behavior remain open. This change reports partial failure and does not make logout atomic.

### Logout skips updates without a nonempty string token — 14 September

Logout_ResetDatabase sent absent, empty and array-valued cookie tokens directly into the update predicate. The logout page permits anonymous requests. The reset method now returns the established empty-success result without a database call unless its cookie token is a nonempty string. This avoids attempting revocation against an empty or malformed token; the normal string predicate is unchanged.

logout-token-type invokes the actual reset method with cookie/database doubles. Before: missing, empty, array and nested-array cases failed because they reached UpdateRecord; the valid-string control passed. After: all five pass, asserting no updates for invalid inputs and exactly one update with the exact valid token. Full suite 440 passing cases; Authentication PHP lint and git whitespace checks passed. This demonstrates the query-argument boundary, not the number of rows a real database would have affected.

Anonymous logout page result indexing, malformed LastAccess reset value, handler token state, partial-failure ordering and live browser/database behavior remain open. Tests changed no native cookies or stored sessions.

### Anonymous logout action handles absent session results — 14 September

The logout page permits anonymous requests, but Display directly indexed Userid on Logout's result. A fresh unauthenticated Authentication instance returns its NULL prior session; that caused an array-offset warning before the existing Failure status assignment. Display now uses !empty for the result Userid, retaining the prior success/failure label policy without warnings for absent data.

logout-display extracts the exact Display method between named source boundaries and invokes it with NULL, empty-array and valid-session authentication results. ORM setup and authentication are doubled. Before: NULL and empty results failed strict reporting; the session control passed. After: all three pass, checking the original result, assigned status, TRUE action result and one logout call. Full suite 443 passing cases; logout.php lint and git whitespace checks passed.

This verifies action result handling, not full template rendering, request dispatch or a live logout. The user-facing choice to call an already-logged-out state Failure is preserved. LastAccess reset validity, handler token state, partial-failure ordering and live database/browser behavior remain open.

### Logout leaves readable unauthenticated state — 14 September

Logout used unset on the declared user_session and user_account properties. Subsequent direct reads, CheckAuthenticationForCurrentObject_IsAdmin and repeated Logout read user_session without an existence guard and raised undefined-property warnings. Successful database revocation now resets both properties to NULL, matching the fresh object's unauthenticated state instead of removing the properties.

logout-state uses real Logout/reset methods and the real admin check, with persistence/cookie doubles. Before: post-logout property reads, the admin check and repeated logout all failed strict reporting. After: all three pass, requiring NULL properties, FALSE admin status and a NULL prior-session result on the repeated call. Existing failure-propagation fixtures still pass. Full suite 446 passing cases; Authentication lint and git whitespace checks passed.

These are in-process lifecycle checks, not live session revocation proof. Handler cookie_token and Cookie's raw snapshot remain separate state; LastAccess reset validity, partial-failure ordering and real request/database/browser behavior remain open. No stored sessions or native cookies were touched.

### Google sign-in forwards the created account and stops on persistence errors — 14 September

AuthenticateOrDisauthenticateWithGoogle indexed an empty account lookup directly, then in its new-user branch passed the old empty lookup array to Login_Successful instead of the newly created account. It also treated structured lookup/creation failures as account data. Empty lookups now use a guarded id check, each persistence operation checks the established line error marker, and successful creation forwards [user_creation_results] to the session method. Error guards run before authentication account assignment or login calls.

The google-account fixture invokes the actual Google orchestration method with provider, database, authentication and login-cookie doubles. Initially only the existing-account control passed; the other three cases hit undefined index zero. After fixing lookup handling, the lookup-error case passed and the new-account/creation-error cases still failed. After the handoff and creation guard repairs, all four pass: both success cases hand off the exact expected account, and persistence failures do not start login or mutate account state. Full suite 450 passing cases; Google.php lint and git whitespace checks passed.

This proves orchestration arguments and failure boundaries, not Google identity verification or real session creation. Provider payload validation, empty/malformed successful creation readback, account shape consistency, login-indicator cookie failure and full browser/database behavior remain open. No provider requests, native cookies or database writes occurred.

### Google account creation requires returned identity — 14 September

The Google creation guard previously rejected only structured database errors. A NULL creation result or row without id still changed authentication.user_account and entered Login_Successful. The guard now also requires a nonempty returned id before either action.

Expanded google-account from four to six cases with NULL readback and a row containing only EmailAddress. Before: both added cases failed by proceeding to login; after: all six pass, requiring no authentication state mutation, login or authentication recheck on the missing-identity paths. Full suite 452 passing cases; Google.php lint and git whitespace checks passed.

DBAccess.CreateRecord still directly indexes new_records[0] after readback, so an actual empty readback can first raise an undefined-index warning in that lower layer. This caller guard is not a claim that the database path is warning-free. The database write may already have succeeded: no rollback is provided. Identity-provider validation, concurrent duplicate creation and live database/browser behavior remain unverified.

### Database creation reports an empty readback — 14 September

DBAccess.CreateRecord directly returned new_records[0] after obtaining an insert ID and performing its readback. An empty readback produced an undefined-index warning and effectively NULL despite the already completed insert. It now returns GetError metadata with specifictype Readback, a nonempty line marker and an explicit message when the first returned row is absent. Existing query/readback errors and successful rows retain their prior paths.

Expanded create-query-errors with an empty-readback case. The actual CreateRecord method is extracted into a namespace with native insert-ID interception; query construction, persistence, readback and error formatting are doubled. Before: the new case failed strict reporting. After: all five creation cases pass, checking the error contract and expected operation counts. Full suite 453 passing cases; DBAccess lint and git whitespace checks passed.

GetError was source-inspected: supplied metadata overrides its default connection error text and adds provenance. Its real formatting is not executed by this fixture. No actual database operations occurred. The insert may have succeeded before readback failure; no rollback or retry was added. Zero insert-ID handling, malformed nonempty readback rows, transactions and caller recovery remain separate work.

### Readback diagnostic now executes the real error formatter — 14 September

Strengthened create-query-errors by replacing its GetError double with the exact production GetError method extracted alongside CreateRecord. The fixture supplies connection error properties and imports the global Exception class in its diagnostic namespace. The empty-readback assertion now also checks MySQL type, error number, CreateRecord function metadata and populated array/string stack traces. A deliberately different connection error text confirms the explicit readback message survives formatting.

All five creation cases and the full 453-case suite pass; git whitespace checks are clean. This supersedes the preceding note that real error formatting was only source-inspected. Native insert ID, database operations and schema helpers remain doubled; eval changes source provenance values, so exact production file/line metadata is not claimed. No production code changed in this verification pass and no live database was accessed.

### Session revalidation invalidates stale authentication state — 14 September

CheckCurrentAuthentication left an existing session/account intact when the token was absent or malformed, the lookup found no row, or the lookup returned an error. A later admin check could still see the old UserAdmin.id. The method now clears user_session, user_account and access_granted before attempting revalidation. A valid lookup rebuilds the session/account; access remains ungranted until the existing Authenticate authorization logic evaluates the current script.

session-recheck seeds an old admin session and checks missing token, array token, empty lookup, structured query error and valid-session outcomes with database/cookie doubles. Before: all four rejection/error cases retained stale state; valid passed. After: all five pass, with rejected state NULL, admin checks FALSE and operation counts correct. The fixture additionally seeds access_granted=1 and checks it resets to zero. Full suite 458 passing cases; Authentication lint and git whitespace checks passed.

Handler separately copies authentication.access_granted into Handler.access. That previously copied value is not synchronized by this repair; full mid-request authorization behavior remains unverified. Provider/session integration, orphan joined users and malformed successful rows remain open. No real session expiration or database operation was performed. This supersedes the earlier note that the current-session query error throws before any state change: it now deliberately invalidates stale state first.

### Session authentication requires the joined user to exist — 14 September

CheckCurrentAuthentication uses a LEFT JOIN from UserSession to User but accepted any nonempty session result. An orphaned session therefore authenticated with NULL account fields; a retained UserAdmin association could also satisfy the admin check. Acceptance now requires a nonempty User.id on the first returned session row before rebuilding authentication state.

session-orphan invokes the real session check and admin check with two database-double rows: a missing joined user with an admin association, and a valid joined user control. Before: the orphan case failed by authenticating; the valid control passed. After: both pass, requiring absent account/session and FALSE admin status for the orphan while preserving valid-user authentication. Full suite 460 passing cases; Authentication lint and git whitespace checks passed.

No real account deletion, database constraint or session query was exercised. This establishes acceptance behavior for the LEFT JOIN result shape, not a demonstrated production orphan. ErrorLogging has a separate admin-session lookup whose user-existence rule remains to be checked. Malformed nonempty identities, wider authorization state and live request behavior remain open.

### Admin diagnostic display requires an existing joined user — 14 September

The separate ErrorLogging session lookup used LEFT JOIN for User but granted diagnostic display solely from UserAdmin.id. An orphan session with a retained admin association could therefore expose diagnostics despite its missing user. The display condition now requires both User.id and UserAdmin.id. The existing localhost policy is unchanged.

error-orphan invokes the actual display method with missing-user and existing-user rows supplied by a database double. Before: the orphan case displayed the private fixture text and failed; valid admin passed. After: both pass, requiring FALSE and zero output for the orphan. Existing positive admin fixtures now include the User.id a valid joined account would supply. Full suite 462 passing cases; ErrorLogging lint and git whitespace checks passed.

This proves behavior for supplied join results, not the existence of an orphan in production or live database constraints. Localhost trust, query failure behavior and full request/browser validation remain open. No database records, native cookies or deployed code were changed.

### Basic RDF conversion tolerates absent associations — 14 September

ConvertHTMLToFormat read seven association keys before counting them, then directly indexed valid_record_fields for every ordinary record field. Missing associations warned on tag; records supplying NULL or empty lists reached an undefined whitelist key for id. Association counts now default absent/NULL values to empty arrays, and optional-field membership uses !empty.

rdf-empty invokes the complete real converter with absent, NULL and empty association lists, ordinary scalar metadata and a domain double. Before: all three cases failed strict reporting. After: all three pass and DOM parses each full document, checks the expected title and absence of bags. Full suite 465 passing cases; RDF lint and git whitespace checks passed.

This is basic XML well-formedness coverage, not RDF graph validation. Populated associations still emit multi-colon names and inconsistent bag capitalization; optional special fields have malformed closing markup, and scalar values/URLs remain unescaped. Non-array association values and full Display/request integration remain unverified. These structural problems remain required follow-up work; this pass does not claim general RDF correctness.

### RDF entry metadata escapes XML text — 14 September

The seven top-level entry metadata fields were concatenated directly into XML. They now use htmlspecialchars with ENT_QUOTES, ENT_XML1 and ENT_SUBSTITUTE and the shared SystemCharSet accessor. String casting preserves numeric IDs and empty NULL text behavior; values are escaped at output rather than altering stored records.

rdf-text invokes the whole converter with plain, markup-containing, multilingual and malformed-byte title/subtitle/list-title values. Before: plain and multilingual controls passed; markup and malformed bytes failed XML parsing. After: all four pass, checking decoded field text and no injected test element. Invalid bytes are replaced. Existing basic-converter fixtures now supply the charset dependency. Full suite 469 passing cases; RDF lint and git whitespace checks passed.

This does not resolve populated association element names, association scalar escaping, URL attributes, special-field closing markup or XML-forbidden control characters. DOM checks establish basic XML/text behavior, not RDF graph validity or deployed output. No stored content or production files outside the local source tree were changed.

### RDF namespace and subject URLs escape XML attributes — 14 September

The base URL was inserted raw in xmlns:entry and rdf:about. Both output sites now use XML escaping with the shared charset. rdf-url covers a normal path, a literal ampersand and a literal ampersand-entity-looking path, requiring XML parsing and exact underlying subject/namespace URL preservation. Before: only the ordinary path passed. Raw ampersands broke parsing and entity-looking text changed the interpreted URL.

After escaping, rdf:about round-tripped correctly but PHP DOM exposed namespace ampersands as numeric references (&#38;). The fixture now normalizes that single representation layer. An independent Python ElementTree check on representative escaped namespace snippets confirmed the expected namespace identities; that check is not a whole-converter test or a new suite dependency. All three URL cases and the full 472-case suite pass. RDF lint and git whitespace checks passed.

This repairs XML attribute encoding, not URL construction or normalization. Missing REDIRECT_URL, populated association structure and escaping, special-field markup, XML control characters and RDF graph validation remain open. No deployed or database state was changed.

### RDF special scalar fields use literal properties — 14 September

The special-field loop emitted invalid multi-colon nested names, a slash inside an opening name, and an outer closing tag missing its colon. It now represents each permitted scalar field as a direct entry property with escaped literal text. This retains the field name and scalar value while removing the invalid nested value wrapper. Syntax reference: https://www.w3.org/TR/rdf-syntax-grammar/ (literal property elements).

rdf-special runs the whole converter for privacypolicy, termsofservice, userdata, comments and likesdislikes supplied as scalar text containing ampersands and markup. Before: all five documents failed parsing. After: all five pass, with exactly one property under rdf:Description, matching text and no child elements. Full suite 477 passing cases; RDF lint and git whitespace checks passed.

The intended graph shape is now a direct literal rather than the old unparseable nested structure; consumers relying on textual patterns may require adjustment. No RDF graph parser is installed in the checked Python runtime, so validation is DOM parsing plus source comparison to the RDF/XML syntax. Array-valued special fields, actual script-produced field shapes, populated associations and graph/deployed integration remain open. No stored content or remote state was modified.

### RDF policy template integration and special-field shape audit — 14 September

Source inspection of the default privacy and terms templates confirms their RDF branches assign assembled HTML strings to privacypolicy and termsofservice. The user export template assigns an HTML string to userdata, but assigns arrays returned by entry-sort to comments and likesdislikes. The previous scalar fixture cases for those latter names do not represent real user export data and must not be read as coverage of that path. Current RDF string casting still cannot serialize those arrays correctly.

Added rdf-policy-template, which executes the actual default privacy and terms templates followed by the complete RDF converter. Paragraph/title providers and domain setup are doubled. Both cases pass, requiring no template stdout, the expected assembled HTML string and exact decoded RDF literal text. Full suite 479 passing cases; git whitespace checks passed. No production code changed in this pass.

Policy source generation beyond the provider doubles, full request dispatch and graph validation remain unverified. Required next serializer work includes structured comments/likesdislikes and the existing malformed populated association elements. No database or remote state was accessed.

### Populated RDF associations use valid namespaces and resource members — 14 September

All seven association branches emitted invalid names such as entry:tag:id. They now use declared prefixes such as entry_tag:id. Each association namespace combines the existing entry namespace with its association name and colon, retaining the intended name components without claiming the previous invalid XML defined a valid graph. Lowercase rdf:bag was corrected to rdf:Bag, and members containing fields now carry rdf:parseType="Resource". Syntax reference: https://www.w3.org/TR/rdf-syntax-grammar/ (containers and parseType Resource).

rdf-associations invokes the whole converter for tag, image, description, quote, textbody, eventdate and link rows. Before: all seven failed strict XML namespace/structure checks. After: all seven pass, requiring no libxml diagnostics, exactly one member in the expected association Bag, Resource parseType and its expected namespaced id. Existing quote/language mapping fixtures were updated to the new prefix spelling. Full suite 486 passing cases; RDF lint and git whitespace checks passed.

These cases use plain scalar values. Association text escaping, image URL construction, nested comments/likesdislikes arrays and malformed field values remain open. Validation uses DOM/XPath rather than an RDF graph parser; graph consumers and deployed requests are unverified. Prefix spelling changed from malformed multi-colon names, so textual consumers may need adjustment. No stored or remote data changed.

### RDF association values escape XML text — 14 September

The 47 scalar output expressions across the seven association branches now use XML htmlspecialchars flags, string conversion and the shared charset. Composed image URLs are escaped as whole text values, preserving their underlying text without claiming their path construction is correct. Existing field mappings are unchanged.

Expanded rdf-associations from seven to fourteen cases. Each association retains its plain control and adds a special-text case: Language contains ampersands, markup, multilingual text and an invalid byte in six branches; image filenames contain ampersands. Before: all seven added cases failed. After: all fourteen pass with strict parser diagnostics, retained structure, expected decoded Language text with replacement for the invalid byte, and exact decoded image/icon URL text. Existing field-mapping fixtures now supply the charset dependency. Full suite 493 passing cases; RDF lint and git whitespace checks passed.

This covers representative escaping in each branch rather than every field/value combination. Missing association fields, XML-forbidden control characters, image directory/URL encoding, structured comments/likesdislikes arrays and graph/deployed validation remain open. No stored content or remote state changed.

### RDF image URLs include stored directories and encoded filenames — 14 September

RDF image and icon URLs omitted FileDirectory and concatenated raw filenames. Both now include the character-sharded directory layout used by existing image templates and rawurlencode the filename component before XML escaping. Missing/NULL directories default to empty; a nonempty string directory is expanded with slash separators.

Strengthened the existing image special-value case in rdf-associations to use directory ab and filenames containing ampersands, spaces, fragment and query characters. Before: this case failed the exact parsed URL assertions; the unsharded control and other association cases passed. After: all fourteen cases pass, requiring the a/b/ directory and encoded filename for both image and icon. Full suite remains 493 passing cases; RDF lint and git whitespace checks passed.

No filesystem image or HTTP URL was accessed, so file existence and deployed delivery are unverified. Directory character validation, non-string filename inputs, zero-directory regression coverage, XML control characters and structured user-export arrays remain open. These changes repair URL construction independently of the prior XML escaping repair.

### RDF user-export arrays retain nested keys, types and values — 14 September

Special-field arrays now use ArrayToRDF rather than string casting. It emits nested rdf:Bag containers with resource-valued members carrying entry:key, entry:type and entry:value. These fixed property names permit arbitrary escaped array keys without turning them into XML names. Arrays recurse; scalar types and NULL are recorded explicitly, with boolean values written as true/false. Objects and resources raise an explicit exception. Existing scalar special-field output is retained.

rdf-user-export executes the actual user export template and sorter, then the real RDF converter for empty, comments-only, likes-only and combined inputs. Initial synthetic timestamps were corrected to the full database datetime shape before reproduction. Before the repair all four cases failed with array-to-string conversion. After: all four pass; the fixture reconstructs comments and likesdislikes from the XML and requires strict equality to the template-produced nested arrays, including entry/parent data, string zero, FALSE and NULL. It also checks the userdata HTML string survives as literal text and that the template emits no stray output. Full suite 497 passing cases; RDF lint and git whitespace checks passed.

This introduces an explicit key/type/value vocabulary for formerly unserializable arrays. External RDF consumers may need to understand it; no graph parser or consumer was executed. Bag order is not a semantic ordering guarantee, though keys are retained. Cyclic or extremely deep arrays, nonfinite floats, invalid-byte exact preservation, XML-forbidden controls and live request/database behavior remain unverified. No stored or remote data changed.

### RDF replaces XML-forbidden control characters — 14 September

Existing RDF escaping handled markup and malformed encoding but retained XML-illegal characters such as NUL and vertical tab. Added ENT_DISALLOWED alongside the existing XML1 and substitution flags throughout this serializer's escaping boundaries. Disallowed characters become replacement characters in exported output; stored values are unchanged.

Expanded rdf-text with forbidden controls and legal tab/newline whitespace. Before: the forbidden-controls case failed XML parsing while legal whitespace passed. After: both added cases and all six metadata cases pass, checking expected decoded text. Full suite 499 passing cases; RDF lint and git whitespace checks passed.

Representative dynamic coverage is at the metadata boundary; the same flags were applied to association, special-field, array and URL escaping sites, but every codepoint/site combination was not separately tested. Replacement is deliberately lossy, not exact binary preservation. URL semantic validity, RDF graph consumers, deep/cyclic arrays and live request integration remain open.

### Independent RDF graph parser validates 37 converter documents — 14 September

Added optional rdf-graph-check.py. It instruments the existing seven RDF fixture sources in memory to capture real converter output while still executing their original assertions. It then parses those documents with RDFLib and checks representative title and RDF Bag type triples. All 37 documents pass: three basic, six metadata, three URL, five scalar special-field, two policy-template, fourteen association and four user-template outputs.

Validated with Python 3.12.10 and RDFLib 7.6.0 installed in a temporary dependency directory. The initial sandboxed pip attempt failed temporary-file permissions; an authorized escalated retry succeeded. No global Python installation was changed. The optional diagnostic is documented separately from the 499-case PHP runner, whose dependency requirements are unchanged. No production source changed in this verification pass; git whitespace checks passed.

This supersedes prior notes that these representative RDF documents lacked graph-parser validation. RDFLib accepts their RDF/XML syntax and expected representative triples. Complete graph equivalence, consumer-specific vocabulary support, ordering requirements, adversarial/deep values and deployed request behavior remain unverified. The checker does not fetch URLs or touch stored content.
