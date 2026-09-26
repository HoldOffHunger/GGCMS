<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require dirname(__DIR__,3).'/src/classes/Database/DBAccess.php';
class UpdateQueryFixture extends DBAccess {
 public $calls=0,$reads=0,$fail_at;
 public $failure=['line'=>123,'error'=>'fixture query failure'];
 public function __construct($fail_at){$this->fail_at=$fail_at;}
 public function MarkPageCacheDirty($args){return TRUE;}
 public function MarkRowCacheDirty($args){return TRUE;}
 public function GetRecordDescription($args){return [];}
 public function GetRecordWhere($args){return ['sqlbindstring'=>'i','sqlwhereclause'=>'id = ?','sqlwherevalues'=>[7]];}
 public function FillArraysFromDB($args){$this->calls++;return $this->calls===$this->fail_at?$this->failure:($this->calls===3?[['@update_id'=>7]]:[]);}
 public function GetRecords($args){$this->reads++;return [['id'=>7]];}
}
$failed=0;
foreach([1,2,3,0] as $fail_at){
 $db=new UpdateQueryFixture($fail_at);
 try{
  $result=$db->UpdateRecord(['type'=>'Tag','update'=>['id'=>7,'Tag'=>'edited'],'where'=>['id'=>7]]);
  $ok=$fail_at?($result===$db->failure&&$db->calls===$fail_at&&$db->reads===0):($result===[['id'=>7]]&&$db->calls===3&&$db->reads===1);
 }catch(Throwable $e){$ok=FALSE;}
 echo ($ok?'PASS ':'FAIL ').'update query failure stage '.$fail_at.PHP_EOL;
 if(!$ok){$failed++;}
}
exit($failed?1:0);