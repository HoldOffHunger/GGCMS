<?php
error_reporting(E_ALL);
$source=file_get_contents(dirname(__DIR__,6).'/var/www/html/index.php');
$start=strpos($source,'$handler = NULL;');$end=strpos($source,'$page_output =',$start);
if($start===FALSE||$end===FALSE){throw new Exception('Request boundaries missing');}
$block=substr($source,$start,$end-$start);
$block=str_replace("require(GGCMS_DIR . 'classes/StandardLibraries.php');",'', $block);
class Handler {
 public static $mode;public $error_logging;
 public function __construct(){if(self::$mode==='constructor'){throw new RuntimeException('private diagnostic');}$this->error_logging=new RequestLogger();}
 public function HandleRequest(){print('partial content');if(self::$mode==='exception'||self::$mode==='logger-error'){throw new RuntimeException('private diagnostic');}if(self::$mode==='type-error'){throw new TypeError('private diagnostic');}}
}
class RequestLogger {
 public static $calls=0;
 public function mylog($error,$level,$trace){self::$calls++;if(Handler::$mode==='logger-error'){print('partial logger');throw new RuntimeException('private logging diagnostic');}http_response_code(500);print('Configured server error');}
}
$failed=0;
foreach(['success','exception','type-error','constructor','logger-error'] as $mode){
 Handler::$mode=$mode;RequestLogger::$calls=0;http_response_code(200);$request_failed=FALSE;
 ob_start();
 try{eval($block);$output=ob_get_clean();
  $expected=$mode==='success'?'partial content':(in_array($mode,['exception','type-error'])?'Configured server error':'Internal Server Error');
  $ok=$output===$expected && http_response_code()===($mode==='success'?200:500) && $request_failed===($mode!=='success');
 }catch(Throwable $error){ob_end_clean();$ok=FALSE;}
 echo ($ok?'PASS ':'FAIL ').'request exception mode='.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);