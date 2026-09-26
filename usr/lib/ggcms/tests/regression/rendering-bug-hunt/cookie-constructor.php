<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Networking/Cookie.php';
$failed=0;
foreach([[],['AuthenticationToken'=>'fixture-token','Preference'=>'0']] as $index=>$cookies){
 $_COOKIE=$cookies;$cleanser=new class{public $calls=[];public function CleanseInput_EscapeBitVariableChars($args){$this->calls[]=$args;return ['cleansedinput'=>$args['input']];}};
 try{$cookie=new Cookie(['handler'=>(object)['cleanser'=>$cleanser]]);$ok=$cookie->cookie===$cookies;}
 catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'cookie constructor case='.$index.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);