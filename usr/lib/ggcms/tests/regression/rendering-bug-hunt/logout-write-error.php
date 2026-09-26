<?php
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new ErrorException($message, 0, $level); });
require dirname(__DIR__, 3) . '/src/classes/Security/Authentication.php';
$failed = 0;
foreach(['empty'=>[], 'row'=>[['id'=>8]], 'error'=>['line'=>123, 'error'=>'fixture failure']] as $name=>$response) {
 $db = new class { public $response; public $calls=0; public function UpdateRecord($args) { $this->calls++; return $this->response; } };
 $db->response = $response;
 $cookie = new class { public $calls=0; public function GetCookie($args) { return 'fixture-token'; } public function SetCookie($args) { $this->calls++; return TRUE; } };
 $auth = new Authentication(['handler'=>(object)['db_access'=>$db, 'cookie'=>$cookie]]);
 $session = ['id'=>8, 'Userid'=>7]; $account = ['id'=>7];
 $auth->user_session = $session; $auth->user_account = $account;
 $caught = FALSE; $result = NULL;
 try { $result = $auth->Logout(); } catch(RuntimeException $error) { $caught = TRUE; }
 $ok = $name === 'error'
  ? ($caught && $auth->user_session === $session && $auth->user_account === $account)
  : (!$caught && $result === $session && !isset($auth->user_session) && !isset($auth->user_account));
 $ok = $ok && $db->calls === 1 && $cookie->calls === 1;
 echo ($ok ? 'PASS ' : 'FAIL ') . 'logout write ' . $name . PHP_EOL;
 if(!$ok) { $failed++; }
}
exit($failed ? 1 : 0);
