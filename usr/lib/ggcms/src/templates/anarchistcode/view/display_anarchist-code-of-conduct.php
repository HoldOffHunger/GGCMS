<?php
	
			// Code of Conduct Text
		
		// -------------------------------------------------------------

	ggreq('traits/scripts/CodeOfConductTrait.php');
	
	$this->handler->abstractglobals->buildAbstractGlobals_Language_Scripts_reset([
		'script_file'=>'codeofconduct',
	]);
	
	class code extends basicscript {
		use CodeOfConductTrait;
		
		public function __construct($args) {
			$this->language_object = $args['languageobject'];
			$this->handler = $args['handler'];
		}
	}
	$code_of_conduct = new code([
		'languageobject'=>$this->language_object,
		'handler'=>$this->handler,
	]);
	$html_paragraphs = $code_of_conduct->getCodeOfConductParagraphs();
	
			// HTML Displaying
		
		// -------------------------------------------------------------
		
	if(	($this->script_format_lower == 'pdf') ||
		($this->script_format_lower == 'rtf') ||
		($this->script_format_lower == 'epub') ||
		($this->script_format_lower == 'daisy') ||
		($this->script_format_lower == 'sgml') ||
		($this->script_format_lower == 'tex') ||
		($this->script_format_lower == 'json') ||
		($this->script_format_lower == 'xml') ||
		($this->script_format_lower == 'txt') ||
		($this->script_format_lower == 'csv') ||
		($this->script_format_lower == 'opds') ||
		($this->script_format_lower == 'rdf') ||
		($this->script_format_lower == 'html' && $this->Param('printerfriendly')) ||
		($this->script_format_lower == 'html' && $this->Param('invertedcolors'))
	) {
		$html_document = '';
		
		$html_document = implode("\n", $html_paragraphs);
		
		$html_document = '<h1>' . $this->GetHTMLFormatData_Title() . '</h1>' . "\n" . $html_document;
		
		if($this->script_format_lower == 'pdf') {
			$this->source_content = $html_document;
		} elseif($this->script_format_lower == 'tex') {
			$this->source_content = $html_document;
		} elseif($this->script_format_lower == 'rtf') {
			$this->source_content = $html_document;
		} elseif($this->script_format_lower == 'opds' || $this->script_format_lower == 'rdf') {
			$this->record_to_use['codeofconduct'] = $html_document;
		} elseif($this->script_format_lower == 'json') {
			$this->record_to_use['codeofconduct'] = $html_document;
		} elseif($this->script_format_lower == 'xml' || $this->script_format_lower == 'csv') {
			$this->record_to_use['codeofconduct'] = $html_document;
			$this->source_content = $this->record_to_use;
		} elseif ($this->script_format_lower == 'daisy') {
			$this->source_content = $html_document;
		} elseif ($this->script_format_lower == 'sgml') {
			$this->source_content = $html_document;
		} elseif($this->script_format_lower == 'epub') {
			$this->record_to_use = $this->entry;
			$this->source_content = $html_document;
		} elseif($this->script_format_lower == 'txt') {
			$text_document = strip_tags($html_document);
			
			if($this->Param('wrapped'))
			{
				$text_document = wordwrap($text_document, 75, "\n", FALSE);
			}
			
			print($text_document);
		} elseif($this->script_format_lower == 'html' && $this->Param('printerfriendly')) {
			print('<div class="font-family-arial">');
			
			print($html_document);
			
			print('</div>');
		} elseif($this->script_format_lower == 'html' && $this->Param('invertedcolors')) {
			print('<div class="font-family-arial">');
			
			print($html_document);
			
			print('</div>');
		}
	} elseif($this->script_format_lower == 'html') {
	
				// Standard Requires
			
			// -------------------------------------------------------------
	
		ggreq('modules/spacing.php');
		
		ggreq('modules/' . $this->script_format_lower . '/text.php');
		$text = new module_text;
		
		ggreq('modules/' . $this->script_format_lower . '/form.php');
		$form = new module_form;
		
		ggreq('modules/' . $this->script_format_lower . '/divider.php');
		$divider = new module_divider;
		
		ggreq('modules/' . $this->script_format_lower . '/table.php');
		$table = new module_table;
		
		ggreq('modules/' . $this->script_format_lower . '/list/generic.php');
		$generic_list = new module_genericlist;
		
		ggreq('modules/' . $this->script_format_lower . '/languages.php');
		$languages_args = [
			'languageobject'=>$this->language_object,
			'domainobject'=>$this->domain_object,
		];
		$languages = new module_languages($languages_args);
		
		ggreq('modules/' . $this->script_format_lower . '/navigation.php');
		$navigation_args = [
			'globals'=>$this->handler->globals,
			'languageobject'=>$this->language_object,
			'divider'=>$divider,
			'domainobject'=>$this->domain_object,
		];
		$navigation = new module_navigation($navigation_args);
		
				// Get Info Header Language
			
			// -------------------------------------------------------------
		
		if($this->language_object->getLanguageCode() == 'en')
		{
			$div_mouseover = $this->master_record['description'][0]['Description'];
		}
		else
		{
			$instructions_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainInstructionsContent']);
			
			if($instructions_language_translations[$this->language_object->getLanguageCode()])
			{
				$div_mouseover = $instructions_language_translations[$this->language_object->getLanguageCode()];
			}
			else
			{
				$div_mouseover = $this->master_record['description'][0]['Description'];
			}
		}
		
		if($this->language_object->getLanguageCode() == 'en')
		{
			$quote_text = $this->master_record['quote'][0]['Quote'];
		}
		else
		{
			$image_language_translations = $this->getListAndItems(['ListTitle'=>'LanguagesMainImageQuote']);
			
			if($image_language_translations[$this->language_object->getLanguageCode()])
			{
				$quote_text = $image_language_translations[$this->language_object->getLanguageCode()];
			}
			else
			{
				$quote_text = $this->master_record['quote'][0]['Quote'];
			}
		}
		
				// Display Header
			
			// -------------------------------------------------------------
			
		ggreq('modules/html/entry-header.php');
		ggreq('modules/html/entry-index-header.php');
		$entryheader = new module_entryindexheader([
			'that'=>$this,
			'main_text'=>$this->header_title_text,
			'sub_text'=>$sub_text,
			'sub_title'=>$sub_title,
		]);
		
		$entryheader->Display();
		
				// Basic Divider Arguments
			
			// -------------------------------------------------------------
		
		$divider_navigation_args = [
			'class'=>'width-95percent horizontal-center margin-top-14px border-2px',
		];
		
		$file_divider = [
			'class'=>'width-100percent horizontal-left margin-5px font-family-arial',
		];
		
		$divider_instruction_area_start_args = [
			'class'=>'width-50percent border-1px horizontal-center margin-top-22px',
		];
		
				// Get Info Header Language
			
			// -------------------------------------------------------------
			
		switch($this->language_object->getLanguageCode()) {
			default:
			case 'en':
				$codeofconduct_instructions_text = 'This is the code of conduct for ' . $this->master_record['Code'] . '.';
				break;
				
			case 'de':
				$codeofconduct_instructions_text = 'Dies ist der Verhaltenskodex für ' . $this->master_record['Code'] . '.';
				break;
				
			case 'es':
				$codeofconduct_instructions_text = 'Este es el código de conducta para ' . $this->master_record['Code'] . '.';
				break;
			
			case 'fr':
				$codeofconduct_instructions_text = 'Ceci est le code de conduite de ' . $this->master_record['Code'] . '.';
				break;
				
			case 'ja':
				$codeofconduct_instructions_text = 'これは' . $this->master_record['Code'] . 'の行動規範です。';
				break;
				
			case 'it':
				$codeofconduct_instructions_text = 'Questo è il codice di condotta per ' . $this->master_record['Code'] . '.';
				break;
				
			case 'nl':
				$codeofconduct_instructions_text = 'Dit is de gedragscode voor ' . $this->master_record['Code'] . '.';
				break;
				
			case 'pl':
				$codeofconduct_instructions_text = 'To jest kodeks postępowania dla ' . $this->master_record['Code'] . '.';
				break;
			
			case 'pt':
				$codeofconduct_instructions_text = 'Este é o código de conduta para o ' . $this->master_record['Code'] . '.';
				break;
				
			case 'ru':
				$codeofconduct_instructions_text = 'Это кодекс поведения для ' . $this->master_record['Code'] . '.';
				break;
			
			case 'tr':
				$codeofconduct_instructions_text = $this->master_record['Code'] . ' için davranış kuralları budur.';
				break;
				
			case 'zh':
				$codeofconduct_instructions_text = '这是' . $this->master_record['Code'] . '的行为准则。';
				break;
		}
		
				// Code of Conduct Instructions
			
			// -------------------------------------------------------------
		
		$primary_url = $this->domain_object->GetPrimaryDomain(['lowercase'=>1, 'www'=>1]);
		
		$version_list_display_args = [
			'options'=>[
				'tableheaders'=>0,
				'tableclass'=>'font-family-arial width-70percent horizontal-center border-2px background-color-' . $third_color . ' margin-top-14px',
				'rowclass'=>'border-1px horizontal-left',
				'cellclass'=>[
					'border-1px vertical-top',
					'border-1px width-100percent vertical-top',
				],
			],
			'list'=>[[
				$codeofconduct_instructions_text,
			]],
		];
		$generic_list->Display($version_list_display_args);
		
				// Start Top Bar
			
			// -------------------------------------------------------------
		
		print('<div class="horizontal-center width-95percent margin-top-5px">');
		
				// Breadcrumbs Info
			
			// -------------------------------------------------------------
		
		ggreq('modules/html/breadcrumbs.php');
		$breadcrumbs = new module_breadcrumbs(['that'=>$this]);
		$breadcrumbs->Display();
		
				// Login Info
			
			// -------------------------------------------------------------
			
		ggreq('modules/html/auth.php');
		$auth = new module_auth(['that'=>$this]);
		$auth->Display();
		
				// End Top Bar
			
			// -------------------------------------------------------------
		
		print('</div>');
	
			// Finish Breadcrumb Trails
		
		// -------------------------------------------------------------
							
	$clear_float_divider_start_args = [
		'class'=>'clear-float',
	];
	
	$divider->displaystart($clear_float_divider_start_args);
	
	$clear_float_divider_end_args = [
	];
	
	$divider->displayend($clear_float_divider_end_args);
			
				// Alternate Formats Info
			
			// -------------------------------------------------------------
			
		ggreq('modules/html/alternateformats.php');
		$auth = new module_alternateformats(['that'=>$this]);
		$auth->Display();
			
				// Get Info Header Language
			
			// -------------------------------------------------------------
			
		switch($this->language_object->getLanguageCode()) {
			default:
			case 'en':
				$codeofconduct_instructions_header = $this->master_record['Code'] . ' Privacy Policy';
				break;
				
			case 'de':
				$codeofconduct_instructions_header = $this->master_record['Code'] . '-Datenschutzrichtlinie';
				break;
				
			case 'es':
				$codeofconduct_instructions_header = 'Política de privacidad de ' . $this->master_record['Code'];
				break;
			
			case 'fr':
				$codeofconduct_instructions_header = 'Politique de confidentialité de ' . $this->master_record['Code'];
				break;
				
			case 'ja':
				$codeofconduct_instructions_header = $this->master_record['Code'] . 'プライバシーポリシー';
				break;
				
			case 'it':
				$codeofconduct_instructions_header = 'Informativa sulla privacy di ' . $this->master_record['Code'];
				break;
				
			case 'nl':
				$codeofconduct_instructions_header = $this->master_record['Code'] . ' Privacybeleid';
				break;
				
			case 'pl':
				$codeofconduct_instructions_header = 'Polityka prywatności XYZ ' . $this->master_record['Code'];
				break;
			
			case 'pt':
				$codeofconduct_instructions_header = 'Política de Privacidade ' . $this->master_record['Code'];
				break;
				
			case 'ru':
				$codeofconduct_instructions_header = 'Политика конфиденциальности ' . $this->master_record['Code'];
				break;
			
			case 'tr':
				$codeofconduct_instructions_header = $this->master_record['Code'] . ' Gizlilik Politikası';
				break;
				
			case 'zh':
				$codeofconduct_instructions_header = $this->master_record['Code'] . '隐私政策';
				break;
		}
		
				// Display Code of Conduct File
			
			// -------------------------------------------------------------
		
		$divider->displaystart($divider_instruction_area_start_args);
		

		print('<center><h2 class="margin-5px font-family-tahoma">' . $codeofconduct_instructions_header . ' :</h2></center>');
		
		
		$divider->displaystart($file_divider);
		$codeofconduct_text = implode("\n", $html_paragraphs);
		$codeofconduct_text = str_replace('<p>', '<p class="margin-top-10px margin-bottom-0px text-indent-25px">', $codeofconduct_text);
		
		print('<div class="text-to-play-as-audio">');
		print($codeofconduct_text);
		print('</div>');
		
		$divider->displayend($divider_end_args);
		
		$divider->displayend($divider_end_args);
	
				// Display Language Options
			
			// -------------------------------------------------------------
		
		$languages->display();
		
				// Display Final Ending Navigation
			
			// -------------------------------------------------------------
		
		$bottom_navigation_args = [];
		$navigation->DisplayBottomNavigation($bottom_navigation_args);
	}
	
?>