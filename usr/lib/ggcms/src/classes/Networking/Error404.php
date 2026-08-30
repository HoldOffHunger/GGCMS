<?php

	class Error404 {
		public function __construct($args) {
			$this->handler = $args['handler'];
			
			return $this;
		}
		
		public function Display($args) {
			print("\n\n<BR><BR>I'm sorry, we didn't find that information for you!  We're going to try to help you now!  Redirecting in 5 seconds...");
			
			print('<script type="text/javascript">');
			print('window.setTimeout(function(){');
			print('window.location.href = "/";');
			print('}, 5000);');
			print('</script>');
			
			print('<BR><BR>If you do not see anything in five seconds, click here please: <a href="/">Home Directory/<a>.');
			
			return TRUE;
		}
	}

?>