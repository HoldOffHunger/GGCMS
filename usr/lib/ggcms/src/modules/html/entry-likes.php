<?php

		/*
			Upvote and downvote.  The ids are like-dislike.js's: it binds the
			two -button-container elements, counts in total-likes and
			total-dislikes, and marks a vote with is-on.

			Signed out, a vote cannot be cast, so the buttons are links to
			the sign-in page instead of controls that do nothing.  A count of
			nought is kept in the page for the script to raise, and hidden:
			a row of zeroes reads as a deserted room.
		*/

	class module_entrylikes extends module_spacing {
		public $that;
		public $like_mouseover_value;
		public $signed_in;

		public function __construct($args) {
			$this->that = $args['that'];

			$this->signed_in = ($_SERVER['HTTPS'] ?? '') === 'on' && $this->that->handler->authentication->user_session;

			if(!$this->signed_in) {
				$this->like_mouseover_value = 'Sign in to upvote or downvote.';
			} else {
				$this->like_mouseover_value = 'Let your feelings be known!  Like or dislike this here.';
			}
		}

		public function Display() {
			print('<div class="votes" title="' . htmlentities($this->like_mouseover_value) . '">');

			$this->DisplayVote([
				'id'=>'thumbs-up',
				'label'=>'Upvote',
				'count'=>$this->that->likes_count,
				'spanid'=>'total-likes',
				'path'=>'M12 4l7 8h-4v8H9v-8H5z',
			]);

			$this->DisplayVote([
				'id'=>'thumbs-down',
				'label'=>'Downvote',
				'count'=>$this->that->dislikes_count,
				'spanid'=>'total-dislikes',
				'path'=>'M12 20l-7-8h4V4h6v8h4z',
			]);

			print('</div>');

			return TRUE;
		}

		public function DisplayVote($args) {
			$count = (int) $args['count'];

			if($this->signed_in) {
				print('<button type="button" id="' . $args['id'] . '-button-container" class="vote ' . $args['id'] . '">');
			} else {
				print('<a id="' . $args['id'] . '-button-container" class="vote ' . $args['id'] . '" href="/login.php" rel="nofollow">');
			}

			print('<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="' . $args['path'] . '"/></svg>');
			print('<span class="vote-label">' . $args['label'] . '</span>');
			print('<span id="' . $args['spanid'] . '" class="vote-count' . ($count ? '' : ' is-zero') . '">' . number_format($count) . '</span>');

			print($this->signed_in ? '</button>' : '</a>');

			return TRUE;
		}
	}

?>
