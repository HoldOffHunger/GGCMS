<?php

	trait BaseConversion {
		public $SelectableValue;
		
		public function SetConversionBases() {
			$this->SelectableValue = [];
			
			foreach(['Base64', 'Hexadecimal', 'EightBit', 'Binary'] as $base) {
				$this->SelectableValue[] = [
					'optionvalue'=>$base,
					'optiontitle'=>$base,
					'optionmouseover'=>'Convert to ' . $base,
				];
			}
			
			return TRUE;
		}
	}

?>