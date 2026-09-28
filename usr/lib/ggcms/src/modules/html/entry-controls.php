<?php

	class module_entrycontrols extends module_spacing {
		public function Display($args) {
	#		print("<PRE>");
	#		print_r($args['that']->entry);
	#		print("</PRE>");
		
			$this->Display_Start($args);
			$this->Display_Header($args);
			
			$this->Display_Separator($args);
			
			$this->Display_Links($args);
			$this->Display_End($args);
			
			return TRUE;
		}
		
		public function DisplayHeader_Title($args) {
			$that = $args['that'];

			print('<span class="admin-bar-id">Entry ' . $that->entry['id'] . '</span>');

			if($that->entry['Publish']) {
				print('<span class="admin-bar-status is-public">Public</span>');
			} else {
				print('<span class="admin-bar-status is-private">Private</span>');
			}

			if($that->entry['entrypermission'][0]['user']['Username']){
				$user = $that->entry['entrypermission'][0]['user'];

				print('<span class="admin-bar-owner">From ');
				print('<a target="_parent" href="/users.php?action=viewuser&amp;user=' . urlencode($user['Username']) . '">' . $user['Username'] . '</a>');
				print(' [id: ' . $user['id'] . ']');
				print(' (' . $user['EmailAddress'] . ')');
				print('</span>');
			}

			return TRUE;
		}

		public function DisplayHeader_BreadCrumbs($args) {

				/*
					Admins only.

					This prints the template file that rendered the page, which is
					a useful thing to have while building a site and an internal
					filesystem path to show a stranger.  It was showing to
					everyone: every entry page on revoltlib carried
					../ggcms/src/templates/revoltlib/view/display_childof_anarchism.php
					in a code block, to anybody who loaded it.

					Not every caller passes `that` -- entry-children-grandchildren
					calls Display_Header with only an entry -- so no script object
					counts as not an admin, which is the safe direction for a
					diagnostic to fail in.
				*/

			$that = isset($args['that']) ? $args['that'] : (isset($this->that) ? $this->that : NULL);

			if(!$that || !method_exists($that, 'isUserAdmin') || !$that->isUserAdmin()) {
				return FALSE;
			}

			$file = $args['file'];
			
			$file_display = $this->getFileDisplay(['file'=>$file]);
			
			print('<code class="admin-bar-file">' . $file_display . '</code>');
			
			return TRUE;
		}
		
		public function DisplayHeader_Start() {
			print('<div class="admin-bar-head">');

			return TRUE;
		}

		public function DisplayHeader_End() {
			print('</div>');

			return TRUE;
		}

		public function Display_Header($args) {
			$this->DisplayHeader_Start();
			
			$this->DisplayHeader_Title($args);
			$this->DisplayHeader_BreadCrumbs($args);
			
			$this->DisplayHeader_End();
			
			return TRUE;
		}
		
		public function Display_Links_Edit($args) {
			print('<nav class="admin-bar-links" aria-label="Edit">');
			print('<a target="_parent" href="modify.php?action=Edit">Edit</a>');
			print('<a target="_parent" href="modify.php?action=Add">Add</a>');
			print('<a target="_parent" href="transfer.php">Transfer</a>');
			print('<a target="_parent" href="chapterify.php">Chapterify</a>');
			print('</nav>');

			return TRUE;
		}

		public function Display_Links_View($args) {
			print('<nav class="admin-bar-links" aria-label="View">');
			print('<a target="_parent" href="/">Home</a>');
			print('<a target="_parent" href="view.php">View</a>');
			print('<a target="_parent" href="view.php?action=index">Index</a>');
			print('</nav>');

			return TRUE;
		}

		public function Display_Links($args) {
			$this->Display_Links_Edit($args);
			$this->Display_Links_View($args);

			return TRUE;
		}

			/*
				Administrators only -- every caller checks.  A slim bar above
				the page, apart from what readers see.
			*/

		public function Display_Start($args) {
			print('<div class="admin-bar">');

			return TRUE;
		}

		public function Display_End($args) {
			print('</div>');

			return TRUE;
		}

		public function Display_Separator($args) {
			return TRUE;
		}

		public function getFileDisplay($args) {
			$file = $args['file'];
			
			$file_pieces = explode('/', $file);
			unset($file_pieces[0]);
			unset($file_pieces[1]);
			$file_pieces[2] = '..';
			$file_display = implode('/', $file_pieces);
			
			return $file_display;
		}
		
		public function showAddedBy($args) {
			return TRUE;
			$that = $args['that'];
			
		#	print("<!-- BT: \n\n");
			
		#	print_r($that->entry['entrypermission']);
			
			print('<div style="margin-right:5px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray15 float-right">');
			print('<span class="comments-link-box" style="font-size:0.8em;font-family:arial, tahoma;margin:3px;padding:0px;display:inline-block;">');
			print('<strong>');
			print('Added By: ');
			print('<a target="_parent" href="');
			print('/users.php?action=viewuser&user=' . urlencode($that->entry['entrypermission'][0]['user']['Username']));
			print('">');
			print($that->entry['entrypermission'][0]['user']['Username']);
			print('</a>');
			print('</strong>');
			print('</span>');
			print('</div>');
		#	print("\n\n" . "-->\n\n");
			
			return TRUE;
		}
		
		public function showNotPublishedNote($args) {
			$that = $args['that'];

			if($that->entry['Publish'] === 1 || $that->isUserAdmin()) {
				return FALSE;
			}

			print('<div class="notice notice-warning">');
			print('<p><strong>Not yet published.</strong> Only you, its author, can see this page.</p>');
			print('<p>To add chapters or other records, use <a target="_parent" href="modify.php?action=Add">Add</a>; to change it, <a target="_parent" href="modify.php?action=Edit">Edit</a>. All your pending submissions are on your <a target="_parent" href="/user-panel.php">user panel</a>.</p>');
			print('</div>');

			return TRUE;
		}
	}

?>