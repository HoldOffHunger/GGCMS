<?php

	/*
		The modify form's field loop, through a page object that offers
		only what Save's fields read.
	*/

	require_once(GGCMS_DIR . 'modules/spacing.php');
	require_once(GGCMS_DIR . 'modules/html/form.php');
	require_once(GGCMS_DIR . 'modules/html/divider.php');
	require_once(GGCMS_DIR . 'modules/html/table.php');
	require_once(GGCMS_DIR . 'modules/html/modify-fields.php');

	class ModifyFieldsTestPage {
		public $elements;
		public $tag;
		public $association;
		public $SelectableLanguages = [];

		public function modifiableRecordTypes() {
			return $this->elements;
		}
	}

	class ModifyFieldsTest extends GGCMSTestCase {
		public function render($args) {
			$page = new ModifyFieldsTestPage;
			$page->elements = $args['elements'];
			$page->tag = [['Tag'=>'history', 'Language'=>'en']];
			$page->association = [['ChosenEntryid'=>'5', 'Type'=>'', 'SubType'=>''], ['ChosenEntryid'=>'6', 'Type'=>'', 'SubType'=>'']];

			$fields = new module_modifyfields([
				'that'=>$page,
				'plainlabels'=>TRUE,
				'noassociationtypes'=>TRUE,
				'fields'=>[
					'Tag'=>[],
					'Association'=>['followtags'=>TRUE, 'rawidrows'=>['value', 'blank', 'hidden']],
				],
			]);

			ob_start();
			$fields->Display();

			return ob_get_clean();
		}

			/*
				Save and Update's association rows take their buttons from
				whether the tag rows printed any, so with a tag shown even
				the first association has Remove rather than Add.
			*/

		public function testDisplayField() {
			$html = $this->render(['elements'=>['Tag', 'Association', 'Unlisted']]);

			$this->assertStringContainsString('id="add-tag-button"', $html);
			$this->assertStringNotContainsString('id="add-association-button"', $html, 'the tag rows printed, so no association row has Add');
			$this->assertMatchesRegularExpression('#Unlisted</td>\s*<td valign="top" width="80%">\s*</td>#', $html, 'an element the form does not list still gets its table, empty');

			$html = $this->render(['elements'=>['Association']]);

			$this->assertStringContainsString('id="add-association-button"', $html, 'with no Tag element nothing was printed above it');
		}
	}

?>
