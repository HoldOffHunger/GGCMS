<?php
error_reporting(E_ALL);set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/RSS.php';
class FallbackFixture extends RSS {public $rows;public function getEntries(){return $this->rows;}}
$failed=0;libxml_use_internal_errors(TRUE);
foreach(['missing'=>[],'null'=>['description'=>NULL,'textbody'=>NULL],'empty'=>['description'=>[],'textbody'=>[]],'text'=>['description'=>[],'textbody'=>[['FirstThousandCharacters'=>'Text &amp; more']]],'long'=>['description'=>[['Description'=>str_repeat('A',600)]],'textbody'=>[]]] as $mode=>$optional){
 $feed=new FallbackFixture();$feed->version_float=2.0;$feed->line_separator='';$feed->section_separator='';$feed->indent_levels=[1=>'',2=>'',3=>''];
 $feed->handler=(object)['domain'=>new class {public function GetPrimaryDomain($args){return 'https://example.test';}},'cleanser'=>(object)['utf8_characters'=>new class {public function SystemCharSet(){return 'UTF-8';}}]];
 $feed->script=(object)['parent'=>['ChildNoun'=>'Book','GrandChildNoun'=>'Book & <chapter>']];
 $feed->rows=[array_merge(['Title'=>'Entry','Subtitle'=>'','GrandParent_Title'=>'','Parent_Title'=>'','GrandParent_Code'=>'','Parent_Code'=>'','Code'=>'entry','OriginalCreationDate'=>'2026-09-10','association'=>[],'PermaLinkid'=>7,'Count'=>0],$optional)];
 $expected=$mode==='text'?'Text & more...':($mode==='long'?str_repeat('A',600).'...':'New Book & <chapter> with 0 chapters.');
 try{$xml=$feed->ConvertHTMLToFormat_renderContent();$doc=new DOMDocument();$ok=$doc->loadXML('<rss><channel>'.$xml.'</channel></rss>',LIBXML_NONET);if($ok){$xpath=new DOMXPath($doc);$xpath->registerNamespace('a','http://www.w3.org/2005/Atom');$ok=$xpath->evaluate('string(/rss/channel/item/description)')===$expected&&$xpath->query('//description/*')->length===0;}}catch(Throwable $e){$ok=FALSE;}
 libxml_clear_errors();echo ($ok?'PASS ':'FAIL ').'RSS summary fallback '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);