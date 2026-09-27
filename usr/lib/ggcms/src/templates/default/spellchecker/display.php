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
	
	ggreq('modules/html/header.php');
	$header = new module_header;
	
	ggreq('modules/html/languages.php');
	$languages_args = [
		'languageobject'=>$this->language_object,
		'domainobject'=>$this->domain_object,
	];
	$languages = new module_languages($languages_args);
	
	ggreq('modules/html/navigation.php');
	$navigation_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
	];
	$navigation = new module_navigation($navigation_args);
	
			// Share Package
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/socialmediasharelinks.php');
	$social_media_share_links_args = [
		'globals'=>$this->handler->globals,
		'languageobject'=>$this->language_object,
		'divider'=>$divider,
		'domainobject'=>$this->domain_object,
		'socialmedia'=>$this->social_media,
		'socialmediasharelinkargs'=>[
			'url'=>$this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]),
			'title'=>$this->header_title_text,
			'desc'=>$instructions_content_text,
			'provider'=>$this->domain_object->primary_domain_lowercased,
		],
	];
	$social_media_share_links = new module_socialmediasharelinks($social_media_share_links_args);
	
			// Display Header
		
		// -------------------------------------------------------------
	
	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->master_record['Title'] . ' Spellchecker',
	]);
	
	$entryindexheader->Display();
	
			// Basic Divider Arguments
		
		// -------------------------------------------------------------
	
	$divider_main_area_start_args = [
		'class'=>'width-90percent horizontal-center padding-top-22px',
	];
	
	$divider_secondary_area_start_args = [
		'class'=>'width-90percent horizontal-center',
	];
	
	$divider_instruction_area_start_args = [
		'class'=>'width-50percent border-1px horizontal-center margin-top-22px',
	];
	
	$divider_instruction_area_text_args = [
		'class'=>'width-95percent horizontal-left',
	];
	
	$divider_note_args = [
		'class'=>'width-50percent horizontal-center float-left',
	];
	
	$divider_end_args = [
	];
	
			// Get Instructions Language
		
		// -------------------------------------------------------------
	
	if($this->language_object->getLanguageCode() == 'en')
	{
		$instructions_label_text = 'Instructions';
	}
	else
	{
		$instruction_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainInstructionsHeader']);
		
		if($instruction_language_translations[$this->language_object->getLanguageCode()])
		{
			$instructions_label_text = $instruction_language_translations[$this->language_object->getLanguageCode()];
		}
		else
		{
			$instructions_label_text = 'Instructions';
		}
	}
	
	$instructions_content_text = 'Enter text into the left panel and see typos and errors pointed out in the right panel.';
	
			// Display Instructions
		
		// -------------------------------------------------------------
	
	$divider->displaystart($divider_instruction_area_start_args);
	
	$divider->displaystart($divider_instruction_area_text_args);
	
print('<div class="padding-5px"><span class="font-family-tahoma"><b>' . $instructions_label_text . ' :</b> ' . $instructions_content_text . '</span></div>');
	
	
	$divider->displayend($divider_end_args);
	
	$divider->displayend($divider_end_args);
	
			// Get Status Language
		
		// -------------------------------------------------------------
	
	if($this->language_object->getLanguageCode() == 'en')
	{
		$status_label_text = 'Status';
	}
	else
	{
		$status_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainStatusHeader']);
		
		if($status_language_translations[$this->language_object->getLanguageCode()])
		{
			$status_label_text = $status_language_translations[$this->language_object->getLanguageCode()];
		}
		else
		{
			$status_label_text = 'Status';
		}
	}
	
			// Get Status Language
		
		// -------------------------------------------------------------
	
	if($this->language_object->getLanguageCode() == 'en')
	{
		$status_content_text = 'Waiting for User';
	}
	else
	{
		$status_content_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainActivityHeader']);
		
		if($status_content_language_translations[$this->language_object->getLanguageCode()])
		{
			$status_content_text = $status_content_language_translations[$this->language_object->getLanguageCode()];
		}
		else
		{
			$status_content_text = 'Waiting for User';
		}
	}
	
			// Display Status
		
		// -------------------------------------------------------------
	
	$divider->displaystart($divider_instruction_area_start_args);
	
	$divider->displaystart($divider_instruction_area_text_args);
	
print('<div class="padding-5px"><span class="font-family-tahoma"><b>' . $status_label_text . ' :</b> <span id="status-text">' . $status_content_text . '</span>.</span></div>');
	
	
	$divider->displayend($divider_end_args);
	
	$divider->displayend($divider_end_args);
	
			// Get Input / Output Areas Language
		
		// -------------------------------------------------------------
	
	$list_one_main_header_text = 'Text to Find Typos and Errors From';

	$list_two_main_header_text = 'Typos and Errors from Provided Text';

	$list_one_content_text = 'Type or copy-and-paste your text into this text box.';

	$list_two_content_text = 'Then your typos and errors list will appear in this text box.';

			// Display Input / Output Areas
		
		// -------------------------------------------------------------
	
	print('<CENTER>');
	print('<table class="padding-top-22px" border="0" style="border:1px solid black;max-width:1px;">');
	
	print('<tr><td>');
	
