<?php

	class module_breadcrumbs extends module_spacing {
		public $that;
		public $action;
		public $title;
		public $record_list_count;
		public $sub_page;
		
		public function __construct($args) {
			$this->that = $args['that'];
			$this->action = $args['action'];
			$this->title = $args['title'];
			
			if(strlen($this->title) < 1) {
				$this->title = $this->that->entry['Title'];
			}
			
			$this->record_list_count = count($this->that->record_list);
			
			$this->sub_page = $args['subpage'];
		}
		
		public function Display() {
			$this->DisplayBlockStart();
			$this->DisplayAllBreadcrumbRecords();
			$this->DisplayBlockEnd();
			
			return TRUE;
		}
		
		public function DisplayAllBreadcrumbRecords() {
			$this->DisplayFirstBreadcrumbRecord();
			$this->DisplayIntermediateBreadcrumbRecords();
			$this->DisplayLastEntryBreadcrumbRecord();
			
			return TRUE;
		}
		
		public function DisplayFirstBreadcrumbRecord() {
			if($this->that->master_record && $this->that->entry['id'] !== $this->that->master_record['id']) {
				$this->DisplayImage(['record'=>$this->that->master_record]);
				
				if($this->record_list_count || $this->title) {
					print('<a href="' . $this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '">');
				}
				
				print($this->that->master_record['Title']);
				
				if($this->record_list_count || $this->title) {
					print('</a>');
				}
				
				$this->DisplaySeparator();
			}
			
			return TRUE;
		}
		
		public function DisplayImage($args) {
			$record = $args['record'];
			$images = $record['image'];
			
			if(!$images || !$images[0]) {
				if($record['association'] && $record['association'][0] && $record['association'][0]['entry']) {
					$images = $record['association'][0]['entry']['image'];
				}
			}
			
			if($images && $images[0]) {
				$image = $images[0];
				$directory = implode('/', str_split($image['FileDirectory']));
				
			#	print("\n\n<!--\n\n");
				
				#print_r($this->that->master_record);
			#	print_r($image);
				
		#		print("BT: Breadcrumbs!\n\n");
		#	print("-->\n\n");
			#	$alt = htmlentities($record['Title']);
				print('<img class="crumbs-icon" alt="" width="20" height="20" ');
				# An 18-pixel crumb: the icon, never the original, which can run to megabytes.
				$crumb_filename = $image['IconFileName'] ? $image['IconFileName'] : $image['FileName'];
				print('src="/image/' . $directory . '/' . $crumb_filename . '"');
			#	print('title="' . $alt . '" ');
			#	print('alt="' . $alt . '" ');
				print('>');
			}
			
			return TRUE;
		}
		
		public function DisplayIntermediateBreadcrumbRecords() {
			$link_list = '';
			
			for($i = 0; $i < $this->record_list_count; $i++) {
				$record = $this->that->record_list[$i];
				if($record['id'] !== $this->that->master_record['id']) {
					if(($record['id'] !== $this->that->entry['id'] && $record['id'] !== (int)$this->that->entry_unset['id']) || $this->title !== $this->that->entry['Title']) {
						$this->DisplayImage(['record'=>$record]);
						
						$link_list .= '/' . $record['Code'];
						
						print('<a href="' . $this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . $link_list . '/view.php');
						
						if($this->action) {
							print('?action=' . $this->action);
						} elseif($i === 0 || $record['Code'] === 'people') {
							print('?action=index');
						}
						
						print('">');
						
						print($record['Title']);
						
						print('</a>');
						
						$this->DisplaySeparator();
					}
				}
			}
			
			return TRUE;
		}
		
		public function DisplayLastEntryBreadcrumbRecord() {
			$title = $this->title;
			
			if(strlen($title) < 1) {
				$title = ' --- ';
			}
			
			$this->DisplayImage(['record'=>$this->that->entry]);
			
			if($this->sub_page) {
				print('<a href="');
				if($this->record_list_count === 1) {
					if($this->action) {
						print('view.php?action=' . $this->action);
					} else {
						print('view.php?action=index');
					}
				} else {
					print('.');
				}
				print('">');
			}
			
			print($this->sub_page ? $title : '<span class="crumbs-here" aria-current="page">' . $title . '</span>');
			
			if($this->sub_page) {
				print('</a>');
				
				$this->DisplaySeparator();
				
				print('<span class="crumbs-here" aria-current="page">' . $this->sub_page . '</span>');
			}
			
			return TRUE;
		}
		
		public function DisplayBlockStart() {
			print("\n\n");
			print('<nav class="crumbs" aria-label="Breadcrumb">');

			return TRUE;
		}

		public function DisplayBlockEnd() {
			print('</nav>');
			print("\n\n");

			return TRUE;
		}

		public function DisplaySeparator() {
			if($this->that->handler->script_format_lower === 'html') {
				print('<span class="crumbs-sep" aria-hidden="true">&rsaquo;</span>');
			} else {
				print(' >> ');
			}

			return TRUE;
		}
	}
?>