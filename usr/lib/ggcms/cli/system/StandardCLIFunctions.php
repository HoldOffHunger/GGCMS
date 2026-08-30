<?php

	function clireq($filename) {
		return require(GGCMS_CLI_DIR . $filename);
	}
	
	function cli_isfile($filename) {
		return is_file(GGCMS_CLI_DIR . $filename);
	}
	
?>