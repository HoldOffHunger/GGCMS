<?php

	class module_entrycomments extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			
			return $this;
		}
		
		public function BackToTopLinkBox() {
			print('<div style="margin-right:50px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray13 float-right">');
			print('<span class="comments-link-box" style="font-family:arial, tahoma;margin:3px;padding:0px;display:inline-block;">');
			print('<strong>');
			
			print('<a href="#top">');
			print('<nobr>');
			print('Back to Top');
			print('</nobr>');
			print('</a>');
			
			print('</strong>');
			print('</span>');
			print('</div>');
			
			return TRUE;
		}
		
		public function DisplayHeader() {
					// Comments Header
				
				// -------------------------------------------------------------
				
			print('<a name="comments"></a>');
			
			print('<center>');
			print('<div class="horizontal-center width-95percent">');
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<h2 class="horizontal-left margin-5px font-family-arial">');
			print('Comments');
			print('</h2>');
			print('</div>');
			print('</div>');
			print('</center>');
			
			$this->BackToTopLinkBox();
			
			return TRUE;
		}
		
		public function Display() {
			$this->DisplayHeader();
			
					// Finish Textbody Header
				
				// -------------------------------------------------------------
									
			print('<div class="clear-float"></div>');
			
					// Display Comment Form
				
				// -------------------------------------------------------------
				
			if($_SERVER['HTTPS'] === 'on') {
				print('<form action="view.php#comments" method="POST" id="comment-form" class="margin-0px">');
				
				if($this->that->handler->authentication->user_session) {
					print('<center>');
					
					if($this->that->username_record_conflict) {
						print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-70percent font-family-tahoma">');
						
						$username = '';
						
						if($this->that->handler->authentication->user_session['User.Username']) {
							$username = $this->that->handler->authentication->user_session['User.Username'];
						} else {
							$username = $this->that->handler->authentication->user_session['User.EmailAddress'];
						}
						
						print('<p class="margin-10px horizontal-left">We\'re sorry.  The following username is already taken :<br><br><em>' . str_replace("\n", "<br>\n", strip_tags($this->that->Param('Username'))) . '</em></p>');
						
						print('</div>');
					}
					
					if($this->that->comment_results) {
						print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-70percent font-family-tahoma">');
						
						$username = '';
						
						if($this->that->handler->authentication->user_session['User.Username']) {
							$username = $this->that->handler->authentication->user_session['User.Username'];
						} else {
							$username = $this->that->handler->authentication->user_session['User.EmailAddress'];
						}
						
						print('<p class="margin-10px horizontal-left">Thank you for your comment!  The following was received and will be published after review.<br><br><em>' . str_replace("\n", "<br>\n", strip_tags($this->that->comment_results['Comment'])) . '</em></p>');
						
						print('</div>');
					}
					print('<div id="error-box" class="border-2px background-color-gray13 margin-5px horizontal-center width-70percent font-family-tahoma" style="display:none;">');
					
					print('<p id="validation-error-message" class="margin-10px horizontal-left"></p>');
					
					print('</div>');
					
					$this->DisplayLikeDislike();
					
							// Finish Textbody Header
						
						// -------------------------------------------------------------
											
					print('<div class="clear-float"></div>');
			
					print('<div class="width-90percent">');
					print('<div class="border-2px background-color-gray13 margin-5px font-family-tahoma float-left">');
					
					if(!$this->that->handler->authentication->user_session['User.Username']) {
						print('<p class="margin-5px horizontal-left"><b>Username : </b>');
						print('<input id="Username" class="Username" name="Username" type="text" size="80">');
						print(' <span title="This will be viewable to the public, unlike your E-mail Address.">(Publicly Viewable)</span> <span style="color:FF0000;vertical-align:top;margin:10px;">*</span>');
						print('</p>');
					}
					
					print('<nobr>');
					
					$comment_value = '';
					
					if($this->that->username_record_conflict) {
						$comment_value = $this->that->Param('Comments');
					}
					
					print('<textarea id="Comments" name="Comments" cols="120" rows="10">' . htmlspecialchars($comment_value, ENT_QUOTES, 'UTF-8') . '</textarea>');
					
					print(' <span style="color:FF0000;vertical-align:top;margin:10px;">*</span>');
					print('</nobr>');
					
					print('<br>');
					
					print('<input type="submit" id="submit" name="Comment" value="Comment" >');

					
					print('</div>');
					print('</div>');
					print('</center>');
					
							// Finish Textbody Header
						
						// -------------------------------------------------------------
											
					print('<div class="clear-float"></div>');
					
					print('<input type="hidden" name="google_token_id" id="google_token_id" class="google_token_id">');		
				} else {
					print('<center>');
					print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-50percent font-family-tahoma">');
					print('<p>Login through Google to Comment or Like/Dislike :</p>');
					
					print('<div class="horizontal-center width-50percent margin-top-5px margin-bottom-5px">');
					print('<div class="g-signin2" data-onsuccess="onSignIn"></div>');
					print('</div>');
					
					print('<input type="hidden" name="google_token_id" id="google_token_id" class="google_token_id">');
					print('<div style="display:none;">');
					print('<input type="submit" id="submit" name="Comment" value="Comment" >');
					print('</div>');
					
					print('</div>');
					print('</center>');
					
							// Finish Textbody Header
						
						// -------------------------------------------------------------
											
					print('<div class="clear-float"></div>');
				}
				
				print('</form>');
			} else {		
				print('<center>');
				print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-50percent font-family-tahoma">');
				
				$new_url = str_replace('/view.php', '/view.php#comments', $_SERVER['SCRIPT_URL']);
				print('<p><b><a href="' . $this->that->handler->domain->GetPrimaryDomain(['secure'=>1, 'lowercase'=>0, 'www'=>1]) . $new_url . '">Login to Comment</a></b></p>');
				
				print('</div>');
				print('</center>');
				
						// Display Likes/Dislikes
					
					// -------------------------------------------------------------
				
				$like_mouseover_value = '';
				$cursor_class = '';
				
				if($_SERVER['HTTPS'] !== 'on' || !$this->that->handler->authentication->user_session) {
					$like_mouseover_value = 'You must login before you are allowed to upvote or downvote.';
				} else {
					$like_mouseover_value = 'Let your feelings be known!  Like or dislike this here.';
					$cursor_class = 'cursor-pointer';
				}
				
				print('<center>');
				print('<div class="horizontal-center width-70percent" title="' . $like_mouseover_value . '">');
				
				print('<div class="border-2px background-color-gray15 margin-5px float-left">');
				print('<div class="border-2px background-color-gray15 margin-5px float-left');
				if($cursor_class) {
					print(' ' . $cursor_class);
				}
				print('">');
				print('<div class="height-100px width-100px background-color-gray15">');
				print('<div class="vertical-specialcenter">');
				print('<img width="90" height="90" src="');
				print($this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
				print('/image/');
				print('thumbs-up-right.jpg');
				print('"');
				print('>');
				print('</div>');
				print('</div>');
				print('<span id="total-dislikes" class="font-family-tahoma"><nobr>');
				print(number_format($this->that->likes_count));
				print(' Likes</nobr></span>');
				print('</div>');
				print('</div>');
				
				print('<div class="border-2px background-color-gray15 margin-5px float-left">');
				print('<div class="border-2px background-color-gray15 margin-5px float-left');
				if($cursor_class) {
					print(' ' . $cursor_class);
				}
				print('">');
				print('<div class="height-100px width-100px background-color-gray15">');
				print('<div class="vertical-specialcenter">');
				print('<img width="90" height="90" src="');
				print($this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
				print('/image/');
				print('thumbs-down-right.jpg');
				print('"');
				print('>');
				print('</div>');
				print('</div>');
				print('<span id="total-dislikes" class="font-family-tahoma"><nobr>');
				print(number_format($this->that->dislikes_count));
				print(' Dislikes');
				print('</nobr></span>');
				print('</div>');
				print('</div>');
				
				print('<div class="border-2px background-color-gray15 margin-5px float-right" title="Click here to see users who liked this.">');
				print('<div class="background-color-gray15 margin-5px float-left');
				print('<span class="font-family-tahoma">');
				print('<a href="view.php?action=viewUserLikes" class="font-family-tahoma">');
				print('Who Liked This?');
				print('</a>');
				print('</span>');
				print('</div>');
				print('</div>');
				
				print('</div>');
				print('</center>');
					
							// Finish Textbody Header
						
						// -------------------------------------------------------------
											
					print('<div class="clear-float"></div>');
			}
			
			print('<input type="hidden" name="userid" id="userid" class="userid" value="' . $this->that->handler->authentication->user_session['User.id'] . '">' . "\n\n");
			print('<input type="hidden" name="usersessionid" id="usersessionid" class="usersessionid" value="' . $this->that->handler->authentication->user_session['CookieToken'] . '">' . "\n\n");
			print('<input type="hidden" name="logout" id="logout" class="logout" value="' . htmlspecialchars($this->that->Param('logout'), ENT_QUOTES, 'UTF-8') . '">' . "\n\n");
			
			if($this->that->user_likedislike && $this->that->user_likedislike['id']) {
				print('<input type="hidden" id="likeordislike" class="likeordislike" name="likeordislike" value="');
				print($this->that->user_likedislike['LikeOrDislike']);
				print('">');
			}
			
					// Display Comments
				
				// -------------------------------------------------------------
			
			print('<center>');
			
			if($this->that->comments && is_array($this->that->comments) && $this->that->counts['comment'] !== 0) {
				print('<div class="horizontal-center width-80percent">');
				print('<div class="border-2px background-color-gray13 margin-5px horizontal-left width-50percent font-family-tahoma">');
				print('<p class="margin-5px"><nobr>Total Comments : ' . count($this->that->comments) . '</nobr></p>');
				print('</div>');
				print('</div>');
				
				foreach($this->that->comments as $comment) {
					print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-90percent font-family-tahoma">');
					print('<div class="horizontal-left margin-5px">');
					
					print('<div class="border-2px background-color-gray15 margin-5px float-left">');
					print('<p class="horizontal-left margin-5px font-family-arial">');
					print('Posted By : ');
					print('<a href="' . $this->that->handler->domain->GetPrimaryDomain(['lowercase'=>0, 'www'=>1]) . '/users.php?action=viewuser&user=' . urlencode($comment['user']['Username']) . '">');
					print($comment['user']['Username']);
					print('</a>');
					print('</p>');
					print('</div>');
					
					print('<div class="border-2px background-color-gray15 margin-5px float-right">');
					print('<p class="horizontal-right margin-5px font-family-arial">');
					print('Original Post Date : ');
					$date_epoch_time = strtotime($comment['OriginalCreationDate']);
					$full_date = date("F d, Y; H:i:s", $date_epoch_time);
					print($full_date);
					print('</p>');
					print('</div>');
					
					print('<div class="clear-float"></div>');
					
					$comments_text = strip_tags($comment['Comment']);	
					$comments_text = $this->that->HyperlinkizeText(['text'=>$comments_text]);
					$comments_text = str_replace("\n", "<BR>\n", $comments_text);
					print($comments_text);
					
					print('</div>');
					print('</div>');
				}
			} else {
				print('<div class="border-2px background-color-gray13 margin-5px horizontal-center width-90percent font-family-tahoma">');
				print('<div class="horizontal-left margin-5px">');
				print('<p class="margin-0px"><b>No comments so far.  You can be the first!</b></p>');
				print('</div>');
				print('</div>');
			}
			
			print('</center>');
		}
		
		public function DisplayLinkBox() {
			print('<div style="margin-right:5px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray15 float-right">');
			print('<span class="comments-link-box" style="font-family:arial, tahoma;margin:3px;padding:0px;display:inline-block;">');
			print('<strong>');
			
			print('<a href="#comments">');
			print('Comments (');
			print(number_format($this->that->counts['comment']));
			print(')');
			print('</a>');
			
			print('</strong>');
			print('</span>');
			print('</div>');
			
			return TRUE;
		}
		
		public function DisplayLikeDislike() {
			return FALSE;
					// Display Likes/Dislikes
				
				// -------------------------------------------------------------
			
			$like_mouseover_value = '';
			$cursor_class = '';
			
			if($_SERVER['HTTPS'] !== 'on' || !$this->that->handler->authentication->user_session) {
				$like_mouseover_value = 'You must login before you are allowed to upvote or downvote.';
			} else {
				$like_mouseover_value = 'Let your feelings be known!  Like or dislike this here.';
				$cursor_class = 'cursor-pointer';
			}
			
			print('<div class="horizontal-center width-70percent" title="' . $like_mouseover_value . '">');
			
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<div id="thumbs-up-button-container" class="border-2px background-color-gray15 margin-5px float-left');
			if($cursor_class) {
				print(' ' . $cursor_class);
			}
			print('">');
			print('<div class="height-100px width-100px background-color-gray15">');
			print('<div class="vertical-specialcenter">');
			print('<img id="thumbs-up-button" width="90" height="90" src="');
			print($this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
			print('/image/');
			print('thumbs-up-right.jpg');
			print('"');
			print('>');
			print('</div>');
			print('</div>');
			print('<nobr><span id="total-likes" class="font-family-tahoma">');
			print(number_format($this->that->likes_count));
			print('</span>');
			print('<span class="font-family-tahoma"> Likes</span>');
			print('</nobr>');
			print('</div>');
			print('</div>');
			
			print('<div class="border-2px background-color-gray15 margin-5px float-left">');
			print('<div id="thumbs-down-button-container" class="border-2px background-color-gray15 margin-5px float-left');
			if($cursor_class) {
				print(' ' . $cursor_class);
			}
			print('">');
			print('<div class="height-100px width-100px background-color-gray15">');
			print('<div class="vertical-specialcenter">');
			print('<img id="thumbs-down-button" width="90" height="90" src="');
			print($this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
			print('/image/');
			print('thumbs-down-right.jpg');
			print('"');
			print('>');
			print('</div>');
			print('</div>');
			print('<nobr>');
			print('<span id="total-dislikes" class="font-family-tahoma">');
			print(number_format($this->that->dislikes_count));
			print('</span>');
			print('<span class="font-family-tahoma"> Dislikes</span>');
			print('</nobr>');
			print('</div>');
			print('</div>');
			
			print('<div class="border-2px background-color-gray15 margin-5px float-right" title="Click here to see users who liked this.">');
			print('<div class="background-color-gray15 margin-5px float-left');
			print('<span class="font-family-tahoma">');
			print('<a href="view.php?action=viewUserLikes" class="font-family-tahoma">');
			print('Who Liked This?');
			print('</a>');
			print('</span>');
			print('</div>');
			print('</div>');
			
			print('</div>');
			
			return TRUE;
		}
	}

?>