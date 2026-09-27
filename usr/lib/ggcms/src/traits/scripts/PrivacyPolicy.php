<?php

	trait PrivacyPolicy {
		public $header_title_text;
		
		public function getPrivacyPolicy() {
			$privacy_policy_paragraphs = $this->getPrivacyPolicyParagraphs();
			
			$privacy_policy = implode("\n\n", $privacy_policy_paragraphs);
			
			return $privacy_policy;
		}
		
		public function getPrivacyPolicyImages() {
			$images = [
							# image 1
				'mission'=>[
					'creator'=>'Freepik from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/premium-icon/megaphone_13809',
					'original-source'=>'https://www.freepik.com/free-icon/promoting_754744.htm',
					'filename'=>'mission.jpg',
				],
							# image 2
				'table-of-contents'=>[
					'creator'=>'Freepik from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/free-icon/list_130291',
					'original-source'=>'https://www.freepik.com/free-icon/list_942266.htm',
					'filename'=>'table-of-contents.jpg',
				],
							# image 3
				'what-data'=>[
					'creator'=>'Gregor Cresnar from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/free-icon/id-card_126352',
					'original-source'=>'https://www.freepik.com/free-icon/id-card_934346.htm',
					'filename'=>'what-data.jpg',
				],
							# image 4
				'when-collected'=>[
					'creator'=>'Freepik from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/free-icon/business-presentation_66520',
					'original-source'=>'https://www.freepik.com/free-icon/business-presentation_772506.htm',
					'filename'=>'when-collected.jpg',
				],
							# image 5
				'how-long-stored'=>[
					'creator'=>'Freepik from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/free-icon/shelf-full_32152',
					'original-source'=>'https://www.freepik.com/free-icon/shelf-full_740180.htm',
					'filename'=>'how-long-stored.jpg',
				],
							# image 6
				'who-processes'=>[
					'creator'=>'Gregor Cresnar from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/free-icon/networking_126309',
					'original-source'=>'https://www.freepik.com/free-icon/networking_934303.htm',
					'filename'=>'who-processes.jpg',
				],
							# image 7
				'data-portability'=>[
					'creator'=>'Freepik from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/free-icon/wallet_31368',
					'original-source'=>'https://www.freepik.com/free-icon/wallet_739397.htm',
					'filename'=>'data-portability.jpg',
				],
							# image 8
				'data-editability'=>[
					'creator'=>'Freepik from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/free-icon/businessman-paper-of-the-application-for-a-job_30382',
					'original-source'=>'https://www.freepik.com/free-icon/businessman-paper-of-the-application-for-a-job_738304.htm',
					'filename'=>'data-editability.jpg',
				],
							# image 9
				'erase-data'=>[
					'creator'=>'Freepik from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/free-icon/safe-box_31074',
					'original-source'=>'https://www.freepik.com/free-icon/safe-box_739062.htm',
					'filename'=>'erase-data.jpg',
				],
							# image 10
				'privacy-violation'=>[
					'creator'=>'Freepik from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/free-icon/balance_122112',
					'original-source'=>'https://www.freepik.com/free-icon/balance_924648.htm',
					'filename'=>'privacy-violation.jpg',
				],
							# image 11
				'contact-events'=>[
					'creator'=>'Freepik from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/free-icon/open-mail_18635',
					'original-source'=>'https://www.freepik.com/free-icon/open-envelope-with-letter_750512.htm',
					'filename'=>'contact-events.jpg',
				],
							# image 12
				'avoided-site-activity'=>[
					'creator'=>'Freepik from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/free-icon/insurance_122057',
					'original-source'=>'https://www.freepik.com/free-icon/insurance_924593.htm',
					'filename'=>'avoided-site-activity.jpg',
				],
							# image 13
				'privacy-policy-license'=>[
					'creator'=>'Freepik from Flaticon',
					'license'=>'Flaticon Basic License',
					'source'=>'https://www.flaticon.com/free-icon/open-hanging-signal_44957',
					'original-source'=>'https://www.freepik.com/free-icon/open-hanging-signal_724429.htm',
					'filename'=>'privacy-policy-license.jpg',
				],
			];
			
			return $images;
		}
		
						// Privacy Policy Paragraphs
						// ---------------------------------------------
		
		public function getPrivacyPolicyParagraphs() {
			$header_index = 1;
			
			$images = $this->getPrivacyPolicyImages();
			
			$privacy_policy_paragraphs = [];
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getMissionSection([
						'image'=>$images['mission'],
						'headerindex'=>$header_index++,
					])
				);
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getTableOfContentsSection([
						'images'=>$images,
						'headerindex'=>$header_index++,
					])
				);
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getWhatDataSection([
						'image'=>$images['what-data'],
						'headerindex'=>$header_index++,
					])
				);
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getWhenCollectedSection([
						'image'=>$images['when-collected'],
						'headerindex'=>$header_index++,
					])
				);
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getHowLongStoredSection([
						'image'=>$images['how-long-stored'],
						'headerindex'=>$header_index++,
					])
				);
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getWhoProcessesSection([
						'image'=>$images['who-processes'],
						'headerindex'=>$header_index++,
					])
				);
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getDataPortabilitySection([
						'image'=>$images['data-portability'],
						'headerindex'=>$header_index++,
					])
				);
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getDataEditabilitySection([
						'image'=>$images['data-editability'],
						'headerindex'=>$header_index++,
					])
				);
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getEraseDataSection([
						'image'=>$images['erase-data'],
						'headerindex'=>$header_index++,
					])
				);
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getPrivacyViolationSection([
						'image'=>$images['privacy-violation'],
						'headerindex'=>$header_index++,
					])
				);
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getContactEventsSection([
						'image'=>$images['contact-events'],
						'headerindex'=>$header_index++,
					])
				);
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getAvoidedSiteActivitySection([
						'image'=>$images['avoided-site-activity'],
						'headerindex'=>$header_index++,
					])
				);
			
			$privacy_policy_paragraphs =
				array_merge($privacy_policy_paragraphs,
					$this->getPrivacyPolicyLicenseSection([
						'image'=>$images['privacy-policy-license'],
						'headerindex'=>$header_index++,
					])
				);
			
			return $privacy_policy_paragraphs;
		}
		
						// Individual Privacy Policy Sections
						// ---------------------------------------------
		
		public function getMissionSection($args) {
			$header_index = $args['headerindex'];
			$image = $args['image'];
			
			$mission_section[] =
				'<h3><a name="mission"></a>' .
				$this->getImageMarkup(['image'=>$image]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_MissionAndPurposeOfThePrivacyPolicy() . '</h3>';
			
			$mission_section[] =
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_MissionAndPurposeOfThePrivacyPolicy() . '</p>';
					
			return $mission_section;
		}
		
		public function getTableOfContentsSection($args) {
			$header_index = $args['headerindex'];
			$images = $args['images'];
			
			$toc_index = 1;
			
			$toc_section = [
				'<h3><a name="table-of-contents"></a>' .
				$this->getImageMarkup(['image'=>$images['table-of-contents']]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_TableOfContents() . '</h3>',
				
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_TableOfContents() . '</p>',
				
				'<ul>',
					'<li><a href="#mission">' .
						$this->getImageIconMarkup(['image'=>$images['mission']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_MissionAndPurposeOfThePrivacyPolicy() . '</a></li>',
					'<li><a href="#table-of-contents">' .
						$this->getImageIconMarkup(['image'=>$images['table-of-contents']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_TableOfContents() . '</a></li>',
					'<li><a href="#what-data">' .
						$this->getImageIconMarkup(['image'=>$images['what-data']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhatDataDoWeCollect() . '</a></li>',
					'<li><a href="#when-collected">' .
						$this->getImageIconMarkup(['image'=>$images['when-collected']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhenDoWeCollectData() . '</a></li>',
					'<li><a href="#how-long-stored">' .
						$this->getImageIconMarkup(['image'=>$images['how-long-stored']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_HowLongDoWeStoreDataWeCollect() . '</a></li>',
					'<li><a href="#who-processes">' .
						$this->getImageIconMarkup(['image'=>$images['who-processes']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhoProcessesOurData() . '</a></li>',
					'<li><a href="#data-portability">' .
						$this->getImageIconMarkup(['image'=>$images['data-portability']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_HowDoIGetACopyOfMyPersonalInformationOnThisSite() . '</a></li>',
					'<li><a href="#data-editability">' .
						$this->getImageIconMarkup(['image'=>$images['data-editability']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_HowDoIUpdateMyPersonalInformationOnThisSite() . '</a></li>',
					'<li><a href="#erase-data">' .
						$this->getImageIconMarkup(['image'=>$images['erase-data']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhoDoIContactToEraseMyPersonalInformationOnThisSite() . '</a></li>',
					'<li><a href="#privacy-violation">' .
						$this->getImageIconMarkup(['image'=>$images['privacy-violation']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhoDoIContactToReportAViolationOfThePrivacyPolicy() . '</a></li>',
					'<li><a href="#contact-events">' .
						$this->getImageIconMarkup(['image'=>$images['contact-events']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhenWillWeContactYou() . '</a></li>',
					'<li><a href="#avoided-site-activity">' .
						$this->getImageIconMarkup(['image'=>$images['avoided-site-activity']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhatDontWeDo() . '</a></li>',
					'<li><a href="#privacy-policy-license">' .
						$this->getImageIconMarkup(['image'=>$images['privacy-policy-license']]) .
						$this->getSectionText(['headerindex'=>$toc_index++]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_HowIsTheTextOfThisPrivacyPolicyLicensed() . '</a></li>',
				'</ul>',
			];
			
			return $toc_section;
		}
		
		public function getWhatDataSection($args) {
			$header_index = $args['headerindex'];
			$image = $args['image'];
			
			$what_data_section = [
				'<h3><a name="what-data"></a>' .
				$this->getImageMarkup(['image'=>$image]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhatDataDoWeCollect() . '</h3>',
				
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_WhatDataDoWeCollect() . '</p>',
				
				'<ul>',
					'<li><em>' . $this->getIPAddressesHeader() . '</em> - ' . $this->handler->abstractglobals->language_script->SectionText_WhatDataDoWeCollect_IPAddresses() . '  ' . $this->getNotPersonallyIdentifiableText() . '</li>',
					'<li><em>' . $this->getTrafficAnalyticsHeader() . '</em> - ' . $this->handler->abstractglobals->language_script->SectionText_WhatDataDoWeCollect_TrafficAnalytics() . '  ' . $this->getNotPersonallyIdentifiableText() . '</li>',
					'<li><em>' . $this->getUserAccountInformationHeader() . '</em> - ' . $this->handler->abstractglobals->language_script->SectionText_WhatDataDoWeCollect_UserAccountInformation() . '  ' . $this->getPersonallyIdentifiableText() . '</li>',
				'</ul>',
			];
			
			return $what_data_section;
		}
		
		public function getWhenCollectedSection($args) {
			$header_index = $args['headerindex'];
			$image = $args['image'];
			
			$when_collected_section = [
				'<h3><a name="when-collected"></a>' .
				$this->getImageMarkup(['image'=>$image]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhenDoWeCollectData() . '</h3>',
				
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_WhenDoWeCollectData() . '</p>',
				
				'<ul>',
					'<li><em>' . $this->getIPAddressesHeader() . '</em> - ' . $this->handler->abstractglobals->language_script->SectionText_WhenDoWeCollectData_IPAddresses() . '</li>',
					'<li><em>' . $this->getTrafficAnalyticsHeader() . '</em> - ' . $this->handler->abstractglobals->language_script->SectionText_WhenDoWeCollectData_TrafficAnalytics() . '</li>',
					'<li><em>' . $this->getUserAccountInformationHeader() . '</em> - ' . $this->handler->abstractglobals->language_script->SectionText_WhenDoWeCollectData_UserAccountInformation() . '</li>',
				'</ul>',
			];
			
			return $when_collected_section;
		}
		
		public function getHowLongStoredSection($args) {
			$header_index = $args['headerindex'];
			$image = $args['image'];
			
			$how_long_stored_section = [
				'<h3><a name="how-long-stored"></a>' .
				$this->getImageMarkup(['image'=>$image]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_HowLongDoWeStoreDataWeCollect() . '</h3>',
				
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_HowLongDoWeStoreDataWeCollect() . '</p>',
				
				'<ul>',
					'<li><em>' . $this->getIPAddressesHeader() . '</em> - ' . $this->handler->abstractglobals->language_script->SectionText_HowLongDoWeStoreDataWeCollect_IPAddresses() . '</li>',
					'<li><em>' . $this->getTrafficAnalyticsHeader() . '</em> - ' . $this->handler->abstractglobals->language_script->SectionText_HowLongDoWeStoreDataWeCollect_TrafficAnalytics() . '</li>',
					'<li><em>' . $this->getUserAccountInformationHeader() . '</em> - ' . $this->handler->abstractglobals->language_script->SectionText_HowLongDoWeStoreDataWeCollect_UserAccountInformation() . '</li>',
				'</ul>',
			];
			
			return $how_long_stored_section;
		}
		
		public function getWhoProcessesSection($args) {
			$header_index = $args['headerindex'];
			$image = $args['image'];
			
			$who_processes_section = [
				'<h3><a name="who-processes"></a>' .
				$this->getImageMarkup(['image'=>$image]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhoProcessesOurData() . '</h3>',
				
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_WhoProcessesOurData() . '</p>',
				
				'<ul>',
					'<li><em>Google OAuth</em> - ' . $this->handler->abstractglobals->language_script->SectionText_WhoProcessesOurData_GoogleOAuth() . '</li>',
					'<li><em>Google Analytics</em> - ' . $this->handler->abstractglobals->language_script->SectionText_WhoProcessesOurData_GoogleAnalytics() . '</li>',
				'</ul>',
			];
			
			return $who_processes_section;
		}
		
		public function getDataPortabilitySection($args) {
			$header_index = $args['headerindex'];
			$image = $args['image'];
			
			$data_portability_section = [
				'<h3><a name="data-portability"></a>' .
				$this->getImageMarkup(['image'=>$image]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_HowDoIGetACopyOfMyPersonalInformationOnThisSite() . '</h3>',
				
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_HowDoIGetACopyOfMyPersonalInformationOnThisSite() . ' ' . 'https://www.' . $this->master_record['Code'] . '/users.php?action=exportuser&user=(' . $this->handler->abstractglobals->language_script->SectionText_YourUserNameHere() . ')</p>',
				
				'<blockquote><a href="https://www.' . $this->domain_object->host . '.com/users.php?action=exportuser&user=holdoffhunger" target="_blank">https://www.' . $this->master_record['Code'] . '/users.php?action=exportuser&user=holdoffhunger</a></blockquote>',
			];
			
			return $data_portability_section;
		}
		
		public function getDataEditabilitySection($args) {
			$header_index = $args['headerindex'];
			$image = $args['image'];
			
			$data_editability_section = [
				'<h3><a name="data-editability"></a>' .
				$this->getImageMarkup(['image'=>$image]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_HowDoIUpdateMyPersonalInformationOnThisSite() . '</h3>',
				
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_HowDoIUpdateMyPersonalInformationOnThisSite() . '</p>',
			];
			
			return $data_editability_section;
		}
		
		public function getEraseDataSection($args) {
			$header_index = $args['headerindex'];
			$image = $args['image'];
			
			$erase_data_section = [
				'<h3><a name="erase-data"></a>' .
				$this->getImageMarkup(['image'=>$image]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhoDoIContactToEraseMyPersonalInformationOnThisSite() . '</h3>',
				
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_WhoDoIContactToEraseMyPersonalInformationOnThisSite() . '</p>',
				
				'<blockquote><a href="/contact.php">' . $this->getContactUsText() . '</a></blockquote>',
			];
			
			return $erase_data_section;
		}
		
		public function getPrivacyViolationSection($args) {
			$header_index = $args['headerindex'];
			$image = $args['image'];
			
			$privacy_violation_section = [
				'<h3><a name="privacy-violation"></a>' .
				$this->getImageMarkup(['image'=>$image]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhoDoIContactToReportAViolationOfThePrivacyPolicy() . '</h3>',
				
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_WhoDoIContactToReportAViolationOfThePrivacyPolicy() . '</p>',
				
				'<blockquote><a href="/contact.php">' . $this->getContactUsText() . '</a></blockquote>',
			];
			
			return $privacy_violation_section;
		}
		
		public function getContactEventsSection($args) {
			$header_index = $args['headerindex'];
			$image = $args['image'];
			
			$contact_events_section = [
				'<h3><a name="contact-events"></a>' .
				$this->getImageMarkup(['image'=>$image]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhenWillWeContactYou() . '</h3>',
				
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_WhenWillWeContactYou() . '</p>',
				
				'<ul>',
					'<li>' . $this->handler->abstractglobals->language_script->SectionText_WhenWillWeContactYou_PrivacyPolicyUpdated() . '</li>',
					'<li>' . $this->handler->abstractglobals->language_script->SectionText_WhenWillWeContactYou_BreachOfProtectedUserInformation() . '</li>',
				'</ul>',
			];
			
			return $contact_events_section;
		}
		
		public function getAvoidedSiteActivitySection($args) {
			$header_index = $args['headerindex'];
			$image = $args['image'];
			
			$avoided_site_activity_section = [
				'<h3><a name="avoided-site-activity"></a>' .
				$this->getImageMarkup(['image'=>$image]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_WhatDontWeDo() . '</h3>',
				
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_WhatDontWeDo() . '</p>',
				
				'<ul>',
					'<li>' . $this->handler->abstractglobals->language_script->SectionText_WhatDontWeDo_CreditCardNumbers() . '</li>',
					'<li>' . $this->handler->abstractglobals->language_script->SectionText_WhatDontWeDo_SellTradeOtherwiseTransferPersonalInformation() . '</li>',
					'<li>' . $this->handler->abstractglobals->language_script->SectionText_WhatDontWeDo_ThirdPartyHyperlinkingResponsibility() . '</li>',
				'</ul>',
			];
			
			return $avoided_site_activity_section;
		}
		
		public function getPrivacyPolicyLicenseSection($args) {
			$header_index = $args['headerindex'];
			$image = $args['image'];
			
			$privacy_policy_license_section = [
				'<h3><a name="privacy-policy-license"></a>' .
				$this->getImageMarkup(['image'=>$image]) .
				$this->getSectionText(['headerindex'=>$header_index]) . ' : ' . $this->handler->abstractglobals->language_script->HeaderText_HowIsTheTextOfThisPrivacyPolicyLicensed() . '</h3>',
				
				'<p>' . $this->handler->abstractglobals->language_script->SectionText_HowIsTheTextOfThisPrivacyPolicyLicensed() . '</p>',
				
				'<blockquote><a href="http://www.CopyLeftLicense.com/?id=440" target="_blank">' . $this->handler->abstractglobals->language_script->SectionText_HowIsTheTextOfThisPrivacyPolicyLicensed_LicenseName() . '</a></blockquote>',
			];
			
			return $privacy_policy_license_section;
		}
		
						// Privacy Policy Helper Functions
						// ---------------------------------------------
		
		public function getContactUsText() {
			return $this->handler->abstractglobals->language_script->SubHeaderText_ContactUs();
		}
		
		public function getIPAddressesHeader() {
			return $this->handler->abstractglobals->language_script->SubHeaderText_IPAddresses();
		}
		
		public function getTrafficAnalyticsHeader() {
			return $this->handler->abstractglobals->language_script->SubHeaderText_TrafficAnalytics();
		}
		
		public function getUserAccountInformationHeader() {
			return $this->handler->abstractglobals->language_script->SubHeaderText_UserAccountInformation();
		}
		
		public function getNotPersonallyIdentifiableText() {
			return $this->handler->abstractglobals->language_script->SubHeaderText_ThisIsNotPersonallyIdentifiableInformation();
		}
		
		public function getPersonallyIdentifiableText() {
			return $this->handler->abstractglobals->language_script->SubHeaderText_ThisIsPersonallyIdentifiableInformation();
		}
		
		public function getSectionText($args) {
			$header_index = $args['headerindex'];
			return $this->handler->abstractglobals->language_script->SubHeaderText_Section() . ' ' . $header_index;
		}
		
		public function getImageBaseDirectory() {
			$base_directory = '/image/privacy/';
			
			if(	($this->script_format_lower == 'pdf') ||
				($this->script_format_lower == 'rtf')) {
				$base_directory = 'https://www.' . $this->domain_object->host . '.com' . $base_directory;
			}
			
			return $base_directory;
		}
		
		public function getImageMarkup($args) {
			$image = $args['image'];
			
			if($this->mobile_friendly) {
				return '';
			}
			
			$base_directory = $this->getImageBaseDirectory();
			
			$markup =
				'<img src="' . $base_directory .
				$image['filename'] .
				'" title="Icon designed by ' .
				$image['creator'] . ', ' .
				$image['license'] . ' License, from ' .
				$image['source'] .
				'"height="50" width="50" class="border-2px"> '
			;
			$markup .= "\n";
			
			return $markup;
		}
		
		public function getImageIconMarkup($args) {
			$image = $args['image'];
			
			if($this->mobile_friendly || ($this->script_format_lower == 'pdf')) {
				return '';
			}
			
			$base_directory = $this->getImageBaseDirectory();
			
			$markup =
				'<img src="' . $base_directory .
				$image['filename'] .
				'" title="Icon designed by ' .
				$image['creator'] . ', ' .
				$image['license'] . ' License, from ' .
				$image['source'] .
				'"height="15" width="15" class="border-2px"> '
			;
			
			return $markup;
		}
		
						// HTML Data
						// ---------------------------------------------
		
		public function GetHTMLFormatData_Title() {
			$header_title_text = $this->handler->abstractglobals->language_script->SubHeaderText_PrivacyPolicyFor() . ' ' . $this->master_record['Title'] . ' : ' . $this->master_record['Subtitle'];
			
			return $this->header_title_text = $header_title_text;
		}
	}
	
?>