<?php
		
	function loadStandardGGCMSLibraries() {
				// Define Trait Classes
				// -----------------------------------------------------------------
		
		$standard_classes = [
			'ReverseDNSNotation',
			'GGCMSDateFormat',
		];
	
		$traits_folder_location_prefix = GGCMS_DIR . 'traits/';
		
				// Call Trait Classes
				// -----------------------------------------------------------------
		
		foreach($standard_classes as $class) {
			$location = $traits_folder_location_prefix . $class . '.php';
			require($location);
		}
	
				// Define Standard Class Folder
				// -----------------------------------------------------------------
		
		$folder_location_prefix = GGCMS_DIR . 'classes/';
		
				// Define Standard Classes
				// -----------------------------------------------------------------
		
		$standard_classes = [
			
					// API
					// -----------------------------------------------------------------
			
			'API/Google',
			
					// Charsets
					// -----------------------------------------------------------------
			
			'Charset/HTMLEntities',
			'Charset/UTF8Characters',
			
					// Database
					// -----------------------------------------------------------------
			
			'Database/DBAccess',
			'Database/HardcodedTableDescriptions',
			'Database/Time',
			'Database/TimeMySQL',
			'Database/EscapeMySQL',
			'Database/ORM',
			
					// Development
					// -----------------------------------------------------------------
					
			'Development/Version',
			
					// Errors
					// -----------------------------------------------------------------
			
			'Error/ErrorLogging',
			'Error/IssueLogging',
			
					// Format
					// -----------------------------------------------------------------
			
			'Format/Language',
			
					// Networking
					// -----------------------------------------------------------------
			
			'Networking/Cookie',
			'Networking/Domain',
			'Networking/IPAddress',
			'Networking/Handler',
			'Networking/Query',
			
					// Math
					// -----------------------------------------------------------------
			
			'Math/Base',
			'Math/Binary',
			
					// Security
					// -----------------------------------------------------------------
		
			'Security/Authentication',
			'Security/HandleInput',
			'Security/PhishingCharacters',
		
		];
		
				// Call Standard Classes
				// -----------------------------------------------------------------
		
		foreach($standard_classes as $class) {
			require($folder_location_prefix . $class . '.php');
		}
	}
	
	loadStandardGGCMSLibraries();	
?>