<?php

	class CSS extends AbstractBaseFormat {
		public function MimeType() {
			return 'text/css';
		}
		
		public function __construct($args) {
			$this->SetArgs($args);
			
			if($this->script_extension === 'css') {
				if($args['firstcall']) {
					$this->Construct_Requires();
				}
				
				$constructor_args = $this->SetScriptConstructorArgs($args);
				
				ggreqonce('scripts/style.php');
				$this->script = new style($constructor_args);
			}
			
			return $this;
		}
		
			// Render CSS Objects
			// -----------------------------------------------
			
		public function Display() {
			$this->HandleHTTPHeaders();
			
			return $this->RunScript();
		}
		
			// HTML Spacing
			// -----------------------------------------------
		
		public function DisplaySingleReturn() {
			print("\n");
		}
		
		public function DisplayDoubleReturns() {
			print("\n\n");
		}
		
		public function OneCSSFilePerPage() {
			return 1;
		}
	}

?>