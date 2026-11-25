# GGCMS script map

Regenerate the shared map from the repository root with:

```sh
php usr/lib/ggcms/cli/script_map.php --output=Docs/Scripts.md
```

Access describes the script-level guards for HTML. Entry visibility, action checks, and site settings can further restrict an individual request.
Actions are public zero-argument methods with a matching default template, the default display method, or a known non-template action recorded in the catalogue.
HTML cache indicates membership in the engine allowlist. Request and response checks still apply. See `Docs/PageCache.md` for details.

## Reading pages

| Script | Purpose | Access | HTML cache | Actions |
| --- | --- | --- | --- | --- |
| `Homepage.php` | Redirects an entry’s legacy Homepage route to its view. | Anyone | — | Display |
| `chapterify.php` | Prepares an entry and its children for chapter-oriented presentation. | Logged in | — | Display |
| `image_src.php` | Redirects the legacy image source route to the image browser. | Anyone | — | Display |
| `image.php` | Browses and paginates an entry’s images. | Anyone | — | Display |
| `images.php` | Redirects the plural legacy route to the image browser. | Anyone | — | Display |
| `index.php` | Redirects the legacy index script to the entry view. | Anyone | — | Display |
| `languages.php` | Lists the site’s languages. | Anyone | — | display |
| `router.php` | Redirects the legacy router script to the entry view. | Anyone | — | Display |
| `search.php` | Searches entries and displays matching records. | Anyone | — | display |
| `searchlink.php` | Searches entries and prepares matching links with their context. | Anyone | — | display |
| `shop.php` | Redirects the legacy shop script to the entry view. | Anyone | — | Display |
| `view.php` | Displays, browses, and indexes entries, including dictionary views and votes. | Anyone | Eligible | browse, browseAssociated, browseByTag, definitions, dictionary, display, downvote, index, Select, undodownvote, undoupvote, upvote, viewUserLikes |

## Editing

| Script | Purpose | Access | HTML cache | Actions |
| --- | --- | --- | --- | --- |
| `convertspelling.php` | Converts submitted text between British and American spelling. | Anyone | — | Display |
| `formmaker.php` | Lists and creates forms. | Admin | — | display, list, newform |
| `importer.php` | Accepts structured imports and prepares entries and uploads for saving. | Admin | — | display, Import, Preview |
| `modify.php` | Adds, edits, and manages entry records. | Logged in | — | Add, Display, Edit, Save, Update |
| `transfer.php` | Searches for a target parent and moves an entry to it. | Admin | — | display, search, transferentry |
| `upload.php` | Redirects the legacy upload route to the add-entry form. | Anyone | — | Display |

## Accounts

| Script | Purpose | Access | HTML cache | Actions |
| --- | --- | --- | --- | --- |
| `install.php` | Redirects the legacy install route to login. | Anyone | — | Display |
| `login.php` | Displays login and handles authentication. | Anyone | — | Authenticate, Display |
| `logout.php` | Ends the current sign-in session. | Anyone | — | Display |
| `register.php` | Redirects the legacy registration route to login. | Anyone | — | Display |
| `signup.php` | Redirects the legacy signup route to login. | Anyone | — | Display |
| `user-panel.php` | Shows the signed-in user’s submissions and their status. | Logged in | — | display |
| `user.php` | Displays the user entry point and handles its login action. | Anyone | — | Display, login |
| `users.php` | Lists users and supports viewing or exporting user information. | Anyone | — | browseComments, browseLikes, display, exportuser, viewuser |
| `wp-login.php` | Redirects the WordPress-style login route to GGCMS login. | Anyone | — | Display |

## Formats and feeds

| Script | Purpose | Access | HTML cache | Actions |
| --- | --- | --- | --- | --- |
| `feed.php` | Redirects the legacy feed route to news. | Anyone | — | Display |
| `new.php` | Redirects the legacy new-content route to news. | Anyone | — | Display |
| `news.php` | Renders recent entries and feed documentation. | Anyone | — | display, docs |
| `robots.php` | Builds robots directives and their browsable representation. | Anyone | — | display |
| `sitemap.php` | Renders the site’s sitemap index and paginated URL lists. | Anyone | — | display |
| `style.php` | Renders the stylesheet associated with a requested style and action. | Conditional | — | Display |

## Admin and status

