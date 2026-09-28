<?php

		/*
			The discussion under an entry: the form, then the comments.

			The ids are google-comments.js's and like-dislike.js's: the form
			is comment-form, its fields Username and Comments, its button
			submit; error-box and validation-error-message report a form
			sent short; userid, logout, google_token_id and likeordislike
			are the hidden state the two scripts read.

			Signing in happens on the sign-in page now -- Google's script is
			only loaded there -- so a reader who is signed out is sent there
			rather than shown a button that cannot draw.
		*/

	class module_entrycomments extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function BackToTopLinkBox() {
			print('<a class="to-top" href="#top">Back to top</a>');

			return TRUE;
		}

		public function DisplayHeader() {
			print('<h2 class="block-title">Discussion</h2>');

			return TRUE;
		}

		public function Display() {
			print('<section class="block discussion" id="comments">');

			$this->DisplayHeader();

			if(($_SERVER['HTTPS'] ?? '') === 'on') {
				$this->DisplayForm();
			} else {
				print('<div class="discussion-cta">');
				print('<p>Sign in to join the discussion.</p>');
				print('<a class="btn btn-line" href="' . $this->that->handler->domain->GetPrimaryDomain(['secure'=>1, 'lowercase'=>0, 'www'=>1]) . '/login.php" rel="nofollow">Sign in</a>');
				print('</div>');
			}

			print('<input type="hidden" name="userid" id="userid" class="userid" value="' . $this->that->handler->authentication->user_session['User.id'] . '">' . "\n\n");
			print('<input type="hidden" name="logout" id="logout" class="logout" value="' . htmlspecialchars($this->that->Param('logout'), ENT_QUOTES, 'UTF-8') . '">' . "\n\n");

			if($this->that->user_likedislike && $this->that->user_likedislike['id']) {
				print('<input type="hidden" id="likeordislike" class="likeordislike" name="likeordislike" value="');
				print($this->that->user_likedislike['LikeOrDislike']);
				print('">');
			}

			$this->DisplayComments();

			print('</section>');

			return TRUE;
		}

		public function DisplayForm() {
			print('<form action="view.php#comments" method="POST" id="comment-form" class="comment-form">');

			if(!$this->that->handler->authentication->user_session) {
				print('<div class="discussion-cta">');
				print('<p>Sign in to join the discussion, and to upvote the texts that helped you.</p>');
				print('<a class="btn btn-line" href="/login.php" rel="nofollow">Sign in</a>');
				print('</div>');

				print('<input type="hidden" name="google_token_id" id="google_token_id" class="google_token_id">');
				print('<input type="submit" id="submit" name="Comment" value="Comment" hidden>');
				print('</form>');

				return TRUE;
			}

			if($this->that->username_record_conflict) {
				print('<div class="notice notice-error">');
				print('<p>That username is already taken:</p>');
				print('<p><em>' . str_replace("\n", "<br>\n", strip_tags($this->that->Param('Username'))) . '</em></p>');
				print('</div>');
			}

			if($this->that->comment_results) {
				print('<div class="notice notice-success">');
				print('<p>Thank you for your comment! It will appear here once it has been reviewed.</p>');
				print('<p><em>' . str_replace("\n", "<br>\n", strip_tags($this->that->comment_results['Comment'])) . '</em></p>');
				print('</div>');
			}

			print('<div id="error-box" class="notice notice-error" hidden>');
			print('<p id="validation-error-message"></p>');
			print('</div>');

			if(!$this->that->handler->authentication->user_session['User.Username']) {
				print('<p class="field">');
				print('<label for="Username">Username</label>');
				print('<input id="Username" class="Username" name="Username" type="text" required>');
				print('<span class="field-hint">Shown with your comments. Your email address never is.</span>');
				print('</p>');
			}

			$comment_value = '';

			if($this->that->username_record_conflict) {
				$comment_value = $this->that->Param('Comments');
			}

			print('<p class="field">');
			print('<label for="Comments">Your comment</label>');
			print('<textarea id="Comments" name="Comments" rows="6" required>' . htmlspecialchars($comment_value, ENT_QUOTES, 'UTF-8') . '</textarea>');
			print('</p>');

			print('<p class="form-actions">');
			print('<button type="submit" id="submit" name="Comment" value="Comment" class="btn btn-primary">Post comment</button>');
			print('<span class="field-hint">Comments are read before they appear.</span>');
			print('</p>');

			print('<input type="hidden" name="google_token_id" id="google_token_id" class="google_token_id">');
			print('</form>');

			return TRUE;
		}

		public function DisplayComments() {
			if(!$this->that->comments || !is_array($this->that->comments) || $this->that->counts['comment'] === 0) {
				print('<p class="empty">No comments yet. Yours could be the first.</p>');

				return TRUE;
			}

			print('<ol class="comment-list">');

			foreach($this->that->comments as $comment) {
				$date_epoch_time = strtotime($comment['OriginalCreationDate']);

				print('<li class="comment">');
				print('<p class="comment-meta">');
				print('<a class="comment-author" href="/users.php?action=viewuser&amp;user=' . urlencode($comment['user']['Username']) . '">' . $comment['user']['Username'] . '</a>');
				print(' <time datetime="' . date('c', $date_epoch_time) . '">' . date("F j, Y", $date_epoch_time) . '</time>');
				print('</p>');

				$comments_text = strip_tags($comment['Comment']);
				$comments_text = $this->that->HyperlinkizeText(['text'=>$comments_text]);
				$comments_text = str_replace("\n", "<br>\n", $comments_text);

				print('<div class="comment-body">' . $comments_text . '</div>');
				print('</li>');
			}

			print('</ol>');

			return TRUE;
		}

		public function DisplayLinkBox() {
			print('<a class="action" href="#comments">');
			print('Discuss');

			if($this->that->counts['comment']) {
				print('<span class="action-count">' . number_format($this->that->counts['comment']) . '</span>');
			}

			print('</a>');

			return TRUE;
		}

		public function DisplayLikeDislike() {
			return FALSE;
		}
	}

?>
