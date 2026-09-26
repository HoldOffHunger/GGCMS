<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__, 3) . '/src/classes/Format/RSS.php';
require dirname(__DIR__, 3) . '/src/classes/Format/ATOM.php';
$parent='2026-09-10 12:00:00';$failed=0;
foreach(['RSS','ATOM'] as $type){foreach([NULL,[],[['OriginalCreationDate'=>'2026-09-09 12:00:00']],[['OriginalCreationDate'=>'2026-09-11 12:00:00']]] as $children){
 $feed=new $type();$feed->entries=NULL;$feed->script=(object)['entry'=>['LastModificationDate'=>$parent],'newest_entries'=>$children,'children'=>[]];
 $expected=$children&&$children[0]['OriginalCreationDate']>$parent?$children[0]['OriginalCreationDate']:$parent;
 try{$ok=$feed->ConvertHTMLToFormat_lastBuildDate()===$expected;}catch(Throwable $e){$ok=FALSE;echo $e->getMessage().' ';}
 echo ($ok?'PASS ':'FAIL ').$type.' expected='.$expected.' children='.count($children??[]).PHP_EOL;if(!$ok){$failed++;}
}}
exit($failed?1:0);
?>