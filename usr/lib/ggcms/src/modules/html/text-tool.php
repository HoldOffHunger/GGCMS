<?php

	require_once(GGCMS_DIR . 'modules/html/entry-header.php');
	require_once(GGCMS_DIR . 'modules/html/entry-share.php');
	require_once(GGCMS_DIR . 'modules/html/similarsites-satellites.php');
	require_once(GGCMS_DIR . 'modules/html/languages.php');
	require_once(GGCMS_DIR . 'modules/html/navigation.php');

		/*
			The page of one of the text tools -- RemoveSpacing,
			RemoveBlankLines, RemoveDuplicateLines, SortWords, ListKeywords and
			PronounceThat: a box to paste into, a box the result appears in
			where the tool has one, a button, and the choices beside it, all
			worked in the browser by the tool's own script.  The template says
			what its tool is; this lays it out.

				ggreq('modules/html/text-tool.php');
				$tool = new module_texttool(['that'=>$this]);
				$tool->Display(['file'=>__FILE__, 'tool'=>[...]]);

			A tool is its boxes' headings and placeholders, its button, a
			select, and any checkboxes.  Translate() is the lookup the
			templates used to write out longhand for every label: English for
			an English reader, otherwise the site's list for the reader's
			language, and the English where that is empty.  A tool whose
			template translates for itself -- PronounceThat keeps its words in
			the template, one set per language -- says 'translated'=>TRUE, and
			its words are printed as given.

			The ids and classes are the scripts' own -- .input-area and
			.output-area, #status-text, the button's, the select's, each
			checkbox's -- so every tool's script works unchanged.  The
			placeholders are placeholders: the old pages wrote them into the
			boxes as text, which a reader had to delete before pasting.

			Line numbers come from CodeMirror, through text-tool-editor.js; the
			old .lined class and its textarea plugin are gone, since only
			CodeMirror keeps its numbers level with the lines for long.
		*/

	class module_texttool extends module_spacing {
		public $that;
		public $translated;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function Translate($list_title, $english) {
			$code = $this->that->language_object->getLanguageCode();

			if($code === 'en') {
				return $english;
			}

			$translations = $this->that->getListAndItems(['ListTitle'=>$list_title]);

			return (is_array($translations) && !empty($translations[$code])) ? $translations[$code] : $english;
		}

			// a label: translated from its list, or as given by a template that translated it already

		public function Text($list_title, $text) {
			return $this->translated ? $text : $this->Translate($list_title, $text);
		}

		public function Display($args) {
			$tool = $args['tool'];
			$that = $this->that;

			$this->translated = !empty($tool['translated']);

			print('<a id="top"></a>');

			$entryheader = new module_entryheader(['that'=>$that]);
			$entryheader->DisplaySiteBar();

			if($that->authentication_object->user_session['UserAdmin.id']) {
				require_once(GGCMS_DIR . 'modules/html/entry-controls.php');
				$entry_controls = new module_entrycontrols;
				$entry_controls->Display(['that'=>$that, 'file'=>$args['file']]);
			}

			$this->DisplayHead(['tool'=>$tool]);
			$this->DisplayBench(['tool'=>$tool]);
			$this->DisplaySisters();

			$share = new module_entryshare(['that'=>$that]);
			$share->Display();

			$languages = new module_languages([
				'languageobject'=>$that->language_object,
				'domainobject'=>$that->domain_object,
			]);
			$languages->display();

			$navigation = new module_navigation([
				'globals'=>$that->handler->globals,
				'languageobject'=>$that->language_object,
				'domainobject'=>$that->domain_object,
			]);
			$navigation->DisplayBottomNavigation(['thispage'=>'']);

			return TRUE;
		}

			/*
				The tool's name and what it does.  The page title is "Name :
				Tagline", translated with the rest of the page, so the two are
				read from it; the instructions are the site's description,
				unless the tool gives its own.
			*/

		public function DisplayHead($args) {
			$tool = $args['tool'];
			$that = $this->that;

			$title = (string) $that->header_title_text;
			$tagline = '';

			if(strpos($title, ' : ') !== FALSE) {
				[$title, $tagline] = explode(' : ', $title, 2);
			}

			if(array_key_exists('instructions', $tool)) {
				$instructions = $tool['instructions'];
			}
			else {
				$description = ($that->master_record['description'] && $that->master_record['description'][0]) ? $that->master_record['description'][0]['Description'] : '';
				$instructions = $this->Text('LanguagesMainInstructionsContent', $description);
			}

			print('<header class="tool-head">');
			print('<div class="tool-head-inner">');

			if(!empty($tool['mark'])) {
				print('<span class="tool-mark" aria-hidden="true">' . $tool['mark'] . '</span>');
			}

			print('<div class="tool-head-text">');
			print('<h1 class="tool-title">' . $title . '</h1>');

			if($tagline) {
				print('<p class="tool-tagline">' . $tagline . '</p>');
			}

			if($instructions) {
				print('<p class="tool-instructions">' . $instructions . '</p>');
			}

			print('</div>');
			print('</div>');
			print('</header>');

			return TRUE;
		}

			/*
				The bench: the choices and the button across the top, then the
				boxes side by side -- one above the other on a narrow screen,
				or one alone for a tool with no result to show -- and the
				status under them, for a tool that reports one.
			*/

		public function DisplayBench($args) {
			$tool = $args['tool'];
			$button_text = $this->Text('LanguagesMainButtonText', $tool['button']['text']);

			print('<section class="block page-wide tool-bench" aria-label="' . $this->Attribute($button_text) . '">');

			print('<div class="tool-controls">');

			if(!empty($tool['select'])) {
				$this->DisplaySelect(['select'=>$tool['select']]);
			}

			foreach((array) ($tool['checkboxes'] ?? []) as $checkbox) {
				$this->DisplayCheckbox(['checkbox'=>$checkbox]);
			}

			$button = $tool['button'];
			print('<input type="button" id="' . $button['id'] . '" class="' . trim(($button['class'] ?? '') . ' tool-button') . '" value="' . $this->Attribute($button_text) . '">');

			print('</div>');

			print('<div class="tool-panes' . (empty($tool['output']) ? ' is-single' : '') . '">');
			$this->DisplayPane(['side'=>'input', 'number'=>1, 'pane'=>$tool['input']]);

			if(!empty($tool['output'])) {
				$this->DisplayPane(['side'=>'output', 'number'=>2, 'pane'=>$tool['output']]);
			}

			print('</div>');

			if(!array_key_exists('status', $tool) || $tool['status']) {
				print('<p class="tool-status">');
				print('<span class="tool-status-label">' . $this->Text('LanguagesMainStatusHeader', 'Status') . '</span> ');
				print('<span id="status-text">' . $this->Text('LanguagesMainActivityHeader', 'Waiting for User') . '</span>');
				print('</p>');
			}

			print('</section>');

			return TRUE;
		}

		public function DisplayPane($args) {
			$side = $args['side'];
			$number = $args['number'];
			$pane = $args['pane'];

			$heading = $this->Text('LanguagesMainList' . $number . 'Header', $pane['heading']);
			$placeholder = $this->Text('LanguagesMainList' . $number . 'Content', $pane['placeholder']);
			$rows = $pane['rows'] ?? 18;

			print('<div class="tool-pane is-' . $side . '">');
			print('<label class="tool-pane-title" for="' . $side . '-area">' . $heading . '</label>');
			print('<textarea id="' . $side . '-area" name="' . $side . '-area" class="' . $side . '-area" rows="' . (int) $rows . '" spellcheck="false" placeholder="' . $this->Attribute($placeholder) . '"></textarea>');
			print('</div>');

			return TRUE;
		}

		public function DisplaySelect($args) {
			$select = $args['select'];

			print('<label class="tool-choice tool-select">');

			if(!empty($select['label'])) {
				print('<span class="tool-choice-label">' . $select['label'] . '</span>');
			}

			print('<select id="' . $select['id'] . '" name="' . $select['id'] . '"' . (!empty($select['class']) ? ' class="' . $select['class'] . '"' : '') . '>');

			foreach($select['options'] as $option) {
				$list = $option['list'] ?? NULL;
				$title = $list ? $this->Text($list . 'Title', $option['title']) : $option['title'];
				$mouseover = $list ? $this->Text($list . 'Mouseover', $option['mouseover'] ?? '') : ($option['mouseover'] ?? '');

				print('<option value="' . $option['value'] . '"' . ($mouseover ? ' title="' . $this->Attribute($mouseover) . '"' : '') . '>' . $title . '</option>');
			}

			print('</select>');
			print('</label>');

			return TRUE;
		}

		public function DisplayCheckbox($args) {
			$checkbox = $args['checkbox'];

			$label = $this->Text($checkbox['list'] . 'Option', $checkbox['label']);
			$description = $this->Text($checkbox['list'] . 'Description', $checkbox['description']);

			print('<label class="tool-choice tool-check" title="' . $this->Attribute($description) . '">');
			print('<input type="checkbox" id="' . $checkbox['id'] . '" name="' . $checkbox['id'] . '" class="' . $checkbox['class'] . '" value="1"' . (!empty($checkbox['checked']) ? ' checked' : '') . '>');
			print('<span>' . $label . '</span>');
			print('</label>');

			return TRUE;
		}

			/*
				The rest of the family, from the satellites module's own list
				of them -- the same sites, slogans and translations the old
				"Similar Sites of Interest" list printed.
			*/

		public function DisplaySisters() {
			$that = $this->that;

			$satellites = new module_similarsites_satellites([
				'site'=>$that->domain_object->primary_domain_lowercased,
				'language'=>$that->language_object,
			]);

			$satellites->DisplayCards();

			return TRUE;
		}

		public function Attribute($text) {
			return str_replace('"', '&quot;', strip_tags((string) $text));
		}
	}

?>
