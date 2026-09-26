# Rendering bug-hunt regression diagnostics

From the repository root:

```text
php usr/lib/ggcms/tests/regression/rendering-bug-hunt/run.php
```

Validated with PHP 8.1.34 CLI on Windows. Requires mbstring, SimpleXML, DOM/libxml and enabled
proc_open. Each diagnostic runs in a separate PHP process because fixtures
sometimes define minimal base classes. Feed fixtures get theirs from
`abstract-base-format-stub.php`, which lifts `XMLText()` and `XMLEscape()` out
of the real `AbstractBaseFormat.php` so escaping is tested as shipped. Paths
resolve from this directory.
The runner checks exit status, stderr and the expected PASS count, detecting
premature die/exit even when PHP reports exit code zero.

These 499 cases preserve the focused diagnostics from 10-14 September. Some invoke real
methods with dependency doubles; others extract exact methods or output blocks
from source. They do not open a database, perform network requests or delete
real records/files. The suite is independent of the existing PHPUnit suite.

Coverage includes RSS self-links and RSS/Atom absent images and date fallbacks,
OPDS image XML/MIME fields, RDF field mappings, portable/HTML failed-action
handling, false database execution results and deletion failure propagation, plus ASCII/multilingual Link and EventDate length boundaries and EventDate label checks.
See Docs/RenderingBugHunt.md at the repository root for before/after evidence
and each fixture's limits.

Passing does not prove full HTTP rendering, complete feed/RDF conformance,
XML escaping, MySQL behaviour, transaction rollback, filesystem coordination
or production readiness. Earlier 8 September diagnostics are described in the
ledger but are not reconstructed or counted by this runner. PHP versions other
than 8.1 have not been verified; dynamic-property diagnostics can differ.
Optional independent RDF graph check:

```text
python usr/lib/ggcms/tests/regression/rendering-bug-hunt/rdf-graph-check.py
```

Requires PHP on PATH and RDFLib importable by Python; it is not part of the dependency-light
PHP runner. Validated with Python 3.12.10 and RDFLib 7.6.0 (installed separately in a temporary
directory and exposed through PYTHONPATH). The checker instruments fixture source in memory
to capture actual converter documents, reruns their PHP assertions, and parses all 37 outputs
as RDF/XML. It checks representative title and RDF Bag triples. It does not contact a database
or service, and it does not prove external consumer compatibility or all graph semantics.
