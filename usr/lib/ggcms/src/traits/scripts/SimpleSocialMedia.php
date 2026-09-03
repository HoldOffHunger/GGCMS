<?php

	trait SimpleSocialMedia {
		public function SetSocialMediaBasics() {
			ggreqonce('classes/API/SocialMedia.php');
			
			$this->social_media = new SocialMedia();
		}
	}
	
?>