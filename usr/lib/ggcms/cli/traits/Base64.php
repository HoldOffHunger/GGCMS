<?php

	trait Base64 {
		public function validateBase64($args) {
			$text = $args['text'];
			
			if (base64_encode(base64_decode($text, TRUE)) === $text) {
				return TRUE;
			}
			
			return FALSE;
		}
	}

?>