<?php

	/*
		The return policy: one block of text, for the sites that sell
		things.  Off everywhere unless a site's globals override
		ReturnPolicyEnabled() to TRUE, and off means a 404 rather than a
		page saying there is no policy -- a site that sells nothing has no
		business answering this address.
	*/

	ggreq('traits/scripts/DBFunctions.php');
	ggreq('traits/scripts/SimpleErrors.php');
	ggreq('traits/scripts/SimpleForms.php');
	ggreq('traits/scripts/SimpleLookupLists.php');
	ggreq('traits/scripts/SimpleORM.php');

	class returnpolicy extends basicscript {
						// Traits
						// ---------------------------------------------

		use DBFunctions;
		use SimpleErrors;
		use SimpleForms;
		use SimpleLookupLists;
		use SimpleORM;

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
			if(!$this->ReturnPolicyEnabled()) {
				return FALSE;	# 404
			}

			$this->SetORM();
			$this->SetRecordTree();

			if(!$this->ValidateOrm()) {
				return FALSE;	# 404
			}

			$this->SetMasterRecord();

			$this->FormatErrors();

			return TRUE;
		}

		public function ReturnPolicyEnabled() {
			$globals = $this->handler->globals;

			return is_object($globals) && method_exists($globals, 'ReturnPolicyEnabled') && $globals->ReturnPolicyEnabled();
		}

						// Return Policy Text
						// ---------------------------------------------

		public function getReturnPolicy() {
			return implode("\n\n", $this->getReturnPolicyParagraphs());
		}

		public function getReturnPolicyParagraphs() {
			return [
				'<p>Returns are accepted within 30 days of receiving your order, as long as the item comes back in the same condition it arrived in.</p>',
				'<p>In practice that means no new marks, damage or wear, and anything that came with it -- a dust jacket, an insert, the packaging -- still with it.</p>',
				'<p>To start a return, get in touch through the <a href="/contact.php">contact page</a> with your order details, and we will tell you where to send it.  Once the item arrives and its condition is checked, the refund goes back to the original payment method.</p>',
				'<p>If an item arrived damaged, or is not what was described, tell us and we will put it right; the 30 days and the condition rule do not stand in the way of that.</p>',
			];
		}

		public function GetHTMLFormatData_Title() {
			return $this->header_title_text = 'Return Policy for ' . $this->master_record['Title'] . ($this->master_record['Subtitle'] ? ' : ' . $this->master_record['Subtitle'] : '');
		}
	}
?>