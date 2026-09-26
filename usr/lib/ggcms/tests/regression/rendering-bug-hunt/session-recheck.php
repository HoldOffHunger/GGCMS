<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Security/Authentication.php';
$failed=0;
foreach(['missing-token','invalid-token','missing-session','query-error','valid'] as $mode){
 $row=['id'=>8,'Userid'=>7,'User.id'=>7,'User.Username'=>'Reader','User.EmailAddress'=>'reader@example.test','UserAdmin.id'=>7];
 $db=new class {public $rows,$calls=0;public function GetRecords($args){$this->calls++;return $this->rows;}};
 $db->rows=$mode==='valid'?[$row]:($mode==='query-error'?['line'=>123,'error'=>'fixture failure']:[]);
 $cookie=new class {public function GetCookie($args){return NULL;}};
 $token=$mode==='missing-token'?NULL:($mode==='invalid-token'?['token']:'fixture-token');
 $issues=new class{public $logged=0;public function createLog($args){$this->logged++;return TRUE;}};
 $auth=new Authentication(['handler'=>(object)['cookie_token'=>$token,'cookie'=>$cookie,'db_access'=>$db,'issue_logging'=>$issues]]);
 $auth->user_session=$row;$auth->user_account=['id'=>7];$auth->access_granted=1;
 $caught=FALSE;$result=NULL;
 try{$result=$auth->CheckCurrentAuthentication();}catch(RuntimeException $error){$caught=TRUE;}
 $ok=$mode==='valid'?($result===TRUE && !$caught && $auth->user_session===$row && $auth->CheckAuthenticationForCurrentObject_IsAdmin()===TRUE)
  :(!$caught && $result===0 && $issues->logged===($mode==='query-error'?1:0) && $auth->user_session===NULL && $auth->user_account===NULL && $auth->CheckAuthenticationForCurrentObject_IsAdmin()===FALSE);
 $ok=$ok && $auth->access_granted===0 && $db->calls===(in_array($mode,['missing-token','invalid-token'])?0:1);
 echo ($ok?'PASS ':'FAIL ').'session recheck '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);
