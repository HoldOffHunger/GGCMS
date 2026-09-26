<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
$source=file_get_contents(dirname(__DIR__,3).'/src/scripts/modify.php');
$methods='';
foreach(['SaveRecordFromQueryForAll','SaveRecordFromQueryForUpdate'] as $name){$start=strpos($source,'public function '.$name.'()');$end=strpos($source,'public function ',$start+16);$methods.=substr($source,$start,$end-$start);}
eval('class ParentMethods {'.$methods.'}');
class ParentFixture extends ParentMethods {public $errors=[],$children=[],$entry_unset=['id'=>7],$entry=['id'=>7];public function SaveRecordFromQuery_Entry(){return FALSE;}public function __call($name,$args){$this->children[]=$name;return TRUE;}}
$failed=0;
foreach(['SaveRecordFromQueryForAll','SaveRecordFromQueryForUpdate'] as $name){$fixture=new ParentFixture();$result=$fixture->$name();$ok=$result===FALSE&&$fixture->children===[]&&$fixture->errors===[['There was a problem with saving the Entry.']];echo ($ok?'PASS ':'FAIL ').$name.' parent failure stops children'.PHP_EOL;if(!$ok){$failed++;}}
exit($failed?1:0);