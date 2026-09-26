<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/RDF.php';
require dirname(__DIR__,3).'/src/classes/Charset/UTF8Characters.php';
$failed=0;
foreach(['missing','null','empty'] as $mode){
 $record=['id'=>7,'Code'=>'example','Title'=>'Example','Subtitle'=>'','ListTitle'=>'','OriginalCreationDate'=>'2026-09-14','LastModificationDate'=>'2026-09-14'];
 if($mode!=='missing'){foreach(['tag','image','description','quote','textbody','eventdate','link'] as $field){$record[$field]=$mode==='null'?NULL:[];}}
 $rdf=new RDF();$rdf->handler=(object)['cleanser'=>(object)['utf8_characters'=>new UTF8Characters()]];$rdf->script=(object)['record_to_use'=>$record];
 $rdf->domain_object=new class {public function GetPrimaryDomain($args){return 'https://example.test';}};
 $_SERVER['REDIRECT_URL']='/example/view.rdf';
 try{
  $output=$rdf->ConvertHTMLToFormat();$dom=new DOMDocument();$ok=$dom->loadXML($output);
  $xpath=new DOMXPath($dom);$xpath->registerNamespace('entry','https://example.test/example/view.php');
  $ok=$ok && $xpath->evaluate('string(//entry:Title)')==='Example' && $dom->getElementsByTagNameNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#','Bag')->length===0;
 }catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'RDF absent associations '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);
