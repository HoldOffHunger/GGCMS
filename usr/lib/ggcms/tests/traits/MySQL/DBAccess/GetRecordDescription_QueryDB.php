<?php
	trait GetRecordDescription_QueryDBTrait {
		public function testGetRecordDescription() {
			print('hey.');
			
		#	ggreq('classes/Database/DBAccess.php');
		#	ggreq('classes/Database/TimeMySQL.php');
		#	ggreq('classes/Networking/IPAddress.php');
		#	ggreq('classes/Database/EscapeMySQL.php');
			
					ini_set('session.referer_check', 'TRUE');	# HOLY GOD, WHY WOULD YOU NOT?	
		#	print_r($_SERVER);
			$_SERVER['HTTP_HOST'] = 'localhost';
			$_SERVER['SERVER_NAME'] = 'localhost';
			$_SERVER['REQUEST_URI'] = '/image/uuuuuuuu';
			$_GET['domain'] = 'sortwords.com';
			
			require(GGCMS_DIR . 'classes/StandardLibraries.php');
			$handler = new Handler();
			$handler->HandleRequest();
			
			$entry_description = $handler->db_access->GetRecordDescription_QueryDB([
				'type'=>'Entry',
			]);
			
			print_r($entry_description);
			
			/*
			$db_access = new DBAccess([
				'handler'=>null,
				'database'=>'clonefrom',
			]);
			*/
			
			print('got it.');
			
			return TRUE;
		}
	}
?>