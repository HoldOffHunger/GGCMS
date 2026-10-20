<?php

		/*
			The entry's links on the modify form: a title, the URL and its
			language, one row each.  See modify-repeater.php for the rows
			around them.

			Switches:
			  urlonly          a link gets a row only when it has a URL,
			                   not a title alone; Save and Update
			  languagefromurl  a value row's language is selected by the
			                   link's URL, so none is; Save
			  nolanguage       the Language : label with no select after
			                   it; Update
			  plaintitlelabel  label the title with a plain Title :
			                   rather than TitleDisplay(); Update
			  hiddentitlename  the hidden row's title's name in place of
			                   link_Title-Hidden; link_Title-Hidden[] on
			                   Save, link_Title[] on Update
		*/

	if(!class_exists('module_modifyrepeater')) {
		ggreq('modules/html/modify-repeater.php');
	}

	class module_modifylink extends module_modifyrepeater {
		public $code = 'url';
		public $label = 'URL';
		public $urlonly;
		public $languagefromurl;
		public $nolanguage;
		public $plaintitlelabel;
		public $hiddentitlename;

		public function __construct($args) {
			parent::__construct($args);

			$this->urlonly = !empty($args['urlonly']);
			$this->languagefromurl = !empty($args['languagefromurl']);
			$this->nolanguage = !empty($args['nolanguage']);
			$this->plaintitlelabel = !empty($args['plaintitlelabel']);
			$this->hiddentitlename = !empty($args['hiddentitlename']) ? $args['hiddentitlename'] : 'link_Title-Hidden';
		}

		public function Values() {
			return $this->that->link;
		}

			// Accepts()
			// Tests: ModifyLinkTest::testAccepts()
			// Test file: tests/src/modules/html/ModifyLinkTest.php
		public function Accepts($args) {
			$link = $args['value'];

			if($this->urlonly) {
				return $link && strlen($link['URL']);
			}

			return $link && (strlen($link['Title']) || strlen($link['URL']));
		}

		public function DisplayInputs($args) {
			$link = $args['value'];
			$row = $args['row'];

			if($this->plaintitlelabel) {
				print('Title : ');
			} else {
				$this->that->TitleDisplay();
			}

			$this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$row === 'hidden' ? $this->hiddentitlename : 'link_Title[]',
				'size'=>40,
				'value'=>$link['Title'],
				'maxlength'=>255,
			]);

			print('URL : ');

			$this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$this->Name(['name'=>'URL', 'row'=>$row]),
				'size'=>40,
				'value'=>$link['URL'],
				'maxlength'=>$row === 'blank' ? NULL : 255,
			]);

			print('Language : ');

			if(!$this->nolanguage) {
				$this->DisplayLanguage([
					'name'=>'link_Language',
					'row'=>$row,
					'selected'=>$row === 'value' ? $link[$this->languagefromurl ? 'URL' : 'Language'] : 'en',
				]);
			}

			return TRUE;
		}
	}

?>
