<?php

	require_once(GGCMS_DIR . 'modules/html/entry-controls.php');
	require_once(GGCMS_DIR . 'modules/html/breadcrumbs.php');
	require_once(GGCMS_DIR . 'modules/html/auth.php');

		/*
			The top of a view page under its heading: the administrator's
			controls, then the top bar with the breadcrumbs and sign-in.

			view/index.php and news/display.php each built this by hand, and
			view/display_index.php the controls alone -- a front page has no
			trail to show -- so it calls DisplayAdminControls() on its own.

			Switches:
			  subpage  the breadcrumb trail's last crumb, as News; none by
			           default
			  width    the top bar's width class: 95percent by default,
			           100percent on the news page
		*/

	class module_viewtopbar extends module_spacing {
		public $that;
		public $subpage;
		public $width;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->subpage = $args['subpage'] ?? NULL;
			$this->width = $args['width'] ?? '95percent';
		}

		public function Display($args) {
			$this->DisplayAdminControls($args);
			$this->DisplayTopBar();

			return TRUE;
		}

		public function DisplayAdminControls($args) {
			if(!$this->that->authentication_object->user_session['UserAdmin.id']) {
				return FALSE;
			}

			$entry_controls = new module_entrycontrols;
			$entry_controls->Display(['that'=>$this->that, 'file'=>$args['file']]);

			return TRUE;
		}

		public function DisplayTopBar() {
			print('<div class="horizontal-center width-' . $this->width . ' margin-top-5px">');

			$breadcrumbs = new module_breadcrumbs([
				'that'=>$this->that,
				'subpage'=>$this->subpage,
			]);
			$breadcrumbs->Display();

			$auth = new module_auth(['that'=>$this->that]);
			$auth->Display();

				// The news page kept these switched off here; the reading
				// page has its own.
				//	$entry_likes->Display();
				//	$entry_comments->DisplayLinkBox();
				//	$entry_image->DisplayLinkBox();
				//	$entry_quotes->DisplayLinkBox();
				//	$entry_share->DisplayPermalink();
				//	$entry_share->DisplaySmall();

			print('</div>');
			print('<div class="clear-float"></div>');

			return TRUE;
		}
	}

?>
