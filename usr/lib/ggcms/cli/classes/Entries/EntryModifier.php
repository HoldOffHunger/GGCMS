<?php

	clireq('traits/CLIAccess.php');
	clireq('traits/DomainValidation.php');
	clireq('traits/ErrorCLI.php');

	/*
		Drives modify.php from a shell.

		It does not reimplement anything.  modify.php is 3,565 lines of rules
		that exist nowhere else -- how an entry and its assignment are created
		together, how child records attach, and how a date before the year 1000
		is stored at all, given that MySQL DATETIME cannot hold one.  That last
		is encoded at modify.php:2515 and decoded in traits/GGCMSDateFormat.php,
		and a second implementation would get it wrong eventually and silently.

		So this makes the shell look like a request and runs the real thing:
		$_SERVER and $_POST are populated, a Handler is constructed exactly as
		index.php constructs one, and HandleRequest() runs the ordinary path.
		Every quirk comes along, including the ones nobody has written down.

		What that costs is honesty about authentication -- see grantAccess().
	*/

	class EntryModifier {
		use CLIAccess;
		use DomainValidation;
		use ErrorCLI;

		public function bannerMessageText() {
			return 'Modify Entry';
		}

		public function confirmDomainText() {
			return 'Modifying entries on: ';
		}

			// Entry point
			// -----------------------------------------------

		public function modifyEntry() {
			$this->bannerMessage();
			$this->setHandle();

			if(!$this->checkExtensions()) {
				return FALSE;
			}

			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}

			$this->setOptions();

			if(!$this->fields) {
				return $this->cancelAction(['message'=>'Nothing to write.  Pass at least one --field=name=value.']);
			}

			$this->reportIntent();

			if(!$this->apply) {
				print('Dry run.  Nothing was written.  Add --apply to run it.' . PHP_EOL . PHP_EOL);
				return TRUE;
			}

			return $this->runModify();
		}

			// Preflight
			// -----------------------------------------------

			/*
				The CLI and Apache SAPIs read different php.ini files and load
				different extensions -- Triage.md records the same split biting a
				database tool.  Running the engine from a shell needs what the
				engine needs, and the first thing Handler does with a POST is
				hand it to UTF8Characters, which is mbstring.

				Checked here so the failure is one line naming the package,
				rather than an uncaught Error from four frames down inside a
				constructor.
			*/

		public function checkExtensions() {
			$required = [
				'mbstring'=>'php8.1-mbstring',
				'mysqli'=>'php8.1-mysql',
			];

			$missing = [];

			foreach($required as $extension => $package) {
				if(!extension_loaded($extension)) {
					$missing[$extension] = $package;
				}
			}

			if(!count($missing)) {
				return TRUE;
			}

			print('This PHP is missing extensions the engine needs:' . PHP_EOL . PHP_EOL);

			foreach($missing as $extension => $package) {
				printf("  %-12s apt-get install %s" . PHP_EOL, $extension, $package);
			}

			print(PHP_EOL);
			print('PHP here is ' . PHP_VERSION . ' (' . PHP_SAPI . '), reading ' . (php_ini_loaded_file() ? php_ini_loaded_file() : 'no php.ini') . '.' . PHP_EOL);
			print('Apache may have these while the CLI does not; see Docs/Triage.md.' . PHP_EOL . PHP_EOL);

			return FALSE;
		}

			// Options
			// -----------------------------------------------

		public function setOptions() {
			$this->action = $this->option(['name'=>'action', 'default'=>'Save']);
			$this->path = $this->option(['name'=>'path', 'default'=>'/']);
			$this->apply = $this->flag(['name'=>'apply']);
			$this->fields = $this->fieldArguments();

			return TRUE;
		}

		public function option($args) {
			$prefix = '--' . $args['name'] . '=';

			foreach($this->argv as $argument) {
				if(strpos($argument, $prefix) === 0) {
					return substr($argument, strlen($prefix));
				}
			}

			return $args['default'];
		}

		public function flag($args) {
			return in_array('--' . $args['name'], $this->argv);
		}

			/*
				--field=Title=Pavo, repeated.  The value may contain = signs;
				only the first one after the field name separates.
			*/

		public function fieldArguments() {
			$fields = [];

			foreach($this->argv as $argument) {
				if(strpos($argument, '--field=') !== 0) {
					continue;
				}

				$pair = substr($argument, strlen('--field='));
				$pieces = explode('=', $pair, 2);

				if(count($pieces) !== 2) {
					continue;
				}

				$fields[$pieces[0]] = $pieces[1];
			}

			return $fields;
		}

		public function reportIntent() {
			print('Action   : ' . $this->action . PHP_EOL);
			print('URL      : ' . $this->scriptURL() . PHP_EOL);
			print('Mode     : ' . ($this->apply ? 'APPLY -- writes to the database' : 'dry run') . PHP_EOL);
			print(PHP_EOL);

			foreach($this->fields as $name => $value) {
				printf("  %-24s %s" . PHP_EOL, $name, $value);
			}

			print(PHP_EOL);

			return TRUE;
		}

			// Making a shell look like a request
			// -----------------------------------------------

			/*
				Handler reads the request out of $_SERVER, and there is no
				request here.  These are the keys the construction path actually
				touches; anything absent is an undefined-index notice at best and
				a wrong branch at worst.

				HTTPS matters more than it looks.  Authenticate() refuses every
				secure script when DetectHTTPS() does not answer ssl, so without
				it modify.php is unreachable no matter what else is set.
			*/

		public function fakeRequest() {
			$url = $this->scriptURL();

			$_SERVER['HTTP_HOST'] = 'www.' . $this->domain;
			$_SERVER['SERVER_NAME'] = 'www.' . $this->domain;
			$_SERVER['HTTPS'] = 'on';
			$_SERVER['REQUEST_METHOD'] = 'POST';
			$_SERVER['REQUEST_URI'] = $url;
			$_SERVER['SCRIPT_URL'] = $url;
			$_SERVER['REDIRECT_URL'] = $url;
			$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
			$_SERVER['HTTP_REFERER'] = '';

			$_GET = ['action'=>$this->action];
			$_POST = $this->fields;
			$_POST['action'] = $this->action;

			$_REQUEST = array_merge($_GET, $_POST);

			return TRUE;
		}

			/*
				The URL has to name the script, because that is how this system
				routes: the last segment is the verb and everything before it is
				the entry graph.  /a/b/c/modify.php edits the entry at /a/b/c/,
				and /a/b/c/ on its own edits nothing -- it is a view.

				Three variables carry it, and they are not interchangeable.
				Construct_ObjectsAndScripts() reads REDIRECT_URL and nothing
				else; HandleRequest_Content() reads SCRIPT_URL; RequestPath()
				and the cache read REQUEST_URI.  Apache sets all three.  Setting
				only two produced an empty desired_script, a request that
				reached no script at all, and a fall through to the 404 path.
			*/

		public function scriptURL() {
			$path = '/' . trim($this->path, '/');

			if($path !== '/') {
				$path .= '/';
			}

			return $path . 'modify.php';
		}

			/*
				modify.php is IsSecure and RequiresLogin, so Authenticate() wants
				a user_session -- a login cookie this process does not have and
				should not be given one by storing a password on the server.

				Access is therefore granted directly, and the reasoning is worth
				stating rather than hiding: whoever runs this already has a shell
				on the host, and a shell is strictly more power than any web
				login. The login gate exists to stop a remote visitor. It is not
				a second lock on someone who is already inside.

				It is one line, and it is the only line in this tool that gives
				anything away. Remove it and the tool stops working rather than
				failing quietly, which is the right way round.
			*/

		public function grantAccess($args) {
			return TRUE;
		}

			// Running it
			// -----------------------------------------------

		public function runModify() {
			$this->fakeRequest();

			require(GGCMS_DIR . 'classes/StandardLibraries.php');

				//  After StandardLibraries, because it extends Handler.

			clireq('classes/Entries/CLIHandler.php');

			$handler = new CLIHandler();

			ob_start();
			$handler->HandleRequest();
			$output = ob_get_contents();
			ob_end_clean();

			return $this->reportResult([
				'handler'=>$handler,
				'output'=>$output,
			]);
		}

			/*
				modify.php reports through $script->save_status rather than a
				return value, so that is what gets read.  The rendered page is
				captured and discarded -- it is a form, and nobody is looking at
				it -- but its length is printed, because a zero-length response
				means the request never reached the script at all.
			*/

		public function reportResult($args) {
			$handler = $args['handler'];
			$output = $args['output'];

			$script = FALSE;

			if(property_exists($handler, 'script') && is_object($handler->script)) {
				$script = $handler->script->script;
			}

			if(!$script) {
				print('The request did not reach a script.  Check --path.' . PHP_EOL . PHP_EOL);
				return FALSE;
			}

			$status = property_exists($script, 'save_status') ? $script->save_status : '';

			print('Status   : ' . ($status ? $status : '(none reported)') . PHP_EOL);
			print('Response : ' . strlen($output) . ' bytes' . PHP_EOL);

				/*
					No status means Save() was never reached, and the response is
					then the only evidence of what happened instead.  It is
					usually short and usually says so -- an error page, or a
					redirect to the login screen.  Printing it beats guessing.
				*/

			if(!$status) {
				print(PHP_EOL . '--- response, since nothing reported a status ---' . PHP_EOL);
				print(trim(strip_tags($output)) . PHP_EOL);
				print('--- end ---' . PHP_EOL);
			}

			print(PHP_EOL);

			return TRUE;
		}
	}

?>
