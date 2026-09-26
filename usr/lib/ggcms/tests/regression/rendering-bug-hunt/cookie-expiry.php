<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Networking/Cookie.php';
$failed=0;
foreach([1700000000,1800000000] as $now){
 $cookie=(new ReflectionClass('Cookie'))->newInstanceWithoutConstructor();$cookie->handler=(object)['time'=>(object)['time'=>$now]];
 try{$deleted=$cookie->DeleteCookieExpirationTime();$ok=$deleted<0&&$deleted===-($now+10*365*24*60*60)&&$cookie->TemporaryCookieExpirationTime()===$now+4*60*60&&$cookie->PermanentCookieExpirationTime()===$now+10*365*24*60*60;}
 catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'cookie expiry clock='.$now.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);