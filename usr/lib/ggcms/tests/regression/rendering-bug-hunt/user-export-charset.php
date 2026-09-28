<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
function ggreq($path) {}
class module_entrysort { public function __construct($args){} public function Sort($args){return $args['entries'];} }
class UserExportFixture {public $likedislikes;public $likes_count;public $comments_count;public $comments;public $user;public $script_format_lower;public $handler;
 public function Param($name){return FALSE;}
 public function render(){include dirname(__DIR__,3).'/src/templates/default/users/exportuser.php';}
}
$failed=0;
foreach(['UTF-8','ISO-8859-1'] as $default){
 ini_set('default_charset',$default);$comment="Caf\xC3\xA9 & bad\xFF";
 $script=new UserExportFixture();$script->handler=(object)['cleanser'=>(object)['utf8_characters'=>new class{public function SystemCharSet(){return 'UTF-8';}}]];$script->script_format_lower='txt';
 $script->user=['Username'=>'Reader','id'=>7,'OriginalCreationDate'=>'2026-09-12 12:00:00'];
 $script->comments=[['id'=>10,'Comment'=>$comment,'OriginalCreationDate'=>'2026-09-12 12:00:00','entry'=>['id'=>9,'parents'=>[]]]];$script->comments_count=1;$script->likes_count=1;
 $script->likedislikes=[['id'=>8,'LikeOrDislike'=>1,'OriginalCreationDate'=>'2026-09-12 12:00:00','entry'=>['id'=>9,'parents'=>[]]]];
 ob_start();$script->render();$output=ob_get_clean();
 $expected="Caf\xC3\xA9 & bad\xEF\xBF\xBD";
 $ok=strpos($output,$expected)!==FALSE && mb_check_encoding($output, 'UTF-8');
 echo ($ok?'PASS ':'FAIL ').'user text export default charset='.$default.PHP_EOL;
 if(!$ok){$failed++;}
}
exit($failed?1:0);