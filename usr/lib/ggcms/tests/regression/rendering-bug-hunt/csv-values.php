<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/CSV.php';
$failed=0;
foreach([0,'0',0.0,1,'Text',NULL,'',FALSE] as $index=>$value){
 $feed=new CSV();
 $feed->script=(object)['record_to_use'=>['id'=>7,'Value'=>$value,'child'=>[['id'=>9,'Value'=>0]]]];
 $output=$feed->GenerateCSV();
 $stream=fopen('php://memory','w+');fwrite($stream,$output);rewind($stream);
 $rows=[];while(($row=fgetcsv($stream))!==FALSE){$rows[]=$row;}fclose($stream);
 $expected=[['RecordType','Recordid','FieldName','FieldValue'],['Entry','7','id','7']];
 if($index<5){$expected[]=['Entry','7','Value',(string)$value];}
 $expected[]=['child','9','id','9'];$expected[]=['child','9','Value','0'];
 $ok=$rows===$expected;
 echo ($ok?'PASS ':'FAIL ').'CSV scalar value case='.$index.PHP_EOL;
 if(!$ok){$failed++;}
}
exit($failed?1:0);