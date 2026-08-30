<?php

	class module_divider extends module_spacing {
		public function displaystart ($args) {
			print('<div');
			
			if($args['id'])
			{
				print(' id="' . $args['id'] . '"');
			}
			
			if($args['class'])
			{
				print(' class="' . $args['class'] . '"');
			}
			
			if($args['title'])
			{
				print(' title="' . $args['title'] . '"');
			}
			
			print('>');
			
			$this->DisplayDoubleLineBreak();
		}
		
		public function displayend ($args) {
			print('</div>');
			
			$this->DisplayDoubleLineBreak();
		}
	}
?>