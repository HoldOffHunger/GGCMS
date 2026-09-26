<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/traits/LogRedaction.php';require dirname(__DIR__,3).'/src/classes/Error/ErrorLogging.php';
class ShutdownFixture extends ErrorLogging {public $calls=[];public function __construct(){}public function mylog($error,$level,$trace){$this->calls[]=[$error,$level];return TRUE;}}
error_clear_last();$logger=new ShutdownFixture();
try{$result=$logger->shutdownHandler();$ok=$result===TRUE && $logger->calls===[];}catch(Throwable $error){$ok=FALSE;echo $error->getMessage().PHP_EOL;}
echo ($ok?'PASS ':'FAIL ').'clean request shutdown'.PHP_EOL;
exit($ok?0:1);