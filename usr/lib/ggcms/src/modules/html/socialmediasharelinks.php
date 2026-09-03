<?php

	class module_socialmediasharelinks extends module_spacing {
		public function __construct($args) {
			$this->globals = $args['globals'];
			$this->text_only = $args['textonly'];
			$this->language_object = $args['languageobject'];
			$this->domain_object = $args['domainobject'];
			$this->social_media_object = $args['socialmedia'];
			$this->social_media_share_link_args = $args['socialmediasharelinkargs'];
			$this->share_text = $this->globals->functionality['sharing']['text']['Share'][$this->language_object->getLanguageCode()];
			$this->share_with_text = $this->globals->functionality['sharing']['text']['Share With'][$this->language_object->getLanguageCode()];
			
			if(!$this->social_media_object) {
				ggreqonce('classes/API/SocialMedia.php');
				
				$this->social_media_object = new SocialMedia();
			}
			
			return TRUE;
		}
		
		public function display_styles() {
			print('<STYLE>');
			print('
				.web-icons-image-div {
					float:left;
					position:relative;
					cursor:pointer;
				}
				
				.web-icons-image-div-source {
					z-index:100;
					position:relative;
				}
			');
			print('</STYLE>');
			
			return TRUE;
		}
		
		public function display() {
						// Start Div
						// -------------------------------------------------------
			
			print('<script src="/javascript/web-icons.js"></script>');
			
			$this->display_styles();
			
			if($this->share_text) {
				print("<div 'class'='horizontal-center margin-top-14px border-1px'>");
				print("<div 'class'='display-inline-block'>");
				
						// Display "Share" Text
						// -------------------------------------------------------
				
				print('<table border="0" class="padding-0px margin-0px">');
				print('<tr valign="top">');
				print('<td valign="top">');
				print('<div class="font-family-tahoma font-size-150percent margin-10px border-2px background-color-gray10"><span class="margin-5px"><nobr>' . $this->share_text . ' :</nobr></span></div>');
				print('</td>');
				print('<td>');
			}
			
						// Display Social Media
						// -------------------------------------------------------
			
			$current_language_code = $this->language_object->getLanguageCode();
			
			$social_media_share_links = $this->social_media_object->GetSocialMediaSiteLinks_WithShareLinks($this->social_media_share_link_args);
			$social_media_nice_names = $this->social_media_object->GetSocialMediaSites_NiceNames();
			
			foreach($this->social_media_object->GetSocialMediaSites_WithShareLinks_OrderedByPopularity() as $social_media_code) {
							// Gather Data
							// -------------------------------------------------------
							
				$social_media_share_link = $social_media_share_links[$social_media_code];
				$social_media_nice_name = $social_media_nice_names[$social_media_code];
				
							// Start Div
							// -------------------------------------------------------
				
				print('<div class="float-left margin-0px" title="' . $this->share_with_text . ' ' . $social_media_nice_name . '"></div>');
				
							// Display Language Option
							// -------------------------------------------------------
				
				print('<div class="font-family-tahoma margin-0px web-icons-image-div">');
				print('<a href="' . $social_media_share_link . '" target="_blank" rel="nofollow">');
				
				if($this->text_only) {
					print('Share on ' . $social_media_nice_name);
				} else {
					print('<img width="25" height="25" src="' . $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/image/social-media-logo-icons-opaque-background/' . $social_media_code . '.png" class="web-icons-image-div-source">');
				}
				
				print('</a>');
				print('</div>');
				#print($native_language_key . "|" . $native_language_name . "|" . $language_flag_filename . "<BR><BR>");
				
							// End Div
							// -------------------------------------------------------
				
				print('</div>');
			}
			
						// Start Div
						// -------------------------------------------------------
			
			print('<div class="clear-float"></div>');
			
						// Conclude Table
						// -------------------------------------------------------
			
			if($this->share_text) {
				print('</td>');
				print('</tr>');
				print('</table>');
				
							// Start Div
							// -------------------------------------------------------
				
				print('<div class="clear-float"></div>');
				
							// End Div
							// -------------------------------------------------------
				
				print('</div>');
				print('</div>');
			}
		}
	}

?>