<?php
error_reporting(E_ALL);
set_error_handler(function($level, $message) { throw new ErrorException($message, 0, $level); });
require __DIR__.'/abstract-base-format-stub.php';
function ggreq($path) { require_once dirname(__DIR__, 3) . '/src/' . $path; }
require dirname(__DIR__, 3) . '/src/classes/Format/OPDS.php';
class OPDSSetupFixture extends OPDS { public function __construct() {} }
$feed = new OPDSSetupFixture();
$feed->handler = (object)['fixture'=>'handler'];
try {
 $ok = $feed->SetMimeTypeAndFormats() === TRUE;
 $ok = $ok && $feed->mimetype instanceof MIMEType && $feed->format_object instanceof Formats;
 $ok = $ok && $feed->mimetype->handler === $feed->handler && $feed->format_object->handler === $feed->handler;
 $ok = $ok && $feed->mimetype->GetMIMETypeCodes()['png'] === 'image/png';
 $ok = $ok && $feed->format_object->GetListOfSupportedFormatExtensions()['OPDS'] === 'opds';
} catch(Throwable $error) {
 $ok = FALSE;
 echo $error->getMessage() . PHP_EOL;
}
echo ($ok ? 'PASS' : 'FAIL') . ' OPDS setup with real MIME and format helpers' . PHP_EOL;
exit($ok ? 0 : 1);