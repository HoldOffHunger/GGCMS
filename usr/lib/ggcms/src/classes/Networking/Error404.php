<?php

	class Error404 {
		public function __construct($args) {
			$this->handler = $args['handler'];
		}
		
		/*
			An entry that exists but is not published is not the same as a
			URL that names nothing, and telling a reader we could not find
			something we found perfectly well is both untrue and unhelpful.
			It is worse for whoever wrote the page: on 3 September 2026 an
			entry sat unreachable for twenty-one months without ever saying
			so, because the refusal had no voice.
			
			Still a 404 -- to a reader without permission the resource is
			genuinely not there, and a crawler should not index it -- but a
			404 that says which of the two things happened.
			
			And no automatic redirect home.  The reader asked for a real
			thing that is not ready; taking them somewhere else five seconds
			later loses the address they were holding.
		*/

		public function DisplayUnavailableEntry($args) {
			$entry = $this->handler->unavailable_entry;

			print('<br><br>');
			print('<div style="font-family:arial;max-width:40em;margin:2em auto;padding:1em;border:2px solid #000;">');
			print('<h3 style="margin-top:0;">Not available yet</h3>');

			print('<p>');
			if(strlen($entry['Title'])) {
				print('&ldquo;' . htmlspecialchars($entry['Title'], ENT_QUOTES, 'UTF-8') . '&rdquo; ');
				print('exists, but it has not been published yet.');
			} else {
				print('That entry exists, but it has not been published yet.');
			}
			print('</p>');

			if($this->handler->authentication->user_session['UserAdmin.id']) {
				print('<p style="border-top:1px solid #999;padding-top:0.75em;">');
				print('<strong>Admin:</strong> set <code>Entry.Publish = 1</code>');
				if($entry['id']) {
					print(' for entry <code>' . (int)$entry['id'] . '</code>');
				}
				print(' to make this page live.');
				print('</p>');
			}

			print('<p><a href="/">Home</a></p>');
			print('</div>');

			return TRUE;
		}

		public function Display($args) {
			if($this->handler->unavailable_entry) {
				return $this->DisplayUnavailableEntry($args);
			}

			print("\n\n<BR><BR>I'm sorry, we didn't find that information for you!  We're going to try to help you now!  Redirecting in 5 seconds...");
			
			print('<script type="text/javascript">');
			print('window.setTimeout(function(){');
			print('window.location.href = "/";');
			print('}, 5000);');
			print('</script>');
			
			print('<BR><BR>If you do not see anything in five seconds, click here please: <a href="/">Home Directory/</a>.');
			
			return TRUE;
		}
	}

?>