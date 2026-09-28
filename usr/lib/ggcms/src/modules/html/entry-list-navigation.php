<?php

		/*
			The pages of a list of results: Previous and Next, the first and
			last pages, and the two either side of this one, with a gap mark
			for the pages between.  A long list -- a collection of thousands
			-- no longer prints a link to every one of its pages.

			The links carry what the old ones did (headless, the action, the
			page and how many a page shows), the tag on a tag's page, and the
			list switches a template passes:

				$entrylistnavigation->Display(['parents'=>2, 'list_author'=>1]);
		*/

	class module_entrylistnavigation extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function Display($args) {
			$total = (int) $this->that->total_pages;
			$current = (int) $this->that->page;

			if($total < 2) {
				return FALSE;
			}

			print('<nav class="block page-wide pager" aria-label="Pages of results">');

			if($current > 1) {
				print('<a class="pager-step" rel="prev" href="' . $this->PageURL(['page'=>$current - 1, 'list'=>$args]) . '"><span aria-hidden="true">&larr;</span> Previous</a>');
			}

			print('<ol class="pager-pages">');

			$last = 0;

			foreach($this->PageNumbers() as $number) {
				if($number - $last > 1) {
					print('<li class="pager-gap" aria-hidden="true">&hellip;</li>');
				}

				if($number === $current) {
					print('<li><span class="pager-current" aria-current="page">' . $number . '</span></li>');
				} else {
					print('<li><a href="' . $this->PageURL(['page'=>$number, 'list'=>$args]) . '">' . $number . '</a></li>');
				}

				$last = $number;
			}

			print('</ol>');

			if($current < $total) {
				print('<a class="pager-step" rel="next" href="' . $this->PageURL(['page'=>$current + 1, 'list'=>$args]) . '">Next <span aria-hidden="true">&rarr;</span></a>');
			}

			print('</nav>');

			return TRUE;
		}

			/*
				The first and last pages and two either side of this one.  A
				gap of a single page is filled in rather than marked, since
				the mark would take the same room as the number.
			*/

		public function PageNumbers() {
			$total = (int) $this->that->total_pages;
			$current = (int) $this->that->page;

			$numbers = [1, $total];

			for($i = $current - 2; $i <= $current + 2; $i++) {
				if($i >= 1 && $i <= $total) {
					$numbers[] = $i;
				}
			}

			$numbers = array_values(array_unique($numbers));
			sort($numbers);

			$filled = [];

			foreach($numbers as $number) {
				if($filled && $number - end($filled) === 2) {
					$filled[] = $number - 1;
				}

				$filled[] = $number;
			}

			return $filled;
		}

		public function PageURL($args) {
			$query = [];

			if($this->that->Param('headless')) {
				$query['headless'] = 1;
			}

			$query['action'] = $this->that->desired_action;

			if($this->that->desired_action === 'browseByTag') {
				$query['tag'] = $this->that->tag;
			}

			$query['page'] = $args['page'];
			$query['perpage'] = $this->that->perpage;

			foreach(['ignore_parent', 'parents', 'item_title', 'list_author', 'stats_prefix'] as $key) {
				if(!empty($args['list'][$key])) {
					$query[$key] = $args['list'][$key];
				}
			}

			return 'view.php?' . http_build_query($query, '', '&amp;');
		}
	}

?>
