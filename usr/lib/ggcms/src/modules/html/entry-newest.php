<?php

	class module_entrynewest extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

				// Newest-Entries Record List

			// -------------------------------------------------------------

			/*
				The newest entries as a ledger: the date each was catalogued,
				where it went, and its title.
			*/

		public function Display() {
				/*
					The script decides whether to gather this at all -- see
					RecordRelationEnabled and the record_relations config -- so a
					template that includes this module on a site where the switch
					is off arrives here with nothing set.  count(NULL) is fatal in
					PHP 8, so that combination took whole sites to a 500 rather
					than printing no block.  A module renders what it was given,
					and being given nothing is a valid answer.
				*/

			if(!$this->that->newest_entries || !is_array($this->that->newest_entries)) {
				return TRUE;
			}

			$newest_entries_count = count($this->that->newest_entries);

			if($newest_entries_count === 0) {
				return TRUE;
			}

			print('<section class="block newest" aria-labelledby="newest-title">');
			print('<div class="block-head">');
			print('<h2 class="block-title" id="newest-title">Recently catalogued</h2>');

				/*
					Absolute, not relative.  "news.php" resolves against the
					current entry, so on /anarchism/anna-karenina/ this asked for
					/anarchism/anna-karenina/news.php -- a 404 that a crawler then
					follows and compounds.  navigation.php has always built this
					link correctly; this one did not.
				*/

			print('<a class="block-more" href="' . $this->that->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]) . '/news.php">All additions &rarr;</a>');
			print('</div>');

			print('<ol class="ledger">');

			for($i = 0; $i < $newest_entries_count; $i++) {
				$this->Display_Entry(['entry'=>$this->that->newest_entries[$i]]);
			}

			print('</ol>');
			print('</section>');

			return TRUE;
		}

		public function Display_Entry($args) {
			$newest_entry = $args['entry'];
			$last_parent = NULL;

			$newest_entry_parents = $newest_entry['parents'];

			if($newest_entry_parents[0] && $newest_entry['id'] !== $newest_entry_parents[0]['id']) {
				$last_parent = $newest_entry_parents[0];
			} elseif($this->that->master_record['id'] !== $newest_entry['id']) {
				$last_parent = $this->that->master_record;
			}

			$parent_codes = $this->Display_GetParentCodes(['entry'=>$newest_entry]);
			$date_epoch_time = strtotime($newest_entry['OriginalCreationDate']);

			print('<li>');
			print('<time class="ledger-date" datetime="' . date('Y-m-d', $date_epoch_time) . '">' . date('j M Y', $date_epoch_time) . '</time>');
			print('<span class="ledger-tag">' . ($last_parent ? $last_parent['Title'] : '') . '</span>');
			print('<a class="ledger-title" href="' . implode('/', $parent_codes) . '/' . $newest_entry['Code'] . '/">' . $newest_entry['Title'] . '</a>');
			print('</li>');

			return TRUE;
		}

		public function Display_GetParentCodes($args) {
			$entry = $args['entry'];

			$parent_count = $entry['parents'] ? count($entry['parents']) : 0;

			$parent_codes = [];

			for($j = 0; $j - 1 < $parent_count; $j++) {
				$parent = $entry['parents'][$j];
				$parent_codes[] = $parent['Code'];
			}

			return $parent_codes;
		}
	}

?>
