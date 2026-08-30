<?php

			// TOC:
			//
			//		Part 1: Empties
			//	1. Empty Arrays
			//
			//		Part 2: Element Inserts
			//	1. Inserts
			//	2. Already Existing
			//
			//		Part 3: Array inserts
			//	1. Empty Sorted Arrays
			//	2. 1 Element Sorted Arrays
			//	3. Multi-Element Sorted Arrays
			//	4. Multi-Multi-Element Sorted Arrays

	trait ArrayInsert {
						// Empties
						// --------------------------------------------------------
						
					// Empty Arrays
					// ------------------------------
					// ------------------------------
					// ------------------------------
					
		public function testinsertElementIntoSortedArray_emptyArrays() {
			$this->assertEqualsCanonicalizing(
				[1],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[],
					'element'=>1,
				]),
				'1 for Array []',
			);
			
			$this->assertEqualsCanonicalizing(
				[100],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[],
					'element'=>100,
				]),
				'100 for Array []',
			);
			
			return TRUE;
		}
						// Element Inserts
						// --------------------------------------------------------
		
					// Element Inserts: 1 to 2
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testinsertElementIntoSortedArray_1to2() {
			$this->assertEqualsCanonicalizing(
				[1,2],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[2],
					'element'=>1,
				]),
				'1 for Array [2]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1],
					'element'=>2,
				]),
				'2 for Array [1]',
			);
			
			return TRUE;
		}
		
					// Element Inserts: 1 to 3
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testinsertElementIntoSortedArray_1to3() {
			$this->assertEqualsCanonicalizing(
				[1,2,3],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[2,3],
					'element'=>1,
				]),
				'1 for Array [2,3]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,3],
					'element'=>2,
				]),
				'2 for Array [1,3]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2],
					'element'=>3,
				]),
				'3 for Array [1,2]',
			);
			
			return TRUE;
		}
		
					// Element Inserts: 1 to 4
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testinsertElementIntoSortedArray_1to4() {
			$this->assertEqualsCanonicalizing(
				[1,2,3,4],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[2,3,4],
					'element'=>1,
				]),
				'1 for Array [2,3,4]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,3,4],
					'element'=>2,
				]),
				'2 for Array [1,3,4]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,4],
					'element'=>3,
				]),
				'3 for Array [1,2,4]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3],
					'element'=>4,
				]),
				'4 for Array [1,2,3]',
			);
			
			return TRUE;
		}
		
					// Element Inserts: 1 to 5
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testinsertElementIntoSortedArray_1to5() {
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[2,3,4,5],
					'element'=>1,
				]),
				'1 for Array [2,3,4,5]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,3,4,5],
					'element'=>2,
				]),
				'2 for Array [1,3,4,5]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,4,5],
					'element'=>3,
				]),
				'3 for Array [1,2,4,5]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,5],
					'element'=>4,
				]),
				'4 for Array [1,2,3,5]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4],
					'element'=>5,
				]),
				'5 for Array [1,2,3,4]',
			);
			
			return TRUE;
		}
		
					// Element Inserts: 1 to 10
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testinsertElementIntoSortedArray_1to10() {
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[2,3,4,5,6,7,8,9,10],
					'element'=>1,
				]),
				'1 for Array [2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,3,4,5,6,7,8,9,10],
					'element'=>2,
				]),
				'2 for Array [1,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,4,5,6,7,8,9,10],
					'element'=>3,
				]),
				'3 for Array [1,2,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,5,6,7,8,9,10],
					'element'=>4,
				]),
				'4 for Array [1,2,3,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,6,7,8,9,10],
					'element'=>5,
				]),
				'5 for Array [1,2,3,4,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,7,8,9,10],
					'element'=>6,
				]),
				'6 for Array [1,2,3,4,5,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,8,9,10],
					'element'=>7,
				]),
				'7 for Array [1,2,3,4,5,6,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,9,10],
					'element'=>8,
				]),
				'8 for Array [1,2,3,4,5,6,7,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,10],
					'element'=>9,
				]),
				'9 for Array [1,2,3,4,5,6,7,8,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9],
					'element'=>10,
				]),
				'10 for Array [1,2,3,4,5,6,7,8,9]',
			);
			
			return TRUE;
		}
		
					// Element Inserts: 1 to 11
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testinsertElementIntoSortedArray_1to11() {
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[2,3,4,5,6,7,8,9,10,11],
					'element'=>1,
				]),
				'1 for Array [2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,3,4,5,6,7,8,9,10,11],
					'element'=>2,
				]),
				'2 for Array [1,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,4,5,6,7,8,9,10,11],
					'element'=>3,
				]),
				'3 for Array [1,2,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,5,6,7,8,9,10,11],
					'element'=>4,
				]),
				'4 for Array [1,2,3,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,6,7,8,9,10,11],
					'element'=>5,
				]),
				'5 for Array [1,2,3,4,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,7,8,9,10,11],
					'element'=>6,
				]),
				'6 for Array [1,2,3,4,5,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,8,9,10,11],
					'element'=>7,
				]),
				'7 for Array [1,2,3,4,5,6,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,9,10,11],
					'element'=>8,
				]),
				'8 for Array [1,2,3,4,5,6,7,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,10,11],
					'element'=>9,
				]),
				'9 for Array [1,2,3,4,5,6,7,8,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,11],
					'element'=>10,
				]),
				'10 for Array [1,2,3,4,5,6,7,8,9,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>11,
				]),
				'11 for Array [1,2,3,4,5,6,7,8,9,10]',
			);
			
			return TRUE;
		}
		
					// Element Inserts: 1 to 100
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testinsertElementIntoSortedArray_1to100() {
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
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>1,
				]),
				'1 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>2,
				]),
				'2 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>3,
				]),
				'3 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>4,
				]),
				'4 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>5,
				]),
				'5 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>6,
				]),
				'6 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>7,
				]),
				'7 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>8,
				]),
				'8 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>9,
				]),
				'9 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>10,
				]),
				'10 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>11,
				]),
				'11 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>12,
				]),
				'12 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>13,
				]),
				'13 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>14,
				]),
				'14 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>15,
				]),
				'15 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>16,
				]),
				'16 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>17,
				]),
				'17 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>18,
				]),
				'18 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,20,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>19,
				]),
				'19 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,
						21,22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>20,
				]),
				'20 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						22,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>21,
				]),
				'21 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,23,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>22,
				]),
				'22 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,24,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>23,
				]),
				'23 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,25,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>24,
				]),
				'24 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,26,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>25,
				]),
				'25 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,27,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>26,
				]),
				'26 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,28,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>27,
				]),
				'27 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,29,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>28,
				]),
				'28 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,30,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>29,
				]),
				'29 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,
						31,32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>30,
				]),
				'30 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						32,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>31,
				]),
				'31 for Array [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[
						1,2,3,4,5,6,7,8,9,10,
						11,12,13,14,15,16,17,18,19,20,
						21,22,23,24,25,26,27,28,29,30,
						31,33,34,35,36,37,38,39,40,
						41,42,43,44,45,46,47,48,49,50,
						51,52,53,54,55,56,57,58,59,60,
						61,62,63,64,65,66,67,68,69,70,
						71,72,73,74,75,76,77,78,79,80,
						81,82,83,84,85,86,87,88,89,90,
						91,92,93,94,95,96,97,98,99,100,
					],
					'element'=>32,
				]),
				'32 for Array [1 to 100]',
			);
			
			return TRUE;
		}
		
						// Element Already Existing
						// --------------------------------------------------------
		
					// Element Already Existing Keys - 1 to 10
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testinsertElementIntoSortedArray_alreadyExistingKey_1to10() {
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>1,
				]),
				'Already Exists - 1 for [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>2,
				]),
				'Already Exists - 2 for [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>3,
				]),
				'Already Exists - 3 for [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>4,
				]),
				'Already Exists - 4 for [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>5,
				]),
				'Already Exists - 5 for [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>6,
				]),
				'Already Exists - 6 for [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>7,
				]),
				'Already Exists - 7 for [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>8,
				]),
				'Already Exists - 8 for [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>9,
				]),
				'Already Exists - 9 for [1,2,3,4,5,6,7,8,9,10]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10],
					'element'=>10,
				]),
				'Already Exists - 10 for [1,2,3,4,5,6,7,8,9,10]',
			);
			
			return TRUE;
		}
		
					// Element Already Existing Keys - 1 to 11
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testinsertElementIntoSortedArray_alreadyExistingKey_1to11() {
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>1,
				]),
				'Already Exists - 1 for [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>2,
				]),
				'Already Exists - 2 for [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>3,
				]),
				'Already Exists - 3 for [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>4,
				]),
				'Already Exists - 4 for [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>5,
				]),
				'Already Exists - 5 for [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>6,
				]),
				'Already Exists - 6 for [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>7,
				]),
				'Already Exists - 7 for [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>8,
				]),
				'Already Exists - 8 for [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>9,
				]),
				'Already Exists - 9 for [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>10,
				]),
				'Already Exists - 10 for [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10,11],
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>[1,2,3,4,5,6,7,8,9,10,11],
					'element'=>10,
				]),
				'Already Exists - 11 for [1,2,3,4,5,6,7,8,9,10,11]',
			);
			
			return TRUE;
		}
		
					// Element Already Existing Keys - 1 to 100
					// ------------------------------
					// ------------------------------
					// ------------------------------
		
		public function testinsertElementIntoSortedArray_alreadyExistingKey_1to100() {
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
			
			$second_one_to_100_array = [
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
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>$second_one_to_100_array,
					'element'=>1,
				]),
				'Already Exists - 1 for [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>$second_one_to_100_array,
					'element'=>2,
				]),
				'Already Exists - 2 for [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>$second_one_to_100_array,
					'element'=>3,
				]),
				'Already Exists - 3 for [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>$second_one_to_100_array,
					'element'=>4,
				]),
				'Already Exists - 4 for [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>$second_one_to_100_array,
					'element'=>5,
				]),
				'Already Exists - 5 for [1 to 100]',
			);
			
			$this->assertEqualsCanonicalizing(
				$one_to_100_array,
				$this->db_file_cache->insertElementIntoSortedArray([
					'array'=>$second_one_to_100_array,
					'element'=>10,
				]),
				'Already Exists - 10 for [1 to 100]',
			);
			
			return TRUE;
		}
		
						// Array Inserts
						// --------------------------------------------------------
		
					// Array Inserts - Empty Sorted Arrays
					// ------------------------------
					// ------------------------------
					// ------------------------------
					
		public function testinsertArrayIntoSortedArray_emptySortedArrays() {
			$this->assertEqualsCanonicalizing(
				[1],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[],
					'arr2'=>[1],
				]),
				'Array[1](arr2) for Array[](arr1)',
			);
			
			return TRUE;
		}
		
					// Array Inserts - 1 Element Sorted Arrays
					// ------------------------------
					// ------------------------------
					// ------------------------------
					
		public function testinsertArrayIntoSortedArray_1ElementSortedArrays() {
			$this->assertEqualsCanonicalizing(
				[1,2],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[2],
					'arr2'=>[1],
				]),
				'Array[1](arr2) for Array[2](arr1)',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[1],
					'arr2'=>[2],
				]),
				'Array[2](arr2) for Array[1](arr1)',
			);
			
			return TRUE;
		}
		
					// Array Inserts - Multi-Element Sorted Arrays
					// ------------------------------
					// ------------------------------
					// ------------------------------
					
		public function testinsertArrayIntoSortedArray_MultiElementSortedArrays() {
			$this->assertEqualsCanonicalizing(
				[1,2,3],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[2,3],
					'arr2'=>[1],
				]),
				'Array[1](arr2) for Array[2,3](arr1)',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[1],
					'arr2'=>[2,3],
				]),
				'Array[2,3](arr2) for Array[1](arr1)',
			);
			
			return TRUE;
		}
		
					// Array Inserts - Multi-Multi-Element Sorted Arrays
					// ------------------------------
					// ------------------------------
					// ------------------------------
					
		public function testinsertArrayIntoSortedArray_MultiMultiElementSortedArrays() {
			$this->assertEqualsCanonicalizing(
				[1,2,3,4],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[2,4,],
					'arr2'=>[1,3,],
				]),
				'Array[1,3](arr2) for Array[2,4](arr1)',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[2,4,5,],
					'arr2'=>[1,3,],
				]),
				'Array[1,3](arr2) for Array[2,4,5](arr1)',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[2,4,],
					'arr2'=>[1,3,5],
				]),
				'Array[1,3,5](arr2) for Array[2,4](arr1)',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[2,4,6],
					'arr2'=>[1,3,5],
				]),
				'Array[1,3,5](arr2) for Array[2,4,6](arr1)',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[2,4,6,7],
					'arr2'=>[1,3,5],
				]),
				'Array[1,3,5](arr2) for Array[2,4,6,7](arr1)',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[2,4,6],
					'arr2'=>[1,3,5,7],
				]),
				'Array[1,3,5,7](arr2) for Array[2,4,6](arr1)',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[2,4,6,8],
					'arr2'=>[1,3,5,7],
				]),
				'Array[1,3,5,7](arr2) for Array[2,4,6,8](arr1)',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[2,4,6,8,9],
					'arr2'=>[1,3,5,7],
				]),
				'Array[1,3,5,7](arr2) for Array[2,4,6,8,9](arr1)',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[2,4,6,8],
					'arr2'=>[1,3,5,7,9],
				]),
				'Array[1,3,5,7,9](arr2) for Array[2,4,6,8](arr1)',
			);
			
			$this->assertEqualsCanonicalizing(
				[1,2,3,4,5,6,7,8,9,10],
				$this->db_file_cache->insertArrayIntoSortedArray([
					'arr1'=>[2,4,6,8,10],
					'arr2'=>[1,3,5,7,9],
				]),
				'Array[1,3,5,7,9](arr2) for Array[2,4,6,8,10](arr1)',
			);
			
			return TRUE;
		}
	}
?>