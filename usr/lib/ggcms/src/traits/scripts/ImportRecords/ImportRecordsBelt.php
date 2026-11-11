<?php

	/*
		Each entry of an import, put on the belt.

		An entry rides on a rider: a fresh importerrow script, built the way
		the format builds any script, with a Query of its own made from the
		entry's post exactly as a browser's post is made -- the same UTF-8
		cleansing, the same trimming -- and the handler's desired_action set
		to the verb for the length of the ride, since three stages of the
		belt read it there.  Nothing a rider does survives into the next one
		except what it wrote.

		A new entry is a Save beneath its parent.

		An existing entry is read first, the way the Edit form reads it, and
		its records posted back with the file's changes laid over them --
		so a field the file does not mention is posted as it is, and stays.
		Before that update is written it is ridden once with the belt
		stopped at Prepare, and every change the Backup stage would file is
		looked at: a change to a table the file never named means the
		records did not come back as they went out, and the entry is
		refused rather than damaged.  Only then is it ridden for real, and
		Backup files every old value in RecordChange.

		A delete is DeleteEntryWithChildRecords(), or for a child record an
		update without it.
	*/

	trait ImportRecordsBelt {
		public $import_registry;
		public $import_page_cache_report;

			// Riders
			// -----------------------------------------------

		public function ImportRider($args) {
			$action = $args['action'];
			$object_list = array_values($args['objectlist']);
			$post = $args['post'];
			$files = isset($args['files']) ? $args['files'] : [];

			$saved = [
				'post'=>$_POST,
				'get'=>$_GET,
				'request'=>$_REQUEST,
				'files'=>$_FILES,
				'action'=>$this->handler->desired_action,
			];

			$_POST = $post;
			$_POST['action'] = $action;
			$_GET = ['action'=>$action];
			$_REQUEST = array_merge($_GET, $_POST);
			$_FILES = $files;

			$query = new Query(['handler'=>$this->handler]);

			$constructor_args = $this->import_constructor_args;
			$constructor_args['queryobject'] = $query;
			$constructor_args['desiredaction'] = $action;
			$constructor_args['objectlist'] = $object_list;
			$constructor_args['objectcode'] = count($object_list) ? $object_list[count($object_list) - 1] : '';
			$constructor_args['objectparent'] = count($object_list) > 1 ? $object_list[count($object_list) - 2] : '';

			$this->handler->desired_action = $action;

			$rider = new importerrow($constructor_args);
			$rider->entry_modification_stop_before_save = !empty($args['stop']);

			$verb = $args['verb'];

			try {
				$rider->ride_results = $verb ? $rider->$verb() : TRUE;
			} finally {
				$_POST = $saved['post'];
				$_GET = $saved['get'];
				$_REQUEST = $saved['request'];
				$_FILES = $saved['files'];
				$this->handler->desired_action = $saved['action'];
			}

			return $rider;
		}

			/*
				An existing entry as the Edit form has it: its path, its own
				fields and its records, events split into date and time and a
				date before 1000 written with its minus.
			*/

		public function ImportReadEntry($args) {
			$entryid = (int)$args['entryid'];

			$path = $this->orm->GetEntryCodePath(['entryid'=>$entryid, 'masterid'=>(int)$this->master_record['id']]);

			if($path === NULL || !count($path)) {
				return ['error'=>'Entry ' . $entryid . ' is not on this site, or cannot be reached by a path.'];
			}

			$reader = $this->ImportRider(['action'=>'Edit', 'objectlist'=>$path, 'post'=>[], 'verb'=>'ImportReadForEdit']);

			if(!$reader->ride_results || !$reader->entry || (int)$reader->entry['id'] !== $entryid) {
				return ['error'=>'Entry ' . $entryid . ' could not be read at /' . implode('/', $path) . '/.'];
			}

			$records = [];

			foreach(array_keys($this->RecordQueryDefinitions()) as $variable_name) {
				$records[$variable_name] = is_array($reader->$variable_name) ? array_values($reader->$variable_name) : [];
			}

			return [
				'path'=>$path,
				'entry'=>$reader->entry,
				'records'=>$records,
			];
		}

			// The Entries
			// -----------------------------------------------

		public function RunImportGroups($args) {
			$plan = $args['plan'];
			$apply = $args['apply'];
			$limit = $args['limit'];

			$this->import_registry = [];

			$ordered = $this->OrderImportGroups(['groups'=>$plan['groups']]);
			$rows = $plan['rows'];
			$results = [];
			$attempted_rows = 0;
			$wrote = FALSE;

			foreach($ordered['order'] as $key) {
				$group = $ordered['groups'][$key];

				$live_rows = array_values(array_filter($group['rows'], function($row_index) use ($rows) { return !count($rows[$row_index]['errors']); }));

				if(!count($live_rows)) {
					continue;
				}

				if($limit && $attempted_rows >= $limit) {
					foreach($live_rows as $row_index) {
						$results[$row_index] = ['outcome'=>'Not attempted', 'details'=>['Past the limit of ' . $limit . ' row(s).']];
					}
					continue;
				}

				$attempted_rows += count($live_rows);
				$group['rows'] = $live_rows;

				if(isset($group['unplaceable'])) {
					$outcome = ['rows'=>array_fill_keys($live_rows, ['outcome'=>'Skipped', 'details'=>[$group['unplaceable']]])];
				} elseif($group['action'] === 'create') {
					$outcome = $this->ImportCreateGroup(['group'=>$group, 'rows'=>$rows, 'apply'=>$apply]);
				} elseif($group['action'] === 'delete') {
					$outcome = $this->ImportDeleteGroup(['group'=>$group, 'rows'=>$rows, 'apply'=>$apply]);
				} else {
					$outcome = $this->ImportUpdateGroup(['group'=>$group, 'rows'=>$rows, 'apply'=>$apply]);
				}

				foreach($outcome['rows'] as $row_index => $result) {
					$results[$row_index] = $result;
				}

				if(!empty($outcome['wrote'])) {
					$wrote = TRUE;

						//  So the next entry's lookups see this one: a parent made a row ago is a parent now.

					if($this->db_access_object->db_file_cache) {
						$this->db_access_object->FlushRowCacheNow();
					}
				}
			}

			if($apply && $wrote) {
				$this->import_page_cache_report = $this->FlushPageCacheAndReport();
			}

			return $results;
		}

			/*
				Where a new entry goes, as the path its Save is posted to.
				A parent made earlier in this run is found in the registry; in a
				preview it was never made, so the entry is checked at the top of
				the tree instead, and says so.
			*/

		public function ImportParentPath($args) {
			$parent = $args['parent'];

			if($parent === NULL || $parent === 'root') {
				return ['path'=>[]];
			}

			if(strpos($parent, 'path:') === 0) {
				return ['path'=>$this->ImportPathCodes(['path'=>substr($parent, strlen('path:'))])];
			}

			if(strpos($parent, 'id:') === 0) {
				$path = $this->orm->GetEntryCodePath(['entryid'=>(int)substr($parent, 3), 'masterid'=>(int)$this->master_record['id']]);

				if($path === NULL) {
					return ['error'=>'There is no Entry ' . substr($parent, 3) . ' to file it under.'];
				}

				return ['path'=>$path];
			}

			$identifier = substr($parent, strlen('identifier:'));

			if(isset($this->import_registry[$identifier])) {
				$registered = $this->import_registry[$identifier];

				if($registered['path'] === NULL) {
					return ['path'=>[], 'note'=>'Its parent, ' . $identifier . ', is new, so it was checked as if filed at the top of the tree.'];
				}

				return ['path'=>$registered['path']];
			}

			return ['error'=>'Its parent, ' . $identifier . ', was not made.'];
		}

			// Create
			// -----------------------------------------------

		public function ImportCreateGroup($args) {
			$group = $args['group'];
			$rows = $args['rows'];
			$apply = $args['apply'];

			$parent = $this->ImportParentPath(['parent'=>$group['parent']]);

			if(isset($parent['error'])) {
				return ['rows'=>array_fill_keys($group['rows'], ['outcome'=>'Skipped', 'details'=>[$parent['error']]])];
			}

			$records = $this->ImportGroupNewRecords(['group'=>$group, 'rows'=>$rows]);

			$post = $this->ImportBlankPost();

			foreach($group['entryfields'] as $parameter => $value) {
				$post[$parameter] = $value;
			}

			if(!isset($group['entryfields']['Publish'])) {
				$post['Publish'] = '1';		# the Add form ticks it for a new entry
			}

			foreach($group['options'] as $parameter => $value) {
				$post[$parameter] = $this->ImportTruthy(['value'=>$value]) ? '1' : '';
			}

			$post = $this->ImportAppendRecords(['post'=>$post, 'records'=>$records, 'new'=>TRUE]);

			$files = $this->ImportImageFiles(['post'=>$post, 'records'=>$records, 'existing'=>0]);

			if(isset($files['error'])) {
				return ['rows'=>array_fill_keys($group['rows'], ['outcome'=>'Error', 'details'=>[$files['error']]])];
			}

			$duplicate = $this->ImportDuplicateTitle(['group'=>$group, 'path'=>$parent['path']]);

			if($duplicate) {
					/*
						The twin answers for this row's identifier, so the rows
						filed beneath it are filed beneath the twin -- where they
						are skipped in turn if they are already there.  Running a
						file again finishes what a first run left undone.
					*/

				if(strlen($group['identifier']) && isset($duplicate['twin'])) {
					$this->import_registry[$group['identifier']] = [
						'id'=>(int)$duplicate['twin']['id'],
						'path'=>array_merge($parent['path'], [$duplicate['twin']['Code']]),
					];
				}

				return ['rows'=>array_fill_keys($group['rows'], ['outcome'=>'Skipped', 'details'=>[$duplicate['message']]])];
			}

			$rider = $this->ImportRider([
				'action'=>'Save',
				'objectlist'=>$parent['path'],
				'post'=>$post,
				'files'=>$files['files'],
				'verb'=>'SaveEntry',
				'stop'=>!$apply,
			]);

			$failure = $this->ImportRiderFailure(['rider'=>$rider]);

			if($failure) {
				return ['rows'=>array_fill_keys($group['rows'], $failure)];
			}

			$entryid = $apply ? (int)$rider->entry['id'] : 0;

			if(strlen($group['identifier'])) {
				$this->import_registry[$group['identifier']] = [
					'id'=>$entryid,
					'path'=>$apply ? array_merge($parent['path'], [$rider->entry['Code']]) : NULL,
				];
			}

			$where = count($parent['path']) ? '/' . implode('/', $parent['path']) . '/' : 'the top of the tree';
			$name = $apply ? 'Entry ' . $entryid . ' at ' . $where . $rider->entry['Code'] . '/' : 'an entry under ' . $where;

			$results = [];
			$first = TRUE;

			foreach($group['rows'] as $row_index) {
				$details = [];

				if($first) {
					$details[] = ($apply ? 'Made ' : 'Would make ') . $name . (isset($group['entryfields']['Title']) ? ', titled "' . $group['entryfields']['Title'] . '"' : '') . ', code ' . $rider->entry['Code'] . '.';

					if(isset($parent['note'])) {
						$details[] = $parent['note'];
					}
				}

				foreach($this->ImportRowRecordDescriptions(['row'=>$rows[$row_index]]) as $description) {
					$details[] = ($apply ? 'Added ' : 'Would add ') . $description . '.';
				}

				$results[$row_index] = ['outcome'=>'New record', 'details'=>$details, 'entryid'=>$entryid];
				$first = FALSE;
			}

			return ['rows'=>$results, 'wrote'=>$apply];
		}

			/*
				A new entry titled like one its parent already has is very
				likely the same entry made twice -- a second run of the same
				file, most often -- so it is skipped and its twin named.
				Allow it with Option_allow-duplicate-title.
			*/

		public function ImportDuplicateTitle($args) {
			$group = $args['group'];
			$path = $args['path'];

			if(!isset($group['entryfields']['Title']) || $this->ImportTruthy(['value'=>isset($group['options']['allow-duplicate-title']) ? $group['options']['allow-duplicate-title'] : ''])) {
				return NULL;
			}

			if(count($path)) {
				$tree = $this->orm->GetRecordTree(['codelist'=>$path, 'availabilitylimit'=>1]);

				if(!is_array($tree) || count($tree) !== count($path)) {
					return ['message'=>'There is no entry at /' . implode('/', $path) . '/ to file it under.'];
				}

				$parentid = (int)$tree[count($tree) - 1]['id'];
			} else {
				$parentid = (int)$this->master_record['id'];
			}

			$twins = $this->orm->GetChildEntriesTitled(['parentid'=>$parentid, 'title'=>$group['entryfields']['Title']]);

			if(!count($twins)) {
				return NULL;
			}

			return [
				'message'=>'Its parent already has an entry titled "' . $group['entryfields']['Title'] . '" (Entry ' . $twins[0]['id'] . '); give that Entry_id to change it, or Option_allow-duplicate-title=1 to make a second.',
				'twin'=>$twins[0],
			];
		}

			// Update
			// -----------------------------------------------

		public function ImportUpdateGroup($args) {
			$group = $args['group'];
			$rows = $args['rows'];
			$apply = $args['apply'];

			$current = $this->ImportReadEntry(['entryid'=>$group['entryid']]);

			if(isset($current['error'])) {
				return ['rows'=>array_fill_keys($group['rows'], ['outcome'=>'Skipped', 'details'=>[$current['error']]])];
			}

			$post = $this->RecordsAsQuery(['entry'=>$current['entry'], 'records'=>$current['records']]);
			$existing_images = count($current['records']['image']);

			$changes = $this->ImportLayChanges(['group'=>$group, 'rows'=>$rows, 'current'=>$current, 'post'=>$post]);

			if(isset($changes['error'])) {
				return ['rows'=>$changes['rows']];
			}

			if(!$changes['changed']) {
				return ['rows'=>$changes['rows']];
			}

			$post = $changes['post'];

			$files = $this->ImportImageFiles(['post'=>$post, 'records'=>$changes['newrecords'], 'existing'=>$changes['keptimages']]);

			if(isset($files['error'])) {
				return ['rows'=>array_fill_keys($group['rows'], ['outcome'=>'Error', 'details'=>[$files['error']]])];
			}

				//  The check ride: stopped at Prepare, and every change Backup would file looked at.

			$check = $this->ImportRider([
				'action'=>'Update',
				'objectlist'=>$current['path'],
				'post'=>$post,
				'files'=>$files['files'],
				'verb'=>'UpdateEntry',
				'stop'=>TRUE,
			]);

			$failure = $this->ImportRiderFailure(['rider'=>$check]);

			if($failure) {
				return ['rows'=>array_fill_keys($group['rows'], $failure)];
			}

			$strays = $this->ImportStrayChanges(['rider'=>$check, 'touched'=>$changes['touched']]);

			if(count($strays)) {
				return ['rows'=>array_fill_keys($group['rows'], ['outcome'=>'Error', 'details'=>array_merge(['Refused: the update would also change records this file does not name, so they did not come back as they were read.'], $strays)])];
			}

			if($apply) {
				$rider = $this->ImportRider([
					'action'=>'Update',
					'objectlist'=>$current['path'],
					'post'=>$post,
					'files'=>$files['files'],
					'verb'=>'UpdateEntry',
					'stop'=>FALSE,
				]);

				$failure = $this->ImportRiderFailure(['rider'=>$rider]);

				if($failure) {
					return ['rows'=>array_fill_keys($group['rows'], $failure), 'wrote'=>TRUE];
				}
			}

			$results = [];

			foreach($changes['rows'] as $row_index => $result) {
				if(!$apply) {
					$result['details'] = array_map(function($detail) { return $this->ImportPreviewWording(['detail'=>$detail]); }, $result['details']);
				}

				$results[$row_index] = $result;
			}

			if(strlen($group['identifier'])) {
				$this->import_registry[$group['identifier']] = ['id'=>$group['entryid'], 'path'=>$current['path']];
			}

			return ['rows'=>$results, 'wrote'=>$apply];
		}

			/*
				The file's say laid over the entry's records.  Entry fields are
				replaced; a child record named by its id is changed or, with
				Delete, removed; a child record without an id is added, unless
				the entry already has one exactly like it.  A Current_ column
				that no longer matches stops the whole entry, since it was
				edited after the file was written and the edit is newer.
			*/

		public function ImportLayChanges($args) {
			$group = $args['group'];
			$rows = $args['rows'];
			$current = $args['current'];
			$post = $args['post'];

			$entry = $current['entry'];
			$records = $current['records'];

			$touched = [];
			$results = [];
			$changed = FALSE;
			$new_records = [];
			$removed = [];

			foreach($group['entryguards'] as $parameter => $expected) {
				$live = isset($entry[$parameter]) ? (string)$entry[$parameter] : '';

				if($live !== $expected) {
					return $this->ImportGroupError(['group'=>$group, 'outcome'=>'Skipped', 'detail'=>'Entry_' . $parameter . ' is now "' . $live . '", not the "' . $expected . '" this file expects: it was changed after the file was made.']);
				}
			}

			foreach($group['rows'] as $row_index) {
				$row = $rows[$row_index];
				$details = [];
				$outcome = 'Unchanged';

				foreach($row['entryfields'] as $parameter => $value) {
					$live = isset($entry[$parameter]) ? (string)$entry[$parameter] : '';

					if($live === $value) {
						continue;
					}

					$post[$parameter] = $value;
					$touched['Entry'] = TRUE;
					$changed = TRUE;
					$outcome = 'Updated old record';
					$details[] = 'Changed Entry ' . $group['entryid'] . ' ' . $parameter . ' from "' . $live . '" to "' . $value . '".';
				}

				foreach($row['options'] as $parameter => $value) {
					$post[$parameter] = $this->ImportTruthy(['value'=>$value]) ? '1' : '';
				}

				foreach($row['children'] as $variable_name => $row_records) {
					$definition = $this->RecordQueryDefinitions()[$variable_name];
					$formal = $definition['formal'];

					foreach($row_records as $record) {
						if(strlen($record['id'])) {
							$index = $this->ImportRecordIndex(['records'=>$records[$variable_name], 'id'=>$record['id']]);

							if($index === NULL) {
								return $this->ImportGroupError(['group'=>$group, 'outcome'=>'Error', 'detail'=>$formal . ' ' . $record['id'] . ' is not one of Entry ' . $group['entryid'] . '\'s records.']);
							}

							foreach($record['guards'] as $parameter => $expected) {
								$live = (string)$records[$variable_name][$index][$this->ImportFieldName(['variablename'=>$variable_name, 'parameter'=>$parameter])];

								if($live !== $expected) {
									return $this->ImportGroupError(['group'=>$group, 'outcome'=>'Skipped', 'detail'=>$formal . ' ' . $record['id'] . ' ' . $this->ImportFieldName(['variablename'=>$variable_name, 'parameter'=>$parameter]) . ' is now "' . $live . '", not the "' . $expected . '" this file expects.']);
								}
							}

							if(!empty($row['deletes']) && $row['deletes']['what'] === 'child') {
								$removed[$variable_name][$index] = TRUE;
								$touched[$formal] = TRUE;
								$changed = TRUE;
								$outcome = 'Deleted old record';
								$details[] = 'Deleted ' . $formal . ' ' . $record['id'] . ' from Entry ' . $group['entryid'] . '.';
								continue;
							}

							foreach($record['fields'] as $parameter => $value) {
								$field = $this->ImportFieldName(['variablename'=>$variable_name, 'parameter'=>$parameter]);
								$live = (string)$records[$variable_name][$index][$field];

								if($live === $value) {
									continue;
								}

								$post[$parameter][$index] = $value;
								$touched[$formal] = TRUE;
								$changed = TRUE;

								if($outcome === 'Unchanged') {
									$outcome = 'Updated old record';
								}

								$details[] = 'Changed ' . $formal . ' ' . $record['id'] . ' ' . $field . ' from "' . $live . '" to "' . $value . '".';
							}

							foreach($record['options'] as $parameter => $value) {
								$post[$parameter][$index] = $this->ImportTruthy(['value'=>$value]) ? '1' : '';
								$touched[$formal] = TRUE;
								$changed = TRUE;
							}

							continue;
						}

						if($this->ImportRecordAlreadyThere(['records'=>$records[$variable_name], 'record'=>$record, 'variablename'=>$variable_name])) {
							$details[] = 'Entry ' . $group['entryid'] . ' already has ' . $this->ImportRecordDescription(['variablename'=>$variable_name, 'record'=>$record]) . '; not added again.';
							continue;
						}

						$new_records[$variable_name][] = $record;
						$touched[$formal] = TRUE;
						$changed = TRUE;

						if($outcome === 'Unchanged' || $outcome === 'Updated old record') {
							$outcome = ($outcome === 'Unchanged') ? 'New record' : $outcome;
						}

						$details[] = 'Added ' . $this->ImportRecordDescription(['variablename'=>$variable_name, 'record'=>$record]) . ' to Entry ' . $group['entryid'] . '.';
					}
				}

				if(!count($details)) {
					$details[] = 'Entry ' . $group['entryid'] . ' already says all of this.';
				}

				$results[$row_index] = ['outcome'=>$outcome, 'details'=>$details, 'entryid'=>$group['entryid']];
			}

				//  Removals last, from the end, so the positions above stayed true while they were used.

			foreach($removed as $variable_name => $indexes) {
				krsort($indexes);

				foreach(array_keys($indexes) as $index) {
					foreach($this->ImportPostedParameters(['variablename'=>$variable_name]) as $parameter) {
						if(isset($post[$parameter]) && is_array($post[$parameter])) {
							array_splice($post[$parameter], $index, 1);
						}
					}
				}
			}

			$post = $this->ImportAppendRecords(['post'=>$post, 'records'=>$new_records, 'new'=>TRUE]);

			return [
				'post'=>$post,
				'rows'=>$results,
				'changed'=>$changed,
				'touched'=>$touched,
				'newrecords'=>$new_records,
				'keptimages'=>count($records['image']) - (isset($removed['image']) ? count($removed['image']) : 0),
			];
		}

		public function ImportGroupError($args) {
			return [
				'error'=>TRUE,
				'rows'=>array_fill_keys($args['group']['rows'], ['outcome'=>$args['outcome'], 'details'=>[$args['detail']]]),
			];
		}

		public function ImportRecordIndex($args) {
			foreach($args['records'] as $index => $record) {
				if((string)$record['id'] === (string)(int)$args['id']) {
					return $index;
				}
			}

			return NULL;
		}

			//  The same record, by every field the file gives: a tag already tagged is not tagged twice.

		public function ImportRecordAlreadyThere($args) {
			foreach($args['records'] as $existing) {
				$same = TRUE;

				foreach($args['record']['fields'] as $parameter => $value) {
					$field = $this->ImportFieldName(['variablename'=>$args['variablename'], 'parameter'=>$parameter]);

					if((string)$existing[$field] !== $value) {
						$same = FALSE;
						break;
					}
				}

				if($same && count($args['record']['fields'])) {
					return TRUE;
				}
			}

			return FALSE;
		}

			/*
				Every change the Backup stage would file for this ride, as
				DetectChangedRecords() finds them, kept to the tables the file
				did not name.  Those are the strays.
			*/

		public function ImportStrayChanges($args) {
			$rider = $args['rider'];
			$touched = $args['touched'];

			$strays = [];
			$primary_fields = $rider->GetRecordsPrimaryFields();

			foreach($rider->GetRecordTypes() as $formal => $variable_name) {
				if(isset($touched[$formal]) || !isset($primary_fields[$formal])) {
					continue;
				}

				$old_records = ($variable_name === 'entry') ? [$rider->entry_unset] : $rider->entry_unset[$variable_name];

				$changes = $rider->DetectChangedRecords([
					'recordtype'=>$variable_name,
					'records'=>$old_records,
					'fields'=>$primary_fields[$formal],
				]);

				foreach($changes as $change) {
					$field = isset($change['conflictingfield']) ? $change['conflictingfield'] : '';
					$strays[] = $formal . ' ' . $change['id'] . ($field ? ' ' . $field . ' "' . $change[$field] . '"' : '') . ' would ' . ($field ? 'change' : 'be deleted') . '.';
				}
			}

			return $strays;
		}

			// Delete
			// -----------------------------------------------

		public function ImportDeleteGroup($args) {
			$group = $args['group'];
			$apply = $args['apply'];

			$current = $this->ImportReadEntry(['entryid'=>$group['entryid']]);

			if(isset($current['error'])) {
				return ['rows'=>array_fill_keys($group['rows'], ['outcome'=>'Skipped', 'details'=>[$current['error']]])];
			}

			$name = 'Entry ' . $group['entryid'] . ' at /' . implode('/', $current['path']) . '/, "' . $current['entry']['Title'] . '"';

			if(!$apply) {
				return ['rows'=>array_fill_keys($group['rows'], ['outcome'=>'Deleted old record', 'details'=>['Would delete ' . $name . ', with its child records and its assignment, every one filed whole in RecordChange first.'], 'entryid'=>$group['entryid']])];
			}

			$rider = $this->ImportRider([
				'action'=>'Delete',
				'objectlist'=>$current['path'],
				'post'=>[],
				'verb'=>'DeleteEntryWithChildRecords',
			]);

			if(!$rider->ride_results) {
				return ['rows'=>array_fill_keys($group['rows'], ['outcome'=>'Error', 'details'=>['Not deleted: ' . $name . ' could not be reached, or may not be deleted by this account.']])];
			}

			if(!$rider->saveattemptresults) {
				return ['rows'=>array_fill_keys($group['rows'], ['outcome'=>'Error', 'details'=>[$rider->save_status]]), 'wrote'=>TRUE];
			}

			return ['rows'=>array_fill_keys($group['rows'], ['outcome'=>'Deleted old record', 'details'=>['Deleted ' . $name . ', with its child records and its assignment, every one filed whole in RecordChange first.'], 'entryid'=>$group['entryid']]), 'wrote'=>TRUE];
		}

			// The Post
			// -----------------------------------------------

			/*
				A post that names every field and carries no record: what the
				Add form sends with nothing typed in it, less its blank rows,
				which the belt treats as nothing.
			*/

		public function ImportBlankPost() {
			return $this->RecordsAsQuery(['entry'=>[], 'records'=>[]]);
		}

		public function ImportPostedParameters($args) {
			$variable_name = $args['variablename'];
			$parameters = $this->RecordQueryDefinitions()[$variable_name]['objectdefinition'];

			if($variable_name === 'textbody') {
				$parameters = array_merge($parameters, array_keys($this->TextBodyQueryOptions()));
			}

			return $parameters;
		}

			/*
				New child records added to the end of the post, every field of
				their type filled -- blank where the file said nothing -- so the
				positions line up.  A new text body's switches are the Add
				form's for a new entry: URLs stripped, vocabulary Americanized,
				HTML formatting as the site's globals say, unless a
				TextBody_<switch> column says otherwise.
			*/

		public function ImportAppendRecords($args) {
			$post = $args['post'];

			foreach($args['records'] as $variable_name => $records) {
				foreach($records as $record) {
					foreach($this->RecordQueryDefinitions()[$variable_name]['objectdefinition'] as $parameter) {
						if(!isset($post[$parameter]) || !is_array($post[$parameter])) {
							$post[$parameter] = [];
						}

						$post[$parameter][] = isset($record['fields'][$parameter]) ? $record['fields'][$parameter] : '';
					}

					if($variable_name === 'textbody') {
						$defaults = [
							'textbody_StripURLs'=>'1',
							'textbody_AmericanizeVocabulary'=>'1',
							'textbody_HTMLFormatting'=>$this->handler->globals->AddEntryHTMLFormatting() ? '1' : '',
							'textbody_SpellCheckImmune'=>'',
						];

						foreach($defaults as $parameter => $default) {
							if(!isset($post[$parameter]) || !is_array($post[$parameter])) {
								$post[$parameter] = [];
							}

							$value = isset($record['options'][$parameter]) ? ($this->ImportTruthy(['value'=>$record['options'][$parameter]]) ? '1' : '') : $default;
							$post[$parameter][] = $value;
						}
					}
				}
			}

			return $post;
		}

			//  Every new child record a create group's rows carry, in row order.

		public function ImportGroupNewRecords($args) {
			$records = [];

			foreach($args['group']['rows'] as $row_index) {
				foreach($args['rows'][$row_index]['children'] as $variable_name => $row_records) {
					foreach($row_records as $record) {
						$records[$variable_name][] = $record;
					}
				}
			}

			return $records;
		}

			/*
				A ride that stopped short: why, in the belt's own words.
				Validation's complaints are in $rider->errors, one list each.
			*/

		public function ImportRiderFailure($args) {
			$rider = $args['rider'];

			if(!$rider->ride_results) {
				return ['outcome'=>'Error', 'details'=>['The entry could not be reached at its path, or this account may not change it.']];
			}

			if($rider->entry_modification_stopped) {
				return NULL;
			}

			if($rider->saveattemptresults) {
				return NULL;
			}

			$details = [$rider->save_status];

			foreach((array)$rider->errors as $error) {
				$details[] = strip_tags(implode(' ', (array)$error));
			}

			return ['outcome'=>'Error', 'details'=>$details];
		}

			//  "Changed ..." as a preview says it: "Would change ...".

		public function ImportPreviewWording($args) {
			$detail = $args['detail'];
			
			$wordings = [
				'Changed '=>'Would change ',
				'Added '=>'Would add ',
				'Deleted '=>'Would delete ',
			];

			foreach($wordings as $done => $would) {
				if(strpos($detail, $done) === 0) {
					return $would . substr($detail, strlen($done));
				}
			}

			return $detail;
		}

		public function ImportTruthy($args) {
			return in_array(mb_strtolower(trim((string)$args['value'])), ['1', 'y', 'yes', 'true', 'on'], TRUE);
		}

			// Describing
			// -----------------------------------------------

		public function ImportRecordDescription($args) {
			$variable_name = $args['variablename'];
			$record = $args['record'];
			$formal = $this->RecordQueryDefinitions()[$variable_name]['formal'];

			$pieces = [];

			foreach($record['fields'] as $parameter => $value) {
				$shown = mb_strlen($value) > 60 ? mb_substr($value, 0, 57) . '...' : $value;
				$pieces[] = $this->ImportFieldName(['variablename'=>$variable_name, 'parameter'=>$parameter]) . ' "' . $shown . '"';
			}

			return (preg_match('/^[AEIOU]/', $formal) ? 'an ' : 'a ') . $formal . (count($pieces) ? ' (' . implode(', ', $pieces) . ')' : '');
		}

		public function ImportRowRecordDescriptions($args) {
			$descriptions = [];

			foreach($args['row']['children'] as $variable_name => $records) {
				foreach($records as $record) {
					$descriptions[] = $this->ImportRecordDescription(['variablename'=>$variable_name, 'record'=>$record]);
				}
			}

			return $descriptions;
		}
	}

?>
