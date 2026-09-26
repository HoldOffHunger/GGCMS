<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Security/Authentication.php';
class RefreshFixture extends Authentication {public $token_account;public function GenerateCookieToken($args){$this->token_account=$args['useraccount'];return 'replacement-token';}}
$failed=0;
foreach(['refresh','list-control'] as $mode){
 $db=new class{public $queries=[],$updates=[];public function GetRecords($args){$this->queries[]=$args;return [['id'=>8,'Userid'=>7,'CookieToken'=>'old-token']];}public function UpdateRecord($args){$this->updates[]=$args;return [['id'=>8,'Userid'=>7,'CookieToken'=>'replacement-token']];}};
 $cookie=new class{public $calls=[];public function SetCookie($args){$this->calls[]=$args;}};
 $auth=new RefreshFixture(['handler'=>(object)['db_access'=>$db,'cookie'=>$cookie]]);
 $account=['id'=>7,'Username'=>'Reader','EmailAddress'=>'reader@example.test'];$auth->user_account=$account;$auth->user_session=['Userid'=>7,'CookieToken'=>'old-token'];
 try{$result=$mode==='refresh'?$auth->RefreshAuthentication():$auth->Login_Successful(['useraccount'=>[$account],'refresh'=>1]);
 $ok=$result['status']==='Success'&&$result['useraccount']===$account&&$auth->token_account===$account&&count($db->updates)===1&&count($cookie->calls)===1&&$db->queries[0]['definition']===['Userid'=>7,'CookieToken'=>'old-token'];}
 catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'session '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);