<?php

	ggreq('traits/scripts/DBFunctions.php');
	ggreq('traits/scripts/SimpleErrors.php');
	ggreq('traits/scripts/SimpleForms.php');
	ggreq('traits/scripts/SimpleLookupLists.php');
	ggreq('traits/scripts/SimpleORM.php');
	ggreq('traits/scripts/SimpleSocialMedia.php');

	class peerblocklist extends basicscript {
						// Traits
						// ---------------------------------------------
		
		use DBFunctions;
		use SimpleErrors;
		use SimpleForms;
		use SimpleLookupLists;
		use SimpleORM;
		use SimpleSocialMedia;
		
						// View Functionality
						// ---------------------------------------------
		
		public function display() {
		#	printf("SOYBEANS!");
			$list_url = '';
/*
http://tinyurl.com/PeerBlock-p2p
http://tinyurl.com/PeerBlock-Ad-Tracker-Porn
http://tinyurl.com/PeerBlock-dshield
http://tinyurl.com/PeerBlock-edu
http://tinyurl.com/PeerBlock-hijacked
http://tinyurl.com/PeerBlock-level-1
http://tinyurl.com/PeerBlock-level-2
http://tinyurl.com/PeerBlock-level-3
http://tinyurl.com/PeerBlock-spyware
*/
			
			switch($_GET['list']) {
				case 'ad-tracker-porn':
					$list_url = 'http://tinyurl.com/PeerBlock-Ad-Tracker-Porn';
					break;
				
				case 'dshield':
					$list_url = 'http://tinyurl.com/PeerBlock-dshield';
					break;
				
				case 'edu':
					$list_url = 'http://tinyurl.com/PeerBlock-edu';
					break;
				
				case 'hijacked':
					$list_url = 'http://tinyurl.com/PeerBlock-hijacked';
					break;
				
				case 'level-1':
					$list_url = 'http://tinyurl.com/PeerBlock-level-1';
					break;
				
				case 'level-2':
					$list_url = 'http://tinyurl.com/PeerBlock-level-2';
					break;
				
				case 'level-3':
					$list_url = 'http://tinyurl.com/PeerBlock-level-3';
					break;
				
				case 'spyware':
					$list_url = 'http://tinyurl.com/PeerBlock-spyware';
					break;
				
				case 'p2p':
				default:
					$list_url = 'http://tinyurl.com/PeerBlock-p2p';
					break;
			}
			unlink (GGCMS_DIR . 'data/my-zip.gz');
			unlink (GGCMS_DIR . 'data/altered-zip.gz');
			$file = file_put_contents(GGCMS_DIR . 'data/my-zip.gz', fopen($list_url, 'r'), LOCK_EX);
			$file = gzopen(GGCMS_DIR . 'data/my-zip.gz', 'rb');
			
			$text = '';
			
			while (!gzeof($file)) {
				$text .= (gzread($file, 4000));
			}
			
			$text = str_replace('# List distributed by iblocklist.com', '', $text);
			
			$filep = gzopen(GGCMS_DIR . 'data/altered-zip.gz', 'w9');
			
			gzwrite($filep, $text);
			
			gzclose($filep);
		#	print("TEXT???" . strlen($text));
			header('Content-disposition: attachment;filename=p2p.gz');
			readfile(GGCMS_DIR . 'data/altered-zip.gz');
			
			return true;
		}
	}

?>