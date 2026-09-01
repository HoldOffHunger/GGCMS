<?php

	class ErrorLogging {
		public $handler;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			$this->InitiateLogging();
			
			return $this;
		}
		
		public function InitiateLogging() {
			set_error_handler([$this, 'errorHandler']);
			
			register_shutdown_function([$this, 'shutdownHandler']);
			
			return TRUE;
		}
		
		public function errorHandler($error_level, $error_message, $error_file, $error_line, $error_context=[]) {
			$error = "lvl: " . $error_level . " | msg:" . $error_message . " | file:" . $error_file . " | ln:" . $error_line . "|";
			$stack_trace = (new Exception)->getTraceAsString();
			
			switch ($error_level) {
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_PARSE:
					$this->mylog($error, "fatal", $stack_trace);
					break;
					
				case E_USER_ERROR:
				case E_RECOVERABLE_ERROR:
					$this->mylog($error, "error", $stack_trace);
					break;
					
				case E_WARNING:
				case E_CORE_WARNING:
				case E_COMPILE_WARNING:
				case E_USER_WARNING:
					$this->mylog($error, "warn", $stack_trace);
					break;
					
				case E_NOTICE:
				case E_USER_NOTICE:
					$this->mylog($error, "info", $stack_trace);
					break;
					
				case E_STRICT:
					$this->mylog($error, "debug", $stack_trace);
					break;
					
				default:
					$this->mylog($error, "warn", $stack_trace);
					break;
			}
			
			return TRUE;
		}
		
		public function shutdownHandler() { //will be called when php script ends.
			$lasterror = error_get_last();
			$stack_trace = (new Exception)->getTraceAsString();
			
			switch ($lasterror['type']) {
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
				case E_RECOVERABLE_ERROR:
				case E_CORE_WARNING:
				case E_COMPILE_WARNING:
				case E_PARSE:
					$error = "[SHUTDOWN] lvl:" . $lasterror['type'] . " | msg:" . $lasterror['message'] . " | file:" . $lasterror['file'] . " | ln:" . $lasterror['line'] . "|";
					$this->mylog($error, "fatal", $stack_trace);
					break;
			}
			
			return TRUE;
		}
		
		public function mylog($error, $errlvl, $stack_trace) {
			if($errlvl === 'fatal') {
				$this->indicateError(['error'=>$error]);
				
				$this->displayErrorToAdmin(['error'=>$error, 'stack_trace'=>$stack_trace,]);
				
				$this->backupError(['error'=>$error]);
			}
			
			return TRUE;
		}
		
		/*
			One defect is one ticket.  Two fatals are the same defect when the same
			script produced the same message, and the message already carries the
			file and the line, so nothing is normalised away here -- an exact match
			or a new ticket.
		*/

		public function ErrorSignature($args) {
			return hash('sha256', $args['script'] . "\n" . $args['error']);
		}

			/*
				This runs from the shutdown handler, so the handler may have died
				before it named a script.  An unattributed ticket is still a ticket.
			*/

		public function ErrorScript() {
			if(!$this->handler) {
				return '';
			}

			if(!property_exists($this->handler, 'script_file')) {
				return '';
			}

			return (string)$this->handler->script_file;
		}
		
		public function backupError($args) {
			$error = $args['error'];
			
			if(!$this->handler->globals->EnableErrorLogging()) {
				return FALSE;
			}
			
			if(!$this->handler->db_access) {
				$this->indicateBackupFailure(['error'=>$error]);
				return FALSE;
			}
			
			$error_script = $this->ErrorScript();

			$internal_server_error_insert_args = [
				'type'=>'InternalServerError',
				'instancetype'=>'InternalServerErrorInstance',
				'instancefield'=>'Errorid',
				'url'=>$_SERVER['REQUEST_URI'],
				'definition'=>[
					'Signature'=>$this->ErrorSignature([
						'script'=>$error_script,
						'error'=>$error,
					]),
					'Script'=>$error_script,
					'IncidentCount'=>1,
					'Resolved'=>0,
					'ErrorMessage'=>$error,
					'URL'=>$_SERVER['REQUEST_URI'],
					'ServerVariable'=>print_r($_SERVER, TRUE),
					'PostVariable'=>print_r($_POST, TRUE),
					'GetVariable'=>print_r($_GET, TRUE),
					'EnvironmentVariables'=>print_r($this->handler, TRUE) . (new Exception)->getTraceAsString(),
				],
			];
			
				/*
					This runs from the shutdown handler, after a request has
					already failed, and it is the last chance to record why.
					A throw here replaces the original error with its own and
					takes the request down with it, so the record we wanted is
					lost and the record we get is about the recorder.

					Whatever goes wrong writing the row, the error being
					reported still reaches the admin display above, and the
					reason the row could not be written is printed rather than
					swallowed.
				*/

			try {
				return $this->internal_server_error = $this->handler->db_access->CreateCountedRecord($internal_server_error_insert_args);
			} catch (Throwable $throwable) {
				$this->indicateBackupFailure([
					'error'=>$error . ' | unlogged: ' . $throwable->getMessage(),
				]);

				return FALSE;
			}
		}
		
		public function indicateBackupFailure($args) {
			$error = $args['error'];
			
			print('<p><b>DB Error</b> : Unable to save the following error &mdash;<br><br><blockquote>' . $error . '</blockquote></p>');
			return TRUE;
		}
		
		public function indicateError($args) {
			$error = $args['error'];
			
			http_response_code(500);
			
			load_config('error/errormessage_globals.php', $this->handler->domain->primary_domain_lowercased);
			
			$error_message_globals = new AbstractGlobals_type_error_errormessage();
			
			print($error_message_globals->GetServer500ErrorMessage());
			
			return TRUE;
		}
		
		public function displayErrorToAdmin($args) {
			$error = $args['error'];
			$stack_trace = $args['stack_trace'];
			
			$show_errors = FALSE;
			
			if($_SERVER['HTTP_HOST'] === 'localhost' && $_SERVER['SERVER_NAME'] === 'localhost') {
				$show_errors = TRUE;
			} elseif($_SERVER['HTTPS'] === 'on') {
				$authentication_token = $_COOKIE['AuthenticationToken'];
				
				if($authentication_token) {
					$user_session_record_args = [
						'type'=>'UserSession',
						'definition'=>[
							'CookieToken'=>$authentication_token,
							'RAW'=>[
								'LastAccess'=>[
									'<',
									'DATE_ADD(UserSession.LastAccess, INTERVAL 4 HOUR)',
								],
							],
						],
						'limit'=>1,
						'joins'=>[
							'LEFT JOIN'=>[
								'User'=>'User.id = UserSession.Userid',
								'UserAdmin'=>'UserAdmin.Userid = UserSession.Userid',
							],
						],
					];
					$user_session = $this->handler->db_access->GetRecords($user_session_record_args)[0];
				}
				
				if($user_session['UserAdmin.id']) {
					$show_errors = TRUE;
				}
			}
			
			if($show_errors) {
				print("ERROR : <BR><BR>");
				print('<PRE>');
				print($error);
				
				print(PHP_EOL);
				print_r($stack_trace);
			#	print_r(debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
				
				print('</PRE>');
				
				return TRUE;
			}
			
			return FALSE;
		}
	}

?>