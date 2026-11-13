#!/usr/bin/php
<?php

	/*
		Names a new child table in every file of both repositories that has
		to name it.  See ChildTableAdder.  Says what it would do unless given
		--apply.

		It edits source, so it runs from a checkout and nowhere else, and
		needs the private configuration checkout beside it:

			GGCMS_CONFIG_REPO=/path/to/GreenGluonCMS_Unhireable \
			php add_child_table.php --table=WordPiece --columns="Language:varchar(32),Position:int,Piece:varchar(255)"

		It reads no configuration and touches no database.
	*/

	$ggcms_repo_root = dirname(__DIR__, 7);
	$ggcms_config_repo = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', (string) getenv('GGCMS_CONFIG_REPO')), '/');

	if(!file_exists($ggcms_repo_root . '/.git') || !is_dir($ggcms_repo_root . '/usr/lib/ggcms/src')) {
		fwrite(STDERR, "add_child_table.php edits the engine's source, so it runs from a checkout: none found at " . $ggcms_repo_root . "\n");
		exit(1);
	}

	if(!strlen($ggcms_config_repo) || !is_dir($ggcms_config_repo . '/etc/ggcms/clonefrom')) {
		fwrite(STDERR, "GGCMS_CONFIG_REPO must name the checkout of the private configuration repository, the one holding etc/ggcms/clonefrom/.\n");
		exit(1);
	}

	require(__DIR__ . '/../../../classes/Database/ChildTableAdder.php');

	$child_table_adder = new ChildTableAdder([
		'argv'=>$argv,
		'publicroot'=>$ggcms_repo_root,
		'privateroot'=>$ggcms_config_repo,
	]);

	exit($child_table_adder->add() === FALSE ? 1 : 0);

?>
