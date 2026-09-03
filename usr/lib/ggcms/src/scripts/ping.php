<?php
	
	ggreqonce('traits/scripts/BaseConversion.php');
	ggreqonce('traits/scripts/DBAdminFunctions.php');
	ggreqonce('traits/scripts/DBFunctions.php');
	ggreqonce('traits/scripts/SimpleAPI.php');
	ggreqonce('traits/scripts/SimpleErrors.php');
	ggreqonce('traits/scripts/SimpleForms.php');
	ggreqonce('traits/scripts/SimpleORM.php');
	ggreqonce('traits/scripts/SimplePing.php');

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
			
			$curl_directory = 'curl';
			
			if(!is_dir($curl_directory)) {
				mkdir($curl_directory, 0777);
			}
			
			$curl_resource = curl_init($this->url);
			curl_setopt($curl_resource, CURLOPT_URL, $this->url);
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
			
			$curl_backup_file = fopen($curl_backup_filename, 'w+');
			fwrite($curl_backup_file, $curl_backup_file_data);
			
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
			
			curl_close($curl_resource);
			
			$this->output = $output;
			
			return TRUE;
		}
	}
	
?>