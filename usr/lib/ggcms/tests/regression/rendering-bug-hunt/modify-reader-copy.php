<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
// A reader's update to an entry is filed as an unpublished copy; the original must be left as it was.
// UpdatesInPlace(), BackupOldRecord() and DeleteChildRecordsForUpdate() lifted from modify.php, run against
// stand-ins and a scratch image folder.  Entry 50 is the original; a reader's copy is saved as 51.
$source=file_get_contents(dirname(__DIR__,3).'/src/scripts/modify.php');$methods='';
foreach(['UpdatesInPlace','BackupOldRecord','DeleteChildRecordsForUpdate'] as $name){
 if(!preg_match('/\t\tpublic function '.$name.'\(.*?\r?\n\t\t}\r?\n/s',$source,$m)){throw new Exception($name.' not found');}
 $methods.=$m[0];
}
eval('class ReaderCopyMethods {'.$methods.'}');
class ReaderCopyFixture extends ReaderCopyMethods {
 public $entry,$entry_unset,$record_list=[],$image=[],$delete_in_progress,$orm,$folder,$reserved=[],$created=[],$db_access_object,$authentication_object;
 public function __construct(){$this->orm=$this;$this->db_access_object=$this;$this->authentication_object=(object)['user_session'=>['id'=>7]];}
 public function Param($name){return NULL;}
 public function GetRecordTypes(){return ['Entry'=>'entry'];}
 public function GetRecordsPrimaryFields(){return ['Entry'=>['Title']];}
 public function DetectChangedRecords($args){return [['id'=>50,'conflictingfield'=>'Title','Title'=>'Old']];}
 public function BackupEntryCodeReservation($args){$this->reserved[]=$args;}
 public function CreateRecord($args){$this->created[]=$args;return $args['definition'];}
 public function GetStandardChildRecordTypes(){return ['Image'=>'image'];}
 public function DeleteChildRecords($args){return TRUE;}
 public function GetImageFolderDirectory(){return $this->folder;}
}
$folder=sys_get_temp_dir().'/ggcms-reader-copy-'.getmypid().'/';
function scene($entry_id,$code,$kept_image_ids,$folder){
 if(!is_dir($folder.'z/z')){mkdir($folder.'z/z',0777,TRUE);}file_put_contents($folder.'z/z/a.jpg','a');file_put_contents($folder.'z/z/a-icon.jpg','a');
 $f=new ReaderCopyFixture();$f->folder=$folder;
 $f->entry_unset=['id'=>'50','Code'=>'original','entry'=>NULL,'image'=>[['id'=>'500','FileName'=>'a.jpg','IconFileName'=>'a-icon.jpg','StandardFileName'=>'','FileDirectory'=>'zz']]];
 $f->entry=['id'=>$entry_id,'Code'=>$code];
 $f->image=array_map(function($id){return ['id'=>$id];},$kept_image_ids);
 return $f;
}
$failed=0;
$check=function($label,$ok) use (&$failed){echo ($ok?'PASS ':'FAIL ').'modify reader copy '.$label.PHP_EOL;if(!$ok){$failed++;}};
$f=scene('50','original',[],$folder);
$check('an administrator\'s update is in place',$f->UpdatesInPlace()===TRUE);
$f=scene('0','',[],$folder);
$check('a reader\'s update, before saving, is not',$f->UpdatesInPlace()===FALSE);
$f=scene('51','original-2',[],$folder);
$check('a reader\'s saved copy is not',$f->UpdatesInPlace()===FALSE);
$f=scene('0','reader-code',[],$folder);$f->BackupOldRecord();
$check('a reader\'s update reserves no path and records no change against the original',$f->reserved===[]&&$f->created===[]);
$f=scene('50','renamed',[],$folder);$f->BackupOldRecord();
$check('an administrator\'s rename reserves the old path and records the change',count($f->reserved)===1&&count($f->created)===1&&$f->created[0]['definition']['Entryid']==='50');
$f=scene('51','original-2',['501'],$folder);$f->DeleteChildRecordsForUpdate();
$check('a reader\'s copy leaves the original\'s image files',is_file($folder.'z/z/a.jpg')&&is_file($folder.'z/z/a-icon.jpg'));
$f=scene('50','original',[],$folder);$f->DeleteChildRecordsForUpdate();
$check('an administrator removing an image removes its files',!is_file($folder.'z/z/a.jpg')&&!is_file($folder.'z/z/a-icon.jpg'));
$f=scene('50','original',['500'],$folder);$f->DeleteChildRecordsForUpdate();
$check('an administrator keeping an image keeps its files',is_file($folder.'z/z/a.jpg'));
foreach(['a.jpg','a-icon.jpg'] as $file){if(is_file($folder.'z/z/'.$file)){unlink($folder.'z/z/'.$file);}}rmdir($folder.'z/z');rmdir($folder.'z');rmdir($folder);
exit($failed?1:0);
