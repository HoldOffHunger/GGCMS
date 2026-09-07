<?php

	class UserTracking {
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			return $this;
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
			
			chmod($filename, 0755);
			fwrite($filename_handle, $log_string);
			
			flock($filename_handle, LOCK_UN);
			fclose($filename_handle);
			
			return TRUE;
		}
	}

?>