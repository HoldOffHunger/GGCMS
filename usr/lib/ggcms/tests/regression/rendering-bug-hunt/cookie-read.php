<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Networking/Cookie.php';
$failed=0;
foreach([[],['Fixture'=>''],['Fixture'=>'0'],['Fixture'=>'token']] as $index=>$values){
 $cookie=(new ReflectionClass('Cookie'))->newInstanceWithoutConstructor();$cookie->cookie=$values;
 try{$result=$cookie->GetCookie(['cookie'=>'Fixture']);$ok=$result===($values['Fixture']??NULL);}catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'cookie read case='.$index.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);