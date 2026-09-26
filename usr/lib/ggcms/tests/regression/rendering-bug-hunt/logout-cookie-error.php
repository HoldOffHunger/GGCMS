<?php
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new ErrorException($message, 0, $level); });
require dirname(__DIR__, 3) . '/src/classes/Security/Authentication.php';
$failed = 0;
foreach([TRUE, FALSE] as $cookie_result) {
 $db = new class { public $calls=0; public function UpdateRecord($args) { $this->calls++; return []; } };
 $cookie = new class { public $result; public $calls=0; public function GetCookie($args) { return 'fixture-token'; } public function SetCookie($args) { $this->calls++; return $this->result; } };
 $cookie->result = $cookie_result;
 $auth = new Authentication(['handler'=>(object)['db_access'=>$db, 'cookie'=>$cookie]]);
 $session = ['id'=>8, 'Userid'=>7]; $auth->user_session = $session; $auth->user_account = ['id'=>7];
 $caught = FALSE; $result = NULL;
 try { $result = $auth->Logout(); } catch(RuntimeException $error) { $caught = TRUE; }
 $ok = $cookie_result ? (!$caught && $result === $session) : ($caught && $result === NULL);
 $ok = $ok && $db->calls === 1 && $cookie->calls === 1 && !isset($auth->user_session) && !isset($auth->user_account);
 echo ($ok ? 'PASS ' : 'FAIL ') . 'logout cookie result=' . (int)$cookie_result . PHP_EOL;
 if(!$ok) { $failed++; }
}
exit($failed ? 1 : 0);
