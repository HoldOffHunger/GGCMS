<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
// transfer.php's move, lifted from its source and run against a small tree kept in memory:
//   1 (root) -> 3 (site) -> 7 people -> 8 marina, 9 bakunin;  3 -> 20 archive, which holds a "bakunin" of its own
$source=file_get_contents(dirname(__DIR__,3).'/src/scripts/transfer.php');$methods='';
foreach(['AdminOnly','transferentry','SetTargetParent','SetConflictingCodeEntries','TargetIsWithinEntry'] as $name){
 if(!preg_match('/\t\tpublic function '.$name.'\(.*?\r?\n\t\t}\r?\n/s',$source,$m)){throw new Exception($name.' not found');}
 $methods.=$m[0];
}
eval('class TransferMethods {'.$methods.'}');
class TransferTree {
 public $parents=[3=>1,7=>3,8=>7,9=>7,20=>3,21=>20],$codes=[1=>'',3=>'site',7=>'people',8=>'marina',9=>'bakunin',20=>'archive',21=>'bakunin'],$updates=[],$fail_update=FALSE;
 public function GetRecords($args){
  if($args['type']==='Assignment'){$c=$args['definition']['Childid'];return isset($this->parents[$c])?[['Childid'=>$c,'Parentid'=>$this->parents[$c]]]:[];}
  preg_match('/Assignment\.Parentid = (\d+)/',$args['joins']['JOIN']['Assignment'],$m);$rows=[];
  foreach($this->parents as $child=>$parent){if($parent===(int)$m[1]&&$this->codes[$child]===$args['definition']['Code']){$rows[]=['id'=>$child];}}
  return $rows;
 }
 public function UpdateRecord($args){$this->updates[]=$args;return $this->fail_update?['line'=>1,'error'=>'refused']:[$args['update']];}
}
class TransferFixture extends TransferMethods {
 public $handler,$entry,$record_list=[],$target_parent,$new_parent_results,$selections,$admin_errors,$entry_update_args,$entry_update,$conflicting_entries,$conflicting_entry_count,$target,$backups=[];
 public function SetOrmBasics(){} public function SetRecordTree(){} public function ValidateOrm(){return TRUE;}
 public function Param($name){return $name==='target-parent'?$this->target:NULL;}
 public function SearchForEntries($args){$id=(int)$args['fieldvalue'];return isset($this->handler->db_access->codes[$id])?[['id'=>$id]]:[];}
 public function BackupEntryCodeReservation($args){$this->backups[]=$args;}
}
function transfer($entry_id,$target,$tree){$f=new TransferFixture();$f->handler=(object)['db_access'=>$tree];$f->target=$target;
 $f->entry=['id'=>$entry_id,'Code'=>$tree->codes[$entry_id],'assignment'=>[['id'=>100+$entry_id,'Childid'=>$entry_id,'Parentid'=>$tree->parents[$entry_id]]]];$f->transferentry();return $f;}
$failed=0;
$check=function($label,$ok) use (&$failed){echo ($ok?'PASS ':'FAIL ').'transfer '.$label.PHP_EOL;if(!$ok){$failed++;}};
$check('is for administrators only',(new TransferFixture())->AdminOnly()===TRUE);
$tree=new TransferTree();$f=transfer(8,20,$tree);
$check('moves an entry, reserving its old path first',count($tree->updates)===1&&$tree->updates[0]['update']['Parentid']===20&&$tree->updates[0]['where']['id']===108&&$f->backups[0]['entry']['id']===8&&$f->admin_errors===NULL);
$tree=new TransferTree();$f=transfer(9,20,$tree);
$check('refuses a code already under the target',$tree->updates===[]&&$f->conflicting_entry_count===1);
$tree=new TransferTree();$f=transfer(8,999,$tree);
$check('refuses a parent that does not exist',$tree->updates===[]&&$f->backups===[]&&count($f->admin_errors)===1);
$tree=new TransferTree();$f=transfer(7,7,$tree);
$check('refuses to move an entry under itself',$tree->updates===[]&&count($f->admin_errors)===1);
$tree=new TransferTree();$f=transfer(7,8,$tree);
$check('refuses to move an entry under its own child',$tree->updates===[]&&$f->backups===[]&&count($f->admin_errors)===1);
$tree=new TransferTree();$tree->fail_update=TRUE;$f=transfer(8,20,$tree);
$check('reports a move the database refused',count($f->admin_errors)===1&&$f->entry_update===NULL);
exit($failed?1:0);
