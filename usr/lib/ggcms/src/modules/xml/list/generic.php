<?php

	class module_genericlist extends module_spacing {
				// Display List
			
			// -------------------------------------------------------------
			
		public function Display($args) {
			$options = $args['options'];
			$list = $args['list'];
			
			$human_readable = $options['humanreadable'];
			
			$this->DisplayList([
				'list'=>$list,
				'humanreadable'=>$human_readable,
			]);
		}
		
		public function DisplayList($args) {
			$list = $args['list'];
			$human_readable = $args['humanreadable'];
			
			foreach($list as $list_key => $list_items) {
				$this->DisplayList_DisplayItem([
					'listitems'=>$list_items,
					'humanreadable'=>$human_readable,
				]);
			}
		}
		
		public function DisplayList_DisplayItem($args) {
			$list_items = $args['listitems'];
			$human_readable = $args['humanreadable'];
			
			foreach($list_items as $list_key => $list_item) {
				if(is_array($list_item)) {
					if(!is_int($list_key)) {
						print('<' . $list_key . '>');
						
						if($human_readable) {
							$this->DisplayDoubleLineBreak();
						}
					}
					
					$this->DisplayList_DisplayItem([
						'listitems'=>$list_item,
						'humanreadable'=>$human_readable,
					]);
					
					if(!is_int($list_key)) {
						
						$list_key_usable = explode(' ', $list_key)[0];
						
						print('</' . $list_key_usable . '>');
						
						if($human_readable) {
							$this->DisplayDoubleLineBreak();
						}
					}
				} else {

						/*
							An integer key cannot be an XML element name -- names may
							not begin with a digit -- and this branch wrapped one
							anyway, emitting <0>1</0> and making the document invalid
							at the first entry.  Fourteen of seventeen sitemaps failed
							to parse on exactly this.  The array branch above has
							always skipped integer keys; this one did not.

							Skipped rather than unwrapped, because bare text inside
							<url> is well formed but not a valid sitemap either.
							NOTE: something upstream is putting a scalar under an
							integer key where 'loc' belongs; this makes the output
							valid but does not explain that.
						*/

					if(is_int($list_key)) {
						continue;
					}

					print('<' . $list_key  . '>' . $list_item . '</' . $list_key . '>');
					
					if($human_readable) {
						$this->DisplayDoubleLineBreak();
					}
				}
			}
		}
			
				// Antiquated Functions for HTML backward compatibility
			
			// -------------------------------------------------------------
			
		public function Display_TableBody($args) {
		}
		
		public function Display_CellContents($args) {
		}
		
		public function Display_CellStart($args) {
		}
		
		public function Display_CellEnd($args) {
		}
		
		public function Display_RowStart($args) {
		}
		
		public function Display_RowEnd($args) {
		}
		
		public function Display_TableBodyHeader($args) {
		}
		
		public function Display_TableStart($args) {
		}
		
		public function Display_TableEnd($args) {
		}
	}
?>