<?php

		/*
			The permalink and the share links.  The permalink is the entry's
			/?id= address, which outlives any change to its path; clicking it
			copies it, and shows it for copying by hand where the browser
			will not.
		*/

	class module_entryshare extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

			// A site's own front page has no assignment to number; its permalink is the site itself.
		public function PermalinkURL() {
			$site = $this->that->handler->domain->GetPrimaryDomain(['insecure'=>1, 'lowercase'=>1, 'www'=>1]);
			$id = $this->that->entry['assignment'][0]['id'] ?? '';

			if(!$id) {
				return $site . '/';
			}

			return $site . '/?id=' . $id;
		}

		public function DisplayPermalink() {
			print('<span class="permalink">');
			print('<button type="button" id="permalink-button" class="action">Permalink</button>');
			print('<span id="permalink-value" hidden>');
			print('<input id="permalink-value-text" class="select-input-contents" type="text" readonly aria-label="Permalink" value="' . htmlspecialchars($this->PermalinkURL(), ENT_QUOTES, 'UTF-8') . '">');
			print('</span>');
			print('</span>');

			print('
				<script>
					$(document).ready(function() {
						$("#permalink-button").click(function() {
							var field = document.getElementById("permalink-value-text");
							var button = this;

							$("#permalink-value").prop("hidden", false);
							field.select();

							if(navigator.clipboard && navigator.clipboard.writeText) {
								navigator.clipboard.writeText(field.value).then(function() {
									button.textContent = "Copied";
									setTimeout(function() { button.textContent = "Permalink"; }, 2500);
								}, function() {});
							}
						});
					});
				</script>
			');

			return TRUE;
		}

		public function DisplaySmall() {
			$acceptable = [
				'facebook'=>TRUE,
				'google.bookmarks'=>TRUE,
				'reddit'=>TRUE,
				'twitter'=>TRUE,
			];

			$social_media_share_links_args = [
				'handler'=>$this->that->handler,
				'globals'=>$this->that->globals,
				'textonly'=>$this->that->mobile_friendly,
				'languageobject'=>$this->that->language_object,
				'domainobject'=>$this->that->handler->domain,
				'socialmedia'=>$this->that->social_media,
				'sharewithtext'=>$this->that->share_with_text,
				'url'=>$this->PermalinkURL(),
				'title'=>$this->that->header_title_text,
				'desc'=>'',
				'provider'=>$this->that->handler->domain->primary_domain_lowercased,
			];
			$social_media_share_links = $this->that->social_media->GetSocialMediaSiteLinks_WithShareLinks($social_media_share_links_args);
			$social_media_nice_names = $this->that->social_media->GetSocialMediaSites_NiceNames();

			print('<span class="share-small">');

			foreach($this->that->social_media->GetSocialMediaSites_WithShareLinks_OrderedByPopularity() as $social_media_code) {
				if($acceptable[$social_media_code]) {
					$this->DisplayShareLink([
						'url'=>$social_media_share_links[$social_media_code],
						'code'=>$social_media_code,
						'name'=>$social_media_nice_names[$social_media_code],
					]);
				}
			}

			print('</span>');

			return TRUE;
		}

		public function DisplayShareLink($args) {
			$name = $args['name'];

			print('<a class="share-link" href="' . $args['url'] . '" target="_blank" rel="nofollow noopener" title="' . htmlentities($this->that->share_with_text . ' ' . $name) . '">');

			if($this->that->text_only) {
				print('Share on ' . $name);
			} else {
				print('<img alt="' . htmlentities($name) . '" width="20" height="20" src="/image/social-media-logo-icons-opaque-background/' . $args['code'] . '.png">');
			}

			print('</a>');

			return TRUE;
		}

		public function Display() {
			print('<section class="block share" id="share">');
			print('<h2 class="block-title">Share</h2>');

			print('<p class="field">');
			print('<label for="share-permalink">Permalink</label>');
			print('<input id="share-permalink" class="select-input-contents" type="text" readonly value="' . htmlspecialchars($this->PermalinkURL(), ENT_QUOTES, 'UTF-8') . '">');
			print('</p>');

			$this->hard_display();

			print('</section>');

			return TRUE;
		}

		public function hard_display() {
			if($this->that->share_text) {
				print('<p class="share-label">' . $this->that->share_text . '</p>');
			}

			$social_media_share_links = $this->that->social_media->GetSocialMediaSiteLinks_WithShareLinks($this->that->social_media_share_link_args);
			$social_media_nice_names = $this->that->social_media->GetSocialMediaSites_NiceNames();

			print('<div class="share-links">');

			foreach($this->that->social_media->GetSocialMediaSites_WithShareLinks_OrderedByPopularity() as $social_media_code) {
				$this->DisplayShareLink([
					'url'=>$social_media_share_links[$social_media_code],
					'code'=>$social_media_code,
					'name'=>$social_media_nice_names[$social_media_code],
				]);
			}

			print('</div>');

			return TRUE;
		}
	}

?>
