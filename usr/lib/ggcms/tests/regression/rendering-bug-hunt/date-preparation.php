<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require_once(__DIR__.'/belt-source.php');$source=belt_source();
$start=strpos($source,'public function PrepareRecordForSaving_EventDate()');$end=strpos($source,'public function PrepareRecordForSaving_Association()', $start);
eval('class DateFixture {public $eventdate_unprepared;public $eventdate;'.substr($source,$start,$end-$start).'}');
$failed=0;
foreach(['empty'=>[], 'blank'=>[['EventDate'=>'','EventTime'=>'','Title'=>'']], 'year'=>[['EventDate'=>'1900','EventTime'=>'','Title'=>'']], 'BCE'=>[['EventDate'=>'-44-03-15','EventTime'=>'','Title'=>'Event']]] as $label=>$rows){
 $fixture=new DateFixture();$fixture->eventdate=$rows;$result=$fixture->PrepareRecordForSaving_EventDate();
 $expected = match($label) {
 'empty','blank'=>[],
 'year'=>[['Title'=>'Publication','EventDateTime'=>'1900-00-00 00:00:00']],
 'BCE'=>[['Title'=>'Event','EventDateTime'=>'4044-03-15 00:00:00']],
};
$ok=$result===TRUE && $fixture->eventdate===$expected && $fixture->eventdate_unprepared===$rows;
echo ($ok?'PASS ':'FAIL ').'date preparation '.$label.PHP_EOL;
if(!$ok){$failed++;}
}
exit($failed?1:0);
?>