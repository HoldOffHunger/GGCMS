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
	
		/*
			Every database link the engine and its CLI open comes from here,
			so how they connect is decided once.

			Credentials are the mysqli.default_* settings.  If php.ini also
			names ggcms.database_ca, a CA certificate file, the link is TLS:
			the server's certificate must be signed by that CA and must name
			the host asked for, and a server that can't do TLS is refused
			rather than quietly talked to in plain text.  Without the setting
			the link is plain, which suits a database on the same machine or
			on a private network.

			Production needed it on 12 Oct 2026, when the private network to
			the managed database died after DigitalOcean's maintenance and
			the sites moved to its public address.  That carried the database
			password across the internet unencrypted until this existed.

			get_cfg_var() and not ini_get(): ini_get() only answers for
			settings an extension declares, and nothing declares ggcms.*.
			get_cfg_var() reads php.ini as written.

			A refused connection throws mysqli_sql_exception, exactly as
			`new mysqli` did under PHP 8's default report mode.  $use_port is
			FALSE only for DBAccess's old second attempt, which never passed
			one.
		*/

	function ggcms_mysqli($database = '', $use_port = TRUE) {
		$link = mysqli_init();
		$flags = 0;

		$certificate_authority = get_cfg_var('ggcms.database_ca');

		if($certificate_authority) {
			$link->ssl_set(NULL, NULL, $certificate_authority, NULL, NULL);
			$flags = MYSQLI_CLIENT_SSL;
		}

		$port = NULL;

		if($use_port && ini_get('mysqli.default_port')) {
			$port = (int)ini_get('mysqli.default_port');
		}

		$link->real_connect(
			ini_get('mysqli.default_host'),
			ini_get('mysqli.default_user'),
			ini_get('mysqli.default_pw'),
			$database,
			$port,
			NULL,
			$flags
		);

		return $link;
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