<?php

	require_once(GGCMS_DIR . 'modules/html/modify-records.php');

		/*
			An entry's prices on a modify.php form: the amount and its
			currency.  A price with an amount is shown; the currency alone
			is not a price, because the select always has one chosen.

			The currencies are the Currency enum's, by way of
			modify.php's PriceCurrencies(), so a currency added to the
			schema appears here without this file changing.
		*/

	class module_modifyprice extends module_modifyrecords {
		public function Records() {
			return $this->that->price;
		}

		public function IsShown($args) {
			$price = $args['record'];

			return $price && strlen((string)$price['PriceValue']);
		}

		public function Slug() {
			return 'price';
		}

		public function AddLabel() {
			return 'Add Price';
		}

		public function DisplayFields($args) {
			$record = $args['record'];
			$mode = $args['mode'];

			print('Price : ');

			$amount_args = ['type'=>'text', 'name'=>$this->Name(['name'=>'PriceValue', 'mode'=>$mode]), 'size'=>12, 'maxlength'=>11];

			if($mode === 'row') {
				$amount_args['value'] = $record['PriceValue'];
			}

			$this->form->DisplayFormField($amount_args);

			print('Currency : ');

			$this->form->DisplayFormField([
				'type'=>'select',
				'name'=>$this->Name(['name'=>'price_Currency', 'mode'=>$mode]),
				'options'=>$this->CurrencyOptions(),
				'selected'=>($mode === 'row' && strlen((string)$record['Currency'])) ? $record['Currency'] : 'USD',
			]);

			return TRUE;
		}

		public function CurrencyOptions() {
			$options = [];

			foreach($this->that->PriceCurrencies() as $currency) {
				$options[] = [
					'optionvalue'=>$currency,
					'optiontitle'=>$currency,
					'optionmouseover'=>'Set Currency to ' . $currency,
				];
			}

			return $options;
		}
	}

?>
