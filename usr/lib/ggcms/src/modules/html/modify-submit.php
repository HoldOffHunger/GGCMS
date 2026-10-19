<?php

		/*
			The modify form's buttons, in the Save element's row: Update,
			and beside it Delete when the entry has a parent and the user
			may delete it, then the hidden action they post.

			The Update row closes one more <center> than it opens; the
			Save row does not.

			Switches:
			  save          a Save button posting Save instead, and no
			                Delete; Save
			  nolabel       no "Update This Entry : " before Update;
			                Update
			  alwaysdelete  Delete whatever the parent and the user;
			                Update
		*/

	class module_modifysubmit extends module_spacing {
		public $that;
		public $form;
		public $table;
		public $save;
		public $nolabel;
		public $alwaysdelete;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->form = new module_form;
			$this->table = new module_table;
			$this->save = !empty($args['save']);
			$this->nolabel = !empty($args['nolabel']);
			$this->alwaysdelete = !empty($args['alwaysdelete']);
		}

		public function Display() {
			if($this->save) {
				return $this->DisplaySave();
			}

			print($this->nolabel ? '<center>' : '<center>Update This Entry : ');

			$this->form->DisplayFormField([
				'type'=>'submit',
				'name'=>'Update',
				'value'=>'Update',
			]);

			print('</center>');

			if($this->alwaysdelete || ($this->that->parent['id'] && $this->that->canUserDelete())) {
				$this->DisplayDelete();
			}

			$this->DisplayAction(['action'=>'Update']);

			print('</center>');

			return TRUE;
		}

		public function DisplaySave() {
			print('<center>');

			$this->form->DisplayFormField([
				'type'=>'submit',
				'name'=>'Save',
				'value'=>'Save',
			]);

			$this->DisplayAction(['action'=>'Save']);

			print('</center>');

			return TRUE;
		}

		public function DisplayDelete() {
			$this->table->DisplayComponent_SeparateCells([
				'cellwidth'=>'50%',
				'cellvalign'=>'top',
			]);

			print('<center>');

			$this->form->DisplayFormField([
				'type'=>'submit',
				'name'=>'Delete',
				'value'=>'Delete',
				'class'=>'confirm',
			]);

			print('</center>');

			return TRUE;
		}

		public function DisplayAction($args) {
			return $this->form->DisplayFormField([
				'type'=>'hidden',
				'name'=>'action',
				'value'=>$args['action'],
			]);
		}
	}

?>
