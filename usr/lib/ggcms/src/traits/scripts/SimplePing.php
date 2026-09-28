<?php

	trait SimplePing {
		public $curl_status;
		public $curl;
		public $network_status_code;
		
		public function SetCurlStatus($args) {
			if(!$this->curl) {
				$this->SetCurlStatus_RequireFiles();		# plain ggreq(), so once a request
			}
			
			$curl_resource = $args['curlresource'];
			
			$http_code = curl_getinfo($curl_resource, CURLINFO_HTTP_CODE);
			
			$curl_status = [];
			$curl_status_options = $this->GetCurlOptions();
			
			foreach($curl_status_options as $curl_option_label => $curl_option_constant) {
				$curl_status[$curl_option_label] = curl_getinfo($curl_resource, $curl_option_constant);
			}
			
			return $this->curl_status = $curl_status;
		}
		
		public function SetCurlStatus_RequireFiles() {
			ggreq('classes/Networking/Curl.php');
			$curl = new Curl;
			
			$this->curl = $curl;
			
			ggreq('classes/Networking/NetworkStatusCode.php');
			$network_status_code = new NetworkStatusCode;
			
			$this->network_status_code = $network_status_code;
			
			return TRUE;
		}
		
		public function GetCurlOptions() {
			return $this->curl->GetCurlOptions();
		}
		
		public function GetNetworkStatusCodes() {
			return $this->network_status_code->GetNetworkStatusCodes();
		}
	}
?>