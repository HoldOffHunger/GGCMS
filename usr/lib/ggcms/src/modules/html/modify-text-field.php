<?php

		/*
			One of the entry's own fields as a text box on the modify
			form: the title, subtitle, list title, sort key, code and the
			child and grandchild nouns.  The title, subtitle and list
			title are followed by the checkboxes for the clean-ups the
			site's globals offer on them.

			Of those checkboxes, only Autoincrement Title and the title's
			and subtitle's Smart Title-Case are ever checked to start
			with, by AutoGenerateTitleDefault().  Americanize and
			De-Romanize Numbers once had checks of their own, by
			AmericanizeTitleDefault() and the like, which were commented
			out; they start unchecked.

			Switches:
			  name             the entry's field, ListTitleSortKey say
			  noamericanize    no Americanize checkboxes; Save and Update
			  nolisttitlecase  no Smart Title-Case on the list title;
			                   Save and Update
		*/

	class module_modifytextfield extends module_spacing {
		public $that;
		public $form;
		public $name;
		public $noamericanize;
		public $nolisttitlecase;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->form = new module_form;
			$this->name = $args['name'];
			$this->noamericanize = !empty($args['noamericanize']);
			$this->nolisttitlecase = !empty($args['nolisttitlecase']);
		}

		public function Display() {
			$this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$this->name,
				'size'=>60,
				'value'=>$this->that->entry[$this->name],
				'maxlength'=>255,
			]);

			switch($this->name) {
				case 'Title':
					return $this->DisplayTitleOptions();

				case 'Subtitle':
					return $this->DisplaySubtitleOptions();

				case 'ListTitle':
					return $this->DisplayListTitleOptions();
			}

			return TRUE;
		}

		public function DisplayTitleOptions() {
			$globals = $this->that->handler->globals;

			if($globals->TitleAutoIncrement()) {
				$this->DisplayCheckbox(['name'=>'autoincrement-title', 'label'=>'Autoincrement Title', 'checked'=>$globals->AutoGenerateTitleDefault()]);
			}

			if($globals->TitleAutoSmartTitleCase()) {
				$this->DisplayCheckbox(['name'=>'title-smart-title-case', 'label'=>'Smart Title-Case', 'checked'=>$globals->AutoGenerateTitleDefault()]);
			}

			if(!$this->noamericanize && $globals->TitleAmericanize()) {
				$this->DisplayCheckbox(['name'=>'title-americanize', 'label'=>'Americanize']);
			}

			if($globals->TitleDeRomanizeNumbers()) {
				$this->DisplayCheckbox(['name'=>'title-de-romanize-numbers', 'label'=>'De-Romanize Numbers']);
			}

			return TRUE;
		}

		public function DisplaySubtitleOptions() {
			$globals = $this->that->handler->globals;

			if($globals->SubTitleAutoSmartTitleCase()) {
				$this->DisplayCheckbox(['name'=>'subtitle-smart-title-case', 'label'=>'Smart Title-Case', 'checked'=>$globals->AutoGenerateTitleDefault()]);
			}

			if(!$this->noamericanize && $globals->SubtitleAmericanize()) {
				$this->DisplayCheckbox(['name'=>'subtitle-americanize', 'label'=>'Americanize']);
			}

			return TRUE;
		}

		public function DisplayListTitleOptions() {
			$globals = $this->that->handler->globals;

			if(!$this->nolisttitlecase && $globals->ListTitleAutoSmartTitleCase()) {
				$this->DisplayCheckbox(['name'=>'listtitle-smart-title-case', 'label'=>'Smart Title-Case']);
			}

			if(!$this->noamericanize && $globals->ListTitleAmericanize()) {
				$this->DisplayCheckbox(['name'=>'listtitle-americanize', 'label'=>'Americanize']);
			}

			return TRUE;
		}

		public function DisplayCheckbox($args) {
			print('<input type="checkbox" value="1" name="' . $args['name'] . '" id="' . $args['name'] . '" ');

			if($args['checked']) {
				print('CHECKED="CHECKED"');
			}

			print(' /> ' . $args['label']);

			return TRUE;
		}
	}

?>
