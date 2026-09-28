<?php
error_reporting(E_ALL);set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/OPDS.php';
class OPDSFixture extends OPDS {public $opds_filename;public function __construct(){}public function SetAuthor(){return $this->script->record_to_use['Title'];}public function SetDescription(){return $this->script->record_to_use['Title'];}}
$failed=0;$_SERVER['REDIRECT_URL']='/book/view.opds';
foreach(['Plain','A & B','<b>Title</b>','Literal &amp; entity','A "quoted" category'] as $title){$shown=html_entity_decode($title,ENT_QUOTES|ENT_HTML5,'UTF-8');
 $feed=new OPDSFixture();$feed->handler=(object)['cleanser'=>(object)['utf8_characters'=>new class{public function SystemCharSet(){return 'UTF-8';}}]];$feed->opds_filename='book';
 $feed->domain_object=new class{public $host='example.test';public function GetPrimaryDomain($args){return 'https://example.test';}};
 $feed->script=(object)['master_record'=>['Code'=>'catalog','Title'=>$title,'OriginalCreationDate'=>'2026-09-10'],'record_to_use'=>['Title'=>$title,'Subtitle'=>'','LastModificationDate'=>'2026-09-10','OriginalCreationDate'=>'2026-09-10','image'=>[],'privacypolicy'=>$title],'subject'=>$title,'handler'=>(object)['abstractglobals'=>(object)['site'=>new class{public $text;public function Creator(){return $this->text;}}]]];
 $feed->script->handler->abstractglobals->site->text=$title;
 $feed->mimetype=new class{public function GetMIMETypeCodes(){return [];}};$feed->format_object=new class{public function GetListOfSupportedFormatExtensions(){return [];}};
 try{$xml=$feed->ConvertHTMLToFormat();$doc=new DOMDocument();$ok=$doc->loadXML($xml,LIBXML_NONET);if($ok){$xpath=new DOMXPath($doc);$xpath->registerNamespace('a','http://www.w3.org/2005/Atom');$ok=$xpath->evaluate('string(/a:feed/a:entry/a:id)')===$feed->id&&$feed->id==='example.test_book'&&$xpath->evaluate('string(/a:feed/a:entry/a:title)')===$shown&&$xpath->evaluate('string(/a:feed/a:entry/a:privacypolicy)')===$title;}}catch(Throwable $e){$ok=FALSE;}
 if($ok){foreach(['/a:feed/a:title','/a:feed/a:author/a:name','/a:feed/a:entry/a:author/a:name','/a:feed/a:entry/a:summary','/a:feed/a:entry/a:category/@label'] as $path){$ok=$ok&&$xpath->evaluate('string('.$path.')')===$shown;}$ok=$ok&&$xpath->query('//a:title/*|//a:name/*|//a:summary/*|//a:privacypolicy/*')->length===0&&$xpath->query('//a:category/@*')->length===1;}
 echo ($ok?'PASS ':'FAIL ').'OPDS metadata '.$title.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);