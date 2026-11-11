<?php

	/*
		Imports a file of records: new entries, changes to old ones, and
		deletions, every one of them through the belt modify.php uses -- see
		traits/scripts/ChangeRecords.php and traits/scripts/ImportRecords.php.

		The page is the manual.  From a shell, the same script is reached by
		cli/scripts/internal/entries/import_entries.php.

			display   the manual, and the form
			Fields    a table's columns, as JSON, for the header builder
			Preview   the file run with the belt stopped at Prepare: a report, nothing written
			Import    the file run for real: a report of what each row did
	*/

	ggreq('traits/scripts/DBFunctions.php');
	ggreq('traits/scripts/SimpleErrors.php');
	ggreq('traits/scripts/SimpleForms.php');
	ggreq('traits/scripts/SimpleImages.php');
	ggreq('traits/scripts/SimpleLanguages.php');
	ggreq('traits/scripts/SimpleLookupLists.php');
	ggreq('traits/scripts/SimpleORM.php');
	ggreq('traits/scripts/UserAccounts.php');
	ggreq('traits/scripts/ChangeRecords.php');
	ggreq('traits/scripts/ImportRecords.php');

	class importer extends basicscript {
						// Traits
						// ---------------------------------------------

		use DBFunctions;
		use SimpleErrors;
		use SimpleForms;
		use SimpleImages;
		use SimpleLanguages;
		use SimpleLookupLists;
		use UserAccounts;
		use ImportRecords;

		use SimpleORM, ChangeRecords {
			ChangeRecords::updateFileName insteadof SimpleORM;
			ChangeRecords::startsWith insteadof SimpleORM;
		}

		public $import_tables;
		public $import_formats;
		public $import_fields;
		public $import_token;
		public $import_upload_error;
		public $import_constructor_args;
		
			/*
				The args this request's format built this script with, kept: every
				rider is built from a copy of them, as a script for a request of
				its own would be.  HTML builds its own list rather than asking
				SetScriptConstructorArgs(), so they are taken here, where both
				arrive.
			*/
		
		public function __construct($args) {
			$this->import_constructor_args = $args;
			
			parent::__construct($args);
		}

						// Security Data
						// ---------------------------------------------

			/*
				An administrator's tool.  It sets Code and Publish, deletes
				entries by the hundred, and puts files on the server; the
				belt's isUserAdmin() checks would refuse a reader those field
				by field, but a reader has no business with a mass delete.
			*/

		public function IsSecure() {
			return TRUE;
		}

		public function RequiresLogin() {
			return TRUE;
		}

		public function AdminOnly() {
			return TRUE;
		}

						// The Manual
						// ---------------------------------------------

		public function display() {
			$this->SetOrmBasics();

			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}

			$this->header_title_text = $this->master_record['Title'];
			$this->import_tables = array_keys($this->ImportTables());
			$this->import_formats = (new ImportFile())->Formats();

			return TRUE;
		}

			/*
				importer.json?action=Fields&table=Tag -- what the header builder
				asks when a table is chosen.  The JSON format prints the
				script's record_to_use, so that is what this sets.
			*/

		public function Fields() {
			$table = $this->ImportTableName(['table'=>(string)$this->Param('table')]);

			if(!$table) {
				$this->import_fields = ['error'=>'No such table.'];
				return TRUE;
			}

			$fields = $this->ImportTableFields(['table'=>$table]);

			$this->import_fields = [
				'table'=>$table,
				'fields'=>$fields,
				'header'=>implode("\t", array_keys($fields)),
			];

			return TRUE;
		}

		public function SetRecordToUseForMetadata() {
			if($this->import_fields) {
				return $this->import_fields;
			}

			return parent::SetRecordToUseForMetadata();
		}

						// Preview and Import
						// ---------------------------------------------

		public function Preview() {
			return $this->ImportFromRequest(['apply'=>FALSE]);
		}

		public function Import() {
			return $this->ImportFromRequest(['apply'=>TRUE]);
		}

		public function ImportFromRequest($args) {
			set_time_limit(0);

			if(!$this->display()) {
				return FALSE;
			}

			$upload = $this->ImportUploadedFile(['name'=>'ImportFile']);

			if($upload === NULL && strlen((string)$this->Param('ImportText'))) {
				$upload = ['content'=>(string)$this->Param('ImportText'), 'name'=>''];
			}

			$images = $this->ImportUploadedFile(['name'=>'ImportImages']);

				//  Import from a preview: the same files, kept under a token rather than sent twice.

			if($upload === NULL && strlen((string)$this->Param('ImportToken'))) {
				$kept = $this->ImportKeptFiles(['token'=>(string)$this->Param('ImportToken')]);

				if($kept) {
					$upload = $kept['upload'];
					$images = $kept['images'];
				}
			}

			if($upload === NULL) {
				$this->import_upload_error = $this->import_upload_error ? $this->import_upload_error : 'Choose a file, or paste one, to import.';
				return TRUE;
			}

			if(!$args['apply']) {
				$this->import_token = $this->ImportKeepFiles(['upload'=>$upload, 'images'=>$images]);
			}

			$this->RunImport([
				'content'=>$upload['content'],
				'name'=>$upload['name'],
				'imagezip'=>$images ? $images['content'] : '',
				'table'=>(string)$this->Param('ImportTable'),
				'apply'=>$args['apply'],
				'limit'=>(int)$this->Param('ImportLimit'),
			]);

			if($args['apply'] && strlen((string)$this->Param('ImportToken'))) {
				$this->ImportForgetFiles(['token'=>(string)$this->Param('ImportToken')]);
			}

			return TRUE;
		}

			/*
				An uploaded file's content.  Only a file PHP received in this
				request, or one the shell staged and CLIHandler vouches for, is
				read: a path in a form field is never opened.
			*/

		public function ImportUploadedFile($args) {
			$name = $args['name'];

			if(!isset($_FILES[$name]) || !is_array($_FILES[$name])) {
				return NULL;
			}

			$file = $_FILES[$name];

			if(is_array($file['tmp_name'])) {
				return NULL;
			}

			if((int)$file['error'] === UPLOAD_ERR_NO_FILE) {
				return NULL;
			}

			if((int)$file['error'] !== UPLOAD_ERR_OK) {
				$this->import_upload_error = 'The upload of ' . $file['name'] . ' failed (PHP upload error ' . (int)$file['error'] . '); a file over upload_max_filesize is the usual reason.';
				return NULL;
			}

			if(!is_uploaded_file($file['tmp_name']) && !$this->handler->IsCLIUpload(['file'=>$file['tmp_name']])) {
				return NULL;
			}

			return [
				'content'=>(string)file_get_contents($file['tmp_name']),
				'name'=>(string)$file['name'],
			];
		}

			/*
				A preview's files, kept for the Import button that follows it.
				The token is the SHA-256 of what was kept, and the only thing
				the form carries; a token that names nothing kept, or names a
				file whose content no longer hashes to it, is no token.
			*/

		public function ImportKeptPath($args) {
			return sys_get_temp_dir() . '/ggcms-import-' . $args['token'] . '.json';
		}

		public function ImportKeepFiles($args) {
			$kept = json_encode([
				'upload'=>['content'=>base64_encode($args['upload']['content']), 'name'=>$args['upload']['name']],
				'images'=>$args['images'] ? ['content'=>base64_encode($args['images']['content']), 'name'=>$args['images']['name']] : NULL,
			]);

			$token = hash('sha256', $kept);

			if(file_put_contents($this->ImportKeptPath(['token'=>$token]), $kept) === FALSE) {
				return '';
			}

			return $token;
		}

		public function ImportKeptFiles($args) {
			$token = $args['token'];

			if(!preg_match('/^[0-9a-f]{64}$/', $token)) {
				return NULL;
			}

			$kept = @file_get_contents($this->ImportKeptPath(['token'=>$token]));

			if($kept === FALSE || !hash_equals($token, hash('sha256', $kept))) {
				return NULL;
			}

			$decoded = json_decode($kept, TRUE);

			return [
				'upload'=>['content'=>base64_decode($decoded['upload']['content']), 'name'=>$decoded['upload']['name']],
				'images'=>$decoded['images'] ? ['content'=>base64_decode($decoded['images']['content']), 'name'=>$decoded['images']['name']] : NULL,
			];
		}

		public function ImportForgetFiles($args) {
			if(preg_match('/^[0-9a-f]{64}$/', $args['token']) && is_file($this->ImportKeptPath(['token'=>$args['token']]))) {
				unlink($this->ImportKeptPath(['token'=>$args['token']]));
			}

			return TRUE;
		}

				// ** HTML FORMAT DATA ** //

		public function GetHTMLFormatData_Title() {
			return 'Importer';
		}
	}

		/*
			One entry's ride on the belt.  ImportRider() builds one of these
			for every entry an import touches, the way the format builds a
			script for every request, and runs one of its verbs.  It wears
			exactly what modify.php wears, so that what it does to an entry is
			what modify.php does.
		*/

	class importerrow extends basicscript {
		use DBFunctions;
		use SimpleErrors;
		use SimpleForms;
		use SimpleImages;
		use SimpleLanguages;
		use SimpleLookupLists;
		use UserAccounts;

		use SimpleORM, ChangeRecords {
			ChangeRecords::updateFileName insteadof SimpleORM;
			ChangeRecords::startsWith insteadof SimpleORM;
		}

		public $ride_results;

			/*
				The entry as modify.php's Edit() reads it for its form, and no
				further: no blank rows filled in, nothing about the last edit.
			*/

		public function ImportReadForEdit() {
			$this->SetOrmBasics();

			if(!$this->ValidateOrm()) {
				return FALSE;
			}

			$this->FormatEntryInformation();

			if(!$this->canUserAccess()) {
				return FALSE;
			}

			$this->SetEntryFromCode();
			$this->OrderAndFillChildRecords();
			$this->SetAssociationRecords();
			$this->FormatDates();

			return TRUE;
		}
	}

?>
