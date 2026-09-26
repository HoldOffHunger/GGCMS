<?php
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new ErrorException($message, 0, $level); });
require dirname(__DIR__,3).'/src/traits/LogRedaction.php';require dirname(__DIR__, 3) . '/src/classes/Error/ErrorLogging.php';
require dirname(__DIR__, 3) . '/src/classes/Charset/UTF8Characters.php';
class TokenDisplayLogger extends ErrorLogging { public function __construct() {} }
$failed = 0;
foreach(['array'=>['token'], 'nested'=>['token'=>['value']], 'empty'=>[], 'valid'=>'fixture-token'] as $name=>$token) {
 $_SERVER = ['HTTP_HOST'=>'example.test', 'SERVER_NAME'=>'example.test', 'HTTPS'=>'on'];
 $_COOKIE = ['AuthenticationToken'=>$token];
 $db = new class {
  public $calls = [];
  public function GetRecords($args) { $this->calls[] = $args; return [['User.id'=>7,'UserAdmin.id'=>7]]; }
 };
 $logger = new TokenDisplayLogger(); $logger->handler = (object)['db_access'=>$db];
 ob_start();
 try {
  $result = $logger->displayErrorToAdmin(['error'=>'private diagnostic', 'stack_trace'=>'private trace']);
  $output = ob_get_clean();
  $ok = is_string($token)
   ? ($result === TRUE && strpos($output, 'private diagnostic') !== FALSE && count($db->calls) === 1 && $db->calls[0]['definition']['CookieToken'] === $token)
   : ($result === FALSE && $output === '' && $db->calls === []);
 } catch(Throwable $error) { if(ob_get_level()) { ob_end_clean(); } $ok = FALSE; echo $error->getMessage() . PHP_EOL; }
 echo ($ok ? 'PASS ' : 'FAIL ') . 'diagnostic token ' . $name . PHP_EOL;
 if(!$ok) { $failed++; }
}
exit($failed ? 1 : 0);
