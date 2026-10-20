<?php

		/*
			What a save or update wrote, in place of the form: a table
			for each element modifiableRecordTypes() lists, its label on
			the left and a plain-text summary of what it holds on the
			right, or N/A.  An admin is shown the records it created
			first.

			Two summaries lean on others, and always have.  Definition
			lists its terms only when the last tag Tag looked at had any
			text, as $this->tagtext; with no tags, or no Tag element,
			there are none.  Association lists only the associations with
			a type or a subtype.

			Switches:
			  associationtypes   print the association type and subtype
			                     datalists first; Update
			  imagetranslations  print the image translations, which are
			                     otherwise worked out and left unprinted;
			                     Update
			  eventdescriptions  bracket an event date's title and
			                     description, each only where it has one,
			                     rather than its title and language;
			                     Update
		*/

	class module_modifyresults extends module_spacing {
		public $that;
		public $divider;
		public $table;
		public $generic_list;
		public $associationtypes;
		public $imagetranslations;
		public $eventdescriptions;
		public $tagtext;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->divider = new module_divider;
			$this->table = new module_table;
			$this->generic_list = new module_genericlist;
			$this->associationtypes = !empty($args['associationtypes']);
			$this->imagetranslations = !empty($args['imagetranslations']);
			$this->eventdescriptions = !empty($args['eventdescriptions']);
		}

		public function Display() {
			if($this->that->authentication_object->CheckAuthenticationForCurrentObject_IsAdmin()) {
				$this->DisplayCreated();
			}

			if($this->associationtypes) {
				$this->that->displayAssociationTypes();
				$this->that->displayAssociationSubTypes();
			}

			foreach($this->that->modifiableRecordTypes() as $element) {
				$this->DisplayElement(['element'=>$element]);
			}

			return TRUE;
		}

		public function DisplayCreated() {
			$this->divider->displaystart(['class'=>'horizontal-center width-70percent']);

			$this->generic_list->Display([
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

			$this->table->DisplayComponent_StartTable([
				'id'=>$element,
				'tableclass'=>'width-100percent blank-element',
				'tableborder'=>'3',
				'cellvalign'=>'top',
			]);

			print($this->that->ElementDisplay(['element'=>$element]));

			$this->table->DisplayComponent_SeparateCells([
				'cellwidth'=>'80%',
				'cellvalign'=>'top',
			]);

			$summary = $this->Summary(['element'=>$element]);

			if($summary !== NULL) {
				print($summary);
			}

			$this->table->DisplayComponent_EndTable([]);

			return TRUE;
		}

			// Data
			// -------------------------------------------------

			// what the element's cell says, or NULL to leave it empty
		public function Summary($args) {
			$entry_fields = [
				'Title'=>'Title',
				'Subtitle'=>'Subtitle',
				'List Title'=>'ListTitle',
				'List Title Sort Key'=>'ListTitleSortKey',
				'Code'=>'Code',
				'Child Adjective'=>'ChildAdjective',
				'Child Noun'=>'ChildNoun',
				'Child Noun Plural'=>'ChildNounPlural',
				'Grand Child Adjective'=>'GrandChildAdjective',
				'Grand Child Noun'=>'GrandChildNoun',
				'Grand Child Noun Plural'=>'GrandChildNounPlural',
			];

			if(array_key_exists($args['element'], $entry_fields)) {
				return $this->that->entry[$entry_fields[$args['element']]];
			}

			switch($args['element']) {
				case 'Entry Translation':
					return $this->EntryTranslations();

				case 'Availability Dates':
					return $this->AvailabilityDates();

				case 'Description':
					return $this->Texts(['values'=>$this->that->description, 'field'=>'Description']);

				case 'Quote':
					return $this->Texts(['values'=>$this->that->quote, 'field'=>'Quote']);

				case 'Text Body':
					return $this->Texts(['values'=>$this->that->textbody, 'field'=>'Text']);

				case 'Image':
					return $this->Images();

				case 'Image Translation':
					return $this->ImageTranslations();

				case 'Tag':
					return $this->Tags();

				case 'Link':
					return $this->Links();

				case 'Event Date':
					return $this->EventDates();

				case 'Association':
					return $this->Associations();

				case 'Definition':
					return $this->Definitions();
			}

			return NULL;
		}

			// the lines, one under the other, or N/A when there are none
		public function Lines($args) {
			if(count($args['lines'])) {
				return implode('<br>', $args['lines']);
			}

			return 'N/A';
		}

		public function EntryTranslations() {
			$lines = [];

			foreach($this->that->entrytranslation as $entry_translation) {
				if($entry_translation['Title']) {
					$lines[] = $entry_translation['Title'] . ': ' . $entry_translation['Subtitle'] . ' (Language: ' . $entry_translation['Language'] . ')';
				}
			}

			return $this->Lines(['lines'=>$lines]);
		}

			// every range, and no N/A when there are none
		public function AvailabilityDates() {
			$lines = [];

			foreach($this->that->availabilitydaterange as $availabilitydaterange) {
				$lines[] = $availabilitydaterange['AvailabilityStart'] . ' to ' . $availabilitydaterange['AvailabilityEnd'];
			}

			return implode('<br>', $lines);
		}

			// descriptions, quotes and text bodies: the text and its language
		public function Texts($args) {
			$lines = [];

			foreach($args['values'] as $value) {
				if($value[$args['field']]) {
					$lines[] = $value[$args['field']] . ' (Language: ' . $value['Language'] . ')';
				}
			}

			return $this->Lines(['lines'=>$lines]);
		}

		public function Images() {
			$lines = [];

			foreach($this->that->image as $image) {
				if($image && $image['Title']) {
					$lines[] = $image['Title'] . ' (' . $image['FileName'] . ')';
				}
			}

			return $this->Lines(['lines'=>$lines]);
		}

		public function ImageTranslations() {
			$lines = [];

			foreach($this->that->imagetranslation as $image_translation) {
				if($image_translation['FileName']) {
					$lines[] = $image_translation['Title'] . ' (' . $image_translation['FileName'] . ' ~ ' . $image_translation['Language'] . ')';
				}
			}

			$text = $this->Lines(['lines'=>$lines]);

			return $this->imagetranslations ? $text : NULL;
		}

		public function Tags() {
			$lines = [];

			foreach($this->that->tag as $tag) {
				$this->tagtext = $tag['Tag'];

				if($this->tagtext) {
					$this->tagtext = '&bull; ' . $this->tagtext;

					if($tag['Language']) {
						$this->tagtext .= ' (' . $tag['Language'] . ')';
					}

					$lines[] = $this->tagtext;
				}
			}

			return $this->Lines(['lines'=>$lines]);
		}

		public function Links() {
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

			return $this->Lines(['lines'=>$lines]);
		}

		public function EventDates() {
			$lines = [];

			foreach($this->that->eventdate as $eventdate) {
				if($eventdate['EventDateTime']) {
					$lines[] = '&bull; ' . $eventdate['EventDateTime'] . $this->EventDateBrackets(['eventdate'=>$eventdate]);
				}
			}

			return $this->Lines(['lines'=>$lines]);
		}

		public function EventDateBrackets($args) {
			$eventdate = $args['eventdate'];

			if(!$this->eventdescriptions) {
				return ' (' . $eventdate['Title'] . ' ~ ' . $eventdate['Language'] . ')';
			}

			if(!$eventdate['Title'] && !$eventdate['Description']) {
				return '';
			}

			$parts = array_filter([$eventdate['Title'], $eventdate['Description']]);

			return ' (' . implode(' ~ ', $parts) . ')';
		}

		public function Associations() {
			$lines = [];

			foreach($this->that->association as $association) {
				if($association['ChosenEntryid'] && ($association['Type'] || $association['SubType'])) {
					$types = array_filter([$association['Type'], $association['SubType']]);

					$lines[] = '&bull; ' . $association['ChosenEntryid'] . ' (' . implode(' ~ ', $types) . ')';
				}
			}

			return $this->Lines(['lines'=>$lines]);
		}

		public function Definitions() {
			$lines = [];

			foreach($this->that->definition as $definition) {
				if($this->tagtext) {
					$lines[] = '&bull; ' . $definition['Term'] . ' : ' . $definition['Definition'];
				}
			}

			return $this->Lines(['lines'=>$lines]);
		}
	}

?>
