<?php

	/*
		A file of records, read into a header and its rows.

		Tab-delimited, comma-separated, JSON (a list of objects, or of lists
		with the header first), JSON lines, and Excel's .xlsx.  A .zip is
		opened, its one data file read, and every other file in it kept as
		an image the rows may name by Image_FileName.

		The header is a list, not a map: a column may be named twice, and
		the second Tag_Tag is a second tag.  Every row is padded or cut to
		the header's length, so a row is always the same shape as the
		header, and a row with nothing in it is dropped with its line kept.

		This only reads.  Which column is which field is the importer's
		question -- see traits/scripts/ImportRecords/.
	*/

	class ImportFile {
		public $headers;
		public $rows;
		public $lines;
		public $errors;
		public $format;
		public $images;
		public $image_directory;

		public function __construct($args = []) {
			$this->headers = [];
			$this->rows = [];
			$this->lines = [];
			$this->errors = [];
			$this->format = '';
			$this->images = [];
			$this->image_directory = '';
		}

			// Formats
			// -----------------------------------------------

		public function Formats() {
			return [
				'tsv'=>'Tab-delimited text (.tsv, .tab, .txt)',
				'csv'=>'Comma-separated values (.csv)',
				'json'=>'JSON: a list of objects keyed by header, or a list of lists with the header first (.json)',
				'jsonl'=>'JSON lines: one object per line (.jsonl, .ndjson)',
				'xlsx'=>'Excel workbook: its first sheet, header in the first row (.xlsx)',
				'zip'=>'A zip holding one of the above, and the images its rows name (.zip)',
			];
		}

			/*
				By the name first, and by the content when the name says
				nothing -- a pasted text has no name at all.  A tab anywhere in
				the first line is a tab-delimited file, since no one types a
				tab into a comma-separated one.
			*/

		public function DetectFormat($args) {
			$name = isset($args['name']) ? mb_strtolower((string)$args['name']) : '';
			$content = (string)$args['content'];

			$extension = pathinfo($name, PATHINFO_EXTENSION);

			$by_extension = [
				'tsv'=>'tsv',
				'tab'=>'tsv',
				'csv'=>'csv',
				'json'=>'json',
				'jsonl'=>'jsonl',
				'ndjson'=>'jsonl',
				'xlsx'=>'xlsx',
				'zip'=>'zip',
			];

			if(isset($by_extension[$extension])) {
				return $by_extension[$extension];
			}

			if(substr($content, 0, 2) === "PK") {
				return (strpos($content, 'xl/workbook') !== FALSE) ? 'xlsx' : 'zip';
			}

			$trimmed = ltrim($this->StripByteOrderMark(['content'=>$content]));
			$first_line = strtok($trimmed, "\n");

			if(substr($trimmed, 0, 1) === '[') {
				return 'json';
			}

			if(substr($trimmed, 0, 1) === '{') {
				return 'jsonl';
			}

			if(strpos((string)$first_line, "\t") !== FALSE) {
				return 'tsv';
			}

			return 'csv';
		}

			// Reading
			// -----------------------------------------------

		public function Read($args) {
			$content = (string)$args['content'];
			$name = isset($args['name']) ? $args['name'] : '';

			$this->format = isset($args['format']) && strlen($args['format']) ? $args['format'] : $this->DetectFormat(['name'=>$name, 'content'=>$content]);

			switch($this->format) {
				case 'tsv':
					$table = $this->ReadDelimited(['content'=>$content, 'delimiter'=>"\t"]);
					break;

				case 'csv':
					$table = $this->ReadDelimited(['content'=>$content, 'delimiter'=>',']);
					break;

				case 'json':
					$table = $this->ReadJSON(['content'=>$content]);
					break;

				case 'jsonl':
					$table = $this->ReadJSONLines(['content'=>$content]);
					break;

				case 'xlsx':
					$table = $this->ReadXLSX(['content'=>$content]);
					break;

				case 'zip':
					return $this->ReadZip(['content'=>$content]);

				default:
					$this->errors[] = 'Not a format this reads: ' . $this->format . '.';
					return FALSE;
			}

			if($table === FALSE) {
				return FALSE;
			}

			return $this->SetTable(['table'=>$table]);
		}

			/*
				A table is a list of [line, cells]: the first is the header.
				Cells are trimmed of surrounding whitespace, as Param() trims
				every value a form posts.
			*/

		public function SetTable($args) {
			$table = $args['table'];

			if(!count($table)) {
				$this->errors[] = 'The file is empty.';
				return FALSE;
			}

			$header_row = array_shift($table);
			$this->headers = [];

			foreach($header_row['cells'] as $header) {
				$this->headers[] = trim((string)$header);
			}

			while(count($this->headers) && $this->headers[count($this->headers) - 1] === '') {
				array_pop($this->headers);
			}

			if(!count($this->headers)) {
				$this->errors[] = 'The first row is the header, and it names no columns.';
				return FALSE;
			}

			$width = count($this->headers);

			foreach($table as $row) {
				$cells = array_map(function($cell) { return trim((string)$cell); }, $row['cells']);

				if(!strlen(implode('', $cells))) {
					continue;
				}

				$cells = array_slice(array_pad($cells, $width, ''), 0, $width);

				$this->rows[] = $cells;
				$this->lines[] = $row['line'];
			}

			return TRUE;
		}

		public function StripByteOrderMark($args) {
			$content = $args['content'];

			if(substr($content, 0, 3) === "\xEF\xBB\xBF") {
				return substr($content, 3);
			}

			return $content;
		}

			/*
				Tab-delimited and comma-separated alike, by str_getcsv() over
				the whole text, so a quoted cell may hold the delimiter and a
				line break.  The line a row is reported as is the line it
				starts on.
			*/

		public function ReadDelimited($args) {
			$content = $this->StripByteOrderMark(['content'=>(string)$args['content']]);
			$delimiter = $args['delimiter'];

			$content = str_replace(["\r\n", "\r"], "\n", $content);

			$table = [];
			$line = 1;
			$length = strlen($content);
			$position = 0;

			while($position < $length) {
				$record = $this->ReadDelimitedRecord(['content'=>$content, 'position'=>$position, 'delimiter'=>$delimiter]);

				$table[] = ['line'=>$line, 'cells'=>str_getcsv($record['text'], $delimiter, '"', '')];

				$line += substr_count($record['text'], "\n") + 1;
				$position = $record['next'];
			}

			return $table;
		}

			//  One record's text: up to the first line break outside quotes.

		public function ReadDelimitedRecord($args) {
			$content = $args['content'];
			$position = $args['position'];
			$length = strlen($content);

			$quoted = FALSE;

			for($i = $position; $i < $length; $i++) {
				$character = $content[$i];

				if($character === '"') {
					$quoted = !$quoted;
				} elseif($character === "\n" && !$quoted) {
					return ['text'=>substr($content, $position, $i - $position), 'next'=>$i + 1];
				}
			}

			return ['text'=>substr($content, $position), 'next'=>$length];
		}

		public function ReadJSON($args) {
			$decoded = json_decode($this->StripByteOrderMark(['content'=>(string)$args['content']]), TRUE);

			if(!is_array($decoded)) {
				$this->errors[] = 'The JSON could not be read: ' . json_last_error_msg() . '.';
				return FALSE;
			}

			$decoded = array_values($decoded);

			if(count($decoded) && array_is_list($decoded[0])) {
				$table = [];

				foreach($decoded as $index => $row) {
					$table[] = ['line'=>$index + 1, 'cells'=>array_map([$this, 'CellText'], (array)$row)];
				}

				return $table;
			}

			return $this->ObjectsToTable(['objects'=>$decoded, 'firstline'=>1]);
		}

		public function ReadJSONLines($args) {
			$content = $this->StripByteOrderMark(['content'=>(string)$args['content']]);
			$objects = [];
			$object_lines = [];

			foreach(explode("\n", str_replace("\r", '', $content)) as $index => $line) {
				if(!strlen(trim($line))) {
					continue;
				}

				$object = json_decode($line, TRUE);

				if(!is_array($object)) {
					$this->errors[] = 'Line ' . ($index + 1) . ' is not a JSON object.';
					return FALSE;
				}

				$objects[] = $object;
				$object_lines[] = $index + 1;
			}

			return $this->ObjectsToTable(['objects'=>$objects, 'lines'=>$object_lines]);
		}

			/*
				Objects become a table whose header is every key any of them
				used, in the order first seen.  A list value is a column named
				more than once: {"Tag_Tag": ["a", "b"]} is two tags.
			*/

		public function ObjectsToTable($args) {
			$objects = $args['objects'];
			$lines = isset($args['lines']) ? $args['lines'] : NULL;

			$header = [];
			$widths = [];

			foreach($objects as $object) {
				foreach((array)$object as $key => $value) {
					$count = is_array($value) ? max(1, count($value)) : 1;

					if(!isset($widths[$key])) {
						$widths[$key] = 0;
						$header[] = $key;
					}

					$widths[$key] = max($widths[$key], $count);
				}
			}

			$header_cells = [];

			foreach($header as $key) {
				for($i = 0; $i < $widths[$key]; $i++) {
					$header_cells[] = $key;
				}
			}

			$table = [['line'=>0, 'cells'=>$header_cells]];

			foreach($objects as $index => $object) {
				$cells = [];

				foreach($header as $key) {
					$values = array_key_exists($key, (array)$object) ? $object[$key] : '';
					$values = is_array($values) ? array_values($values) : [$values];

					for($i = 0; $i < $widths[$key]; $i++) {
						$cells[] = isset($values[$i]) ? $this->CellText($values[$i]) : '';
					}
				}

				$table[] = ['line'=>$lines ? $lines[$index] : $index + 1, 'cells'=>$cells];
			}

			return $table;
		}

		public function CellText($value) {
			if($value === NULL) {
				return '';
			}

			if(is_bool($value)) {
				return $value ? '1' : '0';
			}

			if(is_array($value)) {
				return json_encode($value, JSON_UNESCAPED_UNICODE);
			}

			return (string)$value;
		}

			/*
				The first worksheet of a workbook.  An .xlsx is a zip of XML:
				the strings are held once in sharedStrings.xml and each cell
				names one by number, or carries its own text inline.  Columns
				are lettered, and a cell left empty is simply not written, so
				every cell is placed by its reference rather than its order.
			*/

		public function ReadXLSX($args) {
			$zip = $this->OpenZip(['content'=>$args['content']]);

			if(!$zip) {
				return FALSE;
			}

			$strings = [];
			$shared = $zip['archive']->getFromName('xl/sharedStrings.xml');

			if($shared !== FALSE) {
				$shared_xml = simplexml_load_string($shared);

				foreach($shared_xml->si as $item) {
					$strings[] = $this->XLSXText(['node'=>$item]);
				}
			}

			$sheet = $zip['archive']->getFromName('xl/worksheets/sheet1.xml');
			$this->CloseZip(['zip'=>$zip]);

			if($sheet === FALSE) {
				$this->errors[] = 'The workbook has no first sheet.';
				return FALSE;
			}

			$sheet_xml = simplexml_load_string($sheet);
			$table = [];

			foreach($sheet_xml->sheetData->row as $row) {
				$cells = [];

				foreach($row->c as $cell) {
					$column = $this->XLSXColumnIndex(['reference'=>(string)$cell['r']]);
					$type = (string)$cell['t'];

					if($type === 's') {
						$value = $strings[(int)$cell->v];
					} elseif($type === 'inlineStr') {
						$value = $this->XLSXText(['node'=>$cell->is]);
					} else {
						$value = (string)$cell->v;
					}

					$cells[$column] = $value;
				}

				$filled = [];

				if(count($cells)) {
					for($i = 0; $i <= max(array_keys($cells)); $i++) {
						$filled[] = isset($cells[$i]) ? $cells[$i] : '';
					}
				}

				$table[] = ['line'=>(int)$row['r'], 'cells'=>$filled];
			}

			return $table;
		}

		public function XLSXText($args) {
			$node = $args['node'];

			if(isset($node->t)) {
				$text = '';

				foreach($node->t as $piece) {
					$text .= (string)$piece;
				}

				foreach($node->r as $run) {
					$text .= (string)$run->t;
				}

				return $text;
			}

			$text = '';

			foreach($node->r as $run) {
				$text .= (string)$run->t;
			}

			return $text;
		}

			//  "AB12" is column 27, counted from 0.

		public function XLSXColumnIndex($args) {
			preg_match('/^([A-Z]+)/', strtoupper($args['reference']), $match);

			$index = 0;

			foreach(str_split($match[1]) as $letter) {
				$index = ($index * 26) + (ord($letter) - 64);
			}

			return $index - 1;
		}

			// Zips
			// -----------------------------------------------

			/*
				A zip: one data file, read as its own extension says, and every
				other file kept as an image.  Folders inside the zip are
				ignored, so photos/fig-1.jpg is fig-1.jpg, and a name the rows
				use twice is refused rather than guessed at.
			*/

		public function ReadZip($args) {
			$zip = $this->OpenZip(['content'=>$args['content']]);

			if(!$zip) {
				return FALSE;
			}

			$data_files = [];
			$data_formats = ['tsv', 'tab', 'txt', 'csv', 'json', 'jsonl', 'ndjson', 'xlsx'];

			for($i = 0; $i < $zip['archive']->numFiles; $i++) {
				$name = $zip['archive']->getNameIndex($i);

				if(substr($name, -1) === '/' || strpos($name, '__MACOSX/') === 0 || basename($name)[0] === '.') {
					continue;
				}

				if(in_array(mb_strtolower(pathinfo($name, PATHINFO_EXTENSION)), $data_formats, TRUE)) {
					$data_files[] = $name;
				}
			}

			if(count($data_files) !== 1) {
				$this->CloseZip(['zip'=>$zip]);
				$this->errors[] = count($data_files)
					? 'The zip holds ' . count($data_files) . ' data files (' . implode(', ', $data_files) . '); it may hold only one.'
					: 'The zip holds no data file: a .tsv, .csv, .json, .jsonl or .xlsx beside the images.';
				return FALSE;
			}

			$data_name = $data_files[0];
			$data_content = $zip['archive']->getFromName($data_name);

			$extracted = $this->ExtractImages(['zip'=>$zip, 'skip'=>$data_name]);
			$this->CloseZip(['zip'=>$zip]);

			if($extracted === FALSE) {
				return FALSE;
			}

			$images = $this->images;
			$image_directory = $this->image_directory;

			$extension = mb_strtolower(pathinfo($data_name, PATHINFO_EXTENSION));
			$format = ($extension === 'txt') ? 'tsv' : '';

			$read = $this->Read(['content'=>$data_content, 'name'=>$data_name, 'format'=>$format]);

			$this->images = $images;
			$this->image_directory = $image_directory;

			return $read;
		}

			//  The images of a zip given on its own, beside a data file.

		public function ReadImageZip($args) {
			$zip = $this->OpenZip(['content'=>$args['content']]);

			if(!$zip) {
				return FALSE;
			}

			$extracted = $this->ExtractImages(['zip'=>$zip, 'skip'=>'']);
			$this->CloseZip(['zip'=>$zip]);

			return $extracted;
		}

		public function ExtractImages($args) {
			$zip = $args['zip'];
			$skip = $args['skip'];

			if(!strlen($this->image_directory)) {
				$directory = sys_get_temp_dir() . '/ggcms-import-' . bin2hex(random_bytes(8));

				if(!mkdir($directory, 0700)) {
					$this->errors[] = 'Could not make a temporary directory for the zip\'s images.';
					return FALSE;
				}

				$this->image_directory = $directory;
			}

			for($i = 0; $i < $zip['archive']->numFiles; $i++) {
				$name = $zip['archive']->getNameIndex($i);

				if($name === $skip || substr($name, -1) === '/' || strpos($name, '__MACOSX/') === 0) {
					continue;
				}

				$base = basename($name);

				if(!strlen($base) || $base[0] === '.') {
					continue;
				}

				$key = mb_strtolower($base);

				if(isset($this->images[$key])) {
					$this->errors[] = 'The zip holds two files named ' . $base . '; Image_FileName could not tell them apart.';
					return FALSE;
				}

					//  Written under a name of our own, never the zip's: a name with ../ in it goes nowhere.

				$path = $this->image_directory . '/' . count($this->images) . '-' . preg_replace('/[^A-Za-z0-9._-]/', '_', $base);

				if(file_put_contents($path, $zip['archive']->getFromIndex($i)) === FALSE) {
					$this->errors[] = 'Could not extract ' . $base . ' from the zip.';
					return FALSE;
				}

				$this->images[$key] = ['name'=>$base, 'path'=>$path, 'size'=>filesize($path)];
			}

			return TRUE;
		}

			//  An image by the name a row gives it, case aside; NULL when the zip has none.

		public function Image($args) {
			$key = mb_strtolower(basename((string)$args['filename']));

			return isset($this->images[$key]) ? $this->images[$key] : NULL;
		}

			//  Whatever the belt did not take.

		public function RemoveImages() {
			foreach($this->images as $image) {
				if(is_file($image['path'])) {
					unlink($image['path']);
				}
			}

			if(strlen($this->image_directory) && is_dir($this->image_directory)) {
				rmdir($this->image_directory);
			}

			return TRUE;
		}

		public function OpenZip($args) {
			if(!class_exists('ZipArchive')) {
				$this->errors[] = 'This PHP has no zip extension, so a .zip or .xlsx cannot be read.';
				return FALSE;
			}

			$path = tempnam(sys_get_temp_dir(), 'ggcms-import-zip-');
			file_put_contents($path, $args['content']);

			$archive = new ZipArchive();

			if($archive->open($path) !== TRUE) {
				unlink($path);
				$this->errors[] = 'The zip could not be opened.';
				return FALSE;
			}

			return ['archive'=>$archive, 'path'=>$path];
		}

		public function CloseZip($args) {
			$args['zip']['archive']->close();
			unlink($args['zip']['path']);

			return TRUE;
		}
	}

?>
