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
		
			/*
				The archive in four numbers, for a front page or an about page:
				how many texts, how many words (with the printed pages that
				makes), how many formats each comes in, and how long it would
				take to read the lot.  All four are computed -- from
				child_record_stats and the formats module -- never typed in, so
				they stay true as the archive grows.
			*/

		public function DisplayStats($args) {
			$that = $args['that'];
			$stats = $that->child_record_stats;

			if(!$stats || empty($stats['ChildRecordCount'])) {
				return FALSE;
			}

			require_once(GGCMS_DIR . 'modules/html/alternateformats.php');
			$formats = new module_alternateformats(['that'=>$that]);

			$words = (int) $stats['ChildWordCount'];
			$texts = (int) $stats['ChildRecordCount'];

			print('<section class="stats" aria-label="The archive in numbers">');
			print('<div class="stats-panel">');

			$this->DisplayStat([
				'figure'=>number_format($texts),
				'label'=>'Texts',
				'note'=>'Books, essays, letters and interviews',
			]);

			$this->DisplayStat([
				'figure'=>number_format($words),
				'label'=>'Words',
				'note'=>'About ' . number_format(round($words / 300, -3)) . ' printed pages',
			]);

			$this->DisplayStat([
				'figure'=>number_format(count($formats->getFormats())),
				'label'=>'Formats for every text',
				'note'=>'From EPUB and PDF to Braille and DAISY',
			]);

			$this->DisplayStat([
				'figure'=>number_format(round($words / 250 / 60 / 24)),
				'label'=>'Days to read it all',
				'note'=>'At 250 words a minute, never stopping to sleep',
			]);

			print('</div>');
			print('</section>');

			return TRUE;
		}

		public function DisplayStat($args) {
			print('<div class="stat">');
			print('<span class="stat-figure">' . $args['figure'] . '</span>');
			print('<span class="stat-label">' . $args['label'] . '</span>');
			print('<span class="stat-note">' . $args['note'] . '</span>');
			print('</div>');

			return TRUE;
		}

		public function DisplayGrandChildTotalsSeparator() {
			return ' &mdash; ';
		}
	}

?>