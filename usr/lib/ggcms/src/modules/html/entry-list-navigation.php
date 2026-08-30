<?php

	class module_entrylistnavigation extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			
			return TRUE;
		}
		
		public function Display($args) {
			$total_page_urls = '';
			
			for($i = 1; $i <= $this->that->total_pages; $i++) {
				if($i !== $this->that->page) {
					$total_page_urls .= ' <a href="view.php';
					$total_page_urls .= '?';
					
					if($this->that->Param('headless')) {
						$total_page_urls .= 'headless=1&';
					}
					
					$total_page_urls .= 'action=' . $this->that->desired_action . '&page=' . $i . '&perpage=' . $this->that->perpage;
					
					if($args['ignore_parent']) {
						$total_page_urls .= '&ignore_parent=' . $args['ignore_parent'];
					}
					
					if($args['parents']) {
						$total_page_urls .= '&parents=' . $args['parents'];
					}
					
					if($args['item_title']) {
						$total_page_urls .= '&item_title=' . $args['item_title'];
					}
					
					if($args['list_author']) {
						$total_page_urls .= '&list_author=' . $args['list_author'];
					}
					
					if($args['item_title']) {
						$total_page_urls .= '&stats_prefix=' . urlencode($args['stats_prefix']);
					}
					
					$total_page_urls .= '">';
				}
				
				$total_page_urls .= $i;
				
				if($i !== $this->that->page) {
					$total_page_urls .= '</a>' . ' ';
				}
			}
			
			print('<div class="horizontal-center width-95percent">');
			
			print('<div class="width-100percent border-2px background-color-gray13" title="Navigate to another page of results by using these links.">');
			print('<h3 class="padding-0px margin-5px horizontal-center vertical-center font-family-arial">');
			print($total_page_urls);
			print('</h3>');
			print('</div>');
			
			print('</div>');
			
			return TRUE;
		}
	}

?>