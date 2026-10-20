<?php

	require_once(GGCMS_DIR . 'modules/html/entry-sort.php');
	require_once(GGCMS_DIR . 'modules/html/entry-children.php');

		/*
			The news page's list: a New Items heading with links to longer
			lists beside it -- 20 items up to 1,000 -- then the newest
			entries themselves, newest first, as module_entrychildren lists
			any entries.  The script fills newest_entries.
		*/

	class module_newsitems extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function Display() {
			$this->DisplayHeader();
			$this->DisplayItems();

			return TRUE;
		}

		public function DisplayHeader() {
			print('<div class="horizontal-center width-95percent">');

			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<h2 class="horizontal-left margin-5px font-family-arial">');
			print('New Items');
			print('</h2>');
			print('</div>');

			print('<div class="border-2px background-color-gray15 margin-5px float-right">');
			print('<p style="margin:5px;padding:0px;font-family:\'arial\', \'tahoma\';">');
			$this->DisplayItemCounts();
			print('</p>');

				// Kept switched off, as the template had it:
				//	print('<h2 class="horizontal-left margin-5px font-family-arial">');
				//	print('New Items');
				//	print('</h2>');

			print('</div>');

			print('</div>');

			return TRUE;
		}

		public function DisplayItemCounts() {
			$links = [];

			foreach([20, 50, 100, 500, 1000] as $items) {
				$links[] = '<a href="' . $this->that->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/news.php?items=' . $items . '">' . number_format($items) . ' Items</a>';
			}

			print(implode(' | ', $links));

			return TRUE;
		}

		public function DisplayItems() {
			$entry_children = new module_entrychildren([
				'that'=>$this->that,
				'entrysort'=>new module_entrysort(['that'=>$this->that]),
				'header'=>'',
			]);

			$entry_children->Display_Entries([
				'entries'=>$this->that->newest_entries,
				'count'=>count($this->that->newest_entries),
				'datefield'=>'OriginalCreationDate',
			]);

			return TRUE;
		}
	}

?>
