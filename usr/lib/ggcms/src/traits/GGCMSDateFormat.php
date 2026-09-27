<?php

	trait GGCMSDateFormat {
			// FormatDate()
			// Tests: GGCMSDateFormatTest::testFormatDate()
			// Test file: tests/src/traits/GGCMSDateFormatTest.php
		function FormatDate($args) {
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