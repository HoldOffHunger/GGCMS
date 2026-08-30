<?php

	class HTML_Navigation {
		public $domain_object;
		public function __construct () {
			$this->domain_object = $args['domainobject'];
			$this->handler = $args['handler'];
			
			return $this;
		}
		
		public function Display() {
			return true;
		}
		
		public function Website_Options() {
			return [
				[
					'label'=>'Home',
					'id'=>'home',
					'url'=>'',
					'mouseover'=>'the home',
				],
				[
					'label'=>'Blogs',
					'id'=>'blogs',
					'url'=>'blogs.php',
					'mouseover'=>'bloggiest of blogs',
				],
				[
					'label'=>'Video',
					'id'=>'video',
					'url'=>'video.php',
					'mouseover'=>'all of the revolutionary videos you could possibly handle',
				],
			];
		}
		
			// HTML Spacing
			// -----------------------------------------------
		
		public function DisplayDoubleReturns() {
			print("\n");
			print("\n");
		}
	}

?>