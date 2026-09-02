<?php

	/*
		Authentication, with the cookie lookup replaced.

		The gate in modify.php must hold.  A shell is not exempt from it: if
		nobody is logged in, ValidateRecordForSaving_EntryPermission() should
		refuse the save, and it still does.

		What a shell cannot do is present a cookie.  CheckCurrentAuthentication()
		is the only method in this class that reads one -- it takes the
		AuthenticationToken, finds the UserSession row it belongs to, joins User
		and UserAdmin onto it, and leaves the result in $user_session.  Every
		decision Authenticate() makes afterwards reads that property and nothing
		else.

		So that one method is replaced, and the rest of Authentication runs
		untouched.  The session is assembled from real rows: a User that exists,
		and a UserAdmin row only if the database actually holds one.  Authenticate()
		then grants or refuses on its own terms.  Nothing sets access_granted by
		hand, nothing forges an admin flag, and a --user naming somebody who is not
		an administrator is refused by the same branch that refuses them on the web.

		The identity assertion is the whole of what this gives away, and it is
		worth stating plainly: whoever runs this has a root shell on the host,
		which is strictly more power than any web login confers.  The login gate
		exists to stop a visitor, not to lock a door on somebody already standing
		in the room.  It does not follow that the gate should be removed for them
		-- only that they may name themselves without a password.
	*/

	class CLIAuthentication extends Authentication {

			/*
				Set by CLIHandler before the request runs.  Either a numeric User
				id, or the literal ADMIN, which resolves to the single
				administrator account.
			*/

		public $cli_user = '';

			/*
				Filled in when the lookup fails, and read by EntryModifier so the
				failure arrives as one sentence naming the problem rather than as
				modify.php's "you may only save information if you are logged in",
				which would be true but would not say why.
			*/

		public $cli_error = '';

		public function CheckCurrentAuthentication() {
			if(!strlen($this->cli_user)) {
				$this->cli_error = 'No --user was given, so there is nobody to save as.  Pass --user=ADMIN, or --user=<id>.';

				return 0;
			}

			$record = ($this->cli_user === 'ADMIN')
				? $this->CLIAdministratorRecord()
				: $this->CLIUserRecord(['id'=>$this->cli_user]);

			if(!$record) {
				return 0;
			}

			return $this->CLIEstablishSession(['record'=>$record]);
		}

			/*
				--user=ADMIN, with no name to type and no id to look up.

				Erroring on more than one is deliberate and is the reason this
				resolves rather than taking the first row.  A host with two
				administrators has no single obvious answer, and quietly picking
				the lower id would attribute the work to whichever account
				happened to be created first.  Refusing costs one flag; guessing
				wrong writes the wrong name into an EntryPermission row and nobody
				looks at those again.
			*/

		public function CLIAdministratorRecord() {
			$administrator_args = [
				'type'=>'UserAdmin',
				'joins'=>[
					'LEFT JOIN'=>[
						'User'=>'User.id = UserAdmin.Userid',
					],
				],
			];

			$administrators = $this->handler->db_access->GetRecords($administrator_args);

			if(!$administrators || !count($administrators)) {
				$this->cli_error = 'There is no administrator account on this domain, so --user=ADMIN has nobody to resolve to.  Name a user directly with --user=<id>.';

				return FALSE;
			}

			if(count($administrators) > 1) {
				$named = [];

				foreach($administrators as $administrator) {
					$named[] = $administrator['User.Username'] . ' (id ' . $administrator['User.id'] . ')';
				}

				$this->cli_error = 'This domain has ' . count($administrators) . ' administrator accounts, so ADMIN is ambiguous: ' . implode(', ', $named) . '.  Name one with --user=<id>.';

				return FALSE;
			}

			$administrator = $administrators[0];

				/*
					Normalised, because the two lookups do not agree on key names.
					GetRecords leaves the queried table's own fields bare and
					prefixes only what a join brought in, so this query -- which
					asks for UserAdmin and joins User onto it -- returns the
					administrator id as `id` and the person as `User.*`, while the
					--user=<id> lookup below returns the opposite.
				*/

			return [
				'id'=>$administrator['User.id'],
				'Username'=>$administrator['User.Username'],
				'EmailAddress'=>$administrator['User.EmailAddress'],
				'UserAdminid'=>$administrator['id'],
			];
		}

			/*
				--user=<id>.  The UserAdmin join is a LEFT JOIN and is expected to
				miss for an ordinary user; UserAdmin.id then comes back empty and
				Authenticate() refuses the admin-only branches on its own.  That is
				the correct outcome rather than an error here.
			*/

		public function CLIUserRecord($args) {
			$id = $args['id'];

			if(!ctype_digit($id)) {
				$this->cli_error = '--user must be a numeric User id, or the word ADMIN.  Given: ' . $id;

				return FALSE;
			}

			$user_args = [
				'type'=>'User',
				'definition'=>[
					'id'=>intval($id),
				],
				'limit'=>1,
				'joins'=>[
					'LEFT JOIN'=>[
						'UserAdmin'=>'UserAdmin.Userid = User.id',
					],
				],
			];

			$users = $this->handler->db_access->GetRecords($user_args);

			if(!$users || !count($users)) {
				$this->cli_error = 'No user has id ' . intval($id) . ' on this domain.';

				return FALSE;
			}

			$user = $users[0];

			return [
				'id'=>$user['id'],
				'Username'=>$user['Username'],
				'EmailAddress'=>$user['EmailAddress'],
				'UserAdminid'=>$user['UserAdmin.id'],
			];
		}

			/*
				The shape CheckCurrentAuthentication() leaves behind on the web,
				built from a User row rather than a UserSession row.

				Two fields are worth the words.

				id is compared with intval() and === in modify.php's
				ValidateRecordForSaving_EntryPermission(), so it must be an int and
				not the numeric string a query returns.  On the web this field
				holds the UserSession row id.  There is no session row here, and
				the value therefore carries the User id instead -- which matters
				because modify.php also writes it into RecordChange.Userid, a
				column named for a user that receives a session id everywhere else.
				See the note in EntryModifier.

				LastAccess is now, so that ReAuthenticate() sees a fresh session and
				returns rather than calling RefreshAuthentication(), which would try
				to issue cookies into a process that has no browser.
			*/

		public function CLIEstablishSession($args) {
			$record = $args['record'];

			$userid = intval($record['id']);

			if(!$userid) {
				$this->cli_error = 'The account found has no User id, which should not be possible.  Check the User and UserAdmin tables.';

				return 0;
			}

			$this->user_session = [
				'id'=>$userid,
				'Userid'=>$userid,
				'LastAccess'=>date('Y-m-d H:i:s', $this->handler->time->time),
				'User.id'=>$userid,
				'User.Username'=>$record['Username'],
				'User.EmailAddress'=>$record['EmailAddress'],
				'UserAdmin.id'=>intval($record['UserAdminid']),
			];

			$this->user_account = [
				'id'=>$userid,
				'Username'=>$record['Username'],
				'EmailAddress'=>$record['EmailAddress'],
			];

			return TRUE;
		}
	}

?>
