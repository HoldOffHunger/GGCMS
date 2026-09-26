<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/ATOM.php';
class TitleFixture extends ATOM {
 public $rows;
 public function RunTemplates(){}
 public function ConvertHTMLToFormat_renderImage($args){return '';}
 public function ConvertHTMLToFormat_lastBuildDate(){return '2026-09-10';}
 public function getEntries(){return $this->rows;}
 public function ConvertHTMLToFormat_renderContent_FormattedDescription($args){return 'Description';}
}
$failed=0;libxml_use_internal_errors(TRUE);
foreach(['Plain','A & B','<b>Title</b>','Literal &amp; entity', 'A "quoted" category',"Caf\u{00e9} 'quoted'"] as $value){$shown=html_entity_decode($value,ENT_QUOTES|ENT_HTML5,'UTF-8');
 $feed=new TitleFixture();$feed->section_separator='';$feed->line_separator='';$feed->indent_levels=[1=>'',2=>''];$feed->human_readable=FALSE;
 $feed->script=(object)['entry'=>['Title'=>$value,'Subtitle'=>$value],'record_list'=>[],'parent'=>['ChildNoun'=>$value,'GrandChildNoun'=>'Chapter']];
 $feed->rows=[['Title'=>$value,'Subtitle'=>$value,'GrandParent_Title'=>$value,'Parent_Title'=>$value,'GrandParent_Code'=>'','Parent_Code'=>'','Code'=>'entry','OriginalCreationDate'=>'2026-09-10','association'=>[['entry'=>['Title'=>$value]]],'PermaLinkid'=>7]];
 $feed->handler=(object)['cleanser'=>(object)['utf8_characters'=>new class {public function SystemCharSet(){return 'UTF-8';}}],'domain'=>new class {public function GetPrimaryDomain($args){return 'https://example.test';}},'version'=>new class {public $text;public function GetSoftwareNameAcronymAndVersion(){return $this->text;}},'globals'=>new class {public $text;public function AdminEmailAddress(){return $this->text;}public function AdminName(){return $this->text;}public function SiteCategory(){return $this->text;}}];
 $feed->handler->globals->text=$value;$feed->handler->version->text=$value;
 $xml=$feed->ConvertHTMLToFormat();$doc=new DOMDocument();$ok=$doc->loadXML($xml,LIBXML_NONET);
 if($ok){$xpath=new DOMXPath($doc);$xpath->registerNamespace('a','http://www.w3.org/2005/Atom');$ok=$xpath->evaluate('string(/a:feed/a:title)')===$shown.': '.$shown&&$xpath->evaluate('string(/a:feed/a:entry/a:title)')==='['.$shown.'] '.$shown.' -- '.$shown.': '.$shown&&$xpath->query('//a:title/*')->length===0;}
 if($ok){foreach(['/a:feed/a:author/a:name','/a:feed/a:author/a:email','/a:feed/a:category/@term','/a:feed/a:entry/a:author/a:name','/a:feed/a:entry/a:category/@term'] as $path){$ok=$ok&&$xpath->evaluate('string('.$path.')')===$shown;}$ok=$ok&&$xpath->query('//a:author/a:name/*')->length===0&&$xpath->query('//a:category/@*')->length===2;}
 if($ok){$ok=$xpath->evaluate('string(/a:feed/a:generator)')===$shown.', Released Under BSD 3-Clause License'&&$xpath->query('//a:generator/*')->length===0;}
 libxml_clear_errors();echo ($ok?'PASS ':'FAIL ').'Atom metadata '.json_encode($value).PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);