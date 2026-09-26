<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Security/Authentication.php';
require dirname(__DIR__,3).'/src/classes/Error/ErrorLogging.php';
class ExpiryLogger extends ErrorLogging {public function __construct($args){$this->handler=$args['handler'];}}
$failed=0;
foreach(['authentication'=>160,'diagnostics'=>4] as $mode=>$hours){
 $db=new class{public $queries=[];public function GetRecords($args){$this->queries[]=$args;return [];}};
 $handler=(object)['cookie_token'=>'fixture-token','db_access'=>$db];
 $_SERVER=['HTTP_HOST'=>'example.test','SERVER_NAME'=>'example.test','HTTPS'=>'on'];$_COOKIE=['AuthenticationToken'=>'fixture-token'];
 if($mode==='authentication'){(new Authentication(['handler'=>$handler]))->CheckCurrentAuthentication();}
 else{(new ExpiryLogger(['handler'=>$handler]))->displayErrorToAdmin(['error'=>'fixture','stack_trace'=>'fixture']);}
 $query=$db->queries[0];
 $ok=count($db->queries)===1 && $query['definition']['CookieToken']==='fixture-token' && $query['definition']['RAW']['LastAccess']===['>','DATE_SUB(NOW(), INTERVAL '.$hours.' HOUR)'];
 echo ($ok?'PASS ':'FAIL ').'session expiry query '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);