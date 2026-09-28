<?php
error_reporting(E_ALL);
$warnings=[];
set_error_handler(function($n,$m)use(&$warnings){$warnings[]=$m;return TRUE;});
require dirname(__DIR__, 3) . '/src/classes/Charset/UTF8Characters.php';
$source=file_get_contents(dirname(__DIR__, 3) . '/src/scripts/modify.php');
$start=strpos($source,'public function ValidateRecordForSaving_EventDate()');$end=strpos($source,'public function ValidateRecordForSaving_Association()', $start);
eval('class EventFixture {public $handler;public $eventdate;public $errors=[];'.substr($source,$start,$end-$start).'}');
$cases=[];
foreach(['Title','Description'] as $field){foreach(['a',hex2bin('c3a9')] as $character){foreach([255,256] as $length){$row=['Title'=>'Publication','Description'=>'Description'];$row[$field]=str_repeat($character,$length);$cases[]=[$field.' '.$length.' chars/'.strlen($row[$field]).' bytes',$row,$length<=255];}}}
foreach(['Birth','BirthDay','BirthDate','Birth Date','Death','DeathDay','DeathDate','Death Date','Birth Day','Death Day','Publication'] as $title){$cases[]=[$title,['Title'=>$title,'Description'=>''],in_array($title,['Birth Day','Death Day','Publication'],TRUE)];}
$failed=0;
foreach($cases as [$label,$row,$expected]){$warnings=[];$fixture=new EventFixture();$fixture->handler=(object)['cleanser'=>(object)['utf8_characters'=>new UTF8Characters()]];$fixture->eventdate=[$row];$actual=$fixture->ValidateRecordForSaving_EventDate();$ok=$actual===$expected && !$warnings && (count($fixture->errors)>0)===!$expected;echo ($ok?'PASS ':'FAIL ').$label.' accepted='.(int)$actual.' warnings='.count($warnings).PHP_EOL;if(!$ok){$failed++;}}
exit($failed?1:0);
?>