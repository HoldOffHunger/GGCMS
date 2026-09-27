<?php

	class Time {
		public $handler;
		
		public $time;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
			$this->time = time();
		}
	}

?>