<?php
		
			// Standard Requires
		
		// -------------------------------------------------------------

	ggreq('modules/spacing.php');
	
	ggreq('modules/html/text.php');
	$text = new module_text;
	
	ggreq('modules/html/form.php');
	$form = new module_form;
	
	ggreq('modules/html/divider.php');
	$divider = new module_divider;
	
	ggreq('modules/html/table.php');
	$table = new module_table;
	
	ggreq('modules/html/list/generic.php');
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
	
	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->header_title_text . ' &mdash; Chapter-Ification',
		'sub_text'=>'Chapterify will divide your text into sub-chapters automatically.',
	]);
	
	$entryindexheader->Display();
	
			// Admin Controls
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/entry-controls.php');
	$entry_controls = new module_entrycontrols;
	$entry_controls->Display(['that'=>$this, 'file'=>__FILE__]);

			// Start Top Bar
		
		// -------------------------------------------------------------

	print('<div class="horizontal-center width-95percent margin-top-5px">');

			// Breadcrumbs Info
		
		// -------------------------------------------------------------
	ggreq('modules/html/breadcrumbs.php');
	$breadcrumbs = new module_breadcrumbs(['that'=>$this, 'title'=>'Chapterification']);
	$breadcrumbs->Display();
	
			// Login Info
		
		// -------------------------------------------------------------
		
	ggreq('modules/html/auth.php');
	$auth = new module_auth(['that'=>$this]);
	$auth->Display();

			// End Top Bar
		
		// -------------------------------------------------------------
	
	print('</div>');

	print('<div class="clear-float"></div>');
	
			// Display List Items
		
		// -------------------------------------------------------------

				// Start Form
			
			// -------------------------------------------------------------
	
	print('<form METHOD="POST">');
	
	print('<div style="width:80%;text-align:left;margin:auto;border:1px solid black; margin-top:5px;margin-bottom:5px;">');

				// Direction
			
			// -------------------------------------------------------------
	
	print('<p class="font-family-arial">');
	print('Text : ');
	print('<select id="text-source" name="text">');
	print('<option value="custom">Custom</option>');
	print('<option value="this-text">This Text</option>');
	print('</select>');
	print('</p>');
	
	print('<p class="font-family-arial">');
	print('Match Text (case-sensitive) : ');
	print('<input type="text" size="60" id="match-text" placeholder="&lt;h4&gt;Chapter" />');
	print('</p>');
	
	print('<p class="font-family-arial">');
	print('Title Preface : ');
	print('<input type="text" size="60" id="title-preface" placeholder="Volume 1,(space)" />');
	print('</p>');
	
	print('<p class="font-family-arial">');
	print('Source : ');
	print('<input type="text" size="60" id="source" placeholder="Gutenberg.org" />');
	print('</p>');
	
	print('</div>');

				// Text Area
			
			// -------------------------------------------------------------
	
	print('<center>');
	print('<textarea id="textbody" name="textbody" cols="75" rows="20">');
	
	print('</textarea>');
	
	print('<textarea style="display:none;" id="textbody-hidden" name="textbody-hidden" cols="75" rows="20">');
	
	print($this->entry['textbody'][0]['Text']);
	
	print('</textarea>');
	
	print('<br>');
	print('<input id="chapterify-button" type="submit" value="Chapterify" style="margin-top:5px;">');
	
	print('</center>');

				// Output
			
			// -------------------------------------------------------------
	
	print('<div style="width:80%;text-align:left;margin:auto;border:1px solid black; margin-top:5px;margin-bottom:5px;">');
	print('<p class="font-family-arial"><h2 style="font-family:tahoma;">Results :</h2></p>');
	
	print('<div class="font-family-arial">');
	
	print('<p id="results"><i>Results will appear here.</i></p>');
	
	print('</div>');
	print('</div>');
	
				// End Form
			
			// -------------------------------------------------------------
	
	print('</form>');
	
?>