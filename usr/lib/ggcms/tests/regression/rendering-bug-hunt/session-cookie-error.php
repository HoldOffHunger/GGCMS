<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Security/Authentication.php';
class CookieLoginFixture extends Authentication {public function GenerateCookieToken($args){return 'new-token';}}
$failed=0;
foreach([FALSE,TRUE] as $cookie_success){
 $db=new class{public $creates=0;public function CreateRecord($args){$this->creates++;return ['id'=>8,'Userid'=>7];}};
 $cookie=new class{public $success,$calls=0;public function SetCookie($args){$this->calls++;return $this->success;}};$cookie->success=$cookie_success;
 $handler=(object)['db_access'=>$db,'cookie'=>$cookie,'cookie_token'=>'old-token'];$auth=new CookieLoginFixture(['handler'=>$handler]);$caught=FALSE;$result=NULL;
 try{$result=$auth->Login_Successful(['useraccount'=>[['id'=>7,'Username'=>'Reader']]]);}catch(RuntimeException $error){$caught=TRUE;}
 $ok=$db->creates===1&&$cookie->calls===1&&($cookie_success?(!$caught&&$result['status']==='Success'&&$handler->cookie_token==='new-token'):($caught&&$handler->cookie_token==='old-token'));
 echo ($ok?'PASS ':'FAIL ').'session cookie success='.(int)$cookie_success.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);