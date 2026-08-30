<?php

			// TOC:
			//
			//	testfindElementInSortedArray_false_...
			//
			//		testfindElementInSortedArray_false_emptyArrays()
			//		testfindElementInSortedArray_false_missingFromArrays()
			//
			//	testfindElementInSortedArray_true_...
			//
			//		testfindElementInSortedArray_true_1to2()
			//		testfindElementInSortedArray_true_1to3()
			//		testfindElementInSortedArray_true_1to4()
			//		testfindElementInSortedArray_true_1to5()
			//		testfindElementInSortedArray_true_1to10()
			//		testfindElementInSortedArray_true_1to11()
			//		testfindElementInSortedArray_true_1to100()

	trait ArrayContains {
						// FALSE
						// --------------------------------------------------------
						
					// Empty Arrays
					// ------------------------------
					// ------------------------------
					// ------------------------------
					
		public function testfindElementInSortedArray_false_emptyArrays() {
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[],
					'element'=>1,
				]),
				'FALSE: 1 for Array []',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[],
					'element'=>2,
				]),
				'FALSE: 2 for Array []',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[],
					'element'=>3,
				]),
				'FALSE: 3 for Array []',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[],
					'element'=>4,
				]),
				'FALSE: 4 for Array []',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[],
					'element'=>5,
				]),
				'FALSE: 5 for Array []',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[],
					'element'=>6,
				]),
				'FALSE: 6 for Array []',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[],
					'element'=>7,
				]),
				'FALSE: 7 for Array []',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[],
					'element'=>8,
				]),
				'FALSE: 8 for Array []',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[],
					'element'=>9,
				]),
				'FALSE: 9 for Array []',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[],
					'element'=>10,
				]),
				'FALSE: 10 for Array []',
			);
			
			return TRUE;
		}
						
					// Missing from Array
					// ------------------------------
					// ------------------------------
					// ------------------------------
					
		public function testfindElementInSortedArray_false_missingFromArrays() {
			$test_array = $this->one_thousand_array();
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$test_array,
					'element'=>1234,
				]),
				'FALSE: 1234 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$test_array,
					'element'=>12345,
				]),
				'FALSE: 12345 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$test_array,
					'element'=>123456,
				]),
				'FALSE: 123456 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$test_array,
					'element'=>1234567,
				]),
				'FALSE: 1234567 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$test_array,
					'element'=>12345678,
				]),
				'FALSE: 12345678 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				FALSE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$test_array,
					'element'=>123456789,
				]),
				'FALSE: 123456789 for Array [1 to 1000]',
			);
			
			return TRUE;
		}
		
						// TRUE
						// --------------------------------------------------------
		
					// Element Contains: 1 to 2
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testfindElementInSortedArray_true_1to2() {
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2],
					'element'=>1,
				]),
				'TRUE: 1 for Array [1,2]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2],
					'element'=>2,
				]),
				'TRUE: 2 for Array [1,2]',
			);
			
			return TRUE;
		}
		
					// Element Contains: 1 to 3
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testfindElementInSortedArray_true_1to3() {
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3],
					'element'=>1,
				]),
				'TRUE: 1 for Array [1,2,3]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3],
					'element'=>2,
				]),
				'TRUE: 2 for Array [1,2,3]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3],
					'element'=>3,
				]),
				'TRUE: 3 for Array [1,2,3]',
			);
			
			return TRUE;
		}
		
					// Element Contains: 1 to 4
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testfindElementInSortedArray_true_1to4() {
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4],
					'element'=>1,
				]),
				'TRUE: 1 for Array [1,2,3,4]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4],
					'element'=>2,
				]),
				'TRUE: 2 for Array [1,2,3,4]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4],
					'element'=>3,
				]),
				'TRUE: 3 for Array [1,2,3,4]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4],
					'element'=>4,
				]),
				'TRUE: 4 for Array [1,2,3,4]',
			);
			
			return TRUE;
		}
		
					// Element Contains: 1 to 5
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testfindElementInSortedArray_true_1to5() {
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5],
					'element'=>1,
				]),
				'TRUE: 1 for Array [1,2,3,4,5]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5],
					'element'=>2,
				]),
				'TRUE: 2 for Array [1,2,3,4,5]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5],
					'element'=>3,
				]),
				'TRUE: 3 for Array [1,2,3,4,5]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5],
					'element'=>4,
				]),
				'TRUE: 4 for Array [1,2,3,4,5]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5],
					'element'=>5,
				]),
				'TRUE: 5 for Array [1,2,3,4,5]',
			);
			
			return TRUE;
		}
		
					// Element Contains: 1 to 10
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testfindElementInSortedArray_true_1to10() {
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>1,
				]),
				'1 for Array [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>2,
				]),
				'2 for Array [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>3,
				]),
				'3 for Array [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>4,
				]),
				'4 for Array [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>5,
				]),
				'5 for Array [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>6,
				]),
				'6 for Array [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>7,
				]),
				'7 for Array [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>8,
				]),
				'8 for Array [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>9,
				]),
				'9 for Array [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>10,
				]),
				'10 for Array [1,2,3,4,5,6,7,8,9,10]',
			);
			
			return TRUE;
		}
		
					// Element Contains: 1 to 11
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testfindElementInSortedArray_true_1to11() {
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>1,
				]),
				'TRUE: 1 for Array [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>2,
				]),
				'TRUE: 2 for Array [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>3,
				]),
				'TRUE: 3 for Array [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>4,
				]),
				'TRUE: 4 for Array [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>5,
				]),
				'TRUE: 5 for Array [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>6,
				]),
				'TRUE: 6 for Array [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>7,
				]),
				'TRUE: 7 for Array [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>8,
				]),
				'TRUE: 8 for Array [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>9,
				]),
				'TRUE: 9 for Array [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>10,
				]),
				'TRUE: 10 for Array [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>11,
				]),
				'TRUE: 11 for Array [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			return TRUE;
		}
		
					// Element Contains: 1 to 100
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testfindElementInSortedArray_true_1to100() {
			$one_to_100_array = [
				1,2,3,4,5,6,7,8,9,10,
				11,12,13,14,15,16,17,18,19,20,
				21,22,23,24,25,26,27,28,29,30,
				31,32,33,34,35,36,37,38,39,40,
				41,42,43,44,45,46,47,48,49,50,
				51,52,53,54,55,56,57,58,59,60,
				61,62,63,64,65,66,67,68,69,70,
				71,72,73,74,75,76,77,78,79,80,
				81,82,83,84,85,86,87,88,89,90,
				91,92,93,94,95,96,97,98,99,100,
			];
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>1,
				]),
				'TRUE: 1 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>2,
				]),
				'TRUE: 2 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>3,
				]),
				'TRUE: 3 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>4,
				]),
				'TRUE: 4 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>5,
				]),
				'TRUE: 5 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>6,
				]),
				'TRUE: 6 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>7,
				]),
				'TRUE: 7 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>8,
				]),
				'TRUE: 8 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>9,
				]),
				'TRUE: 9 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>10,
				]),
				'TRUE: 10 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>11,
				]),
				'TRUE: 11 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>12,
				]),
				'TRUE: 12 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>13,
				]),
				'TRUE: 13 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>14,
				]),
				'TRUE: 14 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>15,
				]),
				'TRUE: 15 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>16,
				]),
				'TRUE: 16 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>17,
				]),
				'TRUE: 17 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>18,
				]),
				'TRUE: 18 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>19,
				]),
				'TRUE: 19 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>20,
				]),
				'TRUE: 20 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>21,
				]),
				'TRUE: 21 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>22,
				]),
				'TRUE: 22 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>23,
				]),
				'TRUE: 23 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>24,
				]),
				'TRUE: 24 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>25,
				]),
				'TRUE: 25 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>26,
				]),
				'TRUE: 26 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>27,
				]),
				'TRUE: 27 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>28,
				]),
				'TRUE: 28 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>29,
				]),
				'TRUE: 29 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>30,
				]),
				'TRUE: 30 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>31,
				]),
				'TRUE: 31 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>32,
				]),
				'TRUE: 32 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>33,
				]),
				'TRUE: 33 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>34,
				]),
				'TRUE: 34 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>35,
				]),
				'TRUE: 35 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>36,
				]),
				'TRUE: 36 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>37,
				]),
				'TRUE: 37 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>38,
				]),
				'TRUE: 38 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>39,
				]),
				'TRUE: 39 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>40,
				]),
				'TRUE: 40 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>41,
				]),
				'TRUE: 41 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>42,
				]),
				'TRUE: 42 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>43,
				]),
				'TRUE: 43 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>44,
				]),
				'TRUE: 44 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>45,
				]),
				'TRUE: 45 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>46,
				]),
				'TRUE: 46 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>47,
				]),
				'TRUE: 47 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>48,
				]),
				'TRUE: 48 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>49,
				]),
				'TRUE: 49 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>50,
				]),
				'TRUE: 50 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>51,
				]),
				'TRUE: 51 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>52,
				]),
				'TRUE: 52 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>53,
				]),
				'TRUE: 53 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>54,
				]),
				'TRUE: 54 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>55,
				]),
				'TRUE: 55 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>56,
				]),
				'TRUE: 56 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>57,
				]),
				'TRUE: 57 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>58,
				]),
				'TRUE: 58 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>59,
				]),
				'TRUE: 59 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>60,
				]),
				'TRUE: 60 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>61,
				]),
				'TRUE: 61 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>62,
				]),
				'TRUE: 62 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>63,
				]),
				'TRUE: 63 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>64,
				]),
				'TRUE: 64 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>65,
				]),
				'TRUE: 65 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>66,
				]),
				'TRUE: 66 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>67,
				]),
				'TRUE: 67 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>68,
				]),
				'TRUE: 68 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>69,
				]),
				'TRUE: 69 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>70,
				]),
				'TRUE: 70 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>71,
				]),
				'TRUE: 71 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>72,
				]),
				'TRUE: 72 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>73,
				]),
				'TRUE: 73 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>74,
				]),
				'TRUE: 74 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>75,
				]),
				'TRUE: 75 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>76,
				]),
				'TRUE: 76 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>77,
				]),
				'TRUE: 77 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>78,
				]),
				'TRUE: 78 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>79,
				]),
				'TRUE: 79 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>80,
				]),
				'TRUE: 80 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>81,
				]),
				'TRUE: 81 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>82,
				]),
				'TRUE: 82 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>83,
				]),
				'TRUE: 83 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>84,
				]),
				'TRUE: 84 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>85,
				]),
				'TRUE: 85 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>86,
				]),
				'TRUE: 86 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>87,
				]),
				'TRUE: 87 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>88,
				]),
				'TRUE: 88 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>89,
				]),
				'TRUE: 89 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>90,
				]),
				'TRUE: 90 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>91,
				]),
				'TRUE: 91 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>92,
				]),
				'TRUE: 92 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>93,
				]),
				'TRUE: 93 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>94,
				]),
				'TRUE: 94 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>95,
				]),
				'TRUE: 95 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>96,
				]),
				'TRUE: 96 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>97,
				]),
				'TRUE: 97 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>98,
				]),
				'TRUE: 98 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>99,
				]),
				'TRUE: 99 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_100_array,
					'element'=>100,
				]),
				'TRUE: 100 for Array [1 to 100]',
			);
			
			return TRUE;
		}
		
					// Element Contains: 1 to 1000
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function one_thousand_array() {
			return [
				1,2,3,4,5,6,7,8,9,10,
				11,12,13,14,15,16,17,18,19,20,
				21,22,23,24,25,26,27,28,29,30,
				31,32,33,34,35,36,37,38,39,40,
				41,42,43,44,45,46,47,48,49,50,
				51,52,53,54,55,56,57,58,59,60,
				61,62,63,64,65,66,67,68,69,70,
				71,72,73,74,75,76,77,78,79,80,
				81,82,83,84,85,86,87,88,89,90,
				91,92,93,94,95,96,97,98,99,100,
				
				101,102,103,104,105,106,107,108,109,110,
				111,112,113,114,115,116,117,118,119,120,
				121,122,123,124,125,126,127,128,129,130,
				131,132,133,134,135,136,137,138,139,140,
				141,142,143,144,145,146,147,148,149,150,
				151,152,153,154,155,156,157,158,159,160,
				161,162,163,164,165,166,167,168,169,170,
				171,172,173,174,175,176,177,178,179,180,
				181,182,183,184,185,186,187,188,189,190,
				191,192,193,194,195,196,197,198,199,200,
				
				201,202,203,204,205,206,207,208,209,210,
				211,212,213,214,215,216,217,218,219,220,
				221,222,223,224,225,226,227,228,229,230,
				231,232,233,234,235,236,237,238,239,240,
				241,242,243,244,245,246,247,248,249,250,
				251,252,253,254,255,256,257,258,259,260,
				261,262,263,264,265,266,267,268,269,270,
				271,272,273,274,275,276,277,278,279,280,
				281,282,283,284,285,286,287,288,289,290,
				291,292,293,294,295,296,297,298,299,300,
				
				301,302,303,304,305,306,307,308,309,310,
				311,312,313,314,315,316,317,318,319,320,
				321,322,323,324,325,326,327,328,329,330,
				331,332,333,334,335,336,337,338,339,340,
				341,342,343,344,345,346,347,348,349,350,
				351,352,353,354,355,356,357,358,359,360,
				361,362,363,364,365,366,367,368,369,370,
				371,372,373,374,375,376,377,378,379,380,
				381,382,383,384,385,386,387,388,389,390,
				391,392,393,394,395,396,397,398,399,400,
				
				401,402,403,404,405,406,407,408,409,410,
				411,412,413,414,415,416,417,418,419,420,
				421,422,423,424,425,426,427,428,429,430,
				431,432,433,434,435,436,437,438,439,440,
				441,442,443,444,445,446,447,448,449,450,
				451,452,453,454,455,456,457,458,459,460,
				461,462,463,464,465,466,467,468,469,470,
				471,472,473,474,475,476,477,478,479,480,
				481,482,483,484,485,486,487,488,489,490,
				491,492,493,494,495,496,497,498,499,500,
				
				501,502,503,504,505,506,507,508,509,510,
				511,512,513,514,515,516,517,518,519,520,
				521,522,523,524,525,526,527,528,529,530,
				531,532,533,534,535,536,537,538,539,540,
				541,542,543,544,545,546,547,548,549,550,
				551,552,553,554,555,556,557,558,559,560,
				561,562,563,564,565,566,567,568,569,570,
				571,572,573,574,575,576,577,578,579,580,
				581,582,583,584,585,586,587,588,589,590,
				591,592,593,594,595,596,597,598,599,600,
				
				601,602,603,604,605,606,607,608,609,610,
				611,612,613,614,615,616,617,618,619,620,
				621,622,623,624,625,626,627,628,629,630,
				631,632,633,634,635,636,637,638,639,640,
				641,642,643,644,645,646,647,648,649,650,
				651,652,653,654,655,656,657,658,659,660,
				661,662,663,664,665,666,667,668,669,670,
				671,672,673,674,675,676,677,678,679,680,
				681,682,683,684,685,686,687,688,689,690,
				691,692,693,694,695,696,697,698,699,700,
				
				701,702,703,704,705,706,707,708,709,710,
				711,712,713,714,715,716,717,718,719,720,
				721,722,723,724,725,726,727,728,729,730,
				731,732,733,734,735,736,737,738,739,740,
				741,742,743,744,745,746,747,748,749,750,
				751,752,753,754,755,756,757,758,759,760,
				761,762,763,764,765,766,767,768,769,770,
				771,772,773,774,775,776,777,778,779,780,
				781,782,783,784,785,786,787,788,789,790,
				791,792,793,794,795,796,797,798,799,800,
				
				801,802,803,804,805,806,807,808,809,810,
				811,812,813,814,815,816,817,818,819,820,
				821,822,823,824,825,826,827,828,829,830,
				831,832,833,834,835,836,837,838,839,840,
				841,842,843,844,845,846,847,848,849,850,
				851,852,853,854,855,856,857,858,859,860,
				861,862,863,864,865,866,867,868,869,870,
				871,872,873,874,875,876,877,878,879,880,
				881,882,883,884,885,886,887,888,889,890,
				891,892,893,894,895,896,897,898,899,900,
				
				901,902,903,904,905,906,907,908,909,910,
				911,912,913,914,915,916,917,918,919,920,
				921,922,923,924,925,926,927,928,929,930,
				931,932,933,934,935,936,937,938,939,940,
				941,942,943,944,945,946,947,948,949,950,
				951,952,953,954,955,956,957,958,959,960,
				961,962,963,964,965,966,967,968,969,970,
				971,972,973,974,975,976,977,978,979,980,
				981,982,983,984,985,986,987,988,989,990,
				991,992,993,994,995,996,997,998,999,1000,
			];
		}
		
		public function testfindElementInSortedArray_true_1to1000() {
			$one_to_1000_array = $this->one_thousand_array();
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>1,
				]),
				'TRUE: 1 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>2,
				]),
				'TRUE: 2 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>3,
				]),
				'TRUE: 3 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>4,
				]),
				'TRUE: 4 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>5,
				]),
				'TRUE: 5 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>6,
				]),
				'TRUE: 6 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>7,
				]),
				'TRUE: 7 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>8,
				]),
				'TRUE: 8 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>9,
				]),
				'TRUE: 9 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>10,
				]),
				'TRUE: 10 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>11,
				]),
				'TRUE: 11 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>12,
				]),
				'TRUE: 12 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>13,
				]),
				'TRUE: 13 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>14,
				]),
				'TRUE: 14 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>15,
				]),
				'TRUE: 15 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>16,
				]),
				'TRUE: 16 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>17,
				]),
				'TRUE: 17 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>18,
				]),
				'TRUE: 18 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>19,
				]),
				'TRUE: 19 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>20,
				]),
				'TRUE: 20 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>21,
				]),
				'TRUE: 21 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>22,
				]),
				'TRUE: 22 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>23,
				]),
				'TRUE: 23 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>24,
				]),
				'TRUE: 24 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>25,
				]),
				'TRUE: 25 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>26,
				]),
				'TRUE: 26 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>27,
				]),
				'TRUE: 27 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>28,
				]),
				'TRUE: 28 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>29,
				]),
				'TRUE: 29 for Array [1 to 1000]',
			);
			
			$this->assertEqualsCanonicalizing(
				TRUE,
				$this->db_file_cache->findElementInSortedArray([
					'array'=>$one_to_1000_array,
					'element'=>30,
				]),
				'TRUE: 30 for Array [1 to 1000]',
			);
			
			return TRUE;
		}
	}
?>