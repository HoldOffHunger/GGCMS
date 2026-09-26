<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require dirname(__DIR__, 3) . '/src/classes/Database/ORM.php';
$failed=0;
foreach([['Tag',0,1],['Tag',1,1],['Association',0,2],['Association',1,1],['Association',2,2]] as [$type,$failAt,$calls]) {
 $db=new class {public $calls=[]; public $failAt; public function FillArraysFromDB($args){$this->calls[]=$args;return count($this->calls)===$this->failAt?['line'=>123,'specifictype'=>'Execute']:[];}};
 $db->failAt=$failAt;
 $orm=new ORM(['handler'=>(object)['db_access'=>$db]]);
 try {$result=$orm->DeleteChildRecords(['parent'=>['id'=>7],'recordtype'=>$type,'recordidstokeep'=>[8,9]]);$ok=$result===($failAt===0)&&count($db->calls)===$calls;}
 catch(Throwable $e){$ok=FALSE;echo $e->getMessage().' ';}
 echo ($ok?'PASS ':'FAIL ').$type.' failAt='.$failAt.PHP_EOL;
 if(!$ok){$failed++;}
}
exit($failed?1:0);
?>