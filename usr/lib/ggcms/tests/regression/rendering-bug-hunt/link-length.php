<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require dirname(__DIR__, 3) . '/src/classes/Charset/UTF8Characters.php';
$failures=0;
$source=file_get_contents(dirname(__DIR__, 3) . '/src/scripts/modify.php');
$start=strpos($source,'public function ValidateRecordForSaving_Link()');
$end=strpos($source,'public function ValidateRecordForSaving_EventDate()', $start);
eval('class LinkFixture {public $link; public $errors=[];'.substr($source,$start,$end-$start).'}');
foreach(['Title','URL'] as $field){foreach(['a',hex2bin('c3a9')] as $character){foreach([255,256] as $length){
 $fixture=new LinkFixture();$fixture->handler=(object)['cleanser'=>(object)['utf8_characters'=>new UTF8Characters()]];$row=['Title'=>'Link','URL'=>'https://example.test/'];$row[$field]=str_repeat($character,$length);$fixture->link=[$row];
 $actual=$fixture->ValidateRecordForSaving_Link();$expected=$length<=255;
 if($actual!==$expected){$failures++;} echo ($actual===$expected?'PASS ':'FAIL ').$field.' characters='.$length.' bytes='.strlen($row[$field]).' accepted='.(int)$actual.PHP_EOL;
}}}
exit($failures?1:0);
?>