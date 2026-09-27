<?php

	class module_imagelistnavigation extends module_spacing {
		public $that;
		
		public function __construct($args) {
			$this->that = $args['that'];
		}
		
		public function Display($args) {
			$total_page_urls = '';
			
			for($i = 1; $i <= $this->that->total_pages; $i++) {
				if($i !== $this->that->page) {
					$total_page_urls .= ' <a href="image.php';
					$total_page_urls .= '?';
					
					if($this->that->Param('headless')) {
						$total_page_urls .= 'headless=1&';
					}
					
					$total_page_urls .= 'action=' . $this->that->desired_action . '&page=' . $i . '&perpage=' . $this->that->perpage . '">';
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