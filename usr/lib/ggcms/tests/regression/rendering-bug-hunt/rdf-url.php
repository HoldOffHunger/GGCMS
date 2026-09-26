<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/RDF.php';
require dirname(__DIR__,3).'/src/classes/Charset/UTF8Characters.php';
$failed=0;
foreach(['/example/','/a&b/','/a&amp;b/'] as $path){
 $rdf=new RDF();$rdf->script=(object)['record_to_use'=>['id'=>7,'Code'=>'example','Title'=>'Example','Subtitle'=>'','ListTitle'=>'','OriginalCreationDate'=>'2026-09-14','LastModificationDate'=>'2026-09-14']];
 $rdf->handler=(object)['cleanser'=>(object)['utf8_characters'=>new UTF8Characters()]];
 $rdf->domain_object=new class {public function GetPrimaryDomain($args){return 'https://example.test';}};
 $_SERVER['REDIRECT_URL']=$path.'view.rdf';$expected='https://example.test'.$path.'view.php';
 $previous=libxml_use_internal_errors(TRUE);
 try{
  $dom=new DOMDocument();$ok=$dom->loadXML($rdf->ConvertHTMLToFormat());
  if($ok){$description=$dom->getElementsByTagNameNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#','Description')->item(0);
   $ok=$description!==NULL && $description->getAttributeNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#','about')===$expected && str_replace('&#38;','&',$dom->documentElement->lookupNamespaceURI('entry'))===$expected;
  }
 }catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 // PHP/libxml exposes namespace ampersands as numeric references; decode one layer above.
 libxml_clear_errors();libxml_use_internal_errors($previous);
 echo ($ok?'PASS ':'FAIL ').'RDF URL '.$path.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);
