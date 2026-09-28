<?php

	class module_entrylist extends module_spacing {
		public $that;
		public $record;
		public $entrydate;
		
		public function __construct($args) {
			$this->that = $args['that'];
			$this->record = $this->that->entry;
			
			$this->entrydate = $args['entrydate'];
		}
		
		public function Display($args) {
			$children = $args['children'];
			
			if(!$children) {
				$children = $this->that->children;	# oh, you know, this 'n' that (joke will not get old)
			}
			
			$child_id_hash = [];
			
			$child_ids = array_keys($child_id_hash);
			$child_ids_count = count($child_ids);
			
			if($child_ids_count > 0) {
				$entry_id_string = implode(', ', $child_ids);
				$child_entries = $this->that->handler->db_access->RunQuery([
					'sql'=>'SELECT Entry.* FROM Entry WHERE id IN(' . $entry_id_string . ') ',
				]);
				
				$child_entries_hash = [];
				
				foreach($child_entries as $child_entry) {
					$child_entries_hash[$child_entry['id']] = $child_entry;
				}
				
				foreach($children as $child_key => $child) {
					foreach($child['association'] as $child_association_key => $child_association) {
						$children[$child_key]['association'][$child_association_key]['entry'] = $child_entries_hash[$child_association['ChosenEntryid']];
					}
				}
			}
			
			return $this->DisplayChildren($args);
		}
		
		public function DisplayChildGroups($args) {
			$children = $args['children'];
			$childgroups = $args['childgroups'];
			$childgroupscount = count($childgroups);
			
			$childgroupsdisplayed = [];
			
			
			
		#	print("BT: " . $childgroupscount);
			ksort($childgroups);
			
			foreach($childgroups as $childgroupkey => $childgroup) {
				$first_child = $childgroup['children'][0];
				
				$child_group_url_so_far = '/';
				$child_group_url = [];

				$quote_parent = false;				
				$index = 0;
				
				foreach($first_child['parents'] as $first_child_parent) {
					if($first_child_parent['Code'] == 'quotes') {
						$quote_parent = true;
					}
				}
				
				foreach($first_child['parents'] as $first_child_parent) {
					if($first_child_parent['Code'] == 'quotes') {
						$quote_parent = true;
					} else {
						if($first_child_parent['Code']) {
							$child_group_url_so_far .= $first_child_parent['Code'] . '/';
							
							if($quote_parent && $index == 0) {
								$child_group_url_so_far .= 'quotes/';
							}
							
							$child_group_url[] = '<a target="_parent" href="' . $child_group_url_so_far . 'view.php">' . $first_child_parent['Title'] . '</a>';
						}
					}
					$index++;
				}
				
				if($childgroupscount > 1 && !$childgroupsdisplayed[$childgroupkey]) {
					print('<h3 class="block-subtitle entry-group-title">');
					$record = $this->record;
					if($quote_parent) {
						print($record['Title'] . ' Quotes on ' );
					}
	#				print_r($record);
					print(implode(' &gt;&gt; ', $child_group_url));
				//	print('<a href="/' . $childgroupkey . '/view.php">');
				//	print($childgroupkey);
				//	print('</a>');
					$childgroupsdisplayed[$childgroupkey] = true;
					print('</h3>');
				}
				$args['children'] = $childgroup['children'];
				$this->DisplayChildren($args);
			}
			
			return TRUE;
		}
		
			/*
				The entries as cards: thumbnail, title, a line of who and where
				and when, a passage, and tags.  Titles are no longer cut; the
				card has room.
			*/

		public function DisplayChildren($args) {
			$children = $args['children'];

			print('<div class="entry-list">');

			foreach($children as $child) {
				if($child['entry']) {
					$child = $child['entry'];
				}

				$this->DisplayChild(['child'=>$child, 'args'=>$args]);
			}

			print('</div>');

			return TRUE;
		}

		public function DisplayChild($args) {
			$child = $args['child'];
			$list_args = $args['args'];

			$display_image = NULL;

			if($child['image'] && count($child['image'])) {
				$child_images = $child['image'];
				shuffle($child_images);
				$display_image = $child_images[0];
			}

			if(!$display_image && $this->that->master_record['image'] && count($this->that->master_record['image']) > 0) {
				$display_image = $this->that->master_record['image'][0];
			}

			$codes = [];
			$parent_count = 0;

			if($child['parents']) {
				$parent_count = count($child['parents']);
				foreach($child['parents'] as $parent) {
					if($parent['Code'] && $parent['id'] !== $child['id']) {
						$codes[] = $parent['Code'];
					}
				}

				$codes[] = $child['Code'];
				$link = '/' . implode('/', $codes);
			} else {
				$link = $child['Code'];
			}

			print('<article class="entry-card"');
			if($this->that->handler->authentication->user_session['UserAdmin.id']) {
				print(' title="id: ' . $child['id'] . '"');
			}
			print('>');

			if($display_image) {
				print('<a class="entry-card-image" target="_parent" href="' . $link . '/view.php" tabindex="-1" aria-hidden="true">');
				print('<img alt="" loading="lazy" width="' . ceil($display_image['IconPixelWidth'] / 2) . '" height="' . ceil($display_image['IconPixelHeight'] / 2) . '" src="/image/' . implode('/', str_split($display_image['FileDirectory'])) . '/' . $display_image['IconFileName'] . '">');
				print('</a>');
			}

			print('<div class="entry-card-body">');
			print('<h3 class="entry-card-title"><a target="_parent" href="' . $link . '/view.php">' . $this->ChildTitle(['child'=>$child, 'args'=>$list_args]) . '</a></h3>');

			$meta = $this->ChildMeta(['child'=>$child, 'args'=>$list_args, 'codes'=>$codes, 'parent_count'=>$parent_count, 'link'=>$link]);

			if($meta) {
				print('<p class="entry-card-meta">' . implode('<span class="dot" aria-hidden="true"> &middot; </span>', $meta) . '</p>');
			}

			print('<p class="entry-card-excerpt">');
			$this->DisplayPassage(['child'=>$child, 'args'=>$list_args]);
			print('</p>');

			if($child['tag'] && count($child['tag'])) {
				$tags = $child['tag'];
				shuffle($tags);
				$max_limit = min(10, count($tags));

				print('<ul class="chips chips-small">');

				for($i = 0; $i < $max_limit; $i++) {
					$tag = $tags[$i]['Tag'];

					print('<li><a target="_parent" href="/view.php?action=browseByTag&amp;tag=' . urlencode($tag) . '">' . $tag);

						// how many share it, where the page has counted them
					if(!empty($this->that->tag_counts[$tag])) {
						print(' <span class="chip-count">' . number_format($this->that->tag_counts[$tag]) . '</span>');
					}

					print('</a></li>');
				}

				print('</ul>');
			}

			print('</div>');
			print('</article>');

			return TRUE;
		}

		public function ChildTitle($args) {
			$child = $args['child'];
			$list_args = $args['args'];

			$title = $child['ListTitle'] ? $child['ListTitle'] : $child['Title'];

			if($list_args['title_prefix']) {
				$title = $list_args['title_prefix'] . $title;
			}

			if($list_args['title_suffix']) {
				$parent_count = count($child['parents']);
				$title .= $list_args['title_suffix'] . $child['parents'][$parent_count - 3]['Title'] . ' &amp; ' . $child['parents'][$parent_count - 2]['Title'];
			}

			if($child['Subtitle']) {
				$title .= '<span class="entry-card-subtitle">: ' . $child['Subtitle'] . '</span>';
			}

			return $title;
		}

		public function ChildMeta($args) {
			$child = $args['child'];
			$list_args = $args['args'];
			$codes = $args['codes'];
			$parent_count = $args['parent_count'];
			$meta = [];

			if($child['association'] && count($child['association']) > 0) {
				$writings = [];
				$roles = [];
				$cancelled = 0;

				foreach($child['association'] as $child_association) {
					if($child_association['ChosenEntryid'] == $this->that->entry['id']) {
						$cancelled = 1;
					}
					if($child_association['Type'] == 'Writing') {
						$writings[] = $child_association;
					} elseif($child_association['Type'] == 'Role') {
						$roles[] = $child_association;
					}
				}

				if(count($roles) > 0 && ($list_args['list_author'] || $cancelled == 0)) {
					$printable_roles = [];

					foreach($roles as $role) {
						$printable_roles[] = '<a target="_parent" href="/people/' . $role['entry']['Code'] . '/view.php">' . $role['entry']['Title'] . '</a>';
					}

					$meta[] = 'by ' . implode(', ', $printable_roles);
				}

				if($list_args['parents'] == 2) {
					$parents_codes = $codes;
					array_pop($parents_codes);

					$meta[] = 'listed in <a target="_parent" href="/' . implode('/', $parents_codes) . '/view.php">' . $child['parents'][$parent_count - 5]['Title'] . ' :: ' . $child['parents'][$parent_count - 3]['Title'] . ' :: ' . $child['parents'][$parent_count - 2]['Title'] . '</a>';
				}

				if(!$list_args['list_author'] && (count($writings) > 0)) {
					$printable_roles = [];

					foreach($writings as $writing) {
						$role_info = '<a href="/writings/' . $writing['entry']['Code'] . '/view.php">' . $writing['entry']['Title'] . '</a>';

						if($writing['associated_entry'] && $this->that->entry['id'] !== $writing['associated_entry']['id']) {
							$role_info .= ', by <a href="/people/' . $writing['associated_entry']['Code'] . '/">' . $writing['associated_entry']['Title'] . '</a>';
						}

						$printable_roles[] = $role_info;
					}

					$meta[] = 'from ' . implode(', ', $printable_roles);
				}
			}

			if($this->entrydate) {
				$time_info = $this->entrydate->getEntrySimpleData(['entry'=>$child, 'short-dates'=>$list_args['short-dates']]);

				if($time_info['text']) {
					$meta[] = '<span class="entry-card-date">' . $time_info['text'] . '</span>';
				}
			}

			if($list_args['show_associations'] && $child['associated'] && count($child['associated']) > 0) {
				$writings_count = 0;
				$quotes_count = 0;

				foreach($child['associated'] as $single_associated) {
					foreach($single_associated['entry']['parents'] as $associated_entry_parent) {
						if($associated_entry_parent['Code'] === 'writings') {
							$writings_count++;
						} elseif($associated_entry_parent['Code'] === 'quotes') {
							$quotes_count++;
						}
					}
				}

				if($writings_count > 0) {
					$meta[] = number_format($writings_count) . ' writings';
				}

				if($quotes_count > 0) {
					$meta[] = number_format($quotes_count) . ' quotes';
				}
			}

			if($child['textbody'] && $child['textbody'][0] && $child['textbody'][0]['WordCount']) {
				$meta[] = number_format($child['textbody'][0]['WordCount']) . ' words';
			}

			if($this->that->handler->authentication->user_session['UserAdmin.id']) {
				$meta[] = '<a class="admin-link" target="_parent" href="' . $args['link'] . '/modify.php?action=Edit">Edit</a>';
			}

			return $meta;
		}

		public function DisplayPassage($args) {
			$child = $args['child'];
			$list_args = $args['args'];

			$this->DisplayDescription(['child'=>$child]);

			if($child['quote']) {
				$child_quotes = $child['quote'];
				shuffle($child_quotes);
				$max_limit = min(3, count($child_quotes));

				for($i = 0; $i < $max_limit; $i++) {
					$quote = $child_quotes[$i];

					if($quote && $quote['Quote']) {
						print('<span class="entry-card-quote">&ldquo;' . str_replace('"', '\'', $quote['Quote']) . '&rdquo;');

						if($quote['Source']) {
							print(' <span class="source">(' . $this->ShortSource(['source'=>$quote['Source']]) . ')</span>');
						}

						print('</span> ');
					}
				}

				return TRUE;
			}

			if($list_args['quotes_body']) {
				$valid_textbody = FALSE;
				$valid_work = FALSE;

				foreach((array) $child['associated'] as $associated) {
					$associated_entry = $associated['entry'];

					if(!$associated_entry) {
						continue;
					}

					if($associated_entry['textbody']) {
						$valid_textbody = $associated_entry['textbody'][0];
					}

					if($associated_entry['parents'] && $associated_entry['parents'][0]['Code'] === 'writings') {
						$valid_work = $associated_entry;
					}

					if($valid_textbody && $valid_work) {
						break;
					}
				}

				if($valid_textbody) {
					print('&ldquo;' . trim(strip_tags($valid_textbody['FirstThousandCharacters'])) . '&rdquo;');
				}

				if($valid_work) {
					print(' <span class="source">(Works: ' . $valid_work['Title'] . ')</span>');
				}

				return TRUE;
			}

			if($child['textbody'] && $child['textbody'][0]) {
				$this->DisplayTextBody(['child'=>$child]);
			} elseif($child['children'] && $child['children'][0] && $child['children'][0]['textbody'] && $child['children'][0]['textbody'][0]) {
				$this->DisplayTextBody(['child'=>$child['children'][0]]);
			}

			return TRUE;
		}

		public function ShortSource($args) {
			$source = trim(strip_tags((string) $args['source']));

			return strlen($source) > 50 ? substr($source, 0, 50) . '...' : $source;
		}

		public function getBlockingHTML() {
			return [
				'_',
				'&nbsp;',
				'<br>',
				'<br >',
				'<br />',
				'<hr>',
				'<hr >',
				'<hr />',
			];
		}
		
		public function DisplayTextBody($args) {
			$child = $args['child'];
			
			$text_bodies = $child['textbody'];
			
			$text_display = $text_bodies[0]['FirstThousandCharacters'];
			
			$text_display = preg_replace('/Image::(\d+)(\s+)/', '', $text_display);
			$text_display = str_replace($this->getBlockingHTML(), ' ', $text_display);
	#		$text_display = str_replace('<hr>', ' ', $text_display);
			$text_display = trim(strip_tags($text_display));
			
			if(strlen($text_display) > 750) {
				$text_display = substr($text_display, 0, 750) . '...';
			}
			
			if($text_display) {
				print($text_display);
				
				if($text_bodies[0]['Source']) {
					$source = $text_bodies[0]['Source'];
					
					if(strlen($source) > 50) {
						$source = substr($source, 0, 50) . '...';
					}
					
					print(' <span class="source">(' . $source . ')</span>');
				}
			}
			
			return TRUE;
		}
		
		public function DisplayDescription($args) {
			$child = $args['child'];
			
			if($child['description']) {
				$description = $child['description'][0];
				
				if($description && $description['Description']) {
					print('<em>');
					print($description['Description']);
					print(' ');
					print('</em>');
					
					if($description['Source']) {
						$source = $description['Source'];
						
						if(strlen($source) > 50) {
							$source = substr($source, 0, 50) . '...';
						}
						
						print(' <span class="source">(' . $source . ')</span>');
					}
				}
			}
			
			return TRUE;
		}
		
		public function DisplayTimeFrame($args) {
			if(!$this->entrydate) {
				return FALSE;
			}

			$time_info = $this->entrydate->getEntrySimpleData(['entry'=>$args['child'], 'short-dates'=>$args['short-dates']]);

			if($time_info['text']) {
				print('<span class="entry-card-date">' . $time_info['text'] . '</span>');
			}

			return TRUE;
		}
	}

?>