<?php
namespace InsertDiagnostic;
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new \ErrorException($m,0,$n);});
function mysqli_insert_id($link){$link->id_reads++;return $link->id;}
$source=file_get_contents(dirname(__DIR__,3).'/src/classes/Database/DBAccess.php');
$start=strpos($source,'public function CreateRecord($args)');
$end=strpos($source,'// Insert Information, Counted',$start);
$method=substr($source,$start,$end-$start);
$error_start=strpos($source,'public function GetError($args)');
$error_end=strpos($source,'public function FillArraysFromDB_FormatRow($args)',$error_start);
if($start===FALSE || $end===FALSE || $error_start===FALSE || $error_end===FALSE){throw new \RuntimeException('Method boundaries missing');}
$error_method=substr($source,$error_start,$error_end-$error_start);
eval('namespace InsertDiagnostic; use Exception; class InsertMethod {'.$method.$error_method.'}');
class InsertFixture extends InsertMethod {
 public $db_link,$calls=0,$reads=0,$mode;
 public $failure=['line'=>123,'error'=>'fixture failure'];
 public function __construct($mode){$this->mode=$mode;$this->db_link=(object)['id'=>$mode==='zero'?0:7,'id_reads'=>0,'error'=>'connection fixture text','errno'=>0];}
 public function MarkPageCacheDirty($args){return TRUE;}
 public function MarkRowCacheDirty($args){return TRUE;}
 public function GetRecordDescription($args){return [];}
 public function GetRecordWhere($args){return ['sqlbindstring'=>'s','sqlwhereclause'=>'Tag = ?','sqlwherevalues'=>['example'],'allbindings'=>['?']];}
 public function GetRecordColumns($args){return 'Tag';}
 public function FillArraysFromDB($args){$this->calls++;return $this->mode==='insert-error'?$this->failure:[];}
 public function GetRecords($args){$this->reads++;if($this->mode==='read-empty'){return [];}return $this->mode==='read-error'?$this->failure:[['id'=>7,'Tag'=>'example']];}
}
$failed=0;
foreach(['insert-error','read-error','success','zero','read-empty'] as $mode){
 $db=new InsertFixture($mode);
 try{
  $result=$db->CreateRecord(['type'=>'Tag','definition'=>['Tag'=>'example']]);
  $expected=in_array($mode,['insert-error','read-error'])?$db->failure:($mode==='zero'?[]:['id'=>7,'Tag'=>'example']);
  $matches=$mode==='read-empty'?(!empty($result['line'])&&$result['specifictype']==='Readback'&&$result['error']==='Created record could not be read back.'):$result===$expected;
  if($mode==='read-empty') {
   $matches=$matches && $result['type']==='MySQL' && $result['errornumber']===0 && $result['function']==='CreateRecord' && is_array($result['stacktrace']) && count($result['stacktrace'])>0 && is_string($result['stacktracestring']) && $result['stacktracestring']!=='';
  }
  $ok=$matches&&$db->calls===1&&$db->db_link->id_reads===($mode==='insert-error'?0:1)&&$db->reads===(in_array($mode,['insert-error','zero'])?0:1);
 }catch(\Throwable $e){$ok=FALSE;}
 echo ($ok?'PASS ':'FAIL ').'create '.$mode.PHP_EOL;
 if(!$ok){$failed++;}
}
exit($failed?1:0);