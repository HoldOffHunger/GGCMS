<?php
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new ErrorException($message, 0, $level); });
require dirname(__DIR__, 3) . '/src/classes/Security/Authentication.php';
$failed = 0;
foreach(['missing'=>NULL, 'empty'=>'', 'array'=>['token'], 'nested'=>['token'=>['value']], 'valid'=>'fixture-token'] as $name=>$token) {
 $db = new class { public $calls=[]; public function UpdateRecord($args) { $this->calls[]=$args; return []; } };
 $cookie = new class { public $token; public function GetCookie($args) { return $this->token; } };
 $cookie->token=$token;
 $auth=new Authentication(['handler'=>(object)['db_access'=>$db, 'cookie'=>$cookie]]);
 $result=$auth->Logout_ResetDatabase();
 $ok=$result===[] && ($name==='valid' ? (count($db->calls)===1 && $db->calls[0]['where']['CookieToken']===$token) : $db->calls===[]);
 echo ($ok?'PASS ':'FAIL ').'logout token '.$name.PHP_EOL;
 if(!$ok) { $failed++; }
}
exit($failed?1:0);