| Script | Purpose | Access | HTML cache | Actions |
| --- | --- | --- | --- | --- |
| `datautilities.php` | Identifies duplicate keys in submitted lists. | Anyone | — | Display, findDuplicateArrayKeys |
| `dbstatus.php` | Provides database diagnostics and lookup-list maintenance. | Admin | — | ArchivesURLDetect, ArchiveURLDetect, ClonePrimaryHostDatabase, CopyPasteIssuesURLsDetect, CreateList, CustomDelimitedDBSDetect, DeactivatedSiteDetect, DetectAndFixBlankListTitles, DetectAndFixBritishSpellings, DetectBadText, DetectBlankImageFields, DetectEntryCodeLength, DetectMissingAssociations, DetectMissingDates, DetectMissingDescriptions, DetectMissingImageFiles, DetectMissingImages, DetectMissingLinks, DetectMissingQuotes, DetectMissingTags, DetectMissingTextBodies, Display, DumpTables, EditList, EntriesMissingChildAdjectives, EntriesMissingChildNouns, EntriesMissingChildNounsPlural, EntriesMissingGrandChildAdjectives, EntriesMissingGrandChildNouns, EntriesMissingGrandChildNounsPlural, EntriesMissingListTitles, EntriesMissingListTitleSortKeys, EntrySymmetryCheck, FixBlankImageFields, FixMissingImages, GenerateSearchEngineSitemapSubmissionLinks, GetCleanupDBMySQL, ImportList, KillDisconnectedImages, MySQLDBEquivalent, ShowTablesListNames, ShowTablesListSchemasTable, ViewAllLists, ViewAllListsAndItems, ViewAllPrimaryHosts, ViewMySQLCharacterSetsTable, ViewMySQLCollationCharacterSetApplicabilityTable, ViewMySQLCollationsTable, ViewMySQLCollationTable, ViewMySQLColumnPrivilegesTable, ViewMySQLColumnsTable, ViewMySQLDatabases, ViewMySQLEnginesTable, ViewMySQLEventsTable, ViewMySQLFilesTable, ViewMySQLFunctionStatusTable, ViewMySQLGlobalStatusTable, ViewMySQLGlobalVariablesTable, ViewMySQLInformationSchemaTables, ViewMySQLKeyColumnUsageTable, ViewMySQLOpenTablesTable, ViewMySQLOptimizerTraceTable, ViewMySQLParametersTable, ViewMySQLPartitionsTable, ViewMySQLPluginsTable, ViewMySQLProcedureStatusTable, ViewMySQLProcessListTable, ViewMySQLProfilingTable, ViewMySQLReferentialConstraintsTable, ViewMySQLRoutinesTable, ViewMySQLSchemaPrivilegesTable, ViewMySQLSchemataTable, ViewMySQLSessionStatusTable, ViewMySQLSessionVariablesTable, ViewMySQLStatisticsTable, ViewMySQLStatusTable, ViewMySQLTableConstraintsTable, ViewMySQLTablePrivilegesTable, ViewMySQLTableSpacesTable, ViewMySQLTablesTable, ViewMySQLTableStatusTable, ViewMySQLTriggersTable, ViewMySQLUserPrivilegesTable, ViewMySQLVariablesTable, ViewMySQLViewsTable, ViewPrimaryHostSEOStatus, ViewPrimaryHostStatus |
| `languageutils.php` | Cleans and transforms lists of words and other language data. | Admin | — | display |
| `master-c.php` | Runs specialized administrative content and image maintenance. | Admin | — | Display, Special |
| `peerblocklist.php` | Produces a PeerBlock-compatible list. | Anyone | — | display |
| `phpinfo.php` | Displays PHP runtime diagnostics. | Anyone | — | Display |
| `ping.php` | Checks a URL through the administrative ping tools. | Admin | — | Curl, Display |
| `redirect.php` | Provides redirect handling for sign-in and secure connections. | Disabled | — | Display, redirecttoasecuredconnection, redirecttologin |
| `security.php` | Defines an internal security script with access disabled. | Disabled | — | Display |
| `social.php` | Provides Instagram sign-in and posting actions. | Anyone | — | display, login_instagram, post_instagram |
| `systemstatus.php` | Displays system and runtime diagnostic views. | Admin | — | ApacheGetEnv, ApacheGetModules, ApacheGetVersion, ApacheLookupURI, ApacheRequestHeaders, ApacheResponseHeaders, AssertOptions, BaseConvertBase, BinaryDecrementBinaryValue, BinaryIncrementBinaryValue, BoolVal, CheckDNSRR, ClassExists, CLIGetProcessTitle, Display, DNSGetRecord, EmptyFunc, ExtensionLoaded, FloatVal, FuncGetArgs, FuncNumArgs, FunctionExists, GCEnabled, GetAllHeaders, GetCalledClass, GetCFGVar, GetClassMethods, GetClassVars, GetCurrentUser, GetDeclaredClasses, GetDeclaredInterfaces, GetDeclaredTraits, GetDefinedConstants, GetDefinedFunctions, GetDefinedVars, GetExtensionFuncs, GetHostByAddr, GetHostByName, GetHostByNameL, GetHostName, GetIncludedFiles, GetIncludePath, GetLastMod, GetLoadedExtensions, GetMagicQuotesGPC, GetMagicQuotesRuntime, GetMXRR, GetMyGid, GetMyInode, GetMyUID, GetOpt, GetParentClass, GetProtoByName, GetProtoByNumber, GetRequiredFiles, GetResourceType, GetRUsage, GetServByName, GetServByPort, GetType, Hash, HashAlgos, HashFile, HeadersList, HeadersSent, HTTPResponseCode, INetNTop, INetPTon, INIGetAll, InterfaceExists, IntVal, IP2Long, IsArray, IsBool, IsCallable, IsDouble, IsFloat, IsLong, IsNull, IsNumeric, IsObject, IsResource, IsScalar, IsString, IsSubclassOf, Long2IP, MemoryGetPeakUsage, MemoryGetUsage, MethodExists, MHash, MHashCount, MHashGetBlockSize, MHashGetHashName, PHPCredits, PHPCredits_IFrameDisplay, PHPInfo, PHPInfo_IFrameDisplay, PHPINILoadedFile, PHPINIScannedFiles, PHPSapiName, PHPUname, PHPVersion, PropertyExists, RandomBytes, RandomInt, ShowMasterVariables, SysGetTempDir, TraitExists, ViewCookieVariable, ViewGetVariable, ViewPostVariable, ViewServerVariable |
| `userstatus.php` | Reviews comments and saves moderation changes. | Admin | — | display, PageStatistics, ReviewAcceptedComments, ReviewAcceptedSuggestions, ReviewComments, ReviewRejectedComments, ReviewRejectedSuggestions, ReviewSuggestions, SaveCommentChanges |
| `warroom.php` | Shows administrative statistics across hosts. | Admin | — | acceptComment, acceptSuggestion, Display, rejectComment, rejectSuggestion, resolveError, viewComment, viewError, viewSuggestion |

