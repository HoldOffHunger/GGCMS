<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/Apache.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/DBAccess.php');
	clireq('traits/Directories.php');
	clireq('traits/FileSystem.php');
	clireq('traits/GlobalsTrait.php');

	clireq('classes/Install/InstallChecker.php');

		/*
			Stands up a GGCMS host from bare Linux, following
			Docs/Installation.md step for step.

			It draws a hard line down the middle of that document.

			The mechanical half it will do: create the directory layout with
			the right modes and owner, create the generated-document cache,
			enable Apache's rewrite module, install the PHP extensions the
			engine needs, purge the one package that breaks the docroot.  All
			of it idempotent, and nothing invasive without a yes first -- you
			see the command before it runs.

			The judgement half it refuses: database credentials, MPM sizing,
			swap, AllowOverride, DNS.  Those depend on the box, the traffic and
			the hosting, and an installer that guesses at them is worse than no
			installer.  It names each one and stops.

			Preflight and postflight are both InstallChecker.  Run alone, that
			tool answers "is this host ready"; run here it answers "did this
			work", and the two questions want the same list.
		*/

	class InstallGGCMS {
		use Apache;
		use CLIAccess;
		use DBAccess;
		use Directories;
		use FileSystem;
		use GlobalsTrait;

		public function installGGCMS() {
			$this->setHandle();
			$this->bannerMessage();

			$this->preflight();

			if(!$this->userConfirm()) {
				return $this->cancelAction(['message'=>'Nothing was changed.']);
			}

			$this->installPHPExtensions();
			$this->purgeConflictingPackages();
			$this->buildDirectoryLayout();
			$this->buildGeneratedDocumentCache();
			$this->enableApacheRewrite();

			$this->reportWhatYouMustDecide();
			$this->postflight();

			return TRUE;
		}

			// Preflight and postflight
			// -------------------------------------------------

		public function newChecker() {
			return new InstallChecker(['argv'=>$this->argv]);
		}

		public function preflight() {
			print('Reading the host before changing anything.' . PHP_EOL . PHP_EOL);

			$this->newChecker()->checkInstall();

			return TRUE;
		}

		public function postflight() {
			print(PHP_EOL . 'Re-reading the host.' . PHP_EOL . PHP_EOL);

			$this->newChecker()->checkInstall();

			return TRUE;
		}

			// Steps this will take
			// -------------------------------------------------

			/*
				Shown before it is run, and only run on a yes.  An installer
				that shells out to a package manager unattended on somebody
				else's machine is the fragile kind.
			*/

		public function runConfirmedCommand($args) {
			print(PHP_EOL . $args['description'] . PHP_EOL . PHP_EOL);
			print("\t" . $args['command'] . PHP_EOL . PHP_EOL);

			$confirmed = $this->basicConfirmDialogue([
				'message'=>'Run it?',
			]);

			if(!$confirmed) {
				print('Skipped.' . PHP_EOL);

				return FALSE;
			}

			print((string)shell_exec($args['command'] . ' 2>&1'));

			return TRUE;
		}

		public function installPHPExtensions() {
			$missing = [];

			foreach($this->newChecker()->requiredExtensions() as $extension) {
				if(!extension_loaded($extension)) {
					$missing[] = $extension;
				}
			}

			if(!count($missing)) {
				print('PHP extensions: all present.' . PHP_EOL);

				return FALSE;
			}

			return $this->runConfirmedCommand([
				'description'=>'Missing PHP extensions: ' . implode(', ', $missing),
				'command'=>'apt-get install -y php-' . implode(' php-', $missing),
			]);
		}

			/*
				javascript-common makes every file under <docroot>/javascript/
				return its own ad-laden 404 rather than Apache's.  Not obvious,
				and it wastes hours.
			*/

		public function purgeConflictingPackages() {
			$installed = trim((string)shell_exec("dpkg-query -W -f='\${Status}' javascript-common 2>/dev/null"));

			if(strpos($installed, 'install ok installed') === FALSE) {
				print('Conflicting packages: none.' . PHP_EOL);

				return FALSE;
			}

			return $this->runConfirmedCommand([
				'description'=>'javascript-common serves its own 404 for everything under the docroot javascript directory.',
				'command'=>'apt-get purge -y javascript-common',
			]);
		}

			/*
				755, not 644.  A directory needs its execute bit to be traversed
				at all.  mkdir -p and chown are both idempotent, so this is safe
				to run against a host that is already half built.
			*/

		public function buildDirectoryLayout() {
			print(PHP_EOL . 'Directory layout --' . PHP_EOL . PHP_EOL);

			foreach($this->newChecker()->requiredDirectories() as $directory) {
				$existed = is_dir($directory);

				shell_exec('mkdir --mode=755 -p ' . $directory);
				shell_exec('chown -R ' . $this->defaultWebServerUser() . ' ' . $directory);

				print("\t" . str_pad($directory, 22) . ($existed ? 'present, owner set' : 'created') . PHP_EOL);
			}

			return TRUE;
		}

			/*
				Not in the repository and deleted by any rsync --delete that
				forgets to exclude it.  The RTF, TEX, SGML, OPDS and PDF classes
				cache here; without it every document-format request fails on
				fopen() returning false.
			*/

		public function buildGeneratedDocumentCache() {
			$cache_directory = '/usr/lib/ggcms/src/data';

			shell_exec('mkdir --mode=755 -p ' . $cache_directory);
			shell_exec('chown -R ' . $this->defaultWebServerUser() . ' ' . $cache_directory);

			print(PHP_EOL . 'Generated document cache: ' . $cache_directory . PHP_EOL);

			return TRUE;
		}

		public function enableApacheRewrite() {
			$modules = (string)shell_exec('apache2ctl -M 2>/dev/null');

			if(strpos($modules, 'rewrite_module') !== FALSE) {
				print('Apache rewrite module: enabled.' . PHP_EOL);

				return FALSE;
			}

			return $this->runConfirmedCommand([
				'description'=>'Apache rewrite module is not enabled.  Without it no request routes at all.',
				'command'=>'a2enmod rewrite && systemctl reload apache2',
			]);
		}

			// Steps this will not take
			// -------------------------------------------------

			/*
				Each of these depends on the box, its traffic or its hosting,
				and a wrong answer is worse than no answer.  Named, with the
				section of Installation.md that covers it.
			*/

		public function decisionsLeftToYou() {
			return [
				['Database credentials', 'Set mysqli.default_user, _pw, _host and _port in BOTH the Apache and CLI php.ini files.  Installation.md, "Database credentials".'],
				['AllowOverride All', 'Required for /var/www in apache2.conf, or .htaccess is ignored and nothing routes.  Installation.md, "Apache".'],
				['MPM sizing', 'MaxRequestWorkers and MaxConnectionsPerChild depend on the memory you have.  Installation.md, "MPM sizing".'],
				['Swap', 'The reference host had none and paid for it.  Installation.md, "Swap".'],
				['The database itself', 'CREATE DATABASE with utf8mb4, and put it in the same region as the host.  Installation.md, "Database credentials".'],
				['DNS', 'Installation.md, "DNS".'],
				['Your first domain', 'cli/scripts/internal/domain/install_domain.php example.com'],
			];
		}

		public function reportWhatYouMustDecide() {
			print(PHP_EOL . 'Left to you --' . PHP_EOL . PHP_EOL);

			foreach($this->decisionsLeftToYou() as $decision) {
				print("\t" . $decision[0] . PHP_EOL);
				print("\t\t" . $decision[1] . PHP_EOL . PHP_EOL);
			}

			return TRUE;
		}

		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Build the directory layout and offer the packaging steps above.',
			]);
		}

		public function bannerMessageText() {
			return 'Install GGCMS';
		}
	}

?>
