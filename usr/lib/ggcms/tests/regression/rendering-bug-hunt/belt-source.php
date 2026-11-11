<?php
	// modify.php's source and the belt it rides, as one text: since November 2026 the save pipeline the fixtures lift
	// methods from lives in traits/scripts/ChangeRecords/, one stage a file.  Each stage keeps its methods in modify.php's
	// old order, so a slice from one method to the next still begins and ends in the same file.
	function belt_source() {
		$src = dirname(__DIR__, 3) . '/src/';
		$files = array_merge([$src . 'scripts/modify.php', $src . 'traits/scripts/ChangeRecords.php'], glob($src . 'traits/scripts/ChangeRecords/*.php'));

		return implode("\n", array_map('file_get_contents', $files));
	}
?>
