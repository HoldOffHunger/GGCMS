<?php

	trait SimpleGeography {
		public $country;
		
		public function SetGeographyBasics() {
			$this->SetCountryGeography();
			
			return TRUE;
		}
		
		public function SetCountryGeography() {
			ggreq('classes/Geography/Country.php');
			
			$this->country = new Country();
			
			return TRUE;
		}
	}
	
?>