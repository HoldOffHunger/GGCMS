<?php
error_reporting(E_ALL);set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require dirname(__DIR__, 3) . '/src/classes/Networking/Error404.php';
$failed=0;
foreach(['missing','unpublished','untitled','admin'] as $case){
 $entry=$case==='missing'?NULL:['id'=>7,'Title'=>$case==='untitled'?'':'<script>bad()</script>'];
 $handler=(object)['unavailable_entry'=>$entry,'authentication'=>(object)['user_session'=>['UserAdmin.id'=>$case==='admin'?1:0]]];
 $error=new Error404(['handler'=>$handler]);ob_start();$result=$error->Display([]);$html=ob_get_clean();
 $doc=new DOMDocument();$doc->loadHTML('<html><body>'.$html.'</body></html>');$xpath=new DOMXPath($doc);
 $ok=$result===TRUE&&$xpath->query('//a')->length===1&&$xpath->query('//a[@href="/"]')->length===1;
 if($case==='missing'){$ok=$ok&&strpos($html,'setTimeout')!==FALSE;}
 else{$ok=$ok&&strpos($html,'setTimeout')===FALSE&&strpos($html,'<script>bad()')===FALSE&&($xpath->query('//strong')->length>0)===($case==='admin');}
 echo ($ok?'PASS ':'FAIL ').'404 '.$case.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);
?>