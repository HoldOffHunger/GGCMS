<?php

	/*
		The decisions ChildRecordCoverage makes once the counts are in: which
		state a table or column is in, which rows are printed, and which
		entries are counted.  None of it needs a database; the counting itself
		is checked by running the tool against Fumiko's copies.
	*/

	class ChildRecordCoverageTest extends GGCMSTestCase {
		public function newCoverage($args = []) {
			$this->requireCLI(['file'=>'classes/Database/ChildRecordCoverage.php']);

			$coverage = new ChildRecordCoverage([
				'argv'=>$args['argv'] ?? ['show_child_record_coverage.php'],
			]);

			ob_start();
			$coverage->setArguments();
			ob_end_clean();

			return $coverage;
		}

		public function testSetArguments() {
			$coverage = $this->newCoverage(['argv'=>['show_child_record_coverage.php', '--database=revoltlib, earthfluent', '--table=Quote', '--of=text', '--threshold=75', '--json']]);

			$this->assertSame(['revoltlib', 'earthfluent'], $coverage->arguments['database']);
			$this->assertSame(['Quote'], $coverage->arguments['table']);
			$this->assertSame('text', $coverage->arguments['of']);
			$this->assertSame(75, $coverage->arguments['threshold']);
			$this->assertTrue($coverage->arguments['json']);
			$this->assertFalse($coverage->arguments['fields']);
		}

		public function testSetArgumentsGapsImpliesFields() {
			$coverage = $this->newCoverage(['argv'=>['show_child_record_coverage.php', '--gaps']]);

			$this->assertTrue($coverage->arguments['gaps']);
			$this->assertTrue($coverage->arguments['fields']);
		}

		public function testSetArgumentsRefusesAnUnknownDenominator() {
			$coverage = $this->newCoverage(['argv'=>['show_child_record_coverage.php', '--of=chapters', '--threshold=400']]);

			$this->assertSame('leaves', $coverage->arguments['of']);
			$this->assertSame(100, $coverage->arguments['threshold']);
		}

		public function testTableState() {
			$coverage = $this->newCoverage();

			$cases = [
				[['table'=>'Quote', 'enabled'=>TRUE, 'rows'=>14062, 'covered'=>14000, 'candidates'=>15092], 'used'],
				[['table'=>'Tag', 'enabled'=>TRUE, 'rows'=>84970, 'covered'=>749, 'candidates'=>15092], 'underused'],
				[['table'=>'Suggestion', 'enabled'=>TRUE, 'rows'=>0, 'covered'=>0, 'candidates'=>15092], 'unused'],
				[['table'=>'Price', 'enabled'=>FALSE, 'rows'=>0, 'covered'=>0, 'candidates'=>15092], 'off'],
				[['table'=>'AvailabilityDateRange', 'enabled'=>FALSE, 'rows'=>29, 'covered'=>8, 'candidates'=>15092], 'unusual'],
				[['table'=>'Comment', 'enabled'=>TRUE, 'rows'=>27, 'covered'=>9, 'candidates'=>15092], 'used'],
				[['table'=>'Quote', 'enabled'=>TRUE, 'rows'=>5, 'covered'=>0, 'candidates'=>0], 'used'],
			];

			foreach($cases as [$args, $expected]) {
				$this->assertSame($expected, $coverage->tableState($args), $args['table']);
			}
		}

		public function testTableStateAtTheThreshold() {
			$coverage = $this->newCoverage();

			$this->assertSame('used', $coverage->tableState(['table'=>'Quote', 'enabled'=>TRUE, 'rows'=>90, 'covered'=>90, 'candidates'=>100]));
			$this->assertSame('underused', $coverage->tableState(['table'=>'Quote', 'enabled'=>TRUE, 'rows'=>89, 'covered'=>89, 'candidates'=>100]));
		}

		public function testTableNote() {
			$coverage = $this->newCoverage();

			$this->assertSame('a worker could fill it', $coverage->tableNote(['state'=>'underused', 'table'=>'Description']));
			$this->assertSame('the engine\'s own: stale?', $coverage->tableNote(['state'=>'underused', 'table'=>'ChildRecordStats']));
			$this->assertSame('thin, but not a worker\'s to fill', $coverage->tableNote(['state'=>'underused', 'table'=>'Price']));
			$this->assertSame('off, yet holds rows: on purpose?', $coverage->tableNote(['state'=>'unusual', 'table'=>'Image']));
			$this->assertSame('', $coverage->tableNote(['state'=>'used', 'table'=>'Quote']));
		}

		public function testFieldState() {
			$coverage = $this->newCoverage();

			$this->assertSame('empty', $coverage->fieldState(['filled'=>0, 'total'=>2942]));
			$this->assertSame('sparse', $coverage->fieldState(['filled'=>8, 'total'=>2942]));
			$this->assertSame('filled', $coverage->fieldState(['filled'=>2942, 'total'=>2942]));
		}

		public function testFieldRows() {
			$coverage = $this->newCoverage();

			$rows = $coverage->fieldRows([
				'database'=>'revoltlib',
				'table'=>'Entry',
				'columns'=>['Subtitle', 'ChildNoun'],
				'counts'=>['RowTotal'=>15092, 'FilledSubtitle'=>4548, 'FilledChildNoun'=>0],
				'fillable'=>TRUE,
			]);

			$this->assertSame('Entry.Subtitle', $rows[0]['Subject']);
			$this->assertSame('sparse', $rows[0]['State']);
			$this->assertSame(10544, $rows[0]['Missing']);
			$this->assertSame('a worker could fill it', $rows[0]['Note']);

			$this->assertSame('by design', $rows[1]['State'], 'a parent\'s column, blank on a leaf');
			$this->assertSame('only on parents', $rows[1]['Note']);
		}

		public function testWantsRow() {
			$coverage = $this->newCoverage();

			$this->assertTrue($coverage->wantsRow(['row'=>['State'=>'underused', 'Note'=>'']]));
			$this->assertTrue($coverage->wantsRow(['row'=>['State'=>'unusual', 'Note'=>'']]));
			$this->assertFalse($coverage->wantsRow(['row'=>['State'=>'used', 'Note'=>'']]));
			$this->assertFalse($coverage->wantsRow(['row'=>['State'=>'by design', 'Note'=>'']]));

			$gaps = $this->newCoverage(['argv'=>['show_child_record_coverage.php', '--gaps']]);

			$this->assertTrue($gaps->wantsRow(['row'=>['State'=>'sparse', 'Note'=>'a worker could fill it']]));
			$this->assertFalse($gaps->wantsRow(['row'=>['State'=>'underused', 'Note'=>'thin, but not a worker\'s to fill']]));
			$this->assertFalse($gaps->wantsRow(['row'=>['State'=>'unused', 'Note'=>'on, never used: switch off, or use it']]));
		}

		public function testCandidateQuery() {
			$leaves = $this->newCoverage();
			$query = $leaves->candidateQuery(['database'=>'revoltlib`; DROP']);

			$this->assertStringContainsString('`revoltlibDROP`.`Entry`', $query, 'the identifier is escaped');
			$this->assertStringContainsString('Publish = 1', $query);
			$this->assertStringContainsString('NOT EXISTS', $query);

			$text = $this->newCoverage(['argv'=>['show_child_record_coverage.php', '--of=text']]);
			$this->assertStringContainsString('`TextBody`', $text->candidateQuery(['database'=>'revoltlib']));

			$all = $this->newCoverage(['argv'=>['show_child_record_coverage.php', '--of=all']]);
			$this->assertStringNotContainsString('Publish', $all->candidateQuery(['database'=>'revoltlib']));
		}

		public function testPercent() {
			$coverage = $this->newCoverage();

			$this->assertSame('0.6%', $coverage->percent(['part'=>90, 'whole'=>15092]));
			$this->assertSame('-', $coverage->percent(['part'=>0, 'whole'=>0]));
		}

		public function testReadFlag() {
			$coverage = $this->newCoverage();

			$configuration = "public function Quote_enabled() {\n\treturn TRUE;\n}\npublic function Image_enabled() { return false; }";

			$this->assertTrue($coverage->readFlag(['configuration'=>$configuration, 'table'=>'Quote']));
			$this->assertFalse($coverage->readFlag(['configuration'=>$configuration, 'table'=>'Image']));
			$this->assertNull($coverage->readFlag(['configuration'=>$configuration, 'table'=>'Tag']), 'unmentioned is not off');
		}

		public function testSwitchedTypes() {
			$coverage = $this->newCoverage();

			if(!is_file($coverage->childTypesDefaultLocation())) {
				$this->markTestSkipped('No configuration checkout to read clonefrom\'s switches from.');
			}

			$types = $coverage->switchedTypes();

			foreach(['Quote', 'Tag', 'Image', 'Description', 'EventDate'] as $type) {
				$this->assertContains($type, $types);
			}

			$this->assertSame(count($types), count(array_unique($types)));
		}
	}

?>
