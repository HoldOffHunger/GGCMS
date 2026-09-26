<?php
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new ErrorException($message, 0, $level); });
$root = dirname(__DIR__, 3) . '/src/classes/Format/';
require $root . 'Base/AbstractBaseFormat.php';
$failed = 0;
foreach(['ATOM','RSS','XML','TXT','TEX','OPDS','RDF','CSV','BRF','JSON','PDF','RTF','SGML','EPub','DAISY'] as $name) {
 require $root . $name . '.php';
 $instance = (new ReflectionClass($name))->newInstanceWithoutConstructor();
 $instance->human_readable = FALSE;
 $instance->desired_action = 'display';
 $instance->script = new class {
  public $calls = 0;
  public function display() { $this->calls++; return FALSE; }
  public function Param($name) { return '2.00'; }
  public function SetDocumentAttributes() { throw new Exception('Attributes after failed action'); }
  public function DisplayTemplates() { throw new Exception('Templates after failed action'); }
 };
 ob_start();
 try { $result = $instance->Display(); $output = ob_get_clean();
  $ok = $result === FALSE && $output === '' && $instance->script->calls === 1;
 } catch(Throwable $error) { ob_end_clean(); $ok = FALSE; echo $error->getMessage() . PHP_EOL; }
 echo ($ok ? 'PASS ' : 'FAIL ') . $name . ' failed action' . PHP_EOL;
 if(!$ok) { $failed++; }
}
exit($failed ? 1 : 0);
?>