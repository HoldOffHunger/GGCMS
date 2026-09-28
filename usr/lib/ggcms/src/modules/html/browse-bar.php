<?php

		/*
			The bar over a page of results: which of them this page shows,
			and how many to show at a time.  The form posts back to the page
			it is on, with the fields view.php already reads -- perpage,
			CustomPerPage and page -- so nothing behind it changes.

				ggreq('modules/html/browse-bar.php');
				$browse_bar = new module_browsebar(['that'=>$this]);
				$browse_bar->Display();
		*/

	class module_browsebar extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function Display() {
			print('<div class="block page-wide browse-bar">');
			$this->DisplayCount();
			$this->DisplayPerPage();
			print('</div>');

			return TRUE;
		}

		public function DisplayCount() {
			$total = (int) $this->that->entry_count;
			$start = (int) $this->that->child_record_start_index;
			$end = (int) $this->that->child_record_end_index;

			print('<p class="browse-count">');

			if($total < 1) {
				print('Nothing here yet.');
			} elseif($start <= 1 && $end >= $total) {
				print('Showing all <strong>' . number_format($total) . '</strong>');
			} else {
				print('Showing <strong>' . number_format($start) . '&ndash;' . number_format($end) . '</strong> of <strong>' . number_format($total) . '</strong>');
			}

			print('</p>');

			return TRUE;
		}

			/*
				Only where it can change something: the smallest choice is
				ten, so a list of ten or fewer on a single page has no use
				for it.  The custom box shows only while Custom is chosen.
			*/

		public function DisplayPerPage() {
			if((int) $this->that->entry_count <= 10 && (int) $this->that->total_pages < 2) {
				return FALSE;
			}

			$perpage = (int) $this->that->perpage;
			$custom = !empty($this->that->custom_per_page_selected);

			print('<form class="browse-perpage" method="post">');
			print('<label for="perpage">Per page</label>');
			print('<select id="perpage" name="perpage">');

			for($i = 10; $i <= 200; $i += 10) {
				print('<option value="' . $i . '"' . ($i === $perpage && !$custom ? ' selected' : '') . '>' . $i . '</option>');
			}

			print('<option value="custom"' . ($custom ? ' selected' : '') . '>Custom</option>');
			print('</select>');
			print('<input id="CustomPerPage" name="CustomPerPage" type="number" min="1" value="' . $perpage . '" aria-label="How many per page">');
			print('<input type="hidden" name="page" value="1">');
			print('<button class="btn btn-line" type="submit">Update</button>');
			print('</form>');

			return TRUE;
		}
	}

?>
