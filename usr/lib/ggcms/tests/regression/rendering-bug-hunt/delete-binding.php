<?php
error_reporting(E_ALL);set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require dirname(__DIR__, 3) . '/src/classes/Database/ORM.php';
$failed=0;
foreach(['DeleteEntry'=>'DELETE FROM Entry WHERE id = ?;','DeleteAssignment'=>'DELETE FROM Assignment WHERE Childid = ?;'] as $method=>$query){foreach([7,'7 OR 1=1'] as $id){
 $db=new class {public $args;public function FillArraysFromDB($args){$this->args=$args;return ['line'=>123,'specifictype'=>'Execute'];}};
 $orm=new ORM(['handler'=>(object)['db_access'=>$db]]);$result=$orm->$method(['entry'=>['id'=>$id]]);
 $ok=$db->args['query']===$query&&$db->args['sqlbindstring']==='i'&&$db->args['recordvalues']===[$id]&&$result===['line'=>123,'specifictype'=>'Execute'];
 echo ($ok?'PASS ':'FAIL ').$method.' '.(is_int($id)?'integer':'SQL-like input').PHP_EOL;if(!$ok){$failed++;}
}}
exit($failed?1:0);
?>