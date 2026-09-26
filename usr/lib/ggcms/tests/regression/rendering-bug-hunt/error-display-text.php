<?php
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new ErrorException($message, 0, $level); });
require dirname(__DIR__,3).'/src/traits/LogRedaction.php';require dirname(__DIR__, 3) . '/src/classes/Error/ErrorLogging.php';
require dirname(__DIR__, 3) . '/src/classes/Charset/UTF8Characters.php';
class TextDisplayLogger extends ErrorLogging { public function __construct() {} }
$failed = 0;
foreach(['local', 'admin'] as $mode) {
 foreach(['plain'=>['ordinary error', 'ordinary trace'], 'markup'=>['</PRE><script>alert(1)</script>', '<img src=x onerror=alert(2)>'], 'array'=>['A & B', ['argument'=>'</PRE><script>alert(3)</script>']], 'bytes'=>["bad \xFF", "trace \xFF"]] as $name=>$values) {
  $_SERVER = $mode === 'local' ? ['HTTP_HOST'=>'localhost', 'SERVER_NAME'=>'localhost'] : ['HTTP_HOST'=>'example.test', 'SERVER_NAME'=>'example.test', 'HTTPS'=>'on'];
  $_COOKIE = ['AuthenticationToken'=>'fixture-token'];
  $db = new class { public function GetRecords($args) { return [['User.id'=>7,'UserAdmin.id'=>7]]; } };
  $logger = new TextDisplayLogger();
  // No cleanser: error logging starts before Handler constructs it.
  $logger->handler = (object)['db_access'=>$db];
  ob_start();
  try {
   $result = $logger->displayErrorToAdmin(['error'=>$values[0], 'stack_trace'=>$values[1]]);
   $output = ob_get_clean();
   $dom = new DOMDocument();
   $previous = libxml_use_internal_errors(TRUE);
   $dom->loadHTML('<?xml encoding="UTF-8">' . $output);
   libxml_clear_errors(); libxml_use_internal_errors($previous);
   $pre = $dom->getElementsByTagName('pre');
   $expected = str_replace("\xFF", "\xEF\xBF\xBD", $values[0] . PHP_EOL . print_r($values[1], TRUE));
   $ok = $result === TRUE && $pre->length === 1 && $pre->item(0)->textContent === $expected && $dom->getElementsByTagName('script')->length === 0 && $dom->getElementsByTagName('img')->length === 0;
  } catch(Throwable $error) { if(ob_get_level()) { ob_end_clean(); } $ok = FALSE; echo $error->getMessage() . PHP_EOL; }
  echo ($ok ? 'PASS ' : 'FAIL ') . 'error text ' . $mode . ' ' . $name . PHP_EOL;
  if(!$ok) { $failed++; }
 }
}
exit($failed ? 1 : 0);
