<?php

	class ATOM extends AbstractBaseFormat {
			// XML MimeType
			// -----------------------------------------------
		
		public function MimeType() {
			return 'application/atom+xml';
		}
		
		public function DefaultVersion() {
			return '1.0';
		}
		
			// Display RSS
			// -----------------------------------------------
		
		public function Display() {
			if($this->human_readable) {
				$this->section_separator = "\n\n";
				$this->line_separator = "\n";
				
				$this->indent_levels = [
					1=>"\t",
					2=>"\t\t",
					3=>"\t\t\t",
					4=>"\t\t\t\t",
					5=>"\t\t\t\t\t",
				];
			} else {
				$this->section_separator = '';
				$this->line_separator = '';
				
				$this->indent_levels = [
					1=>'',
					2=>'',
					3=>'',
					4=>'',
					5=>'',
				];
			}
			
			$this->RunScript();
			
			$this->SetFileNameDisplay();
			$this->HandleHTTPHeaders();
			
		#	$this->StartDocument();
		#	
			print($this->ConvertHTMLToFormat());
		#	
		#	$this->EndDocument();
			
			return TRUE;
		}
		
		public function ConvertHTMLToFormat() {
			$this->RunTemplates();
			
			$xml_atom_content = '<?xml version="1.0" encoding="UTF-8" ?>' . $this->section_separator;
			$xml_atom_content .= '<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="en-US">' . $this->section_separator;
			
			$title = $this->script->entry['Title'];
			if($this->script->entry['SubTitle']) {
				$title .= ': ' . $this->script->entry['SubTitle'];
			}
			
			$xml_atom_content .= $this->indent_levels[1] . '<title>' . $title . '</title>' . $this->line_separator;

			$base_url = $this->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1])  . '/';
			
			$record_list_count = count($this->script->record_list);
			
			if($record_list_count > 0) {
				for($i = 0; $i < $record_list_count; $i++) {
					$record = $this->script->record_list[$i];
					$base_url .= $record['Code'] . '/';
				}
				
				$url .= $base_url . 'view.php?action=index';
			}
			
			$xml_atom_content .= $this->ConvertHTMLToFormat_renderImage(['link'=>$url]);
			
			$xml_atom_content .= $this->indent_levels[1] . '<link href="' .  $base_url . '" />' . $this->line_separator;
			$xml_atom_content .= $this->indent_levels[1] . '<id>' . $base_url . '</id>' . $this->line_separator;
			$xml_atom_content .= $this->indent_levels[1] . '<updated>' . date('Y-m-d\TH:i:sP', strtotime($this->ConvertHTMLToFormat_lastBuildDate())) . '</updated>' . $this->line_separator;
		#	$xml_atom_content .= $this->indent_levels[1] . '<published>' . date('Y-m-d\TH:i:sP', strtotime($this->script->entry['OriginalCreationDate'])) . '</published>' . $this->line_separator;			# IMPLEMENT THIS, you bastards!
			$xml_atom_content .= $this->indent_levels[1] . '<rights>The contents of this feed are in the Public Domain.</rights>' . $this->line_separator;
			$xml_atom_content .= $this->indent_levels[1] . '<generator>' . $this->handler->version->GetSoftwareNameAcronymAndVersion() . ', Released Under BSD 3-Clause License</generator>' . $this->line_separator;
			
			$extra = '';
			
			if($this->human_readable) {
				$extra = '?humanreadable=1';
			}
			
			$xml_atom_content .= $this->indent_levels[1] . '<link href="' . $base_url . 'news.atom' . $extra . '" rel="self" type="application/atom+xml" />' . $this->line_separator;
			$xml_atom_content .= $this->indent_levels[1] . '<link href="' . $base_url . 'news.rss" rel="alternate" type="application/rss+xml" />' . $this->line_separator;

			$xml_atom_content .= $this->indent_levels[1] . '<author>' . $this->line_separator . $this->indent_levels[2] . '<email>' . $this->handler->globals->AdminEmailAddress() . '</email>' . $this->line_separator . $this->indent_levels[2] . '<name>' . $this->handler->globals->AdminName() . '</name>' . $this->line_separator . $this->indent_levels[1] . '</author>' . $this->line_separator;
			
		#	$description = $this->script->getDescription();		# why is there no way of describing this????
		#	
		#	if($description) {
		#		$xml_atom_content .= $this->indent_levels[1] . '<summary>' . $description . '</summary>' . $this->line_separator;
		#	}
			
			$xml_atom_content .= $this->indent_levels[1] . '<category term="' . $this->handler->globals->SiteCategory() . '" />' . $this->section_separator;
			
			$xml_atom_content .= $this->ConvertHTMLToFormat_renderContent();
			
			$xml_atom_content .= '</feed>';
			
			return $xml_atom_content;
		}
		
		public function getEntries() {
			if($this->entries) {
				return $this->entries;
			}
			
			if($this->script->newest_entries) {
				return $this->entries = $this->script->newest_entries;
			}
			
			if($this->script->children) {
				return $this->entries = $this->script->children;
			}
		}
		
		public function ConvertHTMLToFormat_lastBuildDate() {
			if($this->getEntries()) {
				$entry = $this->getEntries()[0];
				$entry_date = $entry['OriginalCreationDate'];
			}
			
			if($entry_date > $this->script->entry['LastModificationDate']) {
				return $entry_date;
			}
			
			return $this->script->entry['LastModificationDate'];
		}
		
		public function ConvertHTMLToFormat_renderImage($args) {
			$link = $args['link'];
			
			$images = $this->script->entry['image'];
			$image = $images[0];
			
			$image_content = '';
			$image_content .= $this->indent_levels[1] . '<logo>';
			$image_content .= $this->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1])  . '/image/';
			$image_content .= implode('/', str_split($image['FileDirectory'])) . '/';
			$image_content .= urlencode($image['FileName']);
			$image_content .= '</logo>' . $this->line_separator;
			
			$image_content .= $this->indent_levels[1] . '<icon>';
			$image_content .= $this->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1])  . '/image/';
			$image_content .= implode('/', str_split($image['FileDirectory'])) . '/';
			$image_content .= urlencode($image['IconFileName']);
			$image_content .= '</icon>' . $this->line_separator;
			
			return $image_content;
		}
		
		public function ConvertHTMLToFormat_renderContent() {
			$domain = $this->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1])  . '/';
			$content = '';
	#		print("BT:");
	#		print_r($this->script->record_list);
	#		print_r($this->script->parent);
			if($this->getEntries()) {
				$newest_entries_count = count($this->getEntries());
				if($newest_entries_count > 0) {
					for($i = 0; $i < $newest_entries_count; $i++) {
						$newest_entry = $this->getEntries()[$i];
						
						$content .= $this->indent_levels[1] . '<entry>' . $this->line_separator;
						
						$content .= $this->indent_levels[2] . '<title>';
						
						if($newest_entry['GrandParent_Title']) {
							$content .= '[' . $newest_entry['GrandParent_Title'] . '] ';
							$content .= $newest_entry['Parent_Title'] . ' -- ';
						} elseif($newest_entry['Parent_Title']) {
							$content .= $newest_entry['Parent_Title'] . ' -- ';
						}
						
						$content .= $newest_entry['Title'];
						
						if($newest_entry['Subtitle']) {
							$content .= ': ' . $newest_entry['Subtitle'];
						}
						
						$content .= '</title>' . $this->line_separator;
						
						$child_link = $domain;
						
						if($newest_entry['GrandParent_Code']) {
							$child_link .= $newest_entry['GrandParent_Code'] . '/';
						}
						
						if($newest_entry['Parent_Code']) {
							$child_link .= $newest_entry['Parent_Code'] . '/';
						}
						
						$child_link .= $newest_entry['Code'] . '/';
						
						$content .= $this->indent_levels[2] . '<updated>' . date('Y-m-d\TH:i:sP', strtotime($newest_entry['OriginalCreationDate'])) . '</updated>' . $this->line_separator;
						
						if($newest_entry['association'] && count($newest_entry['association'])) {
							$association_names = [];
							
							foreach($newest_entry['association'] as $association) {
								$association_names[] = $association['entry']['Title'];
							}
							
							$content .= $this->indent_levels[2] . '<author><name>' . implode('</name></author>' . $this->line_separator . $this->indent_levels[2] . '<author><name>', $association_names) . '</name></author>' . $this->line_separator;
						}
						
						$content .= $this->indent_levels[2] . '<category term="' . $this->script->parent['ChildNoun'] . '" />' . $this->line_separator;
						
						$content .= $this->indent_levels[2] . '<link rel="alternate" href="' . $child_link . '" />' . $this->line_separator;
						$description = $this->ConvertHTMLToFormat_renderContent_FormattedDescription(['entry'=>$newest_entry]);
						if($description) {
							$content .= $this->indent_levels[2] . '<summary>' . $description . '</summary>' . $this->line_separator;
						} else {
							$content .= $this->indent_levels[2] . '<summary>New ' . $this->script->parent['GrandChildNoun'] . ' with ' . $newest_entry['Count'] . ' chapters.</summary>' . $this->line_separator;
						}
						$content .= $this->indent_levels[2] . '<id>' . $domain . '?id=' . $newest_entry['PermaLinkid'] . '</id>' . $this->line_separator;
						
						$content .= $this->indent_levels[1] . '</entry>' . $this->section_separator;
					}
				}
			}
			return $content;
		}
		
		public function ConvertHTMLToFormat_renderContent_FormattedDescription($args) {
			$description = $this->ConvertHTMLToFormat_renderContent_Description($args);
			$description = $this->ConvertHTMLToFormat_cleanupText(['text'=>$description]);
			
			if($this->version_float === 0.91 && strlen($description) > 500) {
				$description = trim(substr($description, 0, 497));
			}
			
			if($description) {
				$description .= '...';
			}
			
			return $description;
		}
		
		public function ConvertHTMLToFormat_renderContent_Description($args) {
			$entry = $args['entry'];
			
			if($entry['description'] && $entry['description'][0] && $entry['description'][0]['Description']) {
				return $entry['description'][0]['Description'];
			}
			
			if($entry['textbody'] && $entry['textbody'][0] && $entry['textbody'][0]['FirstThousandCharacters']) {
				$text = $entry['textbody'][0]['FirstThousandCharacters'];
				
				return $text;
			}
		}
		
		public function ConvertHTMLToFormat_cleanupText($args) {
			$text = $args['text'];
			
			$cleanup_swaps = [
				'<br>'=>' ',
				'<br >'=>' ',
				'<br />'=>' ',
				'</p>'=>'</p> ',
				"\n"=>' ',
				"\r"=>' ',
				"\t"=>' ',
			];
			
			$text = str_replace(array_keys($cleanup_swaps), array_values($cleanup_swaps), $text);
			
			$text = strip_tags($text);
			
			$text = trim(preg_replace('/[\s]+/', ' ', $text));
			
			$text = html_entity_decode($text);
			
			$text = str_replace(['<', '>', '&',], ['&lt;', '&gt;', '&amp;'], $text);
			$text = preg_replace('/Image::(\d+)/', '', $text);
			
			return $text;
		}
	}

?>