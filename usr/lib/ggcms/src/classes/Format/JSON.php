<?php

	class JSON extends AbstractBaseFormat {
		public function MimeType() {
			return 'application/json';
		}
		
			// Display JSON
			// -----------------------------------------------
		
		public function Display() {
			if(!$this->RunScript()) {
				return FALSE;
			}
			
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
			
			$this->script->DisplayTemplates();
			
			return print(json_encode($this->script->record_to_use));
		}
	}
	
?>