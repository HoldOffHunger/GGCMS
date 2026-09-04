<?php

	/*
		The installed bootstrap for a checkout.

		Every CLI script begins by requiring /var/www/ggcms_cli_directories.php
		and /var/www/ggcms_install_directories.php, which define six absolute
		paths belonging to an installed copy of GGCMS.  That is right on a
		server and impossible anywhere else: a workstation has the two
		repositories and nothing else, and installing the engine on it only to
		run one tool leaves behind exactly the litter the tool was written to
		avoid.

		So this defines the same constants from the checkout it lives in.  A
		script requires this OR the installed pair, never both, and afterwards
		cannot tell the difference.

		Two things cannot be derived and must be given:

		  GGCMS_CONFIG_REPO   the private configuration repository, which is a
		                      separate checkout -- the engine is public and the
		                      configuration is not
		  GGCMS_WORK_DIR      somewhere to put logs and generated data, since a
		                      checkout has no /var and should not grow one

		Both are read from the environment.  Missing or wrong, this stops
		rather than guessing: a render performed against the wrong
		configuration produces pages that look entirely correct and are wrong
		on every line, which is worse than not running at all.
	*/

	$ggcms_repo_root = dirname(__DIR__, 5);

	if(!is_dir($ggcms_repo_root . '/usr/lib/ggcms/src')) {
		fwrite(STDERR, "RepoDirectories.php is not where it thinks it is: no engine found at " . $ggcms_repo_root . "/usr/lib/ggcms/src\n");
		exit(1);
	}

	$ggcms_config_repo = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', (string) getenv('GGCMS_CONFIG_REPO')), '/');

	if(!strlen($ggcms_config_repo)) {
		fwrite(STDERR, "GGCMS_CONFIG_REPO is not set.  It must name the checkout of the private configuration repository, the one holding etc/ggcms/.\n");
		exit(1);
	}

	if(!is_dir($ggcms_config_repo . '/etc/ggcms')) {
		fwrite(STDERR, "GGCMS_CONFIG_REPO does not hold etc/ggcms/: " . $ggcms_config_repo . "\n");
		exit(1);
	}

	$ggcms_work_dir = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', (string) getenv('GGCMS_WORK_DIR')), '/');

	if(!strlen($ggcms_work_dir)) {
		fwrite(STDERR, "GGCMS_WORK_DIR is not set.  It must name a writable directory for logs and generated data; nothing is written inside the checkout.\n");
		exit(1);
	}

	foreach([$ggcms_work_dir, $ggcms_work_dir . '/log', $ggcms_work_dir . '/data'] as $ggcms_required_directory) {
		if(!is_dir($ggcms_required_directory) && !@mkdir($ggcms_required_directory, 0755, TRUE)) {
			fwrite(STDERR, "cannot create " . $ggcms_required_directory . "\n");
			exit(1);
		}
	}

	define('GGCMS_DIR',        $ggcms_repo_root . '/usr/lib/ggcms/src/');
	define('GGCMS_DEP_DIR',    $ggcms_repo_root . '/usr/lib/ggcms/dep/');
	define('GGCMS_CLI_DIR',    $ggcms_repo_root . '/usr/lib/ggcms/cli/');
	define('GGCMS_DOC_ROOT',   $ggcms_repo_root . '/var/www/html/');
	define('GGCMS_CONFIG_DIR', $ggcms_config_repo . '/etc/ggcms/');
	define('GGCMS_LOG_DIR',    $ggcms_work_dir . '/log/');
	define('GGCMS_DATA_DIR',   $ggcms_work_dir . '/data/');

		/*
			The same value the installed bootstrap defines.  It is a property of
			the engine rather than of the installation -- clonefrom.com is the
			site this engine clones from -- so it is stated here rather than
			asked for.
		*/

	define('GGCMS_REFERENCE_DOMAIN', 'clonefrom.com');

?>
