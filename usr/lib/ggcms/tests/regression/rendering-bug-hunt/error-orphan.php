<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Error/ErrorLogging.php';
require dirname(__DIR__,3).'/src/classes/Charset/UTF8Characters.php';
class OrphanLogger extends ErrorLogging {public function __construct(){}}
$failed=0;
foreach([FALSE,TRUE] as $exists){
 $_SERVER=['HTTP_HOST'=>'example.test','SERVER_NAME'=>'example.test','HTTPS'=>'on'];
 $_COOKIE=['AuthenticationToken'=>'fixture-token'];
 $db=new class {public $row;public function GetRecords($args){return [$this->row];}};
 $db->row=['User.id'=>$exists?7:NULL,'UserAdmin.id'=>9];
 $logger=new OrphanLogger();$logger->handler=(object)['db_access'=>$db];
 ob_start();$result=$logger->displayErrorToAdmin(['error'=>'private diagnostic','stack_trace'=>'private trace']);$output=ob_get_clean();
 $ok=$exists?($result===TRUE && strpos($output,'private diagnostic')!==FALSE):($result===FALSE && $output==='');
 echo ($ok?'PASS ':'FAIL ').'diagnostic joined user exists='.(int)$exists.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);
