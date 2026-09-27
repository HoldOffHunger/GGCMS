<?php

	class Time {
		public $time;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
			$this->time = time();
		}
	}

?>