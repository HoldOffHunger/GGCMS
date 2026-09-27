<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/CLIAccess.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DBTest.php');
	clireq('traits/Directories.php');
	clireq('traits/FileSystem.php');
	clireq('traits/GlobalsTrait.php');

		/*
			Preflight for a GGCMS host.  Every check here corresponds to a
			section of Docs/Installation.md, in the order that document gives
			them, so a failure names the paragraph that fixes it.

			It reads and reports; it changes nothing.  install_ggcms.php runs
			this first and refuses to proceed on a failure, which is the same
			relationship backup_database.php has with runMySQLTest().

			It never prints a credential.  The database check reports whether a
			directive is set, not what it is set to.
		*/

	class InstallChecker {
		use CLIAccess;
		use DBAccess;
		use DBTest;
		use Directories;
		use FileSystem;
		use GlobalsTrait;
		
		public $failures;

		public function checkInstall() {
			$this->setHandle();
			$this->bannerMessage();

			$this->failures = 0;

			$this->checkPHPVersion();
			$this->checkPHPExtensions();
			$this->checkDirectoryLayout();
			$this->checkDocumentRoot();
			$this->checkDatabaseCredentials();
			$this->checkGeneratedDocumentCache();
			$this->checkApache();
			$this->checkRemovedPackages();

			$this->reportOutcome();

			return TRUE;
		}

			// Reporting
			// -------------------------------------------------

		public function checkHeading($args) {
			print($args['heading'] . ' --' . PHP_EOL . PHP_EOL);

			return TRUE;
		}

		public function checkDetail($args) {
			print("\t" . $args['detail'] . PHP_EOL);

			return TRUE;
		}

		public function checkResult($args) {
			print(PHP_EOL . $args['heading'] . ': ');

			if($args['passed']) {
				$this->successResults();
			} else {
				$this->failures = $this->failures + 1;
				$this->failResults();
			}

			print(PHP_EOL . PHP_EOL);

			return $args['passed'];
		}

		public function reportOutcome() {
			if(!$this->failures) {
				print('Host is ready.  See Docs/Installation.md for installing a domain.' . PHP_EOL . PHP_EOL);

				return TRUE;
			}

			print($this->failures . ' check(s) failed.  Each names the section of' . PHP_EOL);
			print('Docs/Installation.md that fixes it.' . PHP_EOL . PHP_EOL);

			return FALSE;
		}

			// PHP
			// -------------------------------------------------

		public function checkPHPVersion() {
			$this->checkHeading(['heading'=>'PHP version']);

			$this->checkDetail(['detail'=>'Running: ' . PHP_VERSION . ' (' . PHP_SAPI . ')']);
			$this->checkDetail(['detail'=>'Wanted:  8.0 or later']);

			return $this->checkResult([
				'heading'=>'PHP version',
				'passed'=>version_compare(PHP_VERSION, '8.0', '>='),
			]);
		}

			/*
				mbstring is not optional -- the engine is UTF-8 throughout and
				calls mb_* directly.  xml provides DOMDocument, which the SSL
				trait uses to parse Apache vhosts.  zip provides ZipArchive for
				the EPub format.  See Installation.md, "PHP extensions".
			*/

		public function requiredExtensions() {
			return [
				'mbstring',
				'mysqli',
				'curl',
				'xml',
				'zip',
				'intl',
				'imagick',
			];
		}

		public function checkPHPExtensions() {
			$this->checkHeading(['heading'=>'PHP extensions']);

			$missing = [];

			foreach($this->requiredExtensions() as $extension) {
				$loaded = extension_loaded($extension);

				if(!$loaded) {
					$missing[] = $extension;
				}

				$this->checkDetail([
					'detail'=>str_pad($extension, 12) . ($loaded ? 'loaded' : 'MISSING'),
				]);
			}

			if($missing) {
				$this->checkDetail(['detail'=>'']);
				$this->checkDetail(['detail'=>'apt-get install php-' . implode(' php-', $missing)]);
			}

			return $this->checkResult([
				'heading'=>'PHP extensions',
				'passed'=>!count($missing),
			]);
		}

			// Filesystem
			// -------------------------------------------------

		public function requiredDirectories() {
			return [
				'/usr/lib/ggcms',
				'/etc/ggcms',
				'/var/log/ggcms',
				'/srv/ggcms',
				'/var/www/html',
			];
		}

			/*
				755 on directories, not 644.  A directory needs its execute bit
				to be traversed at all, which is the answer to the "why does 644
				not work?" note in the original setup file.
			*/

		public function checkDirectoryLayout() {
			$this->checkHeading(['heading'=>'Directory layout']);

			$faults = 0;

			foreach($this->requiredDirectories() as $directory) {
				if(!is_dir($directory)) {
					$faults = $faults + 1;
					$this->checkDetail(['detail'=>str_pad($directory, 22) . 'MISSING']);

					continue;
				}

				$mode = substr(sprintf('%o', fileperms($directory)), -3);
				$traversable = is_executable($directory);

				if(!$traversable) {
					$faults = $faults + 1;
				}

				$this->checkDetail([
					'detail'=>str_pad($directory, 22) . 'mode ' . $mode . ($traversable ? '' : '  NOT TRAVERSABLE'),
				]);
			}

			if($faults) {
				$this->checkDetail(['detail'=>'']);
				$this->checkDetail(['detail'=>'mkdir --mode=755 ' . implode(' ', $this->requiredDirectories())]);
			}

			return $this->checkResult([
				'heading'=>'Directory layout',
				'passed'=>!$faults,
			]);
		}

			/*
				.htaccess is load-bearing: the whole routing model depends on
				Apache rewriting every unmatched request to index.php.  A
				docroot missing either file serves nothing.
			*/

		public function checkDocumentRoot() {
			$this->checkHeading(['heading'=>'Document root']);

			$faults = 0;

			foreach(['/var/www/html/index.php', '/var/www/html/.htaccess'] as $file) {
				$present = is_file($file);

				if(!$present) {
					$faults = $faults + 1;
				}

				$this->checkDetail(['detail'=>str_pad($file, 28) . ($present ? 'present' : 'MISSING')]);
			}

			return $this->checkResult([
				'heading'=>'Document root',
				'passed'=>!$faults,
			]);
		}

		public function checkGeneratedDocumentCache() {
			$this->checkHeading(['heading'=>'Generated document cache']);

			$cache_directory = '/usr/lib/ggcms/src/data';
			$present = is_dir($cache_directory);
			$writable = $present && is_writable($cache_directory);

			$this->checkDetail(['detail'=>$cache_directory]);
			$this->checkDetail(['detail'=>$present ? ($writable ? 'present and writable' : 'present, NOT WRITABLE') : 'MISSING']);
			$this->checkDetail(['detail'=>'']);
			$this->checkDetail(['detail'=>'The RTF, TEX, SGML, OPDS and PDF classes cache here.  Without it,']);
			$this->checkDetail(['detail'=>'every document-format request fails on fopen() returning false.']);

			return $this->checkResult([
				'heading'=>'Generated document cache',
				'passed'=>$writable,
			]);
		}

			// Database
			// -------------------------------------------------

			/*
				Reports whether each directive is set, never what it is set to.
				php.ini is a credential file on a live host and this output goes
				into terminals, scrollback and bug reports.
			*/

		public function checkDatabaseCredentials() {
			$this->checkHeading(['heading'=>'Database credentials']);

			$directives = [
				'mysqli.default_host',
				'mysqli.default_user',
				'mysqli.default_pw',
				'mysqli.default_port',
			];

			$unset_directives = 0;

			foreach($directives as $directive) {
				$value = ini_get($directive);
				$is_set = ($value !== FALSE) && (strlen($value) > 0);

				if(!$is_set) {
					$unset_directives = $unset_directives + 1;
				}

				$this->checkDetail(['detail'=>str_pad($directive, 24) . ($is_set ? 'set' : 'NOT SET')]);
			}

			$this->checkDetail(['detail'=>'']);
			$this->checkDetail(['detail'=>'Loaded php.ini: ' . (php_ini_loaded_file() ? php_ini_loaded_file() : 'none')]);
			$this->checkDetail(['detail'=>'The CLI and Apache SAPIs load different files and both need these.']);

			return $this->checkResult([
				'heading'=>'Database credentials',
				'passed'=>!$unset_directives,
			]);
		}

			// Apache
			// -------------------------------------------------

		public function checkApache() {
			$this->checkHeading(['heading'=>'Apache']);

			$configtest = trim((string)shell_exec('apache2ctl configtest 2>&1'));
			$modules = (string)shell_exec('apache2ctl -M 2>/dev/null');

			$syntax_ok = (strpos($configtest, 'Syntax OK') !== FALSE);
			$rewrite = (strpos($modules, 'rewrite_module') !== FALSE);

			$this->checkDetail(['detail'=>'configtest      ' . ($syntax_ok ? 'Syntax OK' : $configtest)]);
			$this->checkDetail(['detail'=>'rewrite_module  ' . ($rewrite ? 'enabled' : 'NOT ENABLED  (a2enmod rewrite)')]);

			$this->checkDetail(['detail'=>'']);
			$this->checkDetail(['detail'=>'AllowOverride All is required for /var/www in apache2.conf.']);
			$this->checkDetail(['detail'=>'Without it .htaccess is ignored and no request routes at all.']);

			return $this->checkResult([
				'heading'=>'Apache',
				'passed'=>($syntax_ok && $rewrite),
			]);
		}

			/*
				javascript-common makes every file under <docroot>/javascript/
				return its own ad-laden 404 rather than Apache's.  It is not
				obvious and it wastes hours.
			*/

		public function checkRemovedPackages() {
			$this->checkHeading(['heading'=>'Conflicting packages']);

			$installed = trim((string)shell_exec("dpkg-query -W -f='\${Status}' javascript-common 2>/dev/null"));
			$present = (strpos($installed, 'install ok installed') !== FALSE);

			$this->checkDetail(['detail'=>'javascript-common  ' . ($present ? 'INSTALLED  (apt-get purge javascript-common)' : 'absent')]);

			if($present) {
				$this->checkDetail(['detail'=>'']);
				$this->checkDetail(['detail'=>'It serves its own 404 for everything under <docroot>/javascript/.']);
			}

			return $this->checkResult([
				'heading'=>'Conflicting packages',
				'passed'=>!$present,
			]);
		}

		public function bannerMessageText() {
			return 'Check GGCMS Installation';
		}
	}

?>
