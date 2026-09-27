<?php

	trait SimpleSocialMedia {
		public $social_media;
		
		public function SetSocialMediaBasics() {
			ggreq('classes/API/SocialMedia.php');
			
			$this->social_media = new SocialMedia();
		}
	}
	
?>