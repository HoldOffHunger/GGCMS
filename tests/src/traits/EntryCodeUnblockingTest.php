<?php

	ggreq('traits/scripts/SimpleForms.php');

		// The code generator with modify.php's database questions answered from a list of sibling entries
	class EntryCodeUnblockingTestSubject {
		use SimpleForms;

		public $entry;
		public $entry_unset;
		public $master_record = ['id'=>1];
		public $tag = [];
		public $quote = [['Quote'=>'']];
		public $description = [['Description'=>'']];
		public $eventdate = [];
		public $siblings = [];

		public function __construct($args) {
			$this->entry = ['id'=>'', 'Title'=>$args['title'], 'Subtitle'=>$args['subtitle'] ?? ''];
			$this->entry_unset = ['id'=>'', 'association'=>[['entry'=>$args['author']]]];
			$this->eventdate = $args['eventdate'] ?? [];
			$this->siblings = $args['siblings'] ?? [];
		}

		public function isUserAdmin() {
			return TRUE;
		}

		public function isWordCommon($args) {
			return in_array($args['word'], ['a', 'an', 'and', 'by', 'in', 'is', 'of', 'or', 'the', 'to'], TRUE);
		}

		public function EntryCodeHolder($args) {
			foreach($this->siblings as $sibling) {
				if($sibling['Code'] === $args['code']) {
					return $sibling;
				}
			}

			return FALSE;
		}
	}

		// RevoltLib's four pairs, 24 Oct 2026: each second entry had been given the first one's code, and couldn't be reached
	class EntryCodeUnblockingTest extends GGCMSTestCase {
		const MORRIS = ['id'=>9, 'Title'=>'William Morris', 'ListTitle'=>'Morris, William', 'Code'=>'william-morris'];
		const WILSON = ['id'=>8, 'Title'=>'Charlotte Wilson', 'ListTitle'=>'Wilson, Charlotte', 'Code'=>'charlotte-wilson'];
		const TOLSTOY = ['id'=>7, 'Title'=>'Leo Tolstoy', 'ListTitle'=>'Tolstoy, Leo', 'Code'=>'leo-tolstoy'];
		const PANNEKOEK = ['id'=>6, 'Title'=>'Anton Pannekoek', 'ListTitle'=>'Pannekoek, Anton', 'Code'=>'anton-pannekoek'];

		public function subject($title, $subtitle, $author, $eventdate = [], $siblings = []) {
			return new EntryCodeUnblockingTestSubject(['title'=>$title, 'subtitle'=>$subtitle, 'author'=>$author,
				'eventdate'=>$eventdate, 'siblings'=>$siblings]);
		}

		public function pair($first, $second, $author, $eventdates = [[], []]) {
			$held = $this->subject($first[0], $first[1], $author, $eventdates[0]);
			$holder = ['id'=>100, 'Title'=>$first[0], 'Subtitle'=>$first[1], 'Code'=>$held->GenerateEntryCode()];
			$plain = $this->subject($second[0], $second[1], $author, $eventdates[1])->GenerateEntryCode();
			$unblocked = $this->subject($second[0], $second[1], $author, $eventdates[1], [$holder])->GenerateUnblockedEntryCode();

			return [$holder['Code'], $plain, $unblocked];
		}

		public function testAnUnheldCodeIsLeftAlone() {
			$subject = $this->subject('Honesty is the Best Policy', 'Or, The Inconvenience of Stealing, Part 1', self::MORRIS);
			$this->assertSame($subject->GenerateEntryCode(), $subject->GenerateUnblockedEntryCode());
		}

		public function testPartsKeepTheirNumbers() {
			[$held, $plain, $unblocked] = $this->pair(['Honesty is the Best Policy', 'Or, the Inconvenience of Stealing, Part 2'],
				['Honesty is the Best Policy', 'Or, The Inconvenience of Stealing, Part 1'], self::MORRIS);

			$this->assertSame($held, $plain, 'the old generator gives both parts one code');
			$this->assertNotSame($held, $unblocked);
			$this->assertStringContainsString('part-1', $unblocked);
			$this->assertStringStartsWith('honesty-', $unblocked);
			$this->assertStringEndsWith('-morris-william', $unblocked);
		}

		public function testInstalmentsKeepTheirMonths() {
			$june = [['Title'=>'Publication', 'EventDate'=>'1889-06-01']];
			$september = [['Title'=>'Publication', 'EventDate'=>'1889-09-01']];
			[$held, $plain, $unblocked] = $this->pair(
				['Revolt of the English Workers in the Nineteenth Century, The, Continued [Sep, 1889]', ''],
				['Revolt of the English Workers in the Nineteenth Century, The, Continued [Jun, 1889]', ''], self::WILSON,
				[$september, $june]);

			$this->assertSame($held, $plain);
			$this->assertNotSame($held, $unblocked);
			$this->assertStringContainsString('-jun-', $unblocked);
			$this->assertStringStartsWith('revolt-', $unblocked);
		}

		public function testTranslationsKeepTheirTranslators() {
			$published = [['Title'=>'Publication', 'EventDate'=>'1885-00-00']];
			[$held, $plain, $unblocked] = $this->pair(['The Two Brothers and the Gold', 'Translated by L. Wiener'],
				['The Two Brothers and the Gold', 'Translated by R. N. Bain'], self::TOLSTOY, [$published, $published]);

			$this->assertSame($held, $plain);
			$this->assertNotSame($held, $unblocked);
			$this->assertStringContainsString('by-r-', $unblocked);
		}

		public function testNothingToChopLeavesTheCodeForValidationToRefuse() {
			[$held, $plain, $unblocked] = $this->pair(["Workers' Councils", ''], ['Workers Councils', ''], self::PANNEKOEK);

			$this->assertSame($held, $plain);
			$this->assertSame($held, $unblocked, 'no word differs, so "already in use" is still the answer');
		}
	}

?>
