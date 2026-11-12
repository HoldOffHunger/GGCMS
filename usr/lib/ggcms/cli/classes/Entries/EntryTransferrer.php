<?php

	clireq('classes/Entries/EntryModifier.php');

	/*
		Drives transfer.php from a shell: the entry at --path moved beneath
		the entry --to names, with everything beneath it.

		Like EntryModifier and EntryImporter it reimplements nothing.  It
		makes the shell look like the transfer form being posted and runs the
		real transfer.php, so its guards are the page's own: no parent that
		is not there, no move under itself or anything beneath it, no parent
		that already has an entry of the same code.  And so is what it
		leaves behind: the old paths of the entry and of every entry filed
		directly beneath it reserved, so their links still find them.

		A shell on the host can already do anything, so --user=ADMIN asks for
		nothing a password would have protected.  transfer.php refuses
		anybody who is not an administrator before it reads a field.

		Without --apply it says what it would do and posts nothing, since
		transfer.php itself has no preview.
	*/

	class EntryTransferrer extends EntryModifier {
		public $to;

		public function bannerMessageText() {
			return 'Transfer Entry';
		}

		public function confirmDomainText() {
			return 'Transferring an entry on: ';
		}

			// Entry point
			// -----------------------------------------------

		public function transferEntry() {
			$this->bannerMessage();
			$this->setHandle();

			if(!$this->checkExtensions()) {
				return FALSE;
			}

			if(!$this->setDomain()) {
				return $this->cancelAction(['message'=>'Invalid domain.  Please submit a FQDN in the form of `example.com`.']);
			}

			$this->setTransferOptions();

			if(trim($this->path, '/') === '') {
				return $this->cancelAction(['message'=>'Nothing to move.  Pass --path=/the/entry/ to move.']);
			}

			if($this->to <= 0) {
				return $this->cancelAction(['message'=>'Nowhere to move it.  Pass --to=<entry id> of the new parent.']);
			}

			if(!strlen($this->user)) {
				return $this->cancelAction(['message'=>'transfer.php is an administrator\'s page.  Pass --user=ADMIN, or --user=<id>.']);
			}

			print('Entry    : /' . trim($this->path, '/') . '/' . PHP_EOL);
			print('To       : beneath Entry ' . $this->to . PHP_EOL);
			print('User     : ' . $this->user . PHP_EOL);
			print('Mode     : ' . ($this->apply ? 'APPLY -- writes to the database' : 'preview -- nothing is written') . PHP_EOL . PHP_EOL);

			if(!$this->apply) {
				print('Preview.  Nothing was posted; transfer.php checks the move when it makes it.  Add --apply to move.' . PHP_EOL);

				return TRUE;
			}

			return $this->runTransfer();
		}

		public function setTransferOptions() {
			$this->user = $this->option(['name'=>'user', 'default'=>'']);
			$this->apply = $this->flag(['name'=>'apply']);
			$this->path = $this->option(['name'=>'path', 'default'=>'/']);
			$this->to = (int)$this->option(['name'=>'to', 'default'=>'0']);

			return TRUE;
		}

			//  transfer.php at the entry being moved: the path names the entry, the verb names the script.

		public function scriptURL() {
			return '/' . trim($this->path, '/') . '/transfer.php';
		}

		public function runTransfer() {
			$this->fakeRequest([
				'action'=>'transferentry',
				'post'=>[
					'target-parent'=>(string)$this->to,
				],
			]);

			require(GGCMS_DIR . 'classes/StandardLibraries.php');

			clireq('classes/Entries/CLIAuthentication.php');
			clireq('classes/Entries/CLIHandler.php');

			ob_start();
			$handler = new CLIHandler();
			$handler->SetCLIUser(['user'=>$this->user]);
			$handler->HandleRequest();
			$output = ob_get_contents();
			ob_end_clean();

			return $this->reportTransfer(['handler'=>$handler, 'output'=>$output]);
		}

			/*
				What transfer.php said.  Its errors are in admin_errors, as the
				page shows them; a move made leaves the updated assignment in
				entry_update.  A request that never reached the script is
				printed, since the page is then all there is to say why.
			*/

		public function reportTransfer($args) {
			$handler = $args['handler'];

			$authentication_error = $handler->CLIAuthenticationError();

			if($authentication_error) {
				print('Refused  : ' . $authentication_error . PHP_EOL);

				return FALSE;
			}

			$script = (isset($handler->script) && is_object($handler->script)) ? $handler->script->script : NULL;

			if(!$script || !($script instanceof transfer)) {
				print('The request did not reach transfer.php.  Does --path resolve, and is --user an administrator?' . PHP_EOL . PHP_EOL);
				print(trim(strip_tags($args['output'])) . PHP_EOL);

				return FALSE;
			}

			foreach((array)$script->admin_errors as $error) {
				print('Refused  : ' . implode(' ', (array)$error) . PHP_EOL);
			}

			if(count((array)$script->admin_errors)) {
				return FALSE;
			}

			if($script->conflicting_entry_count) {
				print('Refused  : Entry ' . $this->to . ' already has an entry with this one\'s code.' . PHP_EOL);

				return FALSE;
			}

			if(empty($script->entry_update)) {
				print('Refused  : transfer.php made no move, and said nothing about why.' . PHP_EOL);

				return FALSE;
			}

			$parent = $script->new_parent_results;

			print('Transferred /' . trim($this->path, '/') . '/ beneath Entry ' . $this->to . (isset($parent['Title']) ? ' ("' . $parent['Title'] . '")' : '') . ', with everything beneath it.' . PHP_EOL);
			print('Its old path, and the old path of everything filed directly beneath it, are reserved.' . PHP_EOL);

			return TRUE;
		}
	}

?>
