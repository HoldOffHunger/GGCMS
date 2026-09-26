<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
$source=file_get_contents(dirname(__DIR__,3).'/src/scripts/users.php');$methods='';
foreach(['SetUserComments','SetUserLikesDislikes'] as $name){$start=strpos($source,'public function '.$name.'(');$end=strpos($source,'public function ',$start+16);if($start===FALSE||$end===FALSE){throw new Exception('Boundaries missing');}$methods.=substr($source,$start,$end-$start);}
eval('class UserQueryMethods {'.$methods.'}');
class UserQueryFixture extends UserQueryMethods {
 public $calls=[],$user=['id'=>7];
 public function SetRecordEntries($args){$this->calls[]='full';return $args['records'];}
 public function SetLimitedRecordEntries($args){$this->calls[]='limited';return $args['records'];}
}
$failed=0;
foreach(['SetUserComments'=>'comments','SetUserLikesDislikes'=>'likedislikes'] as $method=>$property){
 foreach([FALSE,TRUE] as $limited){
  foreach(['error','empty','rows'] as $mode){
   $fixture=new UserQueryFixture();$db=new class{public $result;public function RunQuery($args){return $this->result;}};
   $db->result=$mode==='error'?['type'=>'MySQL','line'=>123,'error'=>'']:($mode==='empty'?[]:[['id'=>9]]);
   $fixture->handler=(object)['db_access'=>$db];$caught=FALSE;$result=NULL;
   try{$result=$fixture->$method($limited?['limit'=>2]:[]);}catch(RuntimeException $error){$caught=TRUE;}
   $ok=$mode==='error'?($caught && $fixture->calls===[]):(!$caught && $result===TRUE && $fixture->$property===$db->result && $fixture->calls===[$limited?'full':'limited']);
   echo ($ok?'PASS ':'FAIL ').$method.' limit='.(int)$limited.' '.$mode.PHP_EOL;if(!$ok){$failed++;}
  }
 }
}
exit($failed?1:0);