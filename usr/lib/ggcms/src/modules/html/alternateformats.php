<?php

	class module_alternateformats extends module_spacing {
		public $that;
		public $filename;
		public $audio;
		
		public function __construct($args) {
			$this->that = $args['that'];
			$this->filename = $this->that->handler->script_file;
			if(array_key_exists('audio', $args)) {
				$this->audio = $args['audio'];	// enable audio alternate-formats?
			} else {
				$this->audio = TRUE;
			}
		}
		
		public function Display() {
			print('<table border="1" style="margin-top:5px; border-collapse: true;" class="border-2px"><tr>');
			if($this->audio) {
							// Audio Player
						
						// -------------------------------------------------------------
				
				print('<td>');
				
				print('<div class="background-color-gray15 margin-2px float-left">');
				print('<div class="margin-2px font-family-arial font-size-150percent">');
				print('<button style="font-size:1.0em;" id="play-text-as-audio" class="font-family-arial"><img width="20" src="/image/media-controls/play.png" style="margin-right:0px;"> Listen</button>');
				print('<div class="float-right">');
				print('<div class="margin-2px font-size-75percent">');
				print('<select id="voice-selection" class="float-right"></select><br>');
				print('<nobr>');
				print('On : ');
				print('<input type="text" id="start-on" size="4" value="1">');
				print(' of ');
				print('<span id="total-words">0</span>');
				print(' Words');
				print('</nobr>');
				/*
				print('<div id="container" style="width:100%; height:5px; border:1px solid black;">
				<div id="progress-bar" style="width:50%;
				background-color:green;
				height:30px;">
				</div>
				</div>');*/
				print('</div>');
				print('</div>');
				
				print('<progress id="reading-progress-bar" style="width:100%;height:10px;" value="0" max="100"></progress>');
				
				print('</div>');
				print('</div>');
				
				print('</td>');
			}
			
						// All Formats
					
					// -------------------------------------------------------------
			
			$formats = $this->getFormats();
			
			if($this->that->mobile_friendly) {
				$formats[0]['text'] = 'Standard<br><nobr>PC Format</nobr>';
				$formats[0]['url'] = $this->filename . '.php';
			}
			
			$formats_count = count($formats);
			
			print('<td>');
			for($i = 0; $i < $formats_count; $i++) {
				$format = $formats[$i];
				
				print('<div style="margin:2px;" class="float-left ' . $format['type'] . '-format-icon" title="">');
				
				print('<a href="');
				print($format['url']);
				print('">');
				
				print('<img width="25" src="');
				print($this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
				print('/image/formats/');
				print($format['type']);
				print('-icon.jpg');
				print('">');
				
				print('</a>');
				
				print('</div>');
				
				if($format['type'] === 'epub' && $this->audio) {
					print('<br>');
				}
			}
			
			print('</td></tr></table>');
			
			return TRUE;
		}
		
		public function getFormats() {
			return [
				[
					'text'=>'Mobile<br>Version',
					'type'=>'mobile',
					'url'=>$this->filename . '.php?mobilefriendly=1',
				],
				[
					'text'=>'PDF<br>File',
					'type'=>'pdf',
					'url'=>$this->filename . '.pdf',
				],
				[
					'text'=>'Printer<br>Friendly',
					'type'=>'printer-friendly',
					'url'=>$this->filename . '.php?printerfriendly=1',
				],
				[
					'text'=>'Plaintext<br>File',
					'type'=>'plaintext',
					'url'=>$this->filename . '.txt',
				],
				[
					'text'=>'Wrapped<br>Plaintext',
					'type'=>'wrapped-plaintext',
					'url'=>$this->filename . '.txt?wrapped=1',
				],
				[
					'text'=>'Inverted<br>Colors',
					'type'=>'inverted-colors',
					'url'=>$this->filename . '.php?invertedcolors=1',
				],
				[
					'text'=>'RTF<br>File',
					'type'=>'rtf',
					'url'=>$this->filename . '.rtf',
				],
				[
					'text'=>'BRF<br>File',
					'type'=>'brf2',
					'url'=>$this->filename . '.brf',
				],
				[
					'text'=>'Epub<br>File',
					'type'=>'epub',
					'url'=>$this->filename . '.epub',
				],
				[
					'text'=>'DAISY<br>Format',
					'type'=>'daisy',
					'url'=>$this->filename . '.daisy',
				],
				[
					'text'=>'SGML<br>Format',
					'type'=>'sgml',
					'url'=>$this->filename . '.sgml',
				],
				[
					'text'=>'JSON<br>Format',
					'type'=>'json',
					'url'=>$this->filename . '.json',
				],
				[
					'text'=>'XML<br>Format',
					'type'=>'xml',
					'url'=>$this->filename . '.xml',
				],
				[
					'text'=>'CSV<br>Format',
					'type'=>'csv',
					'url'=>$this->filename . '.csv',
				],
				[
					'text'=>'Latex<br>Format',
					'type'=>'latex',
					'url'=>$this->filename . '.tex',
				],
				[
					'text'=>'OPDS<br>Format',
					'type'=>'opds',
					'url'=>$this->filename . '.opds',
				],
				[
					'text'=>'RDF<br>Format',
					'type'=>'rdf',
					'url'=>$this->filename . '.rdf',
				],
				[
					'text'=>'BRF<br>File',
					'type'=>'brf',
					'url'=>$this->filename . '.brf?mode=dotted',
				],
			];
		}
	}
?>