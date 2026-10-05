<?php

		/*
			The head of a page of entries sharing a tag: the tag as its
			title, how many entries carry it, and -- when the word is in the
			dictionaries -- what it means, on a catalogue card.

				ggreq('modules/html/tag-header.php');
				$tag_header = new module_tagheader(['that'=>$this]);
				$tag_header->Display();
				...
				$tag_header->DisplayDefinitions();

			It is the entry header with a different title and byline, so the
			site bar and the page head's pictures come from the same place.
		*/

	require_once(GGCMS_DIR . 'modules/html/entry-header.php');

	class module_tagheader extends module_entryheader {
		public function __construct($args) {
			$this->that = $args['that'];
			$this->header_text = $this->that->tag_cleansed;
			$this->record_list_count = count((array) $this->that->record_list);
		}

		public function DisplayKicker() {
			print('<p class="page-kicker">Tagged</p>');

			return TRUE;
		}

		public function DisplayByline() {
			$count = (int) $this->that->entry_count;

			print('<p class="page-byline">');
			print(number_format($count) . ' ' . ($count === 1 ? 'entry' : 'entries'));

			if($this->that->master_record && $this->that->master_record['Title']) {
				print(' in ' . $this->that->master_record['Title']);
			}

			print('</p>');

			return TRUE;
		}

			/*
				The word's definitions, folded so the tagged entries stay in
				reach.  The cards are module_definitions', which WordWeight's
				word pages share.
			*/

		public function DisplayDefinitions() {
			if(!$this->that->definition_count) {
				return FALSE;
			}

			require_once(GGCMS_DIR . 'modules/html/definitions.php');
			$definitions = new module_definitions(['that'=>$this->that, 'fold'=>TRUE]);

			return $definitions->Display();
		}
	}

?>
