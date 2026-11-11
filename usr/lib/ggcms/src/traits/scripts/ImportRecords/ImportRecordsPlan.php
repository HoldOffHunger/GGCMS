<?php

	/*
		Rows into entries.

		The belt saves one entry at a time, with all its child records, so
		an import's rows are first gathered into the entries they belong
		to.  A row belongs to:

			the entry its Entry_id names, or the entry that owns the
			<Table>_id it names -- an existing entry, updated or deleted;

			otherwise the entry its Entry_Identifier names -- every row with
			the same identifier is one entry, which is how one entry gets
			five tags from five rows;

			otherwise the entry with its Entry_Title beneath the same
			parent -- two rows titled alike are one entry, unless an
			identifier says they are two.

		A row that names none of these cannot be placed, and says so.

		Two rows of one entry may not disagree about it: a second Title, or
		a second parent, is an error on the row that brought it.  Blank
		cells say nothing, so they never disagree.
	*/

	trait ImportRecordsPlan {

			/*
				One row, read through the header: which entry it names, what
				it says of that entry, and the child records it carries.
			*/

		public function ReadImportRow($args) {
			$cells = $args['cells'];
			$columns = $args['columns'];

			$row = [
				'line'=>$args['line'],
				'entryid'=>'',
				'identifier'=>'',
				'parentidentifier'=>'',
				'parentpath'=>'',
				'parentid'=>'',
				'entryfields'=>[],
				'entryguards'=>[],
				'options'=>[],
				'children'=>[],
				'delete'=>FALSE,
				'errors'=>[],
			];

			foreach($columns as $index => $column) {
				$value = isset($cells[$index]) ? (string)$cells[$index] : '';
				$kind = $column['kind'];

				if($kind === 'blank' || !strlen($value)) {
					continue;
				}

				switch($kind) {
					case 'delete':
						$row['delete'] = $this->ImportSaysDelete(['value'=>$value]);
						break;

					case 'entryid':
					case 'identifier':
					case 'parentidentifier':
					case 'parentpath':
					case 'parentid':
						$row[$kind] = $value;
						break;

					case 'option':
						$row['options'][$column['parameter']] = $value;
						break;

					case 'entryfield':
						$row[!empty($column['guard']) ? 'entryguards' : 'entryfields'][$column['parameter']] = $value;
						break;

					case 'childid':
					case 'childfield':
					case 'childoption':
						$variable_name = $column['variablename'];
						$occurrence = $column['occurrence'];

						if(!isset($row['children'][$variable_name][$occurrence])) {
							$row['children'][$variable_name][$occurrence] = ['id'=>'', 'fields'=>[], 'guards'=>[], 'options'=>[], 'table'=>$column['table']];
						}

						if($kind === 'childid') {
							$row['children'][$variable_name][$occurrence]['id'] = $value;
						} elseif($kind === 'childoption') {
							$row['children'][$variable_name][$occurrence]['options'][$column['parameter']] = $value;
						} else {
							$row['children'][$variable_name][$occurrence][!empty($column['guard']) ? 'guards' : 'fields'][$column['parameter']] = $value;
						}

						break;
				}
			}

			foreach(['entryid', 'parentid'] as $id_field) {
				if(strlen($row[$id_field]) && !ctype_digit($row[$id_field])) {
					$row['errors'][] = ($id_field === 'entryid' ? 'Entry_id' : 'Assignment_Parentid') . ' must be a number, not "' . $row[$id_field] . '".';
				}
			}

			foreach($row['children'] as $variable_name => $records) {
				foreach($records as $record) {
					if(strlen($record['id']) && !ctype_digit($record['id'])) {
						$row['errors'][] = $record['table'] . '_id must be a number, not "' . $record['id'] . '".';
					}
				}
			}

			return $row;
		}

			/*
				What the row does to the table the import was made for, which
				is what Delete means: the entry for an Entry import, the row's
				first <Table> record for a <Table> import.
			*/

		public function ImportRowDeleteTarget($args) {
			$row = $args['row'];
			$table = $args['table'];

			if($table === 'Entry') {
				if(!strlen($row['entryid'])) {
					return ['error'=>'Delete needs the Entry_id of the entry to delete.'];
				}

				if(count($row['entryfields']) || count($row['children'])) {
					return ['error'=>'A row that deletes an entry may name nothing else about it.'];
				}

				return ['what'=>'entry'];
			}

			$variable_name = $this->ImportTables()[$table]['variablename'];
			$records = isset($row['children'][$variable_name]) ? $row['children'][$variable_name] : [];

			if(!count($records) || !strlen(reset($records)['id'])) {
				return ['error'=>'Delete needs the ' . $table . '_id of the ' . $table . ' to delete.'];
			}

			if(count($records) > 1 || count(reset($records)['fields']) || count($row['entryfields'])) {
				return ['error'=>'A row that deletes a ' . $table . ' may name only its ' . $table . '_id.'];
			}

			return ['what'=>'child', 'variablename'=>$variable_name, 'id'=>reset($records)['id']];
		}

			/*
				The rows, gathered into entries.  $args['lookup'] answers which
				entry owns a child record -- the ORM's question, passed in so
				the gathering can be tested without a database.
			*/

		public function PlanImport($args) {
			$rows = $args['rows'];
			$table = $args['table'];
			$lookup = $args['lookup'];

			$groups = [];
			$identifiers = [];

			foreach($rows as $row_index => $row) {
				if(count($row['errors'])) {
					continue;
				}

				if($row['delete']) {
					$target = $this->ImportRowDeleteTarget(['row'=>$row, 'table'=>$table]);

					if(isset($target['error'])) {
						$rows[$row_index]['errors'][] = $target['error'];
						continue;
					}

					$rows[$row_index]['deletes'] = $target;
				}

				$entryid = $row['entryid'];

					//  A child record's id names its entry, when the row does not.

				foreach($row['children'] as $variable_name => $records) {
					foreach($records as $record) {
						if(!strlen($record['id'])) {
							continue;
						}

						$owner = (int)$lookup(['type'=>$record['table'], 'id'=>$record['id']]);

						if(!$owner) {
							$rows[$row_index]['errors'][] = 'There is no ' . $record['table'] . ' with id ' . $record['id'] . '.';
							continue 3;
						}

						if(strlen($entryid) && (int)$entryid !== $owner) {
							$rows[$row_index]['errors'][] = $record['table'] . ' ' . $record['id'] . ' belongs to Entry ' . $owner . ', not Entry ' . $entryid . '.';
							continue 3;
						}

						$entryid = (string)$owner;
					}
				}

				if(strlen($entryid)) {
					$key = 'id:' . (int)$entryid;
				} elseif(strlen($row['identifier'])) {
					$key = isset($identifiers[$row['identifier']]) ? $identifiers[$row['identifier']] : 'identifier:' . $row['identifier'];
				} elseif(isset($row['entryfields']['Title'])) {
					$key = 'title:' . $this->ImportParentKey(['row'=>$row]) . ':' . $row['entryfields']['Title'];
				} else {
					$rows[$row_index]['errors'][] = 'Nothing says which entry this row is: give an Entry_id, an Entry_Identifier, or a new entry\'s Entry_Title.';
					continue;
				}

				if(strlen($row['identifier'])) {
					if(isset($identifiers[$row['identifier']]) && $identifiers[$row['identifier']] !== $key) {
						$rows[$row_index]['errors'][] = 'Entry_Identifier ' . $row['identifier'] . ' already names another entry in this file.';
						continue;
					}

					$identifiers[$row['identifier']] = $key;
				}

				if(!isset($groups[$key])) {
					$groups[$key] = [
						'key'=>$key,
						'entryid'=>strlen($entryid) ? (int)$entryid : 0,
						'identifier'=>$row['identifier'],
						'rows'=>[],
						'entryfields'=>[],
						'entryguards'=>[],
						'options'=>[],
						'parent'=>NULL,
						'delete'=>FALSE,
					];
				}

				$conflict = $this->MergeImportRow(['group'=>$groups[$key], 'row'=>$rows[$row_index], 'rows'=>$rows]);

				if(isset($conflict['error'])) {
					$rows[$row_index]['errors'][] = $conflict['error'];
					continue;
				}

				$groups[$key] = $conflict['group'];
				$groups[$key]['rows'][] = $row_index;
			}

				//  An identifier first seen on a later row still finds the entry made by an earlier one.

			foreach($groups as $key => $group) {
				$groups[$key]['action'] = $group['delete'] ? 'delete' : ($group['entryid'] ? 'update' : 'create');

				if($groups[$key]['action'] === 'delete' && count($group['rows']) > 1) {
					foreach($group['rows'] as $row_index) {
						if(empty($rows[$row_index]['deletes'])) {
							$rows[$row_index]['errors'][] = 'Another row deletes this entry, so nothing else may be done to it.';
						}
					}
				}
			}

			return [
				'rows'=>$rows,
				'groups'=>$groups,
			];
		}

		public function ImportParentKey($args) {
			$row = $args['row'];

			if(strlen($row['parentidentifier'])) {
				return 'identifier:' . $row['parentidentifier'];
			}

			if(strlen($row['parentid'])) {
				return 'id:' . (int)$row['parentid'];
			}

			if(strlen($row['parentpath'])) {
				return 'path:' . implode('/', $this->ImportPathCodes(['path'=>$row['parentpath']]));
			}

			return 'root';
		}

		public function ImportPathCodes($args) {
			return array_values(array_filter(explode('/', trim((string)$args['path'], '/')), 'strlen'));
		}

			/*
				A row's say about its entry, added to what the entry's earlier
				rows said.  Returns the group, or the disagreement.
			*/

		public function MergeImportRow($args) {
			$group = $args['group'];
			$row = $args['row'];
			$rows = $args['rows'];

			$first_line = count($group['rows']) ? $rows[$group['rows'][0]]['line'] : NULL;

			foreach(['entryfields', 'entryguards', 'options'] as $part) {
				foreach($row[$part] as $parameter => $value) {
					if(isset($group[$part][$parameter]) && $group[$part][$parameter] !== $value) {
						$label = ($part === 'options') ? 'Option_' . $parameter : (($part === 'entryguards') ? 'Current_Entry_' : 'Entry_') . $parameter;

						return ['error'=>$label . ' is "' . $value . '" here and "' . $group[$part][$parameter] . '" on line ' . $first_line . ', for the same entry.'];
					}

					$group[$part][$parameter] = $value;
				}
			}

			$parent = NULL;

			if(strlen($row['parentidentifier']) || strlen($row['parentid']) || strlen($row['parentpath'])) {
				$parent = $this->ImportParentKey(['row'=>$row]);
			}

			if($parent !== NULL) {
				if($group['entryid']) {
					return ['error'=>'An existing entry is not moved by an import; leave its parent out.'];
				}

				if($group['parent'] !== NULL && $group['parent'] !== $parent) {
					return ['error'=>'This row files the entry under a different parent from line ' . $first_line . '.'];
				}

				$group['parent'] = $parent;
			}

			if(!empty($row['deletes']) && $row['deletes']['what'] === 'entry') {
				$group['delete'] = TRUE;
			}

			return ['group'=>$group];
		}

			/*
				The order to run the entries in: a new entry filed under
				another new entry of this file waits for it.  An entry whose
				parent never comes is returned with the reason.
			*/

		public function OrderImportGroups($args) {
			$groups = $args['groups'];

			$ordered = [];
			$placed = [];
			$waiting = array_keys($groups);

			while(count($waiting)) {
				$progress = FALSE;

				foreach($waiting as $position => $key) {
					$parent = $groups[$key]['parent'];

					if($parent !== NULL && strpos($parent, 'identifier:') === 0 && !isset($placed[$parent])) {
						$parent_key = isset($groups[$parent]) ? $parent : NULL;

						foreach($groups as $candidate_key => $candidate) {
							if('identifier:' . $candidate['identifier'] === $parent && strlen($candidate['identifier'])) {
								$parent_key = $candidate_key;
							}
						}

						if($parent_key === NULL) {
							$groups[$key]['unplaceable'] = 'No row of this file has the Entry_Identifier ' . substr($parent, strlen('identifier:')) . ' to file it under.';
						} elseif(!isset($placed['done:' . $parent_key])) {
							continue;
						}
					}

					$ordered[] = $key;
					$placed['done:' . $key] = TRUE;

					if(strlen($groups[$key]['identifier'])) {
						$placed['identifier:' . $groups[$key]['identifier']] = TRUE;
					}

					unset($waiting[$position]);
					$progress = TRUE;
				}

				if(!$progress) {
					foreach($waiting as $key) {
						$groups[$key]['unplaceable'] = 'Its parent is filed under it, somewhere in this file: a loop.';
						$ordered[] = $key;
					}

					break;
				}
			}

			return [
				'order'=>$ordered,
				'groups'=>$groups,
			];
		}
	}

?>
