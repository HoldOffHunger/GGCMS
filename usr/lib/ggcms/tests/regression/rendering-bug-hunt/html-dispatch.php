<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require dirname(__DIR__, 3) . '/src/classes/Format/Base/AbstractBaseFormat.php';
require dirname(__DIR__, 3) . '/src/classes/Format/HTML.php';
class HTMLFixture extends HTML {
 public $events=[];
 public function __construct() {}
 public function StartHTML(){ $this->events[]='start'; }
 public function FinishHTML(){ $this->events[]='finish'; }
}
$failures=0;
foreach([FALSE,TRUE] as $override) {
 foreach([FALSE,TRUE] as $success) {
  $script=new class {
   public $success; public $events=[];
   public function display(){ $this->events[]='default'; return $this->success; }
   public function display_fixture(){ $this->events[]='override'; return $this->success; }
   public function GetHTMLFormatData(){ $this->events[]='metadata'; return []; }
   public function DisplayTemplates(){ $this->events[]='template'; }
  };
  $script->success=$success;
  $html=new HTMLFixture(); $html->script=$script; $html->desired_action='display';
  $html->domain_object=(object)['host'=>$override?'fixture':'absent'];
  ob_start(); $result=$html->Display(); $output=ob_get_clean();
  $expected=[$override?'override':'default'];
  if($success){$expected[]='metadata';$expected[]='template';}
  $ok=$result===$success && $script->events===$expected && $html->events===($success?['start','finish']:[]) && $output==='';
  echo ($ok?'PASS':'FAIL').' override='.(int)$override.' success='.(int)$success.PHP_EOL;
  if(!$ok){$failures++;}
 }
}
exit($failures?1:0);
?>