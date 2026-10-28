<?php

	/*
		The house ads a signed-in reader has closed, and never wants again.

		javascript/house-ads.js shows the network's sites advertising one
		another.  A reader who presses its x on an ad has it gone for the
		visit; signed in, it is gone for good, kept here, one row per ad
		per reader.  The codes are the ads' own, from /houseads/catalogue.json,
		and nothing here needs to know which ads exist: a code for an ad
		that has since been retired is a row that matches nothing.

		Pages carrying a reader's list are never cached -- a session cookie
		means a live render, see PageCache -- so the list goes into the page
		itself and the script needs no second request to learn it.
	*/

	class HouseAds {
		public $handler;

		public function __construct($args) {
			$this->handler = $args['handler'];
		}

			// ValidCode()
			// Tests: HouseAdsTest::testValidCode()
			// Test file: tests/src/classes/Networking/HouseAdsTest.php
		public function ValidCode($args) {
			$code = $args['code'];

			return is_string($code) && preg_match('/^[a-z0-9-]{1,64}$/', $code) === 1;
		}

		public function UserId() {
			$authentication = $this->handler->authentication ?? NULL;

			if(!is_object($authentication) || empty($authentication->user_session['User.id'])) {
				return 0;
			}

			return (int)$authentication->user_session['User.id'];
		}

			/*
				Every code the signed-in reader has closed, or none.  A failed
				lookup is none too: the worst it costs is an ad shown again.
			*/

		public function HiddenCodes() {
			$user_id = $this->UserId();

			if(!$user_id) {
				return [];
			}

			$hidden = $this->handler->db_access->GetRecords([
				'type'=>'UserHiddenHouseAd',
				'definition'=>[
					'Userid'=>$user_id,
				],
			]);

			if(!is_array($hidden) || !empty($hidden['line'])) {
				return [];
			}

			$codes = [];

			foreach($hidden as $row) {
				if(is_array($row) && $this->ValidCode(['code'=>$row['AdCode'] ?? NULL])) {
					$codes[$row['AdCode']] = TRUE;
				}
			}

			return array_keys($codes);
		}

			/*
				Closing an ad twice is already done.  TRUE means the reader's
				list now holds the code.
			*/

		public function Hide($args) {
			$code = $args['code'];

			if(!$this->ValidCode(['code'=>$code]) || !$this->UserId()) {
				return FALSE;
			}

			if(in_array($code, $this->HiddenCodes(), TRUE)) {
				return TRUE;
			}

			$created = $this->handler->db_access->CreateRecord([
				'type'=>'UserHiddenHouseAd',
				'definition'=>[
					'Userid'=>$this->UserId(),
					'AdCode'=>$code,
				],
			]);

			return is_array($created) && empty($created['line']) && !empty($created['id']);
		}

			/*
				house-ads.js posts code=<ad> here when a signed-in reader
				closes an ad.  The reader is looked up here because nothing
				else has yet: Authenticate() runs with the script, and this
				answers before any script is loaded.

				A post from another site is refused, so a page elsewhere
				cannot quietly empty a reader's ads.  It would be a small
				harm, but it costs one comparison.
			*/

		public function HandleHideRequest() {
			$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

			$host = explode(':', (string)($_SERVER['HTTP_HOST'] ?? ''))[0];

			if($origin !== '' && strcasecmp((string)parse_url($origin, PHP_URL_HOST), $host) !== 0) {
				http_response_code(403);
				return FALSE;
			}

			$this->handler->authentication->CheckCurrentAuthentication();

			if(!$this->UserId()) {
				http_response_code(403);
				return FALSE;
			}

			if(!$this->Hide(['code'=>$_POST['code'] ?? NULL])) {
				http_response_code(400);
				return FALSE;
			}

			http_response_code(204);

			return TRUE;
		}
	}

?>
