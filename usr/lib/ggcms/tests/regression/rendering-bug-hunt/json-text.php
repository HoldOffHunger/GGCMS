<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/JSON.php';
class JSONTextFixture extends JSON {
 public function RunScript(){return TRUE;}
 public function SetFileNameDisplay(){}
 public function HandleHTTPHeaders(){}
}
$failed=0;
foreach([['Plain','Plain'],["Caf\xC3\xA9 & <text>","Caf\xC3\xA9 & <text>"],["Bad\xFF byte","Bad\xEF\xBF\xBD byte"],["Truncated\xC3","Truncated\xEF\xBF\xBD"]] as $index=>[$input,$expected]){
 $feed=new JSONTextFixture();
 $feed->script=new class { public $record_to_use; public $calls=0; public function DisplayTemplates(){$this->calls++;} };
 $feed->script->record_to_use=['id'=>7,'Title'=>$input,'child'=>[['Text'=>$input,'Zero'=>0]],'Absent'=>NULL];
 ob_start();
 try{
  $result=$feed->Display();$output=ob_get_clean();
  $decoded=json_decode($output,TRUE,512,JSON_THROW_ON_ERROR);
  $ok=$result===1 && $feed->script->calls===1 && $decoded===['id'=>7,'Title'=>$expected,'child'=>[['Text'=>$expected,'Zero'=>0]],'Absent'=>NULL];
 }catch(Throwable $error){if(ob_get_level()){ob_end_clean();}$ok=FALSE;}
 echo ($ok?'PASS ':'FAIL ').'JSON display text case='.$index.PHP_EOL;
 if(!$ok){$failed++;}
}
exit($failed?1:0);