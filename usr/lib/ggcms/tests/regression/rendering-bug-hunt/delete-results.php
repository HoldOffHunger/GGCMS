<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
$source=file_get_contents(dirname(__DIR__, 3) . '/src/scripts/modify.php');
$start=strpos($source,'public function Delete()');
$end=strpos($source,'public function Save()', $start);
$method=substr($source,$start,$end-$start);
eval('class DeleteFixture {public $save_status;public $delete_in_progress; '.$method.'
 public $stage; public $calls=[]; public $saveattemptresults=FALSE;
 public function canUserAccess(){return TRUE;}
 public function canUserDelete(){return TRUE;}
 public function SetOrmBasics(){}
 public function ValidateOrm(){return TRUE;}
 public function OrderAndFillChildRecords(){}
 public function DeleteChildRecordsForUpdate(){return $this->stage!=="child";}
 public function DeleteEntry(){ $this->calls[]="entry"; return $this->stage==="entry"?["line"=>123,"type"=>"MySQL"]:[]; }
 public function DeleteAssignment(){ $this->calls[]="assignment"; return $this->stage==="assignment"?["line"=>123,"type"=>"MySQL"]:[]; }
}');
$failed=0;
foreach(['success','entry','assignment','child'] as $stage){
 $fixture=new DeleteFixture();$fixture->stage=$stage;
 $result=$fixture->Delete();
 $ok=$result===TRUE && $fixture->saveattemptresults===($stage==='success') && $fixture->calls===($stage==='child'?[]:($stage==='entry'?['entry']:['entry','assignment']));
 if($stage!=='success'){$ok=$ok && strpos($fixture->save_status,'Delete successful')===FALSE;}
 echo ($ok?'PASS ':'FAIL ').$stage.PHP_EOL;
 if(!$ok){$failed++;}
}
exit($failed?1:0);
?>