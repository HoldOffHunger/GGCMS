<?php

	/*
		HandlerEntryPath is Handler's entry-path stage: does this path walk
		the entry graph.  It keeps no state, so each test builds a Handler
		without its constructor and hands it over, with an ORM that answers
		from a table rather than a database.
	*/

	class HandlerEntryPathTest extends GGCMSTestCase {
		protected function setUp(): void {
			parent::setUp();

			$this->requireEngine(['file'=>'classes/Networking/Handler/HandlerEntryPath.php']);
		}

			// Resolves every code it is given, bar any named in $missing
		public function newEntryPath($args) {
			$handler = $this->newWithoutConstructor(['class'=>'Handler']);
			$handler->db_access = new class { public function DBEnd() { return TRUE; } };
			$handler->object_list = $args['codes'];
			$handler->script_name = $args['script'];

			$handler->orm = new class {
				public $missing = [];

				public function GetRecordTree($args) {
					$records = [];

					foreach($args['codelist'] as $code) {
						if(in_array($code, $this->missing, TRUE)) {
							break;
						}

						$records[] = ['Code'=>$code];
					}

					return $records;
				}
			};

			$handler->orm->missing = $args['missing'] ?? [];

			return $handler->entry_path_handler = new HandlerEntryPath(['handler'=>$handler]);
		}

			/*
				A path names entries only when it is a view.php walk that
				resolved in full.  Everything EntryPathResolves waves through
				without asking -- the front page, another script, no database
				-- names nothing here.
			*/

		public function testEntryPathNamesEntries() {
			$codes = ['ecological-struggle', 'animal-rights-groups', 'action-against-poisoning-(aap)'];

			$this->assertTrue($this->newEntryPath(['codes'=>$codes, 'script'=>'view.php'])->EntryPathNamesEntries(), 'a bracketed code that exists');

			$this->assertFalse($this->newEntryPath(['codes'=>$codes, 'script'=>'view.php', 'missing'=>['action-against-poisoning-(aap)']])->EntryPathNamesEntries(), 'a walk that stops short');
			$this->assertFalse($this->newEntryPath(['codes'=>[], 'script'=>'view.php'])->EntryPathNamesEntries(), 'the front page');
			$this->assertFalse($this->newEntryPath(['codes'=>$codes, 'script'=>'sitemap.php'])->EntryPathNamesEntries(), 'another script');

			$entry_path = $this->newEntryPath(['codes'=>$codes, 'script'=>'view.php']);
			$entry_path->handler->db_access = NULL;

			$this->assertFalse($entry_path->EntryPathNamesEntries(), 'no database to ask');
		}
	}

?>
