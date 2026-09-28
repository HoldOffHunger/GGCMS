<?php
	
	ggreq('traits/scripts/BaseConversion.php');
	ggreq('traits/scripts/DBAdminFunctions.php');
	ggreq('traits/scripts/DBFunctions.php');
	ggreq('traits/scripts/SimpleAPI.php');
	ggreq('traits/scripts/SimpleErrors.php');
	ggreq('traits/scripts/SimpleForms.php');
	ggreq('traits/scripts/SimpleORM.php');
	ggreq('traits/scripts/SimplePing.php');

	class ping extends basicscript {
					// Class Information
					// --------------------------------------------------------------
					
				// Traits
					
		use BaseConversion;
		use DBAdminFunctions;
		use DBFunctions;
		use SimpleAPI;
		use SimpleErrors;
		use SimpleForms;
		use SimpleOrm;
		use SimplePing;
		
		public $url;
		public $backup_url;
		public $curl_status_display;
		public $output;
		
				// Security
		
		public function IsSecure() {
			return TRUE;
		}
		
		public function RequiresLogin() {
			return TRUE;
		}
		
		public function AdminOnly() {
			return TRUE;
		}
		
					// Function Information
					// --------------------------------------------------------------
					
						// BCE-Informational Functions
						// --------------------------------------------------------------
		
		public function Curl() {
			$this->url = $this->Param('url');
			
			if(!$this->url) {
				return FALSE;
			}
				
				/*
					Only http or https, to public addresses, pinned; see
					Curl::PublicDestination().  Redirects are not followed,
					which is cURL's default, and could only be to http or https.
				*/
			
			$this->SetCurlStatus_RequireFiles();
			
			$destination = $this->curl->PublicDestination(['url'=>$this->url]);
			
			if(!$destination) {
				return FALSE;
			}
			
			$curl_directory = 'curl';
			
			if(!is_dir($curl_directory)) {
				mkdir($curl_directory, 0777);
			}
			
			$curl_resource = curl_init();
			curl_setopt($curl_resource, CURLOPT_URL, $this->url);
			if(defined('CURLOPT_PROTOCOLS_STR')) {		# libcurl 7.85 and later; the host's jammy has 7.81
				curl_setopt($curl_resource, CURLOPT_PROTOCOLS_STR, 'http,https');
				curl_setopt($curl_resource, CURLOPT_REDIR_PROTOCOLS_STR, 'http,https');
			} else {
				curl_setopt($curl_resource, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
				curl_setopt($curl_resource, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
			}
			curl_setopt($curl_resource, CURLOPT_RESOLVE, $destination['resolve']);
			curl_setopt($curl_resource, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($curl_resource, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl_resource, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($curl_resource, CURLOPT_HEADER, 0);
			$output = curl_exec($curl_resource);
			
			$this->SetCurlStatus(['curlresource'=>$curl_resource]);
			
			#print("<PRE>");
			#print_r($this->curl_status);
			#print("</PRE>");
			
			$curl_status = $this->curl_status;
			
			$time = time();
			$curl_backup_file_data = 'Date : ' . $time . ' (' . date('D, d M Y H:i:s', $time) . ')' . "\n";
			
			foreach ($curl_status as $curl_key => $curl_value) {
				$curl_backup_file_data .= $curl_key . ' : ' . $curl_value . "\n";
			}
			
			$curl_backup_file_data .= 'Response :' . "\n";
			
			$curl_backup_file_data .= $output;
			$curl_backup_filename = 'curl/' . urlencode($this->url);
			
			$primary_domain_url = $this->handler->domain->GetPrimaryDomain(['www'=>1]) . '/';
			
			$this->backup_url = '<a href="' . $primary_domain_url . 'curl/' . urlencode(urlencode($this->url)) . '" target="_blank">Backup</a>';
			
				// A URL too long for a file name fails to open; fwrite(FALSE) was a TypeError
			$curl_backup_file = @fopen($curl_backup_filename, 'w+');
			if($curl_backup_file) {
				fwrite($curl_backup_file, $curl_backup_file_data);
				fclose($curl_backup_file);
			}
			
			$network_status_codes = $this->GetNetworkStatusCodes();
			$curl_status['HTTP Code'] .= ' (' . $network_status_codes[$curl_status['HTTP Code']] . ')';
			
			$curl_status_display = [];
			
			foreach($curl_status as $curl_key => $curl_value) {
				if(is_array($curl_value)) {
					$new_curl_value = implode(', ', $curl_value);
					$curl_value = $new_curl_value;
				}
				
				$curl_status_display[] = [
					'<nobr>' . $curl_key . ' :</nobr>', $curl_value,
				];
			}
			
			$this->curl_status_display = $curl_status_display;
			
			$this->output = $output;
			
			return TRUE;
		}
	}
	
?>