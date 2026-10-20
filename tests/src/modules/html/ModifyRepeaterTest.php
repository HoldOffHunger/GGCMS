<?php

	/*
		The rows every repeatable field on the modify form prints, through
		a subject field whose inputs say only which row they are on.
	*/

	require_once(GGCMS_DIR . 'modules/spacing.php');
	require_once(GGCMS_DIR . 'modules/html/form.php');
	require_once(GGCMS_DIR . 'modules/html/divider.php');
	require_once(GGCMS_DIR . 'modules/html/modify-repeater.php');

	class ModifyRepeaterTestSubject extends module_modifyrepeater {
		public $code = 'thing';
		public $label = 'Thing';

		public function Values() {
			return $this->that->things;
		}

		public function Accepts($args) {
			return $args['value'] !== '';
		}

		public function DisplayInputs($args) {
			print('[' . $args['row'] . ':' . $args['value'] . ']');
		}
	}

	class ModifyRepeaterTest extends GGCMSTestCase {
		public function render($args) {
			$field = new ModifyRepeaterTestSubject(['that'=>(object)['things'=>$args['things']]]);

			ob_start();
			$field->Display();

			return ['field'=>$field, 'html'=>ob_get_clean()];
		}

		public function testDisplay() {
			$rendered = $this->render(['things'=>['a', '', 'b']]);
			$html = $rendered['html'];

			$this->assertSame(1, $rendered['field']->displayed);
			$this->assertSame('b', $rendered['field']->last);

			$this->assertMatchesRegularExpression('#^\[value:a\]<button\s+id="add-thing-button" class="float-right">Add Thing</button>#', $html, 'the first row has the Add button and no divider');
			$this->assertMatchesRegularExpression('#<div class="input-divider">\s*\[value:b\]<button\s+id="delete-thing-button" class="float-right">Remove</button>\s*</div>#', $html, 'a later row is boxed, with Remove');
			$this->assertStringNotContainsString('[value:]', $html, 'a value it does not accept gets no row');
			$this->assertStringNotContainsString('[blank:]', $html);
			$this->assertMatchesRegularExpression('#<div id="hidden-thing-input" class="display-none">\s*\[hidden:\]<button\s+id="delete-thing-button"#', $html);
			$this->assertMatchesRegularExpression('#<div id="thing-list">\s*</div>\s*$#', $html, 'the empty list comes last');
		}

		public function testDisplayBlank() {
			$rendered = $this->render(['things'=>['', '']]);

			$this->assertSame(0, $rendered['field']->displayed);
			$this->assertSame('', $rendered['field']->last, 'the last value looked at, accepted or not');
			$this->assertMatchesRegularExpression('#^\[blank:\]<button\s+id="add-thing-button" class="float-right">Add Thing</button>#', $rendered['html']);
		}

		public function testName() {
			$field = new ModifyRepeaterTestSubject(['that'=>(object)[]]);

			$this->assertSame('thing_Source[]', $field->Name(['name'=>'thing_Source', 'row'=>'value']));
			$this->assertSame('thing_Source[]', $field->Name(['name'=>'thing_Source', 'row'=>'blank']));
			$this->assertSame('thing_Source-Hidden', $field->Name(['name'=>'thing_Source', 'row'=>'hidden']));
		}
	}

?>
