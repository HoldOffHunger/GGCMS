<?php

	class module_imagelist extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			
			return $this;
		}
		
		public function DisplayLinkBox() {
			if($this->that->counts['image'] === 0) {
				return FALSE;
			}
			
			print('<div style="margin-right:5px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray15 float-right">');
			print('<span class="image-link-box" style="font-family:arial, tahoma;margin:3px;padding:0px;display:inline-block;">');
			print('<strong>');
			
			print('<a href="#image">');
			print('Images (');
			print(number_format($this->that->counts['image']));
			print(')');
			print('</a>');
			
			print('</strong>');
			print('</span>');
			print('</div>');
			
			return TRUE;
		}
		
		public function Display($args) {
			print('<a name="image"></a>');
			
			if($this->that->counts['image'] === 0) {
				return FALSE;
			}
			
			$header = $args['header'];
			
			if(!$header) {
				$header = 'Image Gallery';
			}
			
			print('<center>');
			print('<div class="horizontal-center width-95percent">');
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<h2 class="horizontal-left margin-5px font-family-arial">');
			print($header);
			print('</h2>');
			print('</div>');
			print('</div>');
		
			print('<div class=\'clear-float\'></div>');
			
			print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-90percent">');
		
			$this->DisplayFloat();
			
			print('<div class=\'clear-float\'></div>');
			
			print('</div>');
			
			print('</center>');
		}
		
		public function DisplayFloat() {
			if(!$this->canDisplay()) {
				return FALSE;
			}
			
			$images = $this->that->children;
			
			#print("<PRE>");
			#print_r($images);
			
			for($i = 0; $i < count($images); $i++) {
				$entry = $images[$i];
				$assignment = $images[$i]['assignment'][0];
				$image = $images[$i]['image_base'];
				print('<div style="height:200px;" class="border-2px background-color-gray15 margin-5px float-left" ');
				print('title="');
				print($image['Title']);
				print('">');
				print('<div class="border-2px background-color-gray15 margin-5px float-left">');
				print('<div class="height-100px width-100px background-color-gray0 horizontal-center">');
				print('<div class="horizontal-center vertical-specialcenter">');
				print('<a href="' . $this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/image/' . implode('/', str_split($image['FileDirectory'])) . '/' . $image['FileName'] . '"');
				print(' target="_blank"');
				print('>');
				print('<img width="');
				print(ceil($image['IconPixelWidth'] / 2));
				print('" height="');
				print(ceil($image['IconPixelHeight'] / 2));
				print('" src="');
				print($this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
				print('/image/');
				print(implode('/', str_split($image['FileDirectory'])));
				print('/');
				print($image['IconFileName']);
				print('">');
				print('</a>');
				print('</div>');
				print('</div>');
				
				$child_title = $entry['Title'];
				
				if(strlen($entry['Subtitle']) !== 0) {
					$child_title .= ': '. $entry['Subtitle'];
				}
				
				print('<div style="max-width:100px;">');
				print('From: <a href="/?id=' . $assignment['id'] . '">');
				print($child_title);
				print('</a>');
				print('</div>');
				
				print('</div>');
				print('</div>');
			}
			
			return TRUE;
		}
		
		public function canDisplay() {
			if($this->that->entry['image']) {
				if($this->that->counts['image'] !== 0) {
					print('<a name="image"></a>');
					
					if($this->that->mobile_friendly) {
						$this->NoDisplayForMobile();
						return FALSE;
					}
					
					return TRUE;
				}
				
				return FALSE;
			}
			
			return FALSE;
		}
		
		public function NoDisplayForMobile() {
			print('<div class="border-2px background-color-gray15 margin-5px float-left background-color-gray13">');
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<div class="horizontal-center">');
			
			print('<p class="font-family-arial margin-5px">');
			print('<a href="view.php">');
			print('Images available at the non-mobile-friendly site.');
			print('</a>');
			print('</p>');
					
			print('</div>');
			print('</div>');
			print('</div>');
			
			return TRUE;
		}
	}

?>