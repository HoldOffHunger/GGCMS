<?php
namespace InvalidUploadDiagnostic;
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new \ErrorException($m,0,$n);});
class Imagick {}
function is_file($path){return TRUE;}
function unlink($path){$GLOBALS['deleted'][]=$path;return TRUE;}
function hash_file($algorithm,$path){throw new \RuntimeException('Invalid upload reached hashing');}
$source=file_get_contents(dirname(__DIR__,3).'/src/scripts/modify.php');
$start=strpos($source,'public function SaveRecordFromQuery_Image()');$end=strpos($source,'public function DeleteFiles(',$start);
eval('namespace InvalidUploadDiagnostic; class ImageMethod {'.substr($source,$start,$end-$start).'}');
class UploadFixture extends ImageMethod {
 public $errors=[],$image=[['id'=>9,'swapped'=>FALSE,'OriginalCreationDate'=>'2026-01-01']],$image_unset=[['FileDirectory'=>'abcd','FileName'=>'old.png','IconFileName'=>'icon.png','StandardFileName'=>'standard.png']],$image_files,$entry=['id'=>7],$calls=0;
 public function SaveRecordFromQuery_Base($args){$this->calls++;return $this->image;}
 public function isUserAdmin(){return TRUE;}
 public function GetImageFolderDirectory(){return '/fixture/';}
}
$failed=0;
foreach(['empty'=>['tmp_name'=>''],'missing'=>[],'reported-error'=>['tmp_name'=>'/fixture/upload','error'=>3]] as $name=>$fields){
 $GLOBALS['deleted']=[];$fixture=new UploadFixture();$fixture->image_files=[array_merge(['name'=>'replacement.png'],$fields)];
 ob_start();try{$result=$fixture->SaveRecordFromQuery_Image();$ok=$result===FALSE&&$GLOBALS['deleted']===[]&&count($fixture->errors)===1;}catch(\Throwable $e){$ok=FALSE;}$output=ob_get_clean();$ok=$ok&&$output==='';
 echo ($ok?'PASS ':'FAIL ').'invalid upload '.$name.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);