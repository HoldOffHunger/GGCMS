<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Security/Authentication.php';
$failed=0;
foreach(['','fixture failure'] as $message){
 $db=new class{public $message;public function GetRecords($args){return ['line'=>123,'error'=>$this->message];}};$db->message=$message;
 $issues=new class{public $logged=[];public function createLog($args){$this->logged[]=$args;return TRUE;}};
 $auth=new Authentication(['handler'=>(object)['cookie_token'=>'fixture-token','db_access'=>$db,'issue_logging'=>$issues]]);
 $caught=FALSE;$result=NULL;try{$result=$auth->CheckCurrentAuthentication();}catch(Throwable $error){$caught=TRUE;}
 $ok=!$caught&&$result===0&&$auth->user_session===NULL&&$auth->user_account===NULL&&count($issues->logged)===1&&$issues->logged[0]['issuetype']==='Session Lookup Failed';
 echo ($ok?'PASS ':'FAIL ').'current session query error message-length='.strlen($message).PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);