# Principles

Why this project makes the choices it makes. Read this before deciding *where*
to fix something, as distinct from *how*.

## Be beneficent to the Internet as a whole

**This project exists to help the community and to be beneficent to the
Internet community as a whole.**

That is a design constraint, not a sentiment. It decides real engineering
questions, and the clearest example is where a malformed request gets repaired.

## Repair what arrives; do not punish the sender

The ordinary engineering instinct is to fix a defect where it is encoded — find
the code emitting the bad thing, fix that, and let everything downstream assume
correctness. That instinct is right when you control every producer.

You do not control every producer of URLs pointing at your site.

Anyone, anywhere, can mistype a query parameter. A forum can mangle a link. A
mail client can wrap one. A CMS on another continent can append `?action=browse`
to a URL that already had a query string. None of those people did anything
wrong that you can reach, and none of them will ever see your bug tracker.

So when a request arrives malformed in a way whose intent is unambiguous,
**repair it and serve the page.** Do not redirect — that spends a round trip
teaching someone a lesson they cannot act on. Do not refuse — that turns
someone else's typo into your error page.

Worked example, from the 30 August 2026 session:

```
view.pdf?mobilefriendly=1?mobilefriendly=1?action=browse
```

A URL carries one `?`. Every later one is a separator that should have been
`&`. `QUERY_STRING` is by definition everything after the *first* `?`, so any
`?` still inside it can only be a mistake — the detection is exact, not a
guess. `Handler::Construct_RepairQueryString()` folds them, re-parses `$_GET`,
and the visitor gets their page. That single small change fixes the URL for
every mistyping human and every broken link generator on the internet at once,
which no amount of fixing our own link construction could do.

This is the robustness principle, and `Handler` is where it lives. The class
already existed to handle `mailto:` paths, `~user` paths, doubled slashes,
copy-paste artefacts, and links with a trailing `)` or `.` from someone
pasting out of prose. Query separator repair is the same job.

## Repair is not a substitute for finding the cause

Repairing on the way in is correct **and** the generator still wants finding.
Both are true, and skipping the second is how a codebase accumulates a
permanent layer of compensations for bugs nobody ever located.

The rule: repair so that *other people's* mistakes cost your visitors nothing.
Then go find whether one of the producers was you. Record it if you cannot fix
it that day — see [../Docs/Triage.md](../Docs/Triage.md).

## Where this principle stops

Be liberal about **form**. Never be liberal about **authority**.

* Repair a malformed separator. Do not repair a malformed permission check.
* Normalise a path. Do not normalise it *after* validating it — that is how
  `%2e%2e%2f` becomes `../` and escapes a directory. `PageCache::SafePath()`
  rejects percent signs outright for exactly this reason.
* Accept a request that means something obvious. Do not guess at a request that
  means two things, and never guess at one that might mean something
  destructive.

Ambiguity is the line. If a repair requires deciding *which* of two plausible
things the sender meant, redirect or refuse instead.

## Cost the fix honestly

Prefer the smallest change that helps the most people. In the worked example
above, three methods in one class did more for real visitors than auditing
twenty years of link construction would have — and the audit is still worth
doing, just not first, and not while the site is on fire.
