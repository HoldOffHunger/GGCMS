<?php

	/*
		One child entry in a listing: its thumbnail, its linked title in a
		header box, the publication year and length, the description, up to
		three quotes or else the start of its text, and up to ten tags.

		This block was pasted into some fifty child and grandchild loops
		across the site templates, about three hundred lines a time, and the
		copies drifted.  The differences that survived are real choices, so
		they are switches here rather than copies there:

			'thumbnail'       'square' (default) half-size icon in a 100px box,
			                  or 'banner', a fixed 200 by 50
			'linked'          FALSE shows the title and image without links
			'imagedirectory'  TRUE puts the image's FileDirectory in its path
			'fallbackimage'   '' (default), 'association' for the page's
			                  first associated entry's image, or 'master' for
			                  the master record's
			'excerpt'         'plain' (default), the first 750 characters with
			                  tags stripped, or 'formatted', through the
			                  cleanser's FormatListOutput()
			'rootlinks'       TRUE starts tag links with /

		Display() prints the whole entry.  A template that puts something of
		its own between the parts -- a quiz link, a listen button -- calls
		DisplayStart(), DisplayHeader(), DisplayDetails(), DisplayTags() and
		DisplayEnd() itself, with its own lines between them.
	*/

	class module_entrychild extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			$this->child = $args['child'];

			$this->thumbnail = $args['thumbnail'] ?? 'square';
			$this->linked = $args['linked'] ?? TRUE;
			$this->image_directory = $args['imagedirectory'] ?? FALSE;
			$this->fallback_image = $args['fallbackimage'] ?? '';
			$this->excerpt = $args['excerpt'] ?? 'plain';
			$this->root_links = $args['rootlinks'] ?? FALSE;

			if(!class_exists('module_header')) {
				ggreq('modules/html/header.php');
			}

			if(!class_exists('module_divider')) {
				ggreq('modules/html/divider.php');
			}

			$this->header = new module_header;
			$this->divider = new module_divider;

			return $this;
		}

		public function Display() {
			$this->DisplayStart();
			$this->DisplayHeader();
			$this->DisplayDetails();
			$this->DisplayTags();
			$this->DisplayEnd();

			return TRUE;
		}

			// Start: the frame and the thumbnail
			// -------------------------------------------------------------

		public function DisplayStart() {
			print('<div class="horizontal-center width-100percent background-color-gray14 border-2px margin-top-5px">');

			$display_image = $this->DisplayImage();

			if(!empty($display_image)) {
				$this->DisplayThumbnail(['image'=>$display_image]);
			}

			return TRUE;
		}

		public function DisplayImage() {
			$child = $this->child;
			$display_image = NULL;

			if($child['image']) {
				$child_images = $child['image'];
				$child_image_count = count($child_images);

				if($child_image_count) {
					shuffle($child_images);
					$display_image = $child_images[0];
				}
			}

			if(!$display_image) {
				if($this->fallback_image === 'association') {
					$display_image = $this->that->entry['association'][0]['entry']['image'][0] ?? NULL;
				} elseif($this->fallback_image === 'master') {
					$display_image = $this->that->master_record['image'][0] ?? NULL;
				}
			}

			return $display_image;
		}

		public function DisplayThumbnail($args) {
			$display_image = $args['image'];
			$child = $this->child;

			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');

			if($this->thumbnail === 'banner') {
				print('<div class="background-color-gray0" style="width:200px;height:50px;">');
			} else {
				print('<div class="height-100px width-100px background-color-gray0">');
			}

			print('<div class="vertical-specialcenter">');

			if($this->linked) {
				print('<a href="' . $child['Code'] . '/view.php">');
			}

			print('<img width="');
			print($this->thumbnail === 'banner' ? 200 : ceil($display_image['IconPixelWidth'] / 2));
			print('" height="');
			print($this->thumbnail === 'banner' ? 50 : ceil($display_image['IconPixelHeight'] / 2));
			print('" src="');
			print($this->that->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
			print('/image/');

			if($this->image_directory) {
				print(implode('/', str_split($display_image['FileDirectory'])));
				print('/');
			}

			print($display_image['IconFileName']);
			print('">');

			if($this->linked) {
				print('</a>');
			}

			print('</div>');
			print('</div>');
			print('</div>');
			print('</div>');

			return TRUE;
		}

			// Header: the title, in a header box
			// -------------------------------------------------------------

		public function DisplayHeader() {
			$child = $this->child;

			$div_mouseover = '';

			if($child['textbody']) {
				$text_bodies = $child['textbody'];
				$text_body_count = count($text_bodies);

				if($text_body_count) {
					$first_textbody = $text_bodies[0];

					$div_mouseover .= number_format($first_textbody['WordCount']) . ' Words / ' . number_format($first_textbody['CharacterCount']) . ' Characters';
				}
			}

			$this->header->display([
				'title'=>$this->ChildTitle(),
				'divmouseover'=>$div_mouseover,
				'level'=>3,
				'divclass'=>'border-2px background-color-gray15 margin-5px float-left',
				'textclass'=>'padding-0px margin-5px horizontal-left font-family-tahoma',
				'imagedivclass'=>'border-2px margin-5px background-color-gray10',
				'imageclass'=>'border-1px',
				'domainobject'=>$this->that->domain_object,
				'leftimageenable'=>0,
				'rightimageenable'=>0,
			]);

			return TRUE;
		}

		public function ChildTitle() {
			$child = $this->child;

			$title_max = 50;
			$title_popup = 0;

			if($child['Subtitle']) {
				$title_max = 30;
			}

			$full_child_title = $child['Title'];

			if(strlen($full_child_title) > $title_max) {
				$full_child_title = substr($full_child_title, 0, $title_max) . '...';
				$title_popup = 1;
			}

			if($child['Subtitle']) {
				$full_child_title .= ' : ';

				$full_child_subtitle = $child['Subtitle'];

				if(strlen($full_child_subtitle) > $title_max) {
					$full_child_subtitle = substr($full_child_subtitle, 0, $title_max) . '...';
				}

				$full_child_title .= $full_child_subtitle;
				$title_popup = 1;
			}

			$child_title = $this->linked ? '<a href="' . $child['Code'] . '/view.php"' : '<span';

			if($title_popup) {
				$popup_title = $child['Title'];

				if($child['Subtitle']) {
					$popup_title .= ' : ';
					$popup_title .= $child['Subtitle'];
				}

				$child_title .= ' title="' . str_replace('"', '&quot;', $popup_title) . '"';
			}

			$child_title .= '>';
			$child_title .= $full_child_title;
			$child_title .= $this->linked ? '</a>' : '</span>';

			return $child_title;
		}

			// Details: year and length, description, quotes or text
			// -------------------------------------------------------------

		public function DisplayDetails() {
			print('<p class="horizontal-left margin-5px font-family-arial">');

			$time_frame = $this->TimeFrame();

			$this->DisplayTimeFrameAndLength(['timeframe'=>$time_frame]);
			$this->DisplayDescription(['timeframe'=>$time_frame]);

			if($this->child['quote']) {
				$this->DisplayQuotes();
			} else {
				$this->DisplayExcerpt();
			}

			print('</p>');

			$this->DisplayClearFloat();

			return TRUE;
		}

		public function TimeFrame() {
			$child = $this->child;
			$time_frame = '';
			$publication_event = NULL;

			if($child['eventdate']) {
				$child_event_count = count($child['eventdate']);

				for($i = 0; $i < $child_event_count; $i++) {
					$child_event = $child['eventdate'][$i];

					if($child_event['Title'] == 'Publication') {
						$publication_event = $child_event;
					}

					if($publication_event) {
						$i = $child_event_count;
					}
				}

				if($publication_event) {
					if($publication_event['EventDateTime'] != '0000-00-00 00:00:00') {
						$event_date_pieces = explode('-', $publication_event['EventDateTime']);
						$time_frame .= $event_date_pieces[0];
					} else {
						$time_frame .= '?';
					}
				}
			}

			return $time_frame;
		}

		public function DisplayTimeFrameAndLength($args) {
			$time_frame = $args['timeframe'];
			$child = $this->child;

			print('<strong>');

			if($time_frame) {
				print($time_frame);
			}

			if($child['textbody'] && count($child['textbody'])) {
				if($time_frame) {
					print(' ~ ');
				}

				$first_textbody = $child['textbody'][0];

				print('(');
				print(number_format($first_textbody['WordCount']));
				print(' Words / ');
				print(number_format($first_textbody['CharacterCount']));
				print(' Characters');
				print(')');
			}

			print('</strong> ');

			return TRUE;
		}

		public function DisplayDescription($args) {
			$time_frame = $args['timeframe'];
			$child = $this->child;

			if(!$child['description']) {
				return FALSE;
			}

			$description = $child['description'][0];

			if(!$description || !$description['Description']) {
				return FALSE;
			}

			print('<em>');

			if($time_frame || $child['Subtitle']) {
				print(' : ');
			}

			print($description['Description']);
			print(' ');
			print('</em>');

			$this->DisplaySource(['source'=>$description['Source']]);

			return TRUE;
		}

		public function DisplayQuotes() {
			$child_quotes = $this->child['quote'];
			$max_limit = min(count($child_quotes), 3);

			shuffle($child_quotes);

			for($i = 0; $i < $max_limit; $i++) {
				$quote = $child_quotes[$i];

				if($quote && $quote['Quote']) {
					print(' <br>&bull; ');
					print('"');
					print(str_replace('"', '\'', $quote['Quote']));
					print('"');

					$this->DisplaySource(['source'=>$quote['Source']]);
				}
			}

			return TRUE;
		}

		public function DisplayExcerpt() {
			$child = $this->child;

			if(!$child['textbody'] || !count($child['textbody'])) {
				return FALSE;
			}

			$first_textbody = $child['textbody'][0];

			if($this->excerpt === 'formatted') {
				$text_display = $this->that->cleanser_object->FormatListOutput([
					'text'=>$first_textbody['FirstThousandCharacters'],
				]);
			} else {
				$text_display = strip_tags($first_textbody['FirstThousandCharacters']);

				if(strlen($text_display) > 750) {
					$text_display = substr($text_display, 0, 750) . '...';
				}
			}

			if(!$text_display) {
				return FALSE;
			}

			print('<br>');
			print($text_display);

			$this->DisplaySource(['source'=>$first_textbody['Source']]);

			return TRUE;
		}

		public function DisplaySource($args) {
			$source = $args['source'];

			if(!$source) {
				return FALSE;
			}

			if(strlen($source) > 50) {
				$source = substr($source, 0, 50) . '...';
			}

			print(' (From : ' . $source . '.)');

			return TRUE;
		}

			// Tags, and the end of the frame
			// -------------------------------------------------------------

		public function DisplayTags() {
			$child = $this->child;

			if(!$child['tag'] || !count($child['tag'])) {
				return FALSE;
			}

			$tags = $child['tag'];
			$max_limit = min(count($tags), 10);

			shuffle($tags);

			for($i = 0; $i < $max_limit; $i++) {
				$tag = $tags[$i];

				print('<div class="border-2px background-color-gray15 margin-left-5px margin-bottom-5px float-left">');
				print('<span class="horizontal-left margin-5px font-family-arial">');
				print('<a href="' . ($this->root_links ? '/' : '') . 'view.php?action=browseByTag&tag=' . urlencode($tag['Tag']) . '">');
				print($tag['Tag']);
				print('</a>');
				print('</span>');
				print('</div>');
			}

			$this->DisplayClearFloat();

			return TRUE;
		}

		public function DisplayEnd() {
			print('</div>');

			return TRUE;
		}

		public function DisplayClearFloat() {
			$this->divider->displaystart([
				'class'=>'clear-float',
			]);

			$this->divider->displayend([
			]);

			return TRUE;
		}
	}

?>
