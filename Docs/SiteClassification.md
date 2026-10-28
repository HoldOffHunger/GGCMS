# Site Classification

`SiteClassification()` records what kind of site a domain is. It returns
`'commercial'`, `'revolutionary'`, or `'unstated'`, and it is set per site in
that site's configuration class.

```php
class globals extends defaultglobals {
    public function SiteClassification() {
        return 'commercial';
    }
}
```

Two conveniences come with it, so callers do not compare strings:

```php
$this->handler->globals->SiteIsCommercial()
$this->handler->globals->SiteIsRevolutionary()
```

## A record first, a behaviour second

This is a record before it is a behaviour. The first thing to read it is the
house ads (`javascript/house-ads.js`), which choose what a site may advertise
from its classification and shape, and print nothing for a site where either
is unstated. It was added because the question gets asked
constantly and answered from memory every time:

- can this site sit behind a third party that terminates its TLS?
- does it carry advertising?
- does it want analytics, and whose analytics?
- if this traffic were readable by someone else, who is exposed?

Those are the same question wearing different clothes, and the answer never
changes for a given site. Writing it down once means the next decision starts
from a fact rather than from a recollection.

Behaviour can grow on top later. The first candidate is already in the engine:
`Format/HTML.php` carries an advertising script inside
`if($this->domain_object->host === 'earthfluent')`, which is a "this is a
commercial site" decision written as a domain comparison. That is the shape of
thing this replaces.

## The two are not opposites

Nothing prevents a revolutionary site from also being commercial. It has not
happened yet, which is why a single value is enough for now. When it does, this
becomes a set and the accessors above keep working unchanged — which is the
reason they exist rather than callers testing the string.

## `unstated` is a real answer

The default is `'unstated'`, and a site nobody has classified should say so.

Inheriting a guess would be worse than saying nothing, because a guess here
reads as a decision later — and the direction that matters is asymmetric.
Wrongly marking a revolutionary site commercial could put its traffic somewhere
it should not be. Wrongly marking a commercial site revolutionary costs an
advertising slot. Only one of those is worth being careful about, so the safe
default is to refuse to answer.

For the same reason, classifications are set only from an explicit decision by
the site's owner. They are not inferred from the site's name, its content, or
its neighbours in the configuration directory.

## Site shape

`SiteShape()` is the companion record: what shape the site's content takes.
It returns `'onepage'`, `'multipage'`, `'snowflake'`, `'dictionary'`, or
`'unstated'`.

- **onepage** — a tool whose whole site is one Entry record.
- **multipage** — an ordinary tree of entries.
- **snowflake** — many pages whose branches repeat one pattern; on a language
  site, every language carries the same lessons for the same words. It counts
  as multi-page.
- **dictionary** — pages fed by the shared `dictionaries` database rather than
  the site's own Entry table, so `/x/` is the word *x*. The site's Entry table
  may hold a single record. It counts as multi-page.

```php
$this->handler->globals->SiteIsOnePage()
$this->handler->globals->SiteIsMultiPage()   // TRUE for snowflake and dictionary too
```

The same rule applies as for classification: the owner decides, and
`'unstated'` is the default. A site's Entry count is evidence for the ruling,
not the ruling — a site can hold one Entry and serve many pages from other
tables.

## Where the values live

In the private configuration repository, in each site's `etc/ggcms/<host>.php`.
Not here: this file describes the mechanism, the configuration holds the facts.

To see what a site currently reports:

```php
$this->handler->globals->SiteClassification()
```
