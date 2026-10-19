<?php

		/*
			The entry's quotes on the modify form: the quote, its source
			and its language, one row each.  See modify-repeater.php for
			the rows around them.

			Switches:
			  hiddenlanguagelabel  the label before the hidden row's
			                       language; Language : on Save and
			                       Update, Language : without the last
			                       space by default
			  plainblank           the blank row's quote is not floated
			                       left and its language has no default;
			                       Save and Update
		*/

	if(!class_exists('module_modifyrepeater')) {
		ggreq('modules/html/modify-repeater.php');
	}

	class module_modifyquote extends module_modifyrepeater {
		public $code = 'quote';
		public $label = 'Quote';
		public $hiddenlanguagelabel;
		public $plainblank;

		public function __construct($args) {
			parent::__construct($args);

			$this->hiddenlanguagelabel = !empty($args['hiddenlanguagelabel']) ? $args['hiddenlanguagelabel'] : 'Language :';
			$this->plainblank = !empty($args['plainblank']);
		}

		public function Values() {
			return $this->that->quote;
		}

		public function Accepts($args) {
			return $args['value'] && strlen($args['value']['Quote']);
		}

		public function DisplayInputs($args) {
			$quote = $args['value'];
			$row = $args['row'];
			$plain = $this->plainblank && $row === 'blank';

			$this->form->DisplayFormField([
				'type'=>'textarea',
				'name'=>$row === 'hidden' ? 'Quote-Hidden' : 'Quote[]',
				'cols'=>60,
				'rows'=>5,
				'value'=>$quote['Quote'],
				'maxlength'=>$row === 'hidden' ? NULL : 512,
				'class'=>$plain ? NULL : 'float-left',
			]);

			$this->that->SourceDisplay();

			$this->form->DisplayFormField([
				'type'=>'text',
				'name'=>$this->Name(['name'=>'quote_Source', 'row'=>$row]),
				'size'=>30,
				'value'=>$quote['Source'],
				'maxlength'=>512,
			]);

			print($row === 'hidden' ? $this->hiddenlanguagelabel : 'Language : ');

			$this->DisplayLanguage([
				'name'=>'quote_Language',
				'row'=>$row,
				'selected'=>$row === 'value' ? $quote['Language'] : ($plain ? NULL : 'en'),
			]);

			return TRUE;
		}
	}

?>
