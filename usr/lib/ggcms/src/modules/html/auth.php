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
		
			/*
				The site bar carries sign-in on every page that has one, so a
				template's own call here would only say it twice.
			*/
		
		public function Display() {
			if(class_exists('module_sitebar', FALSE) && module_sitebar::$displayed) {
				return TRUE;
			}
			
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
		
			/*
				For a front page: what signing in is for, said plainly, to a
				reader who has not.  Upvotes, the discussion and suggested
				corrections all need it; reading never does.  Signed in, there
				is nothing to invite them to.
			*/

		public function DisplayJoin() {
			if($this->that->handler->authentication->user_session) {
				return FALSE;
			}

			print('<aside class="join" aria-labelledby="join-title">');
			print('<h2 class="join-title" id="join-title">Join the readers</h2>');
			print('<p>Everything here is free to read without an account. With one, you can help other readers find their way.</p>');
			print('<ul class="join-list">');
			print('<li><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M12 4l7 8h-4v8H9v-8H5z"/></svg><span><strong>Upvote</strong>Mark the texts that helped you, so others can find them.</span></li>');
			print('<li><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/></svg><span><strong>Discuss</strong>Every text has its own discussion.</span></li>');
			print('<li><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13 7l4 4"/></svg><span><strong>Suggest a correction</strong>Spotted a typo or a missing source? Send a fix for review.</span></li>');
			print('</ul>');
			print('<p class="join-actions"><a class="btn btn-primary" href="/login.php" rel="nofollow">Sign in with Google</a></p>');
			print('</aside>');

			return TRUE;
		}

		public function DisplayBlockStart() {
			print('<p class="auth">');
			
			return TRUE;
		}
		
		public function DisplayBlockEnd() {
			print('</p>');
			
			return TRUE;
		}
		
		public function DisplayLoggedInStatus() {
			print('Signed in as ');
			
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
			print('Sign in');
			print('</a>');
			
			return TRUE;
		}
		
		public function DisplayLogoutLink() {
			print(' <a href="logout.php?redirect=' . $this->redirect_url . '" rel="nofollow">Sign out</a>');
			
			return TRUE;
		}
	}
?>