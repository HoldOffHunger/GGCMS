<?php

	class module_spacing {
		public function DisplayDoubleLineBreak () {
			$this->DisplayLineBreak();
			$this->DisplayLineBreak();
		}
		
		public function DisplayLineBreak () {
			print("\n");
		}
	}
?>