<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
function ggreq($path){require_once dirname(__DIR__,3).'/src/'.$path;}
require __DIR__.'/abstract-base-format-stub.php';
ggreq('classes/Format/RDF.php');ggreq('classes/Charset/UTF8Characters.php');
class RDFUserFixture {public function Param($name){return FALSE;}public function render(){include dirname(__DIR__,3).'/src/templates/default/users/exportuser.php';}}
function decodeBag($xpath,$bag){
 $result=[];
 foreach($xpath->query('rdf:li',$bag) as $item){
  $key=$xpath->evaluate('string(entry:key)',$item);$type=$xpath->evaluate('string(entry:type)',$item);
  $value=$xpath->evaluate('string(entry:value)',$item);
  if($type==='array'){$value=decodeBag($xpath,$xpath->query('entry:value/rdf:Bag',$item)->item(0));}
  elseif($type==='NULL'){$value=NULL;}
  elseif($type==='boolean'){$value=$value==='true';}
  elseif($type==='integer'){$value=(int)$value;}
  elseif($type==='double'){$value=(float)$value;}
  elseif($type!=='string'){throw new RuntimeException('Unexpected serialized type');}
  $result[$key]=$value;
 }
 return $result;
}
$failed=0;
foreach(['empty','comments','likes','both'] as $mode){
 $script=new RDFUserFixture();$script->script_format_lower='rdf';$script->handler=(object)['cleanser'=>(object)['utf8_characters'=>new UTF8Characters()]];
 $script->user=['Username'=>'Reader','id'=>7,'OriginalCreationDate'=>'2026-09-14 12:00:00'];$script->comments=[];$script->likedislikes=[];
 $row=['id'=>2,'OriginalCreationDate'=>'2026-09-14 12:00:00','entry'=>['id'=>3,'Title'=>'Book','parents'=>[['Title'=>'Book']]],'Extra'=>['zero'=>'0','false'=>FALSE,'null'=>NULL]];
 if(in_array($mode,['comments','both'])){$script->comments=[$row+['Comment'=>'A & <literal>']];}
 if(in_array($mode,['likes','both'])){$script->likedislikes=[$row+['LikeOrDislike'=>1]];}
 $script->comments_count=count($script->comments);$script->likes_count=count($script->likedislikes);
 $script->record_to_use=['id'=>7,'Code'=>'user','Title'=>'User','Subtitle'=>'','ListTitle'=>'','OriginalCreationDate'=>'2026-09-14 12:00:00','LastModificationDate'=>'2026-09-14 12:00:00'];
 $rdf=new RDF();$rdf->script=$script;$rdf->handler=$script->handler;$rdf->domain_object=new class{public function GetPrimaryDomain($args){return 'https://example.test';}};
 $_SERVER['REDIRECT_URL']='/user/view.rdf';ob_start();
 try{
  $script->render();$printed=ob_get_clean();$dom=new DOMDocument();$ok=$dom->loadXML($rdf->ConvertHTMLToFormat()) && $printed==='';
  $xpath=new DOMXPath($dom);$xpath->registerNamespace('entry','https://example.test/user/view.php');$xpath->registerNamespace('rdf','http://www.w3.org/1999/02/22-rdf-syntax-ns#');
  foreach(['comments','likesdislikes'] as $field){$bag=$xpath->query('/rdf:RDF/rdf:Description/entry:'.$field.'/rdf:Bag')->item(0);$ok=$ok && $bag!==NULL && decodeBag($xpath,$bag)===$script->record_to_use[$field];}
  $ok=$ok && $xpath->evaluate('string(//entry:userdata)')===$script->record_to_use['userdata'];
 }catch(Throwable $error){if(ob_get_level()){ob_end_clean();}$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'RDF actual user export '.$mode.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);
