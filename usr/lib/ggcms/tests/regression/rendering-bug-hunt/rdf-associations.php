<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/RDF.php';
require dirname(__DIR__,3).'/src/classes/Charset/UTF8Characters.php';
$failed=0;
foreach([FALSE,TRUE] as $special){
foreach(['tag'=>'tag','image'=>'image','description'=>'description','quote'=>'quote','textbody'=>'text','eventdate'=>'event','link'=>'link'] as $key=>$property){
 $child=['id'=>12,'Tag'=>'Tag','Language'=>'en','OriginalCreationDate'=>'2026-09-14','LastModificationDate'=>'2026-09-14','FileName'=>'image.jpg','IconFileName'=>'icon.jpg','PixelWidth'=>100,'PixelHeight'=>100,'IconPixelWidth'=>10,'IconPixelHeight'=>10,'Description'=>'Description','Source'=>'Source','Quote'=>'Quote','Text'=>'Text','WordCount'=>1,'CharacterCount'=>4,'EventDateTime'=>'2026-09-14','Title'=>'Title','URL'=>'https://example.test/'];
 $expected_language=$special?"A & <test> 日本語 \xFF":'en';
 $child['Language']=$expected_language;
 if($special){$child['FileDirectory']='ab';$child['FileName']='a&b #.jpg';$child['IconFileName']='c&d ?.jpg';}
 $record=['id'=>7,'Code'=>'example','Title'=>'Example','Subtitle'=>'','ListTitle'=>'','OriginalCreationDate'=>'2026-09-14','LastModificationDate'=>'2026-09-14',$key=>[$child]];
 $rdf=new RDF();$rdf->script=(object)['record_to_use'=>$record];$rdf->handler=(object)['cleanser'=>(object)['utf8_characters'=>new UTF8Characters()]];
 $rdf->domain_object=new class{public function GetPrimaryDomain($args){return 'https://example.test';}};
 $_SERVER['REDIRECT_URL']='/example/view.rdf';$previous=libxml_use_internal_errors(TRUE);
 try{
  $dom=new DOMDocument();$ok=$dom->loadXML($rdf->ConvertHTMLToFormat());
  $errors=libxml_get_errors();$ok=$ok && count($errors)===0;
  if($ok){$xpath=new DOMXPath($dom);$xpath->registerNamespace('rdf','http://www.w3.org/1999/02/22-rdf-syntax-ns#');$xpath->registerNamespace('entry','https://example.test/example/view.php');$xpath->registerNamespace('child','https://example.test/example/view.php#'.$property.':');
   $base='/rdf:RDF/rdf:Description/entry:'.$property.'/rdf:Bag/rdf:li';
   $ok=$xpath->evaluate('count('.$base.')')===1.0 && $xpath->evaluate('string('.$base.'/@rdf:parseType)')==='Resource' && $xpath->evaluate('string('.$base.'/child:id)')==='12';
   if($key!=='image'){$ok=$ok && $xpath->evaluate('string('.$base.'/child:Language)')===str_replace("\xFF","\xEF\xBF\xBD",$expected_language);}
   else{$ok=$ok && $xpath->evaluate('string('.$base.'/child:FileURL)')==='https://example.test/image/'.($special?'a/b/':'').rawurlencode($child['FileName']) && $xpath->evaluate('string('.$base.'/child:IconFileURL)')==='https://example.test/image/'.($special?'a/b/':'').rawurlencode($child['IconFileName']);}
  }
 }catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 libxml_clear_errors();libxml_use_internal_errors($previous);
 echo ($ok?'PASS ':'FAIL ').'RDF association '.$key.' special='.(int)$special.PHP_EOL;if(!$ok){$failed++;}
}
}
exit($failed?1:0);
