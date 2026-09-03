<?php

	trait SimpleAPI {
		public function SetAPI() {
			ggreqonce('classes/API/SearchEngine.php');
			
			$this->search_engine = new SearchEngine();
			
			return TRUE;
		}
	}
?>