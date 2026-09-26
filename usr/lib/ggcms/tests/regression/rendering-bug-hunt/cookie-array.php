<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Networking/Cookie.php';
require dirname(__DIR__,3).'/src/classes/Security/HandleInput.php';
require dirname(__DIR__,3).'/src/classes/Charset/UTF8Characters.php';
$cleanser=(new ReflectionClass('HandleInput'))->newInstanceWithoutConstructor();$cleanser->utf8_characters=new UTF8Characters();$failed=0;
foreach([['Preference'=>['theme'=>'dark']],['Preference'=>['nested'=>['zero'=>'0']]],['Preference'=>'A & B']] as $index=>$cookies){
 $_COOKIE=$cookies;
 try{$cookie=new Cookie(['handler'=>(object)['cleanser'=>$cleanser]]);$ok=$cookie->cookie===$cookies&&$cookie->GetCookie(['cookie'=>'Preference'])===$cookies['Preference'];}catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'cookie array preservation case='.$index.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);