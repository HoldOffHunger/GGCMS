<?php

	require_once(GGCMS_DIR . 'modules/html/entry-child.php');

		/*
			The sections of a collection's index page: a handful of its
			entries drawn at random, then its pictures, tags, quotes,
			descriptions, texts, dates and best-liked entries, each a few at
			random.  The script fills children_random, images_random,
			tags_random, quotes_random, descriptions_random,
			textbodies_random, eventdates_random and likes_random; each
			section prints nothing when its list is empty.

			The same sections were pasted into the index templates of most
			sites, a hundred lines apiece.  The heading of each is the
			template's own, in its own voice, so it is passed in; how the
			dates read is the one real difference between copies, and is a
			switch.

			Switches:
			  eventstyle  'works' (default) reads "Publication of The Title :
			              Subtitle."; 'people' reads "Birth Day of an
			              Anarchist Writer." from the person's subtitle
			  children    the module_entrychild switches for the random
			              entries, as the template passed them before
		*/

	class module_indexsections extends module_spacing {
		public $that;
		public $event_style;
		public $children_switches;

		public function __construct($args) {
			$this->that = $args['that'];
			$this->event_style = $args['eventstyle'] ?? 'works';
			$this->children_switches = $args['children'] ?? [];
		}

		public function Open($args) {
			print('<section class="block index-section ' . $args['class'] . '">');
			print('<h2 class="block-title">' . $args['header'] . '</h2>');

			return TRUE;
		}

		public function Close() {
			print('</section>');

			return TRUE;
		}

		public function EntryURL($args) {
			return $args['code'] . '/view.php';
		}

		public function EntryTitle($args) {
			$item = $args['item'];

			return $item['Entry.Title'] . ($item['Entry.Subtitle'] ? ' : ' . $item['Entry.Subtitle'] : '');
		}

			// A handful of entries, as cards
			// -------------------------------------------------------------

		public function DisplayChildren($args) {
			if(!$this->that->children_random || !count($this->that->children_random)) {
				return FALSE;
			}

			$this->Open(['class'=>'children index-children', 'header'=>$args['header']]);
			print('<div class="entry-list">');

			foreach($this->that->children_random as $child) {
				$entry_child = new module_entrychild(['that'=>$this->that, 'child'=>$child] + $this->children_switches);
				$entry_child->Display();
			}

			print('</div>');
			$this->Close();

			return TRUE;
		}

			// Pictures
			// -------------------------------------------------------------

		public function DisplayImages($args) {
			if(!$this->that->images_random || !count($this->that->images_random)) {
				return FALSE;
			}

			$this->Open(['class'=>'gallery', 'header'=>$args['header']]);
			print('<ul class="gallery-grid">');

			foreach($this->that->images_random as $image) {
				$title = $this->EntryTitle(['item'=>$image]);

				print('<li><a href="' . $this->EntryURL(['code'=>$image['Entry.Code']]) . '" title="' . htmlspecialchars(strip_tags($title), ENT_QUOTES, 'UTF-8') . '">');
				print('<img alt="' . htmlspecialchars(strip_tags($title), ENT_QUOTES, 'UTF-8') . '" loading="lazy" width="' . ceil($image['IconPixelWidth']) . '" height="' . ceil($image['IconPixelHeight']) . '" src="/image/' . implode('/', str_split($image['FileDirectory'])) . '/' . $image['IconFileName'] . '">');
				print('</a></li>');
			}

			print('</ul>');
			$this->Close();

			return TRUE;
		}

			// Tags
			// -------------------------------------------------------------

		public function DisplayTags($args) {
			if(!$this->that->tags_random || !count($this->that->tags_random)) {
				return FALSE;
			}

			$this->Open(['class'=>'tags', 'header'=>$args['header']]);
			print('<ul class="chips">');

			foreach($this->that->tags_random as $tag) {
				$count = (int) $this->that->tag_counts[$tag['Tag']];

				print('<li><a href="/view.php?action=browseByTag&amp;tag=' . urlencode($tag['Tag']) . '">' . $tag['Tag']);

				if($count > 1) {
					print(' <span class="chip-count">' . number_format($count) . '</span>');
				}

				print('</a></li>');
			}

			print('</ul>');
			$this->Close();

			return TRUE;
		}

			// Quotes, descriptions and texts: a passage, linked to its entry
			// -------------------------------------------------------------

		public function DisplayQuotes($args) {
			if(!$this->that->quotes_random || !count($this->that->quotes_random)) {
				return FALSE;
			}

			$this->Open(['class'=>'passages quotes', 'header'=>$args['header']]);
			print('<div class="passage-grid">');

			foreach($this->that->quotes_random as $quote) {
				$this->DisplayPassage([
					'item'=>$quote,
					'text'=>'&ldquo;' . $quote['Quote'] . '&rdquo;',
					'class'=>'passage-quote',
				]);
			}

			print('</div>');
			$this->Close();

			return TRUE;
		}

		public function DisplayDescriptions($args) {
			if(!$this->that->descriptions_random || !count($this->that->descriptions_random)) {
				return FALSE;
			}

			$this->Open(['class'=>'passages', 'header'=>$args['header']]);
			print('<div class="passage-grid">');

			foreach($this->that->descriptions_random as $description) {
				$this->DisplayPassage([
					'item'=>$description,
					'text'=>$description['Description'],
					'class'=>'passage-description',
				]);
			}

			print('</div>');
			$this->Close();

			return TRUE;
		}

		public function DisplayTexts($args) {
			if(!$this->that->textbodies_random || !count($this->that->textbodies_random)) {
				return FALSE;
			}

			$this->Open(['class'=>'passages', 'header'=>$args['header']]);
			print('<div class="passage-grid">');

			foreach($this->that->textbodies_random as $textbody) {
				$this->DisplayPassage([
					'item'=>$textbody,
					'text'=>$this->that->cleanser_object->FormatListOutput(['text'=>substr($textbody['Text'], 0, 500)]),
					'class'=>'passage-text',
				]);
			}

			print('</div>');
			$this->Close();

			return TRUE;
		}

		public function DisplayPassage($args) {
			$item = $args['item'];
			$url = $this->EntryURL(['code'=>$item['Entry.Code']]);

			print('<figure class="passage ' . $args['class'] . '">');
			print('<blockquote><a href="' . $url . '">' . $args['text'] . '</a></blockquote>');
			print('<figcaption><a href="' . $url . '">' . $this->EntryTitle(['item'=>$item]) . '</a></figcaption>');
			print('</figure>');

			return TRUE;
		}

			// Dates
			// -------------------------------------------------------------

		public function DisplayEventDates($args) {
			if(!$this->that->eventdates_random || !count($this->that->eventdates_random)) {
				return FALSE;
			}

			$this->Open(['class'=>'chronology', 'header'=>$args['header']]);
			print('<ol class="timeline">');

			foreach($this->that->eventdates_random as $eventdate) {
				[$event_date, $event_time] = array_pad(explode(' ', $eventdate['EventDateTime']), 2, '00:00:00');
				$url = $this->EntryURL(['code'=>$eventdate['Entry.Code']]);

				print('<li class="timeline-item">');
				print('<time class="timeline-date">');

				if($event_date != '0000-00-00') {
					print(date("F j, Y", strtotime($event_date)));
				}

				if($event_time != '00:00:00') {
					print(' ' . $event_time);
				}

				print('</time> ');
				print('<a class="timeline-text" href="' . $url . '">' . $eventdate['Title'] . ' of ' . $this->EventSubject(['eventdate'=>$eventdate]) . '.</a>');
				print('</li>');
			}

			print('</ol>');
			$this->Close();

			return TRUE;
		}

			/*
				What the date belongs to.  A work is named; a person is
				described, by the subtitle their page gives them -- "an
				Anarchist Writer" -- falling back to their name.
			*/

		public function EventSubject($args) {
			$eventdate = $args['eventdate'];

			if($this->event_style !== 'people') {
				return $this->EntryTitle(['item'=>$eventdate]);
			}

			$subject = $eventdate['Entry.Subtitle'] ? $eventdate['Entry.Subtitle'] : $eventdate['Entry.Title'];
			$article = strpos('aeiou', strtolower(substr($subject, 0, 1))) !== FALSE ? 'an ' : 'a ';

			return $article . $subject;
		}

			// The best-liked
			// -------------------------------------------------------------

		public function DisplayLikes($args) {
			if(!$this->that->likes_random || !count($this->that->likes_random)) {
				return FALSE;
			}

			$this->Open(['class'=>'likes', 'header'=>$args['header']]);
			print('<ol class="ledger ledger-likes">');

			foreach($this->that->likes_random as $like) {
				$url = $this->EntryURL(['code'=>$like['Entry.Code']]);

				print('<li>');
				print('<span class="ledger-date">' . number_format($like['counts']['likes']) . ' upvotes</span>');
				print('<a class="ledger-title" href="' . $url . '">' . $this->EntryTitle(['item'=>$like]) . '</a>');
				print('</li>');
			}

			print('</ol>');
			$this->Close();

			return TRUE;
		}
	}

?>
