<?php
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new ErrorException($message, 0, $level); });
$source=file_get_contents(dirname(__DIR__,3).'/src/scripts/logout.php');
$start=strpos($source, 'public function Display()');
$end=strpos($source, 'public function HTMLHeadDisplayExtra_HTTPEquivalents()', $start);
if($start===FALSE || $end===FALSE) { throw new RuntimeException('Display boundaries missing'); }
eval('class LogoutDisplayFixture { public $handler, $logout_results, $logout_status; public function SetORMBasics() {} '.substr($source,$start,$end-$start).'}');
$failed=0;
foreach(['anonymous'=>NULL, 'empty'=>[], 'session'=>['Userid'=>7]] as $name=>$session) {
 $authentication=new class { public $session; public $calls=0; public function Logout() { $this->calls++; return $this->session; } };
 $authentication->session=$session;
 $page=new LogoutDisplayFixture(); $page->handler=(object)['authentication'=>$authentication];
 try { $result=$page->Display(); $ok=$result===TRUE && $page->logout_results===$session && $page->logout_status===($name==='session'?'Success':'Failure') && $authentication->calls===1; }
 catch(Throwable $error) { $ok=FALSE; echo $error->getMessage().PHP_EOL; }
 echo ($ok?'PASS ':'FAIL ').'logout display '.$name.PHP_EOL;
 if(!$ok) { $failed++; }
}
exit($failed?1:0);
