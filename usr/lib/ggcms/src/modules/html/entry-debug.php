<?php

	class module_entrydebug extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			
			return TRUE;
		}
		
		public function Debug($args) {
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