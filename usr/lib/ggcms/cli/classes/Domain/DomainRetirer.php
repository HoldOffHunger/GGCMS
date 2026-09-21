<?php

	clireq('traits/Apache.php');
	clireq('traits/Directories.php');
	clireq('traits/CLIAccess.php');
	ggreq('traits/ReverseDNSNotation.php');
	clireq('traits/DomainValidation.php');

	/*
		The inverse of DomainInstaller.

		A retired site leaves pieces in a dozen places, and removing whichever
		ones come to mind is how abstractcon.com kept its configuration, its
		logs and two vhost files long after its database was dropped -- which
		the nightly backup then reported as NO_DB every morning.

		This gathers every piece that installation or operation creates into
		one dated tarball, verifies the tarball, and only then removes the
		originals.  A retirement is therefore a single file that can be kept
		or deleted, never a scatter of half-removed state.

		It reads the site's directories from the same Directories trait as the
		installer and the checker, so the three cannot disagree about where a
		site lives.

		Usage:

			retire_domain.php example.com [y] [y]

		The first `y` confirms archiving and removal; the second confirms
		dropping the database.  Declining either leaves everything in place.
	*/

	class DomainRetirer {
		use Apache;
		use Directories;
		use CLIAccess;
		use ReverseDNSNotation;
		use DomainValidation;

		const RETIRED_DIRECTORY = '/mnt/nyc01/ggcms_retired/';
		const CONFIG_CHECKOUT = '/opt/ggcms-config/';

			// Entry Point
			// -------------------------------------------------

		public function retireDomain() {
			$this->setHandle();
			$this->bannerMessage();

			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}

			$this->ReverseThisDomainName();
			$this->BuildInventory();
			$this->PrintInventory();

			$database_exists = $this->DatabaseExists();

			if(!count($this->archive_paths) && !count($this->discard_paths) && !$database_exists) {
				print('Nothing of ' . $this->domain . ' remains on this host.' . PHP_EOL . PHP_EOL);

				return $this->ReportRepositoryFiles();
			}

			if(!$this->basicConfirmDialogue(['message'=>'Archive everything above into one tarball, then remove the originals.', 'argv_index'=>2])) {
				return $this->cancelAction(['message'=>'User cancelled.  Nothing was changed.']);
			}

			$this->PrepareStaging();

			if($database_exists) {
				$this->DumpDatabase();
			}

			if(!$this->BuildTarball()) {
				return $this->cancelAction(['message'=>'The tarball could not be built or did not verify.  Nothing was removed; the staging directory is left for inspection.']);
			}

			$this->RemoveOriginals();

			if($database_exists) {
				$this->DropDatabase();
			}

			$this->ReportRepositoryFiles();

			return TRUE;
		}

			// Inventory
			// -------------------------------------------------

		/*
			Two kinds of thing are found.

			Archived: anything that cannot be rebuilt -- configuration, logs,
			backups, certificates, and any dump an operator left in /root.

			Discarded: the page and row caches.  They are rendered from the
			database, which goes into the tarball as a dump, so archiving them
			would only preserve a copy of something already preserved -- and on
			a large site it would be tens of gigabytes of it.
		*/

		public function BuildInventory() {
			$domain = $this->domain;
			$reversed = $this->reversed_domain;

			$candidates = array_merge(
				$this->RootDirectories(['directories'=>$this->domainDirectories()]),
				[
					'/etc/ggcms/' . $reversed . '/',
					'/etc/ggcms/' . $reversed . '.php',
					'/etc/apache2/sites-available/' . $domain . '.conf',
					'/etc/apache2/sites-available/' . $domain . '-le-ssl.conf',
					'/etc/letsencrypt/live/' . $domain . '/',
					'/etc/letsencrypt/archive/' . $domain . '/',
					'/etc/letsencrypt/renewal/' . $domain . '.conf',
					'/mnt/nyc01/ggcms_sql_backups/' . $domain . '/',
				],
				glob('/root/' . $this->host . '*.sql*') ?: []
			);

			$this->archive_paths = array_values(array_filter($candidates, 'file_exists'));

			$this->discard_paths = array_values(array_filter([
				'/mnt/nyc01/ggcms_cache/pages/' . $domain . '/',
				'/mnt/nyc01/ggcms_cache/pages/www.' . $domain . '/',
				'/mnt/nyc01/ggcms_cache/mysql_db_file_cache/' . $domain . '/',
			], 'file_exists'));

			$this->enabled_sites = array_values(array_filter([
				$domain,
				$domain . '-le-ssl',
			], function($site) {
				return is_link('/etc/apache2/sites-enabled/' . $site . '.conf');
			}));

			return TRUE;
		}

			/*
				domainDirectories() lists nested paths -- /srv/ggcms/x/ and
				/srv/ggcms/x/www/ both.  Archiving and removing the outermost
				of each covers the rest, and naming both would put the inner
				files into the tarball twice.
			*/

		public function RootDirectories($args) {
			$directories = $args['directories'];
			$roots = [];

			foreach($directories as $directory) {
				$inside_another = FALSE;

				foreach($directories as $other) {
					if($other !== $directory && strpos($directory, $other) === 0) {
						$inside_another = TRUE;
					}
				}

				if(!$inside_another) {
					$roots[] = $directory;
				}
			}

			return $roots;
		}

		public function PrintInventory() {
			print('To archive, then remove:' . PHP_EOL);
			foreach($this->archive_paths as $path) {
				print("\t" . $path . "\t" . $this->SizeOf(['path'=>$path]) . PHP_EOL);
			}

			print(PHP_EOL . 'To remove without archiving (rendered from the database):' . PHP_EOL);
			foreach($this->discard_paths as $path) {
				print("\t" . $path . "\t" . $this->SizeOf(['path'=>$path]) . PHP_EOL);
			}

			print(PHP_EOL . 'Enabled Apache sites to disable: ' . (count($this->enabled_sites) ? implode(', ', $this->enabled_sites) : 'none') . PHP_EOL);
			print('Database `' . $this->host . '`: ' . ($this->DatabaseExists() ? 'present -- will be dumped into the tarball' : 'absent') . PHP_EOL . PHP_EOL);

			return TRUE;
		}

		public function SizeOf($args) {
			return trim(explode("\t", (string) shell_exec('du -sh ' . escapeshellarg($args['path']) . ' 2>/dev/null'))[0]);
		}

		public function DatabaseExists() {
			$found = shell_exec('mysql -N -e ' . escapeshellarg("SHOW DATABASES LIKE '" . $this->host . "'") . ' 2>/dev/null');

			return trim((string) $found) === $this->host;
		}

			// Archiving
			// -------------------------------------------------

		public function PrepareStaging() {
			$this->stamp = date('Ymd-His');
			$this->staging = self::RETIRED_DIRECTORY . $this->domain . '-' . $this->stamp . '-staging/';
			$this->tarball = self::RETIRED_DIRECTORY . $this->domain . '-retired-' . $this->stamp . '.tar.gz';

			if(!mkdir($this->staging, 0700, TRUE)) {
				return $this->cancelAction(['message'=>'Could not create ' . $this->staging . '.  Nothing was changed.']);
			}

			return TRUE;
		}

		public function DumpDatabase() {
			$dump = $this->staging . $this->host . '-final.sql.gz';

			print('Dumping database `' . $this->host . '`...' . PHP_EOL);
			shell_exec('bash -o pipefail -c ' . escapeshellarg('mysqldump --single-transaction ' . escapeshellarg($this->host) . ' | gzip -6 > ' . escapeshellarg($dump)));

			return TRUE;
		}

			/*
				The manifest records every archived file with its checksum, so
				the tarball can be trusted later without having to trust this
				script: the list of what went in travels with it.
			*/

		public function BuildTarball() {
			$manifest = $this->staging . 'MANIFEST.txt';
			$lines = [
				'Retirement of ' . $this->domain . ' on ' . gethostname() . ' at ' . date('c'),
				'',
			];

			foreach($this->archive_paths as $path) {
				$listing = trim((string) shell_exec('find ' . escapeshellarg($path) . ' -type f -exec sha256sum {} + 2>/dev/null'));

				if($listing !== '') {
					$lines[] = $listing;
				}
			}

			file_put_contents($manifest, implode(PHP_EOL, $lines) . PHP_EOL);

			$relative_paths = array_map(function($path) {
				return escapeshellarg(ltrim($path, '/'));
			}, $this->archive_paths);

			$command = 'tar -czf ' . escapeshellarg($this->tarball)
				. ' -C / ' . implode(' ', $relative_paths)
				. ' -C ' . escapeshellarg($this->staging) . ' .';

			shell_exec($command . ' 2>&1');

			return $this->VerifyTarball();
		}

		public function VerifyTarball() {
			if(!is_file($this->tarball)) {
				return FALSE;
			}

			$expected = 0;

			foreach(array_merge($this->archive_paths, [$this->staging]) as $path) {
				$expected += (int) trim((string) shell_exec('find ' . escapeshellarg($path) . ' -type f 2>/dev/null | wc -l'));
			}

			$archived = (int) trim((string) shell_exec('tar -tzf ' . escapeshellarg($this->tarball) . ' 2>/dev/null | grep -v "/$" | wc -l'));

			print('Tarball: ' . $this->tarball . PHP_EOL);
			print("\t" . $archived . ' files archived, ' . $expected . ' expected' . PHP_EOL . PHP_EOL);

			return $archived === $expected && $archived > 0;
		}

			// Removal
			// -------------------------------------------------

		public function RemoveOriginals() {
			foreach($this->enabled_sites as $site) {
				shell_exec('a2dissite ' . escapeshellarg($site) . ' 2>&1');
			}

			if(is_dir('/etc/letsencrypt/live/' . $this->domain . '/')) {
				shell_exec('certbot delete --non-interactive --cert-name ' . escapeshellarg($this->domain) . ' 2>&1');
			}

			foreach(array_merge($this->archive_paths, $this->discard_paths, [$this->staging]) as $path) {
				shell_exec('rm -rf ' . escapeshellarg($path));
				print('Removed ' . $path . PHP_EOL);
			}

			if(count($this->enabled_sites)) {
				shell_exec('systemctl reload apache2 2>&1');
				print('Apache reloaded.' . PHP_EOL);
			}

			print(PHP_EOL);

			return TRUE;
		}

		public function DropDatabase() {
			if(!$this->basicConfirmDialogue(['message'=>'Drop database `' . $this->host . '`?  Its dump is in the tarball.', 'argv_index'=>3])) {
				print('Database left in place.' . PHP_EOL . PHP_EOL);

				return TRUE;
			}

			shell_exec('mysql -e ' . escapeshellarg('DROP DATABASE `' . $this->host . '`') . ' 2>&1');
			print('Dropped database `' . $this->host . '`.' . PHP_EOL . PHP_EOL);

			return TRUE;
		}

			// Repository
			// -------------------------------------------------

			/*
				deploy.sh rsyncs etc/apache2 and etc/nginx from the configuration
				repository.  Anything of this domain still tracked there comes
				straight back on the next deploy, so it is reported here -- the
				repository is the place to remove it, not this host.
			*/

		public function ReportRepositoryFiles() {
			$tracked = trim((string) shell_exec('cd ' . escapeshellarg(self::CONFIG_CHECKOUT) . ' && git ls-files 2>/dev/null | grep -F ' . escapeshellarg($this->domain)));

			if($tracked === '') {
				print('The configuration repository tracks nothing for ' . $this->domain . '.' . PHP_EOL . PHP_EOL);

				return TRUE;
			}

			print('Still tracked in the configuration repository -- remove these there, or the next deploy restores them:' . PHP_EOL);

			foreach(explode("\n", $tracked) as $file) {
				print("\t" . $file . PHP_EOL);
			}

			print(PHP_EOL);

			return TRUE;
		}

		public function bannerMessageText() {
			return 'Domain Retirement';
		}

		public function confirmDomainText() {
			return 'Retiring Domain: ';
		}
	}

?>
