<?php

	/*
		Handler's script stage: from a request's path and action, which
		script answers it, in which file, under which class, in which format
		and with which extension -- view.php, rss, pdf, the lot.  Moved out
		of Handler on 28 September 2026.  Logic only: every property it
		reads or sets is still Handler's, reached through $this->handler,
		and HandlerRedirects::RepairRequest() runs it again after a repair.
	*/

	class HandlerScript {
		public $handler;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
		}
		
		public function Construct_ScriptName() {
			$cleanser_args = [
				'input'=>$this->handler->desired_script,
			];
			
			$this->handler->script_name = $this->handler->cleanser->CleanseInput($cleanser_args)['cleansedinput'];
			
			if(!$this->handler->script_name) {
				$this->Construct_ScriptName_SetScriptNameDefault();
			}
			
			return TRUE;
		}
		
		public function Construct_ScriptFileAndExtension() {
			$script_name_pieces = explode('.', $this->handler->script_name);
			array_pop($script_name_pieces);
			$this->handler->script_file = implode('.', $script_name_pieces);
			$this->handler->script_extension = pathinfo($this->handler->script_name, PATHINFO_EXTENSION);
			
			return TRUE;
		}
		
		public function Construct_ScriptClassname() {
			$this->handler->script_classname = str_replace('-', '', $this->handler->script_file);
			
			return TRUE;
		}
		
		public function Construct_ScriptFormat() {
			$this->handler->script_format = $this->Construct_ScriptFormat_DetermineScriptFormat();
			$this->handler->script_format_lower = $this->Construct_ScriptFormatLower_DetermineScriptFormatLower();
			
			return TRUE;
		}
		
		public function Construct_ScriptLocation() {
			switch($this->handler->script_format) {
				case 'CSS':
					$this->handler->script_location = GGCMS_DIR . 'scripts/style.php';
					break;
				
				default:
					$this->handler->script_location = GGCMS_DIR . 'scripts/' . $this->handler->script_file . '.php';
					break;
			}
		}
		
		public function Construct_ScriptName_SetScriptNameDefault() {
			return $this->handler->script_name = 'view.php';
		}
		
			# one day: https://gist.github.com/aymen-mouelhi/82c93fbcd25f091f2c13faa5e0d61760
		public function Construct_ScriptFormat_DetermineScriptFormat() {
			switch ($this->handler->script_extension) {
				case '':
				case 'php':
				case 'php3':
				case 'cfm':
				case 'cgi':
				case 'asp':
				case 'aspx':
				case 'htm':
				case 'html':
				case 'xhtml':
				case 'phtml':
				case 'shtml':
				case 'rhtml':
				case 'dll':
				case 'py':
				case 'rb':
				case 'php4':
				case 'pl':
				case 'wss':
				case 'jspx':
				case 'do':
				case 'action':
				case 'axd':
				case 'asx':
				case 'asmx':
				case 'ashx':
				case 'svc':
				case 'jsp':
				case 'yaws':
				case 'kt':
				case 'adp':
				case 'hta':
				case 'rjs':
				case 'erb':
				case 'htc':
				case 'dtl':
				case 'mvc':
					return 'HTML';
					
				case 'css':
					return 'CSS';
					
				case 'xml':
					return 'XML';
					
				case 'txt':
					return 'TXT';
				
				case 'pdf':
					return 'PDF';
					
				case 'rtf':
					return 'RTF';
					
				case 'epub':
					return 'EPub';
					
				case 'daisy':
					return 'DAISY';
					
				case 'json':
					return 'JSON';
					
				case 'csv':
					return 'CSV';
					
				case 'sgml':
					return 'SGML';
					
				case 'tex':
					return 'TEX';
					
				case 'opds':
					return 'OPDS';
					
				case 'rdf':
					return 'RDF';
					
				case 'rss':
					return 'RSS';
					
				case 'atom':
					return 'ATOM';
					
				case 'brf':
					return 'BRF';
					
					/*
				case 'apng':
				case 'avif':
				case 'bmp':
				case 'cur':
				case 'gif':
				case 'ico':
				case 'jfif':
				case 'jpeg':
				case 'jpg':
				case 'pjp':
				case 'pjpeg':
				case 'png':
				case 'svg':
				case 'tif':
				case 'tiff':
				case 'webp':
					return 'Image';
					*/
					
				default:
					return '';
			}
		}
		
		public function Construct_ScriptFormatLower_DetermineScriptFormatLower() {
			switch ($this->handler->script_extension) {
				case '':
				case 'php':
				case 'php3':
				case 'cfm':
				case 'cgi':
				case 'asp':
				case 'aspx':
				case 'htm':
				case 'html':
				case 'xhtml':
				case 'phtml':
				case 'shtml':
				case 'rhtml':
				case 'dll':
				case 'py':
				case 'rb':
				case 'php4':
				case 'pl':
				case 'wss':
				case 'jspx':
				case 'do':
				case 'action':
				case 'axd':
				case 'asx':
				case 'asmx':
				case 'ashx':
				case 'svc':
				case 'jsp':
				case 'yaws':
				case 'kt':
				case 'adp':
				case 'hta':
				case 'rjs':
				case 'erb':
				case 'htc':
				case 'dtl':
				case 'mvc':
					return 'html';
					
				case 'css':
					return 'css';
					
				case 'xml':
					return 'xml';
					
				case 'txt':
					return 'txt';
					
				case 'pdf':
					return 'pdf';
					
				case 'rtf':
					return 'rtf';
					
				case 'epub':
					return 'epub';
					
				case 'daisy':
					return 'daisy';
				
				case 'json':
					return 'json';
					
				case 'csv':
					return 'csv';
					
				case 'sgml':
					return 'sgml';
					
				case 'tex':
					return 'tex';
					
				case 'opds':
					return 'opds';
					
				case 'rdf':
					return 'rdf';
					
				case 'rss':
					return 'rss';
					
				case 'atom':
					return 'atom';
					
				case 'brf':
					return 'brf';
					
				default:
					return '';
			}
		}
	}

?>