<?php

	depreq('arr2textTable/arr2textTable.php');
	
	clireq('traits/Apache.php');
	clireq('traits/DBAccess.php');
	clireq('traits/DBTest.php');
	clireq('traits/Directories.php');
	clireq('traits/DNSRecords.php');
	clireq('traits/CLIAccess.php');
	clireq('traits/GlobalsTrait.php');
	ggreq('traits/ReverseDNSNotation.php');
	clireq('traits/DigitalOceanDNSRecords.php');
	clireq('traits/DomainValidation.php');
	
	class DomainInstaller {
		use Apache;
		use DBAccess;
		use DBTest;
		use Directories;
		use DNSRecords;
		use CLIAccess;
		use GlobalsTrait;
		use ReverseDNSNotation;
		use DigitalOceanDNSRecords;
		use DomainValidation;
		
		public $answer_type;
		public $formatted_records;
		public $source_filename;

			/*
				No database selected.  DBAccess connects to the domain's own
				database, which is right for every tool that works on a site
				that exists -- and fatal here, the one tool whose job is to
				create it: mysqli throws "Unknown database" before the first
				question about building it is asked.  Everything after this
				names its database, or runs the mysql client itself.
			*/

		public function setMySQLArgs() {
			return $this->db_link = ggcms_mysqli('');
		}

		public function installDomain() {
			$this->setHandle();
			$this->bannerMessage();
			$this->setSourceFileName();
			
			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}
			
			$this->ReverseThisDomainName();
			
			if($this->userConfirm()) {
				$this->userInputType();
				$this->setGlobals();
				$this->setMySQLArgs();
				
				$this->runMySQLTest(['display'=>'short']);
				
					/*
						Run again on a site that exists, the import would load
						clonefrom.sql over it -- the dump drops and recreates
						every table -- and leave a live site empty.  An existing
						database is kept whole, so the installer can be rerun
						to finish whatever a first run did not.
					*/

				if($this->databaseExists()) {
					print('Database `' . $this->host . '` already exists; kept as it is, and clonefrom.sql is not imported over it.' . PHP_EOL . PHP_EOL);
				} else {
					if($this->userConfirmBuildDatabase()) {
						$this->buildDatabase();
					}

					if($this->userConfirmSource()) {
						$this->rebuildCloneFromSource();
					}

					if($this->userConfirmImport()) {
						$this->importCloneFromSource();
					}
				}

				$this->buildPrimaryEntry();

				if($this->userConfirmDirectories()) {
					$this->buildDirectories();
				}
				
					/*
						The order is the dependency order, and it changed when
						nginx took ports 80 and 443 in front of Apache.  DNS
						first, because the certificate cannot be issued for a
						name that resolves nowhere.  The certificate next, through
						nginx's webroot, because both vhosts below name its
						files and a vhost naming a missing file stops Apache --
						every site on the host, not just this one.  Apache, then
						nginx, last.
					*/

				if($this->userConfirmGGCMSConfig()) {
					$this->buildGGCMSConfig();
				}

				if($this->userConfirmDNSRecords()) {
					$this->buildDNSRecords();
				}

				if($this->userConfirmCertBot()) {
					if($this->waitForDNS()) {
						$this->certBot();
					}
				}

				if($this->userConfirmApacheConf()) {
					$this->buildApacheConf();
				}

				if($this->userConfirmApacheEnable()) {
					$this->enableApacheConf();
				}

				if($this->userConfirmApacheReload()) {
					$this->reloadApache();
				}

				$this->reloadNginx();

				$this->recommendDNSRecords();

				return TRUE;
			}
			
			return $this->cancelAction(['message'=>'User cancelled.']);
		}
		
			/*
				What DigitalOcean now holds for the domain, read back rather
				than recited.  This used to print a table of records for the
				operator to enter by hand, with the server's address as a
				blank to fill in -- from before the installer wrote them
				through doctl itself.
			*/

		public function recommendDNSRecords() {
			print(PHP_EOL . PHP_EOL);
			
			print('DNS records now published for ' . $this->domain . ':' . PHP_EOL . PHP_EOL);
			
			$records = [];
			
			foreach($this->getDigitalOceanDNSRecords(['quiet'=>TRUE]) as $record) {
				$records[] = [
					'Type'=>$record['Type'],
					'Name'=>$record['Name'],
					'Data'=>$record['Data'],
					'TTL'=>$record['TTL'],
				];
			}
			
			if($records) {
				print(arr2textTable($records));
			} else {
				print('None -- DigitalOcean has no zone for this domain.' . PHP_EOL);
			}
			
			print(PHP_EOL . 'Then check the whole domain:' . PHP_EOL . PHP_EOL);
			print("\t\t" . 'php ' . GGCMS_CLI_DIR . 'scripts/internal/domain/check_domain.php ' . $this->domain . PHP_EOL . PHP_EOL);

			return TRUE;
		}
		
		// IPv4Addresses()
		// IPv6Addresses()
		
		public function userInputType() {
			print("\n\n");
			print('Answer type?  (y)es to all, (o)ne at a time?');
			
			if(array_key_exists(3, $this->argv) && $this->argv[3]) {
				$this->answer_type = $this->argv[3];
				print($this->answer_type);
				print(PHP_EOL);
			} else {
				$this->answer_type = strtolower(trim(fgets($this->handle)));
			}
			
			return true;
		}
		
			/*
				Both names, always.  A vhost is written with a ServerAlias for
				www, so a certificate naming only the bare domain leaves
				https://www.<site> throwing a full-page browser interstitial --
				which is what all seventeen sites on this host did, unnoticed,
				because www was only ever reached over plain HTTP.

				Through nginx's webroot.  nginx owns port 80 and its catch-all
				serves /.well-known/acme-challenge/ from /var/www/html, which
				is where renewals already answer.  The --apache authenticator
				this used to run expects Apache on port 80, where it no longer
				listens, so it could not answer the challenge -- and it rewrote
				vhosts for port 443, which belongs to nginx.  certonly touches
				no server configuration at all: the vhosts are written after,
				knowing the certificate is there.

				And the result is read.  This printed success unconditionally,
				ignoring what shell_exec returned, so a failed issuance and a
				good one looked identical to whoever ran it.
			*/

		public function certBot() {
			print("CertBot SSL installing..." . PHP_EOL . PHP_EOL);

			$cert_line = 'certbot certonly';
			$cert_line .= ' --webroot -w ' . escapeshellarg($this->acmeWebroot());
			$cert_line .= ' --non-interactive --agree-tos';
			$cert_line .= ' --cert-name ' . escapeshellarg($this->domain);
			$cert_line .= ' -d ' . escapeshellarg($this->domain);
			$cert_line .= ' -d ' . escapeshellarg('www.' . $this->domain);
			$cert_line .= ' 2>&1';

			$output = (string) shell_exec($cert_line);

			print($output . PHP_EOL);

			$succeeded = (strpos($output, 'Successfully received certificate') !== FALSE);
			$succeeded = $succeeded || (strpos($output, 'Congratulations') !== FALSE);
			$succeeded = $succeeded || (strpos($output, 'not yet due for renewal') !== FALSE);
			$succeeded = $succeeded || (strpos($output, 'Certificate not yet due') !== FALSE);

			if(!$succeeded || !$this->certificateExists()) {
				print("SSL certificate was NOT installed.  Read the certbot output above." . PHP_EOL . PHP_EOL);

				return FALSE;
			}

			print("Successfully installed SSL Cert." . PHP_EOL . PHP_EOL);

			return TRUE;
		}

		public function acmeWebroot() {
			return '/var/www/html';
		}

		public function certificateDirectory() {
			return '/etc/letsencrypt/live/' . $this->domain . '/';
		}

		public function certificateExists() {
			return is_file($this->certificateDirectory() . 'fullchain.pem') && is_file($this->certificateDirectory() . 'privkey.pem');
		}

			/*
				Let's Encrypt resolves the name itself, from outside, so the
				question is not whether DigitalOcean holds the record but
				whether the world can see it.  A public resolver is asked for
				both names, until both answer with this host's address.  A
				zone created a moment ago usually answers within a minute;
				a resolver still holding a failure from before the zone
				existed can take a few.
			*/

		public function waitForDNS() {
			$expected = $this->validIPAddresses(['addresses'=>$this->globals->IPv4Addresses(), 'flag'=>FILTER_FLAG_IPV4]);

			if(!$expected) {
				print('No IPv4 address in IPv4Addresses(), so there is nothing to wait for.  Certificate skipped.' . PHP_EOL . PHP_EOL);

				return FALSE;
			}

			$names = [$this->domain, 'www.' . $this->domain];
			$attempts = 40;
			$pause = 15;

			print('Waiting for ' . implode(' and ', $names) . ' to resolve to ' . $expected[0] . '...' . PHP_EOL);

			for($attempt = 1; $attempt <= $attempts; $attempt++) {
				$resolved = TRUE;

				foreach($names as $name) {
					$answer = trim((string) shell_exec('dig +short A ' . escapeshellarg($name) . ' @8.8.8.8 2>/dev/null'));

					if(!in_array($expected[0], preg_split('/\s+/', $answer), TRUE)) {
						$resolved = FALSE;
					}
				}

				if($resolved) {
					print('Resolved.' . PHP_EOL . PHP_EOL);

					return TRUE;
				}

				if($attempt < $attempts) {
					print('.');
					sleep($pause);
				}
			}

			print(PHP_EOL . 'Still not resolving after ' . (($attempts * $pause) / 60) . ' minutes.  Certificate skipped; run this again once `dig ' . $this->domain . '` answers ' . $expected[0] . '.' . PHP_EOL . PHP_EOL);

			return FALSE;
		}

		public function validIPAddresses($args) {
			$valid = [];

			foreach((array) $args['addresses'] as $address) {
				if(filter_var($address, FILTER_VALIDATE_IP, $args['flag'])) {
					$valid[] = $address;
				}
			}

			return $valid;
		}

			/*
				A test before the reload.  A reload that fails takes no site
				down, but the next deploy restarts Apache, and a restart on a
				configuration that does not validate takes all of them.
			*/

		public function reloadApache() {
			print("Testing apache configuration...\n\n");

			$test_output = (string) shell_exec('apache2ctl configtest 2>&1');

			if(strpos($test_output, 'Syntax OK') === FALSE) {
				print($test_output . PHP_EOL);
				print("Apache configuration did NOT validate; apache was left running as it was.\n\n");

				return FALSE;
			}

			print("Reloading apache...\n\n");

			shell_exec('systemctl reload apache2');

			print("Successfully reloaded apache.\n\n");

			return TRUE;
		}

		public function userConfirmApacheReload() {
			return $this->basicConfirmDialogue([
				'message'=>'Reload apache?',
				'argv_index'=>3,
			]);
		}

		public function userConfirmCertBot() {
			return $this->basicConfirmDialogue([
				'message'=>'Issue the SSL certificate through nginx\'s webroot?  (Waits for DNS first.)',
				'argv_index'=>3,
			]);
		}

			/*
				The redirecting vhost always; the secure one only once its
				certificate exists, because an enabled vhost naming a missing
				certificate stops Apache from starting at all.
			*/

		public function enableApacheConf() {
			$vhosts = [$this->domain];

			if($this->certificateExists()) {
				$vhosts[] = $this->domain . '-le-ssl';
			} else {
				print('No certificate yet, so `' . $this->domain . '-le-ssl.conf` is left disabled.  Run this again once the certificate is issued.' . PHP_EOL . PHP_EOL);
			}

			foreach($vhosts as $vhost) {
				$apache_conf_location = $this->apacheConfLocation(['vhost'=>$vhost]);

				print("Enabling Apache Config file `" . $apache_conf_location . "`.\n\n");

				shell_exec('a2ensite ' . escapeshellarg($vhost) . ' 2>&1');
				shell_exec('chown -R ' . $this->defaultWebServerUser() . ' ' . escapeshellarg($apache_conf_location));

				print("Successfully enabled Apache Config file `" . $apache_conf_location . "`.\n\n");
			}

			return TRUE;
		}

		public function userConfirmApacheEnable() {
			return $this->basicConfirmDialogue([
				'message'=>'Ready to enable the apache config files for `' . $this->domain . '`?',
				'argv_index'=>3,
			]);
		}

		public function apacheConfLocation($args) {
			return '/etc/apache2/sites-available/' . $args['vhost'] . '.conf';
		}

			/*
				Apache sits behind nginx: plain HTTP on 8080, which only ever
				redirects, and HTTPS on 8443, which serves.  The pair is what
				every site in the configuration repository carries, and when
				that repository already has this site's files the deploy has
				put them here -- they are kept, not overwritten, so the
				repository stays the one source of them.
			*/

		public function buildApacheConf() {
			$files = [
				$this->domain=>$this->apacheConfText(),
				$this->domain . '-le-ssl'=>$this->apacheSSLConfText(),
			];

			foreach($files as $vhost => $text) {
				$file_location = $this->apacheConfLocation(['vhost'=>$vhost]);

				if(is_file($file_location)) {
					print("Apache Config file `" . $file_location . "` already exists; kept as it is.\n\n");

					continue;
				}

				print("Building Apache Config file `" . $file_location . "`.\n\n");

				file_put_contents($file_location, $text, LOCK_EX);

				print("Successfully built Apache Config file `" . $file_location . "`." . PHP_EOL . PHP_EOL);
			}

			return TRUE;
		}

		public function apacheConfText() {
			return
				'<VirtualHost *:8080>' . "\n" .
				"\t" . 'ServerAdmin ' . $this->globals->AdminEmailAddress() . "\n" .
				"\t" . 'ServerName ' . $this->domain . "\n" .
				"\t" . 'ServerAlias www.' . $this->domain . "\n" .
				"\t" . 'DocumentRoot /var/www/html' . "\n" .
				"\t" . 'ErrorLog ${APACHE_LOG_DIR}/error.log' . "\n" .
				"\t" . 'CustomLog ${APACHE_LOG_DIR}/access.log combined' . "\n" .
				"\n" .
				"\t" . 'RewriteEngine On' . "\n" .
				"\t" . 'RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/' . "\n" .
				"\t" . 'RewriteRule ^(.*)$ https://' . $this->domain . '$1 [R=301,L]' . "\n" .
				'</VirtualHost>' . "\n";
		}

		public function apacheSSLConfText() {
			return
				'<IfModule mod_ssl.c>' . "\n" .
				'<VirtualHost *:8443>' . "\n" .
				"\t" . 'ServerAdmin ' . $this->globals->AdminEmailAddress() . "\n" .
				"\t" . 'ServerName ' . $this->domain . "\n" .
				"\t" . 'ServerAlias www.' . $this->domain . "\n" .
				"\t" . 'DocumentRoot /var/www/html' . "\n" .
				"\t" . 'ErrorLog ${APACHE_LOG_DIR}/error.log' . "\n" .
				"\t" . 'CustomLog ${APACHE_LOG_DIR}/access.log combined' . "\n" .
				'SSLCertificateFile ' . $this->certificateDirectory() . 'fullchain.pem' . "\n" .
				'SSLCertificateKeyFile ' . $this->certificateDirectory() . 'privkey.pem' . "\n" .
				'Include /etc/letsencrypt/options-ssl-apache.conf' . "\n" .
				'</VirtualHost>' . "\n" .
				'</IfModule>' . "\n";
		}

			/*
				nginx's server blocks are one file in the configuration
				repository, so a block written here would be overwritten by
				the next deploy.  The installer checks instead: if the deploy
				has already delivered this site's block and the certificate
				it names exists, nginx is tested and reloaded; if not, it says
				exactly what is missing.  Never reloaded on a failed test --
				the running configuration keeps serving every other site.
			*/

		public function reloadNginx() {
			print("Checking nginx for `" . $this->domain . "`..." . PHP_EOL . PHP_EOL);

			$pattern = 'server_name[[:space:]]+' . str_replace('.', '\\.', $this->domain) . ';';
			$grep_line = 'grep -RlE ' . escapeshellarg($pattern) . ' /etc/nginx/sites-enabled/ 2>/dev/null';
			$has_block = strlen(trim((string) shell_exec($grep_line))) > 0;

			if(!$has_block) {
				print('nginx has no server block for ' . $this->domain . ' yet.  Add one to etc/nginx/sites-available/ggcms.conf in the configuration repository -- copy another site\'s pair -- and deploy.' . PHP_EOL . PHP_EOL);

				return FALSE;
			}

			if(!$this->certificateExists()) {
				print('nginx has a server block for ' . $this->domain . ', but its certificate does not exist yet, so nginx was not reloaded.' . PHP_EOL . PHP_EOL);

				return FALSE;
			}

			$test_output = (string) shell_exec('nginx -t 2>&1');

			if(strpos($test_output, 'test is successful') === FALSE) {
				print($test_output . PHP_EOL);
				print('nginx configuration did NOT validate; nginx was left running as it was.' . PHP_EOL . PHP_EOL);

				return FALSE;
			}

			shell_exec('systemctl reload nginx');

			print('Successfully reloaded nginx.' . PHP_EOL . PHP_EOL);

			return TRUE;
		}

		public function buildGGCMSConfig() {
			$this->buildGGCMSConfigFile();
			$this->buildGGCMSConfigFolder();

			return TRUE;
		}

			/*
				Kept when it exists.  A site's configuration lives in the
				configuration repository and the deploy puts it here, so an
				empty one written over it would undo the site until the next
				deploy put it back.
			*/

		public function buildGGCMSConfigFile() {
			$file_location = GGCMS_CONFIG_DIR . $this->reversed_domain . '.php';

			if(is_file($file_location)) {
				print("GGCMS Config file `" . $file_location . "` already exists; kept as it is.\n\n");

				return TRUE;
			}

			print("Building GGCMS Config file `" . $file_location . "`.\n\n");

			$ggcms_conf =
				'<?php' . PHP_EOL . PHP_EOL .
				'	class globals extends defaultglobals {' . PHP_EOL .
				'	}' . PHP_EOL . PHP_EOL .
				'?>';

			file_put_contents($file_location, $ggcms_conf, LOCK_EX);

			print("Successfully built GGCMS Config file `" . $file_location . "`." . PHP_EOL . PHP_EOL);

			return TRUE;
		}

		public function buildGGCMSConfigFolder() {
			$folder_location = GGCMS_CONFIG_DIR . $this->reversed_domain . '/';
			
			if(is_dir($folder_location)) {
				print('GGCMS Config folder `' . $folder_location . '` already exists; kept as it is.' . PHP_EOL . PHP_EOL);
				
				return TRUE;
			}
			
			$mkdir_command_base = 'mkdir --mode=744 ' . $folder_location;
			$chown_command_base = 'chown -R ' . $this->defaultWebServerUser() . ' ' . $folder_location;
			
			shell_exec($mkdir_command_base);
			shell_exec($chown_command_base);
			
			print('Successfully built GGCMS Config folder `' . $folder_location . '`.' . PHP_EOL . PHP_EOL);
			
			return TRUE;
		}
		
			/*
				Refused outright while IPv4Addresses() holds no real address.
				It shipped as a placeholder sentence, and every A record this
				wrote carried it as its data -- so a new site's zone pointed
				nowhere, and the certificate after it could never be issued.
			*/

		public function buildDNSRecords() {
			if(!$this->validIPAddresses(['addresses'=>$this->globals->IPv4Addresses(), 'flag'=>FILTER_FLAG_IPV4])) {
				print('IPv4Addresses() in clonefrom.php holds no real IPv4 address, so no DNS records were written.  Put the droplet\'s address there and run this again.' . PHP_EOL . PHP_EOL);

				return FALSE;
			}

				# the zone first: listing the records of one that does not exist yet is a 404
			$this->buildDNSRecords_DigitalOceanConfig();
			
			$this->formatted_records = $this->getFormattedDomainRecords();
			
		#	$this->buildDNSRecords_NameServers();	# this is hanlded by DigitalOceanConfig, sigh
			$this->buildDNSRecords_IPv4Addresses();
			$this->buildDNSRecords_IPv6Addresses();
			$this->buildDNSRecords_CertificateAuthorities();
			
			return TRUE;
		}
		
		public function buildDNSRecords_DigitalOceanConfig() {
			$command = 'doctl compute domain create ' . escapeshellarg($this->domain) . ' 2>/dev/null';
			
			shell_exec($command);
			
			return TRUE;
		}
		
		public function buildDNSRecords_NameServers() {
			$name_servers = $this->globals->NameServers();
			$name_server_lookup = [];
			
			foreach($name_servers as $name_server) {
				$name_server_lookup[$name_server] = $name_server;
			}
			
			if(array_key_exists('NS', $this->formatted_records)) {
				foreach($this->formatted_records['NS'] as $ns_server) {
					if(array_key_exists($ns_server['Data'], $name_server_lookup)) {
						unset($name_server_lookup[$ns_server['Data']]);
					}
				}
			}
			
			$valid_name_servers_to_build = array_keys($name_server_lookup);
			
			foreach($valid_name_servers_to_build as $valid_name_server_to_build) {
				$command = 'doctl compute domain records create ' . $this->domain . ' --record-type "NS" --record-name "." --record-ttl 1800 --record-data "' . $valid_name_server_to_build . '."';
		#		$command = 'doctl compute domain records create ' . $this->domain . ' --record-type "NS" --record-name "' . $this->domain . '" --record-ttl 1800 --record-data "' . $valid_name_server_to_build . '."';		# this makes "domain.com.domain.com" record-name values
				
				shell_exec($command);
			#	print(PHP_EOL);
			#	print($command);
			#	print(PHP_EOL);
			}
			
			return TRUE;
		}
		
		public function buildDNSRecords_IPv4Addresses() {
			$name_server_missing = ['*'=>TRUE, '@'=>TRUE,];
			
			if(array_key_exists('A', $this->formatted_records)) {
				foreach($this->formatted_records['A'] as $ns_server) {
					if(array_key_exists($ns_server['Name'], $name_server_missing)) {
						unset($name_server_missing[$ns_server['Name']]);
					}
				}
			}
			
			$ip_addresses = $this->validIPAddresses(['addresses'=>$this->globals->IPv4Addresses(), 'flag'=>FILTER_FLAG_IPV4]);
			
			foreach($name_server_missing as $name_server_missing_id => $truth) {
				$command = 'doctl compute domain records create ' . $this->domain . ' --record-type "A" --record-name "' . $name_server_missing_id . '" --record-ttl 3600 --record-data "' . $ip_addresses[0] . '"';
				
				shell_exec($command);
			}
			
			return TRUE;
		}
		
		public function buildDNSRecords_IPv6Addresses() {
			$name_server_missing = ['*'=>TRUE, '@'=>TRUE,];
			
			if(array_key_exists('AAAA', $this->formatted_records)) {
				foreach($this->formatted_records['AAAA'] as $ns_server) {
					if(array_key_exists($ns_server['Name'], $name_server_missing)) {
						unset($name_server_missing[$ns_server['Name']]);
					}
				}
			}
			
			$ip_addresses = $this->validIPAddresses(['addresses'=>$this->globals->IPv6Addresses(), 'flag'=>FILTER_FLAG_IPV6]);

			if(!$ip_addresses) {
				print('No IPv6 address in IPv6Addresses(), so no AAAA records were written.' . PHP_EOL . PHP_EOL);

				return FALSE;
			}
			
			foreach($name_server_missing as $name_server_missing_id => $truth) {
				$command = 'doctl compute domain records create ' . $this->domain . ' --record-type "AAAA" --record-name "' . $name_server_missing_id . '" --record-ttl 3600 --record-data "' . $ip_addresses[0] . '"';
				
				shell_exec($command);
			}
			
			return TRUE;
		}
		
			/*
				At "@", the zone's apex.  DigitalOcean reads a record name
				relative to the zone, so the domain written out in full made
				these records for domain.com.domain.com -- the trap the NS
				builder above already notes -- and the domain itself was left
				with no CAA at all.
			*/

		public function buildDNSRecords_CertificateAuthorities() {
			if(!preg_match('/^[a-z0-9.-]+\.[a-z]+$/i', $this->globals->EncryptionAuthority()) || !filter_var($this->globals->CertificateAuthorityAdminEmailAddress(), FILTER_VALIDATE_EMAIL)) {
				print('EncryptionAuthority() or AdminEmailAddress() in clonefrom.php is still a placeholder, so no CAA records were written.' . PHP_EOL . PHP_EOL);

				return FALSE;
			}

			$existing = $this->existingCAARecords();

			foreach($this->wantedCAARecords() as $record) {
				if(in_array($record['tag'] . ' ' . rtrim($record['data'], '.'), $existing, TRUE)) {
					continue;
				}

				$command = 'doctl compute domain records create ' . escapeshellarg($this->domain) . ' --record-type "CAA" --record-name "@" --record-ttl 3600 --record-tag ' . escapeshellarg($record['tag']) . ' --record-data ' . escapeshellarg($record['data']) . ' 2>&1';

				shell_exec($command);

				print('CAA record written: ' . $record['tag'] . ' ' . $record['data'] . PHP_EOL);
			}

			print(PHP_EOL);

			return TRUE;
		}

			/*
				The authority that issues today, and for a commercial site the
				one Cloudflare would: a commercial site is a candidate for
				going behind it, Cloudflare's certificates come from Google
				Trust Services, and a CAA record naming only Let's Encrypt
				would make that certificate silently never appear.  The domain
				checker fails a commercial site without it.  Revolutionary
				sites are left to Let's Encrypt alone, for the reason the
				checker gives.
			*/

		public function wantedCAARecords() {
			$issuers = [$this->globals->EncryptionAuthority()];

			if(method_exists($this->globals, 'SiteIsCommercial') && $this->globals->SiteIsCommercial()) {
				$issuers[] = 'pki.goog';
			}

			$records = [];

			foreach($issuers as $issuer) {
				$records[] = ['tag'=>'issue', 'data'=>$issuer . '.'];
				$records[] = ['tag'=>'issuewild', 'data'=>$issuer . '.'];
			}

			$records[] = ['tag'=>'iodef', 'data'=>'mailto:' . $this->globals->CertificateAuthorityAdminEmailAddress() . '.'];

			return $records;
		}

			/*
				Read from DigitalOcean's own nameserver rather than from the
				doctl listing, whose columns do not carry a CAA record's tag,
				so a second run adds only what the first left out.  Each comes
				back as `0 issue "letsencrypt.org"`; returned as `issue
				letsencrypt.org`.
			*/

		public function existingCAARecords() {
			$name_servers = $this->globals->NameServers();
			$output = trim((string) shell_exec('dig +short CAA ' . escapeshellarg($this->domain) . ' @' . escapeshellarg($name_servers[0]) . ' 2>/dev/null'));

			$records = [];

			foreach(preg_split('/\R/', $output) as $line) {
				if(preg_match('/^\d+\s+(issue|issuewild|iodef)\s+"([^"]*)"/', trim($line), $matches)) {
					$records[] = $matches[1] . ' ' . rtrim($matches[2], '.');
				}
			}

			return $records;
		}
		
		public function userConfirmApacheConf() {
			return $this->basicConfirmDialogue([
				'message'=>'Ready to build apache config file `' . $this->domain . '.conf`?',
				'argv_index'=>3,
			]);
		}
		
		public function userConfirmGGCMSConfig() {
			return $this->basicConfirmDialogue([
				'message'=>'Ready to build GGCMS config file `' . GGCMS_CONFIG_DIR . $this->reversed_domain . '.php`?',
				'argv_index'=>3,
			]);
		}
		
		public function userConfirmDNSRecords() {
			$web_host_services = implode(', ', $this->globals->WebHostServices());
			
			return $this->basicConfirmDialogue([
				'message'=>'Ready to commit DNS records to ' . $web_host_services . '?',
				'argv_index'=>3,
			]);
		}
		
		public function userConfirmDirectories() {
			return $this->basicConfirmDialogue([
				'message'=>'Ready to create directories for `' . $this->host . '`?',
				'argv_index'=>3,
			]);
		}
		
		public function userConfirmBuildDatabase() {
			return $this->basicConfirmDialogue([
				'message'=>'Ready to create a new database named `' . $this->host . '`.',
				'argv_index'=>3,
			]);
		}
		
		public function buildDirectories() {
			print('Building ' . $this->host . ' directories.' . PHP_EOL . PHP_EOL);
			$mkdir_command_base = 'mkdir --mode=744 ';
			$chown_command_base = 'chown -R ' . $this->defaultWebServerUser() . ' ';
			
			$directories = $this->domainDirectories();
			
			foreach($directories as $directory) {
				$directory_make_command = $mkdir_command_base . $directory;
				$directory_chown_command = $chown_command_base . $directory;
				
				shell_exec($directory_make_command);
				shell_exec($directory_chown_command);
				
				print('Created directory owned by ' . $this->defaultWebServerUser() . ': ' . $directory . PHP_EOL);
			}
			
				/*
					755 and -p, like the format folders they sit in, so a rerun
					over a site that already has some of them is harmless.
				*/
			
			foreach($this->documentCacheDirectories() as $directory) {
				shell_exec('mkdir --mode=755 -p ' . escapeshellarg($directory));
				shell_exec($chown_command_base . escapeshellarg($directory));
				
				print('Document cache directory owned by ' . $this->defaultWebServerUser() . ': ' . $directory . PHP_EOL);
			}
			
			print("Successfully built " . $this->host . " directories." . PHP_EOL . PHP_EOL);
			
			return TRUE;
		}
		
		public function databaseExists() {
			$statement = $this->db_link->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
			$statement->bind_param('s', $this->host);
			$statement->execute();

			return (bool) $statement->get_result()->fetch_row();
		}

			/*
				The site's primary entry: the one Entry assigned under nothing
				(Childid 0), which the ORM finds every page of the site from,
				and whose Title, Subtitle and Code the front page, the header
				and the policies print.  clonefrom.sql carries none, so a site
				installed without one answered every address with a 404.

				Asked for, never assumed -- the title is the site's to choose.
				Arguments four to six answer the questions for a scripted run.
				The code is offered from the title and the domain's ending,
				"Autonomy Books" on .com becoming AutonomyBooks.com, which is
				how every site's primary entry has been named.  Kept when one
				exists.
			*/

		public function buildPrimaryEntry() {
			if(!$this->databaseExists()) {
				print('No database `' . $this->host . '`, so no primary entry was made.' . PHP_EOL . PHP_EOL);

				return FALSE;
			}

			$this->db_link->select_db($this->host);

			$existing = $this->db_link->query('SELECT COUNT(*) FROM Entry JOIN Assignment ON Assignment.Parentid = Entry.id AND Assignment.Childid = 0')->fetch_row();

			if((int) $existing[0] > 0) {
				print('The primary entry for `' . $this->host . '` already exists; kept as it is.' . PHP_EOL . PHP_EOL);

				return TRUE;
			}

			$title = $this->promptText(['message'=>'Site title, for the primary entry (you can change this later with modify.php)', 'argv_index'=>4]);

			if(!strlen($title)) {
				print('No title given, so no primary entry was made.  Run this again to make one.' . PHP_EOL . PHP_EOL);

				return FALSE;
			}

			$subtitle = $this->promptText(['message'=>'Site subtitle, may be empty (you can change this later with modify.php)', 'argv_index'=>5]);

			$domain_parts = explode('.', $this->domain);
			$suggested_code = preg_replace('/[^A-Za-z0-9-]/', '', $title) . '.' . end($domain_parts);

			$code = $this->promptText(['message'=>'Code for the primary entry (you can change this later with modify.php)', 'argv_index'=>6, 'default'=>$suggested_code]);

			$statement = $this->db_link->prepare('INSERT INTO Entry (Title, Subtitle, ListTitle, ListTitleSortKey, Code, Publish, OriginalCreationDate, LastModificationDate) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())');
			$statement->bind_param('sssss', $title, $subtitle, $title, $title, $code);
			$statement->execute();

			$entry_id = (int) $this->db_link->insert_id;

			if(!$entry_id) {
				print('The primary entry could NOT be written.' . PHP_EOL . PHP_EOL);

				return FALSE;
			}

			$statement = $this->db_link->prepare('INSERT INTO Assignment (Parentid, Childid, OriginalCreationDate, LastModificationDate) VALUES (?, 0, NOW(), NOW())');
			$statement->bind_param('i', $entry_id);
			$statement->execute();

			print('Made the primary entry: ' . $title . ($subtitle !== '' ? ' : ' . $subtitle : '') . ' (' . $code . ', Entry ' . $entry_id . ').' . PHP_EOL . PHP_EOL);

			$this->flushPageCache();

			return TRUE;
		}

			/*
				Anything the site served before it had a primary entry is in
				the page cache as a page with no title -- autonomybooks.com's
				front page said "Untitled" for that reason -- and nginx serves
				the cached file without asking the engine.  So the site's
				cache goes, under both names, the moment its entry exists.
			*/

		public function flushPageCache() {
			ggreq('classes/Cache/PageCache.php');

			$page_cache = new PageCache(['handler'=>NULL]);

			foreach([$this->domain, 'www.' . $this->domain] as $host) {
				$page_cache->FlushDomain(['domain'=>$host]);
			}

			print('Cleared the page cache for ' . $this->domain . ' (' . count($page_cache->RemovedFiles()) . ' file(s)).' . PHP_EOL . PHP_EOL);

			return TRUE;
		}

		public function promptText($args) {
			$default = isset($args['default']) ? $args['default'] : '';

			print($args['message'] . ($default !== '' ? ' [' . $default . ']' : '') . ': ');

			if(array_key_exists($args['argv_index'], $this->argv)) {
				$answer = trim($this->argv[$args['argv_index']]);
				print($answer . PHP_EOL);
			} else {
				$answer = trim((string) fgets($this->handle));
			}

			print(PHP_EOL);

			return ($answer === '') ? $default : $answer;
		}

		public function buildDatabase() {
			print("Building " . $this->host . " database." . PHP_EOL . PHP_EOL);
			
			$create_database_command = 'mysql -e "CREATE DATABASE ' . $this->host . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci"';
				
			shell_exec($create_database_command);
			
			print("Successfully built " . $this->host . " database." . PHP_EOL . PHP_EOL);
			
			return TRUE;
		}
		
		public function importCloneFromSource() {
			print("Importing clonefrom.sql template database.\n\n");
			
				/*
					Named rather than left to the client default, so the read matches
					the write in rebuildCloneFromSource().  The template's text is
					ASCII and its high bytes are inside _binary literals, which no
					connection charset touches, so this is about not drifting rather
					than about repairing anything.
				*/

			$import_sql_command = 'mysql --default-character-set=utf8mb4 ' . $this->host . ' < ' . GGCMS_CLI_DIR . $this->source_filename;
			
			$output = shell_exec($import_sql_command);
			
			print("\n\n");
			
			print("Successfully imported clonefrom.sql template database into " . $this->host . ".\n\n");
			
			return TRUE;
		}
		
		public function userConfirmImport() {
			return $this->basicConfirmDialogue([
				'message'=>'Ready to import clonefrom.sql database.',
				'argv_index'=>3,
			]);
		}
		
		public function setSourceFileName() {
			return $this->source_filename = 'sql/clonefrom.sql';
		}
		
		public function rebuildCloneFromSource() {
			print($this->source_filename . ' regeneration started.');
			
			if(cli_isfile($this->source_filename)) {
				unlink(GGCMS_CLI_DIR . $this->source_filename);
			}
			
			$mysql_dump_args = '--default-character-set=utf8mb4 --no-tablespaces -N --routines --skip-triggers --set-gtid-purged=OFF';
			$mysql_dump_command = 'mysqldump ' . $mysql_dump_args . ' clonefrom > ' . GGCMS_CLI_DIR . $this->source_filename;
			
			$output = shell_exec($mysql_dump_command);
			
			print("\n\n");
			print($this->source_filename . ' regenerated successfully.' . "\n\n");
			
			return TRUE;
		#	print("BT: DUMP!|" . $mysql_dump_command . "|");
		}
		
		public function userConfirm() {
			return $this->basicConfirmDialogue([
				'message'=>'Overall installation beginning.',
				'argv_index'=>2,
			]);
		}
		
		public function userConfirmSource() {
			$source_filename = $this->source_filename;
			
			print('Rebuild template database (`' . $source_filename . '`)?' . "\n");
			
			if(!cli_isfile($source_filename)) {
				print("\n" . 'Source file does not exist!  Install will crash if you attempt to proceed with "n"!' . "\n");
			}
			
			$creation_time = filectime(GGCMS_CLI_DIR . $source_filename);
			$modification_time = filemtime(GGCMS_CLI_DIR . $source_filename);
			$access_time = fileatime(GGCMS_CLI_DIR . $source_filename);
			
			print($source_filename .
				' - Created: ' .
				date('Y-m-d H:i:s', $creation_time) .
				'; Modified: ' .
				date('Y-m-d H:i:s', $modification_time) .
				'; Last Access: ' .
				date('Y-m-d H:i:s', $access_time)
			. "\n");
			
			return $this->basicConfirmDialogue([
				'message'=>'Rebuild?',
				'argv_index'=>3,
			]);
		}
		
		public function bannerMessageText() {
			return 'New Domain Installation';
		}
		
		public function confirmDomainText() {
			return 'Installing Domain: ';
		}
	}
	
?>