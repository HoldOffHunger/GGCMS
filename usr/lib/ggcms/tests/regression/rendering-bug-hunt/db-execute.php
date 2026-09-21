<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
require dirname(__DIR__, 3) . '/src/classes/Database/DBAccess.php';
class DBFixture extends DBAccess {
 public function __construct() {}
 public function FillArraysFromDB_FormatRow($args) { return $args['row']; }
}
$failures=0;
foreach(['failure','write','emptyread','read'] as $mode) {
 $statement=new class {
  public $mode; public $gets=0;
  public function execute(){return $this->mode!=='failure';}
  public function get_result(){
   $this->gets++;
   if($this->mode==='write'||$this->mode==='failure'){return FALSE;}
   $result=new class {public $rows; public function fetch_assoc(){return array_shift($this->rows);} };
   $result->rows=$this->mode==='read'?[['id'=>7]]:[]; return $result;
  }
 };
 $statement->mode=$mode;
 $db=new DBFixture();
 $db->db_link=new class {public $statement;public $error='fixture failure';public $errno=1234;public function prepare($query){return $this->statement;}};
 $db->db_link->statement=$statement;
 $result=$db->FillArraysFromDB(['query'=>'fixture','sqlbindstring'=>'','recordvalues'=>[],'record_type'=>NULL]);
 $ok=$mode==='failure'? (($result['specifictype']??NULL)==='Execute' && isset($result['line']) && $statement->gets===0):($result===($mode==='read'?[['id'=>7]]:[]) && $statement->gets===1);
 echo ($ok?'PASS ':'FAIL ').$mode.PHP_EOL;
 if(!$ok){$failures++;}
}
exit($failures?1:0);
?>