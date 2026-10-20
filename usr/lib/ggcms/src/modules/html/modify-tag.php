<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's tags on a modify.php form: the tag and its language.
		*/

	class module_modifytag extends module_modifyrecords {
		public function Records() {
			return $this->that->tag;
		}

		public function IsShown($args) {
			return $args['record'] && strlen($args['record']['Tag']);
		}

		public function Slug() {
			return 'tag';
		}

		public function AddLabel() {
			return 'Add Tag';
		}

		public function DisplayFields($args) {
			$mode = $args['mode'];

			print('Tag : ');

			$tag_args = [
				'type'=>'text',
				'name'=>$this->Name(['name'=>'Tag', 'mode'=>$mode]),
				'size'=>40,
				'maxlength'=>255,
				'class'=>'tag',
			];

			if($mode === 'row') {
				$tag_args['value'] = $args['record']['Tag'];
			}

			$this->form->DisplayFormField($tag_args);

			print('Language : ');

			$this->DisplayLanguage(['name'=>'tag_Language', 'record'=>$args['record'], 'mode'=>$mode]);

			return TRUE;
		}
	}

?>
