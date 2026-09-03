<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	classreq('modules/spacing.php');
	
	classreq('modules/html/text.php');
	$text = new module_text;
	
	classreq('modules/html/form.php');
	$form = new module_form;
	
	classreq('modules/html/divider.php');
	$divider = new module_divider;
	
	classreq('modules/html/table.php');
	$table = new module_table;
	
	classreq('modules/html/list/generic.php');
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
	
	classreq('modules/html/entry-header.php');
	classreq('modules/html/entry-index-header.php');
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
	
	print('<center style="margin-top:5px;"><img src="/image/convert-spelling/us-uk-spelling-converter-logo.jpg" width="600" style="border:2px solid black;"></center>');
	
	print('<form METHOD="POST">');
	
	print('<div style="width:80%;text-align:left;margin:auto;border:1px solid black; margin-top:5px;margin-bottom:5px;">');

				// Display Instructions
			
			// -------------------------------------------------------------
	
	print('<p class="font-family-arial">Convert UK spellings to American spellings, or vice versa.  Output will be displayed under the "Results" header below the text box.</p>');
	
	print('<p class="font-family-arial"><strong>Words Supported:</strong> ');
	
	$linkedwords = [];
	
	foreach(range('A', 'Z') as $char) {
		$linkedwords[] = '<a href="/convertspelling.php?action=ViewList&letter=' . strtolower($char) . '">' . $char . '</a>';
	}	
	print(implode(' | ', $linkedwords));
	
	print('</p>');
	
	print('<p class="font-family-arial"><strong>Note:</strong> There is a 100,000-character limit to this form.  If you attempt to insert input beyond this, nothing additional will appear in the text box.</p>');

				// Direction
			
			// -------------------------------------------------------------
	
	print('<p class="font-family-arial">');
	print('Select Conversion Direction : ');
	print('<select name="direction">');
	print('<option value="british-to-american">Convert British spellings to American Spellings</option>');
	print('<option value="american-to-british">Convert American spellings to British Spellings</option>');
	print('</select>');
	print('</p>');
	
	print('</div>');

				// Text Area
			
			// -------------------------------------------------------------
	
	print('<center>');
	print('<textarea name="text" cols="75" rows="20" maxlength="100000">');
	
	if($this->text) {
		print($this->text);
	} else {
		print('For example : Labourers of the World, Unite!');
	}
	
	print('</textarea>');
	print('<br>');
	print('<input type="submit" value="Convert Spelling" style="margin-top:5px;">');
	
	print('</center>');

				// Output
			
			// -------------------------------------------------------------
	
	print('<div style="width:80%;text-align:left;margin:auto;border:1px solid black; margin-top:5px;margin-bottom:5px;">');
	print('<p class="font-family-arial"><h2 style="font-family:tahoma;">Results :</h2></p>');
	
	print('<div class="font-family-arial">');
	
	if($this->text) {
		print($this->converted_text);
	} else {
		print('<p><i>Results will appear here.</i></p>');
	}
	
	print('</div>');
	print('</div>');
	
				// End Form
			
			// -------------------------------------------------------------
	
	print('</form>');
	
?>