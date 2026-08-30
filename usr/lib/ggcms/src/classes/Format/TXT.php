<?php

	class TXT extends AbstractBaseFormat {
			// TXT MimeType
			// -----------------------------------------------
		
		public function MimeType() {
			return 'text/plain';
		}
		
			// Display TXT
			// -----------------------------------------------
		
		public function Display() {
			if(!$this->RunScript()) {
				return FALSE;
			}
			
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
			
			$this->script->DisplayTemplates();
			
			return TRUE;
		}
	}

?>