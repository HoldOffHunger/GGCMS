<?php

	class module_debug extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			
			return $this;
		}
		
		public function DisplayBasicRecords($args) {
			print("view.php script, display.php template<BR><BR>");
			print("<PRE>RECORD LIST:");
			print_r($this->that->record_list);
			print("\n\nMASTER RECORD:\n\n");
			print_r($this->that->master_record);
			print("\n\nPARENT:\n\n");
			print_r($this->that->parent);
			print("\n\nENTRY:\n\n");
			print_r($this->that->entry);
			print("\n\nCHILDREN:\n\n");
			print_r($this->that->children);
			print("</PRE>");
			
			return TRUE;
		}
	}

?>