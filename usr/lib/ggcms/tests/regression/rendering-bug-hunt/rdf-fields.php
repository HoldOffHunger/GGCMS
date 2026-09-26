<?php
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new ErrorException($message, 0, $level); });
$source = file_get_contents(dirname(__DIR__, 3) . '/src/classes/Format/RDF.php');
$cases = [
 ['marker'=>'<entry_quote:Quote>', 'data'=>['Quote'=>'Actual quotation', 'Description'=>'Wrong description'], 'expected'=>'Actual quotation', 'wrong'=>'Wrong description', 'variable'=>'quote'],
 ['marker'=>'<entry_link:Language>', 'data'=>['Language'=>'fr', 'URL'=>'https://example.test/'], 'expected'=>'fr', 'wrong'=>'https://example.test/', 'variable'=>'link'],
];
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Charset/UTF8Characters.php';
$format=new AbstractBaseFormat();$format->handler=(object)['cleanser'=>(object)['utf8_characters'=>new UTF8Characters()]];
$failed = 0;
foreach($cases as $case) {
 foreach([FALSE, TRUE] as $empty) {
  $lines = array_values(array_filter(explode("\n", $source), fn($line)=>strpos($line, $case['marker']) !== FALSE));
  if(count($lines) !== 1) { throw new Exception('Expected unique output statement'); }
  ${$case['variable']} = $case['data'];
  if($empty) { ${$case['variable']}[$case['variable'] === 'quote' ? 'Quote' : 'Language'] = ''; }
  $rdf_body = (function($line, $values) { extract($values); $rdf_body = ''; eval($line); return $rdf_body; })->call($format, $lines[0], [$case['variable']=>${$case['variable']}]);
  $expected = $empty ? '' : $case['expected'];
  $ok = strpos($rdf_body, $case['marker'] . $expected . '</') !== FALSE && strpos($rdf_body, $case['wrong']) === FALSE;
  echo ($ok ? 'PASS' : 'FAIL') . ' ' . $case['variable'] . ' empty=' . (int)$empty . PHP_EOL;
  if(!$ok) { $failed++; }
 }
}
exit($failed ? 1 : 0);
?>