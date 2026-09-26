<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/ATOM.php';
class AtomFixture extends ATOM {
 public $image_link,$rows;
 public function RunTemplates(){}
 public function ConvertHTMLToFormat_renderImage($args){$this->image_link=$args['link'];return '';}
 public function getEntries(){return $this->rows;}public function ConvertHTMLToFormat_renderContent_FormattedDescription($args){return 'Description';}
 public function ConvertHTMLToFormat_lastBuildDate(){return '2026-09-10';}
}
$failed=0;
foreach([[],['books'],['books','essays'],['books&essays'],['a"b']] as $codes){foreach([FALSE,TRUE] as $readable){
 $feed=new AtomFixture();$feed->section_separator='';$feed->line_separator='';$feed->indent_levels=[1=>'',2=>''];$feed->human_readable=$readable;
 $feed->script=(object)['entry'=>['Title'=>'Fixture','Subtitle'=>''],'parent'=>['ChildNoun'=>'Entry'],'record_list'=>array_map(fn($code)=>['Code'=>$code],$codes)];
 $feed->handler=(object)['cleanser'=>(object)['utf8_characters'=>new class {public function SystemCharSet(){return 'UTF-8';}}],'domain'=>new class {public function GetPrimaryDomain($args){return 'https://example.test';}},'version'=>new class {public function GetSoftwareNameAcronymAndVersion(){return 'GGCMS';}},'globals'=>new class {public function AdminEmailAddress(){return 'test@example.test';}public function AdminName(){return 'Fixture';}public function SiteCategory(){return 'Books';}}];
 $feed->rows=[['Title'=>'Entry','Subtitle'=>'','GrandParent_Title'=>'','Parent_Title'=>'','GrandParent_Code'=>$codes[0]??'','Parent_Code'=>$codes[1]??'','Code'=>'entry','OriginalCreationDate'=>'2026-09-10','association'=>[],'PermaLinkid'=>7]];
 $base='https://example.test/'.($codes?implode('/',$codes).'/':'');
 try{$xml=$feed->ConvertHTMLToFormat();$doc=new DOMDocument();$parsed=$doc->loadXML($xml,LIBXML_NONET);$xpath=new DOMXPath($doc);$xpath->registerNamespace('a','http://www.w3.org/2005/Atom');$ok=$parsed&&$feed->image_link===$base.($codes?'view.php?action=index':'')&&$xpath->evaluate('string(/a:feed/a:link[@rel="self"]/@href)')===$base.'news.atom'.($readable?'?humanreadable=1':'')&&$xpath->evaluate('string(/a:feed/a:id)')===$base&&$xpath->evaluate('string(/a:feed/a:entry/a:link/@href)')===$base.'entry/';}catch(Throwable $e){$ok=FALSE;}
 echo ($ok?'PASS ':'FAIL ').'Atom links depth='.count($codes).' readable='.(int)$readable.PHP_EOL;if(!$ok){$failed++;}
}}
exit($failed?1:0);