<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Error/ErrorLogging.php';
class AnonymousLogger extends ErrorLogging {public function __construct(){}}
$failed=0;
foreach(['missing-server','http','https-no-cookie','https-missing-session','https-nonadmin'] as $mode){
 $_SERVER=$mode==='missing-server'?[]:['HTTP_HOST'=>'example.test','SERVER_NAME'=>'example.test'];
 if(strpos($mode,'https')===0){$_SERVER['HTTPS']='on';}
 $_COOKIE=in_array($mode,['https-missing-session','https-nonadmin'])?['AuthenticationToken'=>'fixture-token']:[];
 $db=new class{public $rows=[],$calls=0;public function GetRecords($args){$this->calls++;return $this->rows;}};
 if($mode==='https-nonadmin'){$db->rows=[['id'=>1,'UserAdmin.id'=>NULL]];}
 $logger=new AnonymousLogger();$logger->handler=(object)['db_access'=>$db];
 ob_start();try{$result=$logger->displayErrorToAdmin(['error'=>'private diagnostic','stack_trace'=>'private trace']);$output=ob_get_clean();$ok=$result===FALSE&&$output===''&&$db->calls===(count($_COOKIE)?1:0);}catch(Throwable $error){ob_end_clean();$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'anonymous error display '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);