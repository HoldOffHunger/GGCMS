# clonefrom

`clonefrom.com` is a real domain Ben owns, and it is also the reference
installation this engine clones from. It is deliberately public. Everything a
stranger needs to stand up a GGCMS site is in this repository; everything that
describes one of *his* live sites is not.

## What is public, and where

| What | Where | Size |
|---|---|---|
| Reference site configuration | `etc/ggcms/clonefrom.php` and `etc/ggcms/clonefrom/` | 66 files |
| Reference template set | `usr/lib/ggcms/src/templates/default/` | 842 files |
| Reference database | `usr/lib/ggcms/cli/sql/clonefrom.sql` | 40 KB |

The config tree carries one directory per thing a site can decide:

```
clonefrom/child_types        which child record types this site stores
clonefrom/error              error message globals
clonefrom/formats            default format, link_to, and specific/<format>.php
clonefrom/language_scripts   language handling
clonefrom/record_relations   which relational walks the templates actually need
clonefrom/scripts            per-script overrides
clonefrom/site               the site's own identity
```

`formats/specific/` has one file per output format -- `atom.php`, `brf.php`,
`css.php` and the rest -- which is what makes adding a format to a new site a
matter of configuration rather than code.

## What is private, and why the split holds

`.gitignore` does this with an allowlist rather than a blocklist, which is the
only version that stays correct as sites are added:

```
etc/ggcms/*
!etc/ggcms/clonefrom/
!etc/ggcms/clonefrom.php
usr/lib/ggcms/src/templates/*
!usr/lib/ggcms/src/templates/default/
```

Everything is excluded; two paths are named back in. A new site added tomorrow
is private by default and nobody has to remember to exclude it.

The seventeen live sites' configuration and templates live in the private
repository instead, deployed to the same paths on the host. Per-domain files
holding database credentials, `etc/ggcms/com.<site>.php`, exist only on the
host and are in neither repository.

## The rule

**clonefrom is the default, so it is public. Anything that names a real site is
not.** If a new install would need it, it belongs here; if it describes
revoltlib, it belongs in the private repository.

Checked on 3 September 2026 and the split is intact -- the reference material is
complete and present, and nothing under `etc/ggcms` or `templates` names a live
site.
