<?php

	class UserTracking {
		public $handler;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
		}
		
		public function RecordUserTracking() {

				/*
					A render performed from the command line is not a visit.

					The page cache warmer runs this same pipeline, deliberately,
					so that what it writes is what a request would have written.
					Without this, warming revoltlib would file twelve thousand
					visitors who do not exist, and a day's statistics would
					describe the warm run rather than the audience.

					The stats directory not existing on a workstation is what
					surfaced this -- fopen returned false and flock was handed a
					boolean -- but creating the directory would have been the
					wrong fix for the right error.
				*/

			if(PHP_SAPI === 'cli') {
				return FALSE;
			}

			if($this->handler->script_format_lower !== 'html') {
				return FALSE;
			}
			
			if($this->handler->globals->EnableStats()) {
				$log_string = $this->RecordUserTracking_getLogString([]);
				$filename = $this->RecordUserTracking_getLogFilename();
				
				$this->RecordUserTracking_saveLog([
					'logstring'=>$log_string,
					'filename'=>$filename,
				]);
			}
			
			if($this->handler->globals->EnableStats_LogExcessiveMemoryUse()) {
				if($this->handler->globals->EnableStats_LogExcessiveMemoryUse_MaxSize() < memory_get_usage()) {
					$log_string = $this->RecordUserTracking_getLogString(['show_memory'=>TRUE]);
					$filename = $this->RecordUserTracking_getLogFilename_Memory();
					
					$this->RecordUserTracking_saveLog([
						'logstring'=>$log_string,
						'filename'=>$filename,
					]);
				}
			}
			
			return TRUE;
		}
		
			/*
				A person, rather than a request.

				humanbeacon.js sends one small JSON file per page view, and only
				after a real interaction, so this log counts the visitors the
				request log above cannot tell apart from scrapers -- including
				every reader of a cached page, which never reaches PHP at all.
				It goes beside that log as YYYY-Mon_humans.txt.

				Everything in it was written by the browser, so each field is
				flattened to one printable token and capped.  A log line stays
				one line, whatever is posted.
			*/

		public function RecordHumanBeacon() {
			if(!$this->HumanBeaconEnabled()) {
				return FALSE;
			}

			$upload = $_FILES['humanbeacon'];

			if(!is_array($upload) || $upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 4096) {
				return FALSE;
			}

			$beacon = json_decode(file_get_contents($upload['tmp_name']), TRUE);

			if(!is_array($beacon)) {
				return FALSE;
			}

				/*
					Only the events humanbeacon.js listens for.  Scroll was
					dropped from the script because crawlers fire it, but anyone
					holding the old script in cache keeps sending scroll beacons
					for hours -- 99 of the first 100 lines after the change were
					exactly that -- so the server refuses them as well.
				*/

			if(!in_array($beacon['event'], ['mousemove', 'wheel', 'keydown', 'touchstart', 'pointerdown'], TRUE)) {
				return FALSE;
			}

			$information_pieces = [
				date('o-M-d H:i:s', $this->handler->time->time),
				'[' . $this->handler->time->time . ']',
				$this->RecordHumanBeacon_getClientAddress(),
			];

			$fields = [
				'page',
				'referrer',
				'screen',
				'language',
				'timezone',
				'event',
				'milliseconds',
			];

			foreach($fields as $field) {
				$information_pieces[] = $this->RecordHumanBeacon_getField(['value'=>$beacon[$field]]);
			}

				/*
					The user agent goes last, taken from the request rather than
					the beacon, so a crawler that names itself can be left out
					when the log is read.  Lines written before it was added
					have eleven fields.
				*/

			$information_pieces[] = $this->RecordHumanBeacon_getField(['value'=>$_SERVER['HTTP_USER_AGENT']]);

			return $this->RecordUserTracking_saveLog([
				'logstring'=>implode(' ', $information_pieces) . PHP_EOL,
				'filename'=>$this->RecordHumanBeacon_getLogFilename(),
			]);
		}

			/*
				Off unless the site's globals say otherwise.  A config written
				before the beacon existed has no EnableStats_HumanBeacon at
				all, and should not start logging on the first deploy.
			*/

		public function HumanBeaconEnabled() {
			$globals = $this->handler->globals;

			if(!$globals || !method_exists($globals, 'EnableStats_HumanBeacon')) {
				return FALSE;
			}

			return $globals->EnableStats() && $globals->EnableStats_HumanBeacon();
		}

		public function RecordHumanBeacon_getField($args) {
			$value = $args['value'];

			if(!is_scalar($value)) {
				return '-';
			}

			$value = substr(preg_replace('/[^\x21-\x7E]+/', '', (string)$value), 0, 512);

			if(strlen($value) === 0) {
				return '-';
			}

			return $value;
		}

			/*
				Apache sees only nginx, so REMOTE_ADDR is nginx's address and
				not the visitor's.

				Behind Cloudflare the visitor is CF-Connecting-IP.  Not every
				site is behind Cloudflare -- revoltlib and revoltsource answer
				from nginx directly, and logged 127.0.0.1 on the beacon's first
				day -- and there the visitor is the last address in
				X-Forwarded-For, the one nginx appended for the connection it
				accepted.  Earlier entries are whatever the client claimed, so
				they are never read.  Each is taken only when it is a
				well-formed address, and a statistic is all it decides.
			*/

		public function RecordHumanBeacon_getClientAddress() {
			$cloudflare_address = $_SERVER['HTTP_CF_CONNECTING_IP'];

			if($cloudflare_address && filter_var($cloudflare_address, FILTER_VALIDATE_IP)) {
				return $cloudflare_address;
			}

			$forwarded = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
			$nginx_address = trim(end($forwarded));

			if($nginx_address && filter_var($nginx_address, FILTER_VALIDATE_IP)) {
				return $nginx_address;
			}

			return $_SERVER['REMOTE_ADDR'];
		}

		public function RecordHumanBeacon_getLogFilename() {
			return $this->handler->domain->primary_domain_lowercased . '/stats/' . date('o-M', $this->handler->time->time) . '_humans.txt';
		}

		public function RecordUserTracking_getLogString($args) {
			$information_pieces = [
				date('o-M-d H:i:s', $this->handler->time->time),
				'[' . $this->handler->time->time . ']',
				$_SERVER['REMOTE_ADDR'],
				$_SERVER['REQUEST_URI'],
				'(' . $this->handler->language->GetLanguageCode() . ':' . $this->handler->language->GetLanguage() . ')',
			];
			
			if($args['show_memory'] || $this->handler->globals->EnableStats_LogMemoryUse()) {
				$information_pieces[] = memory_get_usage();
			}
			
			return implode(' ', $information_pieces) . PHP_EOL;
		}
		
		public function RecordUserTracking_getLogFilename() {
			return $this->handler->domain->primary_domain_lowercased . '/stats/' . date('o-M', $this->handler->time->time) . '.txt';
		}
		
		public function RecordUserTracking_getLogFilename_Memory() {
			return $this->handler->domain->primary_domain_lowercased . '/stats/' . date('o-M', $this->handler->time->time) . '_memory.txt';
		}
		
		public function RecordUserTracking_saveLog ($args) {
			$log_string = $args['logstring'];
			$filename = $args['filename'];
			
			$filename_handle = gglog($filename, 'a+');

				/*
					Statistics are incidental to serving the page, and this sits
					in front of every HTML render on the host.

					gglog is fopen, which returns false rather than throwing,
					and that false went straight into flock -- where PHP 8 makes
					it a TypeError and the request dies. A visitor got a 500
					because a log line could not be written.

					It fired once in about a hundred and sixty thousand requests
					on 7 September 2026, with no pattern: every domain's stats
					directory exists and every one is www-data-owned and
					writable. So it was a momentary failure -- a descriptor
					limit, or a write racing something else -- of the kind that
					will happen again and should cost a statistic rather than a
					page.

					The comment in RecordUserTracking above describes the same
					fault found from the command line, fixed there by declining
					to record CLI renders at all. This is the other half: the
					web path, where the answer is not to guess why the file
					would not open but to give up quietly.
				*/

			if(!is_resource($filename_handle)) {
				return FALSE;
			}

				/*
					Bounded. The loop was `while (!flock(...))` with nothing to
					stop it, so a lock that never arrives spins a request
					forever, holding one of a small number of workers. Twenty
					attempts at up to 100ms is two seconds at worst, after which
					the statistic is dropped -- which is the right thing to
					lose.
				*/

			$lock_attempts = 0;

			while (!flock($filename_handle, LOCK_EX)) {
				$lock_attempts++;

				if($lock_attempts >= 20) {
					fclose($filename_handle);

					return FALSE;
				}

				usleep(round(rand(0, 100)*1000)); //0-100 milliseconds
			}
			
				/*
					$filename is what gglog was given -- a path relative to
					GGCMS_LOG_DIR, like revoltlib.com/stats/2026-Sep.txt.
					chmod resolves it against the process working directory
					instead, where it has never existed, so this failed on
					every request the site has ever served -- silently, since
					index.php sets error_reporting(0).

					0644 rather than the 0755 it asked for. The mode it never
					applied would have made every statistics file executable,
					and 0644 is what fopen has actually been creating them as
					all along, so this now asserts the status quo rather than
					changing seventeen sites' logs on the first request after
					a deploy.
				*/

			chmod(GGCMS_LOG_DIR . $filename, 0644);
			fwrite($filename_handle, $log_string);
			
			flock($filename_handle, LOCK_UN);
			fclose($filename_handle);
			
			return TRUE;
		}
	}

?>