<?php

	trait SimpleSocialMedia {
		public function SetSocialMediaBasics() {
			classreq('classes/API/SocialMedia.php');
			
			$this->social_media = new SocialMedia();
		}
	}
	
?>