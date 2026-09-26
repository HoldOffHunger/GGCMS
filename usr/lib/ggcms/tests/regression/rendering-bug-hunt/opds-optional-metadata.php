<?php
error_reporting(E_ALL);set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/OPDS.php';
class OptionalFixture extends OPDS {public function __construct(){}}
$failed=0;
foreach(['missing'=>[],'null'=>['textbody'=>NULL,'description'=>NULL],'empty'=>['textbody'=>[],'description'=>[]],'blank-row'=>['textbody'=>[[]],'description'=>[[]]],'populated'=>['textbody'=>[['Source'=>'Archive']],'description'=>[['Description'=>'Summary']]],'zero'=>['textbody'=>[['Source'=>'0']],'description'=>[['Description'=>'0']]]] as $name=>$record){
 $feed=new OptionalFixture();$feed->script=(object)['record_to_use'=>$record];
 try{$author=$feed->SetAuthor();$description=$feed->SetDescription();$ok=$author===($name==='populated'?'From : Archive.':'')&&$description===($name==='populated'?'Summary':($name==='zero'?'0':''));}catch(Throwable $e){$ok=FALSE;}
 echo ($ok?'PASS ':'FAIL ').'OPDS optional metadata '.$name.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);