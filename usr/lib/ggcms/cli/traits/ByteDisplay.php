<?php

	trait ByteDisplay {
		public function formatBytes($args) {
			$number = $args['number'];
			$number_width = array_key_exists('number_width', $args) ? $args['number'] : 3;
			
			$number_size = strlen($number);
			
			$number_pieces = explode($number, $number_width);
			
			print_r($number_pieces);
			
		//	print("NUMBER!" . $number . "|");
			
			return $number;
		}
	}

?>