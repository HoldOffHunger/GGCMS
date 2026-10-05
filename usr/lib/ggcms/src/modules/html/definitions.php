<?php

		/*
			A word's definitions, each on a catalogue card: the word, how it
			is said, its part of speech, its senses one to a paragraph, its
			etymology, and the dictionary it came from.

			The word is the page's own -- the tag on a page of entries
			sharing one, the word looked up on WordWeight -- and so are the
			definitions, which both set as $this->definitions.

				ggreq('modules/html/definitions.php');
				$definitions = new module_definitions(['that'=>$this, 'fold'=>TRUE]);
				$definitions->Display();

			Switches:
			  fold  TRUE where the definitions sit above something else -- a
			        tag's entries -- to show the first in full and fold the
			        rest away, and each card's senses past the second; FALSE
			        where the definitions are the page, as on WordWeight
		*/

	class module_definitions extends module_spacing {
		public $that;
		public $fold;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->fold = !empty($args['fold']);
		}

		public function Word() {
			return isset($this->that->tag_cleansed) && strlen((string) $this->that->tag_cleansed) ? $this->that->tag_cleansed : $this->that->word;
		}

		public function Display() {
			$definitions = array_values((array) $this->that->definitions);

			if(!$definitions) {
				return FALSE;
			}

			print('<section class="block definitions" aria-label="Definitions">');

			if(!$this->fold) {
				foreach($definitions as $definition) {
					$this->DisplayDefinition(['definition'=>$definition]);
				}
			} else {
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
			print('<dfn>' . $this->Word() . '</dfn>');

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
				dozen, which on a tag's page would push the tagged texts off
				the screen, so there they fold away past the first two.
			*/

		public function DisplaySenses($args) {
			$senses = preg_split('/\R\s*\R/', trim((string) $args['text']));
			$senses = array_values(array_filter(array_map('trim', $senses), 'strlen'));
			$shown = $this->fold ? 2 : count($senses);

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