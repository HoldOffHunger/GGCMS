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
			
		#	print("BT:");
		#	print_r($that->record_list);
		#	print_r(array_keys($that));
			
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<h2 class="horizontal-left margin-5px font-family-arial">');
			print('Entry ' . $that->entry['id']);
			print('</h2>');
			print('</div>');
			
			print('<div class="border-2px background-color-gray15 margin-5px float-left"');
			if($that->entry['Publish']) {
				print(' style="background-color:' . $that->handler->globals->SuccessColour() . '"');
			} else {
				print(' style="background-color:' . $that->handler->globals->FailColour() . '"');
			}
			print('>');
			print('<h2 class="horizontal-left margin-5px font-family-arial">');
			if($that->entry['Publish']) {
				print('Public');
			} else {
				print('Private');
			}
			print('</h2>');
			print('</div>');
			
			if($that->entry['entrypermission'][0]['user']['Username']){
				print('<div class="border-2px background-color-gray15 margin-5px float-left">');
				print('<span style="font-size:0.90em; margin:2px;" class="horizontal-left font-family-arial">');
				print('From: ');
				
				print('<a target="_parent" href="/users.php?action=viewuser&user=');
				print(urlencode($that->entry['entrypermission'][0]['user']['Username']));
				print('">');
				print($that->entry['entrypermission'][0]['user']['Username']);
				print('</a>');
				
				print(' [id: ');
				print($that->entry['entrypermission'][0]['user']['id']);
				print(']');
				print('<br>');
				print(' (');
				print($that->entry['entrypermission'][0]['user']['EmailAddress']);
				print(')');
				
				print('</span>');
				print('</div>');
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
			
			print('<div class="border-2px background-color-gray15 margin-5px float-right">');
			print('<p class="horizontal-left margin-5px font-family-arial">');
			print('<code>');
			print($file_display);
			print('</code>');
			print('</p>');
			print('</div>');
			
			return TRUE;
		}
		
		public function DisplayHeader_Start() {
			print('<center>');
			print('<div class="horizontal-center width-90percent">');
			
			return TRUE;
		}
		
		public function DisplayHeader_End() {
			print('</div>');
			print('</center>');
			
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
			print('<div class="margin-top-5px" style="float:left;margin-left:50px;">');
			
			print('<div class="float-left margin-5px border-2px background-color-gray13">');
			print('<p class="font-family-arial margin-5px">');
			print('<a target="_parent" href="modify.php?action=Edit">EDIT</a>');
			print('</p>');
			print('</div>');
			
			print('<div class="float-left margin-5px border-2px background-color-gray13">');
			print('<p class="font-family-arial margin-5px">');
			print('<a target="_parent" href="modify.php?action=Add">ADD</a>');
			print('</p>');
			print('</div>');
			
			print('<div class="float-left margin-5px border-2px background-color-gray13">');
			print('<p class="font-family-arial margin-5px">');
			print('<a target="_parent" href="transfer.php">TRANSFER</a>');
			print('</p>');
			print('</div>');
			
			print('<div class="float-left margin-5px border-2px background-color-gray13">');
			print('<p class="font-family-arial margin-5px">');
			print('<a target="_parent" href="chapterify.php">CHAPTERIFY</a>');
			print('</p>');
			print('</div>');
			
			print('</div>');
			
			return TRUE;
		}
		
		public function Display_Links_View($args) {
			print('<div class="margin-top-5px" style="float:right;margin-right:50px;">');
			
			print('<div class="float-left margin-5px border-2px background-color-gray13">');
			print('<p class="font-family-arial margin-5px">');
			print('<a target="_parent" href="/">HOME</a>');
			print('</p>');
			print('</div>');
			
			print('<div class="float-left margin-5px border-2px background-color-gray13">');
			print('<p class="font-family-arial margin-5px">');
			print('<a target="_parent" href="view.php">VIEW</a>');
			print('</p>');
			print('</div>');
			
			print('<div class="float-left margin-5px border-2px background-color-gray13">');
			print('<p class="font-family-arial margin-5px">');
			print('<a target="_parent" href="view.php?action=index">INDEX</a>');
			print('</p>');
			print('</div>');
			
			print('</div>');
			
			return TRUE;
		}
		
		public function Display_Links($args) {
			$this->Display_Links_Edit($args);
			$this->Display_Links_View($args);
			
			
			return TRUE;
		}
		
		public function Display_Start($args) {
			print('<div class="horizontal-center width-95percent margin-top-5px border-2px">');
			
			return TRUE;
		}
		
		public function Display_End($args) {
			print('<div class="clear-float">');
			print('</div>');
			
			print('</div>');
			
			return TRUE;
		}
		
		public function Display_Separator($args) {
			print('<div class="clear-float">');
			print('</div>');
			
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
			
			print('<div style="width:50%;" class="horizontal-center">');
			print('<div style="text-align:left;" class="font-family-arial width-100percent background-color-gray14 border-2px margin-top-5px">');
			
			print('<div style="float:left;border:2px solid black;background-color:' . $that->handler->globals->FailColour() . '; margin:3px;">');
			print('<h3 style="font-size:2em;margin:0px;margin:3px;">');
			print('<i>');
			print('Unpublished!');
			print('</i>');
			print('</h3>');
			print('</div>');
			
			print('<p style="margin:2px;">This work is not yet published yet!  Only you, the creator, are able to view it.  If you would like to add chapters or other records, please use the <a target="_parent" href="modify.php?action=Add">Add Link</a>; if you would like to edit your edit, use the <a target="_parent" href="modify.php?action=Edit">Edit Link</a>.  To view all of your current, pending, unpublished submissions, please use the <a target="_parent" href="/user-panel.php">User Panel</a>.</p>');
			
			print('</div>');
			print('</div>');
			
			return TRUE;
		}
	}

?>