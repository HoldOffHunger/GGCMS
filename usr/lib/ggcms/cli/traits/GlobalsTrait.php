<?php

	trait GlobalsTrait {
		public function setGlobals() {
			confreq('clonefrom.php');

				/*
					A site's own configuration, when this tool is working on a
					site.

					Handler::Construct_Globals() has always done this -- load
					clonefrom.php for the base class, then the reversed-domain
					file beside it for the site, and instantiate whichever it
					found.  The command line loaded only the base, so every CLI
					tool saw default configuration no matter which domain it had
					been pointed at.

					It went unnoticed because nothing on the command line asked
					globals a question whose answer differed per site.  The CAA
					checks in DomainChecker do -- they ask whether the site is
					commercial -- and got the default answer for every domain,
					silently, which is the failure mode that makes this worth
					fixing rather than working around.

					Defensive on purpose: a tool that spans every domain, as
					IssueCounts does, sets no domain at all and must keep
					getting the base class.
				*/

			if(isset($this->reversed_domain) && strlen((string) $this->reversed_domain)) {
				$site_globals_file = $this->reversed_domain . '.php';

				if(conf_isfile($site_globals_file)) {
					confreq($site_globals_file);

					return $this->globals = new globals([]);
				}
			}

			$globals = new defaultglobals([]);

			return $this->globals = $globals;
		}
	}

?>