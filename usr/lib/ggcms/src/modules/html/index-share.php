<?php

	require_once(GGCMS_DIR . 'modules/html/socialmediasharelinks.php');
	require_once(GGCMS_DIR . 'modules/html/entry-share.php');

		/*
			The share links at the foot of an index page, described by the
			entry's description and the caption of its picture, and pointing
			at the entry's permalink -- the site itself, on a front page.

			view/index.php and view/display_index.php each built these by
			hand.  The index page's copy shared /anarchism.php?action=index,
			a page of one long-gone site, on every site; both now share the
			permalink module_entryshare gives the reading page.

			Switches:
			  boxed  TRUE sets the links in a centred, floated box, as the
			         front page has them
		*/

	class module_indexshare extends module_spacing {
		public $that;
		public $boxed;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->boxed = !empty($args['boxed']);
		}

		public function Display() {
			$share_links = $this->ShareLinks();

			if($this->boxed) {
				print('<center>');
				print('<div class="margin-5px float-left">');
			}

			$share_links->display();

			if($this->boxed) {
				print('</div>');
				print('</center>');
			}

			print('<div class="clear-float"></div>');

			return TRUE;
		}

		public function ShareLinks() {
			$that = $this->that;
			$entry_share = new module_entryshare(['that'=>$that]);

			return new module_socialmediasharelinks([
				'globals'=>$that->handler->globals,
				'textonly'=>$that->mobile_friendly,
				'languageobject'=>$that->language_object,
				'domainobject'=>$that->domain_object,
				'socialmedia'=>$that->social_media,
				'sharewithtext'=>'Share With',
				'socialmediasharelinkargs'=>[
					'url'=>$entry_share->PermalinkURL(),
					'title'=>$that->header_title_text,
					'desc'=>$this->Description(),
					'provider'=>$that->domain_object->primary_domain_lowercased,
				],
			]);
		}

		public function Description() {
			$entry = $this->that->entry;
			$description = '';

			if($entry['description'] && $entry['description'][0]) {
				$description = str_replace('"', '\'', $entry['description'][0]['Description']);
			}

			if($entry['image'][0] && $entry['image'][0]['id']) {
				$description .= ' (' . $entry['image'][0]['Description'] . ')';
			}

			return $description;
		}
	}

?>
