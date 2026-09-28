<?php
error_reporting(E_ALL);
set_error_handler(function($n,$m){throw new ErrorException($m,0,$n);});
// The war room's single-comment and single-suggestion views, rendered with stand-in modules: the generic
// list records what it is asked to print, which it prints as HTML.  What a visitor wrote must reach it escaped.
function ggreq($path) {}
class module_text {} class module_form {} class module_divider { public function displaystart($a) {} public function displayend($a) {} }
class module_header { public function display($a) {} }
class module_genericlist { public static $shown = []; public function Display($args) { self::$shown[] = $args['list']; } }
class WarRoomView {
 public $comment, $suggestion, $client = 'example.com', $domain_object;
 public function __construct() { $this->domain_object = new class { public $primary_domain = 'example.com'; public function GetPrimaryDomain($a) { return 'https://example.com'; } }; }
 public function render($file) { include $file; }
}
$dir = dirname(__DIR__,3).'/src/templates/default/warroom/';
$payload = 'Nice page <script>alert("x")</script> "quoted"';
$row = ['id'=>5, 'Userid'=>7, 'Comment'=>$payload, 'Suggestion'=>$payload, 'Explanation'=>$payload, 'SuggestionType'=>'<b>Typo</b>'];
$failed = 0;
$check = function($label, $ok) use (&$failed) { echo ($ok ? 'PASS ' : 'FAIL ') . 'war room ' . $label . PHP_EOL; if(!$ok) { $failed++; } };
$shown = function() { return json_encode(module_genericlist::$shown, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); };
foreach(['viewComment'=>'comment', 'viewSuggestion'=>'suggestion'] as $template => $property) {
 module_genericlist::$shown = [];
 $view = new WarRoomView(); $view->$property = $row;
 ob_start(); $view->render($dir . $template . '.php'); ob_end_clean();
 $all = $shown();
 $check($template . ' shows the record', count(module_genericlist::$shown) === 2 && str_contains($all, 'Nice page'));
 $check($template . ' escapes what a visitor wrote', !str_contains($all, '<script>') && !str_contains($all, '<b>') && str_contains($all, '&lt;script&gt;') && str_contains($all, '&quot;quoted&quot;'));
}
exit($failed ? 1 : 0);
