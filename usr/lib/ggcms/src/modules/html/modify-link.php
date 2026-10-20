<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's links on a modify.php form: the title, the URL and
			its language.  A link with either a title or a URL is shown.

			As Edit always printed it, the blank row's URL has no maxlength.
		*/

	class module_modifylink extends module_modifyrecords {
		public function Records() {
			return $this->that->link;
		}

		public function IsShown($args) {
			$link = $args['record'];

			return $link && (strlen($link['Title']) || strlen($link['URL']));
		}

		public function Slug() {
			return 'url';
		}

		public function AddLabel() {
			return 'Add URL';
		}

		public function DisplayFields($args) {
			$record = $args['record'];
			$mode = $args['mode'];

			$this->that->TitleDisplay();

			$title_args = ['type'=>'text', 'name'=>$this->Name(['name'=>'link_Title', 'mode'=>$mode]), 'size'=>40, 'maxlength'=>255];
			$url_args = ['type'=>'text', 'name'=>$this->Name(['name'=>'URL', 'mode'=>$mode]), 'size'=>40];

			if($mode === 'row') {
				$title_args['value'] = $record['Title'];
				$url_args['value'] = $record['URL'];
			}

			if($mode !== 'blank') {
				$url_args['maxlength'] = 255;
			}

			$this->form->DisplayFormField($title_args);

			print('URL : ');

			$this->form->DisplayFormField($url_args);

			print('Language : ');

			$this->DisplayLanguage(['name'=>'link_Language', 'record'=>$record, 'mode'=>$mode]);

			return TRUE;
		}
	}

?>
