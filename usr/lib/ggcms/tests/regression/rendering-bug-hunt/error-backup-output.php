<?php
namespace ErrorBackupFixture;
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new \ErrorException($message,0,$level);});
$source=file_get_contents(dirname(__DIR__,3).'/src/classes/Error/ErrorLogging.php');
$start=strpos($source,'public function indicateBackupFailure(');$end=strpos($source,'public function indicateError(',$start);
if($start===FALSE||$end===FALSE){throw new \Exception('Method boundaries missing');}
eval('namespace ErrorBackupFixture; class Logger {'.substr($source,$start,$end-$start).'}');
function error_log($message){$GLOBALS['captured_logs'][]=$message;return TRUE;}
$failed=0;
foreach(['private diagnostic','<script>private diagnostic</script>',"private\nsecond line"] as $index=>$message){
 $GLOBALS['captured_logs']=[];$_SERVER['HTTP_HOST']='example.test';
 ob_start();$trace=$index===2?'#0 {main}':NULL;$result=(new Logger())->indicateBackupFailure($trace===NULL?['error'=>$message]:['error'=>$message,'trace'=>$trace]);$output=ob_get_clean();
 $logs=$GLOBALS['captured_logs'];
 $ok=$result===TRUE && strpos($output,'private')===FALSE && count($logs)===1;
 if($ok){$record=json_decode($logs[0],TRUE);$ok=$record===['event'=>'GGCMS error backup failure','host'=>'example.test','error'=>$message,'trace'=>$trace??''];}
 echo ($ok?'PASS ':'FAIL ').'error backup output case='.$index.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);