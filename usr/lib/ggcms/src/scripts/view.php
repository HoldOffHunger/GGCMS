<?php

	ggreq('traits/scripts/DBFunctions.php');
	ggreq('traits/scripts/SimpleErrors.php');
	ggreq('traits/scripts/SimpleForms.php');
	ggreq('traits/scripts/SimpleLookupLists.php');
	ggreq('traits/scripts/SimpleORM.php');
	ggreq('traits/scripts/SimpleSocialMedia.php');
	
	class view extends basicscript {
						// Traits
						// ---------------------------------------------
		
		use DBFunctions;
		use SimpleErrors;
		use SimpleForms;
		use SimpleLookupLists;
		use SimpleORM;
		use SimpleSocialMedia;
		
		public $by;
		public $fieldname_validity;
		public $select;
		public $urlaction;
		public $script_name;
		public $fieldname;
		public $matchlike;
		public $admin_errors;
		public $selections;
		public $StatusDataArray;
		public $errors;
		public $likes;
		public $entry_count;
		public $redirect_script;
		public $redirect_action;
		public $redirect_base;
		public $redirect_query;
		public $children;
		public $definitions;
		public $definition_count;
		public $tag;
		public $tag_cleansed;
		public $where;
		public $page;
		public $custom_per_page_selected;
		public $perpage;
		public $child_record_start_index;
		public $child_record_end_index;
		public $total_pages;
		public $total_children_viewed;
		public $total_children_left;
		public $desired_action;
		public $counts;
		public $entry;
		public $parent;
		public $newest_entries;
		public $record_list;
		public $word;
		public $search_term;
		public $dictionary;
		public $entrydictionary;
		public $grammar;
		public $textcleanup;
		public $definition;
		public $definitions_found;
		public $likes_count;
		public $dislikes_count;
		public $user_likedislike;
		public $rpc_results;
		public $user_id;
		
						// Security Data
						// ---------------------------------------------
		
		public function IsSecure() {
			return FALSE;
		}
		
		public function RequiresLogin() {
			return FALSE;
		}
		
						// Select Entry by ID Form
						// ---------------------------------------------
		
		public function Select() {
			if(!$this->isUserAdmin()) {
				return FALSE;
			}

			$this->SetOrmBasics();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# Causes 404
			}
			
			$this->by = $this->param('by');
			
			if(
				($this->by === 'id') ||
				($this->by === 'Title') ||
				($this->by === 'Code') ||
				($this->by === 'Description') ||
				($this->by === 'Quote') ||
				($this->by === 'Link') ||
				($this->by === 'TextBody') ||
				($this->by === 'Tag') ||
				($this->by === 'AvailabilityStart') ||
				($this->by === 'AvailabilityEnd') ||
				($this->by === 'Level')
			) {
				$this->fieldname_validity = 1;
				$this->select = $this->param('Select');	# Form Button
				
				if($this->select) {
					$fieldname = $this->param('fieldname');
					$this->urlaction = $this->param('urlaction');
					
					switch($this->urlaction) {
						case 'view':
							$this->script_name = 'view.php';
							break;
						
						case 'edit':
							$this->script_name = 'modify.php?action=Edit';
							break;
					}
					
					if($fieldname) {
						$this->fieldname = $fieldname;
						$this->matchlike = $this->param('matchlike');
						
						$orm_match_args = [
							'fieldname'=>$this->by,
							'fieldvalue'=>$this->fieldname,
							'matchlike'=>$this->matchlike,
						];
						
						$record_results = $this->SearchForEntries($orm_match_args);
					#	print_r($record_results);
						if($record_results['error']) {
							$this->admin_errors[] = $record_results;
						} else {
							$this->selections = $record_results;
							$this->StatusDataArray = [];
							foreach($this->selections as $entry) {
								$this->StatusDataArray[] = [
									$this->Bullet() .
									$this->NonBreakingSpace() .
									$this->GetHyperlinkedEntryView([
										'entry'=>$entry,
										'entrylist'=>$entry['parents'],
										'scriptname'=>$this->script_name,
										'by'=>$this->by,
									])
								];
							}
						}
					} else {
						$this->errors[] = ['You must enter some search term in order to search.'];
					}
				}
			} else {
				$this->errors[] = ['The selected fieldname, "' . $this->CleanseForDisplay($this->by) . '", is invalid.'];
				$this->fieldname_validity = 0;
			}
			
			$this->FormatErrors();
			
			return TRUE;
		}
		
						// User Functionality
						// ---------------------------------------------
			
							// Main Browse Functionality
							// ---------------------------------------------
		
		public function viewUserLikes() {
			$this->SetORMBasics();
		#	$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->getEntryLikesAndDislikes();
			
			$this->FormatErrors();
			
			return TRUE;
		}
		
		public function getEntryLikesAndDislikes() {
			$likedislike_get_args = [
				'type'=>'LikeDislike',
				'definition'=>[
					'LikeOrDislike'=>1,
					'Entryid'=>$this->entry['id'],
				],
				'joins'=>[
					'JOIN'=>[
						'User'=>'User.id = LikeDislike.Userid',
					],
				],
				'orderby'=>'OriginalCreationDate DESC',
			];
			
			return $this->likes = $this->handler->db_access->GetRecords($likedislike_get_args);
		}
		
						// Browse Functionality
						// ---------------------------------------------
			
							// Main Browse Functionality
							// ---------------------------------------------
		
		public function browse() {
			$this->SetORMBasics();
		#	$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			$this->SetChildRecordCount();
			$this->entry_count = $this->children_count;
			/*
			if($this->entry_count > 50) {
				ini_set('memory_limit','150M');
			} else if($this->entry_count > 100) {
				ini_set('memory_limit','200M');
			} else if($this->entry_count > 150) {
				ini_set('memory_limit','250M');
			}
			*/
			$this->SetBrowseParameters();
			$this->SetChildRecords([]);
			
			$this->SetEntryChildRecordStats([]);
			$this->SetEntryAssociatedRecordStats([]);
			
			$this->SetSimpleChildAssociationRecords();
			$this->SetChildRecordsOfChildren();
			
			$this->ExtendedSetChildRecordsAssociated();
			
			$this->SetTagCounts();
			$this->SetSocialMediaBasics();
			
			$this->FormatEventDates();
			$this->FormatEntryInformation();
			
			$this->FormatErrors();
			
			return TRUE;
		}
		
							// Specialized Browse Functionality
							// ---------------------------------------------
		
		public function browseByTag() {
			if($_GET['soybeans']) {
				print("MEM!" . memory_get_usage() . "|");
			}
			
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(count($this->record_list) !== 0) {
				$this->redirect_script = 'view';
				$this->redirect_action = 'browseByTag';
				$this->redirect_base = '/';
				$this->redirect_query = '&tag=' . $this->Param('tag');
				
				return FALSE;
			}
			$this->SetTagParameters();
			$this->SetTagDefinition();
			//SetChildRecordCount
			$this->SetEntryRecordCount();
			$this->SetBrowseParameters();
			$this->SetChildRecords(['noassignment'=>TRUE]);
			$this->children = $this->GetEntriesParents(['entries'=>$this->children]);
		//	print_r($this->children);
		//	$this->SetEntryRecords([]);
		//	$this->children = $this->entries;	// yargh
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->SetSimpleChildAssociationRecords();
			$this->SetChildRecordsOfChildren();
			$this->SetSocialMediaBasics();
			
			$this->SetTagCounts();
			
			$this->SetEntryChildRecordStats([]);
			$this->SetEntryAssociatedRecordStats([]);
			
			$this->FormatEntryInformation();
			$this->FormatErrors();
			if($_GET['soybeans']) {
				print("MEM!" . memory_get_usage() . "|");
			}
			
			return TRUE;
		}
		
							// Browse Helper Functionality
							// ---------------------------------------------
		
		/*
			A keyword may be a word, and a word may have a definition in
			alldictionaries -- 113,609 of them, shared by every site, because a
			word means the same thing whoever is asking.

			Only this page looks one up.  The dictionary object exists only when
			clonefrom/scripts/view.php said this action wants it, so its absence
			is normal and silent rather than an error.
		*/

		public function SetTagDefinition() {
			$this->definitions = [];
			$this->definition_count = 0;

			if(!$this->tag) {
				return FALSE;
			}

			if(!$this->handler->dictionary) {
				return FALSE;
			}

			$definitions = $this->handler->dictionary->LookupWords([
				'words'=>[$this->tag],
			]);

			$found = $definitions[strtolower($this->tag)];

			if(!$found) {
				return FALSE;
			}

			$this->definitions = $found;
			$this->definition_count = count($found);

			return TRUE;
		}

		public function SetTagParameters() {
			$this->tag = $this->Param('tag');
			$this->tag_cleansed = $this->CleanseForDisplay($this->tag);
			return $this->where = [
				'sql'=>'JOIN Tag ON Tag.Entryid = Entry1.id AND Tag.Tag = ? ',
				'bind'=>'s',
				'value'=>[$this->tag],
			];
		}
		
		public function SetBrowseParameters() {
			$this->SetBrowseParameters_PageAndPerPage();
			$this->SetBrowseParameters_TotalPages();
			$this->SetBrowseParameters_RemainingPages();
			return TRUE;
		}
		
		public function SetBrowseParameters_PageAndPerPage() {
			$this->page = (int)$this->Param('page');
			$possible_per_page = $this->Param('perpage');
			if($possible_per_page == 'custom') {
				$this->custom_per_page_selected = true;
				$possible_per_page = $this->Param('CustomPerPage');
			}
			$this->perpage = (int)$possible_per_page;
			
			if($this->page < 1) {
				$this->page = 1;
			}
			
			if($this->perpage < 0) {
				$this->perpage = $this->browse_DefaultPerPage();
			}
			
			if($this->perpage < $this->browse_MinPerPage()) {
				$this->perpage = $this->browse_DefaultPerPage();
			} elseif($this->perpage > $this->browse_MaxPerPage()) {
				$this->perpage = $this->browse_MaxPerPage();
			}
			
			$child_record_start_index = ($this->page - 1) * $this->perpage + 1;
			if($child_record_start_index > $this->entry_count) {
				$this->page = 1;
				$this->perpage = $this->browse_DefaultPerPage();
				$child_record_start_index = 1;
			}
			$child_record_end_index = $child_record_start_index + $this->perpage - 1;
			
			if($child_record_end_index > $this->entry_count) {
				$child_record_end_index = $this->entry_count;
			}
			
			$this->child_record_start_index = $child_record_start_index;
			$this->child_record_end_index = $child_record_end_index;
			
			return TRUE;
		}
		
		public function SetBrowseParameters_TotalPages() {
			$this->total_pages = (int) ceil($this->entry_count / $this->perpage);
			
			return TRUE;
		}
		
		public function SetBrowseParameters_RemainingPages() {
			$this->total_children_viewed = $this->perpage * ($this->page - 1);
			$this->total_children_left = $this->entry_count - $this->total_children_viewed - ($this->child_record_end_index - $this->child_record_start_index + 1);
			
			return TRUE;
		}
		
		public function maxTextLength() {
			return 10000;
		}
		
		public function maxChildren() {
			return 10;
		}
		
		public function maxAssociated() {
			return 10;
		}
		
		public function browse_DefaultPerPage() {
			return 30;
		}
		
		public function browse_MinPerPage() {
			return 1;
		}
		
		public function browse_MaxPerPage() {
			if($this->isUserAdmin()) {
				ini_set('memory_limit','500M');
				return 10000;	# sometimes, in life, it pays to be a tough sonovabitch ~ bukowski
			}
			
			return 200;
		}
		
						// Browse Associated Functionality
						// ---------------------------------------------
			
							// Main Browse Functionality
							// ---------------------------------------------
		
		public function browseAssociated() {
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			$this->SetChildRecordCount();
			$this->entry_count = $this->children_count;
			
			$this->SetBrowseParameters();
			
			$this->SetChildRecords([]);
			$this->SetEntryChildRecordStats([]);
			$this->SetEntryAssociatedRecordStats([]);
			
			$this->children = $this->orm->GetEntriesParents([
				'entries'=>$this->children,
			]);
			
			$this->SetSimpleChildAssociationRecords();
			$this->SetExtendedChildAssociationRecords();
			$this->SetChildRecordsOfChildren();
			
			$this->SetTagCounts();
			$this->SetSocialMediaBasics();
			
			$this->FormatEventDates();
			$this->FormatEntryInformation();
			
			$this->FormatErrors();
			
			return TRUE;
		}
		
						// View Functionality
						// ---------------------------------------------
		
		public function display() {
			$this->SetORMBasics();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->FormatEntryInformation();
			
			if(!$this->canUserAccess()) {
				return FALSE;
			}
			
			$this->FormatText();
			$this->FormatEventDates();
			
			$this->SaveComments();
			$this->SetComments();
			
			$this->SetChildRecordCount();
			
			if($this->children_count > 400) {
				$this->desired_action = 'index';	// you don't want this page
				return $this->index();
			}
			
			$this->SetChildRecords([]);
			$this->SetEntryChildRecordStats([]);
			$this->SetEntryAssociatedRecordStats([]);
			
		#	if($this->entry && $this->entry['associated'] && count($this->entry['associated']) < $this->maxAssociated() + 1) {
				$this->SetSimpleChildAssociationRecords();
				$this->SetAssociationRecords();
		#	}
			$this->SetChildRecordsOfChildren();
			
			$this->SetLikeDislikeRecords();
			$this->HandleMainPage();
			
			$this->CompactDefinitions();
			$this->SetTagCounts();
			$this->SetSocialMediaBasics();
			if($this->RecordRelationEnabled(['name'=>'Siblings'])) {
				$this->SetSiblings([]);
			}
			
			$this->CountRecords();
			$this->FixDates();
			
			$this->FormatErrors();
			
			return TRUE;
		}
		
		public function FixDates() {
			if($this->entry['eventdate']) {
				$event_dates = $this->entry['eventdate'];
				
				if($this->counts['eventdate']) {
					for($i = 0; $i < $this->counts['eventdate']; $i++) {
						$event_date = $event_dates[$i];
						
						$event_date_time_pieces = explode('-', $event_date['EventDateTime']);
						
						$year = (int)$event_date_time_pieces[0];
						
						if($year >= 3000) {
							$diff = $year - 3000;
							$real_year = 1000 - $diff;
						} else {
							$real_year = $year;
						}
						
						$event_date_time_pieces[0] = $real_year;
						$new_time = implode('-', $event_date_time_pieces);
						$event_date['EventDateTime'] = $new_time;
						
						$event_dates[$i] = $event_date;
						
		#				print("<!-- BT: DATE! ");
		#				print_r($event_date);
		#				print("-->");
					}
				#	print("<!-- BT: event dates exist! go! -->");
				}
			}
		}
		
		public function CountRecords() {
				# BT: REDO!  VARIABLE-IZE!
			if($this->children) {
				$children_count = count($this->children);
			} else {
				$children_count = 0;
			}
			
			if($this->younger_siblings) {
				$younger_sibling_count = count($this->younger_siblings);
			} else {
				$younger_sibling_count = 0;
			}
			
			if($this->older_siblings) {
				$older_sibling_count = count($this->older_siblings);
			} else {
				$older_sibling_count = 0;
			}
			
			if($this->entry['image']) {
				$image_count = count($this->entry['image']);
			} else {
				$image_count = 0;
			}
			
			if($this->entry['description']) {
				$description_count = count($this->entry['description']);
			} else {
				$description_count = 0;
			}
			
			if($this->entry['quote']) {
				$quote_count = count($this->entry['quote']);
			} else {
				$quote_count = 0;
			}
			
			if($this->entry['tag']) {
				$tag_count = count($this->entry['tag']);
			} else {
				$tag_count = 0;
			}
			
			if($this->entry['textbody']) {
				$textbody_count = count($this->entry['textbody']);
			} else {
				$textbody_count = 0;
			}
			
			if($this->entry['associated']) {
				$associated_count = count($this->entry['associated']);
			} else {
				$associated_count = 0;
			}
			
			if($this->entry['association']) {
				$association_count = count($this->entry['association']);
			} else {
				$association_count = 0;
			}
			
			if($this->entry['eventdate']) {
				$eventdate_count = count($this->entry['eventdate']);
			} else {
				$eventdate_count = 0;
			}
			
			if($this->entry['definition']) {
				$definition_count = count($this->entry['definition']);
			} else {
				$definition_count = 0;
			}
			
			if($this->entry['link']) {
				$link_count = count($this->entry['link']);
			} else {
				$link_count = 0;
			}
			
			if($this->comments) {
				$comments_count = count($this->comments);
			} else {
				$comments_count = 0;
			}
			
			$this->counts = [
				'image'=>$image_count,
				'tag'=>$tag_count,
				'description'=>$description_count,
				'quote'=>$quote_count,
				'textbody'=>$textbody_count,
				'associated'=>$associated_count,
				'association'=>$association_count,
				'eventdate'=>$eventdate_count,
				'definition'=>$definition_count,
				'link'=>$link_count,
				
				'children'=>$children_count,
				
				'comment'=>$comments_count,
				
				'younger_sibling'=>$younger_sibling_count,
				'older_sibling'=>$older_sibling_count,
			];
			
			return TRUE;
		}
		
		public function FormatText() {
			if($this->entry['textbody'] && $this->entry['textbody'][0] && $this->entry['textbody'][0]['id'] && $this->entry['textbody'][0]['Text']) {
				$text = $this->formatImageText([
					'text'=>$this->entry['textbody'][0]['Text'],
					'images'=>$this->entry['image'],
				]);
				$this->entry['textbody'][0]['Text'] = $text;
			}
			return TRUE;
		}
		
		public function FormatEventDates() {
			if($this->parent['eventdate'] && $this->parent['eventdate'][0]) {
				for($i = 0; $i < count($this->parent['eventdate']); $i++) {
					$event_date = $this->parent['eventdate'][$i];
					
					$date_time_pieces = explode(' ', $event_date['EventDateTime']);
					$date = $date_time_pieces[0];
					$time = $date_time_pieces[1];
					
					$event_date['date'] = $date;
					$event_date['time'] = $time;
					
					$this->parent['eventdate'][$i] = $event_date;
				}
			}
			
			if($this->entry['eventdate'] && $this->entry['eventdate'][0]) {
				for($i = 0; $i < count($this->entry['eventdate']); $i++) {
					$event_date = $this->entry['eventdate'][$i];
					
					$date_time_pieces = explode(' ', $event_date['EventDateTime']);
					$date = $date_time_pieces[0];
					$time = $date_time_pieces[1];
					
					$event_date['date'] = $date;
					$event_date['time'] = $time;
					
					$this->entry['eventdate'][$i] = $event_date;
				}
			}
			
			if($this->children && count($this->children)) {
				for($i = 0; $i < count($this->children); $i++) {
					$child = $this->children[$i];
					
					if($child['eventdate']) {
						for($j = 0; $j < count($child['eventdate']); $j++) {
							$event_date = $child['eventdate'][$j];
							
							$date_time_pieces = explode(' ', $event_date['EventDateTime']);
							$date = $date_time_pieces[0];
							$time = $date_time_pieces[1];
							
							$event_date['date'] = $date;
							$event_date['time'] = $time;
							
							$this->children[$i]['eventdate'][$j] = $event_date;
						}
					}
				}
			}
#			$this->entry['eventdate'][0]['test'] = 'soybean';
			return TRUE;
		}
		
		public function formatImageText($args) {
			$args['text'] = $this->formatImageText_fulls($args);
			$args['text'] = $this->formatImageText_icons($args);
			
			return $args['text'];
		}
		
		public function formatImageText_icons($args) {
			$text = $args['text'];
			$images = $args['images'];
			
			if ($this->script_format_lower == 'pdf') {
				$text = preg_replace('/Image::(\d+)/', '', $text); 
				return $text;
			}
			
			$dom = preg_split('/Image::(\d+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
			$dom_length = count($dom);
			
			if($dom_length > 1) {
				$orientation = 'right';
				$mobile_friendly = $this->Param('mobilefriendly');
				
				$max_image_height = 400;
				$max_image_width = 500;
				
				for($i = 1; $i < $dom_length; $i += 2) {
					$dom_piece = $dom[$i];
					$number = (int)$dom_piece;
					$image = $images[$number - 1];
					
					if($mobile_friendly || !$image) {
						$dom[$i] = '';
					} else {
						$real_height = 0;
						$real_width = 0;
						$perceived_width = (int)$image['PixelWidth'];
						
						if((int)$image['PixelHeight'] > (int)$image['PixelWidth'] && (int)$image['PixelHeight'] > $max_image_height) {
							$real_height = $max_image_height;
							$perceived_width = ceil((int)$image['PixelWidth'] * ($max_image_height / (int)$image['PixelHeight']));
						} elseif((int)$image['PixelWidth'] > $max_image_width) {
							$real_width = $max_image_width;
							$perceived_width = $real_width;
						}
						
						$perceived_width -= 5;
						
						$image_code = '';
						
						$image_code .= '<div class="document-image-holder document-image-holder-';
						$image_code .= $orientation;
						$image_code .= '" ';
						$image_code .= 'title="';
						$image_code .= $image['Title'];
						$image_code .= ' ';
						$image_code .= ' - (Click to View Full Image)';
						$image_code .= '" ';
						$image_code .= '>';
						
						$image_directory = '/image/' . implode('/', str_split($image['FileDirectory'])) . '/';
						
						$image_code .= '<a href="';
						$image_code .= $image_directory;
						$image_code .= $image['FileName'];
						$image_code .= '" target="_blank">';
						
						$image_code .= '<img ';
						$image_code .= 'class="document-image" ';
						$image_code .= 'src="';
						$image_code .= $image_directory;
						$image_code .= $image['StandardFileName'];
						$image_code .= '" ';
						
						$image_code .= 'alt=" ';
						$image_code .= $image['Title'];
						$image_code .= '" ';
						
						$image_code .= 'style="margin:0px;" ';
						
						if($real_height > 0) {
							$image_code .= 'height="' . $real_height . '" ';
						} elseif($real_width > 0) {
							$image_code .= 'width="' . $real_width . '" ';
						}
						
						$image_code .= '>';
						
						$image_code .= '</a>';
						
						$image_code .= '<p ';
						$image_code .= 'class="margin-2px font-family-arial font-size-75percent horizontal-center" ';
						$image_code .= 'style="';
						$image_code .= 'max-width:' . $perceived_width . 'px;';
						$image_code .= 'font-size:12px;';
						$image_code .= '">';
						
						$image_title = $image['Title'];
						$image_title = str_replace(', CC ', ',<BR>CC ', $image_title);
						$image_code .= $image_title;
						$image_code .= '</p>';
						
						$image_code .= '</div>';
						
						$new_dom_piece = $image_code;
						$dom[$i] = $new_dom_piece;
						
						if($orientation == 'left') {
							$orientation = 'right';
						} else {
							$orientation = 'left';
						}
					}
				}
				$text = implode('', $dom);
			}
			
			return $text;
		}
		public function formatImageText_fulls($args) {
			$text = $args['text'];
			$images = $args['images'];
			
			if ($this->script_format_lower == 'pdf') {
				$text = preg_replace('/FullImage::(\d+)/', '', $text); 
				return $text;
			}
			
			$dom = preg_split('/FullImage::(\d+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
			$dom_length = count($dom);
			
			if($dom_length > 1) {
				$mobile_friendly = $this->Param('mobilefriendly');
				
				for($i = 1; $i < $dom_length; $i += 2) {
					$dom_piece = $dom[$i];
					$number = (int)$dom_piece;
					$image = $images[$number - 1];
					
					if($mobile_friendly || !$image) {
						$dom[$i] = '';
					} else {
						
						if((int)$image['PixelHeight'] > (int)$image['PixelWidth'] && (int)$image['PixelHeight'] > $max_image_height) {
							$real_height = $max_image_height;
						} elseif((int)$image['PixelWidth'] > $max_image_width) {
							$real_width = $max_image_width;
						}
						$real_height = $image['StandardPixelHeight'];
						$real_width = $image['StandardPixelWidth'];
						
						$image_code = '';
						
						$image_code .= '<center>';
						$image_code .= '<div ';
						$image_code .= 'title="';
						$image_code .= $image['Title'];
						$image_code .= ' ';
						$image_code .= ' - (Click to View Full Image)';
						$image_code .= '" ';
						$image_code .= '>';
						
						$image_directory = '/image/' . implode('/', str_split($image['FileDirectory'])) . '/';
						
						$image_code .= '<a href="';
						$image_code .= $image_directory;
						$image_code .= $image['FileName'];
						$image_code .= '" target="_blank">';
						
						$image_code .= '<img ';
						$image_code .= 'class="document-image" ';
						$image_code .= 'src="';
						$image_code .= $image_directory;
						$image_code .= $image['StandardFileName'];
						$image_code .= '" ';
						
						$image_code .= 'alt=" ';
						$image_code .= $image['Title'];
						$image_code .= '" ';
						
						$image_code .= 'style="margin:0px;" ';
						
						if($real_height > 0) {
							$image_code .= 'height="' . $real_height . '" ';
						} elseif($real_width > 0) {
							$image_code .= 'width="' . $real_width . '" ';
						}
						
						$image_code .= '>';
						
						$image_code .= '</a>';
						
						$image_code .= '</div>';
						$image_code .= '</center>';
						
						$new_dom_piece = $image_code;
						$dom[$i] = $new_dom_piece;
					}
				}
				$text = implode('', $dom);
			}
			
			return $text;
		}
		
		public function HandleMainPage() {
			if($this->IsMainPage()) {
				if($this->RecordRelationEnabled(['name'=>'GrandChildAssociations'])) {
					$this->SetGrandChildAssociationRecords();
				}

				if($this->RecordRelationEnabled(['name'=>'GrandChildRecords'])) {
					$this->SetGrandChildRecordsOfChildren();
				}

				if($this->RecordRelationEnabled(['name'=>'NewestChildren'])) {
					$this->SetNewestChildren();
				}
			}
			
			return TRUE;
		}
		
		public function SetNewestChildren() {
			$get_record_where = [
				'type'=>'Entry',
				'limit'=>'10',
				'orderby'=>'Entry.OriginalCreationDate DESC',
				'definition'=>[
					'Publish'=>1,
				],
			];
			$newest_entries = $this->handler->db_access->GetRecords($get_record_where);
			$newest_entries = $this->GetEntriesParents(['entries'=>$newest_entries]);
			
			$this->newest_entries = $newest_entries;
			
			return TRUE;
		}
		
		public function IsMainPage() {
			if(count($this->record_list) < 1) {
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function display_wordweight_setORM() {
			$this->SetORM();
			$this->SetMasterRecord();
			$this->record_list = [];
		#	$this->SetRecordTree();	# save 1 mysql call
			$this->SetEntry();
			
		#	$this->SetOrmBasics();
			
			return TRUE;
		}
		
		public function display_wordweight() {
			if($this->object_list && count($this->object_list) > 1) {
				return FALSE;
			}
			
			$this->display_wordweight_setORM();
			
			if(!$this->handler->dictionary) {
				return FALSE;
			}
			
			if($this->object_list && count($this->object_list)) {
				$this->word = $this->object_code;
				$this->definitions = $this->handler->dictionary->LookupWords(['words'=>[$this->word]])[strtolower($this->word)];
				
				$this->definition_count = $this->definitions ? count($this->definitions) : 0;
				
				if(!$this->definition_count) {
					return FALSE;
				}
			}
			
			$this->SetSocialMediaBasics();
			
			$this->search_term = $this->Param('search');
			
			if($this->search_term) {
				$this->definitions = $this->handler->dictionary->LookupWords(['words'=>[$this->search_term]])[strtolower($this->search_term)] ?? [];
				
					// a word the dictionaries do not hold is NULL, and count(NULL) ended the page in a 500
				$this->definition_count = count($this->definitions);
				
					/*
						A word the dictionaries hold goes to its own page.  This
						returned what header() returns, which is nothing, so the
						look-up went out as a 404 carrying a Location no browser
						follows on a 404 -- every search on WordWeight said not
						found, and Cloudflare kept that answer.
					*/

				if($this->definition_count) {
					header('Location: ' . $this->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/' . rawurlencode($this->search_term) . '/view.php', TRUE, 302);

					exit;
				}
			}
			
			return TRUE;
		}
		
						// Index Functionality
						// ---------------------------------------------
		
		public function index() {
			if(!$this->orm) {
				$this->SetORMBasics();
			#	$this->SetRecordTree();		# why not?
			}
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->FormatEntryInformation();
			
			if(!$this->canUserAccess()) {
				return FALSE;
			}
			
			$this->SetIndexChildRecords([]);
			$this->SetChildRecordsOfChildren();
			$this->where = [];
			$this->SetChildRecordCount();
			$this->SetEntryChildRecordStats([]);
			$this->SetEntryAssociatedRecordStats([]);
			
			$this->SetChildAssociationRecords();
			
			if($this->handler->globals->IndexPullChildRecordStats()) {
				$this->setChildRecordStatsOfChildren();
	#			print('soyo-beano');
			}
			
			$this->SetTagCounts();
			$this->SetSocialMediaBasics();

				/*
					As display() does, last: templates read $this->counts, and
					without it an index showed none of its children.  display()
					hands any entry with more than 400 children to this action,
					so every EarthFluent language page listed no lessons.
				*/

			$this->CountRecords();

			$this->FormatErrors();

			return TRUE;
		}

						// Dictionary Functionality
						// ---------------------------------------------
		
		public function dictionary() {
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			ggreq('classes/Database/ORMDictionary.php');
			$this->dictionary = new ORMDictionary(['dbaccess'=>$this->handler->db_access]);
			$entry_dictionary = $this->dictionary->GetDictionary(['entry'=>$this->entry]);
			$entry_dictionary_count = count($entry_dictionary);
			
			$defined_words = [];
			
			$cache_file_location = 'data/dictionary/' . $this->handler->domain->host . '/' . $this->entry['id'] . '.json';
			if($this->handler->authentication->CheckAuthenticationForCurrentObject_IsAdmin() && $this->Param('godmode')) {
				ini_set('memory_limit','500M');		// God says "Move aside, little ones."
				for($i = 0; $i < $entry_dictionary_count; $i++) {
					$entry_definition = $entry_dictionary[$i];
					
					$term = $entry_definition['Term'];
					
					if(!$defined_words[$term]) {
						$defined_words[$term] = [];
					}
					
					$publication_date = FALSE;
					
					if($entry_definition['EventDate2_EventDateTime']) {
						$date_time_pieces = explode(' ', $entry_definition['EventDate2_EventDateTime']);
						$date_pieces = explode('-', $date_time_pieces[0]);
						
						if($date_pieces[0] && $date_pieces[0] != '0000') {
							$full_date = $date_pieces[0];
							
							if($date_pieces[1] && $date_pieces[2] && $date_pieces[1] != '00' && $date_pieces[2] != '00') {
								$full_date .= '-' . $date_pieces[1] . '-' . $date_pieces[2];
							}
							$publication_date = $full_date;
						}
					}
					
					$defined_words[$term][] = [
						'Definition'=>$entry_definition['Definition'],
						'Author'=>$entry_definition['Title'],
						'AuthorCode'=>$entry_definition['Code'],
						'Source'=>$entry_definition['Entry2_Title'],
						'SourcePermaid'=>$entry_definition['Assignment1_id'],
						'PublicationDate'=>$publication_date,
					];
				}
				
				$this->natksort($defined_words);
				
				$file_handle_for_source = fopen($cache_file_location, 'w+');
				fwrite($file_handle_for_source, json_encode($defined_words));
				fclose($file_handle_for_source);
			} else {
				$defined_words = [];

				if(is_readable($cache_file_location)) {
					$cached_defined_words = json_decode(file_get_contents($cache_file_location), TRUE);

					if(is_array($cached_defined_words)) {
						$defined_words = $cached_defined_words;
					}
				}
#				print_r($defined_words);
			}
			$this->entrydictionary = $defined_words;
		#	print_r();
			
			return TRUE;
		}
				
		function natksort(&$array) {
			$keys = array_keys($array);
			natcasesort($keys);
			
			foreach ($keys as $k) {
				$new_array[$k] = $array[$k];
			}
			
			$array = $new_array;
			return true;
		}
		
						// Definitions Functionality
						// ---------------------------------------------
		
		public function definitions() {
			$this->SetORMBasics();
			$this->SetRecordTree();
			
			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}
			
			$this->SetChildRecords(['alltext'=>TRUE]);
			$this->SetAssociationRecords();
			
			ggreq('classes/Language/Grammar.php');
			$this->grammar = new Grammar();
			
			ggreq('classes/Language/TextCleanup.php');
			$this->textcleanup = new TextCleanup(['grammar'=>$this->grammar]);
			
			ggreq('classes/Language/Definition.php');
			$this->definition = new Definition(['grammar'=>$this->grammar, 'textcleanup'=>$this->textcleanup]);
			
			$text = '';
			
			if($this->entry['textbody'] && $this->entry['textbody'][0] && $this->entry['textbody'][0]['id'] && $this->entry['textbody'][0]['Text']) {
				$text = $this->entry['textbody'][0]['Text'];
			} else {
				if($this->children && count($this->children)) {
					$child_count = count($this->children);
					for($i = 0; $i < $child_count; $i++) {
						$child = $this->children[$i];
						if($child['textbody'] && $child['textbody'][0] && $child['textbody'][0]['id'] && $child['textbody'][0]['Text']) {
							$text .= $child['textbody'][0]['Text'];
							
							if($i + 1 < $child_count) {
								$text .= ' ';
							}
						}
					}
				}
			}
			
			$text = trim(html_entity_decode(strip_tags($text)));
			
			$this->definitions_found = $this->definition->GetDefinitions(['text'=>$text]);
			
			$this->SetChildRecords([]);
			
			$this->FormatErrors();
			
			return TRUE;
		}
		
						// Load Like/Dislike Functionality
						// ---------------------------------------------
		
		public function SetLikeDislikeRecords() {
			$likesdislikes_counts = $this->GetEntryLikesDislikesCount([]);
			
			$this->likes_count = $likesdislikes_counts['likes'];
			$this->dislikes_count = $likesdislikes_counts['dislikes'];
			
			$user_id = $this->handler->authentication->user_session['User.id'];
			
			if($user_id) {
				$user_where = [
					'type'=>'LikeDislike',
					'definition'=>[
						'Userid'=>$user_id,
						'Entryid'=>$this->entry['id'],
					],
				];
				
				$this->user_likedislike = $this->handler->db_access->GetRecords($user_where)[0];
			}
			
			return TRUE;
		}
		
						// JS Like/Dislike Functionality
						// ---------------------------------------------
		
			// BT: Here
		
			/*
				The four actions like-dislike.js posts to view.json.  The
				dispatcher calls them with no arguments, and downvote() and
				undodownvote() required one: an ArgumentCountError, so a
				downvote was a 500 for everyone.  None looked at whether there
				was a user or an entry, so an anonymous vote reached an insert
				with no Userid, which MySQL refused -- another 500 -- and every
				action said Success whatever happened.  Success now means the
				database did it.
			*/
		
		public function upvote() {
			return $this->Vote(['liked'=>1]);
		}
		
		public function undoupvote() {
			return $this->Unvote();
		}
		
		public function downvote() {
			return $this->Vote(['liked'=>0]);
		}
		
		public function undodownvote() {
			return $this->Unvote();
		}
		
		public function Vote($args) {
			if(!$this->SetUserAndEntry()) {
				return $this->rpc_results = ['Success'=>0];
			}
			
			$likedislike = $this->GetUserLike([]);
			$saved = $this->SetUserLike(['likedislike'=>$likedislike, 'liked'=>$args['liked']]);
			
			return $this->rpc_results = ['Success'=>$saved ? 1 : 0];
		}
			
			/*
				Undoing a vote that is not there is already done: success, and
				nothing touched.
			*/
		
		public function Unvote() {
			if(!$this->SetUserAndEntry()) {
				return $this->rpc_results = ['Success'=>0];
			}
			
			$likedislike = $this->GetUserLike([]);
			
			if(!$likedislike) {
				return $this->rpc_results = ['Success'=>1];
			}
			
			$removed = $this->RemoveUserVote(['likedislike'=>$likedislike]);
			
			return $this->rpc_results = ['Success'=>$removed ? 1 : 0];
		}
		
		public function SetUserAndEntry() {
			$user_id = $this->handler->authentication->user_session['User.id'];
			
			if(!$user_id) {
				return FALSE;
			}
			
			$this->user_id = $user_id;
			
			if(!$this->orm) {
				$this->SetORMBasics();
			}
			
			if(!$this->entry || !$this->entry['id']) {
				return FALSE;
			}
			
			return TRUE;
		}
		
		public function GetUserLike($args) {
			$user_id = $this->user_id;
			
			$user_upvote_args = [
				'type'=>'LikeDislike',
				'definition'=>[
					'Userid'=>$user_id,
					'Entryid'=>$this->entry['id'],
				],
			];
			
			$user_vote = $this->handler->db_access->GetRecords($user_upvote_args);
			
			if(!empty($user_vote['line']) || empty($user_vote[0]['id'])) {
				return NULL;
			}
			
			return $user_vote[0];
		}
		
		public function SetUserLike($args) {
			$user_id = $this->user_id;
			$likedislike = $args['likedislike'];
			$liked = $args['liked'];
			
			if($likedislike) {
				if($likedislike['LikeOrDislike'] != $liked) {
					$likedislike['LikeOrDislike'] = $liked;
					
					$likedislike_update_args = [
						'type'=>'LikeDislike',
						'update'=>[
							'LikeOrDislike'=>$liked,
						],
						'where'=>[
							'id'=>$likedislike['id'],
						],
					];
					
					$update_results = $this->handler->db_access->UpdateRecord($likedislike_update_args);
					
					if(!empty($update_results['line'])) {
						return FALSE;
					}
				}
			} else {
				$likedislike_update_args = [
					'type'=>'LikeDislike',
					'definition'=>[
						'Userid'=>$user_id,
						'Entryid'=>$this->entry['id'],
						'LikeOrDislike'=>$liked,
					],
				];
				
					// CreateRecord() returns the row itself; [0] of it was nothing
				$likedislike = $this->handler->db_access->CreateRecord($likedislike_update_args);
				
				if(!empty($likedislike['line']) || empty($likedislike['id'])) {
					return FALSE;
				}
			}
			
			return $likedislike;
		}
		
		public function RemoveUserVote($args) {
			$likedislike = $args['likedislike'];
			
			$likedislike_delete_args = [
				'type'=>'LikeDislike',
				'wherevalues'=>[$likedislike['id']],
				'where'=>'id = ?',
				'sqlbindstring'=>'i',
			];
			
			$delete_results = $this->handler->db_access->DeleteRecords($likedislike_delete_args);
			
			return empty($delete_results['line']);
		}
	}

?>