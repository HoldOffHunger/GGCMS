<?php

	/*
		Shared ground for the image tools.

		Three facts about the image tree drive everything here.

		The first is that the path is derived, not stored.  Image.FileDirectory
		holds four characters and the URL splits them into four directories, so
		"cmot" is /image/c/m/o/t/.  base_format and every template build the
		path that way; so does this.  Nothing anywhere stores the full path,
		which is why a tool that wants to find a file on disk has to know this
		rule rather than read it.

		The second is that one Image row is three files.  FileName is the
		original, StandardFileName the mid-size, IconFileName the thumbnail,
		and on revoltlib all 2,496 rows carry all three.  Any count that treats
		a row as a file is wrong by a factor of three, and any sweep that
		re-encodes the icons has spent an hour to save nothing.

		The third is that the stored dimensions are the rendered dimensions.
		PixelWidth, IconPixelHeight and their siblings are printed straight
		into the img tag's width and height attributes.  So an image tool may
		re-encode a file and may not resize one -- a resize silently makes
		every stored dimension a lie, and the page keeps rendering at the old
		size against a smaller image.  That is the invariant the compressor
		checks after every write, and refuses to keep a result that breaks.
	*/

	trait ImageFiles {
			// Locations
			// -----------------------------------------------

		public function imageDirectory() {
			return '/srv/ggcms/' . $this->domain . '/www/image/';
		}

			/*
				Backups live on the mounted volume, never on the root disk.
				Root is 25 GB with about 8 GB free and revoltlib's images alone
				are 5.3 GB, so a backup written beside them fills the disk that
				serves all seventeen sites.  /mnt/nyc01 is 52 GB and already
				holds the caches and the MySQL tarball for the same reason.
			*/

		public function imageBackupRoot() {
			return '/mnt/nyc01/ggcms_image_backups/';
		}

		public function imageBackupDomainRoot() {
			return $this->imageBackupRoot() . $this->domain . '/';
		}

		public function imageBackupDirectory($args) {
			$label = $args['label'];

			return $this->imageBackupDomainRoot() . $label . '/';
		}

			// Paths
			// -----------------------------------------------

		public function fileDirectoryToPath($args) {
			$file_directory = $args['file_directory'];

			if(strlen($file_directory) === 0) {
				return '';
			}

			return implode('/', str_split($file_directory));
		}

		public function variantNames() {
			return ['original', 'standard', 'icon'];
		}

		public function variantFilename($args) {
			$row = $args['row'];
			$variant = $args['variant'];

			$fields = [
				'original'=>'FileName',
				'standard'=>'StandardFileName',
				'icon'=>'IconFileName',
			];

			if(!array_key_exists($variant, $fields)) {
				return '';
			}

			$field = $fields[$variant];

			return array_key_exists($field, $row) ? $row[$field] : '';
		}

		public function imagePathForVariant($args) {
			$row = $args['row'];
			$variant = $args['variant'];

			$filename = $this->variantFilename(['row'=>$row, 'variant'=>$variant]);

			if(strlen($filename) === 0) {
				return '';
			}

			$directory = $this->fileDirectoryToPath([
				'file_directory'=>$row['FileDirectory'],
			]);

			if(strlen($directory) === 0) {
				return '';
			}

			return $this->imageDirectory() . $directory . '/' . $filename;
		}

		public function relativeImagePath($args) {
			$path = $args['path'];
			$root = $this->imageDirectory();

			if(substr($path, 0, strlen($root)) === $root) {
				return substr($path, strlen($root));
			}

			return $path;
		}

			// Extensions
			// -----------------------------------------------

		public function imageExtensions() {
			return ['jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp'];
		}

			/*
				Only the JPEG family.  PNG and GIF are lossless formats holding
				line art and animations, where a quality search has nothing to
				search and re-encoding either loses the animation or gains
				bytes.  Those want a different tool, not this one with a wider
				list.
			*/

		public function recompressibleExtensions() {
			return ['jpg', 'jpeg', 'jfif'];
		}

		public function extensionOf($args) {
			$path = $args['path'];

			$dot = strrpos($path, '.');

			if($dot === FALSE) {
				return '';
			}

			return strtolower(substr($path, $dot + 1));
		}

		public function isImagePath($args) {
			return in_array($this->extensionOf($args), $this->imageExtensions(), TRUE);
		}

		public function isRecompressiblePath($args) {
			return in_array($this->extensionOf($args), $this->recompressibleExtensions(), TRUE);
		}

			// Walking the tree
			// -----------------------------------------------

			/*
				RecursiveDirectoryIterator rather than find or ls.  Filenames
				here contain brackets -- 3-revolt-lib[1]-icon.jpg is a real
				file -- along with spaces and quotes, and every one of those is
				a shell metacharacter waiting for the one path that was not
				escaped.  Nothing in these tools passes a filename through a
				shell except the encoder, which uses escapeshellarg.
			*/

		public function walkImageFiles($args) {
			$directory = $this->imageDirectory();

			$minimum_size = array_key_exists('minimum_size', $args) ? (int)$args['minimum_size'] : 0;
			$limit = array_key_exists('limit', $args) ? (int)$args['limit'] : 0;
			$recompressible_only = array_key_exists('recompressible_only', $args) ? $args['recompressible_only'] : FALSE;

			if(!is_dir($directory)) {
				return [];
			}

			$files = [];

			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
				RecursiveIteratorIterator::LEAVES_ONLY
			);

			foreach($iterator as $entry) {
				if(!$entry->isFile()) {
					continue;
				}

				$path = $entry->getPathname();

				if(!$this->isImagePath(['path'=>$path])) {
					continue;
				}

				if($recompressible_only && !$this->isRecompressiblePath(['path'=>$path])) {
					continue;
				}

				$size = $entry->getSize();

				if($minimum_size && $size < $minimum_size) {
					continue;
				}

				$files[] = [
					'path'=>$path,
					'relative'=>$this->relativeImagePath(['path'=>$path]),
					'size'=>$size,
					'extension'=>$this->extensionOf(['path'=>$path]),
				];
			}

				/*
					Largest first.  Every one of these tools is asked "what is
					costing me the most", and a --limit that returned an
					arbitrary slice of a 9,000-file tree would answer a
					different question on each run.
				*/

			usort($files, function($first, $second) {
				return $second['size'] <=> $first['size'];
			});

			if($limit > 0 && count($files) > $limit) {
				$files = array_slice($files, 0, $limit);
			}

			return $files;
		}

			// The database side
			// -----------------------------------------------

		public function loadImageRows() {
			$query = 'SELECT id, FileName, StandardFileName, IconFileName, FileDirectory, ';
			$query .= 'PixelWidth, PixelHeight, StandardPixelWidth, StandardPixelHeight, ';
			$query .= 'IconPixelWidth, IconPixelHeight ';
			$query .= 'FROM Image';

			return $this->runQuery(['query'=>$query]);
		}

			/*
				Relative path to variant, for every file the database expects.
				The scanner subtracts this from what is on disk to find the
				orphans, and subtracts what is on disk from this to find the
				rows whose file has gone.
			*/

		public function buildExpectedFileMap($args) {
			$rows = $args['rows'];

			$expected = [];

			foreach($rows as $row) {
				$directory = $this->fileDirectoryToPath([
					'file_directory'=>$row['FileDirectory'],
				]);

				if(strlen($directory) === 0) {
					continue;
				}

				foreach($this->variantNames() as $variant) {
					$filename = $this->variantFilename(['row'=>$row, 'variant'=>$variant]);

					if(strlen($filename) === 0) {
						continue;
					}

					$expected[$directory . '/' . $filename] = [
						'id'=>$row['id'],
						'variant'=>$variant,
						'row'=>$row,
					];
				}
			}

			return $expected;
		}

			// Reading an image
			// -----------------------------------------------

			/*
				identify, not getimagesize, because %Q is the encoder's quality
				setting and nothing in PHP's core reports it.  Knowing the
				source quality is what stops the compressor from "optimising" a
				file that is already at 60 up to 82.

				The [0] suffix takes the first frame only.  Without it a
				multi-frame file makes identify print one line per frame, and
				the parse below silently reads the first of many.
			*/

		public function identifyImage($args) {
			$path = $args['path'];

			$command = 'nice -n 19 identify -format "%w %h %Q" ' . escapeshellarg($path . '[0]') . ' 2>/dev/null';

			$output = trim((string)shell_exec($command));

			if(strlen($output) === 0) {
				return FALSE;
			}

			$pieces = preg_split('/\s+/', $output);

			if(count($pieces) < 3) {
				return FALSE;
			}

			return [
				'width'=>(int)$pieces[0],
				'height'=>(int)$pieces[1],
				'quality'=>(int)$pieces[2],
			];
		}
	}

?>
