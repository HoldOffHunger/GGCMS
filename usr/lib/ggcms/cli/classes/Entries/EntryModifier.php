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
		
		public $action;
		public $path;
		public $user;
		public $apply;
		public $show_response;
		public $dump_form;
		public $fields;
		public $clear;

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

				//  --dump-form only reads; it has nothing to write and needs no field.

			if(!$this->fields && !$this->dump_form) {
				return $this->cancelAction(['message'=>'Nothing to write.  Pass at least one --field=name=value.']);
			}

				/*
					modify.php will not save for nobody, and that is correct --
					ValidateRecordForSaving_EntryPermission() refuses a write with
					no user behind it, on the web and here alike.  Refusing early
					means the message names the missing argument, rather than
					arriving as an invitation to visit the login page, which is
					not advice anybody at a prompt can act on.

					Not required for a dry run, which reaches no gate.
				*/

			if($this->apply && !strlen($this->user)) {
				return $this->cancelAction(['message'=>'Saving needs somebody to save as.  Pass --user=ADMIN for the administrator account, or --user=<id> for a particular one.']);
			}

			$this->reportIntent();

				/*
					A dry run still renders the form.  It is the only way to show
					what would actually be posted -- how many fields came back, and
					whether --path resolved at all, which a 404 otherwise hides
					behind a cheerful report of a save.
				*/

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
				/*
					The set Docs/Installation.md names, not the set the last
					failure named.  Checking them one at a time meant three runs
					and three stack traces to learn three package names --
					mbstring for UTF8Characters, then intl for
					normalizer_normalize() in GenerateURLCodeFromValue().
				*/

			$required = [
				'mbstring'=>'php8.1-mbstring',
				'mysqli'=>'php8.1-mysql',
				'intl'=>'php8.1-intl',
				'xml'=>'php8.1-xml',
				'zip'=>'php8.1-zip',
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
			$this->user = $this->option(['name'=>'user', 'default'=>'']);
			$this->apply = $this->flag(['name'=>'apply']);
			$this->show_response = $this->flag(['name'=>'show-response']);
			$this->dump_form = $this->flag(['name'=>'dump-form']);
			$this->fields = $this->fieldArguments();
			$this->clear = $this->clearArguments();

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

					/*
						A name ending in [] is an array field, and most of the
						interesting ones are: the textbody field is Text[], because
						an entry may carry several.  A browser posts name="Text[]"
						as $_POST['Text'] = ['...'], so storing the literal key
						'Text[]' put something in the request that modify.php never
						reads.  It then rendered the form and saved nothing, with no
						error anywhere, because nothing was wrong -- the field had
						simply not been mentioned.

						Repeat the argument to post several values, in order.
					*/

				if(substr($pieces[0], -2) === '[]') {
					$name = substr($pieces[0], 0, -2);

					if(!array_key_exists($name, $fields) || !is_array($fields[$name])) {
						$fields[$name] = [];
					}

					$fields[$name][] = $pieces[1];

					continue;
				}

				$fields[$pieces[0]] = $pieces[1];
			}

			return $fields;
		}

		public function reportIntent() {
			print('Action   : ' . $this->action . PHP_EOL);
			print('URL      : ' . $this->scriptURL() . PHP_EOL);
			print('User     : ' . (strlen($this->user) ? $this->user : '(none -- dry run only)') . PHP_EOL);
			print('Mode     : ' . ($this->apply ? 'APPLY -- writes to the database' : 'dry run') . PHP_EOL);
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

		public function fakeRequest($args = []) {
			$action = array_key_exists('action', $args) ? $args['action'] : $this->action;
			$post   = array_key_exists('post', $args)   ? $args['post']   : $this->fields;

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

			$_GET = ['action'=>$action];
			$_POST = $post;
			$_POST['action'] = $action;

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

			// Rendering the form in a child process
			// -----------------------------------------------

		public function fetchFormFields() {
			$command = escapeshellarg(PHP_BINARY)
				. ' ' . escapeshellarg($this->argv[0])
				. ' ' . escapeshellarg($this->domain)
				. ' --path=' . escapeshellarg($this->path)
				. ' --user=' . escapeshellarg($this->user)
				. ' --action=' . escapeshellarg($this->action)
				. ' --dump-form'
					//  NUL, not /dev/null, on Windows: cmd cannot open a path that does not
					//  exist, fails the whole command, and the form reads back as nothing.
				. (PHP_OS_FAMILY === 'Windows' ? ' 2>NUL' : ' 2>/dev/null');

			$output = (string) shell_exec($command);

			$start = strpos($output, '--FORMJSON--');
			$end = strpos($output, '--ENDFORMJSON--');

			if($start === FALSE || $end === FALSE || $end <= $start) {
				return NULL;
			}

			$start += strlen('--FORMJSON--');

			$decoded = json_decode(substr($output, $start, $end - $start), TRUE);

			return is_array($decoded) ? $decoded : NULL;
		}

			/*
				Renders the form the verb belongs to and prints its fields as JSON
				between two markers, so the parent can find them in among whatever
				else the engine decided to say.

				The two verbs have two forms, as they do in the admin panel.  Add
				-- "add a child to the current entry" -- renders an empty form
				that posts Save, and the child is made beneath the entry at
				--path.  Edit -- "change the current entry" -- renders the entry's
				own fields and posts Update.  Reading the Edit form for a Save made
				the new child a copy of its parent: its quote, description, tags
				and pictures, all posted back as the child's.
			*/

		public function formAction() {
			return ($this->action === 'Save') ? 'Add' : 'Edit';
		}

		public function dumpForm() {
			$this->fakeRequest(['action'=>$this->formAction(), 'post'=>[]]);

			require(GGCMS_DIR . 'classes/StandardLibraries.php');

				//  After StandardLibraries, because it extends Handler.

			clireq('classes/Entries/CLIAuthentication.php');
			clireq('classes/Entries/CLIHandler.php');

			ob_start();
			$handler = new CLIHandler();
			$handler->SetCLIUser(['user'=>$this->user]);
			$handler->HandleRequest();
			$html = ob_get_contents();
			ob_end_clean();

			print('--FORMJSON--' . json_encode($this->parseFormFields(['html'=>$html])) . '--ENDFORMJSON--');

			return TRUE;
		}

			// Reading the form back
			// -----------------------------------------------

			/*
				Whatever modify.php just rendered, as a request.

				A browser posts the form it was given, so that form is the exact
				shape of a request which changes nothing.  Reading it back gives
				a base to override rather than a field list to maintain.

				Four rules, all of them a browser's.  An unchecked checkbox or
				radio does not post at all.  A select posts its selected option,
				or the first one when the markup names none.  A file input never
				posts a value, and there is nothing to upload from a shell.  And
				a name ending in [] keeps its order, which matters because Text[]
				and textbody_Language[] are read positionally.
			*/

		public function parseFormFields($args) {
			$html = $args['html'];

			$fields = [];

			if(!strlen(trim($html))) {
				return $fields;
			}

			$previous = libxml_use_internal_errors(TRUE);

			$dom = new DOMDocument();
			@$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

			libxml_clear_errors();
			libxml_use_internal_errors($previous);

			$xpath = new DOMXPath($dom);

			foreach($xpath->query('//input|//textarea|//select') as $node) {
				$name = $node->getAttribute('name');

				if(!strlen($name)) {
					continue;
				}

				$tag = strtolower($node->nodeName);

				if($tag === 'input') {
					$type = strtolower($node->getAttribute('type'));

					if(!strlen($type)) {
						$type = 'text';
					}

					if(in_array($type, ['file', 'submit', 'button', 'image', 'reset'], TRUE)) {
						continue;
					}

					if(($type === 'checkbox' || $type === 'radio') && !$node->hasAttribute('checked')) {
						continue;
					}

					$value = $node->getAttribute('value');
				} elseif($tag === 'textarea') {
					$value = $node->textContent;
				} else {
					$value = NULL;
					$first = NULL;

					foreach($node->getElementsByTagName('option') as $option) {
						$option_value = $option->hasAttribute('value') ? $option->getAttribute('value') : $option->textContent;

						if($first === NULL) {
							$first = $option_value;
						}

						if($option->hasAttribute('selected')) {
							$value = $option_value;

							break;
						}
					}

					if($value === NULL) {
						$value = ($first === NULL) ? '' : $first;
					}
				}

				if(substr($name, -2) === '[]') {
					$key = substr($name, 0, -2);

					if(!array_key_exists($key, $fields) || !is_array($fields[$key])) {
						$fields[$key] = [];
					}

					$fields[$key][] = $value;

					continue;
				}

				$fields[$name] = $value;
			}

			return $fields;
		}

			/*
				The form, minus what --clear names, plus what --field sets.

				Clearing happens first, so --clear=Image alongside
				--field=image_Title[]=x is a deliberate replacement rather than a
				contradiction.
			*/

		public function mergeFields($args) {
			$post = $args['base'];

			foreach($this->clear as $type) {
				foreach($this->clearFieldPrefixes(['type'=>$type]) as $prefix) {
					foreach(array_keys($post) as $key) {
						if(stripos($key, $prefix) === 0) {
							unset($post[$key]);
						}
					}
				}
			}

			foreach($this->fields as $name => $value) {
				$post[$name] = $value;
			}

			return $post;
		}

			/*
				A record type names more than one form field.  A textbody is
				Text[] and its textbody_* companions, and dropping one without
				the others leaves the positional arrays misaligned.

				Clearing Image takes ImageTranslation with it, which is intended:
				a translation of an image that no longer exists is not a record
				anybody wants kept.
			*/

		public function clearFieldPrefixes($args) {
			$type = strtolower($args['type']);

			$map = [
				'image'=>['image_', 'Image'],
				'textbody'=>['Text', 'textbody_'],
				'description'=>['Description', 'description_'],
				'quote'=>['Quote', 'quote_'],
				'tag'=>['Tag', 'tag_'],
				'link'=>['Link', 'link_'],
				'eventdate'=>['EventDate', 'eventdate_'],
				'definition'=>['Definition', 'definition_'],
				'entrytranslation'=>['EntryTranslation', 'entrytranslation_'],
				'association'=>['ChosenEntryid', 'association_'],
			];

			if(array_key_exists($type, $map)) {
				return $map[$type];
			}

			return [$args['type']];
		}

		public function clearArguments() {
			$clear = [];

			foreach($this->argv as $argument) {
				if(strpos($argument, '--clear=') !== 0) {
					continue;
				}

				$value = substr($argument, strlen('--clear='));

				if(strlen($value)) {
					$clear[] = $value;
				}
			}

			return $clear;
		}

		public function reportPlan($args) {
			$base = $args['base'];
			$post = $args['post'];

			print('Form     : ' . count($base) . ' field(s) read back from the rendered ' . $this->formAction() . ' form' . PHP_EOL);

			if(count($this->clear)) {
				print('Clearing : ' . implode(', ', $this->clear) . PHP_EOL);
			}

			print('Posting  : ' . count($post) . ' field(s)' . PHP_EOL . PHP_EOL);

			foreach($this->fields as $name => $value) {
				if(is_array($value)) {
					$shown = '[' . count($value) . ' value(s)] ' . (array_key_exists(0, $value) ? substr((string) $value[0], 0, 76) : '');
				} else {
					$shown = substr((string) $value, 0, 90);
				}

				printf('  %-22s %s' . PHP_EOL, $name, $shown);
			}

			return TRUE;
		}

			// Running it
			// -----------------------------------------------

			/*
				Two passes, because a field nobody mentioned must survive.

				modify.php takes the request as the whole truth: a record type
				absent from the POST is a record type the user deleted, which is
				right for a browser -- the form always carries everything, and
				removing a section is how you delete it -- and wrong for a shell,
				where naming one field would silently destroy the rest.  Passing
				only --field=Title=x would have taken every image with it.

				So the first pass asks the engine to render the verb's form -- Edit
				for an Update, Add for a Save, see formAction() -- and the
				second posts it back.  The base state is whatever modify.php
				itself just put on the page, which means there is no field list
				to maintain here and never will be: add a field to Edit.php or Add.php and
				this carries it without being told.  It is the same argument the
				header of this file makes about not reimplementing modify.php.

				Deletion is therefore explicit, --clear=Image, rather than
				something omission does to you by accident.
			*/

		public function runModify() {
			if($this->dump_form) {
				return $this->dumpForm();
			}

				/*
					The first pass runs as its own process.

					Two HandleRequest() calls in one process cannot work: the
					site configuration files declare classes and are pulled in
					with require rather than require_once, so the second pass
					dies on "Cannot declare class defaultglobals".  A separate
					process is also what the web does between rendering a form
					and receiving it, which is the behaviour being imitated.
				*/

			$base = $this->fetchFormFields();

			if($base === NULL) {
				print('Refused  : could not read the ' . $this->formAction() . ' form back.  Run the same' . PHP_EOL);
				print('           command with --dump-form to see what the engine said.' . PHP_EOL);

				return FALSE;
			}

			if(!count($base)) {
				print('Refused  : the ' . $this->formAction() . ' form rendered no fields, so there is no' . PHP_EOL);
				print('           safe base to post back.  Check that --path resolves:' . PHP_EOL);
				print('           a 404 still reports a save and writes nothing.' . PHP_EOL);

				return FALSE;
			}

			$post = $this->mergeFields(['base'=>$base]);

			$this->reportPlan(['base'=>$base, 'post'=>$post]);

			if(!$this->apply) {
				print(PHP_EOL . 'Dry run.  Nothing was written.  Add --apply to run it.' . PHP_EOL);

				return TRUE;
			}

			$this->fakeRequest(['post'=>$post]);

			require(GGCMS_DIR . 'classes/StandardLibraries.php');

				//  After StandardLibraries, because it extends Handler.

			clireq('classes/Entries/CLIAuthentication.php');
			clireq('classes/Entries/CLIHandler.php');

			ob_start();
			$handler = new CLIHandler();
			$handler->SetCLIUser(['user'=>$this->user]);
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

				/*
					The lookup's own complaint comes first when there is one.
					Without this the failure surfaces as modify.php's "you may
					only save information if you are logged in, which you may do
					here: <url>" -- accurate, and useless to somebody at a prompt
					who needs to know that ADMIN matched two accounts.
				*/

			$authentication_error = $handler->CLIAuthenticationError();

			if($authentication_error) {
				print('Refused  : ' . $authentication_error . PHP_EOL . PHP_EOL);

				return FALSE;
			}

			$script = FALSE;

			if(isset($handler->script) && is_object($handler->script)) {
				$script = $handler->script->script;
			}

			if(!$script) {
				print('The request did not reach a script.  Check --path.' . PHP_EOL . PHP_EOL);
				return FALSE;
			}

			$status = isset($script->save_status) ? $script->save_status : '';

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
