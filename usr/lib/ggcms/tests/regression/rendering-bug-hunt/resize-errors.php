<?php
namespace ResizeDiagnostic;
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new \ErrorException($m,0,$n);});
class ImagickException extends \Exception {}
function fopen($path,$mode){$GLOBALS['opens'][]=[$path,$mode];if(($path==='source'&&$GLOBALS['mode']==='input')||($path==='target'&&$GLOBALS['mode']==='output')){return FALSE;}$handle=\fopen('php://memory','w+');$GLOBALS['handles'][]=$handle;return $handle;}
function fclose($handle){return \fclose($handle);}
class Imagick {
 public function readImageFile($handle){if($GLOBALS['mode']==='exception'){throw new ImagickException('fixture');}return $GLOBALS['mode']!=='read';}
 public function getImageHeight(){return $GLOBALS['mode']==='portrait'?400:100;}
 public function getImageWidth(){return in_array($GLOBALS['mode'],['small','portrait'])?100:400;}
 public function scaleImage($w,$h){return $GLOBALS['mode']!=='scale';}
 public function writeImageFile($handle){return $GLOBALS['mode']!=='write';}
}
$source=file_get_contents(dirname(__DIR__,3).'/src/traits/scripts/SimpleImages.php');
$source=preg_replace('/^<\?php/','namespace ResizeDiagnostic;',$source,1);
eval($source);
class Fixture {use SimpleImages;}
$failed=0;
foreach(['input','read','scale','output','write','exception','small','landscape','portrait'] as $mode){
 $GLOBALS['mode']=$mode;$GLOBALS['opens']=[];$GLOBALS['handles']=[];
 try{$result=(new Fixture())->resizeAndSaveFile(['filelocation'=>'source','resizedlocation'=>'target','maxheight'=>200,'maxwidth'=>200]);$success=in_array($mode,['small','landscape','portrait']);$expected=$mode==='small'?[100,100]:($mode==='portrait'?[200,50]:[50,200]);$ok=$success?($result['resizedheight']==$expected[0]&&$result['resizedwidth']==$expected[1]):$result===FALSE;}catch(\Throwable $e){$ok=FALSE;}
 $ok=$ok&&$GLOBALS['opens'][0]===['source','rb'];foreach($GLOBALS['handles'] as $handle){if(is_resource($handle)){$ok=FALSE;\fclose($handle);}}
 echo ($ok?'PASS ':'FAIL ').'resize '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);