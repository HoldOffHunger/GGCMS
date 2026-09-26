<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Security/Authentication.php';
$failed=0;
foreach([FALSE,TRUE] as $user_exists){
 $row=['id'=>8,'Userid'=>7,'User.id'=>$user_exists?7:NULL,'User.Username'=>$user_exists?'Reader':NULL,'User.EmailAddress'=>$user_exists?'reader@example.test':NULL,'UserAdmin.id'=>9];
 $db=new class {public $row;public function GetRecords($args){return [$this->row];}};$db->row=$row;
 $auth=new Authentication(['handler'=>(object)['cookie_token'=>'fixture-token','db_access'=>$db]]);
 $result=$auth->CheckCurrentAuthentication();
 $ok=$user_exists?($result===TRUE && $auth->user_session===$row && $auth->user_account['id']===7 && $auth->CheckAuthenticationForCurrentObject_IsAdmin()===TRUE)
  :($result===0 && $auth->user_session===NULL && $auth->user_account===NULL && $auth->CheckAuthenticationForCurrentObject_IsAdmin()===FALSE);
 echo ($ok?'PASS ':'FAIL ').'session joined user exists='.(int)$user_exists.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);
