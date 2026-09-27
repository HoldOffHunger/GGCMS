<?php

	class Error404Redirect {
		public $handler;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
		}
	}

?>