<?php
error_reporting(E_ALL);
$warnings=[];set_error_handler(function($n,$m)use(&$warnings){$warnings[]=$m;return TRUE;});
require dirname(__DIR__, 3) . '/src/traits/scripts/SimpleORM.php';
class EmptyWriterFixture {use SimpleORM;public $rows;public $entry=['id'=>7];public $handler;public function isUserAdmin(){return TRUE;}}
$failed=0;
foreach([[],[['id'=>0,'Entryid'=>7,'Title'=>'Event','EventDateTime'=>'1900-00-00 00:00:00','swapped'=>FALSE]]] as $rows){
 $warnings=[];$fixture=new EmptyWriterFixture();$fixture->rows=$rows;
 $db=new class {public $calls=[];public function CreateRecord($args){$this->calls[]=$args;return array_merge($args['definition'],['id'=>9,'error'=>FALSE]);}};
 $fixture->handler=(object)['desired_action'=>'Save','db_access'=>$db];
 $result=$fixture->SaveRecordFromQuery_Base(['objectname'=>'rows','objecttype'=>'EventDate','noentryid'=>FALSE]);
 $ok=$rows?((bool)$result&&count($db->calls)===1):($result===TRUE&&$fixture->rows===[]&&count($db->calls)===0);
 $ok=$ok&&!$warnings; echo ($ok?'PASS ':'FAIL ').'writer rows='.count($rows).' calls='.count($db->calls).' warnings='.count($warnings).PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);
?>