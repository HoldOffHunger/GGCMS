<?php

	ggreq('traits/scripts/DBFunctions.php');
	ggreq('traits/scripts/SimpleErrors.php');
	ggreq('traits/scripts/SimpleForms.php');
	ggreq('traits/scripts/SimpleLookupLists.php');
	ggreq('traits/scripts/SimpleORM.php');

		/*
			ads.txt, the IAB's Authorized Digital Sellers file, made the way
			robots.php makes robots.txt: /ads.txt for ad exchanges, /ads.php
			for people, /ads.xml for machines.

			It names who may sell this site's ad space, from the site's own
			AdSellers() in abstract globals.  A buyer who finds no ads.txt, or
			finds the seller missing from it, may refuse to bid; AdSense calls
			a site without one "earnings at risk".  A site that sells no ads
			still publishes one, with the IAB's placeholder line, which tells
			buyers that anyone offering our pages is not us.
		*/

	class ads extends basicscript {
						// Traits
						// ---------------------------------------------

		use DBFunctions;
		use SimpleErrors;
		use SimpleForms;
		use SimpleLookupLists;
		use SimpleORM;

		public $humanreadable;
		public $ads;
		public $ad_sellers;
		public $ads_txt_file;
		public $header_title_text;

						// Security Data
						// ---------------------------------------------

		public function IsSecure() {
			return FALSE;
		}

		public function RequiresLogin() {
			return FALSE;
		}

						// Functionality
						// ---------------------------------------------

		public function display() {
			$this->SetORM();
			$this->SetRecordTree();

			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}

			$this->SetMasterRecord();
			$this->SetAdSellers();

			if($this->script_format_lower !== 'xml') {
				$this->SetAdsTXTFile();
				$this->SetAdsForHTML();
			} else {
				$this->SetAdsForXML();

				$this->humanreadable = $this->Param('humanreadable');
			}

			$this->FormatErrors();

			return TRUE;
		}

			// Each seller as [domain, account, relationship, certification],
			// with the certification optional, as the IAB's file allows.

		public function SetAdSellers() {
			$ad_sellers = [];

			foreach($this->handler->globals->AdSellers() as $ad_seller) {
				$ad_seller = array_values(array_map('trim', array_map('strval', (array)$ad_seller)));

				if(count($ad_seller) < 3 || !strlen($ad_seller[0]) || !strlen($ad_seller[1]) || !strlen($ad_seller[2])) {
					continue;
				}

				$ad_sellers[] = array_slice($ad_seller, 0, 4);
			}

			return $this->ad_sellers = $ad_sellers;
		}

		public function SetAdsTXTFile() {
			$lines = [
				'# ads.txt for ' . $this->handler->domain->primary_domain_lowercased . ' -- who may sell this site\'s ad space.',
				'# The human-readable version is at /ads.php.',
			];

			if($this->ad_sellers) {
				foreach($this->ad_sellers as $ad_seller) {
					$lines[] = implode(', ', $ad_seller);
				}
			} else {
				$lines[] = '# This site sells no advertising.';
				$lines[] = 'placeholder.example.com, placeholder, DIRECT, placeholder';
			}

			return $this->ads_txt_file = implode("\n", $lines) . "\n";
		}

		public function SetAdsForHTML() {
			$ads = [];

			$ads[] = ['Domain :', $this->handler->domain->primary_domain_lowercased];

			if($this->ad_sellers) {
				foreach($this->ad_sellers as $ad_seller) {
					$ads[] = [
						'<nobr>Authorised seller :</nobr>',
						htmlspecialchars($ad_seller[0] . ' (account ' . $ad_seller[1] . ', ' . $ad_seller[2] . ')', ENT_QUOTES, 'UTF-8'),
					];
				}
			} else {
				$ads[] = ['<nobr>Authorised sellers :</nobr>', 'None.  This site sells no advertising.'];
			}

			return $this->ads = $ads;
		}

		public function SetAdsForXML() {
			$sellers = [];

			foreach($this->ad_sellers as $ad_seller) {
				$sellers[] = [
					'seller'=>[
						'domain'=>$ad_seller[0],
						'account'=>$ad_seller[1],
						'relationship'=>$ad_seller[2],
						'certification'=>$ad_seller[3] ?? '',
					],
				];
			}

			$this->ads = [
				[
					'domain'=>$this->handler->domain->primary_domain_lowercased,
					'sellers'=>$sellers,
				],
			];
		}

		public function GetHTMLFormatData_Title() {
			if(!$this->parent['id'] && $this->master_record && $this->master_record['id']) {
				return $this->header_title_text = 'Ads File For ' . $this->master_record['Title'] . ' : ' . $this->master_record['Subtitle'];
			}

			return FALSE;
		}
	}

?>
