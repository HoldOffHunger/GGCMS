<?php
error_reporting(E_ALL);
set_error_handler(function($level,$message){throw new ErrorException($message,0,$level);});
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__,3).'/src/classes/Format/RDF.php';
require dirname(__DIR__,3).'/src/classes/Charset/UTF8Characters.php';
class PolicyTemplateFixture {
 public $script_format_lower='rdf', $record_to_use;
 public function getPrivacyPolicyParagraphs(){return ['<p>A & B</p>'];}
 public function getTermsOfServiceParagraphs(){return ['<p>A & B</p>'];}
 public function GetHTMLFormatData_Title(){return 'Policy';}
 public function render($template){require $template;}
}
$failed=0;
foreach(['privacy'=>'privacypolicy','terms'=>'termsofservice'] as $template=>$field){
 $script=new PolicyTemplateFixture();$script->record_to_use=['id'=>7,'Code'=>'example','Title'=>'Example','Subtitle'=>'','ListTitle'=>'','OriginalCreationDate'=>'2026-09-14','LastModificationDate'=>'2026-09-14'];
 $rdf=new RDF();$rdf->script=$script;
 $rdf->handler=(object)['cleanser'=>(object)['utf8_characters'=>new UTF8Characters()]];
 $rdf->domain_object=new class {public function GetPrimaryDomain($args){return 'https://example.test';}};
 $_SERVER['REDIRECT_URL']='/example/view.rdf';
 ob_start();
 try{
  $script->render(dirname(__DIR__,3).'/src/templates/default/'.$template.'/display.php');$printed=ob_get_clean();
  $dom=new DOMDocument();$ok=$dom->loadXML($rdf->ConvertHTMLToFormat());
  $xpath=new DOMXPath($dom);$xpath->registerNamespace('entry','https://example.test/example/view.php');
  $expected="<h1>Policy</h1>\n<p>A & B</p>";
  $ok=$ok && $printed==='' && $script->record_to_use[$field]===$expected && $xpath->evaluate('string(//entry:'.$field.')')===$expected;
 }catch(Throwable $error){if(ob_get_level()){ob_end_clean();}$ok=FALSE;echo $error->getMessage().PHP_EOL;}
 echo ($ok?'PASS ':'FAIL ').'RDF policy template '.$template.PHP_EOL;if(!$ok){$failed++;}
}
exit($failed?1:0);
