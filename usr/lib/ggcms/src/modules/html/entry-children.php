<?php

	class module_entrychildren extends module_spacing {
		public $that;
		public $header;
		public $entrysort;
		
		public function __construct($args) {
			$this->that = $args['that'];
			$this->header = $args['header'];
			$this->entrysort = $args['entrysort'];
		}
		
		public function Display() {
			if($this->that->children && $this->that->counts['children'] !== 0) {
				return $this->Display_Entries([
					'entries'=>$this->that->children,
					'count'=>$this->that->counts['children'],
					'alts'=>TRUE,
					'stats'=>TRUE,
				]);
			}
			
			return FALSE;
		}
		
		public function Display_Entries($args) {
			$entries = $args['entries'];
			$count = $args['count'];
			$stats = $args['stats'];
			$alts = $args['alts'];
			$date_field = $args['datefield'];
			$url_prefix = $args['url_prefix'];
			$url_action = $args['url_action'];

			print('<section class="block children" id="children">');

			if($this->header || $stats || ($alts && $this->that->counts['textbody'] === 0)) {
				print('<div class="block-head">');

				if($this->header) {
					print('<h2 class="block-title">' . $this->header . '</h2>');
				}

						// Child Record Counts

					// -------------------------------------------------------------

				if($stats) {
					$date_epoch_time = strtotime($this->that->child_record_stats['LastModificationDate']);

					print('<p class="list-stats" title="Last updated ' . date("F d, Y; H:i:s", $date_epoch_time) . '">');
					print(number_format($this->that->child_record_stats['ChildRecordCount']) . ' chapters');
					print('<span class="dot" aria-hidden="true"> &middot; </span>');
					print(number_format($this->that->child_record_stats['ChildWordCount']) . ' words');
					print('</p>');
				}

				print('</div>');

						// Alternates, for a work that is only its chapters

					// -------------------------------------------------------------

				if($alts && $this->that->counts['textbody'] === 0) {
					require_once(GGCMS_DIR . 'modules/html/alternateformats.php');
					$formats = new module_alternateformats(['that'=>$this->that, 'audio'=>FALSE]);
					$formats->Display();
				}
			}

					// Display Children

				// -------------------------------------------------------------

			if($this->that->children_count > $this->that->maxChildren()) {
				$record_list_count = count($this->that->record_list);
				$url = '';
				for($i = 0; $i < $record_list_count; $i++) {
					$record = $this->that->record_list[$i];
					$url .= '/' . $record['Code'];
				}

				$url .= '/view.php?headless=1&amp;action=browse';
				print('<iframe class="entry-browser" title="' . htmlspecialchars((string) $this->header, ENT_QUOTES, 'UTF-8') . '" src="' . $url . '"></iframe>');
			} else {
				$children_display = $this->entrysort->Sort(['entries'=>$entries, 'sort_field'=>$date_field]);

				if($date_field) {
					$children_display = array_reverse($children_display);
				}

				print('<ol class="entry-list">');

				foreach($children_display as $child) {
					if($child['entry']) {
						$child = $child['entry'];
					}

					$this->Display_Entry(['child'=>$child, 'args'=>$args]);
				}

				print('</ol>');
			}

			print('</section>');

			return TRUE;
		}

		public function Display_Entry($args) {
			$child = $args['child'];
			$list_args = $args['args'];
			$url_prefix = $list_args['url_prefix'];
			$url_action = $list_args['url_action'];

			$display_image = NULL;

			if($child['image']) {
				$child_images = $child['image'];
				if(count($child_images)) {
					shuffle($child_images);
					$display_image = $child_images[0];
				}
			}

			if(!$display_image && $this->that->master_record['image'] && count($this->that->master_record['image']) > 0) {
				$display_image = $this->that->master_record['image'][0];
			}

				// Where the child lives: its parents' codes, then its own

			$codes = [];
			$parent_count = 0;

			if($child['parents']) {
				$parent_count = count($child['parents']);
				foreach($child['parents'] as $parent) {
					if($parent['Code'] && $parent['id'] !== $child['id']) {
						$codes[] = $parent['Code'];
					}
				}

				$codes[] = $child['Code'];
				$link = '/' . $url_prefix . implode('/', $codes);
			} else {
				$link = $url_prefix . $child['Code'];
			}

			$href = $link . '/view.php';

			if($this->that->entry['ChildAction']) {
				$href .= '?action=' . $this->that->entry['ChildAction'];
			} elseif($url_action) {
				$href .= '?action=' . $url_action;
			}

			$title = $child['ListTitle'] ? $child['ListTitle'] : $child['Title'];

			print('<li class="entry-card"');
			if($this->that->handler->authentication->user_session['UserAdmin.id']) {
				print(' title="id: ' . $child['id'] . '"');
			}
			print('>');

			if($display_image) {
				print('<a class="entry-card-image" target="_parent" href="' . $href . '" tabindex="-1" aria-hidden="true">');
				print('<img alt="" loading="lazy" width="' . ceil($display_image['IconPixelWidth'] / 2) . '" height="' . ceil($display_image['IconPixelHeight'] / 2) . '" src="/image/' . implode('/', str_split($display_image['FileDirectory'])) . '/' . $display_image['IconFileName'] . '">');
				print('</a>');
			}

			print('<div class="entry-card-body">');

			print('<h3 class="entry-card-title"><a target="_parent" href="' . $href . '">');
			print($title);

			if($child['Subtitle']) {
				print('<span class="entry-card-subtitle">: ' . $child['Subtitle'] . '</span>');
			}

			print('</a></h3>');

			$meta = $this->EntryMeta(['child'=>$child, 'args'=>$list_args, 'codes'=>$codes, 'parent_count'=>$parent_count, 'link'=>$link]);

			if($meta) {
				print('<p class="entry-card-meta">' . implode('<span class="dot" aria-hidden="true"> &middot; </span>', $meta) . '</p>');
			}

			print('<p class="entry-card-excerpt">');

			$this->DisplayDescription(['child'=>$child]);

			if($child['quote']) {
				$child_quotes = $child['quote'];
				$max_limit = min(3, count($child_quotes));
				shuffle($child_quotes);

				for($i = 0; $i < $max_limit; $i++) {
					$quote = $child_quotes[$i];

					if($quote && $quote['Quote']) {
						print('<span class="entry-card-quote">&ldquo;' . str_replace('"', '\'', $quote['Quote']) . '&rdquo;');

						if($quote['Source']) {
							$source = $quote['Source'];

							if(strlen($source) > 50) {
								$source = substr($source, 0, 50) . '...';
							}

							print(' <span class="source">(' . $source . ')</span>');
						}

						print('</span> ');
					}
				}
			} elseif($child['textbody'] && $child['textbody'][0]) {
				$this->DisplayTextBody(['child'=>$child]);
			} elseif($child['children'] && $child['children'][0] && $child['children'][0]['textbody'] && $child['children'][0]['textbody'][0]) {
				$this->DisplayTextBody(['child'=>$child['children'][0]]);
			}

			print('</p>');

					// Tags

				// -------------------------------------------------------------

			if($child['tag'] && count($child['tag'])) {
				$tags = $child['tag'];
				$max_limit = min(10, count($tags));
				shuffle($tags);

				print('<ul class="chips chips-small">');

				for($i = 0; $i < $max_limit; $i++) {
					$tag = $tags[$i];
					print('<li><a target="_parent" href="/view.php?action=browseByTag&amp;tag=' . urlencode($tag['Tag']) . '">' . $tag['Tag'] . '</a></li>');
				}

				print('</ul>');
			}

			print('</div>');
			print('</li>');

			return TRUE;
		}

			/*
				The line under a child's title: who wrote it, where it is
				listed, what it came from, when, how many writings and quotes
				hang off it -- each only where the caller asked and the data has
				it -- and, for an administrator, a way to edit it.
			*/

		public function EntryMeta($args) {
			$child = $args['child'];
			$list_args = $args['args'];
			$codes = $args['codes'];
			$parent_count = $args['parent_count'];
			$link = $args['link'];

			$meta = [];

			if($child['association'] && count($child['association']) > 0) {
				$writings = [];
				$roles = [];
				$cancelled = 0;

				foreach($child['association'] as $child_association) {
					if($child_association['ChosenEntryid'] == $this->that->entry['id']) {
						$cancelled = 1;
					}
					if($child_association['Type'] == 'Writing') {
						$writings[] = $child_association;
					} elseif($child_association['Type'] == 'Role') {
						$roles[] = $child_association;
					}
				}

				if(count($roles) > 0 && ($list_args['list_author'] || $cancelled == 0)) {
					$printable_roles = [];

					foreach($roles as $role) {
						$printable_roles[] = '<a target="_parent" href="/people/' . $role['entry']['Code'] . '/view.php">' . $role['entry']['Title'] . '</a>';
					}

					$meta[] = 'by ' . implode(', ', $printable_roles);
				}

				if($list_args['parents'] == 2) {
					$last_parent = $child['parents'][$parent_count - 2];
					$second_to_last_parent = $child['parents'][$parent_count - 3];
					$third_to_last_parent = $child['parents'][$parent_count - 5];

					$parents_codes = $codes;
					array_pop($parents_codes);

					$meta[] = 'listed in <a target="_parent" href="/' . implode('/', $parents_codes) . '/view.php">' . $third_to_last_parent['Title'] . ' :: ' . $second_to_last_parent['Title'] . ' :: ' . $last_parent['Title'] . '</a>';
				}

				if(!$list_args['list_author'] && (count($writings) > 0)) {
					$printable_roles = [];

					foreach($writings as $writing) {
						$printable_roles[] = '<a href="/writings/' . $writing['entry']['Code'] . '/view.php">' . $writing['entry']['Title'] . '</a>';
					}

					$meta[] = 'from ' . implode(', ', $printable_roles);
				}
			}

			$time_frame = $this->getTimeFrame(['child'=>$child]);

			if($time_frame) {
				$meta[] = '<span class="entry-card-date">' . $time_frame . '</span>';
			}

			if($list_args['show_associations'] && $child['associated'] && count($child['associated']) > 0) {
				$writings_count = 0;
				$quotes_count = 0;

				foreach($child['associated'] as $single_associated) {
					foreach($single_associated['entry']['parents'] as $associated_entry_parent) {
						if($associated_entry_parent['Code'] === 'writings') {
							$writings_count++;
						} elseif($associated_entry_parent['Code'] === 'quotes') {
							$quotes_count++;
						}
					}
				}

				if($writings_count > 0) {
					$meta[] = number_format($writings_count) . ' writings';
				}

				if($quotes_count > 0) {
					$meta[] = number_format($quotes_count) . ' quotes';
				}
			}

			if($child['textbody'] && $child['textbody'][0] && $child['textbody'][0]['WordCount']) {
				$meta[] = number_format($child['textbody'][0]['WordCount']) . ' words';
			}

			if($this->that->handler->authentication->user_session['UserAdmin.id']) {
				$meta[] = '<a class="admin-link" target="_parent" href="' . $link . '/modify.php?action=Edit">Edit</a>';
			}

			return $meta;
		}
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
			$bce_check = mb_substr($year, 0, 3, 'utf-8');
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
		
		public function DisplayTimeFrame($args) {
			if($args['time_frame']) {
				print('<span class="entry-card-date">' . $args['time_frame'] . '</span>');
			}
			
			return TRUE;
		}
		
		public function getTimeFrame($args) {
			$child = $args['child'];
			
			if($child['eventdate']) {
				unset($publication_event);
				unset($birth_day_event);
				unset($death_day_event);
				
				$child_event_count = count($child['eventdate']);
				for($i = 0; $i < $child_event_count; $i++) {
					$child_event = $child['eventdate'][$i];
						
					if($child_event['Title'] === 'Publication' || $child_event['Title'] === 'Written') {
						$publication_event = $child_event;
					}
					
					if($child_event['Title'] === 'Birth Day') {
						$birth_day_event = $child_event;
					}
					
					if($child_event['Title'] === 'Death Day') {
						$death_day_event = $child_event;
					}
					
					if($publication_event || ($birth_day_event && $death_day_event)) {
						$i = $child_event_count;
					}
				}
				
				if($publication_event) {
					if($publication_event['EventDateTime'] != '0000-00-00 00:00:00') {
						$event_date_pieces = explode('-', $publication_event['EventDateTime']);
						$year = $event_date_pieces[0];
						$time_frame = $year;
					} else {
						$time_frame = '?';
					}
				} else if ($birth_day_event && $death_day_event) {
					$time_frame = $this->FormatDate(['date'=>$birth_day_event['EventDateTime']]) . ' - ' . $this->FormatDate(['date'=>$death_day_event['EventDateTime']]);
				} else if ($birth_day_event) {
					$time_frame = $this->FormatDate(['date'=>$birth_day_event['EventDateTime']]) . ' - ' . '?';
				} else if ($death_day_event) {
					$time_frame = '?' . ' - ' . $this->FormatDate(['date'=>$death_day_event['EventDateTime']]);
				}
				
				return $time_frame;
			}
			
			return '';
		}
		
		public function DisplayTextBody($args) {
			$child = $args['child'];
			
			$text_bodies = $child['textbody'];
			
			$text_display = $text_bodies[0]['FirstThousandCharacters'];
			
			$text_display = preg_replace('/Image::(\d+)(\s+)/', '', $text_display);
			$text_display = str_replace($this->getBlockingHTML(), ' ', $text_display);
	#		$text_display = str_replace('<hr>', ' ', $text_display);
			$text_display = trim(strip_tags($text_display));
			
			if(strlen($text_display) > 750) {
				$text_display = substr($text_display, 0, 750) . '...';
			}
			
			if($text_display) {
				print($text_display);
				
				if($text_bodies[0]['Source']) {
					$source = $text_bodies[0]['Source'];
					
					if(strlen($source) > 50) {
						$source = substr($source, 0, 50) . '...';
					}
					
					print(' (From: ' . $source . '.)');
				}
			}
			
			return TRUE;
		}
		
		public function DisplayDescription($args) {
			$child = $args['child'];
			
			if($child['description']) {
				$description = $child['description'][0];
				
				if($description && $description['Description']) {
					print('<em>');
					print($description['Description']);
					print(' ');
					print('</em>');
					
					if($description['Source']) {
						$source = $description['Source'];
						
						if(strlen($source) > 50) {
							$source = substr($source, 0, 50) . '...';
						}
						
						print(' (From : ' . $source . '.)');
					}
				}
			}
			
			return TRUE;
		}
		
		public function getBlockingHTML() {
			return [
				'_',
				'&nbsp;',
				'<br>',
				'<br >',
				'<br />',
				'<hr>',
				'<hr >',
				'<hr />',
			];
		}
	}

?>