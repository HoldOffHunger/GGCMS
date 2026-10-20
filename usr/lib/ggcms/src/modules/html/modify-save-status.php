<?php

		/*
			How a save or update went, at the top of the page: the entry
			it was made to, linked, and the status the script left in
			save_status.

			Switches:
			  adding  the entry is the parent the new one was added to;
			          Save.  The entry itself, being updated, by default
		*/

	class module_modifysavestatus extends module_spacing {
		public $that;
		public $adding;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->adding = !empty($args['adding']);
		}

		public function Display() {
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
	}

?>
