<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
// view.php's vote actions, lifted from its source and run against a LikeDislike table kept in memory.
$source=file_get_contents(dirname(__DIR__,3).'/src/scripts/view.php');$methods='';
foreach(['upvote','undoupvote','downvote','undodownvote','Vote','Unvote','SetUserAndEntry','GetUserLike','SetUserLike','RemoveUserVote'] as $name){
 if(!preg_match('/\t\tpublic function '.$name.'\(.*?\r?\n\t\t}\r?\n/s',$source,$m)){throw new Exception($name.' not found');}
 $methods.=$m[0];
}
eval('class VoteMethods {'.$methods.'}');
class VoteTable {
 public $rows=[],$next=1,$writes=[],$fail_create=FALSE;
 public function GetRecords($args){$d=$args['definition'];return array_values(array_filter($this->rows,fn($r)=>$r['Userid']===$d['Userid']&&$r['Entryid']===$d['Entryid']));}
 public function CreateRecord($args){$this->writes[]='create';if($this->fail_create){return ['line'=>1,'error'=>'refused'];}$row=['id'=>$this->next++]+$args['definition'];$this->rows[$row['id']]=$row;return $row;}
 public function UpdateRecord($args){$this->writes[]='update';$this->rows[$args['where']['id']]['LikeOrDislike']=$args['update']['LikeOrDislike'];return [$this->rows[$args['where']['id']]];}
 public function DeleteRecords($args){$this->writes[]='delete';unset($this->rows[$args['wherevalues'][0]]);return [];}
}
class VoteFixture extends VoteMethods {
 public $handler,$user_id,$orm=TRUE,$entry,$rpc_results;
 public function SetORMBasics(){}
}
function fixture($user,$entry,$table){$f=new VoteFixture();$f->handler=(object)['authentication'=>(object)['user_session'=>['User.id'=>$user]],'db_access'=>$table];$f->entry=$entry;return $f;}
$table=new VoteTable();$entry=['id'=>42];$failed=0;$lines=[];
$check=function($label,$ok) use (&$failed,&$lines){$lines[]=($ok?'PASS ':'FAIL ').'votes '.$label.PHP_EOL;if(!$ok){$failed++;}};
$r=fixture(NULL,$entry,$table)->upvote();
$check('anonymous upvote refused, nothing written',$r===['Success'=>0]&&$table->writes===[]);
$r=fixture(NULL,$entry,$table)->undoupvote();
$check('anonymous undo refused',$r===['Success'=>0]&&$table->writes===[]);
$r=fixture(7,NULL,$table)->upvote();
$check('no entry refused',$r===['Success'=>0]&&$table->writes===[]);
$r=fixture(7,$entry,$table)->upvote();$row=array_values($table->rows)[0]??[];
$check('first upvote made',$r===['Success'=>1]&&$table->writes===['create']&&$row['Userid']===7&&$row['Entryid']===42&&$row['LikeOrDislike']===1);
$r=fixture(7,$entry,$table)->upvote();
$check('the same vote again writes nothing',$r===['Success'=>1]&&$table->writes===['create']);
$r=fixture(7,$entry,$table)->downvote();
$check('downvote, called with no arguments, changes it',$r===['Success'=>1]&&$table->writes===['create','update']&&array_values($table->rows)[0]['LikeOrDislike']===0);
$r=fixture(7,$entry,$table)->undodownvote();
$check('undo removes it',$r===['Success'=>1]&&$table->rows===[]&&end($table->writes)==='delete');
$writes=count($table->writes);$r=fixture(7,$entry,$table)->undoupvote();
$check('undo with no vote succeeds untouched',$r===['Success'=>1]&&count($table->writes)===$writes);
$table->fail_create=TRUE;$r=fixture(7,$entry,$table)->upvote();
$check('a refused insert is not a success',$r===['Success'=>0]);
echo implode('',$lines);
exit($failed?1:0);
