# AbstractGlobals

The configuration refactor, in progress. Started before September 2026,
half-ported, and currently the only config mechanism that is actually growing.

## The thinking

The author's, in his own words:

> Problem: Enough customization in globals will just, make globals unwieldy at
> some point in time in the future.
>
> Solution: Only load globals based on the scripts that are actually being
> accessed. A global for search should not get loaded for a contact page.

And on why the config files are PHP rather than JSON or anything declarative:

> We just need functionality in these configs, it's that simple. I spent a
> while thinking about it, trust me, being able to do a simple for loop is a
> godsend compared to the madness of making the equivalent in json or some
> other such fiasco.

That second decision is load-bearing and should not be revisited casually. A
declarative config format that grows conditionals, interpolation and
inheritance ends up being a bad programming language embedded in a data format.
Config here is code, and override is plain class extension, which gives
per-method granularity for free — something no deep-merge of JSON does cleanly.

## How it loads

`Handler::Construct_Globals()` builds the old monolith, then constructs
`AbstractGlobals`, which runs five builders. Each looks for a shared default
under `clonefrom/`, then a per-domain override under the **reverse-DNS**
directory name — `revoltlib.com` becomes `com.revoltlib` — and names the class
by suffix as it descends:

```
clonefrom/formats/specific/pdf.php   AbstractGlobals_Formats_GivenRequestedFormat_override
com.revoltlib/formats/specific/pdf.php   ..._override_client extends ..._override
com.revoltlib/formats/specific/asp.php   ..._override          (no shared file beneath it)
```

A domain-only file takes the `_override` slot directly, because there is no
shared class for it to extend. The loader must allow for that.

## State of the five builders

| Builder | State |
|---|---|
| `buildAbstractGlobals_Scripts()` | Correct. Used for the dictionary in `aa822a7`. |
| `buildAbstractGlobals_Language_Scripts_specific()` | Correct. Passes the domain down through the `$args` hash. |
| `buildAbstractGlobals_ChildTypes()` | Fixed in `ffd040d`, which restored 2,496 revoltlib images the ORM had never been asked to fetch. |
| `buildAbstractGlobals_Formats_LinkTo()` | **Broken.** Three faults, below. |
| `buildAbstractGlobals_Formats_Specific()` | **Broken.** Two faults, below. |

### `_Formats_LinkTo()`

1. Reads `$primary_domain_lowercased`, an undefined local — it is assigned in a
   different function. The path never resolves.
2. The override branch `confreq`s the **shared** file a second time instead of
   the domain one, so `..._override` is never defined and the `new` below it
   would fatal if the path ever did resolve. The two faults mask each other.
3. Tests with `is_file()` rather than `conf_isfile()`, so the relative config
   path would not resolve even with a correct domain.

### `_Formats_Specific()`

1. The same undefined `$primary_domain_lowercased`, used for the `_client` path.
2. The domain file is only reachable when a `clonefrom` file for that extension
   exists first. A domain-only extension can never load, which is most of them.

### What that costs today

These files exist, are deliberate, and have never once been loaded:

```
com.anarchistcode/formats/link_to.php
com.revoltlib/formats/link_to.php
com.revoltlib/formats/specific/  asp aspx cfm cgi dll do htm html jspx pdf
                                 php3 php4 phtml pl py rb rhtml shtml wss xhtml
```

Nineteen legacy URL extensions — revoltlib's handling for inbound links from
whatever the site was before it was GGCMS. Every old `.html` and `.shtml` URL
anyone ever linked to.

## What is left in the monolith

`etc/ggcms/clonefrom.php` is 681 lines and 82 methods. `Construct_Globals()`
looks for a per-domain `com.<host>.php` beside it; **none of the seventeen
exist**, so every site runs `defaultglobals` unmodified. There is nothing in the
old scheme to preserve — only the monolith to take apart.

### Clearly script-scoped, and the obvious next ports

| Cluster | Count | Belongs to |
|---|---|---|
| `MainMenu`, `MainMenu_Enabled_*`, `MainMenu_URL_*` | 23 | its own area — a quarter of the file is one concern |
| `TitleAmericanize`, `TitleAutoSmartTitleCase`, `TitleDeRomanizeNumbers`, `AutoGenerateTitleDefault`, and the List/Sub variants | 12 | `modify` |
| `ModifiableFieldDisplay`, `ShowModifiableFields`, `RequiredFieldDepths` | 3 | `modify` |
| `RSSFeeds`, `defaultNewsItems`, `defaultNewsItems_RSS`, `maxNewsItemsAllowed`, `minNewsItemsAllowed` | 5 | feeds |
| `AssociationEntryCodes*`, `MaxParent*Depth`, `buildRecordTreeForEntryid_MaxDepth`, `IndexMaxRandomChildren`, `IndexPullChildRecordStats` | 7 | `view` / `search` |

### Genuinely every-page

`isProductionSite`, `EnableErrorLogging`, `useDBFileCache`,
`SetSQLModePerSession`, `OverrideDatabaseName`, `Styling`,
`UseHeaderRedirects`, `AddEntryHTMLFormatting`.

That is eight, against eighty-two.

### Not needed by any page

`IPv4Addresses`, `IPv6Addresses`, `NameServers`, `PrimaryHostInfo`,
`WebHostServices`, `EncryptionAuthority`,
`CertificateAuthorityAdminEmailAddress`.

These are deployment facts consumed by `cli/scripts/internal/domain/`. They are
constructed on every page view of every site and read by none of them. Worth
their own home outside the per-request path entirely.

## The fragile joint

Class names are assembled as strings at load time — `$classname .= '_override'`
then `new $classname`. When a file does not declare exactly the expected name,
the failure is a fatal with nothing useful in it, and both remaining bugs live
at precisely that joint.

A `class_exists()` check before every `new $classname`, reporting the expected
name, would turn that class of failure into a sentence. It is the same shape as
the `function_exists()` guard added to the `Script/PHP.php` registry in
`9ed5388`.

## Loose ends

* `etc/ggcms/holdoffhunger.com/` is a stray. The loader builds reverse-DNS
  paths, so only `com.holdoffhunger/` is reachable; the forward-named directory
  holds a byte-identical copy of `scripts/about.php` and nothing else.
* Whether to activate the nineteen revoltlib extensions is a behavioural
  decision, of the same kind as `ffd040d`. Fix the loader first, then read the
  list of what activates before deploying.
