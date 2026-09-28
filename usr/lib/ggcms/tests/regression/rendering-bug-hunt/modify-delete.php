<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
// Who may delete an entry through modify.php: canUserDelete() from SimpleORM, Delete() and Update() from
// modify.php, lifted from their sources.  The deletions are stand-ins that only count what they were asked.
$lift=function($file,$names){$source=file_get_contents(dirname(__DIR__,3).'/src/'.$file);$methods='';
 foreach($names as $name){if(!preg_match('/\t\tpublic function '.$name.'\(.*?\r?\n\t\t}\r?\n/s',$source,$m)){throw new Exception($name.' not found in '.$file);}$methods.=$m[0];}
 return $methods;};
eval('class ModifyDeleteMethods {'.$lift('traits/scripts/SimpleORM.php',['canUserDelete']).$lift('scripts/modify.php',['Update','Delete']).'}');
class ModifyDeleteFixture extends ModifyDeleteMethods {
 public $handler,$entry,$admin=FALSE,$params=[],$deleted=[],$updated=FALSE,$delete_in_progress,$save_status,$saveattemptresults;
 public function isUserAdmin(){return $this->admin;}
 public function Param($name){return $this->params[$name]??NULL;}
 public function SetOrmBasics(){} public function ValidateOrm(){return TRUE;} public function OrderAndFillChildRecords(){}
 public function FormatEntryInformation(){$this->updated=TRUE;} public function canUserAccess(){return TRUE;}
 public function DeleteChildRecordsForUpdate(){$this->deleted[]='children';return TRUE;}
 public function DeleteEntry(){$this->deleted[]='entry';return [];}
 public function DeleteAssignment(){$this->deleted[]='assignment';return [];}
}
// entry 50 was submitted by reader 7; the administrator is 1
function attempt($user,$admin,$publish,$params,$owner=7){$f=new ModifyDeleteFixture();$f->admin=$admin;$f->params=$params;
 $f->handler=(object)['authentication'=>(object)['user_account'=>$user?['id'=>(string)$user]:NULL]];
 $f->entry=['id'=>50,'Publish'=>$publish,'entrypermission'=>$owner?[['Userid'=>(string)$owner]]:[]];
 $result=isset($params['Delete'])?$f->Update():$f->Delete();return [$f,$result];}
$failed=0;
$check=function($label,$ok) use (&$failed){echo ($ok?'PASS ':'FAIL ').'modify delete '.$label.PHP_EOL;if(!$ok){$failed++;}};
[$f,$r]=attempt(1,TRUE,1,[]);
$check('lets an administrator delete a published entry',$f->deleted===['children','entry','assignment']&&$f->saveattemptresults===TRUE);
[$f,$r]=attempt(9,FALSE,1,[]);
$check('refuses a reader a published entry',$r===FALSE&&$f->deleted===[]);
[$f,$r]=attempt(9,FALSE,1,['Delete'=>'Delete']);
$check('refuses a reader a published entry through Update, and does not update it instead',$r===FALSE&&$f->deleted===[]&&$f->updated===FALSE);
[$f,$r]=attempt(7,FALSE,0,[]);
$check('lets a reader withdraw their own unpublished submission',$f->deleted===['children','entry','assignment']);
[$f,$r]=attempt(7,FALSE,1,[]);
$check('refuses a reader their own submission once published',$r===FALSE&&$f->deleted===[]);
[$f,$r]=attempt(9,FALSE,0,[]);
$check('refuses a reader someone else\'s unpublished submission',$r===FALSE&&$f->deleted===[]);
[$f,$r]=attempt(9,FALSE,0,[],0);
$check('refuses a reader an unpublished entry nobody submitted',$r===FALSE&&$f->deleted===[]);
[$f,$r]=attempt(0,FALSE,0,[],0);
$check('refuses without a user',$r===FALSE&&$f->deleted===[]);
exit($failed?1:0);
