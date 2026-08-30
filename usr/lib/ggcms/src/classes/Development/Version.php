<?php

	class Version {
			// Constants
		public function GetSoftwareDesigner() {
			return 'HoldOffHunger';
		}
		
		public function GetSoftwareName() {
			return 'Green Gluon CMS';
		}
		
		public function GetSoftwareAcronym() {
			return 'GG-CMS';
		}
		
		public function GetSoftwareVersion() {
			return '1.00';
		}
		
		public function GetOpenSourceURL() {
			return 'https://github.com/HoldOffHunger/GreenGluonCMS';
		}
		
			// Constant-Based Functions
		public function GetSoftwareNameAcronymAndVersion() {
			return $this->GetSoftwareName() . ' (' . $this->GetSoftwareAcronym() . '): Version ' . $this->GetSoftwareVersion();
		}
		
		public function GetSoftwareNameAndAcronym() {
			return $this->GetSoftwareName() . ' (' . $this->GetSoftwareAcronym() . ')';
		}
		
		public function GetSoftwareAcronymAndVersion() {
			return $this->GetSoftwareAcronym() . ' V. ' . $this->GetSoftwareVersion();
		}
	}

?>