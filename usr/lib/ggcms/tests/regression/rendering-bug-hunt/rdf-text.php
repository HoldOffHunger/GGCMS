<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/RDF.php';
require dirname(__DIR__,3).'/src/classes/Charset/UTF8Characters.php';
$failed=0;
foreach(['plain'=>'Example','markup'=>'A & B <test> "quoted"','unicode'=>'Été 日本語','invalid'=>"bad \xFF", 'controls'=>"bad \x00\x0B", 'whitespace'=>"tab\tline\nnext"] as $name=>$value){
 $record=['id'=>7,'Code'=>'example','Title'=>$value,'Subtitle'=>$value,'ListTitle'=>$value,'OriginalCreationDate'=>'2026-09-14','LastModificationDate'=>'2026-09-14'];
 $rdf=new RDF();$rdf->script=(object)['record_to_use'=>$record];
 $rdf->handler=(object)['cleanser'=>(object)['utf8_characters'=>new UTF8Characters()]];
 $rdf->domain_object=new class {public function GetPrimaryDomain($args){return 'https://example.test';}};
 $_SERVER['REDIRECT_URL']='/example/view.rdf';
 $previous=libxml_use_internal_errors(TRUE);
 try{
  $output=$rdf->ConvertHTMLToFormat();$dom=new DOMDocument();$ok=$dom->loadXML($output);
  if($ok){$xpath=new DOMXPath($dom);$xpath->registerNamespace('entry','https://example.test/example/view.php');
   foreach(['Title','Subtitle','ListTitle'] as $field){$ok=$ok && $xpath->evaluate('string(//entry:'.$field.')')===str_replace(["\xFF","\x00","\x0B"],"\xEF\xBF\xBD",$value);}
   $ok=$ok && $dom->getElementsByTagName('test')->length===0;
  }
 }catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 libxml_clear_errors();libxml_use_internal_errors($previous);
 echo ($ok?'PASS ':'FAIL ').'RDF metadata '.$name.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);
