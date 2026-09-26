<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/traits/LogRedaction.php';
require dirname(__DIR__,3).'/src/classes/Error/ErrorLogging.php';
class Redactor {use LogRedaction;}
$failed=0;
function check($ok,$name){global $failed;echo ($ok?'PASS ':'FAIL ').'log redaction '.$name.PHP_EOL;if(!$ok){$failed++;}}
function leaks($text){preg_match_all('/SENTINEL\d+/',$text,$m);return $m[0];}

$_SERVER=['HTTP_HOST'=>'example.test','REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/login.php?Password=SENTINEL1&next=/shelf&id_token=SENTINEL2',
 'QUERY_STRING'=>'Password=SENTINEL1&next=/shelf&id_token=SENTINEL2','HTTP_USER_AGENT'=>'Reader/1.0','HTTP_REFERER'=>'https://example.test/?usersessionid=SENTINEL3',
 'HTTP_COOKIE'=>'AuthenticationToken=SENTINEL4','HTTP_AUTHORIZATION'=>'Bearer SENTINEL5','PHP_AUTH_PW'=>'SENTINEL6','MYSQL_PWD'=>'SENTINEL7','REMOTE_ADDR'=>'192.0.2.1'];
$_POST=['username'=>'Reader','PASSWORD'=>'SENTINEL8','profile'=>['New_Password'=>'SENTINEL9','bio'=>'Kept text','nested'=>['google_credential'=>'SENTINEL10']],'usersessionid'=>'SENTINEL11'];
$_GET=['action'=>'login','Token'=>'SENTINEL12','list'=>[['api_key'=>'SENTINEL13']]];
$request=(new Redactor())->LoggableRequest();
$all=implode("\n",$request);
check(leaks($all)===[],'request carries no secrets');
check(strpos($request['post'],'Reader')!==FALSE && strpos($request['post'],'Kept text')!==FALSE,'ordinary post values kept');
check(strpos($request['server'],'HTTP_COOKIE')===FALSE && strpos($request['server'],'HTTP_AUTHORIZATION')===FALSE && strpos($request['server'],'MYSQL_PWD')===FALSE,'server reduced to allowlist');
check(strpos($request['server'],'example.test')!==FALSE && strpos($request['server'],'Reader/1.0')!==FALSE,'diagnostic server fields kept');
check($request['url']==='/login.php?Password=[REDACTED]&next=/shelf&id_token=[REDACTED]','URL keeps harmless parameters');
check(strpos($request['get'],'[action] => login')!==FALSE,'ordinary get values kept');

$handler=new class{public $script_file='login';public $script_format='HTML';public $globals;public $db_access;public $passwordseed='SENTINEL14';};
$handler->globals=new class{public $passwordseed='SENTINEL15';public function EnableErrorLogging(){return TRUE;}};
$handler->db_access=new class{public $records=[];public function CreateCountedRecord($args){$this->records[]=$args;return 1;}};
$logger=new ErrorLogging(['handler'=>$handler]);
restore_error_handler();
$logger->backupError(['error'=>'fixture failure','stacktrace'=>"#0 fixture.php(1): trace()\n#1 {main}"]);
$stored=print_r($handler->db_access->records,TRUE);
check(count($handler->db_access->records)===1 && leaks($stored)===[],'stored error carries no secrets');
check(strpos($stored,'#0 fixture.php(1): trace()')!==FALSE && strpos($stored,'[Script] => login')!==FALSE,'stored error keeps script and real trace');

foreach(['Error/ErrorLogging','Error/IssueLogging'] as $class){
 $source=file_get_contents(dirname(__DIR__,3).'/src/classes/'.$class.'.php');
 check(!preg_match('/print_r\(\$_(SERVER|POST|GET)|print_r\(\$this->handler/',$source),'no raw dumps in '.$class);
}
exit($failed?1:0);
