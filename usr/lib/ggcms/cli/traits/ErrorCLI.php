<?php

	trait ErrorCLI {
		public function DisplaySuccessFailResults($args) {
			$errors = $args['errors'];
			
			$error_count = count($errors);
			
			if($error_count === 0) {
				$this->successResults();
			} else {
				$this->failResults();
				print(' (' . implode(', ', $errors) . ')');
			}
			
			print(PHP_EOL);
			
			return TRUE;
		}
	}

?>