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
				The archive in numbers, for a front page or an about page: how
				many texts, how many words (with the printed pages that makes),
				how many formats each comes in, how long it would take to read
				the lot, and how long the site has been open.  All are computed
				-- from child_record_stats, the formats module and the master
				record's own date -- never typed in, so they stay true as the
				archive grows.

				Every figure is printed, and four of them show.  The page is
				served from the page cache, so a choice made here would stand
				until the next warm; the script after the panel chooses again
				for each visit, before the panel is drawn.  Without scripts,
				the first four show.

					$record_totals->DisplayStats(['that'=>$this]);

				Switches:
				  show   how many figures to show at once; 4 by default
		*/

		public function DisplayStats($args) {
			$that = $args['that'];
			$show = !empty($args['show']) ? (int) $args['show'] : 4;
			$figures = $this->StatFigures(['that'=>$that]);

			if(!$figures) {
				return FALSE;
			}

			print('<section class="stats" aria-label="The archive in numbers">');
			print('<div class="stats-panel">');

			foreach($figures as $index => $figure) {
				$figure['hidden'] = $index >= $show;
				$this->DisplayStat($figure);
			}

			print('</div>');

			if(count($figures) > $show) {
				print('<script>');
				print('(function (panel, show) {');
				print('var stats = [].slice.call(panel.children), chosen = stats.slice();');
				print('for (var i = chosen.length - 1; i > 0; i--) { var j = Math.floor(Math.random() * (i + 1)), t = chosen[i]; chosen[i] = chosen[j]; chosen[j] = t; }');
				print('chosen = chosen.slice(0, show);');
				print('stats.forEach(function (stat) { stat.hidden = chosen.indexOf(stat) === -1; });');
				print('})(document.currentScript.previousElementSibling, ' . $show . ');');
				print('</script>');
			}

			print('</section>');

			return TRUE;
		}

			/*
				Each figure the record can support, in the order they show
				when scripts are off.  One the record cannot support -- no
				texts counted yet, no founding date -- is left out rather than
				shown as a zero.
			*/

		public function StatFigures($args) {
			$that = $args['that'];
			$stats = $that->child_record_stats;
			$figures = [];

			if(!$stats || empty($stats['ChildRecordCount'])) {
				return $figures;
			}

			require_once(GGCMS_DIR . 'modules/html/alternateformats.php');
			$formats = new module_alternateformats(['that'=>$that]);

			$words = (int) $stats['ChildWordCount'];
			$texts = (int) $stats['ChildRecordCount'];

			$figures[] = [
				'figure'=>number_format($texts),
				'label'=>'Texts',
				'note'=>'Books, essays, letters and interviews',
			];

				/*
					Rounded to a precision the size deserves.  To the nearest
					thousand alone, MasereelGroup's 4,375 words were "about 0
					printed pages".
				*/

			$pages = $words / 300;
			$pages = ($pages >= 1000) ? round($pages, -3) : (($pages >= 100) ? round($pages, -1) : max(1, round($pages)));

			$figures[] = [
				'figure'=>number_format($words),
				'label'=>'Words',
				'note'=>'About ' . number_format($pages) . ' printed ' . (($pages == 1) ? 'page' : 'pages'),
			];

			$figures[] = [
				'figure'=>number_format(count($formats->getFormats())),
				'label'=>'Formats for every text',
				'note'=>'From EPUB and PDF to Braille and DAISY',
			];

				// a site that can be read in an afternoon has no days to count
			$days = $words / 250 / 60 / 24;

			if($days >= 1) {
				$figures[] = [
					'figure'=>number_format(round($days)),
					'label'=>'Days to read it all',
					'note'=>'At 250 words a minute, never stopping to sleep',
				];
			}

			$since = $this->SinceFigure(['that'=>$that, 'formats'=>$formats]);

			if($since) {
				$figures[] = $since;
			}

			return $figures;
		}

			/*
				The year the site opened: the master record's creation date,
				which is the day its first entry was made.
			*/

		public function SinceFigure($args) {
			$master = $args['that']->master_record;

			if(!$master || empty($master['OriginalCreationDate'])) {
				return NULL;
			}

			$opened = strtotime($master['OriginalCreationDate']);

			if(!$opened || $opened < strtotime('1990-01-01')) {
				return NULL;
			}

			$years = (int) floor((time() - $opened) / (365.2425 * 24 * 60 * 60));

			return [
				'figure'=>date('Y', $opened),
				'label'=>'Around since',
				'note'=>$years >= 2 ? ucfirst($args['formats']->NumberWord(['number'=>$years])) . ' years of free reading' : 'Open since ' . date('j F Y', $opened),
			];
		}

		public function DisplayStat($args) {
			print('<div class="stat"' . (!empty($args['hidden']) ? ' hidden' : '') . '>');
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