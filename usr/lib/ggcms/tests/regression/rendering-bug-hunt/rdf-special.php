<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/RDF.php';
require dirname(__DIR__,3).'/src/classes/Charset/UTF8Characters.php';
$failed=0;
foreach(['privacypolicy','termsofservice','userdata','comments','likesdislikes'] as $field){
 $value='Text & <b>literal markup</b>';
 $record=['id'=>7,'Code'=>'example','Title'=>'Example','Subtitle'=>'','ListTitle'=>'','OriginalCreationDate'=>'2026-09-14','LastModificationDate'=>'2026-09-14',$field=>$value];
 $rdf=new RDF();$rdf->script=(object)['record_to_use'=>$record];
 $rdf->handler=(object)['cleanser'=>(object)['utf8_characters'=>new UTF8Characters()]];
 $rdf->domain_object=new class {public function GetPrimaryDomain($args){return 'https://example.test';}};
 $_SERVER['REDIRECT_URL']='/example/view.rdf';$previous=libxml_use_internal_errors(TRUE);
 try{
  $dom=new DOMDocument();$ok=$dom->loadXML($rdf->ConvertHTMLToFormat());
  if($ok){$xpath=new DOMXPath($dom);$xpath->registerNamespace('entry','https://example.test/example/view.php');$xpath->registerNamespace('rdf','http://www.w3.org/1999/02/22-rdf-syntax-ns#');
   $nodes=$xpath->query('/rdf:RDF/rdf:Description/entry:'.$field);
   $ok=$nodes->length===1 && $nodes->item(0)->textContent===$value && $nodes->item(0)->getElementsByTagName('*')->length===0;
  }
 }catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 libxml_clear_errors();libxml_use_internal_errors($previous);
 echo ($ok?'PASS ':'FAIL ').'RDF special text '.$field.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);
