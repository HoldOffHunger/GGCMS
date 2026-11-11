<?php

	/*
		What each column of an import is.

		A header is <Table>_<Field>: Entry_Title, Tag_Language,
		TextBody_Text.  The fields are the ones the Edit form posts, read
		out of ChangeRecordsVocabulary, so a column the importer accepts is
		a field modify.php saves, and nothing else.

		Beside them, the columns that are the importer's own and are never
		stored:

			<Table>_id              an existing record: update it, or with Delete, delete it
			Delete                  Y or YES deletes the row's record
			Entry_Identifier        a name for an entry within this file, so several rows
			                        can be one entry and other rows can be its children
			Entry_ParentIdentifier  the Entry_Identifier of the entry to file this one under
			Entry_ParentPath        the path to file it under, as in a URL: /books/poetry/
			Assignment_Parentid     the id of the entry to file it under
			Option_<switch>         one of the Add form's switches, such as title-smart-title-case
			Current_<header>        the value the field must still have for an update to go ahead

		A header with no table, such as id or Title, belongs to the table
		chosen for the import.  Case does not matter.  Every column is
		optional, and a file of "Entry_id<tab>Delete" is a mass delete.
	*/

	trait ImportRecordsHeaders {

			/*
				Every table an import may name, by its formal name, with the
				variable the belt keeps its records under.  Entry first, and
				the rest in the order modify.php reads them.
			*/

		public function ImportTables() {
			$tables = ['Entry'=>['variablename'=>'entry']];

			foreach($this->RecordQueryDefinitions() as $variable_name => $definition) {
				$tables[$definition['formal']] = ['variablename'=>$variable_name];
			}

			return $tables;
		}

		public function ImportTableName($args) {
			$name = mb_strtolower((string)$args['table']);

			foreach(array_keys($this->ImportTables()) as $table) {
				if(mb_strtolower($table) === $name) {
					return $table;
				}
			}

			return NULL;
		}

			/*
				A table's fields as an import names them, each with what it
				does, for the page's header builder and its manual.
			*/

		public function ImportTableFields($args) {
			$table = $this->ImportTableName(['table'=>$args['table']]);

			if(!$table) {
				return [];
			}

			$fields = [];

			if($table === 'Entry') {
				$fields['Entry_id'] = 'Leave blank to make a new entry; fill it to change, or Delete, the entry with that id.';
				$fields['Entry_Identifier'] = 'A name of your own for the entry, within this file.  Rows with the same one are the same entry.';
				$fields['Entry_ParentIdentifier'] = 'File the new entry under the entry this file names by that Entry_Identifier.';
				$fields['Entry_ParentPath'] = 'File the new entry under the entry at this path, e.g. /books/poetry/.';
				$fields['Assignment_Parentid'] = 'File the new entry under the entry with this id.';

				foreach($this->EntryQueryFields() as $field) {
					$fields['Entry_' . $field] = 'The entry\'s ' . $field . '.';
				}

				foreach($this->EntryQueryOptions() as $option => $description) {
					$fields['Option_' . $option] = $description . ', when 1.';
				}
			} else {
				$variable_name = $this->ImportTables()[$table]['variablename'];
				$definition = $this->RecordQueryDefinitions()[$variable_name];

				$fields[$table . '_id'] = 'Leave blank to add a new ' . $table . '; fill it to change, or Delete, the ' . $table . ' with that id.';
				$fields['Entry_id'] = 'The entry a new ' . $table . ' is added to.';
				$fields['Entry_Identifier'] = 'Or: the entry, named by its Entry_Identifier elsewhere in this file.';

				foreach($definition['objectdefinition'] as $parameter) {
					$field = $this->ImportFieldName(['variablename'=>$variable_name, 'parameter'=>$parameter]);
					$fields[$table . '_' . $field] = 'The ' . $table . '\'s ' . $field . '.';
				}

				if($variable_name === 'textbody') {
					foreach($this->TextBodyQueryOptions() as $parameter => $field) {
						$fields['TextBody_' . $field] = '1 or 0: ' . $field . ' for this text.';
					}
				}

				if($variable_name === 'image') {
					$fields['Image_FileName'] = 'The image\'s file name; a new image\'s file is the one in the zip with this name.';
				}
			}

			$fields['Delete'] = 'Y or YES to delete the ' . $table . ' this row names by its id.';

			return $fields;
		}

			//  'tag_Language' is a tag's Language, as SetRecordFromQuery() stores it.

		public function ImportFieldName($args) {
			return preg_replace('/^' . $args['variablename'] . '_/', '', $args['parameter']);
		}

			/*
				One header, understood.  Returns a descriptor -- what kind of
				column it is and what it names -- or NULL when it is nothing
				this importer knows, which refuses the file: a misspelt column
				would otherwise be read as no column, and its values dropped
				without a word.
			*/

		public function ParseImportHeader($args) {
			$header = trim((string)$args['header']);
			$table = $args['table'];

			if(!strlen($header)) {
				return ['kind'=>'blank'];
			}

			$lower = mb_strtolower($header);

			if($lower === 'delete') {
				return ['kind'=>'delete'];
			}

			if(strpos($lower, 'current_') === 0) {
				$guarded = $this->ParseImportHeader(['header'=>substr($header, strlen('current_')), 'table'=>$table]);

				if(!$guarded || !in_array($guarded['kind'], ['entryfield', 'childfield'], TRUE)) {
					return NULL;
				}

				$guarded['guard'] = TRUE;

				return $guarded;
			}

			if(strpos($lower, 'option_') === 0) {
				$option = substr($header, strlen('option_'));

				foreach(array_keys($this->EntryQueryOptions()) as $known) {
					if(mb_strtolower($known) === mb_strtolower($option)) {
						return ['kind'=>'option', 'parameter'=>$known];
					}
				}

				return NULL;
			}

			$pieces = explode('_', $header, 2);

			if(count($pieces) === 2) {
				$named_table = $this->ImportTableName(['table'=>$pieces[0]]);

				if($named_table) {
					return $this->ParseImportTableHeader(['table'=>$named_table, 'field'=>$pieces[1]]);
				}

				if(mb_strtolower($pieces[0]) === 'assignment' && mb_strtolower($pieces[1]) === 'parentid') {
					return ['kind'=>'parentid'];
				}
			}

				//  No table named: the import's own table.

			if($table) {
				return $this->ParseImportTableHeader(['table'=>$table, 'field'=>$header]);
			}

			return NULL;
		}

		public function ParseImportTableHeader($args) {
			$table = $args['table'];
			$field = mb_strtolower($args['field']);

			if($table === 'Entry') {
				$own = [
					'id'=>'entryid',
					'identifier'=>'identifier',
					'parentidentifier'=>'parentidentifier',
					'parentpath'=>'parentpath',
					'parentid'=>'parentid',
				];

				if(isset($own[$field])) {
					return ['kind'=>$own[$field]];
				}

				foreach($this->EntryQueryFields() as $known) {
					if(mb_strtolower($known) === $field) {
						return ['kind'=>'entryfield', 'parameter'=>$known];
					}
				}

				return NULL;
			}

			$variable_name = $this->ImportTables()[$table]['variablename'];

			if($field === 'id') {
				return ['kind'=>'childid', 'table'=>$table, 'variablename'=>$variable_name];
			}

			if($field === 'entryid') {
				return ['kind'=>'entryid'];
			}

			foreach($this->RecordQueryDefinitions()[$variable_name]['objectdefinition'] as $parameter) {
				if(mb_strtolower($this->ImportFieldName(['variablename'=>$variable_name, 'parameter'=>$parameter])) === $field) {
					return ['kind'=>'childfield', 'table'=>$table, 'variablename'=>$variable_name, 'parameter'=>$parameter, 'field'=>$this->ImportFieldName(['variablename'=>$variable_name, 'parameter'=>$parameter])];
				}
			}

			if($variable_name === 'textbody') {
				foreach($this->TextBodyQueryOptions() as $parameter => $option) {
					if(mb_strtolower($option) === $field) {
						return ['kind'=>'childoption', 'table'=>$table, 'variablename'=>$variable_name, 'parameter'=>$parameter, 'field'=>$option];
					}
				}
			}

			return NULL;
		}

			/*
				The whole header, understood, with each column's occurrence:
				the second Tag_Tag is occurrence 1, and goes with the second
				Tag_Language and the second Tag_id.  Returns the descriptors
				and the headers nobody could read.
			*/

		public function ParseImportHeaders($args) {
			$headers = $args['headers'];
			$table = $args['table'];

			$columns = [];
			$unknown = [];
			$seen = [];

			foreach($headers as $index => $header) {
				$descriptor = $this->ParseImportHeader(['header'=>$header, 'table'=>$table]);

				if($descriptor === NULL) {
					$unknown[] = $header;
					$columns[$index] = ['kind'=>'blank'];
					continue;
				}

				$key = json_encode($descriptor);
				$descriptor['occurrence'] = isset($seen[$key]) ? $seen[$key] : 0;
				$seen[$key] = $descriptor['occurrence'] + 1;

				$descriptor['header'] = $header;
				$columns[$index] = $descriptor;
			}

			return [
				'columns'=>$columns,
				'unknown'=>$unknown,
			];
		}

			//  Y and YES, any case; anything else is not a delete.

		public function ImportSaysDelete($args) {
			return in_array(mb_strtolower(trim((string)$args['value'])), ['y', 'yes'], TRUE);
		}
	}

?>
