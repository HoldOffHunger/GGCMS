<?php

	/*
		Handler, with the authentication object swapped and nothing else.

		An earlier version of this class overrode CheckSecurity() and set
		$this->access = 1, which made modify.php save whether anybody was logged
		in or not.  That was wrong.  The gate is not decoration: modify.php
		refuses to write an entry for nobody, and it should go on refusing when
		the request arrives from a shell.

		So the security path is left exactly as it is.  CheckSecurity() runs,
		Authenticate() runs, and access_granted is decided by the same branches
		that decide it for a browser.  The single substitution is the class that
		answers the question "who is logged in" -- CLIAuthentication reads a
		--user argument where Authentication reads a cookie, and builds the
		session out of real User and UserAdmin rows.

		The consequence is that this tool can be refused, and is.  A --user
		naming somebody who does not exist gets nowhere.  A --user naming an
		ordinary account is refused every admin-only field -- Code and Publish
		among them -- by Authenticate(), not by anything written here.
	*/

	class CLIHandler extends Handler {

			/*
				parent is not called.  This method exists to construct a
				different class, and the two property resets above the
				assignment are the whole of what Handler does here.
			*/

		public function Construct_PresetAuthentication() {
			$this->access = 0;
			$this->redirect = '';

			$authentication = new CLIAuthentication($this->getArgs());

			return $this->authentication = $authentication;
		}

			/*
				Set after construction rather than passed in, because
				Construct_PresetAuthentication() is called from Handler's
				constructor -- there is no moment between building the handler
				and building its authentication in which a property could be
				assigned.

				That is late enough.  Nothing reads cli_user until
				CheckCurrentAuthentication(), which runs inside CheckSecurity()
				during HandleRequest().
			*/

		public function SetCLIUser($args) {
			$this->authentication->cli_user = $args['user'];

			return TRUE;
		}

			//  Read by EntryModifier when the request produced no save, so the
			//  reason given is the lookup's rather than modify.php's.

		public function CLIAuthenticationError() {
			return $this->authentication->cli_error;
		}
	}

?>
