<?php

		/*
			An entry's catalogue record and its citation: the facts a reader
			checks before trusting a text, and the line they need to cite
			it.  Every field is already stored; nothing here is typed in.

			For templates with a sidebar.  The record sits on a catalogue
			card (.idx), the library's own furniture.
		*/

	class module_entryrecord extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function DisplayRecord() {
			$fields = $this->RecordFields();

			print('<section class="idx record" aria-label="Catalogue record">');
			print('<div class="idx-head"><span>Catalogue record</span><span>No. ' . (int) $this->that->entry['id'] . '</span></div>');
			print('<dl>');

			foreach($fields as $label=>$value) {
				print('<div><dt>' . $label . '</dt><dd>' . $value . '</dd></div>');
			}

			print('</dl>');
			print('</section>');

			return TRUE;
		}

		public function RecordFields() {
			$fields = [];

			$parent = $this->Parent();

			if($parent) {
				$fields['Collection'] = '<a href="' . $this->ParentURL() . '">' . $parent['Title'] . '</a>';
			}

			$author = $this->Author();

			if($author) {
				$fields['Author'] = '<a href="' . $this->that->EntryAssociationURL(['section'=>'people', 'code'=>$author['Code']]) . '">' . $author['Title'] . '</a>';
			}

			$words = $this->WordCount();

			if($words) {
				$fields['Length'] = number_format($words) . ' words';
				$fields['Reading time'] = $this->ReadingTime(['words'=>$words]);
			}

			if($this->that->children_count) {
				$fields['Chapters'] = number_format($this->that->children_count);
			}

			$catalogued = $this->CataloguedDate();

			if($catalogued) {
				$fields['Catalogued'] = $catalogued;
			}

			$source = $this->Source();

			if($source) {
				$fields['Source'] = $this->that->HyperlinkizeText(['text'=>$source]);
			}

			return $fields;
		}

			/*
				A citation in the plain form most style guides accept: author,
				title in quotation marks, the library in italics, when it was
				catalogued, and the address.  Copying it is one click.
			*/

		public function DisplayCitation() {
			$author = $this->Author();
			$catalogued = $this->CataloguedDate();

			$citation = '';

			if($author) {
				$citation .= strip_tags($author['Title']) . '. ';
			}

			$citation .= '&ldquo;' . strip_tags($this->that->entry['Title']) . '.&rdquo; ';
			$citation .= '<em>' . strip_tags($this->SiteName()) . '</em>';

			if($catalogued) {
				$citation .= ', catalogued ' . $catalogued;
			}

			$citation .= '. ' . htmlspecialchars($this->CanonicalURL(), ENT_QUOTES, 'UTF-8');

			print('<section class="panel cite-panel" id="cite" aria-labelledby="cite-title">');
			print('<h2 class="panel-title" id="cite-title">Cite this text</h2>');
			print('<p class="cite" id="cite-text">' . $citation . '</p>');
			print('<p class="cite-actions"><button type="button" class="btn btn-line" id="cite-copy">Copy citation</button><span class="cite-state" id="cite-state" aria-live="polite"></span></p>');
			print('<script>
				(function() {
					var button = document.getElementById("cite-copy");
					var state = document.getElementById("cite-state");
					var text = document.getElementById("cite-text");

					button.addEventListener("click", function() {
						function select() {
							var range = document.createRange();
							range.selectNodeContents(text);
							var selection = window.getSelection();
							selection.removeAllRanges();
							selection.addRange(range);
							state.textContent = "Selected. Press Ctrl+C to copy.";
						}

						if(navigator.clipboard && navigator.clipboard.writeText) {
							navigator.clipboard.writeText(text.textContent.trim()).then(function() {
								state.textContent = "Copied.";
							}, select);
						} else {
							select();
						}
					});
				})();
			</script>');
			print('</section>');

			return TRUE;
		}

			// Data
			// -------------------------------------------------

		public function Parent() {
			if(count($this->that->record_list) < 2) {
				return NULL;
			}

			return $this->that->record_list[count($this->that->record_list) - 2];
		}

		public function ParentURL() {
			$pieces = is_array($this->that->object_list) ? $this->that->object_list : [];
			array_pop($pieces);

			return $pieces ? '/' . implode('/', $pieces) . '/view.php' : '/';
		}

		public function Author() {
			$associations = $this->that->entry['association'];

			if(!$associations) {
				return NULL;
			}

			foreach($associations as $association) {
				if($association['Type'] === 'Role' && $association['SubType'] === 'Author' && $association['entry'] && $association['entry']['id']) {
					return $association['entry'];
				}
			}

			return NULL;
		}

		public function WordCount() {
			if(empty($this->that->entry['textbody']) || empty($this->that->counts['textbody'])) {
				return 0;
			}

			$words = 0;

			foreach($this->that->entry['textbody'] as $textbody) {
				$words += (int) $textbody['WordCount'];
			}

			return $words;
		}

		public function ReadingTime($args) {
			$minutes = (int) round($args['words'] / 250);

			if($minutes < 1) {
				return 'Under a minute';
			}

			if($minutes < 90) {
				return 'About ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's');
			}

			return 'About ' . round($minutes / 60) . ' hours';
		}

		public function CataloguedDate() {
			$created = $this->that->entry['OriginalCreationDate'];

			if(!$created || strpos($created, '0000') === 0) {
				return '';
			}

			return date('j F Y', strtotime($created));
		}

		public function Source() {
			if(!empty($this->that->entry['textbody'][0]['Source'])) {
				return $this->that->entry['textbody'][0]['Source'];
			}

			if(!empty($this->that->entry['description'][0]['Source'])) {
				return $this->that->entry['description'][0]['Source'];
			}

			return '';
		}

		public function SiteName() {
			if($this->that->master_record && $this->that->master_record['Title']) {
				return $this->that->master_record['Title'];
			}

			return $this->that->handler->domain->primary_domain;
		}

		public function CanonicalURL() {
			$pieces = is_array($this->that->object_list) ? $this->that->object_list : [];
			$domain = $this->that->handler->domain->GetPrimaryDomain(['secure'=>1, 'lowercase'=>1, 'www'=>0]);

			return $domain . '/' . ($pieces ? implode('/', $pieces) . '/' : '') . 'view.php';
		}
	}

?>
