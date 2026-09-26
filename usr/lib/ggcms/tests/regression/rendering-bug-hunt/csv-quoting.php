<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/CSV.php';
$failed=0;$feed=new CSV();
foreach(['Plain','a,b','a"b',"line1\r\nline2",'before\"after','ends' . chr(92),"Caf\xC3\xA9"] as $index=>$value){
 $expected=[['before',$value,'after'],['next','row','intact']];
 $output=$feed->EscapeDataForCSV(['data'=>$expected]);
 $stream=fopen('php://memory','w+');fwrite($stream,$output);rewind($stream);
 $rows=[];while(($row=fgetcsv($stream,0,',','"',''))!==FALSE){$rows[]=$row;}fclose($stream);
 $ok=$rows===$expected;
 echo ($ok?'PASS ':'FAIL ').'CSV quote roundtrip case='.$index.PHP_EOL;
 if(!$ok){$failed++;}
}
exit($failed?1:0);