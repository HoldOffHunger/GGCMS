<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
$source=file_get_contents(dirname(__DIR__,3).'/src/scripts/modify.php');
$start=strpos($source,'public function SaveRecordFromQuery_Entry()');
$end=strpos($source,'public function SaveRecordFromQuery_EntryTranslation()',$start);
eval('class EntryMethod {'.substr($source,$start,$end-$start).'}');
class EntryFixture extends EntryMethod {public $entry,$entry_unsaved,$db_access_object,$admin_errors=[];}
$failures=0;
foreach([FALSE,TRUE] as $update){foreach([FALSE,TRUE] as $failure){
 $fixture=new EntryFixture();$input=['id'=>$update?7:0,'Title'=>'submitted'];$fixture->entry=$input;
 $db=new class {public $failure,$calls=0;public $error=['line'=>123,'error'=>'fixture failure'];public function CreateRecord($args){$this->calls++;return $this->failure?$this->error:['id'=>7,'Title'=>'saved'];}public function UpdateRecord($args){$this->calls++;return $this->failure?$this->error:[['id'=>7,'Title'=>'saved']];}};
 $db->failure=$failure;$fixture->db_access_object=$db;
 try{$result=$fixture->SaveRecordFromQuery_Entry();$ok=$fixture->entry_unsaved===$input&&$db->calls===1&&($failure?($result===FALSE&&$fixture->entry===$input&&$fixture->admin_errors===[$db->error]):($result===['id'=>7,'Title'=>'saved']&&$fixture->entry===$result&&$fixture->admin_errors===[]));}catch(Throwable $e){$ok=FALSE;}
 echo ($ok?'PASS ':'FAIL ').'entry '.($update?'update':'create').' '.($failure?'failure':'success').PHP_EOL;
 if(!$ok){$failures++;}
}}
exit($failures?1:0);