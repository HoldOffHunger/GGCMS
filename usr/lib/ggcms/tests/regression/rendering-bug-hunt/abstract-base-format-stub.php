<?php
	/*
		Feed fixtures stand in for AbstractBaseFormat, whose real constructor
		needs a whole handler.  XMLText() and XMLEscape() are lifted from the
		real source so the fixtures escape exactly as production does.
	*/

	$abstract_base_format_source = file_get_contents(dirname(__DIR__, 3) . '/src/classes/Format/Base/AbstractBaseFormat.php');

	$abstract_base_format_methods = '';

	foreach(['XMLText', 'XMLEscape'] as $method_name) {
		if(!preg_match('/public function ' . $method_name . '\(.*?\r?\n\t\t}\r?\n/s', $abstract_base_format_source, $method_source)) {
			fwrite(STDERR, $method_name . "() not found in AbstractBaseFormat.php\n");
			exit(1);
		}

		$abstract_base_format_methods .= $method_source[0];
	}

	eval('class AbstractBaseFormat {' . $abstract_base_format_methods . '}');
?>
