<?php

	trait SimpleAPI {
		public function SetAPI() {
			classreq('classes/API/SearchEngine.php');
			
			$this->search_engine = new SearchEngine();
			
			return TRUE;
		}
	}
?>