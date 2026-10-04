<?php

		/*
			The network's other sites of one kind -- on OurUprising, every site
			whose configuration says it is revolutionary -- as cards with their
			pictures, each linking out to the site.  A site classified tomorrow
			appears here without anyone touching this page.

			Every site's configuration file declares the same class, globals,
			so one process cannot load two of them to ask SiteIsRevolutionary().
			The answer is read from the file instead: the string its
			SiteClassification() returns, found with PHP's own tokenizer.  A
			file that never says is 'unstated', as clonefrom's default is.

			Each site's name, description and picture come from its master
			record -- the entry coded with its domain -- read across databases
			on the one server they share.  A database is named for its site
			unless the site overrides OverrideDatabaseName(), and none does.
			A site whose database this server does not hold is left out.

				ggreq('modules/html/network.php');
				$network = new module_network(['that'=>$this]);
				$network->Display(['classification'=>'revolutionary']);

			Switches, to Display():
			  classification  which sites to show; revolutionary by default
			  header          the heading; The family by default
			  note            a line beside the heading
		*/

	class module_network extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function Display($args) {
			$classification = !empty($args['classification']) ? $args['classification'] : 'revolutionary';
			$sites = $this->Sites(['classification'=>$classification]);

			if(!$sites) {
				return FALSE;
			}

			print('<section class="block collections-block network" aria-labelledby="network-title">');
			print('<div class="block-head">');
			print('<h2 class="block-title" id="network-title">' . (!empty($args['header']) ? $args['header'] : 'The family') . '</h2>');

			if(!empty($args['note'])) {
				print('<p class="block-note">' . $args['note'] . '</p>');
			}

			print('</div>');
			print('<div class="collections">');

			foreach($sites as $site) {
				$this->DisplaySite(['site'=>$site]);
			}

			print('</div>');
			print('</section>');

			return TRUE;
		}

		public function DisplaySite($args) {
			$site = $args['site'];
			$url = 'https://' . $site['domain'] . '/';

			print('<article class="coll">');

			if($site['image']) {
				print('<a class="coll-image coll-image-whole" href="' . $url . '" tabindex="-1" aria-hidden="true">');
				print('<img alt="" loading="lazy" src="' . htmlspecialchars($this->ImageURL(['domain'=>$site['domain'], 'image'=>$site['image']]), ENT_QUOTES, 'UTF-8') . '">');
				print('</a>');
			}

			print('<div class="coll-body">');
			print('<p class="kicker">' . $site['domain'] . '</p>');
			print('<h3 class="coll-title"><a href="' . $url . '">' . $site['Title'] . '</a></h3>');

			if($site['description'] !== '') {
				print('<p class="coll-note">' . $site['description'] . '</p>');
			}

			print('<a class="coll-more" href="' . $url . '">Visit ' . $site['domain'] . ' &rarr;</a>');
			print('</div>');
			print('</article>');

			return TRUE;
		}

			// The sites, and what each one says it is
			// -------------------------------------------------

		public function Sites($args) {
			$sites = [];

			foreach((array) @scandir(GGCMS_CONFIG_DIR) as $entry) {
				if(!preg_match('/^([a-z]+)\.([a-z0-9\-]+)\.php$/i', (string) $entry, $matches)) {
					continue;
				}

				$host = strtolower($matches[2]);

				if($host === $this->that->handler->domain->host) {
					continue;
				}

				if($this->Classification(['file'=>GGCMS_CONFIG_DIR . $entry]) !== $args['classification']) {
					continue;
				}

				$site = $this->MasterRecord(['database'=>$host, 'code'=>$host . '.' . strtolower($matches[1])]);

				if($site) {
					$sites[] = $site;
				}
			}

			usort($sites, function ($a, $b) {
				return strnatcasecmp($a['Title'], $b['Title']);
			});

			return $sites;
		}

		public function Classification($args) {
			$tokens = token_get_all((string) @file_get_contents($args['file']));
			$count = count($tokens);

			for($i = 0; $i < $count; $i++) {
				if(!is_array($tokens[$i]) || $tokens[$i][0] !== T_STRING || $tokens[$i][1] !== 'SiteClassification') {
					continue;
				}

				for($j = $i + 1; $j < $count && $tokens[$j] !== '}'; $j++) {
					if(is_array($tokens[$j]) && $tokens[$j][0] === T_CONSTANT_ENCAPSED_STRING) {
						return trim($tokens[$j][1], '\'"');
					}
				}
			}

			return 'unstated';
		}

			/*
				The site's own name and words for itself, and its first
				picture -- the master record's, or failing that the site's
				first, as MasereelGroup's master record has none.
			*/

		public function MasterRecord($args) {
			$database = preg_replace('/[^a-z0-9_]/', '', $args['database']);
			$db = $this->that->handler->db_access;

			if(!$database || !$db->RunQuery(['sql'=>'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', 'args'=>[$database]])) {
				return NULL;
			}

			$entries = $db->RunQuery([
				'sql'=>'SELECT id, Title FROM `' . $database . '`.Entry WHERE LOWER(Code) = ? AND Publish = 1 LIMIT 1',
				'args'=>[$args['code']],
			]);

			if(!$entries) {
				return NULL;
			}

			$site = [
				'domain'=>$args['code'],
				'Title'=>$entries[0]['Title'],
				'description'=>'',
				'image'=>NULL,
			];

			$descriptions = $db->RunQuery([
				'sql'=>'SELECT Description FROM `' . $database . '`.Description WHERE Entryid = ? ORDER BY id LIMIT 1',
				'args'=>[$entries[0]['id']],
			]);

			if($descriptions) {
				$site['description'] = trim(preg_replace('/Image::(\d+)/', '', (string) $descriptions[0]['Description']));
			}

			$images = $db->RunQuery([
				'sql'=>'SELECT FileDirectory, FileName, IconFileName FROM `' . $database . '`.Image ORDER BY Entryid = ? DESC, id LIMIT 1',
				'args'=>[$entries[0]['id']],
			]);

			if($images) {
				$site['image'] = $images[0];
			}

			return $site;
		}

		public function ImageURL($args) {
			$image = $args['image'];

			return 'https://' . $args['domain'] . '/image/' . implode('/', str_split($image['FileDirectory'])) . '/' . $image['FileName'];
		}
	}

?>