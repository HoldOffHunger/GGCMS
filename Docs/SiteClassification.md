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

## Nothing reads it yet, and that is the point

This is a record, not a behaviour. It was added because the question gets asked
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

## Where the values live

In the private configuration repository, in each site's `etc/ggcms/<host>.php`.
Not here: this file describes the mechanism, the configuration holds the facts.

To see what a site currently reports:

```php
$this->handler->globals->SiteClassification()
```
