<?php

	define('GGCMS_DIR', '/usr/lib/ggcms/src/');
	define('GGCMS_DEP_DIR', '/usr/lib/ggcms/dep/');
	define('GGCMS_LOG_DIR', '/var/log/ggcms/');
	define('GGCMS_DATA_DIR', '/srv/ggcms/');
	define('GGCMS_CONFIG_DIR', '/etc/ggcms/');
	define('GGCMS_DOC_ROOT', '/var/www/html/');

		#  The reference installation -- the site this engine clones from, and
		#  the only domain that is not one of the live ones.  It is compared
		#  against in eight places and used to build a path in three more, and
		#  it was written out as a literal in all eleven.
		#
		#  It belongs here because it is the same kind of fact as the paths
		#  above: a property of how this copy of GGCMS was installed, not of
		#  any request, and needed by classes that hold a Domain and by classes
		#  that hold only globals.
	define('GGCMS_REFERENCE_DOMAIN', 'clonefrom.com');
	
?>