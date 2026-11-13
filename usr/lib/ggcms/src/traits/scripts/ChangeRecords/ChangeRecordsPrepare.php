<?php

	/*
		Belt stage four: Prepare.  The values are made what they will be
		stored as.  Text bodies are cleaned -- URLs stripped, vocabulary
		Americanized, paragraphs made HTML, word and character counts set --
		and dates are encoded, including the ones MySQL's DATETIME cannot
		hold: the year 6000 BCE is stored, and read back, through here and
		traits/GGCMSDateFormat.php.  The text as it came in is kept as
		textbody_unprepared, and the others likewise.

		One stage of the belt described in ChangeRecords.php.
	*/

	trait ChangeRecordsPrepare {
		public $image;
		public $entry;
		public $eventdate;
		public $entry_unset;
		public $errors;
		public $price;
		public $academicreference;
		public $publication;
		public $wordpiece;
		public $wordform;
		public $entry_unprepared;
		public $textbody_unprepared;
		public $textbody;
		public $image_unfilled;
		public $eventdate_unprepared;
		public $association_unprepared;
		public $association;
		public $availabilitydaterange_unprepared;
		public $availabilitydaterange;
		public $assignment;
		
		public function PrepareRecordForSavingForAll() {
			$prepare_results = TRUE;
			
			if(!$this->PrepareRecordForSaving_Entry()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_EntryTranslation()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_Description()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_Quote()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_TextBody()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_Image()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_ImageTranslation()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_Tag()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_Link()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_Price()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_AcademicReference()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_Publication()) {
				$prepare_results = FALSE;
			}

			if(!$this->PrepareRecordForSaving_WordPiece()) {
				$prepare_results = FALSE;
			}

			if(!$this->PrepareRecordForSaving_WordForm()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_EventDate()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_Association()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_AvailabilityDates()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_Assignment()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_EntryPermission()) {
				$prepare_results = FALSE;
			}
			
			if(!$this->PrepareRecordForSaving_Definition()) {
				$prepare_results = FALSE;
			}
			
			return $prepare_results;
		}
		
						// Record Preparing
						// ---------------------------------------------
		
		public function PrepareRecordForSaving_Entry() {
			$this->entry_unprepared = $this->entry;
			
			if(strlen($this->entry['Title']) === 0) {
				$this->entry['Title'] = $this->GenerateTitle([]);
			}
			
	#		print("BT: PREP!");
			$this->entry_unset['association'] = $this->association;
			$this->entry_unset = $this->SetAssociationRecordsForEntries(['entries'=>[$this->entry_unset]])[0];
		#	print($this->entry['Code']);
			if(strlen($this->entry['Code']) === 0) {
		#		print("BT: GENERATE!!!!");
				$this->entry['Code'] = $this->GenerateUnblockedEntryCode();
				if(strlen($this->entry['Code']) === 0) {
					die ("NO CODE!");
				}
			}
	#		print_r($this->association);
		#	print($this->entry['Code']);
			
		#	die("naw");
			
			$this->entry['Title'] = strip_tags(html_entity_decode($this->entry['Title']));
			$this->entry['Subtitle'] = strip_tags(html_entity_decode($this->entry['Subtitle']));
			$this->entry['ListTitle'] = strip_tags(html_entity_decode($this->entry['ListTitle']));
			
			if(!strlen($this->entry['ListTitle'])) {
				$this->entry['ListTitle'] = $this->GenerateEntryListTitle([]);
			}
			
			if(!strlen($this->entry['ListTitleSortKey'])) {
				$this->entry['ListTitleSortKey'] = $this->GenerateEntryListTitleSortKey([]);
			}
			
			if(	!$this->parent['id'] &&			// Is it a new master record?
				$this->master_record['id'] &&		// Do we already have a master record?
				$this->desired_action != 'Update')	// Is it a non-new-record-creating action?
			{
				$this->errors[] = ['You may presently only have one master entry record.'];	// Then don't save it.
				return FALSE;
			}
			
			return TRUE;
		}
		
		public function PrepareRecordForSaving_EntryTranslation() {
			return TRUE;
		}
		
		public function PrepareRecordForSaving_Description() {
			return TRUE;
		}
		
		public function PrepareRecordForSaving_Quote() {
			return TRUE;
		}
		
		public function PrepareRecordForSaving_TextBody() {
			$this->textbody_unprepared = $this->textbody;
			$new_textbodies = [];
			
			$strip_urls = $this->Param('textbody_StripURLs');
			$americanize_vocabularies = $this->Param('textbody_AmericanizeVocabulary');
			$html_formattings = $this->Param('textbody_HTMLFormatting');
			$spell_check_immunes = $this->Param('textbody_SpellCheckImmune');
			$american_british_spellings = 0;
			$index = 0;
			
			foreach($this->textbody as $textbody) {
				if($textbody['Text']) {
					$strip_url = $strip_urls[$index];
					$americanize_vocabulary = $americanize_vocabularies[$index];
					$html_formatting = $html_formattings[$index];

						# set only on a text with words, or a blank row's 0 would make it worth saving
					$textbody['SpellCheckImmune'] = $spell_check_immunes[$index] ? 1 : 0;

						# <pre> blocks stay as saved: see PreformattedBlocks.php
					if(!class_exists('PreformattedBlocks')) {
						ggreq('classes/Format/HTML/PreformattedBlocks.php');	# plain require, and this runs once a text body
					}
					$preformatted_blocks = new PreformattedBlocks;
					$textbody['Text'] = $preformatted_blocks->SetAside(['text'=>$textbody['Text']]);

					if($html_formatting) {
						$text = $textbody['Text'];
						
						$paragraphs = explode("\n\r\n", $text);
						
						$new_paragraphs = [];
						
						foreach($paragraphs as $paragraph) {
							$new_paragraph = str_replace("\n", "<BR>\n", $paragraph);
							$new_paragraphs[] = $new_paragraph;
						}
						
						$new_text = '<p>' . implode("</p>\n\n<p>", $new_paragraphs) . '</p>';
						
						$textbody['Text'] = $new_text;
					}
					
					$dom = new DOMDocument;
					try {
						@$dom->loadHTML(mb_convert_encoding($textbody['Text'], 'HTML-ENTITIES', 'UTF-8'));
					} catch (Exception $e) {
						
					}
					
					$xpath = new DOMXPath($dom);
					$nodes = $xpath->query('//@*');
					
					if($strip_url) {
						foreach ($nodes as $node_key => $node) {
							if(
								!($node->parentNode->tagName === 'img' && $node->nodeName === 'src') &&
								!($node->parentNode->tagName === 'div' && $node->nodeName === 'class') &&
								!($node->parentNode->tagName === 'img' && $node->nodeName === 'class') &&
								!($node->parentNode->tagName === 'img' && $node->nodeName === 'alt') &&
								!($node->parentNode->tagName === 'div' && $node->nodeName === 'title') &&
								!($node->parentNode->tagName === 'tt') &&
								!($node->parentNode->tagName === 'span')
							) {
								$node->parentNode->removeAttribute($node->nodeName);
							}
						}
					} else {
						foreach ($nodes as $node_key => $node) {
							if(
								!($node->parentNode->tagName === 'a' && $node->nodeName === 'href') &&
								!($node->parentNode->tagName === 'a' && $node->nodeName === 'name') &&
								!($node->parentNode->tagName === 'img' && $node->nodeName === 'src') &&
								!($node->parentNode->tagName === 'div' && $node->nodeName === 'class') &&
								!($node->parentNode->tagName === 'img' && $node->nodeName === 'class') &&
								!($node->parentNode->tagName === 'img' && $node->nodeName === 'alt') &&
								!($node->parentNode->tagName === 'div' && $node->nodeName === 'title') &&
								!($node->parentNode->tagName === 'tt') &&
								!($node->parentNode->tagName === 'span')
							) {
								$node->parentNode->removeAttribute($node->nodeName);
							}
						}
					}
					
					/*
					$nodes = $xpath->query('//tt');
				#	foreach ($dom->getElementsByTagName('tt') as $dom_piece)
					foreach ($nodes as $node_key => $node)
					{
						$node_children = $xpath->query('//@*', $node);
						
						foreach($node_children as $node_child)
						{
							$node->parentNode->parentNode->appendChild($node_child);
						}
						$node->parentNode->removeChild($node);
						#$node->parentNode->replaceChild(, $node);
				#		$dom_piece->parentNode->removeChild($dom_piece);
					}
					
				#	print_r($dom);
					*/
					#print_r($dom->saveHTML());
					$textbody['Text'] = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace(['<html>', '</html>', '<body>', '</body>'], ['', '', '', ''],$dom->saveHTML()));
					
					$textbody['Text'] = str_replace('<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '<p>', $textbody['Text']);
					$textbody['Text'] = str_replace('<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '<p>', $textbody['Text']);
					$textbody['Text'] = str_replace('<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '<p>', $textbody['Text']);
					$textbody['Text'] = str_replace('<p>&nbsp;&nbsp;&nbsp;&nbsp;', '<p>', $textbody['Text']);
					$textbody['Text'] = str_replace('<p>&nbsp;&nbsp;&nbsp;', '<p>', $textbody['Text']);
					$textbody['Text'] = str_replace('<p>&nbsp;&nbsp;', '<p>', $textbody['Text']);
					$textbody['Text'] = str_replace('<p>&nbsp;', '<p>', $textbody['Text']);
					
					$textbody['Text'] = str_replace('<p> &nbsp;', '<p>', $textbody['Text']);
					$textbody['Text'] = str_replace('<p> &nbsp;&nbsp;', '<p>', $textbody['Text']);
					$textbody['Text'] = str_replace('<p> &nbsp;&nbsp;&nbsp;', '<p>', $textbody['Text']);
					$textbody['Text'] = str_replace('<p> &nbsp;&nbsp;&nbsp;&nbsp;', '<p>', $textbody['Text']);
					$textbody['Text'] = str_replace('<p> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '<p>', $textbody['Text']);
					$textbody['Text'] = str_replace('<p> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '<p>', $textbody['Text']);
					$textbody['Text'] = str_replace('<p> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '<p>', $textbody['Text']);
					
					$textbody['Text'] = preg_replace('/<p>Image::([0-9]+)[\s]*[&nbsp;]+[\s]*/', '<p>Image::$1 ', $textbody['Text']);
					
					$swap = [
						'/<pre><\/pre>/'=>'',
						'/(?<!>)(?<!<)[\s]+(?!<)(?!>)/'=>' ',
						"/<\/h([0-6]+)>[\n\r]*/"=>"</h$1>\n\n",
						"/<\/p>[\s]*<p>/"=>"</p>\n\n<p>",
						"/<\/p>[\s]*<h/"=>"</p>\n\n<h",
						
						"/<\/div>[\s]*/"=>"</div>\n\n",
						"/<br>[\r\n]*/"=>"<br>\n",
						"/[\s]+<\/p>/"=>"</p>",
						"/<p>[\s]+/"=>"<p>",
						"/<p>Image::([0-9]+)[\s]*/"=>"<p>Image::$1 ",
						'/[ ]+/'=>' ',
					];
					
					#print("BT: REPLACE???");
					$textbody['Text'] = preg_replace(array_keys($swap), array_values($swap), $textbody['Text']);
					#print("!!!");
					#print $textbody['Text'];
					#print("|||!!!");

					$textbody['Text'] = $preformatted_blocks->Restore(['text'=>$textbody['Text']]);

					$textbody['WordCount'] = str_word_count(strip_tags($textbody['Text']));
					$textbody['CharacterCount'] = strlen($textbody['Text']);
					
					if($americanize_vocabulary) {
						if(!$american_british_spellings) {
							ggreq('classes/Language/AmericanBritishSpellings.php');
							$american_british_spellings = new AmericanBritishSpellings([]);
						}
						
						$textbody['Text'] = $american_british_spellings->SwapBritishSpellingsForAmericanSpellings(['text'=>$textbody['Text']]);
					}
				}
				
				$new_textbodies[] = $textbody;
				$index++;
			}
			
			
			return $this->textbody = $new_textbodies;
		}
		
		public function PrepareRecordForSaving_Image() {
			$valid_images = [];
			
			$this->prepareImagesForSaving();
			$this->FilledRecordForSaving_Image();
			
			
			return TRUE;
		}
		
		public function prepareImagesForSaving() {
			for($i = 0; $i < count($this->image); $i++) {
				$this->image[$i] = $this->prepareImageForSaving(['image'=>$this->image[$i]]);
			}
			
			return TRUE;
		}
		
		public function prepareImageForSaving($args) {
			$image = $args['image'];
			$prefix = $this->entry['id'] . '-';
		#	print("BT: CHECK IMAGE???<BR><BR>");
			if(!$image['FileName']) {
				return '';
			}
			if(!$this->startsWith($image['FileName'], $prefix)) {
				$filename_pieces = explode('-', $image['FileName']);
				$possible_check = $filename_pieces[0] . '-';
				
				#print("BT: CHECK???" . $possible_check . "|" . $prefix . "|<BR><BR>");
				if($possible_check !== $prefix) {
				#	print("BT: prep IMG for save, GO!");
					$image['FileName'] = $prefix . $image['FileName'];
				}
			}
			return $image;
		}
		
		public function FilledRecordForSaving_Image() {
			$this->image_unfilled = $this->image;
			/*
			$image_ids = [];
			
			foreach($this->image as $image) {
				$image_ids[] = $image['id'];
			}
			
			sort($image_ids);
			
			$new_images = [];
			
			foreach($this->image as $image) {
				$image['id'] = array_shift($image_ids);
				$new_images[] = $image;
			}
			
			print("BT: NEW!");
			print("<PRE>");
			print_r($new_images);
			
			die();
			*/
			
			$image_selects = [
				'type'=>'Image',
				'definition'=>[
					'Entryid'=>$this->entry['id'],
				],
				'orderby'=>'OriginalCreationDate DESC',
			];
			
			$image_full_data = $this->db_access_object->GetRecords($image_selects);
			
		//	print("<PRE>");
		//	print_r($image_full_data);
		//	print("</PRE>");
			
			$image_full_data_hash = [];
			
			foreach($image_full_data as $full_image) {
				$image_full_data_hash[$full_image['FileName']] = $full_image;
			}
			
			$old_images = $this->image;
			$new_images = [];
			
			$repeatable_image_fields = [
				'FileName',
				'FileDirectory',
				'IconFileName',
				'StandardFileName',
				
				'PixelWidth',
				'PixelHeight',
				'IconPixelWidth',
				'IconPixelHeight',
				'StandardPixelWidth',
				'StandardPixelHeight',
			];
			
		//	print("<PRE>");
		//	print_r($image_full_data_hash);
		//	print("</PRE>");
			
		//	$index = 0;
			
			foreach($old_images as $old_image) {
					if($old_image && $old_image['FileName'] && $image_full_data_hash[$old_image['FileName']]) {
						$old_image_data = $image_full_data_hash[$old_image['FileName']];
						
						if($old_image['id'] != $old_image_data['id']) {
							$old_image['swapped'] = TRUE;
						}
						
						foreach($repeatable_image_fields as $repeatable_image_field) {
							$old_image[$repeatable_image_field] = $old_image_data[$repeatable_image_field];
						}
						
						$new_images[] = $old_image;
					} else {
						$new_images[] = $old_image;
					}
				
			}
			/*
			print("CURRENT!");
		
			print("<PRE>");
			print_r($this->image);
			print("</PRE>");
			
			print("NEW!");
			
			print("<PRE>");
			print_r($new_images);
			print("</PRE>");
			*/
		//	die();
		
				// BT: Here!
				//     1) match $this->image to $image_full_data;
				//     2) swap according to this->Param('Image_index-adjustment-NUMBER');
	//		die();
	//		return $this->image;
			return $this->image = $new_images;
		}
		
		public function PrepareRecordForSaving_ImageTranslation() {
			return TRUE;
		}
		
		public function PrepareRecordForSaving_Tag() {
			return TRUE;
		}
		
		public function PrepareRecordForSaving_Link() {
			return TRUE;
		}
		
			/*
				The currency is a select and always comes back chosen, so a
				row left without an amount would otherwise look filled in and
				be saved as a price of nothing.  Blanking its currency too
				lets SaveRecordFromQuery_Base() pass it over like any other
				empty row.  It is blanked rather than dropped, because the
				rows are matched to their ids by position.
			*/
		
		public function PrepareRecordForSaving_Price() {
			$prices = [];
			
			foreach ((array)$this->price as $price) {
				$price['PriceValue'] = trim((string)$price['PriceValue']);
				
				if(!strlen($price['PriceValue'])) {
					$price['Currency'] = '';
				}
				
				$prices[] = $price;
			}
			
			$this->price = $prices;
			
			return TRUE;
		}
		
			/*
				The verdict is a select and always comes back chosen, so a row
				left without a book would otherwise look filled in, as Price's
				currency would.  Its verdict is blanked so the row passes as
				empty, in place, because the rows are matched to their ids by
				position.  Every field is trimmed.
			*/
		
		public function PrepareRecordForSaving_AcademicReference() {
			$references = [];
			
			foreach ((array)$this->academicreference as $reference) {
				foreach ((array)$reference as $field => $value) {
					$reference[$field] = trim((string)$value);
				}
				
				if(!strlen($reference['ReferenceEntryid'])) {
					$reference['Verdict'] = '';
				}
				
				$references[] = $reference;
			}
			
			$this->academicreference = $references;
			
			return TRUE;
		}
		
		public function PrepareRecordForSaving_Publication() {
			$publications = [];
			
			foreach ((array)$this->publication as $publication) {
				foreach ((array)$publication as $field => $value) {
					$publication[$field] = trim((string)$value);
				}
				
				$publications[] = $publication;
			}
			
			$this->publication = $publications;
			
			return TRUE;
		}
		
		public function PrepareRecordForSaving_WordPiece() {
			$records = [];
			
			foreach ((array)$this->wordpiece as $record) {
				foreach ((array)$record as $field => $value) {
					$record[$field] = trim((string)$value);
				}
				
				$records[] = $record;
			}
			
			$this->wordpiece = $records;
			
			return TRUE;
		}
		
		public function PrepareRecordForSaving_WordForm() {
			$records = [];
			
			foreach ((array)$this->wordform as $record) {
				foreach ((array)$record as $field => $value) {
					$record[$field] = trim((string)$value);
				}
				
				$records[] = $record;
			}
			
			$this->wordform = $records;
			
			return TRUE;
		}
		
		public function PrepareRecordForSaving_EventDate() {
			$this->eventdate_unprepared = $this->eventdate;
			
			$new_event_dates = [];
			
			foreach($this->eventdate as $eventdate) {
				if($eventdate['EventDate']) {
					$eventdate['EventDateTime'] = $eventdate['EventDate'];
					
					$event_date_pieces = explode('-', $eventdate['EventDate']);
					
					$first_char = $eventdate['EventDate'][0];
					if($first_char === '-') {
						$year = (int)$event_date_pieces[1];
						unset($event_date_pieces[1]);
						$year *= -1;
					} else {
						$year = (int)$event_date_pieces[0];
					}
					
					if($year < 1000) {
						$diff = (($year - 1000) * (-1)) + 3000;
						$event_date_pieces[0] = $diff;
					}
					
					if(count($event_date_pieces) === 1) {
						$event_date_pieces[] = '00';
						$event_date_pieces[] = '00';
					} elseif(count($event_date_pieces) === 2) {
						$event_date_pieces[] = '00';
					}
					
					$eventdate['EventDate'] = implode('-', $event_date_pieces);
					
					$eventdate['EventDateTime'] = $eventdate['EventDate'];
					
					if($eventdate['EventTime']) {
						$eventdate['EventDateTime'] .= ' ' . date("H:i:00", strtotime($eventdate['EventTime']));
					} else {
						$eventdate['EventDateTime'] .= ' ' . '00:00:00';
					}
					
					if(!strlen($eventdate['Title'])) {
						$eventdate['Title'] = 'Publication';
					}
					
					unset($eventdate['EventDate']);
					unset($eventdate['EventTime']);
					
					$new_event_dates[] = $eventdate;
				}
			}
			
			$this->eventdate = $new_event_dates;
			
			return TRUE;
		}
		
		public function PrepareRecordForSaving_Association() {
			$this->association_unprepared = $this->association;
			
			$new_associations = [];
			
			foreach($this->association as $association) {
				if($association['ChosenEntryid'] && !$association['Type'] && !$association['SubType']) {
					$association['Type'] = 'Role';
					$association['SubType'] = 'Author';
				}
				$new_associations[] = $association;
			}
			
			return $this->association = $new_associations;
		}
		
		public function PrepareRecordForSaving_AvailabilityDates() {
			$this->availabilitydaterange_unprepared = $this->availabilitydaterange;
			
			$new_availability_date_ranges = [];
			
			foreach($this->availabilitydaterange as $availabilitydaterange) {
				$availabilitydaterange['AvailabilityStart'] = $availabilitydaterange['AvailabilityStartDate'];
				
				if($availabilitydaterange['AvailabilityStartTime']) {
					$availabilitydaterange['AvailabilityStart'] .= ' ' . date("H:i:00", strtotime($availabilitydaterange['AvailabilityStartTime']));
				} else {
					$availabilitydaterange['AvailabilityStart'] .= ' ' . '00:00:00';
				}
				
				$availabilitydaterange['AvailabilityEnd'] = $availabilitydaterange['AvailabilityEndDate'];
				
				if($availabilitydaterange['AvailabilityEndTime']) {
					$availabilitydaterange['AvailabilityEnd'] .= ' ' . date("H:i:00", strtotime($availabilitydaterange['AvailabilityEndTime']));
				} else {
					$availabilitydaterange['AvailabilityEnd'] .= ' ' . '00:00:00';
				}
				
				unset($availabilitydaterange['AvailabilityStartDate']);
				unset($availabilitydaterange['AvailabilityStartTime']);
				unset($availabilitydaterange['AvailabilityEndDate']);
				unset($availabilitydaterange['AvailabilityEndTime']);
				
				$new_availability_date_ranges[] = $availabilitydaterange;
			}
			
			return $this->availabilitydaterange = $new_availability_date_ranges;
		}
		
		public function PrepareRecordForSaving_Assignment() {
			$this->assignment = [];
			
			if($this->desired_action === 'Save' && $this->entry['id']) {
				$this->assignment['Parentid'] = $this->entry['id'];
			} elseif($this->parent['id']) {		// Populate Parentid if it's a non-master record?
				$this->assignment['Parentid'] = $this->parent['id'];
			}
			
			return TRUE;
		}
		
		public function PrepareRecordForSaving_EntryPermission() {
			return TRUE;
		}
		
		public function PrepareRecordForSaving_Definition() {
			return TRUE;
		}
	}

?>
