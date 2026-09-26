<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Security/Authentication.php';
class SessionLookupFixture extends Authentication {public function GenerateCookieToken($args){return 'new-token';}public function AllowMultipleDeviceLogin(){return FALSE;}}
$failed=0;
foreach([FALSE,TRUE] as $refresh){
 $db=new class{public $writes=0,$queries=0;public function GetRecords($args){$this->queries++;return ['line'=>123,'error'=>''];}public function UpdateRecord($args){$this->writes++;return [];}public function CreateRecord($args){$this->writes++;return [];}};
 $cookie=new class{public $calls=0;public function SetCookie($args){$this->calls++;}};
 $handler=(object)['db_access'=>$db,'cookie'=>$cookie,'cookie_token'=>'old-token'];$auth=new SessionLookupFixture(['handler'=>$handler]);$auth->user_session=['Userid'=>7,'CookieToken'=>'old-token'];
 $caught=FALSE;
 try{$auth->Login_Successful(['useraccount'=>[['id'=>7,'Username'=>'Reader']],'refresh'=>$refresh]);}catch(RuntimeException $error){$caught=TRUE;}catch(Throwable $error){}
 $ok=$caught&&$db->queries===1&&$db->writes===0&&$cookie->calls===0&&$handler->cookie_token==='old-token';
 echo ($ok?'PASS ':'FAIL ').'session lookup error refresh='.(int)$refresh.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);