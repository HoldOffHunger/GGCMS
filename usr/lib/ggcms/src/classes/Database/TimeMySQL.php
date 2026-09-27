<?php

	class TimeMySQL {
		public $handler;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
		}
		
		public function ConvertTimeFromEpochToMySQLFormat($epoch_time) {
			return date ("Y-m-d H:i:s", $epoch_time);
		}
	}

?>