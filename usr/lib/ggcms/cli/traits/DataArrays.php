<?php

	trait DataArrays {
		public function ArrayToHash($args) {
			$array = $args['array'];
			
			$hash = [];
			
			foreach ($array as $element) {
				$hash[$element] = TRUE;
			}
			
			return $hash;
		}
	}

?>