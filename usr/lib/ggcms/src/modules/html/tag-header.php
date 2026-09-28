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

		public function DisplayDefinitions() {
			if(!$this->that->definition_count) {
				return FALSE;
			}

			$definitions = array_values($this->that->definitions);

			print('<section class="block definitions" aria-label="Definitions">');

			$this->DisplayDefinition(['definition'=>$definitions[0]]);

				// a word with several entries -- noun, verb, adjective -- shows the first
			$rest = array_slice($definitions, 1);

			if($rest) {
				print('<details class="definitions-more">');
				print('<summary>' . count($rest) . ' more ' . (count($rest) === 1 ? 'definition' : 'definitions') . '</summary>');

				foreach($rest as $definition) {
					$this->DisplayDefinition(['definition'=>$definition]);
				}

				print('</details>');
			}

			print('</section>');

			return TRUE;
		}

		public function DisplayDefinition($args) {
			$definition = $args['definition'];

			print('<article class="idx definition">');

			print('<div class="idx-head">');
			print('<span>Definition</span>');

			if($definition['DictionaryTitle']) {
				print('<span>' . $definition['DictionaryTitle'] . '</span>');
			}

			print('</div>');

			print('<p class="definition-word">');
			print('<dfn>' . $this->that->tag_cleansed . '</dfn>');

			if($definition['Pronunciation']) {
				print(' <span class="definition-say">' . $definition['Pronunciation'] . '</span>');
			}

			if($definition['PartOfSpeech']) {
				print(' <span class="definition-pos">' . $definition['PartOfSpeech'] . '</span>');
			}

			print('</p>');

			$this->DisplaySenses(['text'=>$definition['Definition']]);

			if($definition['Etymology']) {
				print('<p class="definition-etymology"><span class="definition-label">Etymology</span> ' . $definition['Etymology'] . '</p>');
			}

			print('</article>');

			return TRUE;
		}

			/*
				The senses, one to a paragraph.  Webster's gives "state" a
				dozen, which would push the tagged texts off the screen, so
				past the first two they fold away.
			*/

		public function DisplaySenses($args) {
			$senses = preg_split('/\R\s*\R/', trim((string) $args['text']));
			$senses = array_values(array_filter(array_map('trim', $senses), 'strlen'));
			$shown = 2;

			if(!$senses) {
				return FALSE;
			}

			foreach(array_slice($senses, 0, $shown) as $sense) {
				print('<p class="definition-text">' . nl2br($sense, FALSE) . '</p>');
			}

			$rest = array_slice($senses, $shown);

			if(!$rest) {
				return TRUE;
			}

			print('<details class="definition-more">');
			print('<summary>' . count($rest) . ' more ' . (count($rest) === 1 ? 'sense' : 'senses') . '</summary>');

			foreach($rest as $sense) {
				print('<p class="definition-text">' . nl2br($sense, FALSE) . '</p>');
			}

			print('</details>');

			return TRUE;
		}
	}

?>
