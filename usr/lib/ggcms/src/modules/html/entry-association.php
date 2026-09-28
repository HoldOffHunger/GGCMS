<?php

	class module_entryassociation extends module_spacing {
		public $that;
		public $header;
		
		public function __construct($args) {
			$this->that = $args['that'];
			$this->header = $args['header'];
			
			if($this->that->counts['association'] === 0) {
				$parent = $this->that->record_list[count($this->that->record_list) - 2];
				if($parent['association'] && $parent['association'][0] && $parent['association'][0]['id']) {
					for($i = 0; $i < count($parent['association']); $i++) {
						$this->that->entry['association'][] = $parent['association'][$i];
						$this->that->counts['association']++;
					}
				}
			}
		}
		
		public function DisplayHeader() {
			if($this->header) {
				print('<h2 class="block-title">' . $this->header . '</h2>');
			}

			return TRUE;
		}

			/*
				A card for each associated entry -- on a text, its author: the
				portrait, the role and name, the years, and a line of what they
				wrote or said.
			*/

		public function Display($args) {
			if(!$this->that->entry['association'] || !$this->that->counts['association']) {
				return TRUE;
			}

			if(!empty($args['parent_code'])) {
				$parent_code = $args['parent_code'];
			} else {
				$parent_code = 'people';
			}

			$associations = $this->that->entry['association'];

			if(!empty($args['max'])) {
				$max = $args['max'];
			} else {
				$max = $this->that->counts['association'];
			}

			print('<section class="block associations" id="association">');

			$this->DisplayHeader();

			for($i = 0; $i < $max; $i++) {
				$association = $associations[$i];

				if(empty($args['type']) || $args['type'] === $association['Type']) {
					$this->DisplayCard(['association'=>$association, 'parent_code'=>$parent_code, 'type'=>$args['type']]);
				}
			}

			print('</section>');

			return TRUE;
		}

		public function DisplayCard($args) {
			$association = $args['association'];
			$parent_code = $args['parent_code'];
			$child = $association['entry'];
			$url = '/' . $parent_code . '/' . $child['Code'] . '/view.php';

			$display_image = NULL;

			if($child['image'] && count($child['image'])) {
				$child_images = $child['image'];
				shuffle($child_images);
				$display_image = $child_images[0];
			}

			if(!$display_image) {
				if(!empty($this->that->entry['association'][0]['entry']['image'])) {
					$display_image = $this->that->entry['association'][0]['entry']['image'][0];
				} elseif(!empty($child['association'][0]['entry']['image'])) {
					$display_image = $child['association'][0]['entry']['image'][0];
				} elseif(!empty($this->that->master_record['image'][0])) {
					$display_image = $this->that->master_record['image'][0];
				}
			}

			$role = $association['SubType'];

			if(!$role && !empty($args['type'])) {
				$role = ucfirst($args['type']);
			}

			print('<article class="author-card">');

			if($display_image) {
				print('<a class="author-card-image" href="' . $url . '" tabindex="-1" aria-hidden="true">');
				print('<img alt="" loading="lazy" src="/image/' . implode('/', str_split($display_image['FileDirectory'])) . '/' . $display_image['IconFileName'] . '">');
				print('</a>');
			}

			print('<div class="author-card-body">');

			if($role) {
				print('<p class="author-card-role">' . $role . '</p>');
			}

			$name = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $child['Title']);

			print('<h3 class="author-card-name"><a href="' . $url . '">' . $name . '</a>');

			$time_frame = $this->LifeYears(['child'=>$child]);

			if($time_frame) {
				print(' <span class="author-card-years">' . $time_frame . '</span>');
			}

			print('</h3>');

			if($child['Subtitle']) {
				print('<p class="author-card-subtitle">' . $child['Subtitle'] . '</p>');
			}

			$this->DisplaySummary(['child'=>$child]);
			$this->DisplayTags(['child'=>$child]);

			print('</div>');
			print('</article>');

			return TRUE;
		}

			// Birth and death years, as "(1861 - 1915)", from the event dates.

		public function LifeYears($args) {
			$child = $args['child'];

			if(!$child['eventdate']) {
				return '';
			}

			$birth_event = NULL;
			$death_event = NULL;

			foreach($child['eventdate'] as $child_event) {
				if($child_event['Title'] === 'Birth Day') {
					$birth_event = $child_event;
				} elseif($child_event['Title'] === 'Death Day') {
					$death_event = $child_event;
				}
			}

			if(!$birth_event && !$death_event) {
				return '';
			}

			$years = [];

			foreach([$birth_event, $death_event] as $event) {
				if($event && $event['id'] && $event['EventDateTime'] != '0000-00-00 00:00:00') {
					$years[] = $this->FormatDate(['date'=>explode('-', $event['EventDateTime'])[0] . '-00-00']);
				} else {
					$years[] = '?';
				}
			}

			return '(' . implode(' &ndash; ', $years) . ')';
		}

		public function DisplaySummary($args) {
			$child = $args['child'];

			if($child['description'] && $child['description'][0] && $child['description'][0]['Description']) {
				print('<p class="author-card-description">' . $child['description'][0]['Description'] . '</p>');
			}

			if($child['quote']) {
				$child_quotes = $child['quote'];
				shuffle($child_quotes);
				$max_limit = min(3, count($child_quotes));

				for($j = 0; $j < $max_limit; $j++) {
					$quote = $child_quotes[$j];

					if($quote && $quote['Quote']) {
						print('<blockquote class="author-card-quote"><p>' . str_replace('"', '\'', $quote['Quote']) . '</p>');

						if($quote['Source']) {
							print('<footer>' . $this->ShortSource(['source'=>$quote['Source']]) . '</footer>');
						}

						print('</blockquote>');
					}
				}
			} elseif($child['textbody'] && count($child['textbody'])) {
				$text_display = $this->that->handler->cleanser->FormatListOutput([
					'text'=>$child['textbody'][0]['FirstThousandCharacters'],
				]);

				if($text_display) {
					print('<p class="author-card-excerpt">' . $text_display . '</p>');
				}
			}

			return TRUE;
		}

		public function ShortSource($args) {
			$source = $args['source'];

			if(strlen($source) > 50) {
				$source = substr($source, 0, 50) . '...';
			}

			return $source;
		}

		public function DisplayTags($args) {
			$child = $args['child'];

			if(!$child['tag'] || !count($child['tag'])) {
				return FALSE;
			}

			$tags = $child['tag'];
			shuffle($tags);
			$max_limit = min(10, count($tags));

			print('<ul class="chips chips-small">');

			for($j = 0; $j < $max_limit; $j++) {
				$tag = $tags[$j];

				print('<li><a href="/view.php?action=browseByTag&amp;tag=' . urlencode($tag['Tag']) . '">');
				print($tag['Tag']);

				if($this->that->tag_counts[$tag['Tag']] > 1) {
					print(' <span class="chip-count">' . $this->that->tag_counts[$tag['Tag']] . '</span>');
				}

				print('</a></li>');
			}

			print('</ul>');

			return TRUE;
		}

		public function DisplayTimeFrame($args) {
			if($args['time_frame']) {
				print('<span class="author-card-years">' . $args['time_frame'] . '</span>');
			}

			return TRUE;
		}

			/* poached from html/entry-date.php */
		public function FormatDate($args) {
			$date = $args['date'];
			
			if(!$date) {
				return '?';
			}
			
			$event_date_pieces = explode('-', $date);
			
			$date_epoch_time = strtotime($date);
			
			$month_format = 'F';
			if($args['short-dates']) {
				$month_format = 'M.';
			}
			
			$year = $event_date_pieces[0];
			/*
			if(intval($event_date_pieces[0]) > 3000) {
				if($year >= 3000) {
					$diff = $year - 3000;
					$real_year = 1000 - $diff;
				} else {
					$real_year = $year;
				}
			*/
			$bce_check = substr($year, 0, 3);
			if($bce_check === 'bce') {
				$real_year = str_replace('bce', '', $year);
				$formatted = $real_year . ' BCE';
			} elseif($event_date_pieces[1] !== '00' && $event_date_pieces[2] !== '00') {
				$formatted = date("$month_format j, Y", $date_epoch_time);
			} elseif($event_date_pieces[1] !== '00') {
				$new_date_epoch_time = $event_date_pieces[0] . '-' . $event_date_pieces[1] . '-01';
				$formatted = date("$month_format, Y", strtotime($new_date_epoch_time));
			} else {
				$new_date_epoch_time = $event_date_pieces[0] . '-01-01';
				$formatted = date("Y", strtotime($new_date_epoch_time));
			}
			
			return $formatted;
		}
	}

?>