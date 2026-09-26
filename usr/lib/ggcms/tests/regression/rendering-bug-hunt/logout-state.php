<?php
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new ErrorException($message, 0, $level); });
require dirname(__DIR__, 3) . '/src/classes/Security/Authentication.php';
$failed=0;
foreach(['read', 'admin', 'repeat'] as $mode) {
 $db=new class { public function UpdateRecord($args) { return []; } };
 $cookie=new class { public function GetCookie($args) { return 'fixture-token'; } public function SetCookie($args) { return TRUE; } };
 $auth=new Authentication(['handler'=>(object)['db_access'=>$db, 'cookie'=>$cookie]]);
 $auth->user_session=['id'=>8, 'Userid'=>7, 'UserAdmin.id'=>7]; $auth->user_account=['id'=>7];
 try {
  $auth->Logout();
  if($mode==='read') { $ok=$auth->user_session===NULL && $auth->user_account===NULL; }
  elseif($mode==='admin') { $ok=$auth->CheckAuthenticationForCurrentObject_IsAdmin()===FALSE; }
  else { $ok=$auth->Logout()===NULL; }
 } catch(Throwable $error) { $ok=FALSE; echo $error->getMessage().PHP_EOL; }
 echo ($ok?'PASS ':'FAIL ').'post logout state '.$mode.PHP_EOL;
 if(!$ok) { $failed++; }
}
exit($failed?1:0);
