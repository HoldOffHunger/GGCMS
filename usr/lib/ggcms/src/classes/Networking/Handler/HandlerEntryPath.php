<?php

	/*
		Handler's entry-path stage: does this path walk the entry graph, and
		if not, can it be repaired into one that does.  Asked before anything
		that would render is loaded.  Moved out of Handler on 28 September
		2026.  Logic only: every property it reads or sets is still Handler's,
		reached through $this->handler.
	*/

	class HandlerEntryPath {
		public $handler;
		
		public function __construct($args) {
			$this->handler = $args['handler'];
		}
		
		/*
			Does this path name a real walk through the entry graph?

			HandleRequest_Content builds a format object and a script object before
			it finds out, and both pull in class files -- AbstractBaseFormat, the
			format, view, base_format and the traits.  A request for a path that
			names nothing paid for all of it and then answered 404.  On 3 September
			2026 the majority of traffic to this host was exactly that.

			The question is cheap to ask first.  ORM is already in memory --
			StandardLibraries requires it before this class is constructed -- its
			constructor wants nothing but the handler, and GetRecordTree is row
			cached.  So this costs one query, often none, and loads nothing.

			The test is ValidateOrm's, because it is the same question: every
			segment of the path must have resolved to a record.  A shorter answer
			than the path means some segment named nothing.

			Only entry walks are asked.  The front page has no segments, and
			style.php, sitemap.php, robots.php and search.php are not paths through
			the graph at all; all of them answer TRUE and carry on untouched.
		*/

		public function EntryPathResolves() {
			if(!is_array($this->handler->object_list) || (count($this->handler->object_list) === 0)) {
				return TRUE;		# the front page names no entry
			}

			if($this->handler->script_name !== 'view.php') {
				return TRUE;		# not a walk through the entry graph
			}

			if(!$this->EntryPathRequired()) {
				return TRUE;		# this site's paths name something other than entries
			}

			if(!$this->handler->db_access) {
				return TRUE;		# nothing to ask; let the old path answer
			}

			if(!$this->handler->orm) {
				$this->handler->orm = new ORM(['handler'=>$this->handler]);
			}

			$this->handler->resolved_record_list = $this->handler->orm->GetRecordTree([
				'codelist'=>$this->handler->object_list,
				'availabilitylimit'=>1,
			]);

			if(!is_array($this->handler->resolved_record_list)) {
				return FALSE;
			}

			return (count($this->handler->object_list) === count($this->handler->resolved_record_list));
		}

		/*
			Whether this site's view.php paths are walks through the entry graph
			at all.

			Every site's are bar wordweight's.  /funerate/ there is a word from
			alldictionaries, which display_wordweight looks up for itself, and
			names no entry.  Asked of EntryPathResolves it answered 404, and did
			so for every word on the site from 3 September 2026 until this
			existed.

			Script-level AbstractGlobals config, in the shape Dictionary_enabled
			already uses.  Absent config or an absent method means required,
			which is the behaviour before this existed.
		*/

		public function EntryPathRequired() {
			if(!isset($this->handler->abstractglobals->script)) {
				return TRUE;
			}

			if(!is_object($this->handler->abstractglobals->script)) {
				return TRUE;
			}

			if(!method_exists($this->handler->abstractglobals->script, 'EntryPath_required')) {
				return TRUE;
			}

			return $this->handler->abstractglobals->script->EntryPath_required([
				'action'=>$this->handler->desired_action,
			]);
		}

		/*
			A path that named nothing, corrected and answered rather than
			redirected.

			This can only run where EntryPathResolves has already said no,
			which is the one place in the request where the corrections are
			known and nothing has been loaded to render with.  So the request
			is rewritten and carried forward -- the chain is never re-entered,
			no file is required twice, and the invariant every ggreq in this
			codebase rests on is untouched.

			The handlers are asked what they would have redirected to rather
			than allowed to send it.  All three want only db_access and
			script_name, both of which exist long before a format or a script
			does.

			handleScriptRedirect is deliberately absent: it reads
			$this->script->script->redirect_script, and there is no script
			object here yet.  It keeps its redirect, below, where there is.
		*/

		public function RepairEntryPath() {
			while($this->handler->repair_count < 3) {
				if($this->RepairEntryPath_Once()) {
					return TRUE;
				}

				if(strlen($this->handler->redirect_url)) {
					return FALSE;		# a correction we may not make ourselves
				}

				if(!$this->handler->last_repair_changed) {
					return FALSE;		# nothing left to correct
				}
			}

			return FALSE;
		}

		/*
			One correction.  '/x/view.php' becomes '/x/', which on the next
			pass becomes '/parent/x/', which resolves.  So the caller keeps
			asking while something is still changing, up to three times.
		*/

		public function RepairEntryPath_Once() {
			$this->handler->last_repair_changed = FALSE;

			$this->handler->collect_redirect = TRUE;
			$this->handler->redirect_url = '';

			$this->handler->redirects->handleReservedCodeRedirect();

			if(!$this->handler->redirect_url) {
				$this->handler->redirects->handleMatchingCodeRedirect();
			}

			if(!$this->handler->redirect_url) {
				$this->handler->redirects->handleMisplacedScriptRedirect();
			}

			$this->handler->collect_redirect = FALSE;

			$target = $this->handler->redirect_url;

			if(strlen($target) === 0) {
				return FALSE;
			}

				/*
					Off this host, or a scheme change, or not a GET: those are
					redirects for good reasons and are left as redirects.  The
					url is put back so the chain below sends it.
				*/

			$this->handler->redirect_url = '';

			if(!$this->handler->redirects->RepairInsteadOfRedirect(['url'=>$target])) {
				$this->handler->redirect_url = $target;

				return FALSE;
			}

			$this->handler->last_repair_changed = TRUE;

			return $this->EntryPathResolves();
		}
	}

?>