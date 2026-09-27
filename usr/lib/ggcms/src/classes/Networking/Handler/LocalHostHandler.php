<?php

	class LocalHostHandler {
		public $handler;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
		}
		
		public function HandleLocalRequest() {
			$this->ConfigureServer();
			$this->DisplayVariables();
			
			return TRUE;
		}
		
		public function DisplayVariables() {
			if(array_key_exists('server', $_GET)) {
				print_r($_SERVER);
			}
			if(array_key_exists('get', $_GET)) {
				print_r($_GET);
			}
			if(array_key_exists('post', $_GET)) {
				print_r($_POST);
			}
			
			return TRUE;
		}
		
		public function ConfigureServer() {
			if(array_key_exists('domain', $_GET)) {
				$domain = $_GET['domain'];
			}
			
			return TRUE;
		}
	}

?>