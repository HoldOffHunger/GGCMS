<?php
error_reporting(E_ALL);set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/RSS.php';
$feed=new RSS();$feed->handler=(object)['cleanser'=>(object)['utf8_characters'=>new class {public function SystemCharSet(){return 'UTF-8';}}]];
$failed=0;libxml_use_internal_errors(TRUE);
foreach([0.91,2.0] as $version){foreach([str_repeat('A',500),str_repeat('A',501),str_repeat('A',496).'&amp;'.str_repeat('B',10),str_repeat("\u{00e9}",510)] as $input){
 $feed->version_float=$version;$plain=html_entity_decode($input,ENT_QUOTES|ENT_HTML401,'UTF-8');$expected=($version===0.91&&mb_strlen($plain,'UTF-8')>500?mb_substr($plain,0,497,'UTF-8'):$plain).'...';
 $output=$feed->ConvertHTMLToFormat_renderContent_FormattedDescription(['entry'=>['description'=>[['Description'=>$input]],'textbody'=>[]]]);
 $doc=new DOMDocument();$ok=$doc->loadXML('<description>'.$output.'</description>',LIBXML_NONET)&&$doc->documentElement->textContent===$expected;
 libxml_clear_errors();echo ($ok?'PASS ':'FAIL ').'RSS length version='.$version.' characters='.mb_strlen($plain,'UTF-8').PHP_EOL;if(!$ok){$failed++;}
}}
exit($failed?1:0);