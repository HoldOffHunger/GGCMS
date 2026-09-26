<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/modules/spacing.php';
require dirname(__DIR__,3).'/src/modules/html/entry-sort.php';
$sorter=new module_entrysort();$failed=0;
foreach(['direct','title-only','list-only','untitled','wrapped-title'] as $mode){
 $entries=[];
 foreach([1=>'Book 10',2=>'Book 2'] as $id=>$title){
  $entry=['id'=>$id];
  if($mode==='direct'){$entry+=['ListTitleSortKey'=>$title,'ListTitle'=>$title,'Title'=>$title];}
  if($mode==='title-only'||$mode==='wrapped-title'){$entry['Title']=$title;}
  if($mode==='list-only'){$entry['ListTitle']=$title;}
  $entries[]=$mode==='wrapped-title'?['id'=>$id,'entry'=>$entry]:$entry;
 }
 try{$result=$sorter->Sort(['entries'=>$entries]);$ok=array_column(array_values($result),'id')===($mode==='untitled'?[1,2]:[2,1]);}
 catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'entry sort shape='.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);