<?php

	class AdminTools {
		public function ShowMemory() {
			print('MEMORY : ' . memory_get_usage());
		}
	}

?>