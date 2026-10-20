<?php

		/*
			Which entry Edit is editing, at the top of the page: a link to
			it and its id, links to the last entry edited and the last
			unpublished add and edit where there are any, and a word of
			help for anyone who is not an admin.
		*/

	class module_modifyentrysummary extends module_spacing {
		public $that;
		public $divider;
		public $generic_list;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->divider = new module_divider;
			$this->generic_list = new module_genericlist;
		}

		public function Display() {
			$this->divider->displaystart(['class'=>'margin-5px padding-5px']);

			$this->generic_list->Display([
				'options'=>[
					'tableheaders'=>0,
					'tableclass'=>'width-70percent horizontal-center border-2px background-color-gray13 margin-top-5px',
					'rowclass'=>'border-1px horizontal-left',
					'cellclass'=>[
						'border-1px vertical-top',
						'border-1px width-100percent vertical-top',
					],
				],
				'list'=>$this->Rows(),
			]);

			$this->divider->displayend([]);

			return TRUE;
		}

			// Data
			// -------------------------------------------------

		public function Rows() {
			$rows = [
				['<nobr>Updating Entry:</nobr>', $this->that->GetHyperlinkedEntryView(['entry'=>$this->that->entry, 'entrylist'=>$this->that->record_list])],
				['<nobr>Updating Entry ID:</nobr>', $this->that->entry['id']],
			];

			$recent = [
				'Last Edited Entry'=>$this->that->last_edited_entry,
				'Last Unpublished Add'=>$this->that->last_unpublished_add,
				'Last Unpublished Edit'=>$this->that->last_unpublished_edit,
			];

			foreach($recent as $label => $entry) {
				if($entry && $entry['id']) {
					$rows[] = ['<nobr>' . $label . ':</nobr>', $this->EntryLink(['entry'=>$entry])];
				}
			}

			if(!$this->that->isUserAdmin()) {
				$rows[] = ['<nobr>Want extra help?</nobr>', 'Not sure what something does?  Just float your cursor over it!  That should cause a popup to appear that will help you.  Still lost?  Then just <a href="/contact.php">Contact Us</a> and we\'ll help you get connected with our community!'];
			}

			return $rows;
		}

		public function EntryLink($args) {
			$entry = $args['entry'];
			$link = '<a href="' . $entry['Code'] . '/view.php">' . $entry['Title'];

			if($entry['Subtitle']) {
				$link .= ' : ' . $entry['Subtitle'];
			}

			return $link . '</a>';
		}
	}

?>
