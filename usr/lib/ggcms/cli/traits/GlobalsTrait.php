<?php

	trait GlobalsTrait {
		public function setGlobals() {
			confreq('clonefrom.php');
			
			$globals = new defaultglobals([]);
			
			return $this->globals = $globals;
		}
	}

?>