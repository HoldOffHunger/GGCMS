<?php

	require_once(GGCMS_DIR . 'modules/html/divider.php');
	require_once(GGCMS_DIR . 'modules/html/list/generic.php');

		/*
			The boxes above a modify.php form -- Add, Edit, Save, Update:
			what is being modified, the primary-record note, and the errors.

			A template calls the ones it shows, in its own order:
			DisplayEntryInfo() is Add's and Edit's table of the entry and
			the last ones added or edited; DisplayStatus() is Save's and
			Update's line of what was saved and how it went.

			Switches:
			  padded  give each box the margin-5px padding-5px divider, as
			          Add and Edit do; Save and Update never set one
			  adding  a new entry is being added: for DisplayEntryInfo(),
			          the entry it goes under -- on Add still $that->entry --
			          the last one added, and the Replicate button, as Add
			          shows; for DisplayStatus(), the parent being added to,
			          as Save shows, rather than the entry being updated
		*/

	class module_modifynotices extends module_spacing {
		public $that;
		public $padded;
		public $adding;
		public $divider;
		public $generic_list;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->padded = $args['padded'];
			$this->adding = $args['adding'];

			$this->divider = new module_divider;
			$this->generic_list = new module_genericlist;
		}

			// Entry
			// -------------------------------------------------

		public function DisplayEntryInfo() {
			$info_list = array_merge($this->EntryRows(), $this->RecentEntryRows());

			if(!$this->that->isUserAdmin()) {
				$info_list[] = [
					'<nobr>Want extra help?</nobr>', 'Not sure what something does?  Just float your cursor over it!  That should cause a popup to appear that will help you.  Still lost?  Then just <a href="/contact.php">Contact Us</a> and we\'ll help you get connected with our community!',
				];
			}

			if($this->adding) {
				$info_list[] = $this->ReplicateRow();
			}

			$this->DisplayBox(['list'=>$info_list]);

			if($this->adding) {
				$this->DisplayReplicateScript();
			}

			return TRUE;
		}

			// The entry being updated and its id; or, adding, the entry the
			// new one goes under, which has its own id.

		public function EntryRows() {
			$entry = $this->that->entry;
			$entry_link = $this->that->GetHyperlinkedEntryView(['entry'=>$entry, 'entrylist'=>$this->that->record_list]);

			if($this->adding) {
				return [
					['<nobr>Adding Entry To:</nobr>', $entry_link],
				];
			}

			return [
				['<nobr>Updating Entry:</nobr>', $entry_link],
				['<nobr>Updating Entry ID:</nobr>', $entry['id']],
			];
		}

			// The last entries added or edited, and added or edited
			// unpublished, where there are any.

		public function RecentEntryRows() {
			if($this->adding) {
				$recent = ['<nobr>Last Added Entry:</nobr>'=>$this->that->last_added_entry];
			} else {
				$recent = ['<nobr>Last Edited Entry:</nobr>'=>$this->that->last_edited_entry];
			}

			$recent['<nobr>Last Unpublished Add:</nobr>'] = $this->that->last_unpublished_add;
			$recent['<nobr>Last Unpublished Edit:</nobr>'] = $this->that->last_unpublished_edit;

			$rows = [];

			foreach($recent as $label => $recent_entry) {
				if($recent_entry && $recent_entry['id']) {
					$rows[] = [$label, $this->EntryLink(['entry'=>$recent_entry])];
				}
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

			// The Replicate button copies the last added entry's source and
			// association into the form -- replicate-last-added-record.js,
			// reading the script below.  Explained in a tooltip to anyone
			// but an admin.

		public function ReplicateRow() {
			if($this->that->isUserAdmin()) {
				return [
					'<span>Replicate?</span>',
					'<span><button id="ReplicateLastAddedOptions" value="Replicate Last Added Options">Replicate Last Added Options</button></span>',
				];
			}

			return [
				'<span title="This is for copying over some of the options from the last time you edited, namely, Source and Association.">Replicate?</span>',
				'<span title="This is for copying over some of the options from the last time you edited, namely, Source and Association."><button id="ReplicateLastAddedOptions" value="Replicate Last Added Options">Replicate Last Added Options</button></span>',
			];
		}

		public function DisplayReplicateScript() {
			print('<script type="text/javascript">');
			print('var ReplicateLastAddedOptions = ');
			print(json_encode($this->that->last_added_entry));
			print(';');
			print('</script>');

			return TRUE;
		}

		public function DisplayStatus() {
			print('<center><div style="width:70%;">');
			print('<table style="width:100%;" class="border-2px background-color-gray13 margin-top-5px">');
			print('<tbody><tr class="border-1px horizontal-left">');
			print('<td class="border-1px vertical-top">');
			print($this->adding ? '<nobr>Adding Entry To:</nobr>' : '<nobr>Updating Entry:</nobr>');
			print('</td>');
			print('<td class="border-1px width-100percent vertical-top">');

			print($this->that->GetHyperlinkedEntryView([
				'entry'=>$this->adding ? $this->that->parent : $this->that->entry,
				'entrylist'=>$this->that->record_list,
			]));

			print('</td>');
			print('</tr>');
			print('<tr class="border-1px horizontal-left">');
			print('<td class="border-1px vertical-top">');
			print('<nbr>Status:');
			print('</nbr></td>');
			print('<td class="border-1px width-100percent vertical-top">');
			print($this->that->save_status);
			print('</td>');
			print('</tr>');
			print('</tbody></table>');
			print('</div></center>');

			return TRUE;
		}

		public function DisplayPrimaryRecordNotice() {
			if(count($this->that->record_list)) {
				return FALSE;
			}

			$this->DisplayBox(['list'=>[
				[
					'<nobr>Editing Primary Entry Record:</nobr>',
					'The Code should be the case-sensitive version of the domain URL for this host.',
				],
			]]);

			return TRUE;
		}

			// Errors
			// -------------------------------------------------

			// The heading box is shown once, before whichever errors come
			// first; admin errors are only shown to an admin.

		public function DisplayErrors() {
			$admin_errors = $this->that->authentication_object->CheckAuthenticationForCurrentObject_IsAdmin() && $this->that->admin_errors;

			if($admin_errors || $this->that->errors) {
				$this->DisplayErrorHeading();
			}

			if($admin_errors) {
				foreach($this->that->admin_errors_display as $admin_error_to_display) {
					$this->DisplayBox(['list'=>$admin_error_to_display]);
				}
			}

			if($this->that->errors) {
				foreach($this->that->errors_display as $error_to_display) {
					$this->DisplayBox(['list'=>$error_to_display]);
				}
			}

			return TRUE;
		}

		public function DisplayErrorHeading() {
			$this->divider->displaystart($this->DividerArgs());

			$this->generic_list->Display([
				'options'=>[
					'tableheaders'=>0,
					'tableclass'=>'width-50percent horizontal-center border-2px background-color-gray13 margin-top-5px',
					'rowclass'=>'border-1px horizontal-left',
					'cellclass'=>[
						'border-1px vertical-top horizontal-center font-size-150percent',
						'border-1px width-100percent vertical-top horizontal-center',
					],
				],
				'list'=>[
					[
						'There was an error with your submission.',
					],
				],
			]);

			$this->divider->displayend([]);

			return TRUE;
		}

			// Boxes
			// -------------------------------------------------

		public function DisplayBox($args) {
			$this->divider->displaystart($this->DividerArgs());

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
				'list'=>$args['list'],
			]);

			$this->divider->displayend([]);

			return TRUE;
		}

		public function DividerArgs() {
			if($this->padded) {
				return ['class'=>'margin-5px padding-5px'];
			}

			return [];
		}
	}

?>
