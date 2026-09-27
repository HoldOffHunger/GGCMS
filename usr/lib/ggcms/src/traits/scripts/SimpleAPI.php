<?php

	trait SimpleAPI {
		public $search_engine;
		
		public function SetAPI() {
			ggreq('classes/API/SearchEngine.php');
			
			$this->search_engine = new SearchEngine();
			
			return TRUE;
		}
	}
?>