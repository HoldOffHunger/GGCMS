<?php
error_reporting(E_ALL);set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require dirname(__DIR__, 3) . '/src/classes/Database/ORM.php';
$failed=0;
foreach(['Tag','Association'] as $type){foreach([[],[8,9]] as $keep){
 $db=new class{public $calls=[];public function FillArraysFromDB($args){$this->calls[]=$args;return [];}};
 $orm=new ORM(['handler'=>(object)['db_access'=>$db]]);$result=$orm->DeleteChildRecords(['parent'=>['id'=>7],'recordtype'=>$type,'recordidstokeep'=>$keep]);
 $last=end($db->calls);$query='DELETE FROM '.$type.' WHERE Entryid = ?'.($keep?' AND id NOT IN (?, ?)':'');
 $ok=$result===TRUE&&$last['query']===$query&&$last['sqlbindstring']===str_repeat('i',1+count($keep))&&$last['recordvalues']===array_merge([7],$keep);
 if($type==='Association'){$first=$db->calls[0];$ok=$ok&&count($db->calls)===2&&strpos($first['query'],'ChosenEntryid = ? ')!==FALSE&&$first['recordvalues']===[7]&&$first['sqlbindstring']==='i';}
 echo ($ok?'PASS ':'FAIL ').$type.' keep='.count($keep).PHP_EOL;if(!$ok){$failed++;}
}
}
exit($failed?1:0);
?>