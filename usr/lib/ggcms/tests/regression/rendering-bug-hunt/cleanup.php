<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
$source=file_get_contents(dirname(__DIR__, 3) . '/src/scripts/modify.php');
$start=strpos($source,'public function DeleteChildRecordsForUpdate()');
$end=strpos($source,'public function FormatSavedRecordFromQueryForAll()', $start);
eval('class CleanupFixture { '.substr($source,$start,$end-$start).'
 public $entry=["id"=>7];public $tag;public $quote=[];public $orm;public $delete_in_progress=FALSE;
 public function GetStandardChildRecordTypes(){return ["Tag"=>"tag","Quote"=>"quote"];}
 public function Param($name){return FALSE;}
}');
$fixture=new CleanupFixture();
$fixture->tag=$argv[1]==='keep'?[['id'=>9]]:[];
$fixture->orm=new class {public $success;public $calls=[];public function DeleteChildRecords($args){$this->calls[]=$args;return $this->success;}};
$fixture->orm->success=$argv[2]==='success';
$result=$fixture->DeleteChildRecordsForUpdate();
$ok=$result===$fixture->orm->success && count($fixture->orm->calls)===($result?2:1) && $fixture->orm->calls[0]['recordidstokeep']===($argv[1]==='keep'?[9]:[]);
echo ($ok?'PASS':'FAIL').' '.$argv[1].' '.$argv[2].PHP_EOL;
exit($ok?0:1);
?>