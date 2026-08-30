<?php

	class Formats {
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			return $this;
		}
		
		public function GetListOfAlternateVersionFormats() {
			return [
				'HTML'=>'php',
				'handheld'=>'php?mobilefriendly=1',
				'XML'=>'xml',
				'Mobile'=>'php?mobilefriendly=1',
				'Text'=>'txt',
				'PDF'=>'pdf',
				'RDF'=>'rdf',
			];
		}
		
		public function GetListOfSupportedFormatExtensions() {
			return [
				'HTML'=>'php',
				'Mobile'=>'php?mobilefriendly=1',
				'PDF'=>'pdf',
				'Printer-Friendly'=>'php?printerfriendly=1',
				'Plaintext'=>'txt',
				'Plaintext-Wrapped'=>'txt?wrapped=1',
				'Inverted-Colors'=>'php?invertedcolors=1',
				'RTF'=>'rtf',
				'EPUB'=>'epub',
				'DAISY'=>'daisy',
				'SGML'=>'sgml',
				'JSON'=>'json',
				'XML'=>'xml',
				'CSV'=>'csv',
				'TEX'=>'tex',
				'OPDS'=>'opds',
				'RDF'=>'rdf',
			];
		}
		
		public function GetListOfSupportedFormatExtensions_NEW() {
			return [
				'HTML'=>[
						'extension'=>'php',
						'enabled'=>$this->handler->linkToFormat_HTML(),
					],
				'Mobile'=>[
						'extension'=>'php?mobilefriendly=1',
						'globals_function'=>'linkToFormat_HTMLMobileFriendly',
					],
				'PDF'=>[
						'extension'=>'pdf',
						'globals_function'=>'linkToFormat_PDF',
					],
				'Printer-Friendly'=>'php?printerfriendly=1',
				'Plaintext'=>'txt',
				'Plaintext-Wrapped'=>'txt?wrapped=1',
				'Inverted-Colors'=>'php?invertedcolors=1',
				'RTF'=>'rtf',
				'EPUB'=>'epub',
				'DAISY'=>'daisy',
				'SGML'=>'sgml',
				'JSON'=>'json',
				'XML'=>'xml',
				'CSV'=>'csv',
				'TEX'=>'tex',
				'OPDS'=>'opds',
				'RDF'=>'rdf',
			];
		}
	}

?>