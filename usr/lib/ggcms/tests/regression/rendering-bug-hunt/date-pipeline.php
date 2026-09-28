<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
$root=dirname(__DIR__,3).'/src/';
require $root.'traits/scripts/SimpleORM.php';
$source=file_get_contents($root.'scripts/modify.php');
$start=strpos($source,'public function PrepareRecordForSaving_EventDate()');$end=strpos($source,'public function PrepareRecordForSaving_Association()', $start);
eval('class DatePipelineFixture {public $eventdate_unsaved;use SimpleORM;public $eventdate_unprepared;public function __construct(){$this->entry=["id"=>7];}public $eventdate;public $entry;public $handler;public function isUserAdmin(){return TRUE;}'.substr($source,$start,$end-$start).'}');
$defaults=['id'=>0,'Entryid'=>7,'swapped'=>FALSE,'EventTime'=>'','Title'=>''];
$cases=[
 'empty'=>[[],[]],
 'blank'=>[[['EventDate'=>'']],[]],
 'year'=>[[['EventDate'=>'1900']],['1900-00-00 00:00:00']],
 'BCE'=>[[['EventDate'=>'-44-03-15']],['4044-03-15 00:00:00']],
 'mixed'=>[[['EventDate'=>''],['EventDate'=>'1900']],['1900-00-00 00:00:00']],
];
$failed=0;
foreach($cases as $label=>[$input,$dates]){
 $fixture=new DatePipelineFixture();$fixture->eventdate=array_map(fn($row)=>array_merge($defaults,$row),$input);
 $db=new class{public $calls=[];public function CreateRecord($args){$this->calls[]=$args;return array_merge($args['definition'],['id'=>count($this->calls)+10,'error'=>FALSE]);}};
 $fixture->handler=(object)['desired_action'=>'Save','db_access'=>$db];
 $prepared=$fixture->PrepareRecordForSaving_EventDate();
 $saved=$fixture->SaveRecordFromQuery_Base(['objectname'=>'eventdate','objecttype'=>'EventDate','noentryid'=>FALSE]);
 $actual=array_map(fn($call)=>$call['definition']['EventDateTime'],$db->calls);
 $ok=$prepared===TRUE&&(bool)$saved&&$actual===$dates;
 foreach($db->calls as $call){$ok=$ok&&$call['type']==='EventDate'&&$call['definition']['Entryid']===7&&!isset($call['definition']['EventDate'])&&!isset($call['definition']['EventTime']);}
 if(!$dates){$ok=$ok&&$fixture->eventdate===[];}
 echo ($ok?'PASS ':'FAIL ').'date preparation-to-writer '.$label.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);
?>