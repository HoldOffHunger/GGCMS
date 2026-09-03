<?php

	classreq('scripts/view.php');

	class news extends view {
		public function display() {
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			$this->SetSocialMediaBasics();
			
			$this->SetNewestChildren();
			
			$this->CountRecords();
			
			$this->newest_entries = $this->GetChildRecords([
				'entries'=>$this->newest_entries,
				'notextbodies'=>1,
			]);
			$this->newest_entries = $this->orm->SetAssociationEntryParentRelatedRecords(['entries'=>$this->newest_entries]);
		#	$this->newest_entries = $this->SetAssociatedAssociationEntries([
		#		'entries'=>$this->newest_entries,
		#		'relating_key'=>'ChosenEntryid',
		#		'primary_key'=>'association',
		#	]);		# okay, diz is fine
			
			# TODO: abstractify SetAssociationRecords() in SimpleORM.php
			# -- Make GetRecordAndChildren() Work for Entryids
			# -- Fill GrandChild Records With Parent Associated Records Somehow for Image Purposes
			# -- Proceed
			
			return TRUE;
		}
		
		public function getNewsFeeds() {
			$this->setPrimaryChildren();
			$entry_ids = array_column($this->primary_children, 'id');
			$entry_ids[] = $this->master_record['id'];
			return $this->orm->buildRecordTreeForEntryids(['entryids'=>$entry_ids]);
		}
		
		public function docs() {
			$this->display();
			$this->news_feeds = $this->getNewsFeeds();
			$news_feed_hash = [];
			
			foreach($this->news_feeds as $news_feed) {
				$news_feed_hash[$news_feed[0]['id']] = $news_feed;
			}
			
			$this->news_feed = $news_feed_hash[$this->entry['id']];
			
			$formatted_news_feeds = [];
			
			$base_url = $this->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1])  . '/';
			
			foreach($this->news_feeds as $news_feed) {
				$news_feed_reversed = array_reverse($news_feed);
				$news_feed_url = $base_url;
				$news_feed_titles = [];
				
				foreach($news_feed_reversed as $news_feed_entry) {
					$news_feed_titles[] = $news_feed_entry['Title'];
					if($news_feed_entry['Assignmentid']) {
						$news_feed_url .= $news_feed_entry['Code'] . '/';
					}
					
					$last_entry = $news_feed_entry;
				}
				
				if($last_entry['Assignmentid']) {
					$entry_link = $news_feed_url . 'view.php?action=index';
				} else {
					$entry_link = $news_feed_url;
				}
				$docs_link = $news_feed_url . 'news.php?action=docs';
				$atom_link = $news_feed_url . 'news.atom';
				$news_feed_url .= 'news.rss';
				
			#	print_r($news_feed_titles);
			#	print($news_feed_url . "<BR><BR>");
				
				$formatted_news_feed = [
					'titles'=>$news_feed_titles,
					'url'=>$news_feed_url,
					'entryurl'=>$entry_link,
					'docsurl'=>$docs_link,
					'atomlink'=>$atom_link,
					'id'=>$last_entry,
				];
				
				$formatted_news_feeds[] = $formatted_news_feed;
				
				if($last_entry['id'] === $this->entry['id']) {
					$this->formatted_news_feed = $formatted_news_feed;
				}
			#	print_r($news_feed_reversed);
			}
			
			$this->formatted_news_feeds = $formatted_news_feeds;
			
		#	print("<PRE>");
		#	print_r($this->formatted_news_feeds);
		#	print_r($this->news_feeds);
		#	print_r($this->news_feed);
		#	print_r($this->entry);
		#	print_r($news_feed_hash);
		#	print("</PRE>");
			
			return TRUE;
		}
		
		public function GetHTMLFormatData_Title() {
			if($this->entry && $this->entry['id']) {
				$header_text = '';
				
				if($this->entry['Title']) {
					$header_text .= $this->entry['Title'];
				}
				
				if($this->entry['Subtitle']) {
					if(strlen($header_text) > 0) {
						$header_text .= ' : ';
					}
					
					$header_text .= $this->entry['Subtitle'];
				}
				
				if($this->desired_action === 'docs') {
					$base_header = 'News Feed Information';
				} else {
					$base_header = 'Newest Additions';
				}
				
				return $this->header_title_text = $base_header . ' &mdash; ' . $header_text;
			}
		}
		
		public function maxRecordsToReturn() {
			$items = (int)$this->Param('items');
			
			if($items >= $this->handler->globals->minNewsItemsAllowed() && $items <= $this->handler->globals->maxNewsItemsAllowed()) {
				return $items;
			}
			
			if($this->handler->script_format_lower === 'rss') {
				if($this->format->version_float <= 0.91) {
					return $this->defaultVersion091DefaultNewsItems();
				} else {
					return $this->handler->globals->defaultNewsItems_RSS();
				}
			}
			
			return $this->handler->globals->defaultNewsItems();
		}
		
		public function defaultVersion091DefaultNewsItems() {
			return 15;
		}
		
		public function SetNewestChildren() {
			$sql = '';
			$sql_args = [];
			if($this->master_record['id'] === $this->entry['id']) {
				$sql .= 'SELECT Entry.*, COUNT(ChildAssignment.id) as Count, ';
				$sql .= 'Parent.id as Parent_id, Parent.Title as Parent_Title, Parent.Code as Parent_Code, ';
				$sql .= 'GrandParent.id as GrandParent_id, GrandParent.Title as GrandParent_Title, GrandParent.Code as GrandParent_Code, ';
				$sql .= 'ChildAssignment.id as PermaLinkid ';
				
				$sql .= 'FROM Entry ';
				
				$sql .= 'LEFT JOIN Assignment ChildAssignment ON ChildAssignment.Childid = Entry.id ';
				$sql .= 'LEFT JOIN Entry Parent ON Parent.id = ChildAssignment.Parentid ';
				
				$sql .= 'LEFT JOIN Assignment GrandChildAssignment ON GrandChildAssignment.Childid = Parent.id ';
				$sql .= 'LEFT JOIN Entry GrandParent ON GrandParent.id = GrandChildAssignment.Parentid ';
				
				$sql .= 'WHERE Entry.Publish = 1 ';
				$sql .= 'GROUP BY Entry.id, ChildAssignment.id, GrandChildAssignment.id ';
				$sql .= 'ORDER BY Entry.OriginalCreationDate DESC ';
				$sql .= 'LIMIT ' . $this->maxRecordsToReturn();
			} else {
				$sql .= 'SELECT Entry.*, COUNT(ChildAssignment.id) as Count, Assignment.id as PermaLinkid ';
				
				$sql .= 'FROM Entry ';
				
				$sql .= 'JOIN Assignment ON Assignment.Parentid = ? AND Assignment.Childid = Entry.id ';
				$sql .= 'LEFT JOIN Assignment ChildAssignment ON ChildAssignment.Parentid = Entry.id ';
				
				$sql .= 'WHERE Entry.Publish = 1 ';
				$sql .= 'GROUP BY Entry.id, Assignment.id ';
				$sql .= 'ORDER BY Entry.OriginalCreationDate DESC ';
				$sql .= 'LIMIT ' . $this->maxRecordsToReturn();
				
				$sql_args[] = $this->entry['id'];
			}
			
			/*
			print($sql);
			
			print($this->entry['id']);
			*/
			$newest_entries = $this->handler->db_access->RunQuery(['sql'=>$sql, 'args'=>$sql_args]);
			
		#	print("<PRE>");
		#	print_r($newest_entries);
		#	print("</PRE>");
			
			/*
			print("<PRE>");
			print_r($newest_entries);
			
			/*
			$get_record_where = [
				'type'=>'Entry',
				'limit'=>'10',
				'orderby'=>'Entry.OriginalCreationDate DESC',
			];
			$newest_entries = $this->handler->db_access->GetRecords($get_record_where);
			$newest_entries = $this->GetEntriesParents(['entries'=>$newest_entries]);
			*/
			$this->newest_entries = $newest_entries;
			
			return TRUE;
		}
		
		public function getDescription() {
			$description = 'The newest additions to the ';
			if($this->master_record['id'] !== $this->entry['id']) {
				$description .= $this->master_record['Title'] . ' ' . $this->master_record['ChildNoun'] . ' for ';
			#	print( $this->master_record['id'] . ' !==  ' . $this->entry['id'] );
			}
			
			$description .= $this->entry['Title'];
			
			if(strlen($description) > 0 && $this->entry['description'] && $this->entry['description'][0] && $this->entry['description'][0]['Description']) {
				$description .= ': ';
			}
			
			if($this->entry['description'] && $this->entry['description'][0] && $this->entry['description'][0]['Description']) {
				$description .= $this->entry['description'][0]['Description'];
			}
			
			$url = $this->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1])  . '/';
			
			$record_list_count = count($this->record_list);
			if($record_list_count > 0) {
				for($i = 0; $i < $record_list_count; $i++) {
					$record = $this->record_list[$i];
					$url .= $record['Code'] . '/';
				}
			}
			
			$description = preg_replace('/Image::(\d+)/', '', $description);
			
			return $description;
		}
	}
?>