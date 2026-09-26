<?php
namespace UploadMoveDiagnostic;
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new \ErrorException($m,0,$n);});
class Imagick {}
class Base {public function ConvertBase($args){return 'wxyz';}}
function is_file($path){return TRUE;}
function unlink($path){$GLOBALS['deleted'][]=$path;$GLOBALS['events'][]='unlink';return !($GLOBALS['cleanup_failure']??FALSE);}
function hash_file($algorithm,$path){return ($GLOBALS['hash_failure']??FALSE)?FALSE:'1234';}
function move_uploaded_file($from,$to){$GLOBALS['moves'][]=[$from,$to];return $GLOBALS['move_success'];}
$source=file_get_contents(dirname(__DIR__,3).'/src/scripts/modify.php');
$start=strpos($source,'public function SaveRecordFromQuery_Image()');$end=strpos($source,'public function DeleteFiles(',$start);
eval('namespace UploadMoveDiagnostic; class ImageMethod {'.substr($source,$start,$end-$start).'}');
class UploadFixture extends ImageMethod {
 public $resize_failure=NULL; public $directory_failure=FALSE; public $metadata_failure=FALSE; public $errors=[],$image=[['id'=>9,'swapped'=>FALSE,'OriginalCreationDate'=>'2026-01-01','FileName'=>'new.png']],$image_unset=[['FileDirectory'=>'abcd','FileName'=>'old.png','IconFileName'=>'icon.png','StandardFileName'=>'standard.png']],$image_files=[['name'=>'new.png','tmp_name'=>'/fixture/tmp','icon_name'=>'icon-new.png','standard_name'=>'standard-new.png','error'=>0]],$entry=['id'=>7],$calls=0,$resizes=[];
 public function SaveRecordFromQuery_Base($args){$this->calls++;$GLOBALS['events'][]='write';return $this->metadata_failure&&$this->calls===2?FALSE:$this->image;}
 public function isUserAdmin(){return TRUE;}
 public function GetImageFolderDirectory(){return '/fixture/';}
 public function UpdateImagesDirectory($args){return $this->directory_failure?FALSE:'/fixture/w/x/y/z/';}
 public function prepareImageForSaving($args){return $args['image'];}
 public function updateFileName($args){return $args['filename'];}
 public function makeIcon($args){$this->resizes[]=$args;if($this->resize_failure==='icon'){return FALSE;}return ['originalheight'=>100,'originalwidth'=>200,'resizedheight'=>25,'resizedwidth'=>50];}
 public function makeStandardImage($args){$this->resizes[]=$args;if($this->resize_failure==='standard'){return FALSE;}return ['resizedheight'=>50,'resizedwidth'=>100];}
}
$failed=0;
foreach([FALSE,TRUE] as $success){
 $GLOBALS['moves']=[];$GLOBALS['move_success']=$success;$fixture=new UploadFixture();$result=$fixture->SaveRecordFromQuery_Image();
 $ok=$GLOBALS['moves']===[['/fixture/tmp','/fixture/w/x/y/z/new.png']]&&$fixture->calls===($success?2:1)&&count($fixture->resizes)===($success?2:0)&&($success?($result===$fixture->image&&$fixture->image[0]['FileDirectory']==='wxyz'&&$fixture->errors===[]):($result===FALSE&&count($fixture->errors)===1));
 echo ($ok?'PASS ':'FAIL ').'upload move '.($success?'success':'failure stops resize').PHP_EOL;if(!$ok){$failed++;}
}
foreach(['move-failure','metadata-failure','distinct','same-paths'] as $mode){
 $GLOBALS['moves']=[];$GLOBALS['deleted']=[];$GLOBALS['events']=[];$GLOBALS['move_success']=$mode!=='move-failure';$fixture=new UploadFixture();$fixture->metadata_failure=$mode==='metadata-failure';
 if($mode==='same-paths'){$fixture->image_unset[0]=['FileDirectory'=>'wxyz','FileName'=>'new.png','IconFileName'=>'icon-new.png','StandardFileName'=>'standard-new.png'];}
 $result=$fixture->SaveRecordFromQuery_Image();
 $expected=$mode==='distinct'?['/fixture/a/b/c/d/old.png','/fixture/a/b/c/d/icon.png','/fixture/a/b/c/d/standard.png']:[];
 $expected_events=$mode==='move-failure'?['write']:($mode==='distinct'?['write','write','unlink','unlink','unlink']:['write','write']);
 $ok=$GLOBALS['deleted']===$expected&&$GLOBALS['events']===$expected_events&&($mode==='move-failure'||$mode==='metadata-failure'?$result===FALSE:$result===$fixture->image);
 echo ($ok?'PASS ':'FAIL ').'upload cleanup '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
$GLOBALS['moves']=[];$GLOBALS['deleted']=[];$GLOBALS['events']=[];$GLOBALS['move_success']=TRUE;$GLOBALS['cleanup_failure']=TRUE;
$fixture=new UploadFixture();$result=$fixture->SaveRecordFromQuery_Image();
$ok=$result===FALSE&&$fixture->calls===2&&$GLOBALS['events']===['write','write','unlink']&&count($fixture->errors)===1;
echo ($ok?'PASS ':'FAIL ').'upload cleanup failure reports partial success'.PHP_EOL;if(!$ok){$failed++;}
$GLOBALS['moves']=[];$GLOBALS['deleted']=[];$GLOBALS['events']=[];$GLOBALS['move_success']=TRUE;$GLOBALS['cleanup_failure']=FALSE;
$fixture=new UploadFixture();$fixture->directory_failure=TRUE;$result=$fixture->SaveRecordFromQuery_Image();
$ok=$result===FALSE&&$fixture->calls===1&&$GLOBALS['moves']===[]&&$GLOBALS['deleted']===[]&&$fixture->resizes===[]&&count($fixture->errors)===1;
echo ($ok?'PASS ':'FAIL ').'upload directory failure stops move'.PHP_EOL;if(!$ok){$failed++;}
foreach(['icon','standard'] as $kind){
 $GLOBALS['moves']=[];$GLOBALS['deleted']=[];$GLOBALS['events']=[];$GLOBALS['move_success']=TRUE;$GLOBALS['cleanup_failure']=FALSE;
 $fixture=new UploadFixture();$fixture->resize_failure=$kind;$result=$fixture->SaveRecordFromQuery_Image();
 $ok=$result===FALSE&&$fixture->calls===1&&count($fixture->resizes)===($kind==='icon'?1:2)&&$GLOBALS['deleted']===[]&&count($fixture->errors)===1;
 echo ($ok?'PASS ':'FAIL ').'upload resize failure '.$kind.PHP_EOL;if(!$ok){$failed++;}
}
$GLOBALS['moves']=[];$GLOBALS['deleted']=[];$GLOBALS['events']=[];$GLOBALS['move_success']=TRUE;$GLOBALS['cleanup_failure']=FALSE;$GLOBALS['hash_failure']=TRUE;
$fixture=new UploadFixture();$result=$fixture->SaveRecordFromQuery_Image();
$ok=$result===FALSE&&$fixture->calls===1&&$GLOBALS['moves']===[]&&$GLOBALS['deleted']===[]&&$fixture->resizes===[]&&count($fixture->errors)===1;
echo ($ok?'PASS ':'FAIL ').'upload hash failure stops file work'.PHP_EOL;if(!$ok){$failed++;}
exit($failed?1:0);