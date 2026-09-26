<?php
namespace ImageRenameDiagnostic;
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new \ErrorException($m,0,$n);});
class Imagick {}
function rename($from,$to){$GLOBALS['renames'][]=[$from,$to];return count($GLOBALS['renames'])!==($GLOBALS['rename_fail_at']??0);}
$source=file_get_contents(dirname(__DIR__,3).'/src/scripts/modify.php');
$start=strpos($source,'public function SaveRecordFromQuery_Image()');$end=strpos($source,'public function DeleteFiles(',$start);
eval('namespace ImageRenameDiagnostic; class ImageMethod {'.substr($source,$start,$end-$start).'}');
class RenameFixture extends ImageMethod {
 public $errors=[]; public $image=[],$image_unset=[],$image_files=[],$entry=['id'=>7],$calls=0,$fail;
 public function __construct($fail){$this->fail=$fail;foreach([9,10] as $id){$this->image[]=['id'=>$id,'swapped'=>FALSE,'FileName'=>'new'.$id.'.png','OriginalCreationDate'=>'2026-01-01'];$this->image_unset[]=['FileDirectory'=>'abcd','FileName'=>'old'.$id.'.png'];$this->image_files[]=['name'=>''];}}
 public function SaveRecordFromQuery_Base($args){$this->calls++;return $this->fail&&$this->calls===2?FALSE:$this->image;}
 public function isUserAdmin(){return TRUE;}
 public function GetImageFolderDirectory(){return '/fixture/';}
 public function prepareImageForSaving($args){return $args['image'];}
 public function makeAlternateFileNames($args){return ['icon_name'=>'icon-'.$args['filename'],'standard_name'=>'standard-'.$args['filename']];}
}
$failed=0;
foreach([TRUE,FALSE] as $fail){$GLOBALS['renames']=[];$fixture=new RenameFixture($fail);$result=$fixture->SaveRecordFromQuery_Image();$expected=[];foreach($fail?[9]:[9,10] as $id){foreach(['','icon-','standard-'] as $prefix){$expected[]=['/fixture/a/b/c/d/'.$prefix.'old'.$id.'.png','/fixture/a/b/c/d/'.$prefix.'new'.$id.'.png'];}}$ok=$GLOBALS['renames']===$expected&&$fixture->calls===($fail?2:3)&&($fail?$result===FALSE:$result===$fixture->image);echo ($ok?'PASS ':'FAIL ').'image rename metadata '.($fail?'failure stops next image':'success').PHP_EOL;if(!$ok){$failed++;}}
foreach([1,2,3] as $stage){
 $GLOBALS['renames']=[];$GLOBALS['rename_fail_at']=$stage;
 $fixture=new RenameFixture(FALSE);$result=$fixture->SaveRecordFromQuery_Image();
 $ok=$result===FALSE&&count($GLOBALS['renames'])===$stage&&$fixture->calls===1&&count($fixture->errors)===1;
 echo ($ok?'PASS ':'FAIL ').'image file rename failure stage '.$stage.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);