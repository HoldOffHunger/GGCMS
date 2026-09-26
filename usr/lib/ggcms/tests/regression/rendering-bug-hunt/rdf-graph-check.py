"""Optional independent RDF graph validation. Requires rdflib and PHP on PATH."""
import base64
from pathlib import Path
import subprocess
from rdflib import Graph, URIRef
from rdflib.namespace import RDF

root = Path(__file__).resolve().parent
fixtures = ['rdf-empty', 'rdf-text', 'rdf-url', 'rdf-special', 'rdf-policy-template', 'rdf-associations', 'rdf-user-export']
count = 0
for name in fixtures:
    source = (root / (name + '.php')).read_text(encoding='utf-8')
    directory_literal = "'" + root.as_posix().replace("'", "\\'") + "'"
    source = source.replace('__DIR__', directory_literal)
    source = source.replace('$rdf->ConvertHTMLToFormat()', 'captureRDF($rdf)')
    hook = '''function captureRDF($rdf) {
 $output=$rdf->ConvertHTMLToFormat();
 echo "GRAPH " . base64_encode($output) . PHP_EOL;
 return $output;
}
'''
    source = source.replace('<?php', hook, 1)
    result = subprocess.run(['php', '-r', source], capture_output=True, text=True, encoding='utf-8')
    if result.returncode or result.stderr or 'FAIL ' in result.stdout:
        raise RuntimeError(name + ': ' + result.stdout + result.stderr)
    documents = [base64.b64decode(line[6:]) for line in result.stdout.splitlines() if line.startswith('GRAPH ')]
    if not documents:
        raise RuntimeError(name + ': no converter output captured')
    for document in documents:
        graph = Graph().parse(data=document, format='xml')
        subjects = set(graph.subjects(URIRef('https://example.test/example/view.phpTitle'), None))
        if name != 'rdf-user-export' and name != 'rdf-url' and not subjects:
            raise AssertionError(name + ': expected entry title triple missing')
        if name in ('rdf-associations', 'rdf-user-export') and not list(graph.subjects(RDF.type, RDF.Bag)):
            raise AssertionError(name + ': expected RDF Bag type triple missing')
        count += 1
    print('PASS RDF graph ' + name + ': ' + str(len(documents)) + ' documents')
print('Parsed RDF graphs:', count)
