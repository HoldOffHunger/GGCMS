<?php
error_reporting(E_ALL);
$root=dirname(__DIR__,3);
$source=file_get_contents($root.'/src/scripts/users.php');$methods='';
foreach(['exportuser','SetUserComments','SetUserLikesDislikes'] as $name){$start=strpos($source,'public function '.$name.'(');$end=strpos($source,'public function ',$start+16);if($start===FALSE||$end===FALSE){throw new Exception('Boundaries missing');}$methods.=substr($source,$start,$end-$start);}
eval('class ExportActionMethods {'.$methods.'}');
class ExportActionFixture extends ExportActionMethods {
 public $calls=[],$user=['id'=>7];
 public function SetORMBasics(){}public function SetRecordTree(){}public function ValidateOrm(){return TRUE;}public function SetUser(){return TRUE;}
 public function SetUserCommentsCount(){$this->calls[]='comment-count';}
 public function SetUserLikesDislikesCount(){$this->calls[]='like-count';}
 public function SetLimitedRecordEntries($args){$this->calls[]='hydrate';return $args['records'];}
 public function SetDocumentAttributes(){$this->calls[]='attributes';}
 public function DisplayTemplates(){$this->calls[]='templates';print('Export completed');}
}
require $root.'/src/classes/Format/Base/AbstractBaseFormat.php';
require $root.'/src/classes/Format/TXT.php';
class ExportTextFixture extends TXT {public function __construct(){}public function SetFileNameDisplay(){}public function HandleHTTPHeaders(){}}
class Handler {
 public static $mode;public static $action;public $error_logging;
 public function __construct(){$this->error_logging=new class{public function mylog($error,$level,$trace){print('Server error');}};}
 public function HandleRequest(){
  $action=new ExportActionFixture();self::$action=$action;
  $db=new class{public $calls=0;public function RunQuery($args){$this->calls++;if((Handler::$mode==='comments'&&$this->calls===1)||(Handler::$mode==='likes'&&$this->calls===2)){return ['line'=>123,'error'=>''];}return [];}};
  $action->handler=(object)['db_access'=>$db];
  $format=new ExportTextFixture();$format->script=$action;$format->desired_action='exportuser';$format->Display();
 }
}
$index=file_get_contents(dirname(__DIR__,6).'/var/www/html/index.php');
$start=strpos($index,'$handler = NULL;');$end=strpos($index,'$page_output =',$start);
$block=str_replace("require(GGCMS_DIR . 'classes/StandardLibraries.php');",'',substr($index,$start,$end-$start));
$failed=0;
foreach(['comments','likes','success'] as $mode){
 Handler::$mode=$mode;http_response_code(200);ob_start();eval($block);$output=ob_get_clean();
 $action=Handler::$action;
 $expected=$mode==='comments'?[]:($mode==='likes'?['hydrate','comment-count']:['hydrate','comment-count','hydrate','like-count','attributes','templates']);
 $ok=$action->calls===$expected && $action->handler->db_access->calls===($mode==='comments'?1:2) && http_response_code()===($mode==='success'?200:500) && $output===($mode==='success'?'Export completed':'Server error');
 echo ($ok?'PASS ':'FAIL ').'export query dispatch '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);