<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Security/Authentication.php';
$failed=0;
foreach([FALSE,TRUE] as $found){
 $row=['id'=>8,'Userid'=>7,'User.id'=>7,'User.Username'=>'Reader','User.EmailAddress'=>'reader@example.test'];
 $db=new class{public $rows;public function GetRecords($args){return $this->rows;}};$db->rows=$found?[$row]:[];
 $auth=new Authentication(['handler'=>(object)['cookie_token'=>'fixture-token','db_access'=>$db]]);
 try{$result=$auth->CheckCurrentAuthentication();$ok=$found?($result===TRUE && $auth->user_session===$row && $auth->user_account===['id'=>7,'Username'=>'Reader','EmailAddress'=>'reader@example.test']):($result===0 && $auth->user_session===NULL && $auth->user_account===NULL);}
 catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'session account found='.(int)$found.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);