<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require dirname(__DIR__,3).'/src/traits/scripts/SimpleORM.php';
class ChildWriteFixture {use SimpleORM;public function __construct(){$this->entry=['id'=>7];$this->errors=[];$this->admin_errors=[];}public $entry;public $rows,$rows_unsaved,$handler;public $errors;public $admin_errors;public function isUserAdmin(){return TRUE;}}
$failed=0;
foreach([FALSE,TRUE] as $list){foreach([FALSE,TRUE] as $update){foreach(['success','message','empty-message'] as $mode){
 $row=['id'=>$update?9:0,'Entryid'=>7,'Tag'=>'submitted','swapped'=>FALSE];
 $fixture=new ChildWriteFixture();$input=$list?[$row]:$row;$fixture->rows=$input;
 $db=new class {public $mode,$calls=0;public function result($args,$update){$this->calls++;if($this->mode!=='success'){return ['line'=>123,'error'=>$this->mode==='message'?'query failed':''];}$row=$update?$args['update']:$args['definition'];$row['id']=9;return $update?[$row]:$row;}public function CreateRecord($args){return $this->result($args,FALSE);}public function UpdateRecord($args){return $this->result($args,TRUE);}};
 $db->mode=$mode;$fixture->handler=(object)['db_access'=>$db,'desired_action'=>$update?'Update':'Save'];
 try{$result=$fixture->SaveRecordFromQuery_Base(['objectname'=>'rows','objecttype'=>'Tag','noentryid'=>FALSE]);$saved=$row;$saved['id']=9;$expected=$list?[$saved]:$saved;$ok=$db->calls===1&&$fixture->rows_unsaved===$input&&($mode==='success'?($result===$expected&&$fixture->errors===[]):($result===FALSE&&$fixture->rows===$input&&count($fixture->admin_errors)===1&&$fixture->errors===[['There was a problem with saving the Tag.']]));}catch(Throwable $e){$ok=FALSE;}
 echo ($ok?'PASS ':'FAIL ').'child '.($list?'list':'single').' '.($update?'update':'create').' '.$mode.PHP_EOL;if(!$ok){$failed++;}
}}}
exit($failed?1:0);