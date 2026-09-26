<?php
error_reporting(E_ALL);
$warnings = [];
set_error_handler(function($level, $message) use (&$warnings) { $warnings[] = $message; return TRUE; });
require __DIR__.'/abstract-base-format-stub.php';
require dirname(__DIR__, 3) . '/src/classes/Format/RSS.php';
class FeedFixture extends RSS {
 public function RunTemplates() {}
 public function ConvertHTMLToFormat_renderImage($args) { return ''; }
 public function ConvertHTMLToFormat_renderContent() { return ''; }
 public function ConvertHTMLToFormat_lastBuildDate() { return '2026-09-10'; }
}
$failed = 0;
foreach([[], ['books'], ['books', 'essays']] as $codes) {
 foreach([FALSE, TRUE] as $readable) {
  $warnings = [];
  $feed = new FeedFixture();
  $feed->section_separator = ''; $feed->line_separator = ''; $feed->indent_levels = [1=>'', 2=>''];
  $feed->version = '2.0'; $feed->version_float = 2.0; $feed->human_readable = $readable;
  $feed->script = new class {
   public $entry = ['Title'=>'Fixture', 'Subtitle'=>'', 'OriginalCreationDate'=>'2026-09-10'];
   public $record_list;
   public function getDescription() { return 'Fixture description'; }
  };
  $feed->script->record_list = array_map(fn($code)=>['Code'=>$code], $codes);
  $feed->handler = (object)['cleanser'=>(object)['utf8_characters'=>new class {public function SystemCharSet(){return 'UTF-8';}}],
   'domain'=>new class { public function GetPrimaryDomain($args) { return 'https://example.test'; } },
   'version'=>new class { public function GetSoftwareNameAcronymAndVersion() { return 'GGCMS fixture'; } },
   'globals'=>new class {
    public function AdminEmailAddress() { return 'test@example.test'; }
    public function AdminName() { return 'Fixture'; }
   },
  ];
  $xml = $feed->ConvertHTMLToFormat();
  preg_match('/rel="self"[^>]*href="([^"]+)"/', $xml, $match);
  $base = 'https://example.test/' . ($codes ? implode('/', $codes) . '/' : '');
  $expected = $base . 'news.rss';
  $page = $base . ($codes ? 'view.php?action=index' : '');
  $ok = ($match[1] ?? '') === $expected && strpos($xml, '<link>' . $page . '</link>') !== FALSE && !$warnings;
  echo ($ok ? 'PASS' : 'FAIL') . ' depth=' . count($codes) . ' readable=' . (int)$readable . ' self=' . ($match[1] ?? 'MISSING') . ' warnings=' . count($warnings) . PHP_EOL;
  if(!$ok) { $failed++; }
 }
}
exit($failed ? 1 : 0);
?>