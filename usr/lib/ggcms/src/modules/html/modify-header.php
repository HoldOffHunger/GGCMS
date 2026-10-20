<?php

	require_once(GGCMS_DIR . 'modules/html/entry-header.php');
	require_once(GGCMS_DIR . 'modules/html/entry-index-header.php');
	require_once(GGCMS_DIR . 'modules/html/breadcrumbs.php');
	require_once(GGCMS_DIR . 'modules/html/auth.php');

		/*
			The head of a modify.php page -- Edit, Save, Update: the page
			heading, then the top bar with the breadcrumbs and sign-in.

			The top bar's div is opened here and left open, as the three
			templates always left it.

			Switches:
			  heading  what the page is doing, before the entry's title:
			           Editing, Saved!, Update result, ...
			  subpage  the breadcrumb trail's last crumb: Edit Entry,
			           Saved Entry, Update Result, ...
		*/

	class module_modifyheader extends module_spacing {
		public $that;
		public $heading;
		public $subpage;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->heading = $args['heading'];
			$this->subpage = $args['subpage'];
		}

		public function Display() {
			$this->DisplayHeading();
			$this->DisplayTopBar();

			return TRUE;
		}

		public function DisplayHeading() {
			$entryindexheader = new module_entryindexheader([
				'that'=>$this->that,
				'main_text'=>$this->heading . ' &mdash; ' . $this->that->entry['Title'],
				'sub_text'=>'',
			]);

			$entryindexheader->Display();

			return TRUE;
		}

		public function DisplayTopBar() {
			print('<div class="horizontal-center margin-top-5px" style="width:100%;">');

			$breadcrumbs = new module_breadcrumbs([
				'that'=>$this->that,
				'subpage'=>$this->subpage,
			]);
			$breadcrumbs->Display();

			$auth = new module_auth(['that'=>$this->that, 'noredirect'=>TRUE]);
			$auth->Display();

			print('<div class="clear-float"></div>');

			return TRUE;
		}
	}

?>
