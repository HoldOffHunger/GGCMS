<?php

	require_once(GGCMS_DIR . 'modules/html/divider.php');
	require_once(GGCMS_DIR . 'modules/html/table.php');
	require_once(GGCMS_DIR . 'modules/html/list/generic.php');

		/*
			What a successful Save or Update saved, for Save.php and
			Update.php: to an admin, the ids of the records created; then a
			table per modifiable field, in the order modifiableRecordTypes()
			gives, with what the field now holds, or N/A.

			Kept as the two templates printed it, oddities included:

			  - Availability, Publish and Save have a table with nothing in
			    it; the dates' case was for 'Availability Dates', which
			    modifiableRecordTypes() never returns.
			  - An association is listed only when it has a type or a
			    subtype.
			  - Definitions are listed only when the last tag looked at had
			    text: the test was on $tag_text, the tags' variable, and
			    $last_tag_text is that.
		*/

	class module_modifyresults extends module_spacing {
		public $that;
		public $divider;
		public $table;
		public $last_tag_text;

		public function __construct($args) {
			$this->that = $args['that'];

			$this->divider = new module_divider;
			$this->table = new module_table;
		}

		public function Display() {
			if($this->that->authentication_object->CheckAuthenticationForCurrentObject_IsAdmin()) {
				$this->DisplayCreatedRecords();
			}

			$this->that->displayAssociationTypes();
			$this->that->displayAssociationSubTypes();

			foreach($this->that->modifiableRecordTypes() as $element) {
				$this->DisplayElement(['element'=>$element]);
			}

			return TRUE;
		}

		public function DisplayCreatedRecords() {
			$this->divider->displaystart(['class'=>'horizontal-center width-70percent']);

			#print_r($this->GetCreatedRecordsList());

			#print("sup?");

			$generic_list = new module_genericlist;
			$generic_list->Display([
				'options'=>[
					'tableheaders'=>0,
					'tableclass'=>'width-100percent horizontal-center border-2px background-color-gray13 margin-5px',
					'rowclass'=>'border-1px horizontal-left',
					'cellclass'=>[
						'border-1px vertical-top',
						'border-1px width-100percent vertical-top',
					],
				],
				'list'=>$this->that->GetCreatedRecordsList(),
			]);

			$this->divider->displayend([]);

			return TRUE;
		}

		public function DisplayElement($args) {
			$element = $args['element'];

			$this->table->DisplayComponent_StartTable(['id'=>$element, 'tableclass'=>'width-100percent blank-element', 'tableborder'=>'3', 'cellvalign'=>'top']);

			print($this->that->ElementDisplay(['element'=>$element]));

			$this->table->DisplayComponent_SeparateCells(['cellwidth'=>'80%', 'cellvalign'=>'top']);

			$this->DisplayValue($args);

			$this->table->DisplayComponent_EndTable([]);

			return TRUE;
		}

			// The entry's own fields print as they are; a field with many
			// records prints one line each, or N/A.

		public function DisplayValue($args) {
			$element = $args['element'];
			$entry_fields = ['Title', 'Subtitle', 'List Title', 'List Title Sort Key', 'Code', 'Child Adjective', 'Child Noun', 'Child Noun Plural', 'Grand Child Adjective', 'Grand Child Noun', 'Grand Child Noun Plural'];

			if(in_array($element, $entry_fields, TRUE)) {
				print($this->that->entry[str_replace(' ', '', $element)]);

				return TRUE;
			}

			$lines = $this->Lines($args);

			if($lines === NULL) {
				return FALSE;
			}

			print(count($lines) ? implode('<br>', $lines) : 'N/A');

			return TRUE;
		}

		public function Lines($args) {
			switch($args['element']) {
				case 'Entry Translation':
					return $this->EntryTranslationLines();

				case 'Description':
					return $this->TextLines(['records'=>$this->that->description, 'field'=>'Description']);

				case 'Quote':
					return $this->TextLines(['records'=>$this->that->quote, 'field'=>'Quote']);

				case 'Text Body':
					return $this->TextLines(['records'=>$this->that->textbody, 'field'=>'Text']);

				case 'Image':
					return $this->ImageLines();

				case 'Image Translation':
					return $this->ImageTranslationLines();

				case 'Tag':
					return $this->TagLines();

				case 'Link':
					return $this->LinkLines();

				case 'Event Date':
					return $this->EventDateLines();

				case 'Association':
					return $this->AssociationLines();

				case 'Definition':
					return $this->DefinitionLines();
			}

			return NULL;
		}

			// Lines
			// -------------------------------------------------

		public function EntryTranslationLines() {
			$lines = [];

			foreach($this->that->entrytranslation as $entry_translation) {
				if($entry_translation['Title']) {
					$lines[] = $entry_translation['Title'] . ': ' . $entry_translation['Subtitle'] . ' (Language: ' . $entry_translation['Language'] . ')';
				}
			}

			return $lines;
		}

			// Descriptions, quotes and text bodies: the text and its language.

		public function TextLines($args) {
			$lines = [];

			foreach($args['records'] as $record) {
				if($record[$args['field']]) {
					$lines[] = $record[$args['field']] . ' (Language: ' . $record['Language'] . ')';
				}
			}

			return $lines;
		}

		public function ImageLines() {
			$lines = [];

			foreach($this->that->image as $image) {
				if($image && $image['Title']) {
					$lines[] = $image['Title'] . ' (' . $image['FileName'] . ')';
				}
			}

			return $lines;
		}

		public function ImageTranslationLines() {
			$lines = [];

			foreach($this->that->imagetranslation as $image_translation) {
				if($image_translation['FileName']) {
					$lines[] = $image_translation['Title'] . ' (' . $image_translation['FileName'] . ' ~ ' . $image_translation['Language'] . ')';
				}
			}

			return $lines;
		}

		public function TagLines() {
			$lines = [];

			foreach($this->that->tag as $tag) {
				$tag_text = $tag['Tag'];

				if($tag_text) {
					$tag_text = '&bull; ' . $tag_text;

					if($tag['Language']) {
						$tag_text .= ' (' . $tag['Language'] . ')';
					}

					$lines[] = $tag_text;
				}

				$this->last_tag_text = $tag_text;
			}

			return $lines;
		}

		public function LinkLines() {
			$lines = [];

			foreach($this->that->link as $link) {
				if($link['URL']) {
					$line = '&bull; <a href="' . $link['URL'] . '" target="_blank">' . $link['Title'];

					if($link['Language']) {
						$line .= ' (' . $link['Language'] . ')';
					}

					$lines[] = $line . '</a>';
				}
			}

			return $lines;
		}

		public function EventDateLines() {
			$lines = [];

			foreach($this->that->eventdate as $eventdate) {
				if($eventdate['EventDateTime']) {
					$lines[] = '&bull; ' . $eventdate['EventDateTime'] . $this->Parenthetical(['first'=>$eventdate['Title'], 'second'=>$eventdate['Description']]);
				}
			}

			return $lines;
		}

		public function AssociationLines() {
			$lines = [];

			foreach($this->that->association as $association) {
				if($association['ChosenEntryid'] && ($association['Type'] || $association['SubType'])) {
					$lines[] = '&bull; ' . $association['ChosenEntryid'] . $this->Parenthetical(['first'=>$association['Type'], 'second'=>$association['SubType']]);
				}
			}

			return $lines;
		}

		public function DefinitionLines() {
			$lines = [];

			foreach($this->that->definition as $definition) {
				if($this->last_tag_text) {
					$lines[] = '&bull; ' . $definition['Term'] . ' : ' . $definition['Definition'];
				}
			}

			return $lines;
		}

			// " (first ~ second)", or either alone, or nothing.

		public function Parenthetical($args) {
			$parts = array_filter([$args['first'], $args['second']]);

			if(!$parts) {
				return '';
			}

			return ' (' . implode(' ~ ', $parts) . ')';
		}
	}

?>
