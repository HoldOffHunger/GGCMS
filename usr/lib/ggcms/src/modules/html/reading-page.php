<?php

		/*
			A text laid out for reading: the text on a paper sheet at a
			comfortable measure, and beside it on wide screens -- below it on
			narrow ones -- the catalogue record, the formats, where it sits
			among its neighbours, how to cite it, who wrote it, and its tags.

			Everything it prints comes from the ordinary modules; this only
			decides where each goes.  A template's HTML branch becomes

				ggreq('modules/html/reading-page.php');
				$reading_page = new module_readingpage(['that'=>$this, 'chapters'=>'Chapters']);
				$reading_page->Display(['file'=>__FILE__]);

			Switches:
			  chapters     the heading over the entry's children, or FALSE
			               where the entry has none to show -- a chapter
			  authors      how many associations to show as author cards;
			               0 for all of them
			  pictures_first
			               TRUE for a site whose entries are pictures before
			               they are text -- MasereelGroup's woodcuts -- to
			               show the images under the title, in .lead-pictures,
			               rather than after everything else
		*/

	class module_readingpage extends module_spacing {
		public $that;
		public $chapters;
		public $authors;
		public $pictures_first;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->chapters = array_key_exists('chapters', $args) ? $args['chapters'] : 'Chapters';
			$this->authors = array_key_exists('authors', $args) ? (int) $args['authors'] : 0;
			$this->pictures_first = !empty($args['pictures_first']);

			foreach([
				'entry-association', 'entry-comments', 'entry-controls', 'entry-date', 'entry-description',
				'entry-header', 'entry-image', 'entry-likes', 'entry-link', 'entry-navigation', 'entry-quote',
				'entry-record', 'entry-share', 'entry-sort', 'entry-tag', 'entry-textbody', 'entry-children',
				'alternateformats', 'breadcrumbs', 'divider', 'navigation',
			] as $module) {
				require_once(GGCMS_DIR . 'modules/html/' . $module . '.php');
			}
		}

		public function Display($args) {
			$that = $this->that;

				/*
					First, because it reaches into a chapter's parent for an
					author the chapter does not name itself, and the byline
					should see that author too.
				*/

			$association = new module_entryassociation(['that'=>$that, 'header'=>'']);

			$entrydate = new module_entrydate(['that'=>$that]);
			$time_data = $entrydate->getSimpleData();

			$entryheader = new module_entryheader(['that'=>$that, 'time_frame'=>$time_data['text']]);
			$formats = new module_alternateformats(['that'=>$that]);
			$share = new module_entryshare(['that'=>$that]);
			$comments = new module_entrycomments(['that'=>$that]);
			$record = new module_entryrecord(['that'=>$that]);
			$navigation = new module_entrynavigation(['that'=>$that]);

			$entryheader->DisplaySiteBar();

			$this->DisplayAdminControls($args);

			print('<div class="page-tools">');
			$breadcrumbs = new module_breadcrumbs(['that'=>$that]);
			$breadcrumbs->Display();
			print('</div>');

			print('<div class="read-grid">');

					// The sheet

				// -------------------------------------------------------------

			print('<article class="sheet"><div class="sheet-inner">');

			$entryheader->DisplayTitleBlock();

			print('<div class="actions">');

			if($that->entry['textbody'] && $that->counts['textbody']) {
				$formats->DisplayListen();
			}

			print('<a class="action" href="#formats">Download<span class="action-count">' . count($formats->getFormats()) . ' formats</span></a>');
			print('<a class="action" href="#cite">Cite</a>');
			$share->DisplayPermalink();

			print('<span class="actions-spacer"></span>');

			$likes = new module_entrylikes(['that'=>$that]);
			$likes->Display();

			$comments->DisplayLinkBox();

			print('</div>');

			$images = new module_entryimage(['that'=>$that]);

			if($this->pictures_first) {
				print('<div class="lead-pictures">');
				$images->DisplayLead();
				print('</div>');
			}

			$description = new module_entrydescription(['that'=>$that, 'header'=>'']);
			$description->Display();

			$quotes = new module_entryquotes(['that'=>$that]);
			$quotes->Display(['max'=>1, 'header'=>'']);

			$textbody = new module_entrytextbody(['that'=>$that, 'noalts'=>TRUE]);
			$textbody->Display();

				// The formats are in the sidebar already, so not again here.

			if($this->chapters && $that->children && $that->counts['children'] !== 0) {
				$children = new module_entrychildren(['that'=>$that, 'entrysort'=>new module_entrysort(['that'=>$that]), 'header'=>$this->chapters]);
				$children->Display_Entries([
					'entries'=>$that->children,
					'count'=>$that->counts['children'],
					'alts'=>FALSE,
					'stats'=>TRUE,
				]);
			}

			$entrydate->DisplayEventDatesHistory();

			if(!$this->pictures_first) {
				$images->Display(['header'=>'Images']);
			}

			$links = new module_entrylink(['that'=>$that]);
			$links->Display([]);

			print('<div class="asterism" aria-hidden="true">&#x2042;</div>');

			$this->DisplayInvitation();

			if($that->counts['younger_sibling'] || $that->counts['older_sibling']) {
				print('<section class="block keep-reading" id="siblings">');
				print('<h2 class="block-title">Keep reading</h2>');
				$navigation->DisplayNextPrevious();
				print('</section>');
			}

			$comments->Display();

			print('</div></article>');

					// The sidebar

				// -------------------------------------------------------------

			print('<aside class="side" aria-label="About this text">');

			$record->DisplayRecord();

			print('<section class="panel" id="formats" aria-labelledby="formats-title">');
			print('<h2 class="panel-title" id="formats-title">Download or listen</h2>');
			$formats->DisplayFormats();
			print('</section>');

			if($that->counts['younger_sibling'] || $that->counts['older_sibling']) {
				print('<section class="panel" aria-labelledby="nearby-title">');
				print('<h2 class="panel-title" id="nearby-title">Nearby in ' . $that->parent['Title'] . '</h2>');
				$navigation->DisplayNearby();
				print('</section>');
			}

			$record->DisplayCitation();

			if($that->entry['association'] && $that->counts['association']) {
				print('<div class="panel">');
				$association->Display($this->authors ? ['max'=>$this->authors] : []);
				print('</div>');
			}

			if($that->entry['tag'] && $that->counts['tag']) {
				print('<div class="panel">');
				$tags = new module_entrytag(['that'=>$that]);
				$tags->Display([]);
				print('</div>');
			}

			print('<section class="panel" aria-labelledby="share-title">');
			print('<h2 class="panel-title" id="share-title">Share</h2>');
			$share->DisplaySmall();
			print('</section>');

			print('</aside>');
			print('</div>');

			$footer = new module_navigation([
				'globals'=>$that->handler->globals,
				'languageobject'=>$that->language_object,
				'domainobject'=>$that->domain_object,
			]);
			$footer->DisplayBottomNavigation(['thispage'=>'']);

			return TRUE;
		}

		public function DisplayAdminControls($args) {
			$controls = new module_entrycontrols;

			if($this->that->authentication_object->user_session['UserAdmin.id']) {
				$controls->Display(['that'=>$this->that, 'file'=>$args['file']]);
			}

			$controls->showNotPublishedNote(['that'=>$this->that]);

			return TRUE;
		}

			/*
				For a reader who is not signed in -- the page the cache serves
				to everyone -- an invitation to join in.  A signed-in reader has
				the votes and the discussion right there already.
			*/

		public function DisplayInvitation() {
			if($this->that->handler->authentication->user_session) {
				return FALSE;
			}

			print('<div class="nudge">');
			print('<div>');
			print('<h3>Was this worth reading?</h3>');
			print('<p>Sign in to upvote it, so other readers can find it, and to join the discussion.</p>');
			print('</div>');
			print('<div class="nudge-actions">');
			print('<a class="btn btn-primary" href="/login.php" rel="nofollow">Sign in</a>');
			print('<a class="btn btn-line" href="#comments">Discussion</a>');
			print('</div>');
			print('</div>');

			return TRUE;
		}
	}

?>
