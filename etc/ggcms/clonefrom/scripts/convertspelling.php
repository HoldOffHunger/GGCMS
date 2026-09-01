<?php

	class AbstractGlobals_convertspelling {

			/*
				The spelling converter reads nothing out of the entry graph, so
				every depth beneath it is the same tool wearing another URL.  A
				canonical directory of '/' sends them all to the one address the
				site actually links to.

				Return an empty string to disable, which is what every script
				without this method already does.  A domain needing a rule rather
				than a constant overrides this method and reads $args['handler'].
			*/

		public function ForceCanonicalLink($args) {
			return '/';
		}
	}

?>