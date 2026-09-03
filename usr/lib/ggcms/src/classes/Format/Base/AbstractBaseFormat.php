<?php

	class AbstractBaseFormat {
			// Base MimeType
			// -----------------------------------------------
		
		public function MimeType() {
			return die('You must set this type in your inheriting format.');
		}
		
			// Format Conversion
			// -----------------------------------------------
		
		public function ConvertHTMLToFormat() {
			return die('You must set this converting in your inheriting format.');
		}
		
			// Required Set Functions
			// -----------------------------------------------
		
		public function HTTPHeaders() {
			$base_headers = [
				$this->HTTPHeader_ContentType(),
				$this->HTTPHeader_ContentDisposition(),
			];
			
			if($this->AcceptCharsetHeader()) {		# go crazy, my fren: https://developer.mozilla.org/en-US/docs/Glossary/Forbidden_header_name
				$base_headers[] = 'Accept-Charset: UTF-8, ISO-8859-1';
				$base_headers[] = 'Accept-Encoding: *';
			}
			
			return $base_headers;
		}
		
		public function HTTPHeader_ContentType() {
			return 'Content-Type: ' . $this->MimeType() . '; charset=utf-8';
		}
		
		public function HTTPHeader_ContentDisposition() {
			return 'Content-Disposition:' . $this->ContentDisposition() . ';filename=' . urlencode($this->filename_public) . '.' . $this->handler->script_format_lower;
		}
		
		public function ContentDisposition() {
			if($this->ReadableInBrowser()) {
				return 'inline';
			}
			
			return 'attachment';
		}
		
		public function HTMLEntities() {
			return TRUE;
		}
		
		public function AcceptCharsetHeader() {
			return FALSE;
		}
		
		public function SetFileNameDisplay() {
			$this->SetFileNameDisplay_Internal();
			$this->SetFileNameDisplay_Public();
			
			return TRUE;
		}
		
		public function SetFileNameDisplay_Internal() {
			$filename = $this->script->entry['id'];
			
				# BT: TODO: Jazz this up a bit?  full parent tree codes, what?
				
				# Note: This is used for caching shit, so, yeah.  Be careful fucking too hard with it.
			
			return $this->filename_internal = $filename;
		}
		
		public function SetFileNameDisplay_Public() {
			$filename = '';
			
				# 			if($this->script->record_to_use && $this->script->record_to_use['Code']) {
				#	BT: FIXME; is record_to_use redundant?  yes, yes it is
			if($this->script->entry && $this->script->entry['Code']) {
				$filename = $this->script->entry['Code'];
			} else {
				$filename = 'portable-file';
			}
			
			return $this->filename_public = $filename;
		}
		
			// Constructor
			// -----------------------------------------------
		
		public function __construct($args) {
			$this->SetArgs($args);
			
			$this->handleHumanReadable();
			$constructor_args = $this->SetScriptConstructorArgs($args);
			
			$this->Construct_Requires();
			
			require($this->script_location);
			$this->script = new $this->script_classname($constructor_args);
			
			return $this;
		}
		
		public function handleHumanReadable() {
			return $this->human_readable = $this->query_object->Parameter(['parameter'=>'humanreadable']);
		}
		
		public function HandleHTTPHeaders() {
			$headers = $this->HTTPHeaders();
			
			foreach($headers as $header) {
				header($header);
			}
			
			return TRUE;
		}
		
			// Construct ~ Requires
			// -----------------------------------------------
		
		public function Construct_Requires() {
			classreq('scripts/Format/base_format.php');
			ggreq('scripts/Format/' . $this->script_format . '/basicscript.php');
			
			$this->Construct_Requires_Extras();
			
			return TRUE;
		}
		
		public function Construct_Requires_Extras() {
			return TRUE;
		}
		
			// Source Files
			// -----------------------------------------------
		
		public function SetSourceFileLocation() {
			$source_file_location = GGCMS_DIR . 'data/' . $this->handler->script_format_lower . '/' . $this->domain_object->host . '/' . $this->filename_internal . '.html';
			
			return $this->source_file_location = $source_file_location;
		}
		
		public function SetOutputFileLocation() {
			$output_file_location = GGCMS_DIR . 'data/' . $this->handler->script_format_lower . '/' . $this->domain_object->host . '/' . $this->filename_internal . '.' . $this->handler->script_format_lower;
			
			return $this->output_file_location = $output_file_location;
		}
		
			// Script Running
			// -----------------------------------------------
		
		public function RunScript() {
			$desired_action = $this->desired_action;
			$display_results = $this->script->$desired_action();
			
			if(!$display_results) {
				return FALSE;
			}
			
			$this->script->SetDocumentAttributes();
			
			return TRUE;
		}
		
		public function RunTemplates() {
			if($this->script->source_content) {
				return $this->script->source_content;
			}
			
			$this->script->DisplayTemplates();
			
			if(!$this->HTMLEntities()) {
				$this->script->source_content = html_entity_decode($this->script->source_content);
			}
			
			return $this->script->source_content;
		}
		
		public function DocumentStartSyntax() {
			return '';
		}
		
		public function DocumentEndSyntax() {
			return '';
		}
		
		public function StartDocument() {
			print $this->DocumentStartSyntax();
			
			if($this->human_readable) {
				print("\n");
			}
			
			return TRUE;
		}
		
		public function EndDocument() {
			print $this->DocumentEndSyntax();
			
			if($this->human_readable) {
				print("\n");
			}
			
			return TRUE;
		}
		
			// Caching
			// -----------------------------------------------
		
		public function getLastScriptRun() {
			if($this->last_script_run) {
				return $this->last_script_run;
			}
			
			$source_file_location = $this->SetSourceFileLocation();
			
			if(is_file($source_file_location)) {
				return $this->last_script_run = file_get_contents($source_file_location);
			}
			
			return $this->last_script_run = '';
		}
		
			// Security ~ Access
			// -----------------------------------------------
		
		public function CanAccess() {
			return ($this->script && $this->script->IsAccessible());
		}
		
		public function ReadableInBrowser() {
			return TRUE;
		}
		
			// Script Args
			// -----------------------------------------------
			
		public function SetArgs($args) {
			$this->handler = $args['handler'];
			
				# WTF: WHY is this require_once required????
			require_once(GGCMS_DIR . '/classes/Script/PHPReservedWords.php');
			$this->SetArgsOldSchool($args);		# TODO: delete
			$this->CorrectClassName();
			
			return TRUE;
		}
			
		public function SetScriptConstructorArgs($args) {
			$this->handler = $args['handler'];
			
			$script_constructor_args = [
				'handler'=>$this->handler,
				'formatobject'=>$this,	# TODO: delete 'formatobject'
				'format'=>$this,
				
				/*
					HTML built its own args list including 'dictionary'; this
					one did not, so every other format handed the script a null
					dictionary.  RDF, XML, ATOM and RSS all render the HTML
					templates before converting them, so a template calling
					$this->dictionary fatalled on any non-HTML request -- 899 of
					them on wordweight alone.
					
					Fixed here rather than in each format, and rather than
					guarding the template, which would only move the problem to
					the next template that needs it.
				*/
				
				'dictionary'=>$this->dictionary,
				
				'desiredscript'=>$this->desired_script,		# TODO: delete everything from this line down in this array
				'desiredaction'=>$this->desired_action,
				'objectcode'=>$this->object_code,
				'objectparent'=>$this->object_parent,
				'objectlist'=>$this->object_list,
				'scriptlocation'=>$this->script_location,
				'scriptname'=>$this->script_name,
				'scriptfile'=>$this->script_file,
				'scriptextension'=>$this->script_extension,
				'scriptformat'=>$this->script_format,
				'scriptformatlower'=>$this->script_format_lower,
				'scriptargs'=>$this->script_args,
				'authenticationobject'=>$this->authentication_object,
				'cleanserobject'=>$this->cleanser_object,
				'queryobject'=>$this->query_object,
				'dbaccessobject'=>$this->db_access_object,
				'domainobject'=>$this->domain_object,
				'languageobject'=>$this->language,
				'time'=>$this->time,
				'cookie'=>$this->cookie,
				'formatsobject'=>$this->formats_object,
				'versionobject'=>$this->version_object,
				'redirectobject'=>$this->redirect_object,
			];
			
			return $script_constructor_args;
		}
		
			// Old-School Args (FIXME: DELETE)
			// -----------------------------------------------
			
		public function SetArgsOldSchool($args) {
			$this->script_location = $args['scriptlocation'];
			
			$this->authentication_object = $args['authentication'];
			$this->version_object = $args['versionobject'];
			$this->cleanser_object = $args['cleanser'];
			$this->query_object = $args['query'];
			$this->db_access_object = $args['dbaccess'];
			$this->domain_object = $args['domain'];
			$this->language = $args['language'];
			$this->time = $args['time'];
			$this->cookie = $args['cookie'];
			$this->globals = $args['globals'];
			$this->dictionary = $args['dictionary'];
			$this->desired_script = $args['desiredscript'];
			$this->desired_action = $args['desiredaction'];
			$this->desired_function = $args['desiredfunction'];
			
			$this->object_code = $args['objectcode'];
			$this->object_parent = $args['objectparent'];
			$this->object_list = $args['objectlist'];
			
			$this->script_name = $args['scriptname'];
			$this->script_file = $args['scriptfile'];
			$this->script_classname = $args['scriptclassname'];
			$this->script_extension = $args['scriptextension'];
			$this->script_format = $args['scriptformat'];
			$this->script_format_lower = $args['scriptformatlower'];
			$this->script_args = $args['scriptargs'];
			
			$this->google_api = $args['googleapi'];
			
			return TRUE;
		}
		
		public function CorrectClassName() {
			if($this->classname_corrected) {
				return TRUE;
			}
			
			$reserved_words = new PHPReservedWords();
			
			if($reserved_words->isWordReserved(['word'=>$this->script_classname])) {
				$this->script_classname .= 'Script';
			}
			
			$this->classname_corrected = TRUE;
			
			return TRUE;
		}
	}

?>