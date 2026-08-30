<?php

	trait FileSystem {
		public function getFilePermissions($args) {
			$path = $args['path'];
			
			$path_perms = substr(sprintf('%o',fileperms($path)), -4);
			
			return $path_perms;
		}
	}

?>