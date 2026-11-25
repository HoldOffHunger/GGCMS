<?php

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/CLIAccess.php');

	/*
		Every Let's Encrypt certificate on this host: when it expires, whether
		certbot can still renew it, and -- with --repair -- making it able to.

		On 25 November 2026 seventeen of the nineteen certificates were six
		days from expiry.  They had been issued with --apache, and certbot
		renews a certificate the way it was issued, so every twelve hours it
		looked for an Apache vhost on port 80.  nginx has owned port 80 since
		September; Apache listens on 127.0.0.1:8443.  Every attempt failed with
		"Unable to find a virtual host listening on port 80", nothing said
		so, and each attempt gracefully restarted Apache seventeen times in a
		minute, which showed up as unexplained slow spells twice a day.  The
		August 2026 outage that Docs/Operations.md opens with was also an
		expired certificate nobody was told about.

		What a healthy certificate looks like here:

		  * authenticator = webroot, with every name it covers mapped to
		    /var/www/html.  nginx answers /.well-known/acme-challenge/ from
		    there for every site, and DomainInstaller issues new certificates
		    that way.
		  * no installer.  nginx reads the files under live/ directly, so
		    nothing has to edit a vhost.
		  * a deploy hook that reloads nginx, or a renewed certificate sits on
		    disk while nginx serves the old one until something restarts it.
		  * the challenge folder actually answers for each name -- this writes
		    a probe file and fetches it from nginx on 127.0.0.1:80 with each
		    Host and Let's Encrypt's own user agent, past the bot filters.

		  check_certificates.php                      # report every certificate
		  check_certificates.php --cert=revoltlib.com # just one
		  check_certificates.php --repair             # fix what can be fixed
		  check_certificates.php --repair --quiet     # cron: silent when healthy

		--repair moves an apache or standalone certificate to webroot, keeping
		a copy of the old renewal file under /var/backups/ggcms/letsencrypt/,
		proves the new file with a dry run against Let's Encrypt's staging
		server (putting the old file back if the dry run fails), and renews
		for real straight away when the certificate is inside certbot's own
		30-day window.  It also installs the nginx reload hook if it is
		missing.

		Exits 0 when every certificate is healthy, 1 when one needs attention
		but is not yet close (wrong authenticator, failed probe, missing hook,
		inside --warn days), and 2 when one expires inside --fail days, cannot
		be read, or a repair failed.  Cron mails on 2.  --warn defaults to 21
		days and --fail to 10: certbot starts renewing at 30, so a certificate
		at 21 has already missed nine days of attempts.
	*/

	class CertificateCheck {
		use CLIAccess;

		public $renewal_dir;
		public $hook_dir;
		public $webroot;
		public $backup_dir;
		public $warn_days;
		public $fail_days;
		public $only_cert;
		public $repair;
		public $quiet;

		public $messages = [];

		public function checkCertificates() {
			$this->readArguments();

			$exit_code = 0;

			$hook_code = $this->checkReloadHook();
			$exit_code = max($exit_code, $hook_code);

			$renewal_files = glob($this->renewal_dir . '/*.conf');

			if($renewal_files === FALSE || !$renewal_files) {
				$this->messages[] = 'FAIL -- no renewal files in ' . $this->renewal_dir;
				$exit_code = 2;
				$renewal_files = [];
			}

			$results = [];

			foreach($renewal_files as $renewal_file) {
				$cert_name = basename($renewal_file, '.conf');

				if($this->only_cert !== NULL && $cert_name !== $this->only_cert) {
					continue;
				}

				$result = $this->checkCertificate([
					'cert_name'=>$cert_name,
					'renewal_file'=>$renewal_file,
				]);

				$exit_code = max($exit_code, $result['code']);
				$results[] = $result;
			}

			if($this->only_cert !== NULL && !$results) {
				$this->messages[] = 'FAIL -- no certificate named ' . $this->only_cert;
				$exit_code = 2;
			}

			if(!$this->quiet || $exit_code) {
				$this->bannerMessage();

				print('UTC ' . gmdate('Y-m-d H:i') . PHP_EOL . PHP_EOL);

				$rows = [];

				foreach($results as $result) {
					unset($result['code']);
					$rows[] = $result;
				}

				if($rows) {
					print(arr2textTable($rows) . PHP_EOL);
				}

				foreach($this->messages as $message) {
					print($message . PHP_EOL);
				}

				print(PHP_EOL);
			}

			return $exit_code;
		}

		public function readArguments() {
			$this->renewal_dir = rtrim($this->argumentValue(['name'=>'renewal-dir', 'default'=>'/etc/letsencrypt/renewal']), '/');
			$this->hook_dir = rtrim($this->argumentValue(['name'=>'hook-dir', 'default'=>'/etc/letsencrypt/renewal-hooks/deploy']), '/');
			$this->webroot = rtrim($this->argumentValue(['name'=>'webroot', 'default'=>'/var/www/html']), '/');
			$this->backup_dir = rtrim($this->argumentValue(['name'=>'backup-dir', 'default'=>'/var/backups/ggcms/letsencrypt']), '/');
			$this->warn_days = (int)$this->argumentValue(['name'=>'warn', 'default'=>21]);
			$this->fail_days = (int)$this->argumentValue(['name'=>'fail', 'default'=>10]);
			$this->only_cert = $this->argumentValue(['name'=>'cert', 'default'=>NULL]);
			$this->repair = in_array('--repair', $this->argv, TRUE);
			$this->quiet = in_array('--quiet', $this->argv, TRUE);

			return TRUE;
		}

		public function argumentValue($args) {
			$name = $args['name'];
			$default = $args['default'];

			foreach($this->argv as $argument) {
				if(strpos($argument, '--' . $name . '=') === 0) {
					return substr($argument, strlen($name) + 3);
				}
			}

			return $default;
		}

		public function checkCertificate($args) {
			$cert_name = $args['cert_name'];
			$renewal_file = $args['renewal_file'];

			$notes = [];
			$code = 0;

			$renewal = $this->readRenewal(['file'=>$renewal_file]);
			$certificate = $this->readCertificate(['file'=>$renewal['cert'] ?? '']);

			if($certificate === NULL) {
				return $this->resultRow([
					'cert_name'=>$cert_name,
					'renewal'=>$renewal,
					'days'=>NULL,
					'probe'=>'-',
					'status'=>'FAIL',
					'notes'=>['cannot read ' . ($renewal['cert'] ?? 'the cert path in ' . $renewal_file)],
					'code'=>2,
				]);
			}

			$authenticator = $renewal['authenticator'] ?? '';

			if($authenticator !== 'webroot') {
				if($this->repair) {
					$repaired = $this->repairRenewal([
						'cert_name'=>$cert_name,
						'renewal_file'=>$renewal_file,
						'names'=>$certificate['names'],
						'days'=>$certificate['days'],
					]);

					$notes[] = $repaired['note'];
					$code = max($code, $repaired['code']);

					$renewal = $this->readRenewal(['file'=>$renewal_file]);
					$certificate = $this->readCertificate(['file'=>$renewal['cert'] ?? '']) ?? $certificate;
					$authenticator = $renewal['authenticator'] ?? '';
				} else {
					$notes[] = $authenticator . ' cannot renew while nginx owns port 80; run with --repair';
					$code = max($code, 1);
				}
			}

			if(($renewal['installer'] ?? '') !== '' && $authenticator === 'webroot') {
				$notes[] = 'installer = ' . $renewal['installer'] . ' left in place';
			}

			$probe = '-';

			if($authenticator === 'webroot') {
				$failed_names = $this->probeChallenge(['names'=>$certificate['names']]);

				if($failed_names) {
					$probe = 'FAIL';
					$notes[] = 'challenge not served for ' . implode(', ', $failed_names);
					$code = max($code, 1);
				} else {
					$probe = 'OK';
				}
			}

			$days = $certificate['days'];

			if($days < $this->fail_days) {
				$code = 2;
				$notes[] = 'expires in under ' . $this->fail_days . ' days';
			} elseif($days < $this->warn_days) {
				$code = max($code, 1);
				$notes[] = 'renewal is overdue; certbot starts at 30 days';
			}

			return $this->resultRow([
				'cert_name'=>$cert_name,
				'renewal'=>$renewal,
				'days'=>$days,
				'expires'=>$certificate['expires'],
				'probe'=>$probe,
				'status'=>['OK', 'WARN', 'FAIL'][$code],
				'notes'=>$notes,
				'code'=>$code,
			]);
		}

		public function resultRow($args) {
			return [
				'certificate'=>$args['cert_name'],
				'expires'=>$args['expires'] ?? '-',
				'days'=>$args['days'] === NULL ? '-' : (string)$args['days'],
				'method'=>($args['renewal']['authenticator'] ?? '-') ?: '-',
				'probe'=>$args['probe'],
				'status'=>$args['status'],
				'notes'=>$args['notes'] ? implode('; ', $args['notes']) : ' ',
				'code'=>$args['code'],
			];
		}

			/*
				A renewal file is configobj: key = value lines, a [renewalparams]
				section, and inside it a [[webroot_map]] subsection.  Only the
				top-level keys and renewalparams matter here.
			*/

		public function readRenewal($args) {
			$lines = @file($args['file'], FILE_IGNORE_NEW_LINES);

			$renewal = [];
			$section = '';

			foreach($lines ?: [] as $line) {
				$line = trim($line);

				if($line === '' || $line[0] === '#') {
					continue;
				}

				if(preg_match('/^\[\[(.+)\]\]$/', $line, $match)) {
					$section = $match[1];
					continue;
				}

				if(preg_match('/^\[(.+)\]$/', $line, $match)) {
					$section = $match[1];
					continue;
				}

				if(!preg_match('/^([^=]+?)\s*=\s*(.*)$/', $line, $match)) {
					continue;
				}

				if($section === '' || $section === 'renewalparams') {
					$renewal[$match[1]] = $match[2];
				}
			}

			return $renewal;
		}

		public function readCertificate($args) {
			$file = $args['file'];

			if($file === '' || !is_readable($file)) {
				return NULL;
			}

			$parsed = @openssl_x509_parse((string)file_get_contents($file));

			if(!$parsed || !isset($parsed['validTo_time_t'])) {
				return NULL;
			}

			$names = [];

			$subject_alt_names = $parsed['extensions']['subjectAltName'] ?? '';

			foreach(explode(',', $subject_alt_names) as $entry) {
				$entry = trim($entry);

				if(strpos($entry, 'DNS:') === 0) {
					$names[] = substr($entry, 4);
				}
			}

			if(!$names && isset($parsed['subject']['CN'])) {
				$names[] = $parsed['subject']['CN'];
			}

			return [
				'names'=>$names,
				'expires'=>gmdate('Y-m-d', $parsed['validTo_time_t']),
				'days'=>(int)floor(($parsed['validTo_time_t'] - time()) / 86400),
			];
		}

			/*
				The repair, in order: keep a copy, rewrite, prove it with a
				staging dry run, and put the copy back if the proof fails.  A
				renewal file certbot cannot parse would stop that certificate
				renewing for good, so nothing is left in place unproven.
			*/

		public function repairRenewal($args) {
			$cert_name = $args['cert_name'];
			$renewal_file = $args['renewal_file'];
			$names = $args['names'];
			$days = $args['days'];

			if(!$names) {
				return ['note'=>'not repaired: the certificate names no domains', 'code'=>2];
			}

			if(!is_dir($this->backup_dir) && !@mkdir($this->backup_dir, 0700, TRUE)) {
				return ['note'=>'not repaired: cannot create ' . $this->backup_dir, 'code'=>2];
			}

			$original = (string)file_get_contents($renewal_file);
			$backup_file = $this->backup_dir . '/' . $cert_name . '.conf.' . gmdate('Ymd-His');

			if(@file_put_contents($backup_file, $original) === FALSE) {
				return ['note'=>'not repaired: cannot write ' . $backup_file, 'code'=>2];
			}

			$rewritten = $this->webrootRenewalText([
				'original'=>$original,
				'names'=>$names,
			]);

			if(@file_put_contents($renewal_file, $rewritten) === FALSE) {
				return ['note'=>'not repaired: cannot write ' . $renewal_file, 'code'=>2];
			}

			$dry_run = $this->runCertbot(['arguments'=>'renew --dry-run --no-random-sleep-on-renew --cert-name ' . escapeshellarg($cert_name)]);

			if(!$dry_run['succeeded']) {
				file_put_contents($renewal_file, $original);

				return ['note'=>'not repaired: the staging dry run failed, old file restored (' . $dry_run['summary'] . ')', 'code'=>2];
			}

			if($days >= 30) {
				return ['note'=>'moved to webroot; dry run passed', 'code'=>0];
			}

			$renewal = $this->runCertbot(['arguments'=>'renew --no-random-sleep-on-renew --cert-name ' . escapeshellarg($cert_name)]);

			if(!$renewal['succeeded']) {
				return ['note'=>'moved to webroot, but the renewal failed (' . $renewal['summary'] . ')', 'code'=>2];
			}

			return ['note'=>'moved to webroot and renewed', 'code'=>0];
		}

			/*
				authenticator becomes webroot; installer goes, since nginx
				needs nothing edited; a standalone certificate's pre_hook and
				post_hook, which stopped and started the web server around the
				renewal, go too.  Any old webroot settings are replaced, and the
				new [[webroot_map]] is written at the end of [renewalparams],
				where configobj expects a subsection.
			*/

		public function webrootRenewalText($args) {
			$original = $args['original'];
			$names = $args['names'];

			$was_standalone = (bool)preg_match('/^authenticator\s*=\s*standalone\s*$/m', $original);

			$drop_keys = ['installer', 'webroot_path', 'http01_port', 'http01_address'];

			if($was_standalone) {
				$drop_keys[] = 'pre_hook';
				$drop_keys[] = 'post_hook';
			}

			$webroot_map = ['[[webroot_map]]'];

			foreach($names as $name) {
				$webroot_map[] = $name . ' = ' . $this->webroot;
			}

			$output = [];
			$section = '';
			$skipping_map = FALSE;
			$map_written = FALSE;

			foreach(explode("\n", rtrim($original, "\n")) as $line) {
				$trimmed = trim($line);

				if(preg_match('/^\[\[(.+)\]\]$/', $trimmed, $match)) {
					$skipping_map = ($match[1] === 'webroot_map');

					if(!$skipping_map) {
						$output[] = $line;
					}

					continue;
				}

				if(preg_match('/^\[(.+)\]$/', $trimmed, $match)) {
					if($section === 'renewalparams' && !$map_written) {
						array_push($output, ...$webroot_map);
						$map_written = TRUE;
					}

					$section = $match[1];
					$skipping_map = FALSE;
					$output[] = $line;

					if($section === 'renewalparams') {
						$output[] = 'webroot_path = ' . $this->webroot . ',';
					}

					continue;
				}

				if($skipping_map) {
					continue;
				}

				if($section === 'renewalparams' && preg_match('/^([a-z0-9_]+)\s*=/', $trimmed, $match)) {
					if($match[1] === 'authenticator') {
						$output[] = 'authenticator = webroot';
						continue;
					}

					if(in_array($match[1], $drop_keys, TRUE)) {
						continue;
					}
				}

				$output[] = $line;
			}

			if(!$map_written) {
				if($section !== 'renewalparams') {
					$output[] = '[renewalparams]';
					$output[] = 'webroot_path = ' . $this->webroot . ',';
				}

				array_push($output, ...$webroot_map);
			}

			return implode("\n", $output) . "\n";
		}

		public function runCertbot($args) {
			$output = [];
			$status = 1;

			exec('certbot ' . $args['arguments'] . ' 2>&1', $output, $status);

			$summary = '';

			foreach($output as $line) {
				if(preg_match('/(error|fail|unable)/i', $line)) {
					$summary = trim($line);
					break;
				}
			}

			return [
				'succeeded'=>($status === 0),
				'summary'=>$summary !== '' ? $summary : 'certbot exited ' . $status,
			];
		}

			/*
				Ask nginx for a file in the challenge folder, once per name, the
				way the validation server will: port 80, that Host, Let's
				Encrypt's user agent.  A bot filter or a missing location block
				fails here weeks before it fails a renewal.
			*/

		public function probeChallenge($args) {
			$names = $args['names'];

			$challenge_dir = $this->webroot . '/.well-known/acme-challenge';

			if(!is_dir($challenge_dir) && !@mkdir($challenge_dir, 0755, TRUE)) {
				return $names;
			}

			$token = 'ggcms-probe-' . bin2hex(random_bytes(8));
			$probe_file = $challenge_dir . '/' . $token;

			if(@file_put_contents($probe_file, $token) === FALSE) {
				return $names;
			}

			$failed = [];

			foreach($names as $name) {
				$context = stream_context_create([
					'http'=>[
						'method'=>'GET',
						'header'=>'Host: ' . $name . "\r\n",
						'user_agent'=>'Mozilla/5.0 (compatible; Let\'s Encrypt validation server; +https://www.letsencrypt.org)',
						'timeout'=>10,
						'follow_location'=>0,
						'ignore_errors'=>TRUE,
					],
				]);

				$body = @file_get_contents('http://127.0.0.1/.well-known/acme-challenge/' . $token, FALSE, $context);

				if(trim((string)$body) !== $token) {
					$failed[] = $name;
				}
			}

			@unlink($probe_file);

			return $failed;
		}

			/*
				A deploy hook runs once per certificate actually renewed.  This
				is the one file of the repair that is not about a single
				certificate, so it is reported on its own line.
			*/

		public function ReloadHookFile() {
			return $this->hook_dir . '/ggcms-reload-nginx.sh';
		}

		public function ReloadHookText() {
			return "#!/bin/sh\n"
				. "# Reload nginx after certbot writes a renewed certificate, so the new one is\n"
				. "# served.  nginx terminates TLS here; Apache sits behind it on 127.0.0.1.\n"
				. "# Installed by check_certificates.php --repair.\n"
				. "nginx -t -q && systemctl reload nginx\n";
		}

		public function checkReloadHook() {
			$hook_file = $this->ReloadHookFile();

			if(is_file($hook_file) && is_executable($hook_file)) {
				return 0;
			}

			if($this->repair) {
				if(!is_dir($this->hook_dir)) {
					@mkdir($this->hook_dir, 0755, TRUE);
				}

				if(@file_put_contents($hook_file, $this->ReloadHookText()) !== FALSE && @chmod($hook_file, 0755)) {
					$this->messages[] = 'Installed the nginx reload hook, ' . $hook_file;

					return 0;
				}

				$this->messages[] = 'FAIL -- could not install the nginx reload hook, ' . $hook_file;

				return 2;
			}

			$this->messages[] = 'WARN -- no nginx reload hook at ' . $hook_file . '; a renewed certificate would not be served until nginx restarts.  Run with --repair.';

			return 1;
		}

					// Script-Level Functions

		public function bannerMessageText() {
			return 'Certificates';
		}
	}

?>