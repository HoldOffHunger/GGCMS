<?php
error_reporting(E_ALL);set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/OPDS.php';
class OPDSFixture extends OPDS {public function __construct(){}public function SetAuthor(){return 'Author';}public function SetDescription(){return 'Description';}}
$failed=0;$_SERVER['REDIRECT_URL']='/book/view.opds';
foreach(['Original title','Revised title'] as $title){
 $feed=new OPDSFixture();$feed->handler=(object)['cleanser'=>(object)['utf8_characters'=>new class{public function SystemCharSet(){return 'UTF-8';}}]];$feed->opds_filename='book';
 $feed->domain_object=new class{public $host='example.test';public function GetPrimaryDomain($args){return 'https://example.test';}};
 $feed->script=(object)['master_record'=>['Code'=>'catalog','Title'=>'Catalog','OriginalCreationDate'=>'2026-09-10'],'record_to_use'=>['Title'=>$title,'Subtitle'=>'','LastModificationDate'=>'2026-09-10','OriginalCreationDate'=>'2026-09-10','image'=>[],'privacypolicy'=>'Policy'],'subject'=>'Books','handler'=>(object)['abstractglobals'=>(object)['site'=>new class{public function Creator(){return 'Creator';}}]]];
 $feed->mimetype=new class{public function GetMIMETypeCodes(){return [];}};$feed->format_object=new class{public function GetListOfSupportedFormatExtensions(){return [];}};
 try{$xml=$feed->ConvertHTMLToFormat();$doc=new DOMDocument();$ok=$doc->loadXML($xml,LIBXML_NONET);if($ok){$xpath=new DOMXPath($doc);$xpath->registerNamespace('a','http://www.w3.org/2005/Atom');$ok=$xpath->evaluate('string(/a:feed/a:entry/a:id)')===$feed->id&&$feed->id==='example.test_book'&&$xpath->evaluate('string(/a:feed/a:entry/a:title)')===$title&&$xpath->evaluate('string(/a:feed/a:entry/a:privacypolicy)')==='Policy';}}catch(Throwable $e){$ok=FALSE;}
 echo ($ok?'PASS ':'FAIL ').'OPDS entry ID '.$title.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);