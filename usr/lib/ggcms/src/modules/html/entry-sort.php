<?php

	class module_entrysort extends module_spacing {
		public function Sort($args) {
			$entries = $args['entries'];
			
			$entries_sorted = [];
			
			foreach($entries as $child) {
				$child_sorting = $child;
				if($child['entry']) {
					$child_sorting = $child['entry'];
				}
				
				if($args['sort_field']) {
					$sort_key = $child_sorting[$args['sort_field']];
				} else {
					$sort_key = $child_sorting['ListTitleSortKey'];
				}
				
				if(!$sort_key) {
					$sort_key = $child_sorting['ListTitle'];
				}
				
				if(!$sort_key) {
					$sort_key = $child_sorting['Title'];
				}
				
				if(!$sort_key && $entries_sorted[$sort_key]) {	# hey, got some advice for you!  don't die!
#					$sort_key = rand
				}
				
				$sort_key .= '_' . $child['id'];	# cuz if it is, then it do
				
				# random
				
				$entries_sorted[$sort_key] = $child;
			}
			
			uksort($entries_sorted, 'strnatcasecmp');
			
			return $entries_sorted;
		}
	}

?>