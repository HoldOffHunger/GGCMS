<?php

	require_once(GGCMS_DIR . 'modules/html/header.php');
	require_once(GGCMS_DIR . 'modules/html/divider.php');

		/*
			The top of an index page under its bar: a box with how many
			entries it holds -- "Total Radical Books : 12", hovering one of
			its quotes at random -- then its introduction, the entry's text,
			with how many texts, words and characters lie below it.
		*/

	class module_indexintroduction extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function Display() {
			$this->DisplayTotal();
			$this->DisplayIntroduction();

			return TRUE;
		}

		public function DisplayTotal() {
			$entry = $this->that->entry;
			$child_noun_plural = $entry['ChildNounPlural'] ? $entry['ChildNounPlural'] : 'Works';

			$header = new module_header;
			$header->display([
				'title'=>'Total ' . $entry['ChildAdjective'] . ' ' . $child_noun_plural . ' : ' . $this->that->children_count ,
				'divmouseover'=>$this->QuoteHover(),
				'level'=>3,
				'divclass'=>'width-33percent border-2px background-color-gray13 margin-5px',
				'textclass'=>'padding-0px margin-5px horizontal-center vertical-center font-family-arial',
				'imagedivclass'=>'border-2px margin-5px background-color-gray10',
				'imageclass'=>'border-1px',
				'domainobject'=>$this->that->domain_object,
				'leftimageenable'=>0,
				'rightimageenable'=>0,
			]);

			return TRUE;
		}

		public function QuoteHover() {
			$quotes = $this->that->entry['quote'];

			if(!$quotes || !$quotes[0]) {
				return '';
			}

			$random_quote = $quotes[array_rand($quotes, 1)];

			return '&quot;' . str_replace('"', '\'', $random_quote['Quote']) . '&quot; -- ' . str_replace('"', '\'', $random_quote['Source']);
		}

		public function DisplayIntroduction() {
			$stats = $this->that->child_record_stats;

			print('<center>');
			print('<div class="horizontal-center width-70percent">');
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');

			print('<strong>');
			print(str_replace('<p>', '<p class="horizontal-left margin-5px font-family-tahoma">', $this->that->entry['textbody'][0]['Text']));

			print('<p class="horizontal-left margin-5px font-family-tahoma" title="');
			print(' (Last Updated: ' . date("F d, Y; H:i:s", strtotime($stats['LastModificationDate'])) . '.)');
			print('">');
			print('This archive contains ' . number_format($stats['ChildRecordCount']) . ' texts, with ' . number_format($stats['ChildWordCount']) . ' words or ' . number_format($stats['ChildCharacterCount']) . ' characters.');

			print('</strong>');
			print('</p>');

			print('</div>');
			print('</div>');
			print('</center>');

			$divider = new module_divider;
			$divider->displaystart(['class'=>'clear-float']);
			$divider->displayend([]);

			return TRUE;
		}
	}

?>
