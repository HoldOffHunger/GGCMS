<?php
namespace CookieSetFixture;
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new \ErrorException($message,0,$level);});
$source=file_get_contents(dirname(__DIR__,3).'/src/classes/Networking/Cookie.php');
$start=strpos($source,'public function SetCookie(');$end=strpos($source,'public function GetCookie(',$start);
eval('namespace CookieSetFixture; class Cookie {public $handler;'.substr($source,$start,$end-$start).'public function TemporaryCookieExpirationTime(){return 10;}public function PermanentCookieExpirationTime(){return 20;}public function DeleteCookieExpirationTime(){return -10;}public function CookiePath(){return "/";}public function CookieHTTPOnlyOption(){return FALSE;}}');
function setcookie(...$args){$GLOBALS['cookie_args']=$args;return $GLOBALS['cookie_result'];}
$failed=0;
foreach(['default','secure','permanent','delete','failure'] as $mode){
 $cookie=new Cookie();$cookie->handler=(object)['domain'=>(object)['primary_domain_lowercased'=>'example.test']];
 $args=['key'=>'Fixture','value'=>$mode==='delete'?NULL:'value'];
 if($mode==='secure'){$args['secure']=TRUE;}
 if($mode==='permanent'){$args['permanent']=TRUE;}
 if($mode==='failure'){$args+=['secure'=>FALSE,'permanent'=>FALSE];}
 $GLOBALS['cookie_result']=$mode!=='failure';$GLOBALS['cookie_args']=NULL;
 try{$result=$cookie->SetCookie($args);$expected=['Fixture',$mode==='delete'?'':'value',$mode==='delete'?-10:(in_array($mode,['secure','permanent'])?20:10),'/', 'example.test',$mode==='secure',FALSE];$ok=$result===$GLOBALS['cookie_result']&&$GLOBALS['cookie_args']===$expected;}catch(\Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'cookie set '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);