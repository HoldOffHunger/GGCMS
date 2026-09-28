<?php

	/*
		Handler's constructor opens a database and loads a site, so these
		build one without it and test the methods that need neither.
		Handler::__destruct() closes the database, so it is handed a
		connection that has nothing to close.
	*/

	use PHPUnit\Framework\Attributes\RunInSeparateProcess;

	class HandlerTest extends GGCMSTestCase {
		public function newHandler() {
			$handler = $this->newWithoutConstructor(['class'=>'Handler']);
			$handler->db_access = new class { public function DBEnd() { return TRUE; } };

			return $handler;
		}

		public function testQueryStringNeedsRepair() {
			$handler = $this->newHandler();

			$this->assertTrue($handler->QueryStringNeedsRepair(['querystring'=>'mobilefriendly=1?action=browse']));
			$this->assertFalse($handler->QueryStringNeedsRepair(['querystring'=>'mobilefriendly=1&action=browse']));
			$this->assertFalse($handler->QueryStringNeedsRepair(['querystring'=>'']));
		}

		public function testRepairQueryString() {
			$this->assertSame('mobilefriendly=1&mobilefriendly=1&action=browse', $this->newHandler()->RepairQueryString(['querystring'=>'mobilefriendly=1?mobilefriendly=1?action=browse']));
		}

		public function testConstruct_RepairQueryString() {
			$handler = $this->newHandler();

			$_SERVER['QUERY_STRING'] = 'mobilefriendly=1?action=browse';
			$_GET = ['mobilefriendly'=>'1?action=browse'];

			$this->assertTrue($handler->Construct_RepairQueryString());
			$this->assertSame(['mobilefriendly'=>'1', 'action'=>'browse'], $_GET);
			$this->assertSame('mobilefriendly=1&action=browse', $_SERVER['QUERY_STRING']);
			$this->assertSame(['mobilefriendly'=>'1?action=browse'], $handler->original_get, 'what arrived is kept');

			$_SERVER['QUERY_STRING'] = 'a=1&b=2';

			$this->assertFalse($handler->Construct_RepairQueryString(), 'a well-formed query is left alone');
		}

			/*
				The site's configuration is require()d from a path built out of
				the domain.  A domain that reverses into ../ must never get
				there; it falls back to defaultglobals.  Runs alone because
				clonefrom.php declares classes.
			*/

		#[RunInSeparateProcess]
		public function testConstruct_Globals() {
			$handler = $this->newHandler();
			$handler->domain = new Domain(['handler'=>$handler]);
			$handler->domain->primary_domain_lowercased = $handler->ReverseDomainName(['domain'=>'../../../GGCMS/usr/lib/ggcms/cli/system/RepoDirectories']);

			$handler->Construct_Globals();

			$this->assertSame('defaultglobals', get_class($handler->globals), 'a path is not a site');
		}

		#[RunInSeparateProcess]
		public function testConstruct_GlobalsLoadsTheSite() {
			$handler = $this->newHandler();
			$handler->domain = new Domain(['handler'=>$handler]);
			$handler->domain->primary_domain_lowercased = 'revoltlib.com';

			$handler->Construct_Globals();

			$this->assertSame('globals', get_class($handler->globals), 'a real site loads its own configuration');
		}
	}

?>
