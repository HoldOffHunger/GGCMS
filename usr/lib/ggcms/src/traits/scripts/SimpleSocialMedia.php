<?php

	trait SimpleSocialMedia {
		public function SetSocialMediaBasics() {
			ggreq('classes/API/SocialMedia.php');
			
			$this->social_media = new SocialMedia();
		}
	}
	
?>