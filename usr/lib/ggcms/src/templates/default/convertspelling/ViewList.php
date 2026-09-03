<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreqonce('modules/spacing.php');
	
	ggreqonce('modules/html/text.php');
	$text = new module_text;
	
	ggreqonce('modules/html/form.php');
	$form = new module_form;
	
	ggreqonce('modules/html/divider.php');
	$divider = new module_divider;
	
	ggreqonce('modules/html/table.php');
	$table = new module_table;
	
	ggreqonce('modules/html/list/generic.php');
	$generic_list = new module_genericlist;
	
			// Basic Divider Arguments
		
		// -------------------------------------------------------------
	
	$divider_padding_start_args = [
		'class'=>'',
	];
	
	$divider_end_args = [
	];
	
			// Display Header
		
		// -------------------------------------------------------------
	
	require(GGCMS_DIR . 'modules/html/entry-header.php');
	require(GGCMS_DIR . 'modules/html/entry-index-header.php');
	$entryheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->header_title_text,
		'sub_text'=>$sub_text,
		'sub_title'=>$sub_title,
	]);
	
	$entryheader->Display();
	
			// Display List Items
		
		// -------------------------------------------------------------

				// Start Form
			
			// -------------------------------------------------------------
			
	print('<div style="width:70%;text-align:left;margin:auto;margin-top:5px;margin-bottom:5px;">');
	print('<a href="./convertspelling.php">');
	print('&bull; Back to American/British Spelling Converter');
	print('</a>');
	print('</div>');
	
	print('<div style="width:80%;text-align:left;margin:auto;margin-top:5px;margin-bottom:5px;">');
	
	print('<STYLE>table, tr, th, td { border: solid 1px black; }</STYLE>');
	
	print('<table>');
	
	print('<tr>');
	print('<th><strong>American</strong></th>');
	print('<th><strong>English</strong></th>');
	print('</tr>');
	
	foreach($this->wordlist as $american => $english) {
		print('<tr>');
		print('<td>');
		print($american);
		print('</td>');
		print('<td>');
		if(is_array($english)) {
			print(implode(', ', $english));
		} else {
			print($english);
		}
		print('</td>');
		print('</tr>');
	}
	
	print('<table>');
/*	
	print('<PRE>');
	print_r($this->wordlist);
	print('</PRE>');
*/	
	print('</div>');	
?>