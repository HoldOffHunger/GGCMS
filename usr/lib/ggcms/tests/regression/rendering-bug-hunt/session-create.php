<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Security/Authentication.php';
class CreateSessionFixture extends Authentication {public $multiple=TRUE;public function GenerateCookieToken($args){return 'fixture-token';}public function AllowMultipleDeviceLogin(){return $this->multiple;}}
$failed=0;
foreach(['missing-refresh','explicit-false','single-device'] as $mode){
 $db=new class{public $queries=0,$creates=[];public function GetRecords($args){$this->queries++;return [];}public function CreateRecord($args){$this->creates[]=$args;return ['id'=>8,'Userid'=>7,'CookieToken'=>'fixture-token'];}};
 $cookie=new class{public $calls=[];public function SetCookie($args){$this->calls[]=$args;}};
 $auth=new CreateSessionFixture(['handler'=>(object)['db_access'=>$db,'cookie'=>$cookie]]);$auth->multiple=$mode!=='single-device';
 $args=['useraccount'=>[['id'=>7,'Username'=>'Reader']]];if($mode==='explicit-false'){$args['refresh']=FALSE;}
 try{$result=$auth->Login_Successful($args);$ok=$result['status']==='Success'&&$result['usersession']['Userid']===7&&count($db->creates)===1&&count($cookie->calls)===1&&$db->queries===($mode==='single-device'?1:0);}
 catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'session create '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);