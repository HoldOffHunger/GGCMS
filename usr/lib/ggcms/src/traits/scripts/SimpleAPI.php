<?php

	trait SimpleAPI {
		public function SetAPI() {
			ggreq('classes/API/SearchEngine.php');
			
			$this->search_engine = new SearchEngine();
			
			return TRUE;
		}
	}
?>