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

		/*
			And every property the real class declares, so a format built on
			the stand-in may set what it sets on the real one.  PHP 8.2 and
			later deprecate any other, and these fixtures fail on a
			deprecation.
		*/

	preg_match_all('/^\t\tpublic \$\w+;/m', $abstract_base_format_source, $abstract_base_format_properties);

	eval('class AbstractBaseFormat {' . implode('', $abstract_base_format_properties[0]) . $abstract_base_format_methods . '}');
?>
