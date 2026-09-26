<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
function ggreq($path){require_once dirname(__DIR__,3).'/src/'.$path;}
class UserExportSortingFixture {
 public function Param($name){return FALSE;}
 public function render(){include dirname(__DIR__,3).'/src/templates/default/users/exportuser.php';}
}
$failed=0;
foreach(['empty','comments','likes','both'] as $mode){
 $script=new UserExportSortingFixture();$script->script_format_lower='txt';
 $script->handler=(object)['cleanser'=>(object)['utf8_characters'=>new class{public function SystemCharSet(){return 'UTF-8';}}]];
 $script->user=['Username'=>'Reader & Writer','id'=>7,'OriginalCreationDate'=>'2026-09-12 12:00:00'];
 $script->comments=[];$script->likedislikes=[];
 foreach([2,10] as $number){
  $entry=['id'=>$number,'Title'=>'Book '.$number,'parents'=>[['Title'=>'Book '.$number]]];
  $row=['id'=>$number,'OriginalCreationDate'=>'2026-09-12 12:00:00','entry'=>$entry];
  if($mode==='comments'||$mode==='both'){$script->comments[]=$row+['Comment'=>'Comment '.$number.' & <literal>'];}
  if($mode==='likes'||$mode==='both'){$script->likedislikes[]=$row+['LikeOrDislike'=>1];}
 }
 $script->comments_count=count($script->comments);$script->likes_count=count($script->likedislikes);
 ob_start();
 try{
  $script->render();$output=ob_get_clean();
  $ok=strpos($output,'User Export For : Reader & Writer')!==FALSE;
  $ok=$ok && strpos($output,'Comments : '.$script->comments_count)!==FALSE && strpos($output,'Likes/Dislikes : '.$script->likes_count)!==FALSE;
  foreach(['Comment'=>$script->comments,'Like/Dislike'=>$script->likedislikes] as $label=>$rows){
   if($rows){$first=strpos($output,$label.' id : 10');$second=strpos($output,$label.' id : 2');$ok=$ok && $first!==FALSE && $second!==FALSE && $first<$second;}
   else{$ok=$ok && strpos($output,$label.' id :')===FALSE;}
  }
  if($script->comments){$ok=$ok && strpos($output,'Comment 10 & <literal>')!==FALSE;}
 }catch(Throwable $error){if(ob_get_level()){ob_end_clean();}$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'user export real sorting '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);