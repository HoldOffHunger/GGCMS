<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Security/Authentication.php';
class LoginResultFixture extends Authentication {public $success_calls=0;public function Login_Successful($args){$this->success_calls++;return ['status'=>'Success','useraccount'=>$args['useraccount']];}}
$failed=0;
foreach(['rejected','query-error','accepted'] as $mode){
 $db=new class{public $rows,$queries=0;public function GetRecords($args){$this->queries++;return $this->rows;}};
 $db->rows=$mode==='query-error'?['line'=>123,'error'=>'']:($mode==='accepted'?[['id'=>7,'Username'=>'Reader']]:[]);
 $auth=new LoginResultFixture(['handler'=>(object)['db_access'=>$db]]);$caught=FALSE;$unexpected=FALSE;$result=NULL;
 try{$result=$auth->Login(['username'=>'Reader','password'=>'synthetic-input']);}catch(RuntimeException $error){$caught=TRUE;}catch(Throwable $error){$unexpected=TRUE;echo $error->getMessage().PHP_EOL;}
 $ok=!$unexpected&&$db->queries===1&&($mode==='query-error'?($caught&&$auth->success_calls===0):(!$caught&&$result['status']===($mode==='accepted'?'Success':'Failure')&&$auth->success_calls===($mode==='accepted'?1:0)));
 echo ($ok?'PASS ':'FAIL ').'login result '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);