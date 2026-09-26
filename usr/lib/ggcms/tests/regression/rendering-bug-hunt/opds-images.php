<?php
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new ErrorException($message, 0, $level); });
libxml_use_internal_errors(TRUE);
$source = file_get_contents(dirname(__DIR__, 3) . '/src/classes/Format/OPDS.php');
$start = strpos($source, '$images =');
$end = strpos($source, '$opds_body .= \'  </entry>', $start);
if($start === FALSE || $end === FALSE) { throw new Exception('Source block not found'); }
$block = substr($source, $start, $end - $start);
$render = eval('return function() { $opds_body = ""; $extension_mimetypes = ["png"=>"image/png", "jpg"=>"image/jpeg"]; ' . $block . ' return $opds_body; };');
$failures = 0;
foreach([NULL, [], [['FileDirectory'=>'ab12', 'FileName'=>'cover & title.png', 'IconFileName'=>'thumb.jpg']], [['FileDirectory'=>'ab12', 'FileName'=>'cover & title.png', 'IconFileName'=>'thumb.jpg'], ['FileDirectory'=>'cd34', 'FileName'=>'second.jpg', 'IconFileName'=>'second.png']]] as $images) {
 $context = (object)[
  'script'=>(object)['record_to_use'=>['image'=>$images]],
  'domain_object'=>new class { public function GetPrimaryDomain($args) { return 'https://example.test'; } },
 ];
 $xml = '<entry xmlns="http://www.w3.org/2005/Atom">' . $render->bindTo($context, NULL)() . '</entry>';
 $doc = new DOMDocument();
 $valid = $doc->loadXML($xml);
 $count = count($images ?? []);
 $ok = $valid && $doc->documentElement->childNodes->length >= 0;
 if($valid) {
  $xpath = new DOMXPath($doc); $xpath->registerNamespace('a', 'http://www.w3.org/2005/Atom');
  $ok = $xpath->query('/a:entry/a:link')->length === $count * 2;
  foreach($images ?? [] as $index=>$image) {
   $links = $xpath->query('/a:entry/a:link');
   $expected_types = ['png'=>'image/png', 'jpg'=>'image/jpeg'];
   foreach(['FileName', 'IconFileName'] as $offset=>$field) {
    $node = $links->item($index * 2 + $offset);
    $expected = $expected_types[pathinfo($image[$field], PATHINFO_EXTENSION)];
    $ok = $ok && $node->getAttribute('type') === $expected;
    $expected_href = 'https://example.test/image/' . implode('/', str_split($image['FileDirectory'])) . '/' . rawurlencode($image[$field]);
    $ok = $ok && $node->getAttribute('href') === $expected_href;
   }
  }
 }
 echo ($ok ? 'PASS' : 'FAIL') . ' image-count=' . $count . ' XML, sibling links and MIME types' . PHP_EOL;
 if(!$ok) { $failures++; }
 libxml_clear_errors();
}
$context->script->record_to_use = [];
try {
 $output = $render->bindTo($context, NULL)();
 $ok = $output === '';
} catch(Throwable $error) {
 $ok = FALSE;
 echo $error->getMessage() . PHP_EOL;
}
echo ($ok ? 'PASS' : 'FAIL') . ' missing image association produces no image links' . PHP_EOL;
if(!$ok) { $failures++; }
exit($failures ? 1 : 0);
?>