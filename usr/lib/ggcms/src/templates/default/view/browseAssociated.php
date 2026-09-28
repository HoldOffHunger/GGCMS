<?php
	print("<script type='text/javascript'>");
	print("
		function inIframe () {
			try {
				return window.self !== window.top;
			} catch (e) {
				return true;
			}
		}

		if(!inIframe()) {
			document.location = 'view.php#works';
		}
	");
	print("</script>");

			// Standard Requires

		// -------------------------------------------------------------

	ggreq('modules/spacing.php');

	ggreq('modules/html/entry-sort.php');
	$entrysort = new module_entrysort(['that'=>$this]);

	ggreq('modules/html/entry-list.php');
	$entrylist = new module_entrylist(['that'=>$this]);

	ggreq('modules/html/entry-list-navigation.php');
	$entrylistnavigation = new module_entrylistnavigation(['that'=>$this]);

	ggreq('modules/html/browse-bar.php');
	$browse_bar = new module_browsebar(['that'=>$this]);

		// the list's switches, carried from page to page

	$list_args = [
		'skip'=>$this->Param('ignore_parent'),
		'parents'=>$this->Param('parents'),
		'list_author'=>$this->Param('list_author'),
		'ignore_parent'=>$this->Param('ignore_parent'),
		'item_title'=>$this->Param('item_title'),
		'stats_prefix'=>$this->Param('stats_prefix'),
	];

	if($this->children_count !== 0) {

				// What This Person Wrote, in Numbers

			// -------------------------------------------------------------

		if($this->Param('ignore_parent')) {
			$valid_item_titles = [
				'quotes'=>true,
				'writings'=>true,
			];

			if(!empty($args['creation_type'])) {
				$creation_type_text = $args['creation_type'];
			} elseif($this->Param('item_title') && isset($valid_item_titles[$this->Param('item_title')])) {
				$creation_type_text = $this->Param('item_title');
			} else {
				$creation_type_text = 'documents';
			}

			$valid_stats_prefixes = [
				'This writing has'=>true,
			];

			if($this->Param('stats_prefix') && isset($valid_stats_prefixes[$this->Param('stats_prefix')])) {
				$stats_prefix = $this->Param('stats_prefix') . ' ';
			} else {
				$stats_prefix = 'This person has authored ';
			}

			$date_epoch_time = strtotime($this->associated_record_stats['LastModificationDate']);

			print('<p class="block page-wide browse-note" title="Last updated ' . date("F d, Y; H:i:s", $date_epoch_time) . '">');
			print($stats_prefix . '<strong>' . number_format($this->associated_record_stats['AssociatedRecordCount']) . '</strong> ' . $creation_type_text);

			if(
				$this->associated_record_stats['AssociatedWordCount'] > 0 ||
				$this->associated_record_stats['AssociatedCharacterCount'] > 0
			) {
				print(', with ' . number_format($this->associated_record_stats['AssociatedWordCount']) . ' words or ' . number_format($this->associated_record_stats['AssociatedCharacterCount']) . ' characters.');
			} else {
				print('.');
			}

			print('</p>');
		}

				// The List, a Page at a Time

			// -------------------------------------------------------------

		$browse_bar->Display();

		$entrylistnavigation->Display($list_args);

		print('<div class="block page-wide">');
		$entrylist->Display(['children'=>$entrysort->Sort(['entries'=>$this->children])] + $list_args);
		print('</div>');

		$entrylistnavigation->Display($list_args);
	} else {
		print('<p class="block page-wide empty">Nothing available yet!</p>');
	}

?>
