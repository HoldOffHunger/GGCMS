<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
$source=file_get_contents(dirname(__DIR__, 3) . '/src/scripts/modify.php');
$start=strpos($source,'public function Update()');$end=strpos($source,'public function Delete()', $start);
eval('class UpdateFixture {public $save_status;public $saveattemptresults;public $savepreparedresults;public $saveaccepted;public $entryid; '.substr($source,$start,$end-$start).'
 public $parent=["id"=>1];public $entry=["id"=>7];public $entry_unset=["association"=>[]];public $calls=[];
 public function Param($name){return FALSE;}
 public function DeleteChildRecordsForUpdate(){return FALSE;}
 public function __call($name,$args){$this->calls[]=$name;if($name==="GetHyperlinkedEntryView"||$name==="FlushPageCacheAndReport"){throw new Exception("Success path reached");}return TRUE;}
}');
$fixture=new UpdateFixture();$result=$fixture->Update();
$ok=$result===TRUE && $fixture->saveattemptresults===FALSE && strpos($fixture->save_status,'failed')!==FALSE && in_array('FormatErrors',$fixture->calls,TRUE);
echo ($ok?'PASS':'FAIL').' Update child-cleanup failure returns to result flow'.PHP_EOL;
exit($ok?0:1);
?>