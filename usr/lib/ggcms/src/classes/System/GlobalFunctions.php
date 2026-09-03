<?php

		/*
		
				// Standard Defaults
		
			define('GGCMS_DIR', '/usr/lib/ggcms/src/');
			define('GGCMS_DEP_DIR', '/usr/lib/ggcms/dep/');
			define('GGCMS_LOG_DIR', '/var/log/ggcms/');
			define('GGCMS_DATA_DIR', '/srv/ggcms/');
			define('GGCMS_CONFIG_DIR', '/etc/ggcms/');
			define('GGCMS_DOC_ROOT', '/var/www/html/');
		
		*/
	
	function ggreq($filename) {
		return require(GGCMS_DIR . $filename);
	}

	/*
		A file that declares a class may only be required once, and since
		3 September 2026 a request that repairs its own URL runs the handler
		chain a second time.  The second pass re-entered these and died with
		"Cannot declare class AbstractBaseFormat, because the name is already
		in use".

		Named for what it loads, like the rest of the family: ggreq for the
		engine, depreq for dependencies, datareq for data, confreq for
		configuration.  A class file is a kind of thing, and the kind of thing
		it is decides the rule -- declared once, therefore required once.

		For such a file require and require_once differ only in that the
		second does not fatal, since these files declare and print nothing.
		ggreq itself is left alone: templates and modules are required
		repeatedly on purpose.
	*/

	function classreq($filename) {
		return require_once(GGCMS_DIR . $filename);
	}

	function depreq($filename) {
		return require(GGCMS_DEP_DIR . $filename);
	}

	function gglog($filename, $mode) {
		return fopen(GGCMS_LOG_DIR . $filename, $mode);
	}

	function datareq($filename) {
		return require(GGCMS_DATA_DIR . $filename);
	}

	function confreq($filename) {
		return require(GGCMS_CONFIG_DIR . $filename);
	}
	
	function conf_isfile($filename) {
		return is_file(GGCMS_CONFIG_DIR . $filename);
	}
	
	function load_config($filename, $domain) {
		$config_file_location = $domain . '/' . $filename;
		
		if(!conf_isfile($config_file_location)) {
			$config_file_location = 'clonefrom/' . $filename;
		}
		
		confreq($config_file_location);
		
		return TRUE;
	}
	
	function webroot_isfile($filename) {
		return is_file(GGCMS_DOC_ROOT . $filename);
	}
	
	function data_isfile($filename, $handler) {
		$primary_domain = $handler->domain->primary_domain_lowercased;
		$file_location = GGCMS_DATA_DIR . $primary_domain . '/www/' . $filename;
	#	print("<!-- FILE ----- BT:! " . $file_location . "|||| -->");
		return is_file($file_location);
	}
	
	function data_reqfile($filename, $handler) {
		$primary_domain = $handler->domain->primary_domain_lowercased;
		$file_location = GGCMS_DATA_DIR . $primary_domain . '/www/' . $filename;
	#	print("<!-- FILE ----- BT:! " . $file_location . "|||| -->");
		return print(file_get_contents($file_location));
	}
	
	function FormatDate($args) {
		$date = $args['date'];
		
		if(!$date) {
			return '?';
		}
		
		$event_date_pieces = explode('-', $date);
		
		$date_epoch_time = strtotime($date);
		
		$month_format = 'F';
		if($args['short-dates']) {
			$month_format = 'M.';
		}
		
		$year = $event_date_pieces[0];
		/*
		if(intval($event_date_pieces[0]) > 3000) {
			if($year >= 3000) {
				$diff = $year - 3000;
				$real_year = 1000 - $diff;
			} else {
				$real_year = $year;
			}
		*/
		$bce_check = mb_substr($year, 0, 3, 'utf-8');
		if($bce_check === 'bce') {
			$real_year = str_replace('bce', '', $year);
			$formatted = $real_year . ' BCE';
		} elseif($event_date_pieces[1] !== '00' && $event_date_pieces[2] !== '00') {
			$formatted = date("$month_format j, Y", $date_epoch_time);
		} elseif($event_date_pieces[1] !== '00') {
			$new_date_epoch_time = $event_date_pieces[0] . '-' . $event_date_pieces[1] . '-01';
			$formatted = date("$month_format, Y", strtotime($new_date_epoch_time));
		} else {
			$new_date_epoch_time = $event_date_pieces[0] . '-01-01';
			$formatted = date("Y", strtotime($new_date_epoch_time));
		}
		
		return $formatted;
	}

?>