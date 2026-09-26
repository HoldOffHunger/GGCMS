<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__, 3) . '/src/classes/Format/RSS.php';
require dirname(__DIR__, 3) . '/src/classes/Format/ATOM.php';
$img=['FileDirectory'=>'ab','FileName'=>'cover.png','IconFileName'=>'thumb.jpg','Title'=>'Cover','Description'=>'','IconPixelWidth'=>140,'IconPixelHeight'=>70];
$failed=0;
foreach(['RSS','ATOM'] as $type){foreach([NULL,[],[$img]] as $images){
 $feed=new $type();$feed->script=(object)['entry'=>['image'=>$images]];
 $feed->handler=(object)['cleanser'=>(object)['utf8_characters'=>new class{public function SystemCharSet(){return 'UTF-8';}}],'domain'=>new class{public function GetPrimaryDomain($args){return 'https://example.test';}}];
 $feed->indent_levels=[1=>'',2=>'',3=>''];$feed->line_separator='';
 try{$result=$feed->ConvertHTMLToFormat_renderImage(['link'=>'https://example.test/books/']);
 $ok=$images?strpos($result,'https://example.test/image/a/b/thumb.jpg')!==FALSE:$result==='';
 if($images){$xml=new DOMDocument();$ok=$ok&&$xml->loadXML('<root>'.$result.'</root>');}
 }catch(Throwable $e){$ok=FALSE;echo $e->getMessage().' ';}
 echo ($ok?'PASS ':'FAIL ').$type.' images='.count($images??[]).PHP_EOL;if(!$ok){$failed++;}
}}
exit($failed?1:0);
?>