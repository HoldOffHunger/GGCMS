<?php

	/*
		The names a request uses for each table's fields, in one place.

		The Read stage takes its definitions from here, and the importer
		reads the same table to know which column of a file is which field,
		and to post an existing entry's records back unchanged.  A field
		added here is a field both understand.

		One stage of the belt described in ChangeRecords.php.
	*/

	trait ChangeRecordsVocabulary {
		public $entry;
		public $eventdate;

						// The Request's Vocabulary
						// ---------------------------------------------

			/*
				Every child record type the request carries, and the names its
				fields arrive under.  SetRecordFromQuery() stores each under the
				name with "<variablename>_" taken off, so 'tag_Language' is a
				tag's Language and 'Tag' its Tag.

				One table, read by both halves.  SetRecordFromQuery_*() take
				their definitions from it, and the importer reads it to know
				which column is which field and to post an existing entry's
				records back unchanged.  A field added here is a field both
				understand; there is no second list to forget.

				Order is the order modify.php reads them in.  'file' is the
				upload a type's records may carry.
			*/

		public function RecordQueryDefinitions() {
			return [
				'description'=>[
					'formal'=>'Description',
					'objectdefinition'=>['Description', 'description_Source', 'description_Language'],
				],
				'quote'=>[
					'formal'=>'Quote',
					'objectdefinition'=>['Quote', 'quote_Source', 'quote_Language'],
				],
				'textbody'=>[
					'formal'=>'TextBody',
					'objectdefinition'=>['Text', 'textbody_Source', 'textbody_Language'],
				],
				'image'=>[
					'formal'=>'Image',
					'file'=>'Image',
					'objectdefinition'=>['image_Title', 'image_Description', 'image_FileName'],
				],
				'imagetranslation'=>[
					'formal'=>'ImageTranslation',
					'objectdefinition'=>['imagetranslation_Title', 'imagetranslation_Description', 'imagetranslation_FileName', 'imagetranslation_Language'],
				],
				'tag'=>[
					'formal'=>'Tag',
					'objectdefinition'=>['Tag', 'tag_Language'],
				],
				'link'=>[
					'formal'=>'Link',
					'objectdefinition'=>['link_Title', 'URL', 'link_Language'],
				],
				'price'=>[
					'formal'=>'Price',
					'objectdefinition'=>['PriceValue', 'price_Currency'],
				],
				'academicreference'=>[
					'formal'=>'AcademicReference',
					'objectdefinition'=>[
						'academicreference_ReferenceEntryid',
						'academicreference_Headword',
						'academicreference_VolumeNumber',
						'academicreference_VolumeTitle',
						'academicreference_ChapterNumber',
						'academicreference_ChapterTitle',
						'academicreference_Page',
						'academicreference_Verdict',
						'academicreference_Note',
					],
				],
				'publication'=>[
					'formal'=>'Publication',
					'objectdefinition'=>[
						'publication_Authors',
						'publication_Editors',
						'publication_Translators',
						'publication_Publisher',
						'publication_Edition',
						'publication_Year',
						'publication_ISBN',
					],
				],
				'eventdate'=>[
					'formal'=>'EventDate',
					'objectdefinition'=>['EventDate', 'EventTime', 'eventdate_Title', 'eventdate_Description', 'eventdate_Language'],
				],
				'association'=>[
					'formal'=>'Association',
					'objectdefinition'=>['ChosenEntryid', 'association_Type', 'association_SubType'],
				],
				'availabilitydaterange'=>[
					'formal'=>'AvailabilityDateRange',
					'objectdefinition'=>['AvailabilityStartDate', 'AvailabilityStartTime', 'AvailabilityEndDate', 'AvailabilityEndTime'],
				],
				'definition'=>[
					'formal'=>'Definition',
					'objectdefinition'=>['Term', 'definition_Definition'],
				],
				'entrytranslation'=>[
					'formal'=>'EntryTranslation',
					'objectdefinition'=>[
						'entrytranslation_Title',
						'entrytranslation_Subtitle',
						'entrytranslation_ListTitle',
						'entrytranslation_ListTitleSortKey',
						'entrytranslation_Language',
					],
				],
			];
		}

			//  The args SetRecordFromQuery() takes, for one variable name.

		public function RecordQueryDefinition($args) {
			$variable_name = $args['variablename'];
			$definition = $this->RecordQueryDefinitions()[$variable_name];

			$set_record_from_query_args = [
				'variablename'=>$variable_name,
				'objectdefinition'=>$definition['objectdefinition'],
			];

			if(isset($definition['file'])) {
				$set_record_from_query_args['file'] = $definition['file'];
			}

			return $set_record_from_query_args;
		}

			/*
				The entry's own fields, as the request names them.  Code and
				Publish are an administrator's; SetRecordFromQuery_Entry() reads
				them only for one.
			*/

		public function EntryQueryFields() {
			return [
				'Title',
				'Subtitle',
				'ListTitle',
				'ListTitleSortKey',
				'ChildAdjective',
				'ChildNoun',
				'ChildNounPlural',
				'GrandChildAdjective',
				'GrandChildNoun',
				'GrandChildNounPlural',
				'Publish',
				'Code',
			];
		}

			/*
				The switches a request may carry beside its records -- the
				checkboxes on the Add and Edit forms that change how a value is
				cleaned before it is saved.  The textbody ones are positional,
				one per text body; the rest apply to the whole entry.
			*/

		public function EntryQueryOptions() {
			return [
				'title-smart-title-case'=>'Title in smart title case',
				'title-de-romanize-numbers'=>'Roman numerals in the title written as numbers',
				'subtitle-smart-title-case'=>'Subtitle in smart title case',
				'listtitle-smart-title-case'=>'List title in smart title case',
				'autogenerate-definitions'=>'Definitions generated from the text bodies',
				'autoincrement-title'=>'A blank title numbered after the last sibling',
			];
		}

		public function TextBodyQueryOptions() {
			return [
				'textbody_StripURLs'=>'StripURLs',
				'textbody_AmericanizeVocabulary'=>'AmericanizeVocabulary',
				'textbody_HTMLFormatting'=>'HTMLFormatting',
				'textbody_SpellCheckImmune'=>'SpellCheckImmune',
			];
		}

			/*
				Turned the other way: an entry's records as the request that
				would post them back unchanged.  This is what the Edit form is
				-- a request which, sent as it is, changes nothing -- written
				out from the records rather than read off the page.

				It wants the records as OrderAndFillChildRecords() and
				FormatDates() leave them, which is how Edit has them, with an
				event's date and time already in separate fields.  Text-body
				switches are posted one per text body, the way the form ticks
				them for an existing entry: every cleaning off, and Spell Check
				Immune as it was stored.
			*/

		public function RecordsAsQuery($args) {
			$entry = $args['entry'];
			$records = $args['records'];

			$query = [];

			foreach($this->EntryQueryFields() as $field) {
				$query[$field] = isset($entry[$field]) ? (string)$entry[$field] : '';
			}

			foreach($this->RecordQueryDefinitions() as $variable_name => $definition) {
				$type_records = isset($records[$variable_name]) && is_array($records[$variable_name]) ? array_values($records[$variable_name]) : [];

				foreach($definition['objectdefinition'] as $parameter) {
					$field = preg_replace('/^' . $variable_name . '_/', '', $parameter);
					$query[$parameter] = [];

					foreach($type_records as $record) {
						$query[$parameter][] = isset($record[$field]) ? (string)$record[$field] : '';
					}
				}

				if($variable_name === 'textbody') {
					foreach($this->TextBodyQueryOptions() as $parameter => $field) {
						$query[$parameter] = [];

						foreach($type_records as $record) {
							$query[$parameter][] = ($field === 'SpellCheckImmune' && !empty($record['SpellCheckImmune'])) ? '1' : '';
						}
					}
				}
			}

			return $query;
		}
		
		public function GetRecordTypes() {
			$record_types = array_merge(['Entry'=>'entry'], $this->GetStandardChildRecordTypes());
			
			return $record_types;
		}

			/*
				An event date before the year 1000 comes back from the database
				as "bce..."; the request writes it with a leading minus, so the
				Edit form shows it that way and Prepare encodes it again.
				Edit and the importer's reading of an existing entry both turn
				it round here.
			*/

		public function FormatDates() {
			$this->entry['eventdate'] = $this->FormatDates_dateList(['eventdates'=>$this->entry['eventdate']]);
			$this->eventdate = $this->FormatDates_dateList(['eventdates'=>$this->eventdate]);
			return TRUE;
		}

		public function FormatDates_dateList($args) {
			$event_dates = $args['eventdates'];

			if($event_dates) {
				$changed = FALSE;

				$event_dates_count = count($event_dates);

				for($i = 0; $i < $event_dates_count; $i++) {
					$event_date = $event_dates[$i];

					$event_date_time = $event_date['EventDateTime'];

					$bce_check = substr($event_date_time, 0, 3);

					if($bce_check === 'bce') {
						$event_date_time = str_replace('bce', '-', $event_date_time);
						$event_date_field_pieces = explode(' ', $event_date_time);
						$event_date['EventDateTime'] = $event_date_time;
						$event_date['EventDate'] = $event_date_field_pieces[0];
						$event_dates[$i] = $event_date;
					}
				}
			}

			return $event_dates;
		}
	}

?>
