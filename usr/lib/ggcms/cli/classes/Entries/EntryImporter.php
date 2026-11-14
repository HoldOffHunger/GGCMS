<?php

	clireq('classes/Entries/EntryModifier.php');

	/*
		Drives importer.php from a shell, and prints its report.

		Like EntryModifier, it reimplements nothing: it makes the shell look
		like a request to importer.php, with the file staged as an upload,
		and runs the real thing.  So a shell import and a browser import are
		one importer, one belt, one report -- every row's outcome, with the
		reasons for every error.

		Preview by default; --apply imports.  Either way it needs somebody to
		act as, because importer.php is an administrator's page and refuses
		anybody else before it reads a row.
	*/

	class EntryImporter extends EntryModifier {
		public $file;
		public $images;
		public $table;
		public $limit;
		public $keep_cache;
		public $only;
		public $report_file;

		public function bannerMessageText() {
			return 'Import Entries';
		}

		public function confirmDomainText() {
			return 'Importing entries on: ';
		}

			// Entry point
			// -----------------------------------------------

		public function importEntries() {
			$this->bannerMessage();
			$this->setHandle();

			if(!$this->checkExtensions()) {
				return FALSE;
			}

			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}

			$this->setImportOptions();

			if(!strlen($this->file)) {
				return $this->cancelAction(['message'=>'Nothing to import.  Pass --file=/path/to/records.tsv (or .csv, .json, .jsonl, .xlsx, .zip).']);
			}

			foreach(array_filter([$this->file, $this->images], 'strlen') as $path) {
				if(!is_readable($path)) {
					return $this->cancelAction(['message'=>'Cannot read ' . $path . '.']);
				}
			}

			if(!strlen($this->user)) {
				return $this->cancelAction(['message'=>'importer.php is an administrator\'s page, so even a preview needs somebody to act as.  Pass --user=ADMIN, or --user=<id>.']);
			}

			print('File     : ' . $this->file . ' (' . number_format(filesize($this->file)) . ' bytes)' . PHP_EOL);

			if(strlen($this->images)) {
				print('Images   : ' . $this->images . PHP_EOL);
			}

			print('Table    : ' . $this->table . PHP_EOL);
			print('Limit    : ' . ($this->limit ? $this->limit . ' row(s)' : 'every row') . PHP_EOL);
			print('User     : ' . $this->user . PHP_EOL);
			print('Mode     : ' . ($this->apply ? 'APPLY -- writes to the database' : 'preview -- nothing is written') . PHP_EOL . PHP_EOL);

			return $this->runImport();
		}

		public function setImportOptions() {
			$this->user = $this->option(['name'=>'user', 'default'=>'']);
			$this->apply = $this->flag(['name'=>'apply']);
			$this->keep_cache = $this->flag(['name'=>'keep-cache']);
			$this->path = $this->option(['name'=>'path', 'default'=>'/']);
			$this->file = $this->option(['name'=>'file', 'default'=>'']);
			$this->images = $this->option(['name'=>'images', 'default'=>'']);
			$this->table = $this->option(['name'=>'table', 'default'=>'Entry']);
			$this->limit = (int)$this->option(['name'=>'limit', 'default'=>'20']);
			$this->report_file = $this->option(['name'=>'report', 'default'=>'']);

			$only = $this->option(['name'=>'only', 'default'=>'']);
			$this->only = strlen($only) ? array_map('trim', explode(',', $only)) : [];

			return TRUE;
		}

			//  importer.php at --path, which only changes what the breadcrumbs say.

		public function scriptURL() {
			$path = '/' . trim($this->path, '/');

			if($path !== '/') {
				$path .= '/';
			}

			return $path . 'importer.php';
		}

			/*
				The files as a browser uploads them: one file per field, copied
				into the temporary directory so nothing of the caller's is moved.
				CLIHandler vouches for exactly these copies.
			*/

		public function stageImportFiles() {
			$_FILES = [];
			$staged = [];

			$fields = [
				'ImportFile'=>$this->file,
				'ImportImages'=>$this->images,
			];

			foreach($fields as $name => $path) {
				if(!strlen($path)) {
					continue;
				}

				$temporary = tempnam(sys_get_temp_dir(), 'ggcms-import-upload-');

				if($temporary === FALSE || !copy($path, $temporary)) {
					return NULL;
				}

				$staged[] = $temporary;

				$_FILES[$name] = [
					'name'=>basename($path),
					'type'=>(string)mime_content_type($temporary),
					'tmp_name'=>$temporary,
					'error'=>UPLOAD_ERR_OK,
					'size'=>filesize($temporary),
				];
			}

			return $staged;
		}

		public function runImport() {
			$action = $this->apply ? 'Import' : 'Preview';

			$this->fakeRequest([
				'action'=>$action,
				'post'=>[
					'ImportTable'=>$this->table,
					'ImportLimit'=>(string)$this->limit,
					'ImportKeepCache'=>$this->keep_cache ? '1' : '',
				],
			]);

			$staged = $this->stageImportFiles();

			if($staged === NULL) {
				print('Refused  : could not copy the file into ' . sys_get_temp_dir() . ' to upload it.' . PHP_EOL);

				return FALSE;
			}

			require(GGCMS_DIR . 'classes/StandardLibraries.php');

			clireq('classes/Entries/CLIAuthentication.php');
			clireq('classes/Entries/CLIHandler.php');

			ob_start();
			$handler = new CLIHandler();
			$handler->SetCLIUser(['user'=>$this->user]);
			$handler->SetCLIUploads(['files'=>$staged]);
			$handler->HandleRequest();
			$output = ob_get_contents();
			ob_end_clean();

			$this->unstageFiles(['staged'=>$staged]);

			return $this->reportImport(['handler'=>$handler, 'output'=>$output]);
		}

			/*
				The report importer.php made, as text.  The rendered page is
				thrown away, as EntryModifier throws its form away; when no
				report was made at all, the page is all there is to say why,
				so it is printed.
			*/

		public function reportImport($args) {
			$handler = $args['handler'];

			$authentication_error = $handler->CLIAuthenticationError();

			if($authentication_error) {
				print('Refused  : ' . $authentication_error . PHP_EOL);

				return FALSE;
			}

			$script = (isset($handler->script) && is_object($handler->script)) ? $handler->script->script : NULL;

			if(!$script || !($script instanceof importer)) {
				print('The request did not reach importer.php.  Is --user an administrator?' . PHP_EOL . PHP_EOL);
				print(trim(strip_tags($args['output'])) . PHP_EOL);

				return FALSE;
			}

			if(!$script->import_report) {
				print('Refused  : ' . ($script->import_upload_error ? $script->import_upload_error : 'importer.php made no report.') . PHP_EOL);

				return FALSE;
			}

			$text = $script->ImportReportText(['report'=>$script->import_report, 'only'=>$this->only]);

			print($text);

			if(strlen($this->report_file)) {
				file_put_contents($this->report_file, $script->ImportReportText(['report'=>$script->import_report]));
				print(PHP_EOL . 'The whole report is in ' . $this->report_file . PHP_EOL);
			}

			if(!$this->apply) {
				print(PHP_EOL . 'Preview.  Nothing was written.  Add --apply to import.' . PHP_EOL);
			}

			return !$script->ImportReportHasErrors(['report'=>$script->import_report]);
		}
	}

?>
