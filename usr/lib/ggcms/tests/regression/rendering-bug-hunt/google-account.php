<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require dirname(__DIR__,3).'/src/classes/API/Google.php';
class AccountGoogle extends Google { public function __construct() {} public function handleLoginCookie() { return TRUE; } public function VerifyIdToken($args) { return ['email'=>'reader@example.test', 'email_verified'=>TRUE]; } }
$failed=0;
foreach(['existing','new','lookup-error','create-error','empty-error','id-error'] as $mode) {
 $row=['id'=>7,'Username'=>'Reader','EmailAddress'=>'reader@example.test'];
 $db=new class {
  public $lookup, $created, $creates=0;
  public function GetRecords($args) { return $this->lookup; }
  public function CreateRecord($args) { $this->creates++; return $this->created; }
 };
 $db->lookup=$mode==='existing'?[$row]:($mode==='lookup-error'?['line'=>123,'error'=>'lookup failure']:[]);
 $db->created=$mode==='create-error'?['line'=>123,'error'=>'create failure']:$row;
 if($mode==='empty-error') { $db->created=NULL; }
 if($mode==='id-error') { $db->created=['EmailAddress'=>'reader@example.test']; }
 $auth=new class {
  public $user_account, $calls=[], $checks=0;
  public function Login_Successful($args) { $this->calls[]=$args; return ['Success'=>TRUE]; }
  public function CheckCurrentAuthentication() { $this->checks++; return TRUE; }
 };
 $google=new AccountGoogle(); $google->client_id='fixture-client';
 $google->handler=(object)['db_access'=>$db,'authentication'=>$auth,'globals'=>(object)['passwordseed'=>'fixture-seed']];
 $caught=FALSE; $unexpected=FALSE; $result=NULL;
 try { $result=$google->AuthenticateOrDisauthenticateWithGoogle(['token'=>'fixture-token','logout'=>FALSE]); }
 catch(RuntimeException $error) { $caught=TRUE; }
 catch(Throwable $error) { $unexpected=TRUE; echo $error->getMessage().PHP_EOL; }
 $failure=strpos($mode,'error')!==FALSE;
 $ok=!$unexpected && ($failure
  ? ($caught && $auth->calls===[] && $auth->checks===0 && $auth->user_account===NULL)
  : (!$caught && $auth->calls===[['useraccount'=>[$row]]] && $auth->checks===1 && $result===['newuser'=>$mode==='new'?1:0,'action'=>'login']));
 $ok=$ok && $db->creates===(in_array($mode,['new','create-error','empty-error','id-error'])?1:0);
 echo ($ok?'PASS ':'FAIL ').'Google account '.$mode.PHP_EOL; if(!$ok){$failed++;}
}
exit($failed?1:0);
