<?php

			// Display
		
		// -------------------------------------------------------------
		
		ggreqonce('modules/html/entry-header.php');
		ggreqonce('modules/html/entry-index-header.php');
		$entryindexheader = new module_entryindexheader([
			'that'=>$this,
			'main_text'=>'Redirecting to a Secured Connection',
		]);
		
		$entryindexheader->Display();

?>