<?php

	class module_auth extends module_spacing {
		public $that;
		public $username;
		public $redirect_url;
		
		public function __construct($args) {
			$this->that = $args['that'];
			
			if($this->that->handler->authentication->user_session['User.Username']) {
				$this->username = $this->that->handler->authentication->user_session['User.Username'];
			} else {
				$this->username = $this->that->handler->authentication->user_session['User.EmailAddress'];
			}
			
			$this->redirect_url = urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		}
		
		public function Display() {
			$this->DisplayBlockStart();
			
			if($this->that->handler->authentication->user_session) {
				$this->DisplayLoggedInStatus();
				$this->DisplayLogoutLink();
			} else {
				$this->DisplayLoginLink();
			}
			
			$this->DisplayBlockEnd();
			
			return TRUE;
		}
		
		public function DisplayBlockStart() {
			print('<div class="float-right border-2px background-color-gray13">');
			print('<p class="font-family-arial margin-2px">');
			
			return TRUE;
		}
		
		public function DisplayBlockEnd() {
			print('</p>');
			print('</div>');
			
			return TRUE;
		}
		
		public function DisplayLoggedInStatus() {
			print('logged in: ');
			
			print('<b>' . $this->username . '</b>');
			
			return TRUE;
		}
		
			/*
				Always plain /login.php.  Carrying the current page in ?redirect=
				made every page on every site link to its own login URL, and
				crawlers nested those inside each other without end.
			*/
			
		public function DisplayLoginLink() {
			print('<a href="/login.php" rel="nofollow">');
			print('Not Logged In: Login?');
			print('</a>');
			
			return TRUE;
		}
		
		public function DisplayLogoutLink() {
			print(' <a href="logout.php?redirect=' . $this->redirect_url . '">(logout)</a>');
			
			return TRUE;
		}
	}
?>