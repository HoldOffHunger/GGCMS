<?php

	class module_alternateformats extends module_spacing {
		public $that;
		public $filename;
		public $audio;
		
		public function __construct($args) {
			$this->that = $args['that'];
			$this->filename = $this->that->handler->script_file;
			if(array_key_exists('audio', $args)) {
				$this->audio = $args['audio'];	// enable audio alternate-formats?
			} else {
				$this->audio = TRUE;
			}
		}
		
			/*
				Read aloud, then every other format the entry comes in.  In the
				flow of a page the formats fold into one Download control; a
				template with a sidebar prints DisplayListen() and
				DisplayFormats() where it wants them instead.
			*/

		public function Display() {
			print('<div class="reading-tools">');

			if($this->audio) {
				$this->DisplayListen();
			}

			print('<details class="formats-menu">');

			if($this->OffersDownloads()) {
				print('<summary class="action">Download<span class="action-count">' . count($this->getFormats()) . ' formats</span></summary>');
			} else {
				print('<summary class="action">Other editions</summary>');
			}

			$this->DisplayFormats();
			print('</details>');

			print('</div>');

			return TRUE;
		}

			/*
				The browser reads the text aloud -- text-audio.js, whose ids
				these are.  The voice and the word to start from are tucked
				away; most readers only want the button.
			*/

		public function DisplayListen() {
			print('<div class="listen">');
			print('<button type="button" id="play-text-as-audio" class="btn btn-primary">Listen</button>');
			print('<details class="listen-options">');
			print('<summary>Voice</summary>');
			print('<p class="field"><label for="voice-selection">Voice</label><select id="voice-selection"></select></p>');
			print('<p class="field"><label for="start-on">Start at word</label><input type="text" id="start-on" size="6" value="1" inputmode="numeric"> <span class="field-hint">of <span id="total-words">0</span></span></p>');
			print('</details>');
			print('<progress id="reading-progress-bar" value="0" max="100" aria-label="Reading progress"></progress>');
			print('</div>');

			return TRUE;
		}

			/*
				The formats in four groups a reader recognises, rather than as
				eighteen icons that say nothing until hovered.
			*/

		public function DisplayFormats() {
			$formats = [];

			foreach($this->getFormats() as $format) {
				$formats[$format['type']] = $format;
			}

			if($this->that->mobile_friendly) {
				$formats['mobile']['url'] = $this->filename . '.php';
				$formats['mobile']['label'] = 'Standard edition';
			}

			$labels = $this->getFormatLabels();

			print('<div class="formats">');

			foreach($this->getFormatGroups() as $group=>$types) {
				$present = array_values(array_filter($types, function($type) use ($formats) {
					return isset($formats[$type]);
				}));

				if(!$present) {
					continue;
				}

				print('<div class="formats-group">');
				print('<h3 class="formats-group-title">' . $group . '</h3>');
				print('<ul class="chips">');

				foreach($present as $type) {
					$format = $formats[$type];
					$label = !empty($format['label']) ? $format['label'] : $labels[$type];

					print('<li><a href="' . $format['url'] . '" rel="nofollow">' . $label . '</a></li>');
				}

				print('</ul>');
				print('</div>');
			}

			print('</div>');

			return TRUE;
		}

		public function getFormatGroups() {
			return [
				'On screen'=>['mobile', 'printer-friendly', 'inverted-colors'],
				'E-readers and print'=>['epub', 'pdf', 'rtf', 'plaintext', 'wrapped-plaintext'],
				'Listen and touch'=>['daisy', 'brf2', 'brf'],
				'For researchers'=>['json', 'xml', 'csv', 'latex', 'sgml', 'rdf', 'opds'],
			];
		}

		public function getFormatLabels() {
			return [
				'mobile'=>'Mobile',
				'printer-friendly'=>'Printer-friendly',
				'inverted-colors'=>'Inverted colours',
				'epub'=>'EPUB',
				'pdf'=>'PDF',
				'rtf'=>'RTF',
				'plaintext'=>'Plain text',
				'wrapped-plaintext'=>'Wrapped text',
				'daisy'=>'DAISY',
				'brf2'=>'Braille',
				'brf'=>'Braille, dotted',
				'json'=>'JSON',
				'xml'=>'XML',
				'csv'=>'CSV',
				'latex'=>'LaTeX',
				'sgml'=>'SGML',
				'rdf'=>'RDF',
				'opds'=>'OPDS',
			];
		}

			/*
				For a front page: what the formats are for, in the reader's
				words, one group at a time.  The site's best-kept secret was
				eighteen icons nobody could read; this says what they do.
			*/

		public function DisplayWays() {
			$formats = [];

			foreach($this->getFormats() as $format) {
				$formats[$format['type']] = $format;
			}

			$labels = $this->getFormatLabels();
			$notes = $this->getFormatGroupNotes();
			$icons = $this->getFormatGroupIcons();

			print('<section class="block ways" aria-labelledby="ways-title">');
			print('<div class="block-head">');
			print('<h2 class="block-title" id="ways-title">Read it your way</h2>');
			print('<p class="block-note">Besides the web page, every text comes in ' . $this->NumberWord(['number'=>count($formats)]) . ' other formats. It can also read itself to you.</p>');
			print('</div>');
			print('<div class="ways-grid">');

			foreach($this->getFormatGroups() as $group=>$types) {
				$present = array_values(array_filter($types, function($type) use ($formats) {
					return isset($formats[$type]);
				}));

				if(!$present) {
					continue;
				}

				print('<article class="way">');
				print('<div class="way-head">' . $icons[$group] . '<h3>' . $group . '</h3><span class="way-count">' . count($present) . '</span></div>');
				print('<p>' . $notes[$group] . '</p>');
				print('<ul class="chips chips-plain">');

				foreach($present as $type) {
					print('<li><span>' . $labels[$type] . '</span></li>');
				}

				print('</ul>');
				print('</article>');
			}

			print('</div>');
			print('</section>');

			return TRUE;
		}

		public function getFormatGroupNotes() {
			return [
				'On screen'=>'A mobile edition, a printer-friendly page, and inverted colours for reading at night.',
				'E-readers and print'=>'Send a text to an e-reader, or print a proper copy for the reading group.',
				'Listen and touch'=>'Every page can read itself aloud. For blind readers, DAISY talking books and Braille in two modes.',
				'For researchers'=>'Structured data for citation managers, text corpora and library catalogues.',
			];
		}

		public function getFormatGroupIcons() {
			$svg = '<svg class="way-icon" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';

			return [
				'On screen'=>$svg . '<rect x="2.5" y="4" width="19" height="13" rx="1"/><path d="M8 21h8M12 17v4"/></svg>',
				'E-readers and print'=>$svg . '<path d="M12 6.5C10 4.8 7 4.3 3 4.5v13c4-.2 7 .3 9 2 2-1.7 5-2.2 9-2v-13c-4-.2-7 .3-9 2z"/><path d="M12 6.5v13"/></svg>',
				'Listen and touch'=>'<svg class="way-icon" viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><g fill="currentColor"><circle cx="8" cy="5" r="2.1"/><circle cx="8" cy="12" r="2.1"/><circle cx="16" cy="12" r="2.1"/><circle cx="16" cy="19" r="2.1"/></g><g fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="16" cy="5" r="1.8"/><circle cx="8" cy="19" r="1.8"/></g></svg>',
				'For researchers'=>$svg . '<path d="M8 4c-2 0-3 1-3 3v2.5C5 11 4 12 3 12c1 0 2 1 2 2.5V17c0 2 1 3 3 3M16 4c2 0 3 1 3 3v2.5c0 1.5 1 2.5 2 2.5-1 0-2 1-2 2.5V17c0 2-1 3-3 3"/></svg>',
			];
		}

		public function NumberWord($args) {
			$words = [
				10=>'ten', 11=>'eleven', 12=>'twelve', 13=>'thirteen', 14=>'fourteen', 15=>'fifteen',
				16=>'sixteen', 17=>'seventeen', 18=>'eighteen', 19=>'nineteen', 20=>'twenty',
			];

			return isset($words[$args['number']]) ? $words[$args['number']] : number_format($args['number']);
		}

			/*
				Every format the entry comes in.  An entry the site keeps to the
				web page comes only on screen: its documents are its parent's,
				and asking for one goes there.  See OffersAlternateFormats() in
				view.php.
			*/

		public function OffersDownloads() {
			if(!method_exists($this->that, 'OffersAlternateFormats')) {
				return TRUE;
			}

			return $this->that->OffersAlternateFormats();
		}

		public function getFormats() {
			$formats = $this->getAllFormats();

			if($this->OffersDownloads()) {
				return $formats;
			}

			$groups = $this->getFormatGroups();

			return array_values(array_filter($formats, function($format) use ($groups) {
				return in_array($format['type'], $groups['On screen']);
			}));
		}

		public function getAllFormats() {
			return [
				[
					'text'=>'Mobile<br>Version',
					'type'=>'mobile',
					'url'=>$this->filename . '.php?mobilefriendly=1',
				],
				[
					'text'=>'PDF<br>File',
					'type'=>'pdf',
					'url'=>$this->filename . '.pdf',
				],
				[
					'text'=>'Printer<br>Friendly',
					'type'=>'printer-friendly',
					'url'=>$this->filename . '.php?printerfriendly=1',
				],
				[
					'text'=>'Plaintext<br>File',
					'type'=>'plaintext',
					'url'=>$this->filename . '.txt',
				],
				[
					'text'=>'Wrapped<br>Plaintext',
					'type'=>'wrapped-plaintext',
					'url'=>$this->filename . '.txt?wrapped=1',
				],
				[
					'text'=>'Inverted<br>Colors',
					'type'=>'inverted-colors',
					'url'=>$this->filename . '.php?invertedcolors=1',
				],
				[
					'text'=>'RTF<br>File',
					'type'=>'rtf',
					'url'=>$this->filename . '.rtf',
				],
				[
					'text'=>'BRF<br>File',
					'type'=>'brf2',
					'url'=>$this->filename . '.brf',
				],
				[
					'text'=>'Epub<br>File',
					'type'=>'epub',
					'url'=>$this->filename . '.epub',
				],
				[
					'text'=>'DAISY<br>Format',
					'type'=>'daisy',
					'url'=>$this->filename . '.daisy',
				],
				[
					'text'=>'SGML<br>Format',
					'type'=>'sgml',
					'url'=>$this->filename . '.sgml',
				],
				[
					'text'=>'JSON<br>Format',
					'type'=>'json',
					'url'=>$this->filename . '.json',
				],
				[
					'text'=>'XML<br>Format',
					'type'=>'xml',
					'url'=>$this->filename . '.xml',
				],
				[
					'text'=>'CSV<br>Format',
					'type'=>'csv',
					'url'=>$this->filename . '.csv',
				],
				[
					'text'=>'Latex<br>Format',
					'type'=>'latex',
					'url'=>$this->filename . '.tex',
				],
				[
					'text'=>'OPDS<br>Format',
					'type'=>'opds',
					'url'=>$this->filename . '.opds',
				],
				[
					'text'=>'RDF<br>Format',
					'type'=>'rdf',
					'url'=>$this->filename . '.rdf',
				],
				[
					'text'=>'BRF<br>File',
					'type'=>'brf',
					'url'=>$this->filename . '.brf?mode=dotted',
				],
			];
		}
	}
?>