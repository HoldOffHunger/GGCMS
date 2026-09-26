<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Security/Authentication.php';
class SessionWriteFixture extends Authentication {public function GenerateCookieToken($args){return 'new-token';}}
$failed=0;
foreach(['create','update'] as $operation){foreach([FALSE,TRUE] as $failure){
 $db=new class{public $failure,$creates=0;public function GetRecords($args){return [['id'=>8,'Userid'=>7,'CookieToken'=>'old-token']];}public function UpdateRecord($args){return $this->failure?['line'=>123,'error'=>'']:[['id'=>8,'Userid'=>7]];}public function CreateRecord($args){$this->creates++;return $this->failure?['line'=>123,'error'=>'']:['id'=>8,'Userid'=>7];}};$db->failure=$failure;
 $cookie=new class{public $calls=0;public function SetCookie($args){$this->calls++;}};
 $handler=(object)['db_access'=>$db,'cookie'=>$cookie,'cookie_token'=>'old-token'];$auth=new SessionWriteFixture(['handler'=>$handler]);$auth->user_session=['Userid'=>7,'CookieToken'=>'old-token'];
 $caught=FALSE;$unexpected=FALSE;
 try{$result=$auth->Login_Successful(['useraccount'=>[['id'=>7,'Username'=>'Reader']],'refresh'=>$operation==='update']);}catch(RuntimeException $error){$caught=TRUE;}catch(Throwable $error){$unexpected=TRUE;}
 $ok=!$unexpected && ($failure?($caught&&$cookie->calls===0&&$handler->cookie_token==='old-token'):(!$caught&&$result['status']==='Success'&&$cookie->calls===1&&$handler->cookie_token==='new-token')) && $db->creates===($operation==='create'?1:0);
 echo ($ok?'PASS ':'FAIL ').'session write '.$operation.' failure='.(int)$failure.PHP_EOL;if(!$ok){$failed++;}
}}
exit($failed?1:0);