<?php

	class module_entryshare extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			
			return $this;
		}
		
		public function DisplayPermalink() {
			print('
				<script type="text/javascript">
					$(document).ready(function() {
						function resizePermalink() {
							dummy = document.createElement("span");
							dummy.text = $("#permalink-value-text").val();
							
							$("#permalink-value-text").attr("size", dummy.length);
						}
						
						resizePermalink();

						$("#permalink-button").click(function() {
							$("#permalink-button").hide();
							$("#permalink-value").show();
							resizePermalink();
							
							setTimeout(function (){
								$("#permalink-value").fadeOut(2000);
								
								setTimeout(function (){
									$("#permalink-button").fadeIn(2000);
								}, 2000);
							}, 5000);
						});
					});
				</script>
			');
		
			print('<div style="margin-right:5px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray15 float-right">');
			print('<span class="comments-link-box" style="font-family:arial, tahoma;margin:3px;padding:0px;display:inline-block;">');
			print('<strong>');
			
			print('<span id="permalink-button" style="cursor:pointer;color:blue;text-decoration:underline;">');
			print('Permalink');
			print('</span>');
			
			print('</strong>');
			
			print('<span id="permalink-value" style="display:none;">');
			print('<input id="permalink-value-text" style="height:18px;" class="select-input-contents" type="text" size="30" value="');
			print($this->that->handler->domain->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]));
			print('/?id=');
			print($this->that->entry['assignment'][0]['id']);
			print('">');
			print('</span>');
			print('</span>');
			print('</div>');
			
			return TRUE;
		}
		
		public function DisplaySmall() {
			print('<div style="margin-right:5px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray15 float-right">');
			print('<span class="comments-link-box" style="font-family:arial, tahoma;margin:3px;padding:0px;display:inline-block;">');
			
						// Display Social Media
						// -------------------------------------------------------
			
			$acceptable = [
				'facebook'=>TRUE,
				'google.bookmarks'=>TRUE,
				'reddit'=>TRUE,
				'twitter'=>TRUE,
			];
			
			$current_language_code = $this->that->handler->language->getLanguageCode();
			
				#TODO: Clean up this trash.
				
			$social_media_share_links_args = [
				'handler'=>$this->that->handler,
				'globals'=>$this->that->globals,
				'textonly'=>$this->that->mobile_friendly,
				'languageobject'=>$this->that->language_object,
				'domainobject'=>$this->that->handler->domain,
				'socialmedia'=>$this->that->social_media,
				'sharewithtext'=>$this->that->share_with_text,
				'url'=>$this->that->handler->domain->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]) . '/?id=' . $this->that->entry['assignment'][0]['id'],
				'title'=>$this->that->header_title_text,
				'desc'=>$instructions_content_text,
				'provider'=>$this->that->handler->domain->primary_domain_lowercased,
			];
			$social_media_share_links = $this->that->social_media->GetSocialMediaSiteLinks_WithShareLinks($social_media_share_links_args);
			$social_media_nice_names = $this->that->social_media->GetSocialMediaSites_NiceNames();
			
			foreach($this->that->social_media->GetSocialMediaSites_WithShareLinks_OrderedByPopularity() as $social_media_code) {
							// Gather Data
							// -------------------------------------------------------
				if($acceptable[$social_media_code]) {
					$social_media_share_link = $social_media_share_links[$social_media_code];
					$social_media_nice_name = $social_media_nice_names[$social_media_code];
					
								// Start Div
								// -------------------------------------------------------
					
					
					print('<div class="float-left margin-0px" title="' . $this->that->share_with_text . ' ' . $social_media_nice_name . '">');
					
								// Display Language Option
								// -------------------------------------------------------
					
					print('<div class="font-family-tahoma margin-0px">');
					print('<a href="' . $social_media_share_link . '" target="_blank" rel="nofollow">');
					
					if($this->that->text_only) {
						print('Share on ' . $social_media_nice_name);
					} else {
						print('<img height="19" src="' . $this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/image/social-media-logo-icons-opaque-background/' . $social_media_code . '.png" class="web-icons-image-div-source">');
					}
					
					print('</a>');
					print('</div>');
					#print($native_language_key . "|" . $native_language_name . "|" . $language_flag_filename . "<BR><BR>");
					
								// End Div
								// -------------------------------------------------------
					
					print('</div>');
				}
			}
			
			print('</span>');
			print('</div>');
		}
		
		public function Display() {
						// Share Links Header
					
					// -------------------------------------------------------------
					
			print('<a name="share"></a>');
			
			print('<center>');
			print('<div class="horizontal-center width-95percent">');
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<h2 class="horizontal-left margin-5px font-family-arial">');
			print('Share');
			print('</h2>');
			print('</div>');
			print('</div>');
			print('</center>');
				
						// Finish Share Links Header
					
					// -------------------------------------------------------------
										
			print('<div class="clear-float"></div>');
				
						// Start Display Share Options
					
					// -------------------------------------------------------------
			
			print('<center>');
			print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-90percent">');
			print('<div class="border-2px background-color-gray15 margin-5px horizontal-left font-family-arial">');
			print('<div class="margin-5px horizontal-left font-family-arial">');
			
						// Display "Share" Text
					
					// -------------------------------------------------------------
			
			print('<div class="float-left border-2px margin-5px background-color-gray13">');
			print('<div class="margin-5px">');
			print('<strong>Permalink for Sharing :</strong>');
			print('</div>');
			print('</div>');
				
						// Finish "Share" Text
					
					// -------------------------------------------------------------
										
			print('<div class="clear-float"></div>');
				
						// Display Permalink
					
					// -------------------------------------------------------------
			
			print('<center>');
			print('<div class="margin-5px horizontal-center width-90percent">');
			print('<div class="margin-5px border-2px background-color-gray13 float-left">');
			print('<div class="margin-5px horizontal-left font-family-arial float-left">');
			print('<input class="select-input-contents" type="text" size="100" value="');
			print($this->that->handler->domain->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]));
			print('/?id=');
			print($this->that->entry['assignment'][0]['id']);
			print('">');
			print('</div>');
			print('</div>');
			print('<div class="clear-float"></div>');
			print('</div>');
			print('</center>');
			
						// Display Social Networking Options
					
					// -------------------------------------------------------------
			
			$this->hard_display();
			
						// End Display Share Options
					
					// -------------------------------------------------------------
					
			print('</div>');
			print('</div>');
			print('</div>');
			print('</center>');
			
			return TRUE;
		}
		
		public function hard_display() {
						// Start Div
						// -------------------------------------------------------
			
			print('<script src="/javascript/web-icons.js"></script>');
			
			print('
<STYLE>
	.web-icons-image-div {
		float:left;
		position:relative;
		cursor:pointer;
	}
	
	.web-icons-image-div-source {
		z-index:100;
		position:relative;
	}
</STYLE>');
			
			
			if($this->that->share_text) {
				print("<div 'class'='width-90percent horizontal-center margin-top-14px border-1px'>");
				print("<div 'class'='display-inline-block'>");
				
						// Display "Share" Text
						// -------------------------------------------------------
				
				print('<table border="0" class="padding-0px margin-0px">');
				print('<tr valign="top">');
				print('<td valign="top">');
				print('<div class="font-family-tahoma font-size-150percent margin-10px border-2px background-color-gray10"><span class="margin-5px"><nobr>' . $this->that->share_text . ' :</nobr></span></div>');
				print('</td>');
				print('<td>');
			}
			
						// Display Social Media
						// -------------------------------------------------------
			
			$current_language_code = $this->that->handler->language->getLanguageCode();
			
			$social_media_share_links = $this->that->social_media->GetSocialMediaSiteLinks_WithShareLinks($this->that->social_media_share_link_args);
			$social_media_nice_names = $this->that->social_media->GetSocialMediaSites_NiceNames();
			
			foreach($this->that->social_media->GetSocialMediaSites_WithShareLinks_OrderedByPopularity() as $social_media_code) {
							// Gather Data
							// -------------------------------------------------------
							
				$social_media_share_link = $social_media_share_links[$social_media_code];
				$social_media_nice_name = $social_media_nice_names[$social_media_code];
				
							// Start Div
							// -------------------------------------------------------
				
				
				print('<div class="float-left margin-0px" title="' . $this->that->share_with_text . ' ' . $social_media_nice_name . '">');
				
							// Display Language Option
							// -------------------------------------------------------
				
				print('<div class="font-family-tahoma margin-0px web-icons-image-div">');
				print('<a href="' . $social_media_share_link . '" target="_blank" rel="nofollow">');
				
				if($this->that->text_only) {
					print('Share on ' . $social_media_nice_name);
				} else {
					print('<img src="' . $this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/image/social-media-logo-icons-opaque-background/' . $social_media_code . '.png" class="web-icons-image-div-source">');
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
			
			if($this->that->share_text) {
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
			
			return TRUE;
		}
	}

?>