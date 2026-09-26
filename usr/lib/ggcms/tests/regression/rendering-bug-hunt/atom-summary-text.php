<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/ATOM.php';
$feed=new ATOM();$feed->version_float=1.0;
$feed->handler=(object)['cleanser'=>(object)['utf8_characters'=>new class {public function SystemCharSet(){return 'UTF-8';}}]];
$failed=0;
foreach([
 ['Plain','Plain'],
 ['A &amp; B','A & B'],
 ['&lt;literal&gt;','<literal>'],
 ['&quot;quoted&quot; &gt; end','"quoted" > end'],
 ['<p>First</p><p>Second</p>','First Second'],
 ["Caf\u{00e9} &amp; tea","Caf\u{00e9} & tea"],
] as [$input,$expected]){
 $output=$feed->ConvertHTMLToFormat_renderContent_FormattedDescription(['entry'=>['description'=>[['Description'=>$input]],'textbody'=>[]]]);
 $doc=new DOMDocument();$ok=$doc->loadXML('<summary>'.$output.'</summary>',LIBXML_NONET)&&$doc->documentElement->textContent===$expected.'...'&&$doc->documentElement->getElementsByTagName('*')->length===0;
 echo ($ok?'PASS ':'FAIL ').'Atom summary '.json_encode($input).PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);