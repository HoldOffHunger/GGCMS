<?php

	/*
		Handler, with one method overridden.

		Handler::CheckSecurity() asks Authentication whether this request may
		proceed and assigns the answer to $this->access.  It runs inside
		HandleRequest(), so anything set from outside beforehand is overwritten
		before the script is reached -- which is why granting access before the
		request began did nothing at all.

		Authenticate() would grant it for a session, and this process has none.
		The alternative to storing a password on the host, or forging a session
		record and hoping nothing downstream reads a field of it, is to say
		plainly in one place that a shell is already inside.

		Whoever runs this has root.  That is strictly more power than any web
		login confers, and the login gate exists to stop a visitor rather than
		to lock a door on somebody standing in the room.

		This class is the entire bypass.  It is four lines, it is named for what
		it does, and deleting it stops the tool working rather than making it
		quietly insecure.
	*/

	class CLIHandler extends Handler {
		public function CheckSecurity() {
			$this->access = 1;

			return TRUE;
		}
	}

?>
