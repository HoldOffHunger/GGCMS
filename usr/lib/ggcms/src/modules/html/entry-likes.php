<?php

	class module_entrylikes extends module_spacing {
		public function __construct($args) {
			$this->that = $args['that'];
			
			if($_SERVER['HTTPS'] != 'on' || !$this->that->handler->authentication->user_session) {
				$this->like_mouseover_value = 'You must login before you are allowed to upvote or downvote.';
				$this->cursor_class = '';
			} else {
				$this->like_mouseover_value = 'Let your feelings be known!  Like or dislike this here.';
				$this->cursor_class = 'cursor-pointer';
			}
		}
		
		public function Display() {
			$this->DisplayImage([
				'image'=>'thumbs-down-right.jpg',
				'id'=>'thumbs-down',
				'count'=>$this->that->dislikes_count,
				'spanid'=>'total-dislikes',
			]);
			
			$this->DisplayImage([
				'image'=>'thumbs-up-right.jpg',
				'id'=>'thumbs-up',
				'count'=>$this->that->likes_count,
				'spanid'=>'total-likes',
			]);
			
			return TRUE;
		}
		
		public function DisplayImage($args) {
			$image = $args['image'];
			$id = $args['id'];
			$spanid = $args['spanid'];
			$count = $args['count'];
			
			print('<div id="' . $id . '-button-container" ');
			print('title="' . htmlentities($this->like_mouseover_value) . '" ');
			print('style="margin-right:5px;white-space:nowrap;display: inline-block" class="border-2px background-color-gray15 float-right');
			if($this->cursor_class) {
				print(' ' . $this->cursor_class);
			}
			print('">');
			print('<div style="display: inline-block" class="background-color-gray15">');
			
			print('<table>');
			print('<tr><td>');
			
			print('<img id="' . $id . '-button" width="15" height="15" style="margin:0px;" src="');
			print($this->that->handler->domain->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]));
			print('/image/');
			print($image);
			print('"');
			print('>');
			
			print('</td><td>');
			
			print('<span id="' . $spanid . '" style="font-family:arial, tahoma;margin-right:1px;margin-left:0px;margin-top:0px;margin-bottom:0px;padding:0px;display:inline-block;">');
			print(number_format($count));
			print('</span>');
			
			print('</td></tr></table>');
			
			print('</div>');
			print('</div>');
			
			return TRUE;
		}
	}

?>