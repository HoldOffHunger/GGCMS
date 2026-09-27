<?php

	class module_entrynewest extends module_spacing {
		public $that;
		
		public function __construct($args) {
			$this->that = $args['that'];
		}
	
				// Newest-Entries Record List
			
			// -------------------------------------------------------------
		
		public function Display() {
				/*
					The script decides whether to gather this at all -- see
					RecordRelationEnabled and the record_relations config -- so a
					template that includes this module on a site where the switch
					is off arrives here with nothing set.  count(NULL) is fatal in
					PHP 8, so that combination took whole sites to a 500 rather
					than printing no block.  A module renders what it was given,
					and being given nothing is a valid answer.
				*/

			if(!$this->that->newest_entries || !is_array($this->that->newest_entries)) {
				return TRUE;
			}

			$newest_entries_count = count($this->that->newest_entries);
			
			if($newest_entries_count === 0) {
				return TRUE;
			}
			
			print('<center>');
			print('<div class="horizontal-center width-80percent">');
			print('<div class="border-2px background-color-gray15 margin-5px float-left width-100percent">');
			
			print('<center>');
			
			print('<h2 class="horizontal-left margin-5px font-family-arial font-size-250percent">');
			print('<strong>');
			print('<div class="border-2px" style="display:inline;margin:20px;">');
			print('<div style="display:inline;margin:20px;">');
				/*
					Absolute, not relative.  "news.php" resolves against the
					current entry, so on /anarchism/anna-karenina/ this asked for
					/anarchism/anna-karenina/news.php -- a 404 that a crawler then
					follows and compounds.  navigation.php has always built this
					link correctly; this one did not.
				*/

			print('<a href="' . $this->that->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/news.php">');
			print('Newest Additions :');
			print('</a>');
			print('</div>');
			print('</div>');
			print('</strong>');
			print('</h2>');
			
			print('<div class="horizontal-center width-95percent font-family-arial margin-bottom-5px font-size-75percent">');
			
		#	print('<table>');
			for($i = 0; $i < $newest_entries_count; $i++) {
				$newest_entry = $this->that->newest_entries[$i];
				
				$this->Display_Entry(['entry'=>$newest_entry]);
			}
			#print('</table>');
			
			print('</div>');
			print('</center>');
			
			print('</div>');
			print('</div>');
			print('</center>');
			
			print('<div class="clear-float"></div>');
		}
		
		public function Display_Entry($args) {
			$newest_entry = $args['entry'];
			
			$newest_entry_parents = $newest_entry['parents'];
			#if($newest_entry_parents && array_key_exists(count($newest_entry_parents) - 1, $newest_entry_parents)) {
			if($newest_entry_parents[0] && $newest_entry['id'] !== $newest_entry_parents[0]['id']) {
				$last_parent = $newest_entry_parents[0];
			} elseif($this->that->master_record['id'] !== $newest_entry['id']) {
				$last_parent = $this->that->master_record;
			}
			
			$parent_codes = $this->Display_GetParentCodes(['entry'=>$newest_entry]);
			
			print('<div style="float:left;margin:2px;">');
			
			print('<div class="border-2px background-color-gray14 horizontal-left">');
			print('<div class="margin-2px">');
			print('<nobr><strong>');
			$date_epoch_time = strtotime($newest_entry['OriginalCreationDate']);
			$full_date = date("F d, Y", $date_epoch_time);
			print($full_date);
			print('</strong></nobr>');
			
			print(' &mdash; ');
			
			print('<em>');
			print('<a href="' . implode('/', $parent_codes) . '/' . $newest_entry['Code'] . '/">');
			
			#$parent_codes[] = $newest_entry['Code'];
			if($last_parent) {
				print($last_parent['Title']);
				print(' : ');
			}
			
			print($newest_entry['Title']);
			
			print('</a>');
			print('</em>');
			print('</div>');
			print('</div>');
			
			print('</div>');
			
			return TRUE;
		}
		
		public function Display_GetParentCodes($args) {
			$entry = $args['entry'];
			
			$parent_count = $entry['parents'] ? count($entry['parents']) : 0;
			
			$parent_codes = [];
			
			for($j = 0; $j - 1 < $parent_count; $j++) {
				$parent = $entry['parents'][$j];
				$parent_codes[] = $parent['Code'];
			}
			
			return $parent_codes;
		}
	}
	
?>