print('<center><h4 class="margin-0px padding-0px font-family-tahoma">' . $list_one_main_header_text . '</h4></center>');
	
	
	$separate_cells_args = [
		'cellwidth'=>'80%',
		'cellvalign'=>'top',
	];
	$table->DisplayComponent_SeparateCells($separate_cells_args);
	
print('<center><h4 class="margin-0px padding-0px font-family-tahoma">' . $list_two_main_header_text . '</h4></center>');
	
	
	$separate_cells_args = [
		'cellwidth'=>'80%',
		'cellvalign'=>'top',
	];
	$table->DisplayComponent_SeparateCellsAndRows($separate_cells_args);
	
	$type_args = [
		'type'=>'textarea',
		'name'=>'input-area',
		'id'=>'input-area',
		'class'=>'input-area',
		'value'=>$list_one_content_text,
		'cols'=>50,
		'rows'=>30,
	];
	
	$form->DisplayFormField($type_args);
	
	$separate_cells_args = [
		'cellwidth'=>'80%',
		'cellvalign'=>'top',
	];
	$table->DisplayComponent_SeparateCells($separate_cells_args);
	
	$type_args = [
		'type'=>'textarea',
		'name'=>'output-area',
		'id'=>'output-area',
		'class'=>'output-area',
		'value'=>$list_two_content_text,
		'cols'=>50,
		'rows'=>30,
	];
	
	$form->DisplayFormField($type_args);
	
	print('</table>');
	print('</CENTER>');
	
			// Sort Button and Order Columns
		
		// -------------------------------------------------------------
	
				// Start Div
				// -------------------------------------------------------
	
	$divider->displaystart($divider_main_area_start_args);
	
				// Get 'Sort' Button Language
				// -------------------------------------------------------
	
	$main_button_language_text = 'Find Errors';
	
				// Display 'Sort' Button
				// -------------------------------------------------------
	
	$type_args = [
		'type'=>'button',
		'id'=>'function-button',
		'class'=>'margin-5px',
		'value'=>$main_button_language_text,
	];
	
	$form->DisplayFormField($type_args);
	
	print('<br>');
	
			// Display Strip HTML Option
		
		// -------------------------------------------------------------
	
				// Get Strip HTML Language
				// -------------------------------------------------------
	
	if($this->language_object->getLanguageCode() == 'en')
	{
		$strip_html_option_content = 'Strip HTML';
	}
	else
	{
		$strip_html_option_list = $this->getListAndItems(['ListTitle'=>'LanguagesMainStripHTMLOption']);
		
		if($strip_html_option_list[$this->language_object->getLanguageCode()])
		{
			$strip_html_option_content = $strip_html_option_list[$this->language_object->getLanguageCode()];
		}
		else
		{
			$strip_html_option_content = 'Strip HTML';
		}
	}
	
	if($this->language_object->getLanguageCode() == 'en')
	{
		$strip_html_description_content = 'This option will remove html from each item in the inbound list.';
	}
	else
	{
		$strip_html_description_list = $this->getListAndItems(['ListTitle'=>'LanguagesMainStripHTMLDescription']);
		
		if($strip_html_description_list[$this->language_object->getLanguageCode()])
		{
			$strip_html_description_content = $strip_html_description_list[$this->language_object->getLanguageCode()];
		}
		else
		{
			$strip_html_description_content = 'This option will remove html from each item in the inbound list.';
		}
	}
	
print('<span class="font-family-arial" title="' . $strip_html_description_content . '">' . $strip_html_option_content . '</span>');
	
	
	$type_args = [
		'type'=>'checkbox',
		'name'=>'strip-html',
		'id'=>'strip-html',
		'class'=>'strip-html',
		'value'=>'1',
	];
	
	$form->DisplayFormField($type_args);
	
	
				// End Div
				// -------------------------------------------------------
	
	$divider->displayend($divider_end_args);
	
			// Display Misspelled Words for JavaScript
		
		// -------------------------------------------------------------
	
function utf8ize($d) {
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string ($d)) {
        return mb_convert_encoding($d, 'UTF-8', 'ISO-8859-1');
    }
    return $d;
}
	
//	print("BT: WORDS!");
//	print("<PRE>");
	print('<script type="text/javascript">');
	print('words = ');
#	print_r( "WHY!!!!");
	print_r(json_encode(utf8ize($this->misspelled_words)));
	print(';');
	print('</script>');
//	print("</PRE>");
	
			// Display Social Media Options
		
		// -------------------------------------------------------------
	
	$social_media_share_links->display();
	
			// Display Language Options
		
		// -------------------------------------------------------------
	
	$languages->display();
	
			// Display Final Ending Navigation
		
		// -------------------------------------------------------------
	
	$bottom_navigation_args = [
		'thispage'=>'Home',
	];
	$navigation->DisplayBottomNavigation($bottom_navigation_args);
	
?>