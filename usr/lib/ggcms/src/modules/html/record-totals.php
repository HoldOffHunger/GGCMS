<?php

	class module_recordtotals extends module_spacing {
		public function Display($args) {
			$that = $args['that'];
			
			print('<center>');
			print('<div class="horizontal-center width-90percent">');
			
			$this->DisplayChildTotals($args);
			$this->DisplayGrandChildTotals($args);
			
			print('</div>');
			print('</center>');
		
					// Section
				
				// -------------------------------------------------------------
			
			print('<div class="clear-float">');
			print('</div>');
			
			return true;	// success
		}
		
		public function DisplayChildTotals($args) {
			$that = $args['that'];
			$mouseover = 'This is the total count of ' . $that->entry['ChildAdjective'] . ' ' . $that->entry['ChildNounPlural'] . '.';
			
			print('<div class="border-2px background-color-gray15 margin-5px float-left" title="' . $mouseover . '">');
			print('<h3 class="horizontal-left margin-5px font-family-arial">');
			
			print($that->entry['ChildAdjective'] . ' ' . $that->entry['ChildNounPlural'] . ' : ' . number_format($that->children_count));
			
			print('</h3>');
			print('</div>');
			
			return true;	// success
		}
		
		public function DisplayGrandChildTotals($args) {
			$that = $args['that'];
			
			print('<div class="border-2px background-color-gray15 margin-5px float-right">');
			
			print('<strong>');
			print(str_replace('<p>', '<p class="horizontal-left margin-5px font-family-tahoma">', $this->entry['textbody'][0]['Text']));
			
			print('<h3 class="horizontal-left margin-5px font-family-tahoma" title="');
			
			print(' (Last Updated: ');
			$date_epoch_time = strtotime($that->child_record_stats['LastModificationDate']);
			$full_date = date("F d, Y; H:i:s", $date_epoch_time);
			print($full_date);
			print('.)');
			
			$separator = $this->DisplayGrandChildTotalsSeparator();
			
			print('">');
			print(number_format($that->child_record_stats['ChildRecordCount']) . ' ' . $that->entry['GrandChildNounPlural']);
			print($separator);
			print(number_format($that->child_record_stats['ChildWordCount']) . ' Words');
			print($separator);
			print(number_format($that->child_record_stats['ChildCharacterCount']) . ' Chars');
			
			print('</strong>');
			print('</h3>');
			
			print('</div>');
			
			return true;	// success
		}
		
		public function DisplayGrandChildTotalsSeparator() {
			return ' &mdash; ';
		}
	}

?>