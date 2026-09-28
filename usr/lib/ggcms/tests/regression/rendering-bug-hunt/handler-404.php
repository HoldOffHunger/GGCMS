<?php
error_reporting(E_ALL);
set_error_handler(function($n, $m) { throw new ErrorException($m, 0, $n); });
function ggreq($path) { require_once dirname(__DIR__, 3) . '/src/' . $path; }
ggreq('traits/ReverseDNSNotation.php');
ggreq('classes/Networking/Handler.php');
class DispatchFixture extends Handler {
 public $logs;
 
 public $before_content = TRUE, $error_404 = FALSE, $error404redirect;
 public $unavailable_entry = NULL, $authentication, $issue_logging, $redirects;
 public $resolves, $repairs, $content_result, $content_calls = 0, $repair_calls = 0;
 public function __construct($resolves, $repairs, $result) {
  $this->resolves=$resolves; $this->repairs=$repairs; $this->content_result=$result;
  $this->authentication=(object)['user_session'=>['UserAdmin.id'=>0]];
  // The 404 chain's redirects live in HandlerRedirects since 28 September 2026
  $this->redirects=new class {
   public function handleReservedCodeRedirect() { return FALSE; }
   public function handleMatchingCodeRedirect() { return FALSE; }
   public function handleScriptRedirect() { return FALSE; }
   public function handleMisplacedScriptRedirect() { return FALSE; }
  };
  $this->issue_logging=new class {
   public $logs=[];
   public function createLog($args) { $this->logs[]=$args; }
  };
 }
 public function __destruct() {}
 public function getArgs() { return ['handler'=>$this]; }
 public function handle404Image() { return FALSE; }
 public function handleSrvLocalFiles() { return FALSE; }
 public function EntryPathResolves() { return $this->resolves; }
 public function RepairEntryPath() { $this->repair_calls++; return $this->repairs; }
 public function HandleRequest_Content() { $this->content_calls++; return $this->content_result; }
 public function isScriptImage() { return FALSE; }
}
$failures=0; $reports=[];
foreach([
 ['failed content', TRUE, FALSE, FALSE, 1, 0, TRUE],
 ['unresolved path', FALSE, FALSE, TRUE, 0, 1, TRUE],
 ['repaired failed content', FALSE, TRUE, FALSE, 1, 1, TRUE],
 ['successful content', TRUE, FALSE, TRUE, 1, 0, FALSE],
] as [$name,$resolves,$repairs,$content,$calls,$repair_calls,$error]) {
 http_response_code(200);
 $handler=new DispatchFixture($resolves,$repairs,$content);
 ob_start(); $result=$handler->HandleRequest_ServeContent(); $output=ob_get_clean();
 $ok=$result===TRUE && $handler->before_content===FALSE && $handler->error_404===$error
  && $handler->content_calls===$calls && $handler->repair_calls===$repair_calls
  && http_response_code()===($error?404:200)
  && $handler->issue_logging->logs===($error?[['issuetype'=>'404','description'=>'404 URL']]:[])
  && ($error?strpos($output,'Home Directory/</a>')!==FALSE:$output==='');
 $reports[]=($ok?'PASS ':'FAIL ').'handler '.$name;
 if(!$ok) { $failures++; }
}
echo implode(PHP_EOL,$reports).PHP_EOL;
exit($failures?1:0);