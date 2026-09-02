<?php

	trait SimpleORM {
		public function SetOrmBasics() {
			$this->SetORM();
			$this->SetMasterRecord();
			$this->SetRecordTree();
			$this->SetEntry();
			
			return TRUE;
		}
		
		public function SetEntry() {
			if(count($this->record_list) !== 0) {
				$this->SetEntryParentTable();
				
				if(count($this->record_list) > 1) {
					$this->parent = $this->record_list[count($this->record_list) - 2];
				}
				$this->entry = $this->GetCurrentEntry(['recordslist'=>$this->record_list]);
			} else {
				$this->parent = $this->master_record;
				$this->parentid = $this->master_record['id'];
				$this->entry = $this->parent;
			}
			
			if($this->entry && $this->entry['OriginalCreationDate']) {
				$this->entry['eventdate'][] = [
					'id'=>-1,
					'Title'=>'Added',
					'EventDateTime'=>$this->entry['OriginalCreationDate'],
				];
				
				if($this->entry['OriginalCreationDate'] !== $this->entry['LastModificationDate']) {
					$this->entry['eventdate'][] = [
						'id'=>-2,
						'Title'=>'Updated',
						'EventDateTime'=>$this->entry['LastModificationDate'],
					];
				}
			}
			
			return TRUE;
		}
		
		public function SetORM() {
			if($this->orm) {
				return TRUE;
			}
			if($this->handler->orm) {
				$this->orm = $this->handler->orm;
				
				return TRUE;
			}
			$this->orm = new ORM([
				'handler'=>$this->handler,
			]);
			
			return TRUE;
		}
		
		public function SetRecordTree() {
			return $this->record_list = $this->orm->GetRecordTree(['codelist'=>$this->object_list, 'availabilitylimit'=>1]);
		}
		
		public function CompactDefinitions() {
			$definitions = $this->entry['definition'];
			if(!$definitions) {
				return [];
			}
			$definitions_count = count($definitions);
			
			if($definitions_count) {
				$definitions_hash = [];
				
				for($i = 0; $i < $definitions_count; $i++) {
					$definition = $definitions[$i];
					
					if(!$definitions_hash[$definition['Term']]) {
						$definitions_hash[$definition['Term']] = [];
					}
					
					$definitions_hash[$definition['Term']][] = $definition['Definition'];
				}
			}
			
			ksort($definitions_hash);
			
			return $this->definitions = $definitions_hash;
		}
		
		public function SetTagCounts() {
			$all_tags = [];
			$tag_counts = [];
			
			if($this->entry || $this->user) {
				if($this->entry['tag'] && $this->parent) {
					$entry_tags = $this->entry['tag'];
					$entry_tags_count = count($entry_tags);
					
					if($entry_tags_count) {
						for($i = 0; $i < $entry_tags_count; $i++) {
							$entry_tag = $entry_tags[$i];
							$all_tags[] = $entry_tag['Tag'];
						}
					}
				}
				
				if($this->entry && $this->entry['association']) {
					$associations = $this->entry['association'];
					$association_count = count($associations);
					
					if($association_count) {
						$parents = [];
						for($i = 0; $i < $association_count; $i++) {
							$association = $associations[$i];
							$entry = $association['entry'];
							$tags = $entry['tag'];
							if($tags) {
								$tags_count = count($tags);
								
								for($k = 0; $k < $tags_count; $k++) {
									$tag = $tags[$k];
									$all_tags[] = $tag['Tag'];
								}
							}
							
							$entry_parents = $entry['parents'];
							if($entry_parents) {
								$entry_parents_count = count($entry_parents);
								
								if($entry_parents_count) {
									$first_parent = $entry_parents[$entry_parents_count - 2];
									
									if(!$first_parent) {
										$first_parent = $entry_parents[$entry_parents_count - 1];
									}
									
									if($parents[$first_parent['id']]) {
										$temp_tags = $parents[$first_parent['id']]['tags'];
										for($j = 0; $j < count($temp_tags); $j++) {
											$association_tag = $temp_tags[$j];
											
											$all_tags[] = $association_tag['Tag'];
										}
									}
								}
							}
						}
					}
				}
				
				$likes_dislikes = $this->likedislikes;
				
				if($likes_dislikes) {
					$likes_dislikes_count = count($likes_dislikes);
					
					if($likes_dislikes_count) {
						for($i = 0; $i < $likes_dislikes_count; $i++) {
							$like_dislike = $likes_dislikes[$i];
							$like_dislike_entry = $like_dislike['entry'];
							$like_dislike_tags = $like_dislike_entry['tag'];
							$like_dislike_tags_count = count($like_dislike_tags);
							
							$like_dislike_parents = $like_dislike_entry['parents'];
							$like_dislike_parents_count = count($like_dislike_parents);
							
							if($like_dislike_parents_count) {
								$like_dislike_first_parent = $like_dislike_parents[$like_dislike_parents_count - 2];
								
								if(!$like_dislike_first_parent) {
									$like_dislike_first_parent = $like_dislike_parents[$like_dislike_parents_count - 1];
								}
								
								if($parents[$like_dislike_first_parent['id']]) {
									$temp_tags = $parents[$like_dislike_first_parent['id']]['tags'];
								
									for($j = 0; $j < count($temp_tags); $j++) {
										$like_dislike_tag = $temp_tags[$j];
										
										$all_tags[] = $like_dislike_tag['Tag'];
									}
								}
							}
						}
					}
				}
				
				$comments = $this->comments;
				
				if($comments) {
					$comments_count = count($comments);
					
					if($comments_count) {
						for($i = 0; $i < $comments_count; $i++) {
							$comment = $comments[$i];
							$comment_entry = $comment['entry'];
							$comment_entry_tags = $comment_entry['tag'];
							
							$comment_parents = $comment_entry['parents'];
							
							if($comment_parents) {
								$comment_parents_count = count($comment_parents);
								
								if($comment_parents_count) {
									$comment_first_parent = $comment_parents[$comment_parents_count - 2];
									
									if(!$comment_first_parent) {
										$comment_first_parent = $comment_parents[$comment_parents_count - 1];
									}
									
									if($parents[$comment_first_parent['id']]) {
										$temp_tags = $parents[$comment_first_parent['id']]['tags'];
									
										for($j = 0; $j < count($temp_tags); $j++) {
											$comment_entry_tag = $temp_tags[$j];
											
											$all_tags[] = $comment_entry_tag['Tag'];
										}
									}
								}
							}
						}
					}
				}
				
				if($this->entry['associated']) {
					$parents_collection = [];
					$associated_entries = $this->entry['associated'];
					$associated_entries_count = count($associated_entries);
					if($associated_entries_count) {
						for($i = 0; $i < $associated_entries_count; $i++) {
							$associated_entry = $associated_entries[$i];
							$first_parent = $associated_entry['entry']['parents'][0];
							if(!$parent_tags[$first_parent['id']]) {
								$parent_tags[$first_parent['id']] = [];
								$parents_collection[$first_parent['id']] = $first_parent;
							}
							$associated_entry_tags = $associated_entry['entry']['tag'];
							if($associated_entry_tags) {
								$associated_entry_tags_count = count($associated_entry_tags);
								
								if($associated_entry_tags_count)
								{
									for($j = 0; $j < $associated_entry_tags_count; $j++)
									{
										$associated_entry_tag = $associated_entry_tags[$j];
					//					print("BT: Eh?" . $associated_entry_tag['Tag']);
										$all_tags[] = $associated_entry_tag['Tag'];
									}
								}
							}
						}
					}
				}
				
				if($this->children) {
					if($this->entry) {
						$children_records = $this->children;
						$children_record_count = count($children_records);
						
						if($children_record_count) {
							for($i = 0; $i < $children_record_count; $i++) {
								$child_record = $children_records[$i];
								$child_tags = $child_record['tag'];
								if($child_tags) {
									$child_tag_count = count($child_tags);
									
									for($j = 0; $j < $child_tag_count; $j++) {
										$child_tag = $child_tags[$j];
										$all_tags[] = $child_tag['Tag'];
									}
								}
							}
						}
						
						$children_random_records = $this->children_random;
						
						if($children_random_records) {
							$children_random_records_count = count($children_random_records);
							
							if($children_random_records_count) {
								for($i = 0; $i < $children_random_records_count; $i++) {
									$random_child = $children_random_records[$i];
									$random_child_tags = $random_child['tag'];
									if($random_child_tags) {
										$random_child_tag_count = count($random_child_tags);
										
										for($j = 0; $j < $random_child_tag_count; $j++) {
											$random_child_tag = $random_child_tags[$j];
											$all_tags[] = $random_child_tag['Tag'];
										}
									}
								}
							}
						}
						
						$tags_random_records = $this->tags_random;
						
						if($tags_random_records) {
							$tags_random_records_count = count($tags_random_records);
							
							for($i = 0; $i < $tags_random_records_count; $i++) {
								$tag_random_record = $tags_random_records[$i];
								$all_tags[] = $tag_random_record['Tag'];
							}
						}
					} else {
						$children_records = $this->children;
						$children_record_count = count($children_records);
						
						if($children_record_count) {
							for($i = 0; $i < $children_record_count; $i++) {
								$child = $children_records[$i];
								$child_tags = $child['tag'];
								$child_tags_count = count($child_tags);
								
								$child_parents = $child['parents'];
								$child_parents_count = count($child_parents);
								
								if($child_parents_count) {
									$child_first_parent = $child_parents[$child_parents_count - 2];
									
									if(!$child_first_parent) {
										$child_first_parent = $child_parents[$child_parents_count - 1];
									}
									
									if($parents[$child_first_parent['id']]) {
										$temp_tags = $parents[$child_first_parent['id']]['tags'];
									
										for($j = 0; $j < count($temp_tags); $j++) {
											$child_tag = $temp_tags[$j];
											
											$all_tags[] = $child_tag['Tag'];
										}
									}
								}
							}
						}
					}
				}
			}
			
			if($this->master_record && $this->children) {
				$children_records = $this->children;
				$children_record_count = count($children_records);
				
				if($children_record_count) {
					for($i = 0; $i < $children_record_count; $i++) {
						$child_record = $children_records[$i];
						$child_tags = $child_record['tag'];
						if($child_tags) {
							$child_tag_count = count($child_tags);
							
							for($j = 0; $j < $child_tag_count; $j++) {
								$child_tag = $child_tags[$j];
								$all_tags[] = $child_tag['Tag'];
							}
							
							$grand_children = $child_record['children'];
							
							if($grand_children) {
								$grand_children_count = count($grand_children);
								
								$grand_children_tag_array = [];
								
								for($j = 0; $j < $grand_children_count; $j++) {
									$grand_child = $grand_children[$j];
									$grand_child_tags = $grand_child['tag'];
									
									foreach($grand_child_tags as $grand_child_tag) {
										$all_tags[] = $grand_child_tag['Tag'];
									}
								}
							}
						}
					}
				}
			}
			
			if($this->comments) {
				$comments = $this->comments;
				$comments_count = count($comments);
				
				if($comments_count > 0) {
					for($i = 0; $i < $comments_count; $i++) {
						$comment = $comments[$i];
						$comment_tags = $comment['entry']['tag'];
						if($comment_tags) {
							$comment_tags_count = count($comment_tags);
							
							for($j = 0; $j < $comment_tags_count; $j++) {
								$tag = $comment_tags[$j];
								
								$all_tags[] = $tag['Tag'];
							}
						}
					}
				}
			}
			
			$all_tags = array_unique($all_tags);
			
			$tag_counts = $this->orm->GetTagCounts(['tags'=>$all_tags]);
			
			return $this->tag_counts = $tag_counts;
		}
		
		public function CheckRecordTree($args) {
		#	return count($this->object_list) == count($this->record_list);
			return $this->orm->GetRecordTree(['codelist'=>$args['recordlist'], 'availabilitylimit'=>$args['availabilitylimit']]);
		}
		
		public function GetCurrentEntry($args) {	
			$records_list = $args['recordslist'];
			$records_count = count($records_list);
			$current_record_index = $records_count - 1;
			return $records_list[$current_record_index];
		}
		
		public function SetEntryParentTable() {
			$record_list_count = count($this->record_list);
			
			if($record_list_count) {
				if(
					($this->desired_action == 'Add') ||
					($this->desired_action == 'Save') ||
					($this->desired_action == 'Select') ||
					($this->desired_action == 'Edit') ||
					($this->desired_action == 'Update')
				) {
					$last_record_index = $record_list_count - 1;
					
					$this->parent = $this->record_list[$last_record_index];
					return $this->parentid = $this->record_list[$last_record_index]['id'];
				}
			}
			
			if($this->master_record && $this->master_record['id']) {
				$this->parent = $this->master_record;
				
				return $this->parentid = $this->master_record['id'];
			}
			
			$this->parent = [
				'id'=>'0',
				'Title'=>'MasterDomainRecord',
				'Subtitle'=>'DomainsOfTheMaster',
				'ListTitle'=>'Master,OfTheDomains',
				'ListTitleSortKey'=>'Master,OfTheDomains',
				'Code'=>'',
				'ChildAdjective'=>'',
				'ChildNoun'=>'',
				'ChildNounPlural'=>'',
			];
			$this->parentid = 0;
			
			return TRUE;
		}
		
		public function SetEntryRecords($args) {
			if($this->desired_action === 'browse' || $this->desired_action === 'browseByTag' || $this->desired_action === 'browseComments' || $this->desired_action === 'browseLikes') {
				$orderby = 'ListTitle,Title';
				$start_index = $this->child_record_start_index;
				$end_index = $this->perpage;
			} elseif($this->object_code && $this->desired_action === 'index') {
				$start_index = 1;
				$end_index = 5;
				$orderby = 'OriginalCreationDate DESC';
			} else {
				$orderby = 'ListTitle,Title';
				$start_index = 0;
				$end_index = 0;
			}
			
			$where = [];
			
			if($this->where) {
				$where = $this->where;
			}
			
			$get_record_entries_args = [
				'startindex'=>$start_index,
				'endindex'=>$end_index,
				'orderby'=>$orderby,
				'where'=>$where,
				'alltext'=>$args['alltext'],
				'noassignment'=>$args['noassignment'],
				'extraselect'=>$this->where['extraselect'],
			];
			
			return $this->entries = $this->orm->GetRecordEntries($get_record_entries_args);
		}
		
		public function SetChildRecords($args) {
			$entry = $this->entry;
			
			if(!$entry || !$entry['id']) {
				$entry = $this->parent;
			}
			
			if(
				$this->desired_action === 'browse' ||
				$this->desired_action === 'browseByTag' ||
				$this->desired_action === 'browseComments' ||
				$this->desired_action === 'browseLikes' ||
				$this->desired_action === 'browseAssociated' ||
				$this->handler->script_classname === 'image'
			) {
				$orderby = 'ListTitleSortKey,Entry1.ListTitle,Entry1.Title';
				$start_index = $this->child_record_start_index;
				$end_index = $this->perpage;
			} elseif($this->object_code && $this->desired_action === 'index') {
				$start_index = 1;
				$end_index = 5;
				$orderby = 'OriginalCreationDate DESC';
			} elseif($this->object_code && $this->script_name === 'about') {
				$start_index = 1;
				$end_index = 7;
				$orderby = 'OriginalCreationDate DESC';
			} else {
				$orderby = 'ListTitleSortKey,ListTitle,Title';
				$start_index = 0;
				$end_index = 0;
			}
			
			$publish_sql = ' Entry1.Publish != 0 ';
			
			if($this->entry['Publish'] === 0 && $this->canUserAccess()) {
					# It's unpublished, and user is allowed to access, so show all chirren's.
			} else {
				if($this->where) {
					$this->where['sql'] .= ' AND ' . $publish_sql;
				} else {
					$this->where = [
						'sql'=>' WHERE ' . $publish_sql,
						'bind'=>'',
						'value'=>[],
					];
				}
			}
			
			$get_record_children_args = [
				'entry'=>$entry,
				'startindex'=>$start_index,
				'endindex'=>$end_index,
				'orderby'=>$orderby,
				'where'=>$this->where,
				'alltext'=>$args['alltext'],
				'noassignment'=>$args['noassignment'],
				'extraselect'=>$this->where['extraselect'],
			];
			
			if(
				$this->handler->desired_action === 'browseAssociated'
			) {
				$get_record_children_args['ignore_parent'] = $this->Param('ignore_parent');
				$new_children = $this->orm->GetRecordAssociated($get_record_children_args);
			} elseif(
				$this->handler->script_classname === 'image'
			) {
				if($this->master_record['id'] === $this->entry['id']) {
					unset($get_record_children_args['entry']);
				}
				$new_children = $this->orm->GetRecordImages($get_record_children_args);
			#	print("BT: NEW IMAGES!");
			#	print_r($new_children);
			} else {
				$new_children = $this->orm->GetRecordChildren($get_record_children_args);
			}
			
			$this->children = $new_children;
			
			return $this->children;
		}
		
		public function SetFullChildRecords() {
			return $this->SetChildRecords(['alltext'=>1]);
		}
		
		public function GetRecordAndChildren($args) {
			return $this->orm->GetRecordAndChildren($args);
		}

		public function GetRecordsAndChildren($args) {
			return $this->orm->GetRecordsAndChildren($args);
		}
		
		/*
			Two passes rather than a fetch per association.

			This walked every entry, then every association on it, and asked the
			database for each associated entry separately.  Measured with
			DBAccessUpgraded's query recorder on the revoltlib front page, that
			was 240 of 390 queries in a single render, each one waiting about
			2.3 ms on the managed database -- more than half a second of a
			two-second page, spent asking the same question repeatedly.

			So: collect every ChosenEntryid first, fetch them in one query, and
			hand them back out.  GetRecordsAndChildren returns them keyed by id,
			which is exactly the shape this needs.

			An id with no row -- unpublished, or deleted -- is simply absent from
			the map, which is the same nothing the single fetch returned for it.
		*/

		public function SetAssociationRecordsForEntries($args) {
			$entries = $args['entries'];
			$entry_count = count($entries);

			$chosen_entry_ids = [];

			for($i = 0; $i < $entry_count; $i++) {
				$associations = $entries[$i]['association'];

				if(!$associations || !is_array($associations)) {
					continue;
				}

				$association_count = count($associations);

				for($j = 0; $j < $association_count; $j++) {
					if(empty($associations[$j]['ChosenEntryid'])) {
						continue;
					}

					$chosen_entry_ids[] = $associations[$j]['ChosenEntryid'];
				}
			}

			if(!count($chosen_entry_ids)) {
				return $entries;
			}

			$chosen_entries = $this->GetRecordsAndChildren(['ids'=>$chosen_entry_ids]);

			for($i = 0; $i < $entry_count; $i++) {
				$entry = $entries[$i];

				$associations = $entry['association'];

				if($associations && is_array($associations)) {
					$association_count = count($associations);

					for($j = 0; $j < $association_count; $j++) {
						$chosen_entry_id = $entry['association'][$j]['ChosenEntryid'];

						if(empty($chosen_entry_id)) {
							continue;
						}

						if(!array_key_exists($chosen_entry_id, $chosen_entries)) {
							continue;
						}

						$entry['association'][$j]['entry'] = $chosen_entries[$chosen_entry_id];
					}
				}

				$entries[$i] = $entry;
			}

			return $entries;
		}
		
		public function SetAssociationRecords() {
			$associations = $this->entry['association'];
			if($associations && is_array($associations)) {
				$association_count = count($associations);
				
				if($association_count) {
					for($i = 0; $i < $association_count; $i++) {
						$this->entry['association'][$i]['entry'] = $this->GetRecordAndChildren(['entry'=>['id'=>$this->entry['association'][$i]['ChosenEntryid']]])[0];
						$this->entry['association'][$i]['entry']['parents'] = $this->GetEntryParents(['entry'=>['id'=>$this->entry['association'][$i]['ChosenEntryid']]])['parents'];
					}
				}
			}
			
			$associateds = $this->entry['associated'];
			if($associateds && is_array($associateds)) {
				$associated_count = count($associateds);
				
				if($associated_count) {
					for($i = 0; $i < $associated_count; $i++) {
						$this->entry['associated'][$i]['entry'] = $this->GetRecordAndChildren(['entry'=>['id'=>$this->entry['associated'][$i]['Entryid']]])[0];
						$this->entry['associated'][$i]['entry']['parents'] = $this->GetEntryParents(['entry'=>['id'=>$this->entry['associated'][$i]['Entryid']]])['parents'];
					}
				}
			}
			
			return TRUE;
		}
		
		public function SetAssociatedAssociationEntries($args) {
			$entries = $args['entries'];
			$primary_key = $args['primary_key'];		# either 'association' or 'associated'
			$relating_key = $args['relating_key'];		# either 'ChosenEntryid' or 'Entryid'
			
			$count = count($entries);
			
			for($i = 0; $i < $count; $i++) {
				$associations = $entries[$i]['association'];
				if($associations && is_array($associations)) {
					$association_count = count($associations);
					
					if($association_count) {
						for($j = 0; $j < $association_count; $j++) {
				//			$entries[$i]['association'][$j]['entry'] = $this->GetRecordAndChildren([entry=>['id'=>$records[$i]['association'][$j]['ChosenEntryid']]])[0];
				
							$entry_where = [
								'type'=>'Entry',
								'definition'=>[
									'id'=>$entries[$i][$primary_key][$j][$relating_key],
								],
							];
							
							$entries[$i][$primary_key][$j]['entry'] = $this->handler->db_access->GetRecords($entry_where)[0];
							$entries[$i][$primary_key][$j]['entry']['parents'] = $this->GetEntryParents(['entry'=>['id'=>$entries[$i][$primary_key][$j][$relating_key]]])['parents'];
						}
					}
				}
			}
			
			return $entries;
		}
		
		public function setChildRecordStatsOfChildren() {
			if(!$this->children && !$this->children_random) {
				return FALSE;
			}
			
			$entry_ids = [];
			
			$children_count = $this->children ? count($this->children) : 0;
			$children_random_count = $this->children_random ? count($this->children_random) : 0;
			
			for($i = 0; $i < $children_count; $i++) {
				$child = $this->children[$i];
				
				$entry_ids[] = $child['id'];
			}
			
			for($i = 0; $i < $children_random_count; $i++) {
				$child_random = $this->children_random[$i];
				
				$entry_ids[] = $child_random['id'];
			}
			
			$entry_id_count = count($entry_ids);
			
			if($entry_id_count === 0) {
				return FALSE;
			}
			
			$question_marks = array_fill(0, count($entry_ids), '?');
			$question_mark_string = implode(', ', $question_marks);
			
			$sql = 'SELECT ';
			
			$sql .= '* ';
			
			$sql .= 'FROM ChildRecordStats WHERE Entryid IN(' . $question_mark_string . ')';
			
			$child_record_stats = $this->handler->db_access->RunQuery([
				'sql'=>$sql,
				'args'=>$entry_ids,
			]);
			
			$child_record_stats_hash = [];
			
			$child_record_stats_count = count($child_record_stats);
			
			for($i = 0; $i < $child_record_stats_count; $i++) {
				$child_record_stat = $child_record_stats[$i];
				
				if(!$child_record_stats_hash[$child_record_stat['Entryid']]) {
					$child_record_stats_hash[$child_record_stat['Entryid']] = [];
				}
				
				$child_record_stats_hash[$child_record_stat['Entryid']][] = $child_record_stat;
			}
			
			for($i = 0; $i < $children_count; $i++) {
				$child = &$this->children[$i];
				
				$child['childrecordstats'] = $child_record_stats_hash[$child['id']];
			}
			
			for($i = 0; $i < $children_random_count; $i++) {
				$child_random = &$this->children_random[$i];
				
				$child['childrecordstats'] = $child_record_stats_hash[$child_random['id']];
			}
			
		#	print("<PRE>");
		#	print_r($this->children);
		#	print_r($child_record_stats);
		#	print("</PRE>");
			
			return TRUE;
		}
		
		public function ExtendedSetChildRecordsAssociated() {
			if(!$this->children) {
				return $this->children;
			}
			
			$child_count = count($this->children);
			
			if($child_count === 0) {
				return $this->children;
			}
			
			$entry_ids = [];
			
			for($i = 0; $i < $child_count; $i++) {
				$associateds = $this->children[$i]['associated'];
				
			#	print("<!-- BT:\n\n");
				
			#	print_r($associateds);
			#	print_r($this->children[$i]['associated']);
				
			#	print("\n\n-->");
				
				$associateds_count = $associateds ? count($associateds) : 0;
				
				for($j = 0; $j < $associateds_count; $j++) {
				
					$associated = $associateds[$j];
					$associated_entry = $associated['entry'];
					
					$entry_ids[] = $associated_entry['id'];
				}
			}
			
			$entry_ids_count = count($entry_ids);
			
		#	print("<!-- BT:!!!!\n\n");
		#	
		#	print_r($entry_ids);
		#	print(count($entry_ids));
			
		#	print("\n\n-->");
			
			if($entry_ids_count === 0) {
				return $this->children;
			}
			
			$question_marks = array_fill(0, count($entry_ids), '?');
			$question_mark_string = implode(', ', $question_marks);
			
			$sql = 'SELECT ';
			
			$sql .= 'id, substring(Text,1,1000) as FirstThousandCharacters, Language, WordCount, CharacterCount, Source, Entryid, OriginalCreationDate, LastModificationDate ';
			
			$sql .= 'FROM TextBody WHERE Entryid IN(' . $question_mark_string . ')';
			
			$associated_textbodies = $this->handler->db_access->RunQuery([
				'sql'=>$sql,
				'args'=>$entry_ids,
			]);
			
		#	print("<!-- BT:!!!!\n\n");
			
		#	print_r($entry_ids);
			
		#	print_r($associated_textbodies);
		#	print("\n\n-->");
			
			$associated_textbodies_count = count($associated_textbodies);
			
			$associated_textbody_hash = [];
			
			for($i = 0; $i < $associated_textbodies_count; $i++) {
				$associated_textbody = $associated_textbodies[$i];
				
				if(!$associated_textbody_hash[$associated_textbody['Entryid']]) {
					$associated_textbody_hash[$associated_textbody['Entryid']] = [];
				}
				
				$associated_textbody_hash[$associated_textbody['Entryid']][] = $associated_textbody;
			}
			
		#	print("<!-- BT:\n\n");
		#	print_r($associated_textbody_hash);
		#	print("-->\n\n");
			
			for($i = 0; $i < $child_count; $i++) {
				$associateds = &$this->children[$i]['associated'];
				$associateds_count = $associateds ? count($associateds) : 0;
				
				for($j = 0; $j < $associateds_count; $j++) {
			#		print("<!-- BT: SET GO! -->");
					$associated = &$associateds[$j];
					$associated_entry = &$associated['entry'];
					
					$associated_entry['textbody'] = $associated_textbody_hash[$associated_entry['id']];
				#	$associated_entry['test'] = 'rock on man';
				#	$associated = [];
				}
			}
			
			
		#	print("<!-- BT:!!!!\n\n");
			
		#	print($sql);
		#	print("\n\n");
		#	print_r($entry_ids);
		#	print_r($associated_textbodies);
			
		#	print(count($associated_textbodies));
			
		#	print("\n\n-->");
			
			return $this->children;
		}
		
		public function SetExtendedChildAssociationRecords() {
			$child_count = count($this->children);
			
			$entry_ids = [];
			
			for($i = 0; $i < $child_count; $i++) {
				$associations = $this->children[$i]['association'];
				
				$associations_count = count($associations);
				for($j = 0; $j < $associations_count; $j++) {
					$association = $associations[$j];
					$association_entry = $association['entry'];
					
					$entry_ids[] = $association_entry['id'];
	#				print('<!-- BT: ' . $association_entry['Title'] . '-->');
				}
			}
			
			$entry_ids_count = count($entry_ids);
			
			if($entry_ids_count === 0) {
				return FALSE;
			}
			
			$sql = 'SELECT * FROM Association WHERE Entryid IN(' . implode(',', array_fill(0, $entry_ids_count, '?')) . ')';
			$associations = $this->handler->db_access->RunQuery([
				'sql'=>$sql,
				'args'=>$entry_ids,
			]);
			
			if(!$associations) {
				return FALSE;
			}
			
			$associations_count = count($associations);
			
			if($associations_count === 0) {
				return FALSE;
			}
			
			$associated_entry_ids = [];
			
			foreach($associations as $association) {
				$associated_entry_ids[] = $association['ChosenEntryid'];
			}
			
			$associated_entry_ids_count = count($associated_entry_ids);
			
			$sql = 'SELECT * FROM Entry WHERE id IN(' . implode(',', array_fill(0, $associated_entry_ids_count, '?')) . ')';
			$entries = $this->handler->db_access->RunQuery([
				'sql'=>$sql,
				'args'=>$associated_entry_ids,
			]);
			
			$entry_hash = [];
			$association_hash = [];
			
			foreach($entries as $entry) {
				$entry_hash[$entry['id']] = $entry;
			}
			
			foreach($associations as $association) {
				$association_hash[$association['Entryid']] = $association;
			}
			
			for($i = 0; $i < $child_count; $i++) {
				$associations = $this->children[$i]['association'];
				
				$associations_count = count($associations);
				for($j = 0; $j < $associations_count; $j++) {
					$association = $associations[$j];
					$association_entry = $association['entry'];
					
					$association_entry_association = $association_hash[$association_entry['id']];
					$association_entry_association_entry = $entry_hash[$association_entry_association['ChosenEntryid']];
					
					$this->children[$i]['association'][$j]['associated_entry'] = $association_entry_association_entry;
				#	$entry_ids[] = $association_entry['id'];
			#		print('<!-- BT: ' . $association_entry['id'] . '-->');
			#		print('<!-- BT: ' . $association_entry_association_entry['Title'] . '-->');
			#		print("\n\n");
				}
			}
			
			
		#	print("<!-- BT:!!!\n\n");
			
		#	print_r($associations);
			
		#	print("\n\n-->");
			
		#	print("<!-- BT: COUNT!" . $child_count . "-->");
			
			return TRUE;
		}
		
		public function SetSimpleChildAssociationRecords() {
			$child_count = count($this->children);
			
			for($i = 0; $i < $child_count; $i++) {
				$associations = $this->children[$i]['association'];
				if($associations && is_array($associations)) {
					$association_count = count($associations);
					
					if($association_count) {
						for($j = 0; $j < $association_count; $j++) {
				//			$this->children[$i]['association'][$j]['entry'] = $this->GetRecordAndChildren([entry=>['id'=>$this->children[$i]['association'][$j]['ChosenEntryid']]])[0];
				
							$entry_where = [
								'type'=>'Entry',
								'definition'=>[
									'id'=>$this->children[$i]['association'][$j]['ChosenEntryid'],
								],
							];
							
							$this->children[$i]['association'][$j]['entry'] = $this->handler->db_access->GetRecords($entry_where)[0];
							$this->children[$i]['association'][$j]['entry']['parents'] = $this->GetEntryParents(['entry'=>['id'=>$this->children[$i]['association'][$j]['ChosenEntryid']]])['parents'];
						}
					}
				}
				
				$associateds = $this->children[$i]['associated'];
				if($associateds && is_array($associateds)) {
					$associated_count = count($associateds);
					
					if($associated_count) {
						for($j = 0; $j < $associated_count; $j++) {
							// $this->GetRecordAndChildren([entry=>['id'=>$this->children[$i]['associated'][$j]['Entryid']]])[0];
							$entry_where = [
								'type'=>'Entry',
								'definition'=>[
									'id'=>$this->children[$i]['associated'][$j]['Entryid'],
								],
							];
							
							$this->children[$i]['associated'][$j]['entry'] = $this->handler->db_access->GetRecords($entry_where)[0];
							$this->children[$i]['associated'][$j]['entry']['parents'] = $this->GetEntryParents(['entry'=>['id'=>$this->children[$i]['associated'][$j]['Entryid']]])['parents'];
						}
					}
				}
			}
			
			if($this->children_random) {
				$child_count = count($this->children_random);
				
				for($i = 0; $i < $child_count; $i++) {
					$associations = $this->children_random[$i]['association'];
					if($associations && is_array($associations)) {
						$association_count = count($associations);
						
						if($association_count) {
							for($j = 0; $j < $association_count; $j++) {
								$entry_where = [
									'type'=>'Entry',
									'definition'=>[
										'id'=>$this->children_random[$i]['association'][$j]['ChosenEntryid'],
									],
								];
								
								$this->children_random[$i]['associated'][$j]['entry'] = $this->handler->db_access->GetRecords($entry_where)[0];
						//		$this->children_random[$i]['association'][$j]['entry'] = $this->GetRecordAndChildren([entry=>['id'=>$this->children_random[$i]['association'][$j]['ChosenEntryid']]])[0];
								$this->children_random[$i]['association'][$j]['entry']['parents'] = $this->GetEntryParents(['entry'=>['id'=>$this->children_random[$i]['association'][$j]['ChosenEntryid']]])['parents'];
							}
						}
					}
					
					$associateds = $this->children_random[$i]['associated'];
					if($associateds && is_array($associateds)) {
						$associated_count = count($associateds);
						
						if($associated_count) {
							for($j = 0; $j < $associated_count; $j++) {
								$entry_where = [
									'type'=>'Entry',
									'definition'=>[
										'id'=>$this->children_random[$i]['associated'][$j]['Entryid'],
									],
								];
								
								$this->children_random[$i]['associated'][$j]['entry'] = $this->handler->db_access->GetRecords($entry_where)[0];
						//		$this->children_random[$i]['associated'][$j]['entry'] = $this->GetRecordAndChildren([entry=>['id'=>$this->children_random[$i]['associated'][$j]['Entryid']]])[0];
								$this->children_random[$i]['associated'][$j]['entry']['parents'] = $this->GetEntryParents(['entry'=>['id'=>$this->children_random[$i]['associated'][$j]['Entryid']]])['parents'];
							}
						}
					}
				}
			}
			
			return TRUE;
		}
		
		public function SetChildAssociationRecords() {
			$child_count = count($this->children);
			
			for($i = 0; $i < $child_count; $i++) {
				$associations = $this->children[$i]['association'];
				if($associations && is_array($associations)) {
					$association_count = count($associations);
					
					if($association_count) {
						for($j = 0; $j < $association_count; $j++) {
							$this->children[$i]['association'][$j]['entry'] = $this->GetRecordAndChildren(['entry'=>['id'=>$this->children[$i]['association'][$j]['ChosenEntryid']]])[0];
							$this->children[$i]['association'][$j]['entry']['parents'] = $this->GetEntryParents(['entry'=>['id'=>$this->children[$i]['association'][$j]['ChosenEntryid']]])['parents'];
						}
					}
				}
				
				$associateds = $this->children[$i]['associated'];
				if($associateds && is_array($associateds)) {
					$associated_count = count($associateds);
					
					if($associated_count) {
						for($j = 0; $j < $associated_count; $j++) {
							$this->children[$i]['associated'][$j]['entry'] = $this->GetRecordAndChildren(['entry'=>['id'=>$this->children[$i]['associated'][$j]['Entryid']]])[0];
							$this->children[$i]['associated'][$j]['entry']['parents'] = $this->GetEntryParents(['entry'=>['id'=>$this->children[$i]['associated'][$j]['Entryid']]])['parents'];
						}
					}
				}
			}
			
			if($this->children_random) {
				$child_count = count($this->children_random);
				
				for($i = 0; $i < $child_count; $i++) {
					$associations = $this->children_random[$i]['association'];
					if($associations && is_array($associations)) {
						$association_count = count($associations);
						
						if($association_count) {
							for($j = 0; $j < $association_count; $j++) {
								$this->children_random[$i]['association'][$j]['entry'] = $this->GetRecordAndChildren(['entry'=>['id'=>$this->children_random[$i]['association'][$j]['ChosenEntryid']]])[0];
								$this->children_random[$i]['association'][$j]['entry']['parents'] = $this->GetEntryParents(['entry'=>['id'=>$this->children_random[$i]['association'][$j]['ChosenEntryid']]])['parents'];
							}
						}
					}
					
					$associateds = $this->children_random[$i]['associated'];
					if($associateds && is_array($associateds)) {
						$associated_count = count($associateds);
						
						if($associated_count) {
							for($j = 0; $j < $associated_count; $j++) {
								$this->children_random[$i]['associated'][$j]['entry'] = $this->GetRecordAndChildren(['entry'=>['id'=>$this->children_random[$i]['associated'][$j]['Entryid']]])[0];
								$this->children_random[$i]['associated'][$j]['entry']['parents'] = $this->GetEntryParents(['entry'=>['id'=>$this->children_random[$i]['associated'][$j]['Entryid']]])['parents'];
							}
						}
					}
				}
			}
			
			return TRUE;
		}
		
		/*
			Three loops deep -- children, then grandchildren, then each of their
			associations -- and every iteration asked the database twice: once
			for the associated entry and its children, once for that entry's
			parents.

			Measured with DBAccessUpgraded's query recorder on the revoltlib
			front page, this was the single largest source of queries in a
			render by a wide margin, each one waiting about 2.3 ms on the
			managed database.

			Two passes instead.  Collect every id the tree refers to, fetch them
			all at once -- GetRecordsAndChildren for the entries, GetEntriesParents
			for their parents, both of which already existed -- and then walk the
			tree again handing them out.

			An id with no row is absent from the map and the association keeps no
			'entry' key, which is what the per-id version left behind for it too.
		*/

		public function SetGrandChildAssociationRecords() {
			if(!$this->children) {
				return TRUE;
			}

			$wanted_ids = $this->SetGrandChildAssociationRecords_CollectIds();

			if(!count($wanted_ids)) {
				return TRUE;
			}

			$fetched = $this->GetRecordsAndChildren(['ids'=>$wanted_ids]);

			if(!count($fetched)) {
				return TRUE;
			}

			$with_parents = $this->GetEntriesParents(['entries'=>array_values($fetched)]);

			foreach($with_parents as $entry_with_parents) {
				$fetched[$entry_with_parents['id']] = $entry_with_parents;
			}

			$this->SetGrandChildAssociationRecords_Distribute(['fetched'=>$fetched]);

			return TRUE;
		}

		public function SetGrandChildAssociationRecords_CollectIds() {
			$wanted_ids = [];

			$child_count = count($this->children);

			for($i = 0; $i < $child_count; $i++) {
				$grand_children = $this->children[$i]['children'];

				if(!$grand_children || !is_array($grand_children)) {
					continue;
				}

				$grand_children_count = count($grand_children);

				for($j = 0; $j < $grand_children_count; $j++) {
					$associations = $this->children[$i]['children'][$j]['association'];

					if($associations && is_array($associations)) {
						foreach($associations as $association) {
							if(!empty($association['ChosenEntryid'])) {
								$wanted_ids[] = $association['ChosenEntryid'];
							}
						}
					}

					$associateds = $this->children[$i]['children'][$j]['associated'];

					if($associateds && is_array($associateds)) {
						foreach($associateds as $associated) {
							if(!empty($associated['Entryid'])) {
								$wanted_ids[] = $associated['Entryid'];
							}
						}
					}
				}
			}

			return $wanted_ids;
		}

		public function SetGrandChildAssociationRecords_Distribute($args) {
			$fetched = $args['fetched'];

			$child_count = count($this->children);

			for($i = 0; $i < $child_count; $i++) {
				$grand_children = $this->children[$i]['children'];

				if(!$grand_children || !is_array($grand_children)) {
					continue;
				}

				$grand_children_count = count($grand_children);

				for($j = 0; $j < $grand_children_count; $j++) {
					$associations = $this->children[$i]['children'][$j]['association'];

					if($associations && is_array($associations)) {
						$association_count = count($associations);

						for($k = 0; $k < $association_count; $k++) {
							$chosen_id = $this->children[$i]['children'][$j]['association'][$k]['ChosenEntryid'];

							if(empty($chosen_id) || !array_key_exists($chosen_id, $fetched)) {
								continue;
							}

							$this->children[$i]['children'][$j]['association'][$k]['entry'] = $fetched[$chosen_id];
						}
					}

					$associateds = $this->children[$i]['children'][$j]['associated'];

					if($associateds && is_array($associateds)) {
						$associateds_count = count($associateds);

						for($k = 0; $k < $associateds_count; $k++) {
							$associated_id = $this->children[$i]['children'][$j]['associated'][$k]['Entryid'];

							if(empty($associated_id) || !array_key_exists($associated_id, $fetched)) {
								continue;
							}

							$this->children[$i]['children'][$j]['associated'][$k]['entry'] = $fetched[$associated_id];
						}
					}
				}
			}

			return TRUE;
		}

		/*
			One query for the whole tier instead of one per grandchild.

			Every grandchild wants the same shape of answer -- a random five of
			its children -- so GetRecordsChildren asks for all of them at once
			and returns them keyed by parent id.  A grandchild with no children
			is missing from that map, and gets an empty list here, which is what
			the per-parent call returned for it before.
		*/

		public function SetGrandChildRecordsOfChildren() {
			$start_index = 1;
			$end_index = 5;
			$orderby = 'RAND()';

			$parent_ids = [];

			foreach($this->children as $child_key => $child) {
				foreach($this->children[$child_key]['children'] as $grand_child_key => $grand_child) {
					$parent_ids[] = $grand_child['id'];
				}
			}

			if(!count($parent_ids)) {
				return TRUE;
			}

			$children_by_parent = $this->orm->GetRecordsChildren([
				'parentids'=>$parent_ids,
				'startindex'=>$start_index,
				'endindex'=>$end_index,
				'orderby'=>$orderby,
			]);

			foreach($this->children as $child_key => $child) {
				foreach($this->children[$child_key]['children'] as $grand_child_key => $grand_child) {
					$grand_child_id = $grand_child['id'];

					if(array_key_exists($grand_child_id, $children_by_parent)) {
						$this->children[$child_key]['children'][$grand_child_key]['children'] = $children_by_parent[$grand_child_id];
					} else {
						$this->children[$child_key]['children'][$grand_child_key]['children'] = [];
					}
				}
			}

			return TRUE;
		}

		public function SetFullChildRecordsOfChildren() {
			$start_index = 0;
			$end_index = 0;
			$orderby = '';
			
			foreach($this->children as $child_key => $child) {
				$get_record_children_args = [
					'entry'=>$child,
					'startindex'=>$start_index,
					'endindex'=>$end_index,
					'orderby'=>$orderby,
					'alltext'=>TRUE,
				];
				$this->children[$child_key]['children'] = $this->orm->GetRecordChildren($get_record_children_args);
			}
			
			if($this->entry['associated']) {
				foreach($this->entry['associated'] as $associated_key => $associated_item) {
					$entry = $associated_item['entry'];
					$get_record_children_args = [
						'entry'=>$entry,
						'startindex'=>$start_index,
						'endindex'=>$end_index,
						'orderby'=>$orderby,
						'alltext'=>TRUE,
					];
					
					$this->entry['associated'][$associated_key]['entry']['children'] = $this->orm->GetRecordChildren($get_record_children_args);
				}
			}
			
			return TRUE;
		}
		
		public function SetChildRecordsOfChildren() {
			$start_index = 1;
			$end_index = 5;
			$orderby = 'RAND()';
			
			foreach($this->children as $child_key => $child) {
				$get_record_children_args = [
					'entry'=>$child,
					'startindex'=>$start_index,
					'endindex'=>$end_index,
					'orderby'=>$orderby,
					'publish'=>TRUE,
				];
				
				$this->children[$child_key]['children'] = $this->orm->GetRecordChildren($get_record_children_args);
			}
			
			if($this->children_random && is_array($this->children_random)) {
				foreach($this->children_random as $child_key => $child) {
					$get_record_children_args = [
						'entry'=>$child,
						'startindex'=>$start_index,
						'endindex'=>$end_index,
						'orderby'=>$orderby,
						'publish'=>TRUE,
					];
					
					$this->children_random[$child_key]['children'] = $this->orm->GetRecordChildren($get_record_children_args);
				}
			}
			
			if($this->entry['associated']) {
				foreach($this->entry['associated'] as $associated_key => $associated_item) {
					$entry = $associated_item['entry'];
					$get_record_children_args = [
						'entry'=>$entry,
						'startindex'=>$start_index,
						'endindex'=>$end_index,
						'orderby'=>$orderby,
						'publish'=>TRUE,
					];
					$this->entry['associated'][$associated_key]['entry']['children'] = $this->orm->GetRecordChildren($get_record_children_args);
				}
			}
			
			return TRUE;
		}
		
		public function SetChildRecordCount() {
			$entry = $this->entry;
			
			if(!$entry || !$entry['id']) {
				$entry = $this->parent;
			}
			
			$where = [];
			
			if($this->where) {
				$where = $this->where;
			}
			
			if($this->desired_action === 'browseAssociated') {
				return $this->children_count = $this->orm->GetRecordAssociationCount([
					'entry'=>$entry,
					'where'=>$where,
					'ignore_parent'=>$this->Param('ignore_parent'),
				]);
			}
			
			return $this->children_count = $this->orm->GetRecordChildrenCount(['entry'=>$entry, 'where'=>$where]);
		}
		
		public function SetEntryRecordCount() {
			$where = [];
			
			if($this->where) {
				$where = $this->where;
			}
			
			return $this->entry_count = $this->orm->GetRecordEntryCount(['where'=>$where]);
		}
		
		public function GetCommentsCount($args) {
			return $this->comments_count = $this->orm->GetUserCommentsCount($args);
		}
		
		public function GetLikesDislikesCount($args) {
			$this->likes_count = $this->orm->GetUserLikesCount($args);
			$this->dislikes_count = $this->orm->GetUserDislikesCount($args);
			
			$this->total_likes_count = ($this->likes_count + $this->dislikes_count);
			
			return TRUE;
		}
		
		public function GetEntryLikesDislikesCount($args) {
			$entry = $args['entry'];
			
			if(!$entry || !$entry['id']) {
				$entry = $this->entry;
			}
			
			$likes_where = [
				'type'=>'LikeDislike',
				'select'=>'Count(id) as count',
				'definition'=>[
					'LikeOrDislike'=>1,
					'Entryid'=>$entry['id'],
				],
			];
			
			$likes_count = $this->handler->db_access->GetRecords($likes_where)[0]['count'];
			
			$dislikes_where = [
				'type'=>'LikeDislike',
				'select'=>'Count(id) as count',
				'definition'=>[
					'LikeOrDislike'=>0,
					'Entryid'=>$entry['id'],
				],
			];
			
			$dislikes_count = $this->handler->db_access->GetRecords($dislikes_where)[0]['count'];
			
			return [
				'likes'=>$likes_count,
				'dislikes'=>$dislikes_count,
			];
		}
		
		public function SetEntryChildRecordStats($args) {
			$entry = $args['entry'];
			
			if(!$entry || !$entry['id']) {
				$entry = $this->entry;
				
				if(!$entry || !$entry['id']) {
					$entry = $this->master_record;
				}
			}
			
			if(!$entry || !$entry['id']) {
				return FALSE;
			}
			
			if(!$this->ormstats) {
				if(!$this->SetOrmStats()) {
					return FALSE;
				}
			}
			
			$stats_record_args = [
				'type'=>'ChildRecordStats',
				'definition'=>[
					'Entryid'=>$entry['id'],
				],
				'limit'=>1,
			];
			
			$child_record_stats_records = $this->handler->db_access->GetRecords($stats_record_args);
			
			if($child_record_stats_records && count($child_record_stats_records)) {
				$child_record_stats = $child_record_stats_records[0];
				
				$now = time();
				$then = strtotime($child_record_stats['LastModificationDate']);
				$time_difference = $now - $then;
				
				if($time_difference >= (60 * 60 * 24) || ($this->authentication_object->user_session['UserAdmin.id'] && $this->Param('refreshstats'))) {
					$regenerated_child_record_stats = $this->ormstats->GenerateChildRecordStats(['entry'=>$entry]);
					
					if(!$regenerated_child_record_stats['TotalRecordCount']) {
						$regenerated_child_record_stats['TotalRecordCount'] = 0;
					}
					
					if(!$regenerated_child_record_stats['TotalWordCount']) {
						$regenerated_child_record_stats['TotalWordCount'] = 0;
					}
					
					if(!$regenerated_child_record_stats['TotalRecordCount']) {
						$regenerated_child_record_stats['TotalCharacterCount'] = 0;
					}
					
					$child_record_stats_child_definition = [
						'ChildRecordCount'=>$regenerated_child_record_stats['TotalRecordCount'],
						'ChildWordCount'=>$regenerated_child_record_stats['TotalWordCount'],
						'ChildCharacterCount'=>$regenerated_child_record_stats['TotalCharacterCount'],
					];
					
					$child_record_stats_update_args = [
						'type'=>'ChildRecordStats',
						'update'=>$child_record_stats_child_definition,
						'where'=>[
							'id'=>$child_record_stats['id'],
						],
					];
					
					$child_record_stats = $this->handler->db_access->UpdateRecord($child_record_stats_update_args)[0];
				}
	#			print('Seconds difference?' . ($now - $then) . "|");
#				print($now);
			} else {
				$child_record_stats = $this->ormstats->GenerateChildRecordStats(['entry'=>$entry]);
				
				if(!$child_record_stats['TotalRecordCount']) {
					$child_record_stats['TotalRecordCount'] = 0;
				}
				
				if(!$child_record_stats['TotalWordCount']) {
					$child_record_stats['TotalWordCount'] = 0;
				}
				
				if(!$child_record_stats['TotalCharacterCount']) {
					$child_record_stats['TotalCharacterCount'] = 0;
				}
				
				$child_record_stats_child_definition = [
					'ChildRecordCount'=>$child_record_stats['TotalRecordCount'],
					'ChildWordCount'=>$child_record_stats['TotalWordCount'],
					'ChildCharacterCount'=>$child_record_stats['TotalCharacterCount'],
					'Entryid'=>$entry['id'],
				];
				
				$child_record_stats_insert_args = [
					'type'=>'ChildRecordStats',
					'definition'=>$child_record_stats_child_definition,
				];
				
				$child_record_stats = $this->handler->db_access->CreateRecord($child_record_stats_insert_args);
			}
			
			return $this->child_record_stats = $child_record_stats;
		}
		
		public function SetEntryAssociatedRecordStats($args) {
			$entry = $args['entry'];
			
			if(!$entry || !$entry['id']) {
				$entry = $this->entry;
				
				if(!$entry || !$entry['id']) {
					$entry = $this->master_record;
				}
			}
			
			if(!$entry || !$entry['id']) {
				return FALSE;
			}
			
			$ignore_parents = $this->Param('ignore_parent');
			
			$stats_record_args = [
				'type'=>'AssociatedRecordStats',
				'definition'=>[
					'Entryid'=>$entry['id'],
					'IgnoreParents'=>$ignore_parents,
				],
				'limit'=>1,
			];
			
			$associated_record_stats_records = $this->handler->db_access->GetRecords($stats_record_args);
			
			if($associated_record_stats_records && count($associated_record_stats_records)) {
				$associated_record_stats = $associated_record_stats_records[0];
				
				$now = time();
				$then = strtotime($associated_record_stats['LastModificationDate']);
				$time_difference = $now - $then;
				
				if($time_difference >= (60 * 60 * 24) || ($this->authentication_object->user_session['UserAdmin.id'] && $this->Param('refreshstats'))) {
					$regenerated_associated_record_stats = $this->ormstats->GenerateAssociatedRecordStats([
						'entry'=>$entry,
						'ignore_parent'=>$this->Param('ignore_parent'),
					]);
					
					$associated_record_stats_child_definition = [
						'AssociatedRecordCount'=>$regenerated_associated_record_stats['TotalRecordCount'],
						'AssociatedWordCount'=>$regenerated_associated_record_stats['TotalWordCount'],
						'AssociatedCharacterCount'=>$regenerated_associated_record_stats['TotalCharacterCount'],
						'IgnoreParents'=>$ignore_parents,
					];
					
					$associated_record_stats_update_args = [
						'type'=>'AssociatedRecordStats',
						'update'=>$associated_record_stats_child_definition,
						'where'=>[
							'id'=>$associated_record_stats['id'],
						],
					];
					
					$associated_record_stats = $this->handler->db_access->UpdateRecord($associated_record_stats_update_args)[0];
				}
			} else {
				$associated_record_stats = $this->ormstats->GenerateAssociatedRecordStats([
					'entry'=>$entry,
					'ignore_parent'=>$this->Param('ignore_parent'),
				]);
				
				if(!$associated_record_stats['TotalRecordCount']) {
					$associated_record_stats['TotalRecordCount'] = 0;
				}
				
				if(!$associated_record_stats['TotalWordCount']) {
					$associated_record_stats['TotalWordCount'] = 0;
				}
				
				if(!$associated_record_stats['TotalCharacterCount']) {
					$associated_record_stats['TotalCharacterCount'] = 0;
				}
				
				$associated_record_stats_child_definition = [
					'AssociatedRecordCount'=>$associated_record_stats['TotalRecordCount'],
					'AssociatedWordCount'=>$associated_record_stats['TotalWordCount'],
					'AssociatedCharacterCount'=>$associated_record_stats['TotalCharacterCount'],
					'IgnoreParents'=>$ignore_parents,
					'Entryid'=>$entry['id'],
				];
				
				$associated_record_stats_insert_args = [
					'type'=>'AssociatedRecordStats',
					'definition'=>$associated_record_stats_child_definition,
				];
				
				$associated_record_stats = $this->handler->db_access->CreateRecord($associated_record_stats_insert_args);
			}
			
			return $this->associated_record_stats = $associated_record_stats;
		}
		
		public function SetOrmStats() {
			ggreq('classes/Database/ORMStats.php');
			
			return $this->ormstats = new ORMStats(['dbaccessobject'=>$this->handler->db_access]);
		}
		
		public function SetIndexChildRecords($args) {
				// Set Child Records (sorted by date)
				// ------------------------------------------------------------
				
			$this->SetChildRecords([]);
			
			$exclude_ids = [];
			
			foreach($this->children as $child) {
				$exclude_ids[] = $child['id'];
			}
			
			$sql = 'WHERE Entry1.Publish = 1 ';
			
			if(count($exclude_ids)) {
				$sql .= ' AND Entry1.id NOT IN(' . implode(',', array_fill(0, count($exclude_ids), '?')) .  ') ';
			}
			
			$this->where = [
				'sql'=>$sql,
				'bind'=>str_repeat('i', count($exclude_ids)),
				'value'=>$exclude_ids,
			];
			
				// Set Child Records (sorted randomly)
				// ------------------------------------------------------------
			
			$this->SetRandomChildRecords();
			
			foreach($this->children_random as $child) {
				$exclude_ids[] = $child['id'];
			}
			
			$sql = 'WHERE Entry1.Publish = 1';
			
			if(count($exclude_ids)) {
				$sql .= ' AND Entry1.id NOT IN(' . implode(',', array_fill(0, count($exclude_ids), '?')) .  ') ';
			}
			
			$this->where = [
				'sql'=>$sql,
				'bind'=>str_repeat('i', count($exclude_ids)),
				'value'=>$exclude_ids,
			];
			
			#bt sort here
			
				// Set Image Records
				// ------------------------------------------------------------
			
			$this->SetRandomImageRecords();
			
				// Set Tag Records
				// ------------------------------------------------------------
			
			$this->SetRandomTagRecords();
			
				// Set Quote Records
				// ------------------------------------------------------------
			
			$this->SetRandomQuoteRecords();
			
				// Set Description Records
				// ------------------------------------------------------------
			
			$this->SetRandomDescriptionRecords();
			
				// Set Textbody Records
				// ------------------------------------------------------------
			
			$this->SetRandomTextBodyRecords();
			
				// Set EventDate Records
				// ------------------------------------------------------------
			
			$this->SetRandomEventDateRecords();
			
				// Set Like Records
				// ------------------------------------------------------------
			
			$this->SetRandomLikeRecords();
			
			return TRUE;
		}
		
		public function SetRandomLikeRecords() {
			$definition = [
				'LikeOrDislike'=>1,
			];
			
			if($this->where['value']) {
				$definition['RAW'] = [
						'Entryid'=>[
							'NOT IN',
							'(' . implode(',', $this->where['value']) . ')',
						],
					];
			}
			$get_record_where = [
				'type'=>'LikeDislike',
				'definition'=>$definition,
				'joins'=>[
					'JOIN'=>[
						'Assignment'=>'Assignment.Parentid = ' . $this->entry['id'] . ' AND Assignment.Childid = LikeDislike.Entryid',
						'Entry'=>'Entry.id = LikeDislike.Entryid AND Entry.Publish = 1',
					],
				],
				'limit'=>'5',
				'orderby'=>'RAND()',
			];
			$likes_random = $this->handler->db_access->GetRecords($get_record_where);
			#	print("<!-- BT:\n\n");
			#	print_r($likes_random);
			#	print("-->");

				/*
					Commented out rather than deleted, per CodeConventions.md --
					but it was live, printing a print_r of every random Like
					into the HTML of every page that reached here.

					GetRecords does not always return a list.  When it returns a
					string, the loop below indexes a string with 'Entryid' and
					PHP 8 raises "cannot access offset of type string on
					string", which is fatal.  A row that is not an array is not
					a row.
				*/

			if(!is_array($likes_random)) {
				$likes_random = [];
			}

			foreach($likes_random as $likes_random_key => $random_like) {
				if(!is_array($random_like)) {
					continue;
				}

				$likes_counts = $this->GetEntryLikesDislikesCount(['entry'=>['id'=>$random_like['Entryid']]]);
				$random_like['counts'] = $likes_counts;
				$likes_random[$likes_random_key] = $random_like;
			}
			
			return $this->likes_random = $this->FilterValidChildren(['children'=>$likes_random]);
		}
		
		public function SetRandomEventDateRecords() {
			$definition = [];
			
			if($this->where['value']) {
				$definition['RAW'] = [
					'Entryid'=>[
						'NOT IN',
						'(' . implode(',', $this->where['value']) . ')',
					],
				];
			}
			
			$get_record_where = [
				'type'=>'EventDate',
				'definition'=>$definition,
				'joins'=>[
					'JOIN'=>[
						'Assignment'=>'Assignment.Parentid = ' . $this->entry['id'] . ' AND Assignment.Childid = EventDate.Entryid',
						'Entry'=>'Entry.id = EventDate.Entryid AND Publish = 1',
					],
				],
				'limit'=>'5',
				'orderby'=>'RAND()',
			];
			$eventdates_random = $this->handler->db_access->GetRecords($get_record_where);
			return $this->eventdates_random = $this->FilterValidChildren(['children'=>$eventdates_random]);
		}
		
		public function SetRandomTextBodyRecords() {
			$definition = [];
			
			if($this->where['value']) {
				$definition['RAW'] = [
					'Entryid'=>[
						'NOT IN',
						'(' . implode(',', $this->where['value']) . ')',
					],
				];
			}
			
			$get_record_where = [
				'type'=>'TextBody',
				'definition'=>$definition,
				'joins'=>[
					'JOIN'=>[
						'Assignment'=>'Assignment.Parentid = ' . $this->entry['id'] . ' AND Assignment.Childid = TextBody.Entryid',
						'Entry'=>'Entry.id = TextBody.Entryid AND Entry.Publish = 1',
					],
				],
				'limit'=>'5',
				'orderby'=>'RAND()',
			];
			$textbodies_random = $this->handler->db_access->GetRecords($get_record_where);
			
			return $this->textbodies_random = $this->FilterValidChildren(['children'=>$textbodies_random]);
		}
		
		public function SetRandomDescriptionRecords() {
			$definition = [];
			
			if($this->where['value']) {
				$definition['RAW'] = [
					'Entryid'=>[
						'NOT IN',
						'(' . implode(',', $this->where['value']) . ')',
					],
				];
			}
			
			$get_record_where = [
				'type'=>'Description',
				'definition'=>$definition,
				'joins'=>[
					'JOIN'=>[
						'Assignment'=>'Assignment.Parentid = ' . $this->entry['id'] . ' AND Assignment.Childid = Description.Entryid',
						'Entry'=>'Entry.id = Description.Entryid AND Entry.Publish = 1',
					],
				],
				'limit'=>'5',
				'orderby'=>'RAND()',
			];
			$descriptions_random = $this->handler->db_access->GetRecords($get_record_where);
			
			return $this->descriptions_random = $this->FilterValidChildren(['children'=>$descriptions_random]);
		}
		
		public function SetRandomQuoteRecords() {
			$definition = [];
			
			if($this->where['value']) {
				$definition['RAW'] = [
					'Entryid'=>[
						'NOT IN',
						'(' . implode(',', $this->where['value']) . ')',
					],
				];
			}
			
			$get_record_where = [
				'type'=>'Quote',
				'definition'=>$definition,
				'joins'=>[
					'JOIN'=>[
						'Assignment'=>'Assignment.Parentid = ' . $this->entry['id'] . ' AND Assignment.Childid = Quote.Entryid',
						'Entry'=>'Entry.id = Quote.Entryid AND Entry.Publish = 1',
					],
				],
				'limit'=>'5',
				'orderby'=>'RAND()',
			];
			$quotes_random = $this->handler->db_access->GetRecords($get_record_where);
			
			return $this->quotes_random = $this->FilterValidChildren(['children'=>$quotes_random]);
		}
		
		public function SetRandomImageRecords() {
			$definition = [];
			
			if($this->where['value']) {
				$definition['RAW'] = [
					'Entryid'=>[
						'NOT IN',
						'(' . implode(',', $this->where['value']) . ')',
					],
				];
			}
			
			$get_record_where = [
				'type'=>'Image',
				'definition'=>$definition,
				'joins'=>[
					'JOIN'=>[
						'Assignment'=>'Assignment.Parentid = ' . $this->entry['id'] . ' AND Assignment.Childid = Image.Entryid',
						'Entry'=>'Entry.id = Image.Entryid AND Entry.Publish = 1',
					],
				],
				'limit'=>'20',
				'orderby'=>'RAND()',
			];
			$images_random = $this->handler->db_access->GetRecords($get_record_where);
			
			return $this->images_random = $this->FilterValidChildren(['children'=>$images_random]);
		}
		
		public function SetRandomTagRecords() {
			$definition = [];
			
			if($this->where['value']) {
				$definition['RAW'] = [
					'Entryid'=>[
						'NOT IN',
						'(' . implode(',', $this->where['value']) . ')',
					],
				];
			}
			
			$get_record_where = [
				'type'=>'Tag',
				'definition'=>$definition,
				'joins'=>[
					'JOIN'=>[
						'Assignment'=>'Assignment.Parentid = ' . $this->entry['id'] . ' AND Assignment.Childid = Tag.Entryid',
						'Entry'=>'Entry.id = Tag.Entryid AND Entry.Publish = 1',
					],
				],
				'limit'=>'40',
				'orderby'=>'RAND()',
				'groupby'=>'Tag.Tag',		# needed or it errors out?
			];
			$tags_random = $this->handler->db_access->GetRecords($get_record_where);
			
			return $this->tags_random = $this->FilterValidChildren(['children'=>$tags_random]);
		}
		
		public function SetRandomChildRecords() {
			$entry = $this->entry;
			
			if(!$entry || !$entry['id']) {
				$entry = $this->parent;
			}
			
			if($this->object_code) {
				$start_index = 1;
				$end_index = $this->handler->globals->IndexMaxRandomChildren();
				$orderby = 'RAND()';
			} else {
				$orderby = '';
				$start_index = 0;
				$end_index = 0;
			}
			
			$where = [];
			
			if($this->where) {
				$where = $this->where;
			}
			
			$get_record_children_args = [
				'entry'=>$entry,
				'startindex'=>$start_index,
				'endindex'=>$end_index,
				'orderby'=>$orderby,
				'where'=>$where,
			];
			
			$children_random = $this->orm->GetRecordChildren($get_record_children_args);
			
			return $this->children_random = $this->FilterValidChildren(['children'=>$children_random]);
		}
		
		public function FilterValidChildren($args) {
			$children = $args['children'];
			
			$new_children = [];
			
			foreach($children as $child) {
				if($child && is_array($child) && $child['id']) {
					$new_children[] = $child;
				}
			}
			
			return $new_children;
		}
		
		public function GetEntryDisplayName($args) {
			$entry = $args['entry'];
			
			$entry_display = $entry['Title'];
			
			if(strlen($entry['Subtitle'])) {
				if(strlen($entry_display)) {
					$entry_display .= ': ';
				}
				
				$entry_display .= $entry['Subtitle'];
			}
			
			return $entry_display;
		}
		
		public function GetEntryViewURL($args) {
			$entry_list = $args['entrylist'];
			$script = $args['scriptname'];
			
			if(!$script) {
				$script = 'view.php';
			}
			
			$url = $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]);
			
			if($this->parent['id'] && count($entry_list)) {
				$locations = [];
				
				foreach ($entry_list as $entry) {
					$locations[] = $entry['Code'];
				}
				
				$location = implode('/', $locations) . '/';
			} else {
				$location = '';
			}
			
			$url .= '/' . $location . $script;
			
			return $url;
		}
		
		public function GetHyperlinkedEntryView($args) {
			$entry = $args['entry'];
			
			if(!strlen($entry['id'])) {
				return $this->GetHyperlinkedMainIndex($args);
			}
			
			$entry_list = $args['entrylist'];
			
			$entry_title_args = [
				'entry'=>$entry,
			];
			
			$entry_title = $this->GetEntryDisplayName($entry_title_args);
			
			$entry_view_url_args = [
				'entrylist'=>$entry_list,
				'scriptname'=>$args['scriptname'],
			];
			
			$entry_url = $this->GetEntryViewURL($entry_view_url_args);
			
			return '<a href="' . $entry_url . '">' . $entry_title . '</a>';
		}
		
		public function GetHyperlinkedMainIndex($args) {
			return '<a href="' . $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/' . '">Main Site</a>';
		}
		
		public function ValidateOrm() {
			if(count($this->object_list)) {	# is this not the main page?
				if(count($this->object_list) !== count($this->record_list)) {
					$this->errors[] = ['This is not a valid combination of record codes: ' . implode(', ', $this->object_list) . '.'];
					return FALSE;
				}
			}
			
			return TRUE;
		}
		
		public function SearchForEntries($args) {
			return $this->orm->SearchForEntries($args);
		}
		
		public function SetEntryFromCode() {
			if($this->entry) {
				return $this->entry;
			}
			
			if($this->parent && $this->parent['id']) {
				return $this->entry = [
					'id'=>$this->parent['id'],
					'Title'=>$this->parent['Title'],
					'Subtitle'=>$this->parent['Subtitle'],
					'ListTitle'=>$this->parent['ListTitle'],
					'ListTitleSortKey'=>$this->parent['ListTitleSortKey'],
					'Code'=>$this->parent['Code'],
					'ChildAdjective'=>$this->parent['ChildAdjective'],
					'ChildNoun'=>$this->parent['ChildNoun'],
					'ChildNounPlural'=>$this->parent['ChildNounPlural'],
				];
			} else {
				$this->entry = $this->master_record;
				return TRUE;
			}
		}
		
		public function SetMasterRecord() {
			$master_records = $this->orm->GetMasterRecord();
			
			if($master_records && count($master_records) !== 0) {
				$master_record = $master_records[0];
			} else {
				$master_record = [
					'id'=>0,
					'Title'=>'Untitled',
					'Subtitle'=>'',
					'ListTitle'=>'',
					'ListTitleSortKey'=>'',
					'Code'=>'',
					'ChildAdjective'=>'',
					'ChildNoun'=>'',
					'ChildNounPlural'=>'',
				];
			}
			
			$this->master_record = $master_record;
			
			if($this->master_record && $this->master_record['id'] && $this->master_record['Code']) {
				if(mb_strtolower($this->master_record['Code']) === $this->domain_object->primary_domain_lowercased) {
					$this->domain_object->primary_domain = $this->master_record['Code'];
				}
			}
		}
		
		public function CleanseRecordFromQuery($args) {
			$variable_name = $args['variablename'];
			$data_structure = $args['datastructure'];
			
			$original_name = $variable_name . '_original';
			
			$this->$original_name = $this->$variable_name;
			$array = $this->$variable_name;
			
			if(is_array($this->$variable_name) && is_array($array)) {
				$new_record_array = [];
				
				foreach ($this->$variable_name as $record) {
					$new_record = [];
					
					foreach ($data_structure as $record_field) {
						$new_record[$record_field] = $record[$record_field];
					}
					
					$new_record_array[] = $new_record;
				}
				
				$this->$variable_name = $new_record_array;
			} else {
				$this->$variable_name = [];
				
				$new_record = [];
				
				foreach ($data_structure as $record_field) {
					$new_record[$record_field] = $this->CleanseForDisplay($new_record[$record_field]);
				}
				$this->$variable_name = $new_record;
			}
			
			return $this->$variable_name;
		}
		
		public function SetRecordFromQuery($args) {
			$variable_name = $args['variablename'];
			$file = $args['file'];
			$object_definition = $args['objectdefinition'];
			
			$variable_name_unset = $variable_name . '_unset';
			$this->$variable_name_unset = $this->$variable_name;
			
			$new_records = [];
			
			foreach($object_definition as $field) {
				$queryed_value = $this->Param($field);
				
				$i = 0;
				
				if(is_array($queryed_value) && count($queryed_value)) {
					foreach($queryed_value as $record_value) {
						if(!$new_records[$i]) {
							$new_records[$i] = [];
						}
						
						$save_field = $field;
						
						$save_field = preg_replace('/^' . $variable_name . '_/', '', $save_field);
						
						$new_records[$i][$save_field] = $record_value;
						$i++;
					}
				} else {
					switch($field) {
						case 'AvailabilityStartDate':
						case 'AvailabilityEndDate':
							$new_records[$i][$field] = '0000-00-00';
							break;
							
						case 'AvailabilityStartTime':
						case 'AvailabilityEndTime':
							$new_records[$i][$field] = '00:00:00';
							break;
							
						default:
							$new_records[$i][$field] = '';
							break;
					}
				}
			}
			
			if($file) {
				$file_variable_name = $variable_name . '_files';
				
				$files = [];
				$uploaded_files = $_FILES[$file];
				
				for($i = 0; $i < count($new_records); $i++) {
					$new_file = [
						'name'=>$uploaded_files['name'][$i],
						'type'=>$uploaded_files['type'][$i],
						'tmp_name'=>$uploaded_files['tmp_name'][$i],
						'error'=>$uploaded_files['error'][$i],
						'size'=>$uploaded_files['size'][$i],
					];
					
					if($uploaded_files['name'][$i]) {
						if(isset($new_records[$i]['FileName'])) {
							if(!strlen($new_records[$i]['FileName'])) {
								$new_records[$i]['FileName'] = $uploaded_files['name'][$i];
							}
							/* this breaks on brand-new entries without an id
							$prefix = $this->entry['id'] . '-';
							
							if($new_records[$i]['FileName'] && !$this->startsWith($new_records[$i]['FileName'], $prefix)) {
								$new_records[$i]['FileName'] = $prefix . $new_records[$i]['FileName'];
							}
							*/
							
							$selected_filename_pieces = explode('.', $new_records[$i]['FileName']);
							$uploaded_filename_pieces = explode('.', $uploaded_files['name'][$i]);
							
							$last_selected_filename_piece = $selected_filename_pieces[count($selected_filename_pieces) - 1];
							$last_uploaded_filename_piece = $uploaded_filename_pieces[count($uploaded_filename_pieces) - 1];
							
							if($last_selected_filename_piece != $last_uploaded_filename_piece) {
								$new_file['icon_name'] = $this->updateFileName(['filename'=>$new_records[$i]['FileName']]) . '-icon.' . $last_uploaded_filename_piece;
								$new_file['standard_name'] = $this->updateFileName(['filename'=>$new_records[$i]['FileName']]) . '-standard.' . $last_uploaded_filename_piece;
								$new_records[$i]['FileName'] .= '.' . $last_uploaded_filename_piece;
							} else {
								if(count($selected_filename_pieces) > 1) {
									unset($selected_filename_pieces[count($selected_filename_pieces) - 1]);
								}
								
								$selected_filename_imploded = implode(".", $selected_filename_pieces);
								$new_file['icon_name'] = $this->updateFileName(['filename'=>$selected_filename_imploded]) . '-icon.' . $last_uploaded_filename_piece;
								$new_file['standard_name'] = $this->updateFileName(['filename'=>$selected_filename_imploded]) . '-standard.' . $last_uploaded_filename_piece;
							}
						} else {
							$alternate_filenames = $this->makeAlternateFileNames(['filename'=>$uploaded_files['name'][$i]]);
							$new_file['icon_name'] = $alternate_filenames['icon_name'];
							$new_file['standard_name'] = $alternate_filenames['standard_name'];
						}
					}
					
					$files[] = $new_file;
				}
				
				$this->$file_variable_name = $files;
			}
			
			return $this->$variable_name = $new_records;
		}
		
		public function updateFileName($args) {
			$filename = $args['filename'];
			$prefix = $this->entry['id'] . '-';
			
			if(!$this->startsWith($filename, $prefix)) {
				$filename = $prefix . $filename;
			}
			
			return $filename;
		}
		
		public function startsWith( $haystack, $needle ) {
			$length = strlen($needle);
			return substr( $haystack, 0, $length ) === $needle;
		}
		
		public function makeAlternateFileNames($args) {
			$filename = $args['filename'];
			
			$uploaded_filename_pieces = explode(".", $filename);
			$uploaded_filename_count = count($uploaded_filename_pieces);
			
			$last_uploaded_filename_piece = $uploaded_filename_pieces[$uploaded_filename_count - 1];
			
			if($uploaded_filename_count > 1) {
				unset($uploaded_filename_pieces[$uploaded_filename_count - 1]);
			}
			
			$base_filename = implode(".", $uploaded_filename_pieces);
			
			$new_filenames = [];
			
			$new_filenames['icon_name'] = $base_filename . '-icon.' . $last_uploaded_filename_piece;
			$new_filenames['standard_name'] = $base_filename . '-standard.' . $last_uploaded_filename_piece;
			
			return $new_filenames;
		}
		
		public function OrderAndFillChildRecords() {
			$this->FillChildRecords();
	#		$this->PrepareChildRecords();
			$this->OrderChildRecords();
			
			return TRUE;
		}
		
		public function SaveComments() {
			if($this->entry && $this->entry['id']) {
				if($this->authentication_object->user_session['User.id']) {
					$comments = trim($this->Param('Comments'));
					
					if($comments) {
						$comment_definition = [
							'Entryid'=>$this->entry['id'],
							'Userid'=>$this->authentication_object->user_session['User.id'],
							'Comment'=>$comments,
							'Language'=>$this->language_object->getLanguageCode(),
						];
						
						$comment_get_args = [
							'type'=>'Comment',
							'definition'=>$comment_definition,
						];
						
						$comment_records = $this->handler->db_access->GetRecords($comment_get_args);
						
						if(!count($comment_records)) {
							$comment_definition['Approved'] = 0;
							$comment_definition['IPAddress'] = $_SERVER['REMOTE_ADDR'];
							
							if(!$this->authentication_object->user_session['User.Username']) {
								$new_username = strip_tags($this->Param('Username'));
								
								if($new_username) {
									$user_get_args = [
										'type'=>'User',
										'definition'=>[
											'Username'=>$new_username,
										],
									];
									
									$user_records = $this->handler->db_access->GetRecords($user_get_args);
									
									if(count($user_records)) {
										$this->username_record_conflict = $user_records;
									} else {
										$user_update_args = [
											'type'=>'User',
											'update'=>[
												'Username'=>$new_username,
											],
											'where'=>[
												'id'=>$this->authentication_object->user_session['User.id'],
											],
										];
										
										$updated_user = $this->handler->db_access->UpdateRecord($user_update_args);
										
										if($updated_user) {
											$this->authentication_object->user_session['User.Username'] = $new_username;
										}
									}
								}
							}
							
							if($this->isUserLoggedIn()) {
								$comment_insert_args = [
									'type'=>'Comment',
									'definition'=>$comment_definition,
								];
								
								return $this->comment_results = $this->handler->db_access->CreateRecord($comment_insert_args);
							}
						}
					}
				}
			}
			
			return TRUE;
		}
		
		public function SetComments() {
			if($this->handler->abstractglobals->child_types->Comment_enabled() && $this->entry && $this->entry['id']) {
				$comment_definition = [
					'Entryid'=>$this->entry['id'],
					'Approved'=>1,
					'Rejected'=>0,
				];
				
				$comment_get_args = [
					'type'=>'Comment',
					'definition'=>$comment_definition,
				];
				
				if($this->handler->db_access->db_file_cache) {
					$comments = $this->handler->db_access->db_file_cache->ReadCache([
						'type'=>'ggcms_Comments_approved',
						'arguments'=>[$this->entry['id']],
					]);
				} else {
					$comments = FALSE;
				}
				
				if(!is_array($comments)) {
					$comments = $this->handler->db_access->GetRecords($comment_get_args);
					
					if($this->handler->db_access->db_file_cache) {
						$this->handler->db_access->db_file_cache->WriteCache([
							'type'=>'ggcms_Comments_approved',
							'arguments'=>[$this->entry['id']],
							'data'=>$comments,
						]);
					}
				}
				
				$this->comments = $this->SetRecordUsers(['records'=>$comments]);
			}
			
			return TRUE;
		}
		
		/*
			Whether this site renders one of the expensive relational walks.
			The switches and their reasoning are in
			etc/ggcms/clonefrom/record_relations/enabled.php.

			Absent config answers TRUE for everything.  A deployment that has
			not shipped that directory yet, or a config file that names its
			class wrongly -- which NewConfigClass turns into an absent object
			rather than a fatal -- then behaves exactly as the engine did
			before these switches existed.  The cost of that is a slow page,
			and the cost of the other default would be missing content.
		*/

		public function RecordRelationEnabled($args) {
			$name = $args['name'];

			$record_relations = $this->handler->abstractglobals->record_relations;

			if(!$record_relations) {
				return TRUE;
			}

			$method = $name . '_enabled';

			if(!method_exists($record_relations, $method)) {
				return TRUE;
			}

			return $record_relations->$method();
		}

		public function SetSiblings($args) {
			if(count($this->record_list)) {
				$siblings = $this->GetSiblings($args);
				
				$younger_siblings = $siblings['younger'];
				$older_siblings = $siblings['older'];
				
				if($younger_siblings && count($younger_siblings) !== 0) {
					$younger_siblings = $this->GetChildrenForRecords(['records'=>$younger_siblings]);
					$younger_siblings = $this->SetAssociationRecordsForEntries(['entries'=>$younger_siblings]);
				}
				
				if($older_siblings && count($older_siblings) !== 0) {
					$older_siblings = $this->GetChildrenForRecords(['records'=>$older_siblings]);
					$older_siblings = $this->SetAssociationRecordsForEntries(['entries'=>$older_siblings]);
				}
				
				$this->younger_siblings = $younger_siblings;
				$this->older_siblings = $older_siblings;
			}
			
			return TRUE;
		}
		
		public function GetChildrenForRecords($args) {
			$records = $args['records'];
			
			$records_count = count($records);
			
			for($i = 0; $i < $records_count; $i++) {
				$record = $records[$i];
				$get_record_children_args = [
					'entry'=>$record,
				];
				
				$record_children_count = $this->orm->GetRecordChildrenCount($get_record_children_args);
				if($record_children_count <= 100) {	// stop memory overmax
					$record_children = $this->orm->GetRecordChildren($get_record_children_args);
					$record_children = $this->FilterValidChildren(['children'=>$record_children]);
					
					$records[$i]['children'] = $record_children;
				} else {
					$records[$i]['children'] = [];
				}
			}
			
			return $records;
		}
		
		public function GetSiblings($args) {
			$entry = $args['entry'];
			$parent = $args['parent'];
			
			if(!$entry || !$entry['id']) {
				$entry = $this->entry;
			}
			
			if(!$parent || !$parent['id']) {
				$parent = $this->parent;
			}
			
			if(!$parent || !$parent['id']) {
				$parent = $this->master_record;
			}
			
			if(!$entry || !$entry['id'] || !$parent || !$parent['id']) {
				return FALSE;
			}
			
			return $this->orm->GetSiblings(['entry'=>$entry, 'parent'=>$parent]);
		}
		
		public function SetRecordUsers($args) {
			$records = $args['records'];
			
			if(count($records) < 1) {
				return [];
			}
			$useridlist = [];
			
			foreach($records as $record) {
				$useridlist[$record['Userid']] = $record['Userid'];
			}
			
			$userids = [];
			
			foreach($useridlist as $userid) {
				$userids[] = $userid;
			}
		
			$user_args = [
				'type'=>'User',
				'definition'=>[
					'RAW'=>[
						'id'=>[
							'IN',
							'(' . implode(',', $userids) . ')',
						],
					],
				],
			];
			
			$users = $this->handler->db_access->GetRecords($user_args);
			
			$userlist = [];
			
			foreach($users as $user) {
				if($user && $user['id']) {
					unset($user['Password']);
				}
				$userlist[$user['id']] = $user;
			}
			
			foreach($records as $record_key => $record_record) {
				$record_record['user'] = $userlist[$record_record['Userid']];
				$records[$record_key] = $record_record;
			}
			
			return $records;
		}
		
		public function SetLimitedRecordEntries($args) {
			$comments = $args['records'];
			
			if(count($comments) < 1) {
				return [];
			}
			
			$entryidlist = [];
			
			foreach($comments as $comment) {
				$entryidlist[$comment['Entryid']] = $comment['Entryid'];
			}
			
			$entryids = [];
			
			foreach($entryidlist as $entryid) {
				$entryids[] = $entryid;
			}
			
			$entry_args = [
				'type'=>'Entry',
				'definition'=>[
					'RAW'=>[
						'id'=>[
							'IN',
							'(' . implode(',', $entryids) . ')',
						],
					],
				],
			];
			
			$entries = $this->handler->db_access->GetRecords($entry_args);
			
			foreach($entries as $entry) {
				if($entry && $entry['id']) {
					unset($entry['Password']);
					
					$entry_parents = $this->orm->GetEntryParents(['entry'=>$entry]);
					
					$entry['parents'] = $entry_parents['parents'];
				}
				$entrylist[$entry['id']] = $entry;
			}
			
			foreach($comments as $comment_key => $comment_record) {
				$comment_record['entry'] = $entrylist[$comment_record['Entryid']];
				$comments[$comment_key] = $comment_record;
			}
			
			return $comments;
		}
		
		public function SetRecordEntries($args) {
			$comments = $args['records'];
			
			if(count($comments) < 1) {
				return [];
			}
			
			$entryidlist = [];
			
			foreach($comments as $comment) {
				$entryidlist[$comment['Entryid']] = $comment['Entryid'];
			}
			
			$entryids = [];
			
			foreach($entryidlist as $entryid) {
				$entryids[] = $entryid;
			}
			
			$entry_args = [
				'type'=>'Entry',
				'definition'=>[
					'RAW'=>[
						'id'=>[
							'IN',
							'(' . implode(',', $entryids) . ')',
						],
					],
				],
			];
			
			$entries = $this->handler->db_access->GetRecords($entry_args);
			$entries = $this->GetChildRecords(['entries'=>$entries]);
			$this->children = $entries;
			$this->SetChildRecordsOfChildren();
			$entries = $this->children;
			
			$entrylist = [];
			
			foreach($entries as $entry) {
				if($entry && $entry['id']) {
					unset($entry['Password']);
					
					$entry_parents = $this->orm->GetEntryParents(['entry'=>$entry]);
					
					$entry['parents'] = $entry_parents['parents'];
				}
				$entrylist[$entry['id']] = $entry;
			}
			
			foreach($comments as $comment_key => $comment_record) {
				$comment_record['entry'] = $entrylist[$comment_record['Entryid']];
				$comments[$comment_key] = $comment_record;
			}
			
			return $comments;
		}
		
		public function SaveRecordFromQuery_Base($args) {
			$object_name = $args['objectname'];
			$object_type = $args['objecttype'];
			
			$unsaved_object_name = $object_name . '_unsaved';
			$this->$unsaved_object_name = $this->$object_name;
			
			$object = $this->$object_name;
			
			if(is_array($object) && is_array($object[0])) {
				$objects_saved = [];
				
				foreach($object as $object_item) {
					if(!$object_item) { continue;}
					if($this->handler->desired_action === 'Update' && !$this->isUserAdmin()) {
						$object_item['id'] = 0;
						$object_item['Entryid'] = $this->entry['id'];
						unset($object_item['LastModificationDate']);
						unset($object_item['OriginalCreationDate']);
					}
					if(!$object_item['Entryid'] && $object_type !== 'Assignment') {
						$object_item['Entryid'] = $this->entry['id'];
					}
					
					$save = 0;
					
					foreach($object_item as $object_key => $object_value) {
						if(strlen($object_item[$object_key]) && ($object_key !== 'EventDateTime' && $object_item[$object_key] !== '0000-00-00 00:00:00') && $object_key !== 'Language' && $object_key !== 'id' && $object_key !== 'Entryid') {
							$save = 1;
						}
					}
					
					if($object_item['swapped']) { # FIXME:
						# why is this here?  why is this causing an error?
					#	print_r($object_item['swapped']);
						if(!is_string($object_item)) {
							unset($object_item['swapped']);
						}
					}
					
					unset($object_item['EventDate']);
					unset($object_item['EventTime']);
					
					if($save) {
						if($object_item['id']) {
							$object_update_args = [
								'type'=>$object_type,
								'update'=>$object_item,
								'where'=>[
									'id'=>$object_item['id'],
								],
							];
							
							$object = $this->handler->db_access->UpdateRecord($object_update_args);
							
							if($object['error']) {
								$this->admin_errors[] = $object;
								$this->errors[] = ['There was a problem with saving the ' . $object_type . '.'];
								
								return FALSE;
							} else {
								$objects_saved[] = $object[0];
							}
						} else {
							if(!$args['noentryid']) {
								$object_item['Entryid'] = $this->entry['id'];
							}
							$object_insert_args = [
								'type'=>$object_type,
								'definition'=>$object_item,
							];
							
							$object = $this->handler->db_access->CreateRecord($object_insert_args);
							
							if($object['error']) {
								$this->admin_errors[] = $object;
								$this->errors[] = ['There was a problem with saving the ' . $object_type . '.'];
								
								return FALSE;
							} else {
								$objects_saved[] = $object;
							}
						}
					} else {
						$object_item['id'] = 0;
						$objects_saved[] = $object_item;
					}
				}
				
				if(count($objects_saved) !== 0) {
					$this->$object_name = $objects_saved;
				}
			} else {
				$save = 0;
				
				if($this->handler->desired_action === 'Edit' && !$this->isUserAdmin()) {
					$object['id'] = 0;
					unset($object['LastModificationDate']);
					unset($object['OriginalCreationDate']);
				}
				if(!$object['Entryid'] && $object_type !== 'Assignment') {
					$object['Entryid'] = $this->entry['id'];
				}
				foreach($object as $object_key => $object_value) {
					if(strlen($object[$object_key])) {
						$save = 1;
					}
				}
				
				if($save) {
			#		print("BT: *B* Object type...|" . $object_type . "|...has an id of...|" . $object['id'] . "|<BR><BR>");
					if($object['id']) {
			#			print("(BETA) UPDATE FOR TYPE...|" . $object_type . "|<br><br>");
						$object_update_args = [
							'type'=>$object_type,
							'update'=>$object,
							'where'=>[
								'id'=>$object['id'],
							],
						];
						
						$object = $this->handler->db_access->UpdateRecord($object_update_args);
						if($object['error']) {
							$this->admin_errors[] = $object;
							$this->errors[] = ['There was a problem with saving the ' . $object_type . '.'];
							
							return FALSE;
						} else {
							$this->$object_name = $object[0];
						}
					} else {
						if(!$args['noentryid']) {
							$object['Entryid'] = $this->entry['id'];
						}
						
						$object_insert_args = [
							'type'=>$object_type,
							'definition'=>$object,
						];
						
						$object = $this->handler->db_access->CreateRecord($object_insert_args);
						if($object['error']) {
							$this->admin_errors[] = $object;
							$this->errors[] = ['There was a problem with saving the ' . $object_type . '.'];
							
							return FALSE;
						} else {
							$this->$object_name = $object;
						}
					}
				}
			}
			
			return $this->$object_name;
		}
		
		public function FillChildRecords() {
			$child_records = $this->GetChildRecords(['entries'=>[$this->entry]]);
			
			$child_record_types = [
				'entrytranslation',
				'description',
				'quote',
				'textbody',
				'image',
				'imagetranslation',
				'tag',
				'link',
				'eventdate',
				'availabilitydaterange',
				'association',
				'entrypermission',
				'definition',
			];
			
			foreach($child_record_types as $child_record_type) {
				$this->$child_record_type = $child_records[0][$child_record_type];
				
				if(!$this->$child_record_type || !count($this->$child_record_type)) {
					$this->$child_record_type = [];
				}
			}
		}
		
		public function FillBlankChildRecords() {
			if(!$this->description || !count($this->description)) {
				$this->description = [
					[
						'Description'=>'',
					],
				];
			}
			
			if(!$this->quote || !count($this->quote)) {
				$this->quote = [
					[
						'Quote'=>'',
					],
				];
			}
			
			if(!$this->textbody || !count($this->textbody)) {
				$this->textbody = [
					[
						'Text'=>'',
					],
				];
			}
			
			return TRUE;
		}
		
		public function OrderChildRecords() {
			$this->OrderChildRecords_AvailabilityDateRange();
			$this->OrderChildRecords_EventDate();
			
			return TRUE;
		}
		
		public function OrderChildRecords_AvailabilityDateRange() {
			$new_availability_date_ranges = [];
			
			foreach($this->availabilitydaterange as $availability_date_range) {
				$availability_date_range_start_time_pieces = explode(' ', $availability_date_range['AvailabilityStart']);
				$availability_date_range['AvailabilityStartDate'] = $availability_date_range_start_time_pieces[0];
				$availability_date_range['AvailabilityStartTime'] = $availability_date_range_start_time_pieces[1];
				
				$availability_date_range_end_time_pieces = explode(' ', $availability_date_range['AvailabilityEnd']);
				$availability_date_range['AvailabilityEndDate'] = $availability_date_range_end_time_pieces[0];
				$availability_date_range['AvailabilityEndTime'] = $availability_date_range_end_time_pieces[1];
				
				$new_availability_date_ranges[] = $availability_date_range;
			}
			
			$this->availabilitydaterange = $new_availability_date_ranges;
		}
		
		public function OrderChildRecords_EventDate() {
			$new_event_dates = [];
			
			foreach($this->eventdate as $event_date) {
				$event_date_time_pieces = explode(' ', $event_date['EventDateTime']);
				$event_date['EventDate'] = $event_date_time_pieces[0];
				$event_date['EventTime'] = $event_date_time_pieces[1];
				
				$new_event_dates[] = $event_date;
			}
			
			$this->eventdate = $new_event_dates;
		}
		
		public function GetChildRecords($args) {
			$entries = $args['entries'];
			
			$get_entry_child_records_args = [
				'entrieslist'=>$entries,
				'notextbodies'=>$args['notextbodies'],
			];
			
			return $this->orm->GetRecordTree_GetEntryChildRecords($get_entry_child_records_args);
		}
		
		public function GetCreatedRecordsList() {
			$entry_record_types = $this->GetEntryRecordTypes();
			$entry_record_types['Assignment'] = 'assignment';
			
			$created_records_list = [];
			
			foreach($entry_record_types as $entry_table_name => $entry_attribute_name) {
				$full_table_name = $entry_attribute_name . '_unformatted';
				$record = $this->$full_table_name;
				
				if(!$record) {
					$record = $this->$entry_attribute_name;
				}
				
				if($entry_attribute_name == 'entry' || $entry_attribute_name == 'assignment') {
					if($record['id']) {
						$created_records_list[] = [[
							'contents'=>$this->Bullet() . ' ' . $entry_table_name . ' ~ id #' . $record['id'],
							'mouseover'=>$this->RecordToString(['record'=>$record]),
						]];
					}
				} else {
					if(count($record)) {
						foreach($record as $record_multiple) {
							if($record_multiple && $record_multiple['id']) {
								$created_records_list[] = [[
									'contents'=>$this->Bullet() . ' ' . $entry_table_name . ' ~ id #' . $record_multiple['id'],
									'mouseover'=>$this->RecordToString(['record'=>$record_multiple]),
								]];
							}
						}
					}
				}
			}
			
			return $created_records_list;
		}
		
		public function RecordToString($args) {
			$record = $args['record'];
			
			$record_string_pieces = [];
			
			foreach ($record as $field => $value) {
				$record_string_pieces[] = $field . ' : ' . $value;
			}
			
			$record_string = implode(' -- ', $record_string_pieces);
			
			return $record_string;
		}
		
		public function FormatSavedRecordFromQuery_Base($args) {
			$record_type = $args['recordtype'];
			$record_fields = $args['recordfields'];
			
			$unformatted_record_name = $record_type . '_unformatted';
			$this->$unformatted_record_name = $this->$record_type;
			$record = $this->$record_type;
			
			if(is_array($record) && is_array($record[0])) {
				foreach($record as $record_key => $record_object) {
					foreach ($record_fields as $record_field) {
						if(strlen($record_object[$record_field])) {
							$text_to_format_args = [
								'texttoformat'=>$record[$record_key][$record_field],
							];
							
							$record[$record_key][$record_field] = $this->FormatSavedRecordFromQuery_Base_SingleLine($text_to_format_args);
						}
					}
				}
			} elseif ($record) {
				foreach ($record_fields as $record_field) {
					$text_to_format_args = [
						'texttoformat'=>$record[$record_field],
					];
					
					$record[$record_field] = $this->FormatSavedRecordFromQuery_Base_SingleLine($text_to_format_args);
				}
			}
			
			$this->$record_type = $record;
			
			return $this->$record_type;
		}
		
		public function GetEntryRecordTypes() {
			return $this->orm->GetAllEntryRecordTypes();
		}
		
		public function DeleteEntry() {
			$delete_entry_args = [
				'entry'=>$this->entry,
			];
			
			return $this->orm->DeleteEntry($delete_entry_args);
		}
		
		public function DeleteRecordTree($args) {
			return $this->orm->DeleteRecordTree($args);
		}
		
		public function GetChildRecordTypes() {
			$child_record_types = [
				'entrytranslation',
				'description',
				'quote',
				'textbody',
				'image',
				'imagetranslation',
				'availabilitydaterange',
				'tag',
				'link',
				'eventdate',
				'association',
				'entrypermission',
				'definition',
			];
			
			return $child_record_types;
		}
		
		public function GetStandardChildRecordTypes() {
			return $this->orm->GetStandardEntryRecordTypes();
		}
		
		public function GetEntryParents($args) {
			return $this->orm->GetEntryParents($args);
		}
		
		public function GetEntriesParents($args) {
			return $this->orm->GetEntriesParents($args);
		}
		
		public function SetLastAdd($args) {
			$entry = $args['entry'];
			
			if(!$entry || !$entry['id']) {
				$entry = $this->entry;
			}
			
			if(!$entry || !$entry['id']) {
				return FALSE;
			}
			
			$last_added_entry = $this->orm->GetLastChildAdded(['entry'=>$entry]);
			
			$last_added_entry = $this->GetChildRecords(['entries'=>[$last_added_entry], 'notextbodies'=>1]);
			
			return $this->last_added_entry = $last_added_entry[0];
		}
		
		public function SetLastEdit($args) {
			$entry = $args['entry'];
			
			if(!$entry || !$entry['id']) {
				$entry = $this->parent;
			}
			
			if(!$entry || !$entry['id']) {
				return FALSE;
			}
			
			return $this->last_edited_entry = $this->orm->GetLastChildEdited(['entry'=>$entry]);
		}
		
		public function BackupEntryCodeReservation($args) {
			$entry = $args['entry'];
			$old_entry = $args['old_entry'];
			$record_list = $args['record_list'];
			
			$codes = [];
			
			$record_list_count = count($record_list);
			
			for($i = 0; $i < $record_list_count; $i++) {
				$record = $record_list[$i];
				$codes[] = $record['Code'];
			}
			
			$assignment = $record_list[$record_list_count - 1]['assignment'][0];
			$codes = implode('/', $codes);
			
			$this->BackupEntryCodeReservation_BackupSingleReservation(['entry'=>$entry, 'assignment'=>$assignment, 'code'=>$codes]);
			$this->BackupEntryCodeReservation_BackupSingleReservation(['entry'=>$entry, 'assignment'=>$assignment, 'code'=>$old_entry['Code']]);
			
			$this->BackupEntryCodeReservation_BackupChildReservations(['entry'=>$entry, 'assignment'=>$assignment, 'code'=>$codes]);
			
			return TRUE;
		}
		
		public function BackupEntryCodeReservation_BackupChildReservations($args) {
			$code = $args['code'];
			
			foreach($this->children as $child) {
				$child_assignment = $child['assignment'][0];
				
				$child_code = $code . '/' . $child['Code'];
				$this->BackupEntryCodeReservation_BackupSingleReservation([
					'entry'=>$child,
					'assignment'=>$child_assignment,
					'code'=>$child_code,
				]);
			}
			
			return TRUE;
		}
		
		public function BackupEntryCodeReservation_BackupSingleReservation($args) {
			$entry = $args['entry'];
			$assignment = $args['assignment'];
			$code = $args['code'];
			
			$assignmentid = $assignment['id'];
			
			$reservation_record = [
				'Entryid'=>$entry['id'],
				'Assignmentid'=>$assignmentid,
				'Code'=>$code,
			];
			
			$get_record_where = [
				'type'=>'EntryCodeReservation',
				'limit'=>'1',
				'definition'=>$reservation_record,
			];
			$reservations = $this->handler->db_access->GetRecords($get_record_where);
			
			if(count($reservations) === 0) {
				if(!$this->new_reservation_record) {
					$this->new_reservation_record = [];
				}
				$reservation_insert_args = [
					'type'=>'EntryCodeReservation',
					'definition'=>$reservation_record,
				];
				
				$this->new_reservation_record[] = $this->handler->db_access->CreateRecord($reservation_insert_args);
				
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function FormatEntryInformation() {
			if($this->entry['entrypermission']) {
				$user_ids = [];
				
				$entry_permission_count = count($this->entry['entrypermission']);
				
				for($i = 0; $i < $entry_permission_count; $i++) {
					$entry_permission = $this->entry['entrypermission'][$i];
					
					$user_ids[] = $entry_permission['Userid'];
				}
				
				if($this->handler->db_access->db_file_cache) {
					$users = $this->handler->db_access->db_file_cache->ReadCache([
						'type'=>'ggcms_UserIds',
						'arguments'=>$user_ids,
					]);
				} else {
					$users = FALSE;
				}
				
				if(!is_array($users)) {
					$sql = 'SELECT id, Username, EmailAddress FROM User WHERE id IN(' . implode(',', array_fill(0, count($user_ids), '?')) . ')';
					$users = $this->handler->db_access->RunQuery([
						'sql'=>$sql,
						'args'=>$user_ids,
					]);
					
					if($this->handler->db_access->db_file_cache) {
						if(count($users) !== 0) {
							$this->handler->db_access->db_file_cache->WriteCache([
								'type'=>'ggcms_UserIds',
								'arguments'=>$user_ids,
								'data'=>$users,
							]);
						}
					}
				}
				
				$user_hash = [];
				
				foreach($users as $user) {
					$user_hash[$user['id']] = $user;
				}
				
				for($i = 0; $i < $entry_permission_count; $i++) {
					$entry_permission = $this->entry['entrypermission'][$i];
					$entry_permission['user'] = $user_hash[$entry_permission['Userid']];
					$this->entry['entrypermission'][$i] = $entry_permission;
				}
				
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function canUserAccess() {
			$user_id = $this->handler->authentication->user_account['id'];
			
			if($this->entry['Publish'] === 0 && !$this->isUserAdmin()) {
				if($this->entry['entrypermission'] && $this->entry['entrypermission'][0] && $this->entry['entrypermission'][0]['user'] && $this->entry['entrypermission'][0]['user']['id'] !== $user_id) {
					return FALSE;
				}
			}
			
			return TRUE;
		}
		
		public function setPrimaryChildren() {
			return $this->primary_children = $this->orm->getPrimaryChildren();
		}
		
		public function GenerateSmartTitle($args) {
			$title = $args['title'];
			
			$title = preg_replace('/\s+/', ' ', $title);
			$common_words_hash = $this->CommonWordsHash();
			
			$title_pieces = explode(' ', $title);
			$title_pieces_count = count($title_pieces);
			
			$new_title_pieces = [];
			
			for($i = 0; $i < $title_pieces_count; $i++) {
				$title_piece = $title_pieces[$i];
				
				if(!$common_words_hash[$title_piece] && !$this->starts_with_upper($title_piece)) {
					$title_piece = ucfirst($title_piece);
				}
				
				$new_title_pieces[] = $title_piece;
			}
			
			$new_title = implode(' ', $new_title_pieces);
			
			$separators = [
		                ': ',
		                '- ',
		                '_ ',
		                '; ',
			];
			
			foreach($separators as $separator) {
				$new_title_pieces = explode($separator, $new_title);
				foreach($new_title_pieces as &$new_title_piece) {
					$new_title_piece = ucfirst($new_title_piece);
				}
				$new_title = implode($separator, $new_title_pieces);
			}
			
			return $new_title;
			
			return $new_title;
		}
		
		function starts_with_upper($str) {
			$chr = mb_substr($str, 0, 1, 'UTF-8');
			
			return mb_strtolower($chr, 'UTF-8') !== $chr;
		}
		
		public function isWordCommon($args) {
			$word = $args['word'];
			
			$common_words_hash = $this->CommonWordsHash();
			
			if($common_words_hash[$word]) {
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function CommonWordsHash() {
			if($this->common_words_hash) {
				return $this->common_words_hash;
			}
			
			$common_words = $this->CommonWords();
			
			$common_words_hash = [];
			
			$common_words_count = count($common_words);
			
			for($i = 0; $i < $common_words_count; $i++) {
				$common_word = $common_words[$i];
				
				$common_words_hash[$common_word] = TRUE;
			}
			
			return $this->common_words_hash = $common_words_hash;
		}
		
		public function CommonWords() {
			$common_words = [
				'a',
			#	'able',
			#	'about',
			#	'above',
			#	'abroad',
				'according',
				'accordingly',
				'across',
				'actually',
				'adj',
			#	'after',
				'afterwards',
				'again',
			#	'against',
				'ago',
				'ahead',
				'ain\'t',
				'all',
				'allow',
				'allows',
				'almost',
			#	'alone',
				'along',
				'alongside',
				'already',
				'also',
				'although',
			#	'always',
				'am',
				'amid',
				'amidst',
			#	'among',
			#	'amongst',
				'an',
				'and',
				'another',
				'any',
			#	'anybody',
			#	'anyhow',
			#	'anyone',
			#	'anything',
				'anyway',
				'anyways',
				'anywhere',
				'apart',
				'appear',
			#	'appreciate',
			#	'appropriate',
				'are',
				'aren\'t',
				'around',
				'as',
				'a\'s',
				'aside',
				'ask',
				'asking',
			#	'associated',
				'at',
			#	'available',
			#	'away',
				'awfully',
			#	'b',
			#	'back',
			#	'backward',
			#	'backwards',
				'be',
				'became',
				'because',
			#	'become',
			#	'becomes',
			#	'becoming',
				'been',
				'before',
				'beforehand',
				'begin',
				'behind',
				'being',
			#	'believe',
				'below',
				'beside',
				'besides',
				'best',
			#	'better',
			#	'between',
				'beyond',
				'both',
			#	'brief',
				'but',
				'by',
			#	'c',
				'came',
				'can',
				'cannot',
				'cant',
				'can\'t',
			#	'caption',
			#	'cause',
			#	'causes',
			#	'certain',
				'certainly',
			#	'changes',
				'clearly',
				'c\'mon',
				'co',
				'co.',
				'com',
			#	'come',
			#	'comes',
				'concerning',
				'consequently',
				'consider',
				'considering',
			#	'contain',
			#	'containing',
			#	'contains',
			#	'corresponding',
				'could',
				'couldn\'t',
				'course',
				'c\'s',
				'currently',
			#	'd',
			#	'dare',
				'daren\'t',
				'definitely',
				'described',
				'despite',
				'did',
				'didn\'t',
				'different',
			#	'directly',
				'do',
				'does',
				'doesn\'t',
				'doing',
				'done',
				'don\'t',
				'down',
				'downwards',
				'during',
			#	'e',
				'each',
			#	'edu',
				'eg',
			#	'eight',
			#	'eighty',
				'either',
				'else',
				'elsewhere',
			#	'end',
				'ending',
				'enough',
				'entirely',
				'especially',
				'et',
				'etc',
				'even',
				'ever',
			#	'evermore',
			#	'every',
			#	'everybody',
			#	'everyone',
			#	'everything',
			#	'everywhere',
				'ex',
			#	'exactly',
			#	'example',
				'except',
			#	'f',
				'fairly',
				'far',
				'farther',
			#	'few',
			#	'fewer',
			#	'fifth',
			#	'first',
			#	'five',
			#	'followed',
			#	'following',
			#	'follows',
				'for',
			#	'forever',
				'former',
				'formerly',
				'forth',
			#	'forward',
				'found',
				'four',
				'from',
				'further',
				'furthermore',
			#	'g',
				'get',
				'gets',
				'getting',
				'given',
				'gives',
				'go',
				'goes',
				'going',
				'gone',
				'got',
				'gotten',
			#	'greetings',
			#	'h',
				'had',
				'hadn\'t',
				'half',
				'happens',
				'hardly',
				'has',
				'hasn\'t',
				'have',
				'haven\'t',
				'having',
				'he',
				'he\'d',
				'he\'ll',
			#	'hello',
				'help',
				'hence',
				'her',
				'here',
				'hereafter',
				'hereby',
				'herein',
				'here\'s',
				'hereupon',
				'hers',
			#	'herself',
				'he\'s',
				'hi',
				'him',
			#	'himself',
				'his',
				'hither',
				'hopefully',
				'how',
				'howbeit',
				'however',
			#	'hundred',
			#	'i',
				'i\'d',
				'ie',
				'if',
				'ignored',
				'i\'ll',
				'i\'m',
				'immediate',
				'in',
				'inasmuch',
				'inc',
				'inc.',
				'indeed',
				'indicate',
				'indicated',
				'indicates',
				'inner',
				'inside',
				'insofar',
				'instead',
				'into',
			#	'inward',
				'is',
				'isn\'t',
				'it',
				'it\'d',
				'it\'ll',
				'its',
				'it\'s',
				'itself',
			#	'i\'ve',
			#	'j',
			#	'just',
			#	'k',
			#	'keep',
				'keeps',
				'kept',
			#	'know',
			#	'known',
			#	'knows',
			#	'l',
			#	'last',
				'lately',
			#	'later',
				'latter',
				'latterly',
				'least',
			#	'less',
				'lest',
				'let',
				'let\'s',
			#	'like',
			#	'liked',
				'likely',
				'likewise',
			#	'little',
			#	'look',
				'looking',
				'looks',
			#	'low',
			#	'lower',
				'ltd',
			#	'm',
			#	'made',
				'mainly',
			#	'make',
			#	'makes',
			#	'many',
				'may',
			#	'maybe',
				'mayn\'t',
				'me',
			#	'mean',
				'meantime',
				'meanwhile',
				'merely',
			#	'might',
				'mightn\'t',
			#	'mine',
			#	'minus',
				'miss',
			#	'more',
				'moreover',
			#	'most',
				'mostly',
				'mr',
				'mrs',
				'much',
				'must',
				'mustn\'t',
			#	'my',
			#	'myself',
			#	'n',
				'name',
				'namely',
				'nd',
				'near',
				'nearly',
				'necessary',
			#	'need',
				'needn\'t',
			#	'needs',
			#	'neither',
			#	'never',
				'neverf',
			#	'neverless',
			#	'nevertheless',
			#	'new',
			#	'next',
			#	'nine',
			#	'ninety',
			#	'no',
			#	'nobody',
				'non',
			#	'none',
			#	'nonetheless',
			#	'noone',
			#	'no-one',
				'nor',
			#	'normally',
			#	'not',
			#	'nothing',
				'notwithstanding',
			#	'novel',
			#	'now',
			#	'nowhere',
			#	'o',
				'obviously',
				'of',
				'off',
				'often',
				'oh',
				'ok',
			#	'okay',
			#	'old',
				'on',
				'once',
				'one',
				'ones',
				'one\'s',
				'only',
				'onto',
				'opposite',
				'or',
				'other',
				'others',
				'otherwise',
				'ought',
				'oughtn\'t',
			#	'our',
			#	'ours',
			#	'ourselves',
				'out',
				'outside',
				'over',
				'overall',
			#	'own',
			#	'p',
			#	'particular',
				'particularly',
				'past',
				'per',
				'perhaps',
				'placed',
				'please',
				'plus',
				'possible',
				'presumably',
				'probably',
				'provided',
				'provides',
			#	'q',
				'que',
				'quite',
				'qv',
			#	'r',
				'rather',
				'rd',
				're',
				'really',
			#	'reasonably',
				'recent',
				'recently',
				'regarding',
				'regardless',
				'regards',
				'relatively',
				'respectively',
				'right',
				'round',
			#	's',
				'said',
				'same',
				'saw',
				'say',
				'saying',
				'says',
				'second',
				'secondly',
				'see',
				'seeing',
				'seem',
				'seemed',
				'seeming',
				'seems',
				'seen',
			#	'self',
			#	'selves',
			#	'sensible',
				'sent',
			#	'serious',
			#	'seriously',
				'seven',
				'several',
				'shall',
				'shan\'t',
				'she',
				'she\'d',
				'she\'ll',
				'she\'s',
				'should',
				'shouldn\'t',
				'since',
			#	'six',
				'so',
			#	'some',
			#	'somebody',
			#	'someday',
			#	'somehow',
			#	'someone',
			#	'something',
			#	'sometime',
			#	'sometimes',
			#	'somewhat',
			#	'somewhere',
			#	'soon',
			#	'sorry',
			#	'specified',
			#	'specify',
			#	'specifying',
				'still',
				'sub',
				'such',
				'sup',
				'sure',
			#	't',
				'take',
				'taken',
				'taking',
				'tell',
				'tends',
				'th',
				'than',
			#	'thank',
			#	'thanks',
			#	'thanx',
				'that',
				'that\'ll',
				'thats',
				'that\'s',
				'that\'ve',
				'the',
				'their',
				'theirs',
				'them',
				'themselves',
				'then',
				'thence',
				'there',
				'thereafter',
				'thereby',
				'there\'d',
				'therefore',
				'therein',
				'there\'ll',
				'there\'re',
				'theres',
				'there\'s',
				'thereupon',
				'there\'ve',
				'these',
				'they',
				'they\'d',
				'they\'ll',
				'they\'re',
				'they\'ve',
				'thing',
				'things',
				'think',
			#	'third',
				'thirty',
				'this',
				'thorough',
				'thoroughly',
			#	'those',
				'though',
				'three',
				'through',
				'throughout',
				'thru',
				'thus',
				'till',
				'to',
				'together',
				'too',
				'took',
				'toward',
				'towards',
				'tried',
				'tries',
				'truly',
				'try',
				'trying',
				't\'s',
			#	'twice',
			#	'two',
			#	'u',
				'un',
				'under',
				'underneath',
				'undoing',
				'unfortunately',
				'unless',
				'unlike',
				'unlikely',
				'until',
				'unto',
				'up',
				'upon',
				'upwards',
			#	'us',
			#	'use',
				'used',
				'useful',
				'uses',
				'using',
				'usually',
			#	'v',
				'value',
			#	'various',
			#	'versus',
				'very',
				'via',
				'viz',
				'vs',
			#	'w',
			#	'want',
			#	'wants',
				'was',
				'wasn\'t',
			#	'way',
				'we',
				'we\'d',
			#	'welcome',
				'well',
				'we\'ll',
				'went',
				'were',
				'we\'re',
				'weren\'t',
				'we\'ve',
				'what',
			#	'whatever',
				'what\'ll',
				'what\'s',
				'what\'ve',
				'when',
				'whence',
				'whenever',
				'where',
				'whereafter',
				'whereas',
				'whereby',
				'wherein',
				'where\'s',
				'whereupon',
				'wherever',
				'whether',
				'which',
				'whichever',
				'while',
				'whilst',
				'whither',
				'who',
				'who\'d',
			#	'whoever',
			#	'whole',
				'who\'ll',
			#	'whom',
			#	'whomever',
				'who\'s',
				'whose',
				'why',
				'will',
				'willing',
			#	'wish',
				'with',
			#	'within',
			#	'without',
			#	'wonder',
				'won\'t',
				'would',
				'wouldn\'t',
			#	'x',
			#	'y',
			#	'yes',
				'yet',
			#	'you',
			#	'you\'d',
			#	'you\'ll',
			#	'your',
			#	'you\'re',
			#	'yours',
			#	'yourself',
			#	'yourselves',
				'you\'ve',
			#	'z',
			#	'zero',
			];
			
			return $common_words;
		}
		
		public function RomanNumeralValues() {
			return [
				'I'=>1,
				'V'=>5,
				'X'=>10,
				'L'=>50,
				'C'=>100,
				'D'=>500,
				'M'=>1000,
			];
		}
		
		public function ConvertRomanNumeralToArabic($input_roman){
			$input_length = strlen($input_roman);
			if($input_length === 0) {
				return $result;
			}
			
			$roman_numerals = $this->RomanNumeralValues();
			
			$current_pointer = 1;
			$result = 0;
			
			for($i = $input_length - 1; $i > -1; $i--){ 
				$letter = $input_roman[$i];
				$letter_value = $roman_numerals[$letter];
				
				if($letter_value === $current_pointer) {
					$result += $letter_value;
				} elseif ($letter_value < $current_pointer) {
					$result -= $letter_value;
				} else {
					$result += $letter_value;
					$current_pointer = $letter_value;
				}
			}
			
			return $result;
		}
		
		function isRomanNumeral($number) {
			$number_count = strlen($number);
			
			$roman_numerals = $this->RomanNumeralValues();
			
			for($i = 0; $i < $number_count; $i++) {
				$letter = $number[$i];
				
				if(!array_key_exists(strtoupper($letter), $roman_numerals)) {
					return FALSE;
				}
			}
			
			return TRUE;
		}
		
		function ConvertSentenceRomanNumerals($args) {
			$sentence = $args['sentence'];
			$words = preg_split('/\s+/', $sentence);
			$words_count = count($words);
			
			for($i = 0; $i < $words_count; $i++) {
				$word = &$words[$i];
				if($this->isRomanNumeral($word)) {
					$word = $this->ConvertRomanNumeralToArabic($word);
				}
			}
			
			return implode(' ', $words);
		}
	}
?>
