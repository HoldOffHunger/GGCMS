<?php

	class module_entryassociated extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			$this->header = $args['header'];
			$this->entrysort = $args['entrysort'];
			$this->entrylist = $args['entrylist'];
			$this->iframe = $args['iframe'];
			
			return $this;
		}
		
		public function BackToTopLinkBox() {
			print('<div style="margin-right:50px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray13 float-right">');
			print('<span class="comments-link-box" style="font-family:arial, tahoma;margin:3px;padding:0px;display:inline-block;">');
			print('<strong>');
			
			print('<a href="#top">');
			print('<nobr>');
			print('Back to Top');
			print('</nobr>');
			print('</a>');
			
			print('</strong>');
			print('</span>');
			print('</div>');
			
			return TRUE;
		}
		
		public function DisplayLinkBox() {
			if($this->that->counts['associated'] === 0) {
				return FALSE;
			}
			
			print('<div style="margin-right:5px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray15 float-right">');
			print('<span class="associated-link-box" style="font-family:arial, tahoma;margin:3px;padding:0px;display:inline-block;">');
			print('<strong>');
			
			print('<a href="#associated">');
			print('Works (');
			print(number_format($this->that->counts['associated']));
			print(')');
			print('</a>');
			
			print('</strong>');
			print('</span>');
			print('</div>');
			
			return TRUE;
		}
		
		public function DisplayCustomLinkBox($args) {
			if($this->that->counts['associated'] === 0) {
				return FALSE;
			}
			
			if($args['header']) {
				$header = $args['header'];
			} else {
				$header = 'Works';
			}
			
			if($args['anchor']) {
				$anchor = $args['anchor'];
			} else {
				$anchor = 'associated';
			}
			
			print('<div style="margin-right:5px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray15 float-right">');
			print('<span class="associated-link-box" style="font-family:arial, tahoma;margin:3px;padding:0px;display:inline-block;">');
			print('<strong>');
			
			print('<a href="#' . $anchor . '">');
			print($header);
			if(!$args['hide_stats']) {
				print(' (');
				print(number_format($this->that->counts['associated']));
				print(')');
			}
			print('</a>');
			
			print('</strong>');
			print('</span>');
			print('</div>');
			
			return TRUE;
		}
		
		public function Display($args) {
			if($this->that->entry['associated'] && $this->that->counts['associated']) {
				if($args['header']) {
					$header_text = $args['header'];
				} else {
					$header_text = 'Works';
				}
				
						// Quote Header
					
					// -------------------------------------------------------------
				
				if($args['anchor']) {
					$anchor = $args['anchor'];
				} else {
					$anchor = 'associated';
				}
				
				print('<a name="' . $anchor . '"></a>');
				
				print('<center>');
				print('<div class="horizontal-center width-95percent">');
				print('<div class="border-2px background-color-gray15 margin-5px float-left">');
				print('<h2 class="horizontal-left margin-5px font-family-arial">');
				print($header_text);
				print('</h2>');
				print('</div>');
				print('</div>');
				print('</center>');
				
				$this->BackToTopLinkBox();
				
						// Finish Textbody Header
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
				
						// Display Works Count
					
					// -------------------------------------------------------------
				
			#	print("BT:");
			#	print_r($this->that->associated_record_stats);
				
						// Child Record Counts
					
					// -------------------------------------------------------------
			
				if(!$args['hide_stats']) {
					print('<center>');
					print('<div class="horizontal-center width-70percent">');
					print('<div class="border-2px background-color-gray15 margin-5px float-left" title="');
					
					print(' (Last Updated: ');
					$date_epoch_time = strtotime($this->that->associated_record_stats['LastModificationDate']);
					$full_date = date("F d, Y; H:i:s", $date_epoch_time);
					print($full_date);
					print('.)');
					
					print('">');
					
					if($args['creation_type']) {
						$creation_type_text = $args['creation_type'];
					} else {
						$creation_type_text = 'documents';
					}
					
					print('<strong>');
					print('<p class="horizontal-left margin-5px font-family-tahoma">');
					print('This person has authored ' . number_format($this->that->associated_record_stats['AssociatedRecordCount']) . ' ' . $creation_type_text);
					if($this->that->associated_record_stats['AssociatedWordCount'] !== 0 || $this->that->associated_record_stats['AssociatedCharacterCount'] !== 0) {
						print(', with ' . number_format($this->that->associated_record_stats['AssociatedWordCount']) . ' words or ' . number_format($this->that->associated_record_stats['AssociatedCharacterCount']) . ' characters.');
					} else {
						print('.');
					}
					print('</p>');
					print('</strong>');
					
					print('</div>');
					print('</div>');
					print('</center>');
				}
				
						// Finish Textbody Header
					
					// -------------------------------------------------------------
										
				print('<div class="clear-float"></div>');
				
						// Display Works
					
					// -------------------------------------------------------------
				
				if($this->iframe !== 'always' && $this->that->counts['associated'] < $this->that->maxAssociated() - 1) {
					print('<center>');
					print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-90percent">');
					print('<div class="background-color-gray13 margin-5px horizontal-center">');
					
					$associated_display = $this->entrysort->Sort([
						'entries'=>$this->that->entry['associated'],
					]);
					
					$entry_list_args = $args;
					$entry_list_args['children'] = $associated_display;
					
					$this->entrylist->Display($entry_list_args);
					
					print('</div>');
					print('</div>');
				} else {
					$record_list_count = count($this->that->record_list);
					$url = '';
					for($i = 0; $i < $record_list_count; $i++) {
						$record = $this->that->record_list[$i];
						$url .= '/' . $record['Code'];
					}
					
					$url .= '/view.php?action=browseAssociated';
					
					if($args['ignore_parent']) {
						$url .= '&ignore_parent=' . $args['ignore_parent'];
					}
					
					if($args['parents']) {
						$url .= '&parents=' . $args['parents'];
					}
					
					if($args['item_title']) {
						$url .= '&item_title=' . $args['item_title'];
					}
					
					if($args['list_author']) {
						$url .= '&list_author=' . $args['list_author'];
					}
					
					if($args['item_title']) {
						$url .= '&stats_prefix=' . urlencode($args['stats_prefix']);
					}
					
					print('
<script>
  function resizeIframe(obj) {
    obj.style.height = "0px";
    var newheight = obj.contentWindow.document.documentElement.scrollHeight;
    newheight += 5;	// definitively eliminate scrollbar
    console.log(newheight);
    if(newheight > 900) {
    	newheight = 900;
    }
    obj.style.height = newheight + \'px\';
  }
</script>
					');
					
					print('<center>');
					print('<iframe style="width:95%;height:200px;" onload="resizeIframe(this)" src="' . $url . '">');
					print('</iframe>');
					print('</center>');
				}
			}
			
			return TRUE;
		}
		
	}

?>