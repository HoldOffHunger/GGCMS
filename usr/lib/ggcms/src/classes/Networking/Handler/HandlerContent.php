<?php

	/*
		Handler's content stage: build the format and the script for this
		request and render it -- or answer with the 404.  Nearly all of a
		request's time is spent here: 93.5% of it, measured over the 405-page
		crawl on 28 September 2026, when it was moved out of Handler.  Logic
		only: every property it reads or sets is still Handler's, reached
		through $this->handler.
	*/

	class HandlerContent {
		public $handler;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
		}
		
		public function HandleRequest_Content() {
			$client_location = GGCMS_DIR . $this->handler->domain->primary_domain_lowercased . $_SERVER['SCRIPT_URL'];
			
			$shared_location = GGCMS_DIR . GGCMS_REFERENCE_DOMAIN . $_SERVER['SCRIPT_URL'];
			
			if(!is_file($client_location) && is_file($shared_location)) {
				ggreq('classes/Networking/MIMEType.php');
				
				$mimetype = new MIMEType($this->handler->getArgs());
				$mimetypes = $mimetype->GetMIMETypeCodes();
				
				$desired_content_header = $mimetypes[$this->handler->script_extension];
				
				if($desired_content_header) {
					$header_text = 'Content-type: ' . $desired_content_header . '; charset=utf-8';
					header($header_text);
				}
				
				if($desired_content_header == 'text/html') {
					return require($shared_location);
				} else {
					return readfile($shared_location);
				}
			}
			
			if(!is_file($this->handler->script_location) || !$this->handler->script_format) {
				return FALSE;
			}
			
			$this->HandleRequest_Content_Format_GetFormatObject();
			$this->handler->script = $this->HandleRequest_Content_Format_InstantiateFormatObject();
			
			if($this->handler->script->CanAccess()) {
				return $this->HandleRequest_Content_Format();
			}
			
			return FALSE;
		}
		
		public function HandleRequest_Content_Format() {
			$this->handler->CheckSecurity();
			
			$this->handler->Construct_UpgradeDBAccess();
			
			#	print("BT: ACCESS?");
			if($this->handler->access) {
			#	print("BT: ACCESS!");
				if(method_exists($this->handler->script->script, $this->handler->desired_action)) {
		#			print("BT: METH!" . $this->desired_action . "|");
					$desired_action = $this->handler->desired_action;
					$response = $this->handler->script->Display();
					return $response;		# BT: FIXME ?  use $desired_action var pls; NO!
				}
			} else {
				if($this->handler->authentication->redirect) {
						# handle security-triggered redirect
					$other_script_args = $this->HandleRequest_Content_Format_InstantiateFormatObject_PartialArgs();
					$other_script_args['redirect'] = $this->handler->script->redirect_object;
					return ($this->handler->authentication->RedirectToNewURL($other_script_args));
				}
			}
			
			return FALSE;
		}
		
			/*
				Twice in one request now and then: handle404Image() loads the
				Image format, and when it serves nothing -- a .php name under
				/image/ that is also a script's name -- the request goes on to
				load a format again.  ggreq() is plain require, and a second
				AbstractBaseFormat was a fatal.
			*/
		
		public function HandleRequest_Content_Format_GetFormatObject() {
			if(!class_exists('AbstractBaseFormat', FALSE)) {
				ggreq('classes/Format/Base/AbstractBaseFormat.php');
			}
			
			if(class_exists($this->handler->script_format, FALSE)) {
				return TRUE;
			}

			return ggreq('classes/Format/' . $this->handler->script_format . '.php');
		}
		
		public function HandleRequest_Content_Format_InstantiateFormatObject() {
			$script_format_args = $this->HandleRequest_Content_Format_InstantiateFormatObject_Args();
			
			return (new $this->handler->script_format($script_format_args));
		}
		
		public function HandleRequest_Content_Format_InstantiateFormatObject_Args() {
			return [
				'handler'=>$this->handler,
				'firstcall'=>1,
				'authentication'=>$this->handler->authentication,
				'version'=>$this->handler->version,
				'versionobject'=>$this->handler->version_object,
				'cleanser'=>$this->handler->cleanser,
				'query'=>$this->handler->query,
				'dbaccess'=>$this->handler->db_access,
				'globals'=>$this->handler->globals,
				'domain'=>$this->handler->domain,
				'time'=>$this->handler->time,
				'cookie'=>$this->handler->cookie,
				'language'=>$this->handler->language,
				'desiredscript'=>$this->handler->desired_script,
				'desiredaction'=>$this->handler->desired_action,
				'dictionary'=>$this->handler->dictionary,
				'objectlist'=>$this->handler->object_list,
				'objectcode'=>$this->handler->object_code,
				'objectparent'=>$this->handler->object_parent,
				'scriptname'=>$this->handler->script_name,
				'scriptfile'=>$this->handler->script_file,
				'scriptclassname'=>$this->handler->script_classname,
				'scriptextension'=>$this->handler->script_extension,
				'scriptformat'=>$this->handler->script_format,
				'scriptformatlower'=>$this->handler->script_format_lower,
				'scriptlocation'=>$this->handler->script_location,
				'googleapi'=>$this->handler->google_api,
			];
		}
		
		public function HandleRequest_Content_Format_InstantiateFormatObject_PartialArgs() {
			return [
				'handler'=>$this->handler,
				'firstcall'=>0,
				'cleanser'=>$this->handler->cleanser,
				'dbaccess'=>$this->handler->db_access,
				'language'=>$this->handler->language,
				'globals'=>$this->handler->globals,
				'domain'=>$this->handler->domain,
				'objectcode'=>$this->handler->object_code,
				'objectlist'=>$this->handler->object_list,
				'scriptclassname'=>$this->handler->script_classname,
				'scriptextension'=>$this->handler->script_extension,
				'scriptformat'=>$this->handler->script_format,
			];
		}
		
		public function HandleRequest_Error_404() {
			$this->handler->error_404 = TRUE;

				/*
					Nothing in this codebase set a status code here, so every
					dead URL on every site answered 200 with an apology page.

					A crawler that receives 200 has been told the URL is real and
					comes back for it, forever, and the page cache will not store
					an error page -- so each visit is a full render.  Measured on
					31 August 2026: /w.php, a WordPress probe for a file that has
					never existed, cost 30.6 seconds of work and returned 200.
					There are thousands of such requests a day.

					Search engines call this a soft 404 and index the apology.

					The redirect handlers upstream of this method have already
					had their chance, so anything arriving here is a genuine dead
					end and can say so.
				*/

			http_response_code(404);

			$error_404 = new Error404($this->handler->getArgs());

			$error_404->Display([]);
			
			$this->handler->issue_logging->createLog([
				'issuetype'=>'404',
				'description'=>'404 URL',
			]);
			
			return TRUE;
		}
	}

?>