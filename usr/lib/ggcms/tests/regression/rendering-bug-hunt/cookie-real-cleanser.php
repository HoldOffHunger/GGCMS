<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/Charset/UTF8Characters.php';
require dirname(__DIR__,3).'/src/classes/Security/HandleInput.php';
require dirname(__DIR__,3).'/src/classes/Networking/Cookie.php';
$utf8=new UTF8Characters();$failed=0;
foreach(['omitted','false','true','cookie'] as $mode){
 try{
  if($mode==='cookie'){$cleanser=(new ReflectionClass('HandleInput'))->newInstanceWithoutConstructor();$cleanser->utf8_characters=$utf8;$_COOKIE=['AuthenticationToken'=>'fixture-token','Preference'=>'A & B'];$cookie=new Cookie(['handler'=>(object)['cleanser'=>$cleanser]]);$ok=$cookie->GetCookie(['cookie'=>'Preference'])==='A & B';}
  else{$args=['input'=>'A & B','format'=>'UTF-8'];if($mode!=='omitted'){$args['convertentities']=$mode==='true';}$result=$utf8->CleanseInput_UTF8($args);$ok=$result['cleansedinput']===($mode==='true'?'A &#38; B':'A & B');}
 }catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'UTF8 cookie boundary '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);