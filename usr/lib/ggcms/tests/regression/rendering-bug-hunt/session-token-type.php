<?php
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new ErrorException($message, 0, $level); });
require dirname(__DIR__, 3) . '/src/classes/Security/Authentication.php';
$failed = 0;
foreach(['handler', 'cookie'] as $source) {
 foreach([['nested'=>'token'], ['nested'=>['token']], 'fixture-token'] as $token) {
  $row = ['id'=>8, 'Userid'=>7, 'User.id'=>7, 'User.Username'=>'Reader', 'User.EmailAddress'=>'reader@example.test'];
  $db = new class {
   public $calls = [];
   public $row;
   public function GetRecords($args) { $this->calls[] = $args; return [$this->row]; }
  };
  $db->row = $row;
  $cookie = new class {
   public $token;
   public function GetCookie($args) { return $this->token; }
  };
  $cookie->token = $token;
  $auth = new Authentication(['handler'=>(object)[
   'cookie_token'=>$source === 'handler' ? $token : NULL,
   'cookie'=>$cookie, 'db_access'=>$db,
  ]]);
  try {
   $result = $auth->CheckCurrentAuthentication();
   $ok = is_string($token)
    ? ($result === TRUE && count($db->calls) === 1 && $db->calls[0]['definition']['CookieToken'] === $token && $auth->user_session === $row)
    : ($result === 0 && $db->calls === [] && $auth->user_session === NULL && $auth->user_account === NULL);
  } catch(Throwable $error) { $ok = FALSE; echo $error->getMessage() . PHP_EOL; }
  echo ($ok ? 'PASS ' : 'FAIL ') . 'session token source=' . $source . ' type=' . gettype($token) . PHP_EOL;
  if(!$ok) { $failed++; }
 }
}
exit($failed ? 1 : 0);
