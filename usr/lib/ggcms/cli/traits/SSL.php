<?php

	trait SSL {
		public function extractCertPemContents($args) {
			$file_location = $args['file_location'];

			$cert_pem_contents = file($file_location);
			
			/*
							Example Format -->
			
				[0] =>     -----BEGIN CERTIFICATE-----
				[1-28] =>  [key]
				[29] =>    -----END CERTIFICATE-----
			*/
			
			$cert_pem_contents_length = count($cert_pem_contents);
			
			$begin_cert = trim($cert_pem_contents[0]);
			$end_cert = trim($cert_pem_contents[$cert_pem_contents_length - 1]);
			
			unset($cert_pem_contents[0]);
			unset($cert_pem_contents[$cert_pem_contents_length - 1]);
			
			foreach($cert_pem_contents as &$cert_pem_key) {
				$cert_pem_key = rtrim($cert_pem_key);
			}
			
			$cert_pem_key = implode('', $cert_pem_contents);
			
			return [
				'pem_key'=>$cert_pem_key,
				'begin_cert'=>$begin_cert,
				'end_cert'=>$end_cert,
			];
		}
		
		public function extractChainPemContents($args) {
			$file_location = $args['file_location'];
			
			if(array_key_exists('begin_marker', $args)) {
				$begin_marker = $args['begin_marker'];
			} else {
				$begin_marker = '-----BEGIN CERTIFICATE-----';
			}
			
			if(array_key_exists('end_marker', $args)) {
				$end_marker = $args['end_marker'];
			} else {
				$end_marker = '-----END CERTIFICATE-----';
			}

			$chain_pem_contents = file($file_location);
			$chain_pem_contents_count = count($chain_pem_contents);
			
			$start_keys = [];
			$end_keys = [];
			$cert_keys = [
		//		'cert1'=>'',
		//		'cert2'=>'',
			];
			
			$key_completed = FALSE;
			$cert_index = 1;
			
			for($i = 0; $i < $chain_pem_contents_count; $i++) {
				$chain_pem_line = rtrim($chain_pem_contents[$i]);
				
				if($chain_pem_line === $begin_marker) {
					$start_keys[] = $chain_pem_line;
				} elseif($chain_pem_line === $end_marker) {
					$end_keys[] = $chain_pem_line;
					
					$key_completed = TRUE;
				} elseif(strlen($chain_pem_line) !== 0) {
					if($key_completed) {
						$cert_index++;
						$key_completed = FALSE;
					}
					$cert_pem_line_key = 'cert' . $cert_index;
					if(!array_key_exists($cert_pem_line_key, $cert_keys)) {
						$cert_keys[$cert_pem_line_key] = '';
					}
					$cert_keys[$cert_pem_line_key] .= $chain_pem_line;
					
				/*	if(count($end_keys) === 0) {
						$cert_keys['cert1'] .= $chain_pem_line;
					} else {
						$cert_keys['cert2'] .= $chain_pem_line;
					}*/
				}
			}
			
			return [
				'start_keys'=>$start_keys,
				'end_keys'=>$end_keys,
				'cert_keys'=>$cert_keys,
			];
		}
		
		public function validateSSLCertPem($args) {
			$file_location = $args['file_location'];
			
			$errors = [];
			
			$cert_pem_contents = $this->extractCertPemContents(['file_location'=>$file_location]);
			
			if(strlen($cert_pem_contents['pem_key']) < 1) {
				$errors[] = 'cert key is empty';
			}
			
			if(!$this->validateBase64(['text'=>$cert_pem_contents['pem_key']])) {
				$errors[] = 'cert key is not base64-valid';
			}
			
			if($cert_pem_contents['begin_cert'] !== '-----BEGIN CERTIFICATE-----') {
				$errors[] = 'cert begin key is invalid';
			}
			
			if($cert_pem_contents['end_cert'] !== '-----END CERTIFICATE-----') {
				$errors[] = 'cert end key is invalid';
			}
			
			return $errors;
		}
		
		public function validateSSLChainPem($args) {
			return $this->validate_certkeys($args);
		}
		
		public function validate_certkeys($args) {
			$file_location = $args['file_location'];
			
			if(array_key_exists('begin_marker', $args)) {
				$begin_marker = $args['begin_marker'];
			} else {
				$begin_marker = '-----BEGIN CERTIFICATE-----';
			}
			
			if(array_key_exists('end_marker', $args)) {
				$end_marker = $args['end_marker'];
			} else {
				$end_marker = '-----END CERTIFICATE-----';
			}
			
			$errors = [];
			
			$chain_pem_contents = $this->extractChainPemContents($args);
			
			$start_keys = $chain_pem_contents['start_keys'];
			$end_keys = $chain_pem_contents['end_keys'];
			$cert_keys = $chain_pem_contents['cert_keys'];
			
			$start_keys_count = count($start_keys);
			$end_keys_count = count($end_keys);
			$cert_keys_count = count($cert_keys);
			
			if($start_keys_count === 0) {
				$errors[] = 'insufficient start key count';
			} else {
				$errors = $this->validate_key_markers(['marker'=>$begin_marker, 'keys'=>$start_keys, 'errors'=>$errors]);
			}
			
			if($end_keys_count === 0) {
				$errors[] = 'insufficient end key count';
			} else {
				$errors = $this->validate_key_markers(['marker'=>$end_marker, 'keys'=>$end_keys, 'errors'=>$errors]);
			}
			
			if($cert_keys_count === 0) {
				$errors[] = 'insufficient cert key count';
			} else {
				$errors = $this->validate_cert_key_length(['key_type'=>'cert', 'cert_keys'=>$cert_keys, 'errors'=>$errors]);
				$errors = $this->validate_base64(['cert_keys'=>$cert_keys, 'errors'=>$errors]);
			}
			
			return $errors;
		}
		
		public function validate_key_markers($args) {
			$errors = $args['errors'];
			$marker = $args['marker'];
			$keys = $args['keys'];
			
			foreach($keys as $index => $key) {
				if(strlen($key) === 0) {
					$errors[] = 'empty cert marker: ' . $marker;
				} else {
					if($key !== $marker) {
						$errors[] = 'bad cert marker: ' . $marker;
					}
				}
			}
			
			return $errors;
		}
		
		public function validate_base64($args) {
			$cert_keys = $args['cert_keys'];
			$errors = $args['errors'];
			
			foreach($cert_keys as $cert_key => $cert_value) {
				if(!$this->validateBase64(['text'=>$cert_value])) {
					$errors[] = $cert_key . ' base64-invalid';
				}
			}
			
			return $errors;
		}
		
		public function validate_cert_key_length($args) {
			$key_type = $args['key_type'];
			$cert_keys = $args['cert_keys'];
			$errors = $args['errors'];
			
			foreach($cert_keys as $cert_key => $cert_value) {
				if(strlen($cert_value) === 0) {
					$errors[] = $cert_key . ' ' . $key_type . ' key is empty';
				}
			}
			
			return $errors;
		}
		
		public function validateSSLFullChainPem($args) {
			return $this->validate_certkeys($args);
		}
		
		public function validateSSLPrivKeyPem($args) {
			$args['begin_marker'] = '-----BEGIN PRIVATE KEY-----';
			$args['end_marker'] = '-----END PRIVATE KEY-----';
			
			return $this->validate_certkeys($args);
		}
		
		public function validateLetsEncryptRenewal($args) {
			$errors = [];
			
			$lets_encrypt_renewal_contents = $this->getLetsEncryptRenewal($args);
			
			#print(PHP_EOL . "BT validate LETS ENC!!!!" . PHP_EOL . PHP_EOL);
			
		#	print_r($lets_encrypt_renewal_contents);
			
			/*
			
				BT validate LETS ENC!!!!
				
				Array
				(
				    [version] => 0.40.0
				    [archive_dir] => /etc/letsencrypt/archive/holdoffhunger.com
				    [cert] => /etc/letsencrypt/live/holdoffhunger.com/cert.pem
				    [privkey] => /etc/letsencrypt/live/holdoffhunger.com/privkey.pem
				    [chain] => /etc/letsencrypt/live/holdoffhunger.com/chain.pem
				    [fullchain] => /etc/letsencrypt/live/holdoffhunger.com/fullchain.pem
				    [renewalparams] => Array
				        (
				            [account] => hex
				            [authenticator] => standalone
				            [server] => urlvalue
				        )
				
				)
				
			*/
			
			$standard_keys = [
				'version',
				'archive_dir',
				'cert',
				'privkey',
				'chain',
				'fullchain',
				'renewalparams',
			];
			
			$renewal_keys = [
				'account',
				'authenticator',
				'server',
			];
			
			$standard_isversion_keys = [
				'version',
			];
			
			$standard_isfile_keys = [
					# this is not created until after first renewal?
			#	'archive_dir',
				'cert',
				'privkey',
				'chain',
				'fullchain',
			];
			
			$standard_isarray_keys = [
				'renewalparams',
			];
			
			foreach($standard_keys as $standard_key) {
				if(!array_key_exists($standard_key, $lets_encrypt_renewal_contents)) {
					$errors[] = 'missing standard key, ' . $standard_key;
				}
			}
			
			foreach($renewal_keys as $renewal_key) {
				if(!array_key_exists($renewal_key, $lets_encrypt_renewal_contents['renewalparams'])) {
					$errors[] = 'missing renewal key, ' . $renewal_key;
				}
			}
			
			foreach($standard_isversion_keys as $standard_isversion_key) {
				if(!$this->validateVersionNumber(['string'=>$lets_encrypt_renewal_contents[$standard_isversion_key]])) {
					$errors[] = 'standard isversion key, ' . $standard_isversion_key . '[' . $lets_encrypt_renewal_contents[$standard_isversion_key] . ']';
				}
			}
			
			foreach($standard_isfile_keys as $standard_isfile_key) {
				if(!is_file($lets_encrypt_renewal_contents[$standard_isfile_key])) {
					$errors[] = 'standard isfile key, ' . $standard_isfile_key;
				}
			}
			
			foreach($standard_isarray_keys as $standard_isarray_key) {
				if(!is_array($lets_encrypt_renewal_contents[$standard_isarray_key])) {
					$errors[] = 'standard isarray key, ' . $standard_isarray_key;
				}
			}
			
			if(!ctype_xdigit($lets_encrypt_renewal_contents['renewalparams']['account'])) {
				$errors[] = 'non-Hex value in renewal hash key `account`';
			}
			
			if(strlen($lets_encrypt_renewal_contents['renewalparams']['authenticator']) === 0) {
				$errors[] = 'empty string for renewal hash key `authenticator`';
			}
			
			if(!filter_var($lets_encrypt_renewal_contents['renewalparams']['server'], FILTER_VALIDATE_URL)) {
				$errors[] = 'invalid url for renewal hash key `server`';
			}
			
			return $errors;
		}
		
		public function getLetsEncryptRenewal($args) {
			$file_location = $args['file_location'];
			
			$lets_encrypt_renewal = [];
			
			$param_section = '';
			
			$lets_encrypt_renewal_file = file($file_location);
			
			foreach($lets_encrypt_renewal_file as $lets_encrypt_line) {
				$lets_encrypt_line = rtrim($lets_encrypt_line);
				
				if(strlen($lets_encrypt_line) > 2) {
					if($lets_encrypt_line[0] !== '#') {
						if($lets_encrypt_line[0] === '[' && $lets_encrypt_line[1]) {
							$param_section = substr($lets_encrypt_line, 1, -1);
							$lets_encrypt_renewal[$param_section] = [];
						} else {
							$line_pieces = $this->getLetsEncryptRenewal_splitLine(['line'=>$lets_encrypt_line]);
							
							if(strlen($param_section) === 0) {
								$lets_encrypt_renewal[$line_pieces['key']] = $line_pieces['value'];
							} else {
								$lets_encrypt_renewal[$param_section][$line_pieces['key']] = $line_pieces['value'];
							}
						}
					}
				}
			}
			
		#	print_r($lets_encrypt_renewal);
			
			return $lets_encrypt_renewal;
		}
		
		public function getLetsEncryptRenewal_splitLine($args) {
			$line = $args['line'];
			
			#print(PHP_EOL . PHP_EOL . "LINE!" . $line . PHP_EOL . PHP_EOL);
			$line_pieces = explode(' = ', $line);
			
			return [
				'key'=>$line_pieces[0],
				'value'=>$line_pieces[1],
			];
		}
		
		/*
		
		
				Array
				(
				    [ServerAdmin] => holdoffhunger@gmail.com		// I'm on parole!  You can't stop me!
				    [ServerName] => holdoffhunger.com
				    [ServerAlias] => www.holdoffhunger.com
				    [DocumentRoot] => /var/www/html
				    [ErrorLog] => ${APACHE_LOG_DIR}/error.log
				    [CustomLog] => ${APACHE_LOG_DIR}/access.log
				)

		
		*/
		
		public function validateLetsEncryptRenewal80_formatCheck($args) {
			$file_location = $args['file_location'];
			
			$errors = [];
			
			#print(PHP_EOL . PHP_EOL . "BT: validateLetsEncryptRenewal80_formatCheck!!!!" . PHP_EOL . PHP_EOL);
			
			$lets_encrypt_renewal_80_config = $this->getLetsEncryptRenewal80($args);
			
			if($lets_encrypt_renewal_80_config['ServerAdmin'] !== $this->globals->AdminEmailAddress()) {
				$errors[] = 'invalid 80 server admin email is invalid';
			}
			
			if($lets_encrypt_renewal_80_config['ServerName'] !== $this->domain) {
				$errors[] = 'invalid 80 servername'; 
			}
			
			if($lets_encrypt_renewal_80_config['ServerAlias'] !== 'www.' . $this->domain) {
				$errors[] = 'invalid 80 missing www server alias'; 
			}
			
			$is_dir_folder_keys = [
				'DocumentRoot',
			];
			
			foreach($is_dir_folder_keys as $is_dir_folder_key) {
				if(!is_dir($lets_encrypt_renewal_80_config[$is_dir_folder_key])) {
					$errors[] = 'bad 80 directory, ' . $is_dir_folder_key . ':' . $lets_encrypt_renewal_80_config[$is_dir_folder_key]; 
				}
			}
			
			$is_file_folder_keys = [
				'ErrorLog',
				'CustomLog',
			];
			
			foreach($is_file_folder_keys as $is_file_folder_key) {
				if(!is_file($lets_encrypt_renewal_80_config[$is_file_folder_key])) {
					$errors[] = 'bad 80 file, ' . $is_file_folder_key . ':' . $lets_encrypt_renewal_80_config[$is_file_folder_key]; 
				}
			}
			
			return $errors;
		}
		
		/*
		
		
				SSL Cert Checks, Apache Available File, 443, Format Check: BT: 443 config!!!Array
				(
				    [ServerAdmin] => holdoffhunger@gmail.com
				    [ServerName] => holdoffhunger.com
				    [ServerAlias] => www.holdoffhunger.com
				    [DocumentRoot] => /var/www/html
				    [ErrorLog] => /var/log/apache2/error.log
				    [CustomLog] => /var/log/apache2/access.log
				    [SSLCertificateFile] => /etc/letsencrypt/live/holdoffhunger.com/fullchain.pem
				    [SSLCertificateKeyFile] => /etc/letsencrypt/live/holdoffhunger.com/privkey.pem
				    [Include] => /etc/letsencrypt/options-ssl-apache.conf
				)

		
		*/
		
		public function validateLetsEncryptRenewal443_formatCheck($args) {
			$file_location = $args['file_location'];
			
			$errors = [];
			
			$lets_encrypt_renewal_443_config = $this->getLetsEncryptRenewal443($args);
			
			if($lets_encrypt_renewal_443_config['ServerAdmin'] !== $this->globals->AdminEmailAddress()) {
				$errors[] = 'invalid 443 server admin email is invalid';
			}
			
			if($lets_encrypt_renewal_443_config['ServerName'] !== $this->domain) {
				$errors[] = 'invalid 443 servername'; 
			}
			
			if($lets_encrypt_renewal_443_config['ServerAlias'] !== 'www.' . $this->domain) {
				$errors[] = 'invalid 443 missing www server alias'; 
			}
			
			$is_dir_folder_keys = [
				'DocumentRoot',
			];
			
			foreach($is_dir_folder_keys as $is_dir_folder_key) {
				if(!is_dir($lets_encrypt_renewal_443_config[$is_dir_folder_key])) {
					$errors[] = 'bad 443 directory, ' . $is_dir_folder_key . ':' . $lets_encrypt_renewal_443_config[$is_dir_folder_key]; 
				}
			}
			
			$is_file_folder_keys = [
				'ErrorLog',
				'CustomLog',
				'SSLCertificateFile',
				'SSLCertificateKeyFile',
				'Include',
			];
			
			foreach($is_file_folder_keys as $is_file_folder_key) {
				if(!is_file($lets_encrypt_renewal_443_config[$is_file_folder_key])) {
					$errors[] = 'bad 443 file, ' . $is_file_folder_key . ':' . $lets_encrypt_renewal_443_config[$is_file_folder_key]; 
				}
			}
			
		#	print("BT: 443 config!!!");
		#	print_r($lets_encrypt_renewal_443_config);
			
			return $errors;
		}
		
		public function getLetsEncryptRenewal443($args) {
			$file_location = $args['file_location'];
			
			$lets_encrypt_renewal_443_contents = file($file_location);
			
			$lets_encrypt_renewal_443_contents[0] = str_replace(' mod_ssl.c', '', $lets_encrypt_renewal_443_contents[0]);
			$lets_encrypt_renewal_443_contents[1] = str_replace(' *:443', '', $lets_encrypt_renewal_443_contents[1]);
			
			$xml = implode('', $lets_encrypt_renewal_443_contents);
			
			$xml = str_replace('${APACHE_LOG_DIR}', '/var/log/apache2', $xml);
			
			return $this->getLetsEncryptRenewalFormatting(['xml'=>$xml]);
		}
		
		public function getLetsEncryptRenewal80($args) {
			$file_location = $args['file_location'];
			
			$lets_encrypt_renewal_80_contents = file($file_location);
			
			$lets_encrypt_renewal_80_contents[0] = str_replace(' *:80', '', $lets_encrypt_renewal_80_contents[0]);
			
			$xml = implode('', $lets_encrypt_renewal_80_contents);
			$xml = str_replace('${APACHE_LOG_DIR}', '/var/log/apache2', $xml);
			
			return $this->getLetsEncryptRenewalFormatting(['xml'=>$xml]);
		}
		
		public function getLetsEncryptRenewalFormatting($args) {
			$xml = $args['xml'];
			
			$lets_encrypt_hash = [];
			
			$dom = new DOMDocument;
			$dom->loadXML($xml);
			
			$nodes = $dom->getElementsByTagName('VirtualHost');
			$node = $nodes[0];
			
			$node_lines = explode(PHP_EOL, $node->nodeValue);
			
			foreach($node_lines as &$node_line) {
				$node_line = trim($node_line);
				
				$node_length = strlen($node_line);
				if($node_length !== 0) {
					$node_line_pieces = explode(' ', $node_line);
					
					$node_line_key = $node_line_pieces[0];
					$node_line_value = $node_line_pieces[1];
					
					$lets_encrypt_hash[$node_line_key] = $node_line_value;
				}
			}
			
			return $lets_encrypt_hash;
		}
	}

?>