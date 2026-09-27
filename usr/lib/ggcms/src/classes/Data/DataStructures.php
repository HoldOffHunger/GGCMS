<?php

	class DataStructures {
		public function __construct($args) {
		}
		
		public function cleanupArrayPiece($args) {
			$piece = trim($args['piece']);
			
			$first_letter = $piece[0];
			
			if($first_letter == '\'' || $first_letter == '"') {
				$piece = substr($piece, 1);
			}
			
			$last_letter = $piece[strlen($piece) - 1];
			
			if($last_letter == ',') {
				$piece = substr($piece, 0, -1);
			}
			
			$last_letter = $piece[strlen($piece) - 1];
			
			if($last_letter == '\'' || $last_letter == '"') {
				$piece = substr($piece, 0, -1);
			} else {
				if($last_letter == ']' || $last_letter == ')') {
					$piece = rtrim(substr($piece, 0, -1));
					
					$last_letter = $piece[strlen($piece) - 1];
					
					if($last_letter == '\'' || $last_letter == '"') {
						$piece = substr($piece, 0, -1);
					}
				}
			}
			
			return $piece;
		}
		
		public function findDuplicateArrayKeys($args) {
			$text = $args['text'];
			preg_match_all('/([\'"].*?[\'"][\s\n\r]*=>[\s\n\r]*[\'"]{0,1}.*?[\'"]{0,1})[\s\n\r]*[\]\),]{1}/', $text, $matches);
			
			$matches = $matches[0];
			$matched = [];
			$duplicates = [];
			
			for($i = 0; $i < count($matches); $i++) {
				$match = $matches[$i];
				$match_pieces = explode('=>', $match);
				$key = $this->cleanupArrayPiece(['piece'=>$match_pieces[0]]);
				$value = $this->cleanupArrayPiece(['piece'=>$match_pieces[1]]);
				
				if($matched[$key]) {
					if(!$duplicates[$key]) {
						$duplicates[$key] = [
							$matched[$key],
						];
					}
					
					$duplicates[$key][] = $value;
				} else {
					$matched[$key] = $value;
				}
			}
			
			return $duplicates;
		}
	}

?>