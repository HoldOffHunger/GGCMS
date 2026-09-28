<?php

	class module_entrydate extends module_spacing {
		public $that;
		public $record;
		public $entry_event_count;
		public $simpledisplaytxt;
		public $simpledisplayhtml;
		public $simpledata;
		
		public function __construct($args) {
			$this->that = $args['that'];
			$this->record = $this->that->entry;
			
			$this->entry_event_count = count($this->that->entry['eventdate']);
		}
		
		public function Display() {
			$simple_data = $this->getSimpleData();

			if($simple_data['text'] === '') {
				return;
			}

			$title_hash = $this->getDateTypeTitle();
			$title = $title_hash[$simple_data['type']] ? $title_hash[$simple_data['type']] : $simple_data['type'];

			print('<p class="date-badge" title="' . htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') . '">');
			print($simple_data['text']);
			print('</p>');

			return TRUE;
		}

		public function DisplayEventDatesHistory() {
			if($this->entry_event_count === 0) {
				return FALSE;
			}

			$this->DisplayEventDatesHistory_header();

			for($i = 0; $i < $this->entry_event_count; $i++) {
				$event_date = $this->that->entry['eventdate'][$i];

				$this->DisplayEventDatesHistory_singleDate(['eventdate'=>$event_date]);
			}

			$this->DisplayEventDatesHistory_footer();

			return TRUE;
		}

		public function DisplayEventDatesHistory_singleDate($args) {
			$eventdate = $args['eventdate'];
			
			$this->DisplayEventDatesHistory_singleDate_header($args);
			
			$this->DisplayEventDatesHistory_singleDate_icon($args);
			$this->DisplayEventDatesHistory_singleDate_dateTime($args);
			$this->DisplayEventDatesHistory_singleDate_title($args);
			$this->DisplayEventDatesHistory_singleDate_description($args);
			
			$this->DisplayEventDatesHistory_singleDate_footer($args);
			
			return TRUE;
		}
		
		public function DisplayEventDatesHistory_singleDate_header($args) {
			print('<li class="timeline-item">');
		}
		
		public function DisplayEventDatesHistory_singleDate_icon($args) {
			$eventdate = $args['eventdate'];
			
			$title_icon_hash = $this->getDateTitleIcons();
			$icon_alt_hash = $this->getDateIconAlts();
			
			$icon = $title_icon_hash[$eventdate['Title']];
			$alt = $icon_alt_hash[$eventdate['Title']];
			
			if($icon) {
				print('<img class="timeline-icon" src="/image/events/' . $icon . '.png" alt="' . $alt . '" width="22" height="22">');
			}
			
			return TRUE;
		}
		
		public function getDateIconAlts() {
			$icon_alt_hash = [
				'Added'=>'An icon of a news paper.',
				'Birth Day'=>'An icon of a baby.',
				'Death Day'=>'An icon of a gravestone.',
				'Publication'=>'An icon of a book resting on its back.',
				'Translated'=>'An icon of English being translation into Chinese.',
				'Updated'=>'An icon of a red pin for a bulletin board.',
				'Written'=>'An icon of a hand writing.',
			];
			
			return $icon_alt_hash;
		}
		
		public function getDateTitleIcons() {
			$title_icon_hash = [
				'Added'=>'news',
				'Birth Day'=>'baby',
				'Death Day'=>'gravestone',
				'Publication'=>'book',
				'Translated'=>'translation',
				'Updated'=>'red-pin',
				'Written'=>'writing',
			];
			
			return $title_icon_hash;
		}
		
		public function getDateTypeTitle() {
			$type_title_hash = [
				'Added'=>'This is the date added.',
				'Birth Day'=>'This is the date of birth.',
				'Birth DayDeath Day'=>'These are the dates of birth and death.',
				'Death Day'=>'This is the date of death.',
				'Publication'=>'This is the date of publication.',
				'Translated'=>'This is the date of translation.',
				'Updated'=>'This is the date of last update.',
				'Written'=>'This is the date of authorship.',
			];
			
			return $type_title_hash;
		}
		
		public function DisplayEventDatesHistory_singleDate_dateTime($args) {
			$eventdate = $args['eventdate'];
			
			$event_date = $eventdate['date'];
			$event_time = $eventdate['time'];
			
			print('<time class="timeline-date">');
			
			$eventdate['EventDateTime'] = str_replace('-00-00', '-01-01', $eventdate['EventDateTime']);
			$eventdate['EventDateTime'] = str_replace('-00', '-01', $eventdate['EventDateTime']);
			$date_epoch_time = strtotime($eventdate['EventDateTime']);
			
			if($event_date !== '0000-00-00') {
				print($this->FormatDate(['date'=>$event_date]));
			}
			
			if($event_time !== '00:00:00') {
				print(date("; g:i:s A (e)", $date_epoch_time));
			}
			
			print('</time> ');
			print('<span class="timeline-text">');
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
		
		public function DisplayEventDatesHistory_singleDate_title($args) {
			$eventdate = $args['eventdate'];
			
			if($eventdate['Title'] === 'Added' || $eventdate['Title'] === 'Updated') {
				$url = $this->that->handler->domain->GetPrimaryDomain(['lowercase'=>0, 'www'=>1]);
				print($eventdate['Title']);
				
				if($eventdate['Title'] === 'Added') {
					$article = ' to ';
				} else {
					$article = ' on ';
				}
				
				print(' ' . $article . ' <a href="/">' . $url . '</a>');
			} else {
				if($eventdate['Title'] === 'Publication' || $eventdate['Title'] === 'Written') {
					print($this->that->entry['Title']);
					print(' &mdash; ');
				}
				
				print($eventdate['Title']);
			}
		}
		
		public function DisplayEventDatesHistory_singleDate_description($args) {
			$eventdate = $args['eventdate'];
			
			if($eventdate['Description']) {
				print(' ');
				print('(');
				print($eventdate['Description']);
				print(')');
			}
			
			print('.');
		}
		
		public function DisplayEventDatesHistory_singleDate_footer($args) {
			$eventdate = $args['eventdate'];
			
			print('</span>');
			print('</li>');
		}
		
		public function DisplayEventDatesHistory_header() {
			print('<section class="block chronology" id="eventdate">');
			print('<h2 class="block-title">Chronology</h2>');
			print('<ol class="timeline">');

			return TRUE;
		}

		public function BackToTopLinkBox() {
			print('<a class="to-top" href="#top">Back to top</a>');

			return TRUE;
		}

		public function DisplayEventDatesHistory_footer() {
			print('</ol>');
			print('</section>');

			return TRUE;
		}

		public function getSimpleDisplay_TXT() {
			if($this->simpledisplaytxt) {
				return $this->simpledisplaytxt;
			}
			
			$simple_data = $this->getSimpleData();
			
			if($simple_data['text'] === '') {
				return '';
			}
			
			$text = $simple_data['text'];
			
			$text = str_replace('&mdash;', '--', $text);
			
			$text .= "\n\n";
			
			return $this->simpledisplaytxt = $text;
		}
		
		public function getSimpleDisplay_HTML() {
			if($this->simpledisplayhtml) {
				return $this->simpledisplayhtml;
			}
			
			$simple_data = $this->getSimpleData();
			
			if($simple_data['text'] === '') {
				return '';
			}
			
			$html = '<p>';
			$html .= $simple_data['text'];
			$html .= '</p>';
			$html .= "\n\n";
			
			return $this->simpledisplayhtml = $html;
		}

		public function getSimpleData() {
			if($this->simpledata) {
				return $this->simpledata;
			}
			
			return $this->simpledata = $this->getEntrySimpleData([
				'entry'=>$this->that->entry,
				'checkparents'=>TRUE,
			]);
		}
			
		public function getEntrySimpleData($args) {
			$entry = $args['entry'];
			
			$time_frame = $event_type = '';
			
			if($entry['eventdate'] && $entry['eventdate'][0] && $entry['eventdate'][0]['id']) {
				$events = $this->getRelevantDates(['entry'=>$entry]);
			}
			
			if($args['checkparents']) {
				if(!$events || !$events[0]) {
					$record_list = $this->that->record_list;
					for($i = 0; $i < count($record_list); $i++) {
						$record = $record_list[$i];
						if($record && $record['id']) {
							$events = $this->getRelevantDates(['entry'=>$record]);
							if($events && $events[0]) {
								$i = count($record_list);
							}
						}
					}
				}
			}
			
			if($events) {
				$args['events'] = $events;
				$time_frame = $this->getRelevantTimeFrame($args);
				if(count($events) === 2) {
					$event_type = $events[0]['Title'] . $events[1]['Title'] ;
				} else {
					$event_type = $events[0]['Title'];
				}
			}
			
			return[
				'text'=>$time_frame,
				'type'=>$event_type,
			];
		}
		
		public function getRelevantTimeFrame($args) {
			$events = $args['events'];
			$events_count = count($events);
			
			$events_hash = [];
			
			for($i = 0; $i < $events_count; $i++) {
				$event = $events[$i];
				$events_hash[$event['Title'] ?? ''] = $event;
			}
			
			# bugfix here:
			if(!$events[0]['EventDateTime']) {
				$events_count = 0;
			}
			
			if($events_count === 0) {
				return '';
			}elseif(!$events_hash['Birth Day'] && !$events_hash['Death Day']) {
				if($events[0]['EventDateTime'] === '0000-00-00 00:00:00') {
					return '?';
				}
				
				$event_date_pieces = explode('-', $events[0]['EventDateTime']);
				$year = $event_date_pieces[0];
				return $this->FormatDate(['date'=>$year . '-00-00', 'short-dates'=>$args['short-dates']]);
			}
			
			$date1 = $events_hash['Birth Day']['date'];
			$date2 = $events_hash['Death Day']['date'];
			return $this->FormatDate(['date'=>$date1, 'short-dates'=>$args['short-dates']]) . ' &mdash; ' . $this->FormatDate(['date'=>$date2, 'short-dates'=>$args['short-dates']]);
		}
		
		public function getRelevantDate() {
			for($i = 0; $i < $this->entry_event_count; $i++) {
				$entry_event = $this->that->entry['eventdate'][$i];
				
				if($entry_event['Title'] === 'Publication') {
					$publication_event = $entry_event;
					$i = $this->entry_event_count;
				}
				
				if($entry_event['Title'] === 'Written') {
					$written_event = $entry_event;
				}
			}
			
			if($publication_event) {
				return $publication_event;
			}
			
			return $written_event;
		}
		
		public function getRelevantDates($args) {
			$entry = $args['entry'];
			
			if($entry && $entry['eventdate']) {
				for($i = 0; $i < count($entry['eventdate']); $i++) {
					$entry_event = $entry['eventdate'][$i];
					
					if($entry_event['Title'] === 'Publication') {
						$publication_event = $entry_event;
						$i = count($entry['eventdate']);
					} elseif($entry_event['Title'] === 'Written') {
						$written_event = $entry_event;
					} elseif($entry_event['Title'] === 'Birth Day') {
						$birthday_event = $entry_event;
					} elseif($entry_event['Title'] === 'Death Day') {
						$deathday_event = $entry_event;
					}
				}
			}
			
			$life_events = [];
			
			if($birthday_event) {
				$life_events[] = $birthday_event;
			}
			
			if($deathday_event) {
				$life_events[] = $deathday_event;
			}
			
			if(count($life_events) > 0) {
				return $life_events;
			}
			
			if($publication_event) {
				return [$publication_event];
			}
			
			return [$written_event];
		}
		
		public function showAddedOn() {
			return TRUE;
		#	$that = $args['that'];
			
		#	print("<!-- BT: \n\n");
			
		#	print_r($that->entry['entrypermission']);
			
			print('<div style="margin-right:5px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray15 float-left">');
			print('<span class="comments-link-box" style="font-size:0.8em;font-family:arial, tahoma;margin:3px;padding:0px;display:inline-block;">');
			print('<strong>');
			print('Added On: ');
			
			print(date("M. j, Y, H:i:s", strtotime($this->that->entry['OriginalCreationDate'])));
			
			print('</strong>');
			print('</span>');
			print('</div>');
		#	print("\n\n" . "-->\n\n");
			
			return TRUE;
		}
	}

?>