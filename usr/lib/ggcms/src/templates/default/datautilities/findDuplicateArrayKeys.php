<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	require(GGCMS_DIR . 'modules/spacing.php');
	
	require(GGCMS_DIR . 'modules/html/text.php');
	$text = new module_text;
	
	require(GGCMS_DIR . 'modules/html/form.php');
	$form = new module_form;
	
	require(GGCMS_DIR . 'modules/html/divider.php');
	$divider = new module_divider;
	
	require(GGCMS_DIR . 'modules/html/table.php');
	$table = new module_table;
	
	require(GGCMS_DIR . 'modules/html/list/generic.php');
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
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->domain_object->primary_domain . ' British/American Spelling converter',
		'sub_text'=>'Convert British or American Spellings',
	]);
	
	$entryindexheader->Display();
	
			// Display List Items
		
		// -------------------------------------------------------------

				// Start Form
			
			// -------------------------------------------------------------
	
#	print('<center style="margin-top:5px;"><img src="/image/convert-spelling/us-uk-spelling-converter-logo.jpg" width="600" style="border:2px solid black;"></center>');
	
	print('<form METHOD="POST">');
	
	print('<div style="width:80%;text-align:left;margin:auto;border:1px solid black; margin-top:5px;margin-bottom:5px;">');

				// Display Instructions
			
			// -------------------------------------------------------------
	
	print('<p class="font-family-arial">Detect duplicate keys in your predefined arrays in PHP.</p>');
	
	print('<p class="font-family-arial"><strong>Note:</strong> There is a 100,000-character limit to this form.  If you attempt to insert input beyond this, nothing additional will appear in the text box.</p>');

				// Direction
			
			// -------------------------------------------------------------
	
	print('</div>');

				// Text Area
			
			// -------------------------------------------------------------
	
	print('<center>');
	print('<textarea name="text" cols="75" rows="20" maxlength="100000">');
	
	if($this->text) {
		print($this->text);
	} else {
		print('     // this is a mere example, put anything you\'d like here...' . "\n");
		print("\n");
		print('$array = [' . "\n");
		print('    \'hello1\'=>123,' . "\n");
		print('    \'hello1\'=>456,' . "\n");
		print('    \'hello2\'=>789' . "\n");
		print('];');
	}
	
	print('</textarea>');
	print('<br>');
	print('<input type="submit" value="Detect Duplicates" style="margin-top:5px;">');
	
	print('</center>');

				// Output
			
			// -------------------------------------------------------------
	
	print('<div style="width:80%;text-align:left;margin:auto;border:1px solid black; margin-top:5px;margin-bottom:5px;">');
	print('<p class="font-family-arial"><h2 style="font-family:tahoma;">Results :</h2></p>');
	
	print('<div class="font-family-arial">');
	
	if($this->text) {
		print('<p><i>Duplicates</i></p>');
		foreach($this->duplicates as $duplicate => $values) {
			print($this->CleanseForDisplay($duplicate));
			print( ' :: ');
			print('<ul>');
			foreach($values as $value) {
				print('<li>');
				print($this->CleanseForDisplay($value));
				print('</li>');
			}
			print('</ul>');
		}
	} else {
		print('<p><i>Results will appear here.</i></p>');
	}
	
	print('</div>');
	print('</div>');
	
				// End Form
			
			// -------------------------------------------------------------
	
	print('</form>');
	
?>