<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
libxml_use_internal_errors(TRUE);
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/XML.php';
class XMLTextFixture extends XML { public $content; public function RunTemplates(){return $this->content;} }
$format=new XMLTextFixture();
$format->handler=(object)['cleanser'=>(object)['utf8_characters'=>new class{public function SystemCharSet(){return 'UTF-8';}}]];
$failed=0;
foreach(['UTF-8','ISO-8859-1'] as $default){
 ini_set('default_charset',$default);
 foreach([['A & <B> "quoted"','A & <B> "quoted"'],["Caf\xC3\xA9 &amp;","Caf\xC3\xA9 &amp;"],["Bad\xFF byte","Bad\xEF\xBF\xBD byte"]] as $index=>[$input,$expected]){
  $format->content=['nested'=>[['Text'=>$input,'Zero'=>0]]];
  try{
   $output=$format->ConvertHTMLToFormat();
   $doc=new DOMDocument();$ok=$doc->loadXML($output,LIBXML_NONET);
   if($ok){$xpath=new DOMXPath($doc);$ok=$xpath->evaluate('string(/data/nested/item0/Text)')===$expected && $xpath->evaluate('string(/data/nested/item0/Zero)')==='0';}
  }catch(Throwable $error){$ok=FALSE;}
  echo ($ok?'PASS ':'FAIL ').'XML nested text default='.$default.' case='.$index.PHP_EOL;
  if(!$ok){$failed++;}libxml_clear_errors();
 }
}
exit($failed?1:0);