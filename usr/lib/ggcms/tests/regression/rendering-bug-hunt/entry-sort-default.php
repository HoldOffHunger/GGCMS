<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/modules/spacing.php';
require dirname(__DIR__,3).'/src/modules/html/entry-sort.php';
$sorter=new module_entrysort();$failed=0;
$entries=[['id'=>1,'entry'=>['ListTitleSortKey'=>'Book 10','ListTitle'=>'Ten','Title'=>'Z']],['id'=>2,'entry'=>['ListTitleSortKey'=>'Book 2','ListTitle'=>'Two','Title'=>'A']]];
foreach(['missing','empty','explicit','no-records'] as $mode){
 $args=['entries'=>$mode==='no-records'?[]:$entries];
 if($mode==='empty'){$args['sort_field']='';}
 if($mode==='explicit'){$args['sort_field']='Title';}
 try{$result=$sorter->Sort($args);$ok=array_column(array_values($result),'id')===($mode==='no-records'?[]:[2,1]);}
 catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'entry sort '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);