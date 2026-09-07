<?php

	/*
		The Image table, for the tools that reconcile it against the disk.

		This lived in ImageFiles until it was noticed that ImageFiles is also
		used by LocalExportCompressor, which runs on a machine with no database
		and no configuration -- it reads a manifest and compresses files.  A
		single query method sitting in an otherwise filesystem-only trait made
		every such tool one careless call away from a fatal, because
		$this->runQuery() only exists where DBAccess has also been composed in.

		Nothing there called it, so nothing was broken.  It is separated so
		that nothing can start to be.
	*/

	trait ImageRows {
		public function loadImageRows() {
			$query = 'SELECT id, FileName, StandardFileName, IconFileName, FileDirectory, ';
			$query .= 'PixelWidth, PixelHeight, StandardPixelWidth, StandardPixelHeight, ';
			$query .= 'IconPixelWidth, IconPixelHeight ';
			$query .= 'FROM Image';

			return $this->runQuery(['query'=>$query]);
		}
	}

?>