## Static pages

| Script | Purpose | Access | HTML cache | Actions |
| --- | --- | --- | --- | --- |
| `about.php` | Describes the site or current collection. | Anyone | — | display |
| `codeofconduct.php` | Displays the site’s code of conduct. | Anyone | — | display |
| `contact.php` | Displays the site’s contact information. | Anyone | — | display |
| `hackers.php` | Displays credits for people who built or contributed to the site. | Anyone | — | Display |
| `humans.php` | Displays the human-readable site credits. | Anyone | — | Display |
| `play.php` | Provides word search, crossword, guessing, and typing games. | Anyone | — | CrosswordPuzzle, Display, TypingGame, WordGuessGame, WordSearch |
| `privacy.php` | Displays the site’s privacy policy. | Anyone | — | display |
| `pronounce.php` | Looks up pronunciations and displays a result. | Anyone | — | display |
| `randomwords.php` | Displays randomly selected words. | Anyone | — | display |
| `return-policy.php` | Displays the site’s return policy when enabled. | Anyone | — | display |
| `spellchecker.php` | Displays the spelling-check interface. | Anyone | — | display |
| `suggest.php` | Displays and handles the suggestion form. | Anyone | — | display |
| `terms.php` | Displays the site’s terms of service. | Anyone | — | display |

## Format support

These bases support the routed scripts. Access and actions come from the selected script.

| File under `scripts/Format/` | Purpose | Format cache |
| --- | --- | --- |
| `base_format.php` | Supplies the common script and format base class. | — |
| `ATOM/basicscript.php` | Produces Atom syndication output. | — |
| `BRF/basicscript.php` | Produces Braille Ready Format output. | Eligible |
| `CSS/basicscript.php` | Builds stylesheet output. | Eligible |
| `CSV/basicscript.php` | Produces comma-separated records. | — |
| `DAISY/basicscript.php` | Produces DAISY digital talking book output. | — |
| `EPub/basicscript.php` | Produces EPUB ebook output. | — |
| `HTML/basicscript.php` | Provides browser page rendering and HTML text formatting. | Eligible |
| `JSON/basicscript.php` | Produces JSON output. | — |
| `OPDS/basicscript.php` | Produces an OPDS publication catalog. | — |
| `PDF/basicscript.php` | Produces printable PDF output. | — |
| `RDF/basicscript.php` | Produces RDF metadata output. | — |
| `RSS/basicscript.php` | Produces RSS syndication output. | — |
| `RTF/basicscript.php` | Produces Rich Text Format output. | — |
| `SGML/basicscript.php` | Produces SGML output. | — |
| `TEX/basicscript.php` | Produces TeX output. | — |
| `TXT/basicscript.php` | Produces plain-text output. | — |
| `XML/basicscript.php` | Produces XML output. | — |

## Private overlay

For a local inventory that includes configuration overrides, pass `--overlay=CONFIG_REPO` and write the result outside the public checkout.
