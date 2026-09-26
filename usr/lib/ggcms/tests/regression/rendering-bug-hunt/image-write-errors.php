<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
$source=file_get_contents(dirname(__DIR__,3).'/src/scripts/modify.php');
$start=strpos($source,'public function SaveRecordFromQuery_Image()');$end=strpos($source,'public function DeleteFiles(',$start);
eval('class ImageMethod {'.substr($source,$start,$end-$start).'}');
class ImageFixture extends ImageMethod {public $image,$entry=['id'=>7],$calls=0;public function SaveRecordFromQuery_Base($args){$this->calls++;return FALSE;}}
$failed=0;
foreach(['empty','failed-row'] as $mode){$fixture=new ImageFixture();$fixture->image=$mode==='empty'?[]:[['id'=>9,'swapped'=>FALSE,'FileName'=>'keep.png']];$original=$fixture->image;try{$result=$fixture->SaveRecordFromQuery_Image();$ok=$result===($mode==='empty'?TRUE:FALSE)&&$fixture->calls===($mode==='empty'?0:1)&&$fixture->image===$original;}catch(Throwable $e){$ok=FALSE;}echo ($ok?'PASS ':'FAIL ').'image '.$mode.PHP_EOL;if(!$ok){$failed++;}}
exit($failed?1:0);