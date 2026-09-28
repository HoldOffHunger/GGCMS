<?php

	class Curl {
			// PublicDestination()
			// Tests: CurlTest::testPublicDestination()
			// Test file: tests/src/classes/Networking/CurlTest.php
			/*
				ping.php fetched whatever it was given, and saved the response
				under the document root.  file:// read local files -- the
				database credentials among them -- and loopback, private and
				link-local addresses reached services and the host's metadata.
				This answers where a URL may go: http or https, to a host every
				one of whose addresses is public, with the port it will use.
				The caller pins those addresses with CURLOPT_RESOLVE, so the
				name cannot be answered differently when cURL looks it up.
				FALSE for anything else.
			*/
		public function PublicDestination($args) {
			$parts = parse_url((string)$args['url']);
			
			if(!$parts || !isset($parts['host'])) {
				return FALSE;
			}
			
			$scheme = strtolower($parts['scheme'] ?? '');
			
			if(!in_array($scheme, ['http', 'https'], TRUE)) {
				return FALSE;
			}
			
			$host = trim($parts['host'], '[]');
			$port = (int)($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
			
			$addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
			
			if(!$addresses) {
				return FALSE;
			}
			
			foreach($addresses as $address) {
				if(!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
					return FALSE;
				}
			}
			
			$pinned = [];
			foreach($addresses as $address) {
				$pinned[] = $parts['host'] . ':' . $port . ':' . (str_contains($address, ':') ? '[' . $address . ']' : $address);
			}
			
			return [
				'scheme'=>$scheme,
				'host'=>$host,
				'port'=>$port,
				'addresses'=>$addresses,
				'resolve'=>$pinned,
			];
		}
		
		public function GetCurlOptions() {
			return [
				'Effective URL'=>CURLINFO_EFFECTIVE_URL,
				'HTTP Code'=>CURLINFO_HTTP_CODE,
				'File Name'=>CURLINFO_FILETIME,
				'Total Time'=>CURLINFO_TOTAL_TIME,
				'Name Lookup Time'=>CURLINFO_NAMELOOKUP_TIME,
				'Connect Time'=>CURLINFO_CONNECT_TIME,
				'Pretransfer Time'=>CURLINFO_PRETRANSFER_TIME,
				'Start Transfer Time'=>CURLINFO_STARTTRANSFER_TIME,
				'Redirect Count'=>CURLINFO_REDIRECT_COUNT,
				'Redirect Time'=>CURLINFO_REDIRECT_TIME,
				'Redirect URL'=>CURLINFO_REDIRECT_URL,
				'Primary IP'=>CURLINFO_PRIMARY_IP,
				'Primary Port'=>CURLINFO_PRIMARY_PORT,
				'Local IP'=>CURLINFO_LOCAL_IP,
				'Local Port'=>CURLINFO_LOCAL_PORT,
				'Size Upload'=>CURLINFO_SIZE_UPLOAD,
				'Size Download'=>CURLINFO_SIZE_DOWNLOAD,
				'Speed Download'=>CURLINFO_SPEED_DOWNLOAD,
				'Speed Upload'=>CURLINFO_SPEED_UPLOAD,
				'Header Size'=>CURLINFO_HEADER_SIZE,
				'Header Out'=>CURLINFO_HEADER_OUT,
				'Request Size'=>CURLINFO_REQUEST_SIZE,
				'SSL Verify Result'=>CURLINFO_SSL_VERIFYRESULT,
				'Content Length Download'=>CURLINFO_CONTENT_LENGTH_DOWNLOAD,
				'Content Length Upload'=>CURLINFO_CONTENT_LENGTH_UPLOAD,
				'Content Type'=>CURLINFO_CONTENT_TYPE,
				'Private'=>CURLINFO_PRIVATE,
				'Response Code'=>CURLINFO_RESPONSE_CODE,
				'Connect Code'=>CURLINFO_HTTP_CONNECTCODE,
				'HTTP Auth Avail'=>CURLINFO_HTTPAUTH_AVAIL,
				'Proxy Auth Avail'=>CURLINFO_PROXYAUTH_AVAIL,
				'Proxy OS Error Number'=>CURLINFO_OS_ERRNO,
				'Number of Connects'=>CURLINFO_NUM_CONNECTS,
				'SSL Engines'=>CURLINFO_SSL_ENGINES,
				'Cookie List'=>CURLINFO_COOKIELIST,
				'FTP Entry Path'=>CURLINFO_FTP_ENTRY_PATH,
				'Application Connect Time'=>CURLINFO_APPCONNECT_TIME,
				'Cert Info'=>CURLINFO_CERTINFO,
				'Condition Unmet'=>CURLINFO_CONDITION_UNMET,
				'RTSP Client CSeq'=>CURLINFO_RTSP_CLIENT_CSEQ,
				'RTSP CSeq Received'=>CURLINFO_RTSP_CSEQ_RECV,
				'RTSP Server CSeq'=>CURLINFO_RTSP_SERVER_CSEQ,
				'RTSP Session ID'=>CURLINFO_RTSP_SESSION_ID,
			];
		}
	}

?>