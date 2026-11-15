<?php

	/*
		What child_types/enabled.php says about a site, read the way
		AbstractGlobals reads it but without loading it.

		Moved out of SchemaChecker on 14 November 2026, when ChildRecordCoverage
		became the second tool that needs to know which child types a site
		switches on.  Two copies of the layering below would drift, and the
		layering is the part that has already been wrong once.

		The host class must also use ReverseDNSNotation.
	*/

	trait ChildTypeSwitches {

			/*
				The same two-step AbstractGlobals performs: the domain's own
				override when it has one, clonefrom's defaults when it does not.
				The domain is assumed to be <database>.com, which is true of
				every site on this host and is stated here rather than hidden,
				because the day it stops being true this check will quietly
				compare a site against the wrong file.

				The file is read rather than required.  Every domain override
				declares the same class name, which is correct for the engine --
				one request serves one domain -- and fatal here, where one
				process walks seventeen of them and the second require dies on
				"cannot declare class, the name is already in use".  A checker
				that has to load a site to inspect it can only inspect one site.
			*/

		public function getEnabledFlags($args) {
			$database = $args['database'];
			$tables = $args['tables'];

			$domain_location = $this->childTypesDomainLocation(['database'=>$database]);
			$default_location = $this->childTypesDefaultLocation();

			if(!is_file($default_location)) {
				return FALSE;
			}

			$default_configuration = file_get_contents($default_location);

			if($default_configuration === FALSE) {
				return FALSE;
			}

				/*
					Both files, in the order the engine reads them.  This used
					to take the override when one existed and the defaults
					otherwise, which is not what an override is: the class in a
					domain file extends the clonefrom class and redeclares only
					what it changes.  Reading it alone reported every flag it
					did not mention as off.

					revoltlib was the worked example -- reported as never
					fetching TextBody, Tag, Association or Description, while
					its pages were visibly full of all four.  A check that
					cries wolf is worse than no check, and this is the one the
					documentation says is worth scheduling.
				*/

			$domain_configuration = '';

			if(is_file($domain_location)) {
				$domain_configuration = file_get_contents($domain_location);

				if($domain_configuration === FALSE) {
					$domain_configuration = '';
				}
			}

			$flags = [];

			foreach($tables as $table) {
				$flag = NULL;

				if(strlen($domain_configuration)) {
					$flag = $this->readFlag([
						'configuration'=>$domain_configuration,
						'table'=>$table,
					]);
				}

				if($flag === NULL) {
					$flag = $this->readFlag([
						'configuration'=>$default_configuration,
						'table'=>$table,
					]);
				}

				$flags[$table] = ($flag === NULL) ? FALSE : $flag;
			}

			return $flags;
		}

		public function hasOverride($args) {
			$database = $args['database'];

			return is_file($this->childTypesDomainLocation(['database'=>$database]));
		}

		public function readFlag($args) {
			$configuration = $args['configuration'];
			$table = $args['table'];

			$pattern = '/function\s+' . preg_quote($table, '/') . '_enabled\s*\(\s*\)\s*\{\s*return\s+(TRUE|FALSE|true|false)\s*;/';

			$matches = [];

				/*
					NULL, not FALSE.  A domain override declares only the flags
					it changes and inherits the rest, so "this file does not
					mention the table" and "this file switches the table off"
					are different answers, and the caller has to be able to tell
					them apart to layer the two files correctly.
				*/

			if(!preg_match($pattern, $configuration, $matches)) {
				return NULL;
			}

			return strtoupper($matches[1]) === 'TRUE';
		}

			/*
				Every child type clonefrom has a switch for, in the order it
				lists them.  clonefrom's file is the list because the engine
				fatals on a type it names nowhere -- 10 November 2026 -- so
				every type a site can switch on is named there.
			*/

		public function switchedTypes() {
			$configuration = is_file($this->childTypesDefaultLocation()) ? file_get_contents($this->childTypesDefaultLocation()) : '';

			if(!$configuration) {
				return [];
			}

			$matches = [];
			preg_match_all('/function\s+([A-Za-z]+)_enabled\s*\(/', $configuration, $matches);

			return array_values(array_unique($matches[1]));
		}

		public function childTypesDefaultLocation() {
			return GGCMS_CONFIG_DIR . 'clonefrom/child_types/enabled.php';
		}

		public function childTypesDomainLocation($args) {
			$database = $args['database'];

			return GGCMS_CONFIG_DIR . $this->ReverseDomainName(['domain'=>$database . '.com']) . '/child_types/enabled.php';
		}
	}

?>
