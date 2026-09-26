<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
$source=file_get_contents(dirname(__DIR__,3).'/src/scripts/users.php');
$start=strpos($source,'public function SetUser()');$end=strpos($source,'public function SetUserComments(', $start);
if($start===FALSE||$end===FALSE){throw new Exception('Method boundaries missing');}
eval('class UserLookupMethod {'.substr($source,$start,$end-$start).'}');
class UserLookupFixture extends UserLookupMethod {public $params;public function Param($name){return $this->params[$name]??'';}}
$failed=0;
foreach(['no-params','missing-name','missing-id','found-name','found-id','fallback-id'] as $mode){
 $fixture=new UserLookupFixture();
 $fixture->params=['user'=>in_array($mode,['missing-name','found-name','fallback-id'])?'Reader':'','userid'=>in_array($mode,['missing-id','found-id','fallback-id'])?7:''];
 $db=new class {public $responses=[],$calls=[];public function GetRecords($args){$this->calls[]=$args;return array_shift($this->responses);}};
 $row=['id'=>7,'Username'=>'Reader'];
 $db->responses=$mode==='fallback-id'?[[],[$row]]:[in_array($mode,['found-name','found-id'])?[$row]:[]];
 $fixture->handler=(object)['db_access'=>$db];
 try{
  $result=$fixture->SetUser();
  $expected=in_array($mode,['found-name','found-id','fallback-id'])?['id'=>7,'Username'=>$mode==='found-name'?'Reader':'User #7']:FALSE;
  $ok=$result===$expected && count($db->calls)===($mode==='no-params'?0:($mode==='fallback-id'?2:1));
 }catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'user lookup '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);