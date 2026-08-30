<?php

	trait VersionNumber {
		public function validateVersionNumber($args) {
			$string = $args['string'];
			
			$string_pieces = explode('.', $string);
			$string_pieces_count = count($string_pieces);
			
			for($i = 0; $i < $string_pieces_count; $i++) {
				$string_piece = $string_pieces[$i];
				
				if(!is_numeric($string_piece)) {
					$i = $string_pieces_count;
					return FALSE;
				}
			}
			
			return TRUE;
		}
	}

?>