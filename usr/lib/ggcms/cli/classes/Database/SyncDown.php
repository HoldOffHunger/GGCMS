<?php

	/*
		Copies production databases down to a workstation's MySQL.

		A workstation that renders pages, warms caches or tests changes against
		a copy of the data is only as good as that copy, and a copy drifts:
		content changes, and so does the schema.  Pages rendered against a stale
		copy look right and are wrong.  So the copy is refreshed from the live
		host on demand rather than remembered.

		Dry by default.  Without --apply it lists what the host holds and what
		it would do, and changes nothing locally.

		PER DATABASE

		  1. mysqldump on the host, streamed over ssh into <dumps>/<db>.sql.gz.partial
		  2. the dump must end with mysqldump's "Dump completed" footer, or it
		     is not a dump -- a dropped connection leaves a file that looks
		     exactly like one
		  3. only then is it renamed to <db>.sql.gz, replacing the previous one
		  4. imported into the local server, which replaces every table the dump
		     carries; a table that exists only locally is left alone and reported
		  5. the column lists of host and copy are compared, table names folded
		     to lower case, because a Windows MySQL lowercases them

		ONE OR A FEW TABLES

		  --tables=Entry,Description dumps and imports only those tables, into
		  <dumps>/<db>--tables--Entry-Description.sql.gz, so the one full dump
		  kept per database is never replaced by a partial one.  Every other
		  local table is left exactly as it was.  A test that mangled two tables
		  on a workstation is repaired by syncing those two, not the database.

		Every external command is started with an argument array, never a shell
		string.  On Windows escapeshellarg() replaces double quotes with spaces,
		which silently mangles a command bound for a remote shell.
	*/

	class SyncDown {
		public $argv;
		public $host;
		public $only;
		public $tables;
		public $dumps;
		public $ssh;
		public $mysql;
		public $local_host;
		public $local_port;
		public $local_user;
		public $apply;
		public $last_ssh_at;
		
		public function __construct($args) {
			$this->argv = (array) $args['argv'];
		}

			// Entry point
			// -------------------------------------------------

		public function syncDown() {
			$this->readArguments();

			print("GGCMS - Sync Down\n");
			print("Host     : " . $this->host . "\n");
			print("Local    : " . $this->local_user . '@' . $this->local_host . ':' . $this->local_port . " via " . $this->mysql . "\n");
			print("Dumps    : " . $this->dumps . "\n");
			print("Tables   : " . (count($this->tables) ? implode(', ', $this->tables) : 'all') . "\n");
			print("Mode     : " . ($this->apply ? 'APPLY -- local databases will be replaced' : 'dry run') . "\n\n");

			$databases = $this->hostDatabases();

			if(!count($databases)) {
				print("No databases matched on the host.\n");

				return 2;
			}

			$results = [];

			foreach($databases as $database => $bytes) {
				$results[] = $this->syncOne(['database'=>$database, 'bytes'=>$bytes]);
			}

			print("\n");

			$failed = 0;

			foreach($results as $result) {
				printf("  %-24s %-8s %s\n", $result['database'], $result['status'], $result['note']);

				if($result['status'] === 'FAILED') {
					$failed++;
				}
			}

			return $failed ? 2 : 0;
		}

			// Arguments
			// -------------------------------------------------

		public function readArguments() {
			$this->host        = $this->argumentValue('host', (string) getenv('GGCMS_SYNC_HOST'));
			$this->only        = $this->argumentValue('database', '');
			$this->tables      = $this->tableList($this->argumentValue('tables', ''));
			$this->dumps       = rtrim(str_replace('\\', '/', $this->argumentValue('dumps', (string) getenv('GGCMS_SYNC_DUMPS'))), '/');
			$this->ssh         = $this->argumentValue('ssh', 'ssh');
			$this->mysql       = $this->argumentValue('mysql', 'mysql');
			$this->local_host  = $this->argumentValue('local-host', '127.0.0.1');
			$this->local_port  = (int) $this->argumentValue('local-port', 3306);
			$this->local_user  = $this->argumentValue('local-user', 'root');
			$this->apply       = $this->argumentPresent('apply');

			if(!strlen($this->host)) {
				$this->fail('--host is required, or set GGCMS_SYNC_HOST: --host=***YOUR_SSH_USER***@***YOUR_PRODUCTION_HOST_HERE***');
			}

			if(!strlen($this->dumps)) {
				$this->fail('--dumps is required, or set GGCMS_SYNC_DUMPS: a directory on this machine to keep one dump per database');
			}

			if(!is_dir($this->dumps) && !@mkdir($this->dumps, 0755, TRUE)) {
				$this->fail('cannot create ' . $this->dumps);
			}

			return TRUE;
		}

		public function argumentValue($name, $default) {
			foreach($this->argv as $argument) {
				if(strpos($argument, '--' . $name . '=') === 0) {
					return substr($argument, strlen($name) + 3);
				}
			}

			return $default;
		}

		public function argumentPresent($name) {
			return in_array('--' . $name, $this->argv, TRUE);
		}

			/*
				--tables=Entry,Description as a list.  Every name must be a plain
				identifier, because it is placed into the remote mysqldump command
				and into SQL; anything else is refused before a connection opens.
			*/

		public function tableList($value) {
			$tables = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $value)), 'strlen')));

			foreach($tables as $table) {
				if(!$this->validName($table)) {
					$this->fail('--tables takes plain table names separated by commas; refusing "' . $table . '"');
				}
			}

			return $tables;
		}

			/*
				A table sync writes its own file.  Were it to use <db>.sql.gz it
				would replace the one full dump kept per database with a partial
				one, and the next full import from that file would quietly lose
				every other table.
			*/

		public function dumpSuffix() {
			return count($this->tables) ? '--tables--' . implode('-', $this->tables) : '';
		}

			/*
				Routines and events belong to the database, not to any table, so a
				table sync leaves them out rather than replacing the local ones
				with whatever the host has.  Triggers belong to their tables and
				travel with them.
			*/

		public function dumpObjects() {
			return count($this->tables) ? '--triggers' : '--routines --events --triggers';
		}

		public function tableFilter() {
			if(!count($this->tables)) {
				return '';
			}

			return " AND LOWER(TABLE_NAME) IN ('" . implode("','", array_map('strtolower', $this->tables)) . "')";
		}

			// The host
			// -------------------------------------------------

			/*
				Every database the host holds that is not the server's own, with
				its size.  alldictionaries has no site configuration and is
				included like any other: a copy without it cannot render a
				definition.
			*/

		public function hostDatabases() {
			$sql = "SELECT s.SCHEMA_NAME, COALESCE(SUM(t.DATA_LENGTH + t.INDEX_LENGTH), 0) "
				. "FROM information_schema.SCHEMATA s LEFT JOIN information_schema.TABLES t ON t.TABLE_SCHEMA = s.SCHEMA_NAME "
				. "WHERE s.SCHEMA_NAME NOT IN ('information_schema','performance_schema','mysql','sys','defaultdb') "
				. "GROUP BY s.SCHEMA_NAME ORDER BY s.SCHEMA_NAME";

			$run = $this->run([$this->ssh, '-o', 'BatchMode=yes', $this->host, 'mysql -N -e ' . $this->remoteQuote($sql)]);

			if($run['exit'] !== 0) {
				$this->fail('could not list databases on ' . $this->host . ': ' . trim($run['stderr']));
			}

			$databases = [];

			foreach(explode("\n", trim($run['stdout'])) as $line) {
				$fields = explode("\t", trim($line));

				if(count($fields) !== 2 || !$this->validName($fields[0])) {
					continue;
				}

				if(strlen($this->only) && stripos($fields[0], $this->only) === FALSE) {
					continue;
				}

				$databases[$fields[0]] = (int) $fields[1];
			}

			return $databases;
		}

			// One database
			// -------------------------------------------------

		public function syncOne($args) {
			$database = $args['database'];
			$result = ['database'=>$database, 'status'=>'FAILED', 'note'=>''];

			$target  = $this->dumps . '/' . $database . $this->dumpSuffix() . '.sql.gz';
			$partial = $target . '.partial';

			printf("%-24s host %9s  ", $database, $this->humanBytes($args['bytes']));

			if(!$this->apply) {
				print("would dump to " . $target . " and import\n");
				$result['status'] = 'DRYRUN';
				$result['note'] = 'host ' . $this->humanBytes($args['bytes']);

				return $result;
			}

			$started = microtime(TRUE);

			$dump = $this->dumpTo(['database'=>$database, 'path'=>$partial]);

			if($dump !== TRUE) {
				@unlink($partial);
				print("dump failed\n");
				$result['note'] = $dump . '; previous dump and local copy left alone';

				return $result;
			}

			if(!$this->endsCleanly($partial)) {
				@unlink($partial);
				print("incomplete dump\n");
				$result['note'] = 'no "Dump completed" footer; previous dump and local copy left alone';

				return $result;
			}

			if(!@rename($partial, $target)) {
				print("rename failed\n");
				$result['note'] = 'could not rename ' . $partial . ' into place';

				return $result;
			}

			printf("dump %9s  ", $this->humanBytes((int) filesize($target)));

			$import = $this->importFrom(['database'=>$database, 'path'=>$target]);

			if($import !== TRUE) {
				print("import failed\n");
				$result['note'] = $import;

				return $result;
			}

			$drift = $this->schemaDrift(['database'=>$database]);

			printf("imported in %.0fs  %s\n", microtime(TRUE) - $started, $drift['summary']);

			$result['status'] = 'OK';
			$result['note'] = $drift['summary'];

			foreach($drift['lines'] as $line) {
				print("    " . $line . "\n");
			}

			return $result;
		}

			/*
				The same mysqldump settings backup_all_databases.php uses:
				utf8mb4 declared in the dump, one consistent transaction, and no
				GTID_PURGED line, which a server with its own GTID history refuses.
				pipefail, so a mysqldump that dies half way is not reported as
				gzip's success.
			*/

		public function dumpTo($args) {
			$remote = "bash -c " . $this->remoteQuote(
				'set -o pipefail; nice mysqldump --max_allowed_packet=64M --default-character-set=utf8mb4 --set-charset'
				. ' --no-tablespaces ' . $this->dumpObjects() . ' --single-transaction --quick --set-gtid-purged=OFF '
				. $args['database'] . (count($this->tables) ? ' ' . implode(' ', $this->tables) : '') . ' | gzip -6'
			);

			$out = @fopen($args['path'], 'wb');

			if(!$out) {
				return 'cannot write ' . $args['path'];
			}

			$pipes = [];
			$process = proc_open([$this->ssh, '-o', 'BatchMode=yes', $this->host, $remote], [1=>['pipe', 'w'], 2=>['pipe', 'w']], $pipes);

			if(!is_resource($process)) {
				fclose($out);

				return 'could not start ssh';
			}

			stream_copy_to_stream($pipes[1], $out);
			fclose($out);

			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);

			$exit = proc_close($process);

			if($exit !== 0) {
				return 'mysqldump over ssh exited ' . $exit . (strlen(trim($stderr)) ? ' -- ' . substr(trim($stderr), 0, 200) : '');
			}

			return TRUE;
		}

			/*
				Read the whole compressed stream and keep its tail.  gzip cannot
				seek, and a dump that stopped early decompresses without complaint
				-- only mysqldump's own last line says it finished.
			*/

		public function endsCleanly($path) {
			$gz = @gzopen($path, 'rb');

			if(!$gz) {
				return FALSE;
			}

			$tail = '';

			while(!gzeof($gz)) {
				$chunk = gzread($gz, 1048576);

				if($chunk === FALSE) {
					gzclose($gz);

					return FALSE;
				}

				$tail = substr($tail . $chunk, -512);
			}

			gzclose($gz);

			return strpos($tail, '-- Dump completed') !== FALSE;
		}

		public function importFrom($args) {
			$database = $args['database'];

			$create = $this->run(array_merge($this->localClient(), ['-e', 'CREATE DATABASE IF NOT EXISTS `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci']));

			if($create['exit'] !== 0) {
				return 'could not create local database: ' . trim($create['stderr']);
			}

			$gz = @gzopen($args['path'], 'rb');

			if(!$gz) {
				return 'cannot read ' . $args['path'];
			}

			$pipes = [];
			$process = proc_open(array_merge($this->localClient(), [$database]), [0=>['pipe', 'r'], 1=>['pipe', 'w'], 2=>['pipe', 'w']], $pipes);

			if(!is_resource($process)) {
				gzclose($gz);

				return 'could not start ' . $this->mysql;
			}

			$broken = FALSE;

			while(!gzeof($gz)) {
				$chunk = gzread($gz, 1048576);

				if($chunk === FALSE || @fwrite($pipes[0], $chunk) === FALSE) {
					$broken = TRUE;

					break;
				}
			}

			gzclose($gz);
			fclose($pipes[0]);

			stream_get_contents($pipes[1]);
			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);

			$exit = proc_close($process);

			if($broken || $exit !== 0) {
				return 'local import exited ' . $exit . (strlen(trim($stderr)) ? ' -- ' . substr(trim($stderr), 0, 200) : '');
			}

			return TRUE;
		}

			/*
				Column lists compared, table names folded to lower case.  Anything
				here after a fresh import is a local-only table or column the dump
				did not replace, and it is reported rather than dropped.
			*/

		public function schemaDrift($args) {
			$database = $args['database'];

			$sql = "SELECT LOWER(TABLE_NAME), COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS "
				. "WHERE TABLE_SCHEMA = '" . $database . "'" . $this->tableFilter() . " ORDER BY 1, 2";

			$host = $this->run([$this->ssh, '-o', 'BatchMode=yes', $this->host, 'mysql -N -e ' . $this->remoteQuote($sql)]);
			$local = $this->run(array_merge($this->localClient(), ['-N', '-e', $sql]));

			if($host['exit'] !== 0 || $local['exit'] !== 0) {
				return ['summary'=>'schema not compared', 'lines'=>[]];
			}

			$host_lines = $this->lines($host['stdout']);
			$local_lines = $this->lines($local['stdout']);

			$only_host = array_values(array_diff($host_lines, $local_lines));
			$only_local = array_values(array_diff($local_lines, $host_lines));

			if(!count($only_host) && !count($only_local)) {
				return ['summary'=>'schema identical (' . count($host_lines) . ' columns)', 'lines'=>[]];
			}

			$lines = [];

			foreach(array_slice($only_host, 0, 5) as $line) {
				$lines[] = 'host only:  ' . str_replace("\t", ' ', $line);
			}

			foreach(array_slice($only_local, 0, 5) as $line) {
				$lines[] = 'local only: ' . str_replace("\t", ' ', $line);
			}

			return [
				'summary'=>'SCHEMA DRIFT: ' . count($only_host) . ' host-only, ' . count($only_local) . ' local-only column(s)',
				'lines'=>$lines,
			];
		}

			// Plumbing
			// -------------------------------------------------

			/*
				The local password, if the server has one, comes from MYSQL_PWD,
				which the mysql client reads itself.  It is never an argument,
				because arguments are visible in the process list.
			*/

		public function localClient() {
			return [
				$this->mysql,
				'--host=' . $this->local_host,
				'--port=' . $this->local_port,
				'--user=' . $this->local_user,
				'--default-character-set=utf8mb4',
			];
		}

			/*
				A host may rate-limit new ssh connections.  The GGCMS host's ufw
				'limit' rule refuses a seventh within thirty seconds, and a sync
				of small databases opens three per database in quick succession:
				on 13 September 2026 that locked this machine out mid-sync, and
				every retry renewed the lockout.  So new connections are spaced
				at least --ssh-pace seconds apart (default 6: five in thirty).
			*/

		public function paceSsh() {
			$pace = (float) $this->argumentValue('ssh-pace', 6);

			if(isset($this->last_ssh_at)) {
				$wait = $pace - (microtime(TRUE) - $this->last_ssh_at);

				if($wait > 0) {
					usleep((int) ($wait * 1000000));
				}
			}

			$this->last_ssh_at = microtime(TRUE);

			return TRUE;
		}

		public function run($command) {
			if($command[0] === $this->ssh) {
				$this->paceSsh();
			}

			$pipes = [];
			$process = proc_open($command, [1=>['pipe', 'w'], 2=>['pipe', 'w']], $pipes);

			if(!is_resource($process)) {
				return ['exit'=>-1, 'stdout'=>'', 'stderr'=>'could not start ' . $command[0]];
			}

			$stdout = stream_get_contents($pipes[1]);
			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);

			return ['exit'=>proc_close($process), 'stdout'=>(string) $stdout, 'stderr'=>(string) $stderr];
		}

			/*
				Single quotes for the remote POSIX shell.  The only single quotes
				in anything passed here are SQL string literals, closed and
				reopened around an escaped quote.
			*/

		public function remoteQuote($text) {
			return "'" . str_replace("'", "'\\''", $text) . "'";
		}

		public function validName($name) {
			return (bool) preg_match('/^[A-Za-z0-9_]+$/', $name);
		}

		public function lines($text) {
			return array_values(array_filter(array_map('rtrim', explode("\n", str_replace("\r", '', $text))), 'strlen'));
		}

		public function humanBytes($bytes) {
			$units = ['B', 'KB', 'MB', 'GB'];
			$i = 0;

			while($bytes >= 1024 && $i < count($units) - 1) {
				$bytes /= 1024;
				$i++;
			}

			return sprintf($i ? '%.1f %s' : '%d %s', $bytes, $units[$i]);
		}

		public function fail($message) {
			fwrite(STDERR, $message . "\n");
			exit(1);
		}
	}

?>
