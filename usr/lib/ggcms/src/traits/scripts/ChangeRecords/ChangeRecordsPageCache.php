<?php

	/*
		After the belt: the page cache, for every page the change touched.

		One stage of the belt described in ChangeRecords.php.
	*/

	trait ChangeRecordsPageCache {

			/*
				Clear the page cache here, and say what went.

				DBAccess already registers a flush for every content write, but
				it runs as a shutdown function -- after this script has finished
				speaking. Whatever it clears cannot be reported, so an editor was
				told the save worked and left to wonder why the page had not
				changed.

				Doing it here instead puts the answer next to the entry links,
				where the rest of the outcome already is. The shutdown flush
				still runs and finds nothing left, which costs a scandir.

				Both hostnames, because the cache is keyed on HTTP_HOST verbatim
				and every page is stored twice.
			*/

		public function FlushPageCacheAndReport() {
			if(!class_exists('PageCache')) {
				ggreq('classes/Cache/PageCache.php');
			}

			$page_cache = new PageCache(['handler'=>$this->handler]);

			$domain = $this->handler->domain->primary_domain_lowercased;

			$hosts = (substr($domain, 0, 4) === 'www.')
				? [$domain, substr($domain, 4)]
				: [$domain, 'www.' . $domain];

			foreach($hosts as $host) {
				$page_cache->FlushDomain(['domain'=>$host]);
			}

			$removed = $page_cache->RemovedFiles();
			$count = count($removed);

			if($count === 0) {
				return '<br>&bull; Page cache: nothing was cached to clear.';
			}

				/*
					A section edit on revoltlib clears tens of thousands of
					pages. The count is the useful number; a handful of names
					shows which corner of the tree it reached.
				*/

			$report = '<br>&bull; Page cache: cleared ' . $count . ' file';
			$report .= ($count === 1) ? '' : 's';

			$shown = array_slice($removed, 0, 10);

			foreach($shown as $file) {
				$report .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;' . htmlspecialchars($file);
			}

			if($count > count($shown)) {
				$report .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;... and ' . ($count - count($shown)) . ' more';
			}

			return $report;
		}
	}

?>
