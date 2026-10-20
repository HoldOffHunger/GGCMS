<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's descriptions on a modify.php form: the text, its
			source and its language.

			Quotes are entered the same way and extend this
			(modify-quote.php), changing only the names below.
		*/

	class module_modifydescription extends module_modifyrecords {
		public $records_name = 'description';
		public $text_name = 'Description';
		public $prefix = 'description';
		public $slug = 'description';
		public $add_label = 'Add Description';

		public function Records() {
			return $this->that->{$this->records_name};
		}

		public function IsShown($args) {
			return $args['record'] && strlen($args['record'][$this->text_name]);
		}

		public function Slug() {
			return $this->slug;
		}

		public function AddLabel() {
			return $this->add_label;
		}

		public function DisplayFields($args) {
			$record = $args['record'];
			$mode = $args['mode'];
			$row = $mode === 'row';

			$text_args = [
				'type'=>'textarea',
				'name'=>$this->Name(['name'=>$this->text_name, 'mode'=>$mode]),
				'cols'=>60,
				'rows'=>5,
				'class'=>'float-left',
			];

			if($row) {
				$text_args['value'] = $record[$this->text_name];
				$text_args['maxlength'] = 512;
			}

			$this->form->DisplayFormField($text_args);

			$this->DisplaySource($args);

			print($mode === 'hidden' ? 'Language :' : 'Language : ');

			$this->DisplayLanguage(['name'=>$this->prefix . '_Language', 'record'=>$record, 'mode'=>$mode]);

			return TRUE;
		}

		public function DisplaySource($args) {
			$this->that->SourceDisplay();

			$source_args = [
				'type'=>'text',
				'name'=>$this->Name(['name'=>$this->prefix . '_Source', 'mode'=>$args['mode']]),
				'size'=>30,
				'maxlength'=>512,
			];

			if($args['mode'] === 'row') {
				$source_args['value'] = $args['record']['Source'];
			}

			$this->form->DisplayFormField($source_args);

			return TRUE;
		}
	}

?>
