<?php
error_reporting(E_ALL);set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/RSS.php';
$feed=new RSS();$feed->handler=(object)['cleanser'=>(object)['utf8_characters'=>new class{public function SystemCharSet(){return 'UTF-8';}}],'domain'=>new class{public function GetPrimaryDomain($args){return 'https://example.test';}}];
$feed->indent_levels=[2=>'',3=>''];$feed->line_separator='';$failed=0;libxml_use_internal_errors(TRUE);
foreach(['Plain','A & B','<b>Cover</b>','Literal &amp; entity',"Caf\u{00e9}",'query-link'] as $value){$shown=html_entity_decode($value,ENT_QUOTES|ENT_HTML5,'UTF-8');
 $link=$value==='query-link'?'https://example.test/view.php?a=1&b=2':'https://example.test/books/';
 $feed->script=(object)['entry'=>['image'=>[['Title'=>$value,'Description'=>$value,'FileDirectory'=>'ab','IconFileName'=>'cover & icon.png','IconPixelWidth'=>140,'IconPixelHeight'=>70]]]];
 $result=$feed->ConvertHTMLToFormat_renderImage(['link'=>$link]);$doc=new DOMDocument();$ok=$doc->loadXML($result,LIBXML_NONET);
 if($ok){$xpath=new DOMXPath($doc);$ok=$xpath->evaluate('string(/image/title)')===$shown&&$xpath->evaluate('string(/image/description)')===$shown&&$xpath->evaluate('string(/image/link)')===$link&&$xpath->evaluate('string(/image/url)')==='https://example.test/image/a/b/'.urlencode('cover & icon.png')&&$xpath->query('/image/title/*|/image/description/*')->length===0;}
 libxml_clear_errors();echo ($ok?'PASS ':'FAIL ').'RSS image '.json_encode($value).PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);