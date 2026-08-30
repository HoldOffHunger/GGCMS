<?php

	$grand_parent = $this->record_list[count($this->record_list) - 3];
	$event_dates = $this->entry['association'][0]['entry']['eventdate'];

	print($this->entry['association'][0]['entry']['Title']);
	
	if($event_dates && count($event_dates) > 0) {
		foreach($event_dates as $event_date) {
			if($event_date['Title'] === 'Birth Day') {
				$birth_day = $event_date;
			} elseif($event_date['Title'] === 'Death Day') {
				$death_day = $event_date;
			}
		}
		
		print(' (');
		
		if($birth_day) {
			print(FormatDate(['date'=>$birth_day['EventDateTime']]));
		} else {
			print('?');
		}
		
		print(' - ');
		
		if($death_day) {
			print(FormatDate(['date'=>$death_day['EventDateTime']]));
		} else {
			print('?');
		}
		
		print(')');
	}
	
	print(' on ');
	
	print($grand_parent['Title']);
	
	print(' and ');
	
	print($this->parent['Title']);
	
#	print_r($this->entry['association'][0]['entry']['eventdate']);
	
#	print_r($this->record_list[count($this->record_list) - 3]);

?>