<?php

	/*
		Measures the size and shape of the engine's own source.

		Two questions, one pass.  How big is this thing, and where does the
		bulk actually live -- because "GGCMS is N lines" is a number nobody can
		act on, while "templates are a third of the bytes and a twentieth of
		the code lines" is a fact you can do something about.

		THE COUNTS ARE HONEST ABOUT WHAT THEY ARE.

		Physical lines are exact.  Bytes are exact.  Code, comment and blank
		lines come from a small state machine that tracks block-comment nesting and
		strips // and # tails, and it is deliberately not a PHP parser: a `#`
		inside a string literal will be read as a comment.  The error is small
		and it is always in the same direction, so the ratios stay comparable
		between folders and between runs, which is the whole job.  If you need
		a real parse, this is not the tool.

		It does not count "functions".  ProjectStatistics in the Talasia tree
		learned that the expensive way -- a call-like-line heuristic labelled
		"Functions" overstated that project roughly seventyfold -- so this
		reports comment ratio and bytes per line as its density signals and
		leaves function counting to something that can actually parse.

		SCOPE DEFAULTS TO WHAT YOU WROTE.

		`dep/` is vendored third-party code and is excluded unless asked for.
		Including it by default would answer a different question than the one
		anybody asks, in the same way ProjectStatistics warns that JSON can
		swamp a total.  --root=dep or --root=all when you want it.

		OUTPUT IS NARROW BY DEFAULT AND FILTERS HARD.

		Every argument below exists so the output can be made small enough to
		read, or small enough to hand to an AI without burning a context
		window on rows nobody asked about.
	*/

	depreq('arr2textTable/arr2textTable.php');

	clireq('traits/CLIAccess.php');

	class SourceStatistics {
		use CLIAccess;
		
		public $root;
		public $folder;
		public $depth;
		public $sort;
		public $top;
		public $min_bytes;
		public $show_files;
		public $as_csv;
		public $extensions;

			/*
				Extension -> category.  Anything unlisted lands in Other,
				which is reported rather than hidden: a category nobody
				expected is usually the interesting result.
			*/

		public $category_map = [
			'php'  => 'PHP',
			'phtml'=> 'PHP',
			'inc'  => 'PHP',
			'html' => 'Template',
			'htm'  => 'Template',
			'tpl'  => 'Template',
			'css'  => 'Asset',
			'js'   => 'Asset',
			'json' => 'Data',
			'csv'  => 'Data',
			'txt'  => 'Data',
			'sql'  => 'SQL',
			'md'   => 'Markdown',
			'sh'   => 'Shell',
		];

		public function bannerMessageText() {
			return 'Source Statistics';
		}

			// Entry point

		public function report() {
			$this->readArguments();
			$this->bannerMessage();

			$roots = $this->resolveRoots();

			if(!count($roots)) {
				print("No readable source root.  Checked: " . implode(', ', array_keys($this->rootMap())) . "\n");

				return FALSE;
			}

			$files = [];

			foreach($roots as $root_label => $root_path) {
				$files = array_merge($files, $this->scanTree([
					'root_label' => $root_label,
					'root_path'  => $root_path,
				]));
			}

			if(!count($files)) {
				print("Nothing matched.  Loosen --folder / --ext / --min-bytes.\n");

				return FALSE;
			}

			$this->printGrandTotal($files);
			$this->printGroupTable([
				'files' => $files,
				'key'   => 'group',
				'title' => 'BY FOLDER (depth ' . $this->depth . ')',
			]);
			$this->printGroupTable([
				'files' => $files,
				'key'   => 'category',
				'title' => 'BY CATEGORY',
			]);

			if($this->show_files) {
				$this->printFileTable($files);
			}

			return TRUE;
		}

			// Arguments

		public function rootMap() {
			return [
				'src' => defined('GGCMS_DIR')     ? GGCMS_DIR     : '',
				'cli' => defined('GGCMS_CLI_DIR') ? GGCMS_CLI_DIR : '',
				'dep' => defined('GGCMS_DEP_DIR') ? GGCMS_DEP_DIR : '',
			];
		}

		public function readArguments() {
			$this->root       = $this->argumentValue('root', 'src');
			$this->folder     = $this->argumentValue('folder', '');
			$this->depth      = max(1, (int) $this->argumentValue('depth', 1));
			$this->sort       = $this->argumentValue('sort', 'bytes');
			$this->top        = (int) $this->argumentValue('top', 0);
			$this->min_bytes  = (int) $this->argumentValue('min-bytes', 0);
			$this->show_files = $this->argumentPresent('files');
			$this->as_csv     = $this->argumentPresent('csv');

			$extensions = trim($this->argumentValue('ext', ''));

			$this->extensions = strlen($extensions)
				? array_filter(array_map('strtolower', array_map('trim', explode(',', $extensions))))
				: [];

			if(!in_array($this->sort, ['bytes', 'lines', 'code', 'files'], TRUE)) {
				$this->sort = 'bytes';
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

		public function resolveRoots() {
			$map = $this->rootMap();

			$wanted = ($this->root === 'all') ? array_keys($map) : [$this->root];

			$roots = [];

			foreach($wanted as $label) {
				if(array_key_exists($label, $map) && strlen($map[$label]) && is_dir($map[$label])) {
					$roots[$label] = $map[$label];
				}
			}

			return $roots;
		}

			// Scanning

		public function scanTree($args) {
			$root_label = $args['root_label'];
			$root_path  = rtrim($args['root_path'], '/') . '/';

			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($root_path, FilesystemIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST
			);

			$files = [];

			foreach($iterator as $item) {
				if(!$item->isFile()) {
					continue;
				}

				$absolute = str_replace('\\', '/', $item->getPathname());
				$relative = substr($absolute, strlen($root_path));

				if(strpos($relative, '.git/') === 0) {
					continue;
				}

				$extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));

				if(count($this->extensions) && !in_array($extension, $this->extensions, TRUE)) {
					continue;
				}

				$segments = explode('/', $relative);

					/*
						A file sitting directly in the root has no folder of
						its own.  It is grouped under "(root)" rather than
						dropped, because StandardLibraries.php living loose in
						src/ is exactly the kind of thing worth noticing.
					*/

				$group_parts = array_slice($segments, 0, min($this->depth, max(1, count($segments) - 1)));

				if(count($segments) === 1) {
					$group_parts = ['(root)'];
				}

				$group = $root_label . '/' . implode('/', $group_parts);

				if(strlen($this->folder) && stripos($group, $this->folder) === FALSE) {
					continue;
				}

				$measurement = $this->measureFile($absolute);

				if($measurement['bytes'] < $this->min_bytes) {
					continue;
				}

				$measurement['path']     = $root_label . '/' . $relative;
				$measurement['group']    = $group;
				$measurement['category'] = array_key_exists($extension, $this->category_map)
					? $this->category_map[$extension]
					: 'Other';

				$files[] = $measurement;
			}

			return $files;
		}

			/*
				One file, one pass.  Blank means nothing but whitespace; a line
				is a comment line when removing its comment content leaves it
				blank, so `$x = 1; // why` counts as code and the `// why` on
				its own line counts as comment.  That is the split people mean
				when they ask how commented something is.
			*/

		public function measureFile($absolute) {
			$bytes = (int) @filesize($absolute);

			$measurement = [
				'bytes'   => $bytes,
				'lines'   => 0,
				'code'    => 0,
				'comment' => 0,
				'blank'   => 0,
			];

			$handle = @fopen($absolute, 'rb');

			if($handle === FALSE) {
				return $measurement;
			}

			$in_block = FALSE;

			while(($line = fgets($handle)) !== FALSE) {
				$measurement['lines']++;

				$raw = trim($line);

				if(!strlen($raw)) {
					$measurement['blank']++;

					continue;
				}

				$stripped   = '';
				$length     = strlen($raw);
				$had_comment = FALSE;

				for($i = 0; $i < $length; $i++) {
					if($in_block) {
						if($raw[$i] === '*' && $i + 1 < $length && $raw[$i + 1] === '/') {
							$in_block = FALSE;
							$i++;
						}

						$had_comment = TRUE;

						continue;
					}

					if($raw[$i] === '/' && $i + 1 < $length && $raw[$i + 1] === '*') {
						$in_block    = TRUE;
						$had_comment = TRUE;
						$i++;

						continue;
					}

					if($raw[$i] === '/' && $i + 1 < $length && $raw[$i + 1] === '/') {
						$had_comment = TRUE;

						break;
					}

					if($raw[$i] === '#') {
						$had_comment = TRUE;

						break;
					}

					$stripped .= $raw[$i];
				}

				if(!strlen(trim($stripped))) {
					if($had_comment) {
						$measurement['comment']++;
					} else {
						$measurement['blank']++;
					}
				} else {
					$measurement['code']++;
				}
			}

			fclose($handle);

			return $measurement;
		}

			// Reporting

		public function printGrandTotal($files) {
			$total = $this->sumRows($files);

			print("SCANNED\n");
			print("  root      : " . $this->root . ($this->show_files ? '  (per-file listing on)' : '') . "\n");

			if(strlen($this->folder)) {
				print("  folder    : " . $this->folder . "\n");
			}

			if(count($this->extensions)) {
				print("  extensions: " . implode(', ', $this->extensions) . "\n");
			}

			print("\n");
			print("  files     : " . number_format($total['files']) . "\n");
			print("  bytes     : " . number_format($total['bytes']) . "  (" . $this->humanBytes($total['bytes']) . ")\n");
			print("  lines     : " . number_format($total['lines']) . "\n");
			print("    code    : " . number_format($total['code']) . "  (" . $this->percent($total['code'], $total['lines']) . ")\n");
			print("    comment : " . number_format($total['comment']) . "  (" . $this->percent($total['comment'], $total['lines']) . ")\n");
			print("    blank   : " . number_format($total['blank']) . "  (" . $this->percent($total['blank'], $total['lines']) . ")\n");
			print("\n");

			return TRUE;
		}

		public function printGroupTable($args) {
			$files = $args['files'];
			$key   = $args['key'];
			$title = $args['title'];

			$groups = [];

			foreach($files as $file) {
				$name = $file[$key];

				if(!array_key_exists($name, $groups)) {
					$groups[$name] = [];
				}

				$groups[$name][] = $file;
			}

			$total = $this->sumRows($files);

			$rows = [];

			foreach($groups as $name => $group_files) {
				$sum = $this->sumRows($group_files);

				$rows[] = [
					'Folder'   => $name,
					'Files'    => number_format($sum['files']),
					'Bytes'    => $this->humanBytes($sum['bytes']),
					'Byte %'   => $this->percent($sum['bytes'], $total['bytes']),
					'Lines'    => number_format($sum['lines']),
					'Line %'   => $this->percent($sum['lines'], $total['lines']),
					'Code'     => number_format($sum['code']),
					'Cmt %'    => $this->percent($sum['comment'], $sum['lines']),
					'B/Line'   => $sum['lines'] ? number_format($sum['bytes'] / $sum['lines'], 1) : '0.0',
					'_sort'    => $sum[$this->sortField()],
				];
			}

			usort($rows, function($a, $b) {
				return $b['_sort'] <=> $a['_sort'];
			});

			if($this->top > 0) {
				$rows = array_slice($rows, 0, $this->top);
			}

			foreach($rows as $index => $row) {
				unset($rows[$index]['_sort']);
			}

			if($key === 'category') {
				foreach($rows as $index => $row) {
					$rows[$index] = ['Category' => $row['Folder']] + array_slice($row, 1);

					unset($rows[$index]['Folder']);
				}
			}

			print($title . "  (sorted by " . $this->sort . ")\n");

			if($this->as_csv) {
				$this->printCsv($rows);
			} else {
				print(arr2textTable($rows));
			}

			print("\n");

			return TRUE;
		}

		public function printFileTable($files) {
			usort($files, function($a, $b) {
				return $b[$this->sortField()] <=> $a[$this->sortField()];
			});

			$limit = $this->top > 0 ? $this->top : 25;
			$files = array_slice($files, 0, $limit);

			$rows = [];

			foreach($files as $file) {
				$rows[] = [
					'File'    => $file['path'],
					'Bytes'   => $this->humanBytes($file['bytes']),
					'Lines'   => number_format($file['lines']),
					'Code'    => number_format($file['code']),
					'Comment' => number_format($file['comment']),
					'Cmt %'   => $this->percent($file['comment'], $file['lines']),
				];
			}

			print("LARGEST FILES  (top " . $limit . " by " . $this->sort . ")\n");

			if($this->as_csv) {
				$this->printCsv($rows);
			} else {
				print(arr2textTable($rows));
			}

			print("\n");

			return TRUE;
		}

		public function printCsv($rows) {
			if(!count($rows)) {
				return TRUE;
			}

			print(implode(',', array_keys($rows[0])) . "\n");

			foreach($rows as $row) {
				$cells = [];

				foreach($row as $cell) {
					$cells[] = '"' . str_replace('"', '""', (string) $cell) . '"';
				}

				print(implode(',', $cells) . "\n");
			}

			return TRUE;
		}

			// Small helpers

		public function sortField() {
			return $this->sort === 'files' ? 'files' : $this->sort;
		}

		public function sumRows($files) {
			$sum = ['files' => 0, 'bytes' => 0, 'lines' => 0, 'code' => 0, 'comment' => 0, 'blank' => 0];

			foreach($files as $file) {
				$sum['files']++;
				$sum['bytes']   += $file['bytes'];
				$sum['lines']   += $file['lines'];
				$sum['code']    += $file['code'];
				$sum['comment'] += $file['comment'];
				$sum['blank']   += $file['blank'];
			}

			return $sum;
		}

		public function percent($part, $whole) {
			if(!$whole) {
				return '0.0%';
			}

			return number_format(($part / $whole) * 100, 1) . '%';
		}

		public function humanBytes($bytes) {
			$units = ['B', 'KB', 'MB', 'GB'];
			$index = 0;

			while($bytes >= 1024 && $index < count($units) - 1) {
				$bytes /= 1024;
				$index++;
			}

			return number_format($bytes, $index === 0 ? 0 : 1) . ' ' . $units[$index];
		}
	}

?>
