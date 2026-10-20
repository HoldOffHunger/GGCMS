<?php

	/*
		Save and Update's plain-text summaries of what was written.
	*/

	require_once(GGCMS_DIR . 'modules/spacing.php');
	require_once(GGCMS_DIR . 'modules/html/divider.php');
	require_once(GGCMS_DIR . 'modules/html/table.php');
	require_once(GGCMS_DIR . 'modules/html/list/generic.php');
	require_once(GGCMS_DIR . 'modules/html/modify-results.php');

	class ModifyResultsTest extends GGCMSTestCase {
		public function testLines() {
			$results = new module_modifyresults(['that'=>(object)[]]);

			$this->assertSame('one<br>two', $results->Lines(['lines'=>['one', 'two']]));
			$this->assertSame('N/A', $results->Lines(['lines'=>[]]));
		}

		public function testEventDateBrackets() {
			$save = new module_modifyresults(['that'=>(object)[]]);
			$update = new module_modifyresults(['that'=>(object)[], 'eventdescriptions'=>TRUE]);

			$both = ['Title'=>'Born', 'Description'=>'in Paris', 'Language'=>'en'];
			$titled = ['Title'=>'Born', 'Description'=>'', 'Language'=>'en'];
			$described = ['Title'=>'', 'Description'=>'in Paris', 'Language'=>'en'];
			$neither = ['Title'=>'', 'Description'=>'', 'Language'=>'en'];

			$this->assertSame(' (Born ~ en)', $save->EventDateBrackets(['eventdate'=>$both]));
			$this->assertSame(' ( ~ en)', $save->EventDateBrackets(['eventdate'=>$neither]), 'Save brackets the title and language whatever they hold');

			$this->assertSame(' (Born ~ in Paris)', $update->EventDateBrackets(['eventdate'=>$both]));
			$this->assertSame(' (Born)', $update->EventDateBrackets(['eventdate'=>$titled]));
			$this->assertSame(' (in Paris)', $update->EventDateBrackets(['eventdate'=>$described]));
			$this->assertSame('', $update->EventDateBrackets(['eventdate'=>$neither]));
		}

		public function testAssociations() {
			$results = new module_modifyresults(['that'=>(object)['association'=>[
				['ChosenEntryid'=>'5', 'Type'=>'Author', 'SubType'=>'Editor'],
				['ChosenEntryid'=>'6', 'Type'=>'', 'SubType'=>'Translator'],
				['ChosenEntryid'=>'7', 'Type'=>'', 'SubType'=>''],
				['ChosenEntryid'=>'', 'Type'=>'Author', 'SubType'=>''],
			]]]);

			$this->assertSame('&bull; 5 (Author ~ Editor)<br>&bull; 6 (Translator)', $results->Associations(), 'one with neither a type nor a subtype is left out');
		}

		public function testDefinitions() {
			$that = (object)[
				'tag'=>[['Tag'=>'history', 'Language'=>'en'], ['Tag'=>'', 'Language'=>'']],
				'definition'=>[['Term'=>'Mutualism', 'Definition'=>'An economic theory.']],
			];

			$results = new module_modifyresults(['that'=>$that]);

			$this->assertSame('N/A', $results->Definitions(), 'before the tags are summarised there is no tag text');

			$results->Tags();

			$this->assertSame('N/A', $results->Definitions(), 'the last tag had no text');

			$that->tag = [['Tag'=>'history', 'Language'=>'en']];
			$results->Tags();

			$this->assertSame('&bull; history (en)', $results->tagtext);
			$this->assertSame('&bull; Mutualism : An economic theory.', $results->Definitions());
		}
	}

?>
