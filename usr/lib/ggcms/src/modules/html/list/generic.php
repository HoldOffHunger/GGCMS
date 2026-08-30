<?php

	class module_genericlist extends module_spacing {
			// Display List
		
		// -------------------------------------------------------------
		
		public function Display ($args) {
			$options = $args['options'];
			$list = $args['list'];
			
			$table_headers = $options['tableheaders'];
			$table_class = $options['tableclass'];
			$row_class = $options['rowclass'];
			$cell_class = $options['cellclass'];
			
			$table_start_args = [
				'tableclass'=>$table_class,
			];
			
			$this->Display_TableStart($table_start_args);
			
			$table_body_args = [
				'tableheaders'=>$table_headers,
				'rowclass'=>$row_class,
				'cellclass'=>$cell_class,
				'list'=>$list,
			];
			
			$this->Display_TableBody($table_body_args);
			
			$table_end_args = [
			];
			
			$this->Display_TableEnd($table_end_args);
		}
		
		public function Display_TableBody($args) {
			$table_headers = $args['tableheaders'];
			$row_class = $args['rowclass'];
			$cell_class = $args['cellclass'];
			$list = $args['list'];
			
			if($table_headers) {
				$table_row_start_args = [
					'rowclass'=>$row_class,
				];
				
				$this->Display_RowStart($table_row_start_args);
				
				$table_header_row = $list[0];
				unset($list[0]);
				
				$table_header_row_args = [
					'headerrow'=>$table_header_row,
				];
				
				if(is_array($cell_class)) {
					$table_header_row_args['cellclass'] = array_shift($cell_class);
				} else {
					$table_header_row_args['cellclass'] = $cell_class;
				}
				
				$this->Display_TableBodyHeader($table_header_row_args);
				
				$table_row_end_args = [
				];
				
				$this->Display_RowEnd($table_row_end_args);
				
				$cell_class = $args['cellclass'];
			}
			
			foreach ($list as $item) {
				$table_row_start_args = [
					'rowclass'=>$row_class,
				];
				
				$this->Display_RowStart($table_row_start_args);
				
				foreach ($item as $cell) {
					$table_cell_start_args = [
					];
					
					if(is_array($cell_class)) {
						$table_cell_start_args['cellclass'] = array_shift($cell_class);
					} else {
						$table_cell_start_args['cellclass'] = $cell_class;
					}
					$table_cell_start_args['contents'] = $cell;
					
					$this->Display_CellStart($table_cell_start_args);
					
					$table_cell_contents_args = [
						'contents'=>$cell,
					];
					
					$this->Display_CellContents($table_cell_contents_args);
					
					$table_cell_end_args = [
					];
					
					$this->Display_CellEnd($table_cell_end_args);
				}
				
				$table_row_end_args = [
				];
				
				$this->Display_RowEnd($table_row_end_args);
				
				$cell_class = $args['cellclass'];
			}
		}
		
		public function Display_CellContents($args) {
			$contents = $args['contents'];
			
			if(is_array($contents)) {
				print($contents['contents']);
			} else {
				if(!strlen($contents)) {
					$contents = '&nbsp;';
				}
				
				print($contents);
			}
			
			$this->DisplayDoubleLineBreak();
		}
		
		public function Display_CellStart($args) {
			$cell_class = $args['cellclass'];
			$contents = $args['contents'];
			
			print('<td');
			
			if($cell_class) {
				print(' class="' . $cell_class . '"');
			}
			
			if(is_array($contents)) {
				$mouseover = $contents['mouseover'];
				
				if($mouseover) {
					$mouseover = str_replace('"', '&quot;', $mouseover);
					print(' title="' . $mouseover . '"');
				}
			}
			
			print('>');
			
			$this->DisplayDoubleLineBreak();
		}
		
		public function Display_CellEnd($args) {
			print('</td>');
			
			$this->DisplayDoubleLineBreak();
		}
		
		public function Display_RowStart($args) {
			print('<tr');
			
			if($row_class) {
				print(' class="' . $row_class . '"');
			}
			
			print('>');
			
			$this->DisplayDoubleLineBreak();
		}
		
		public function Display_RowEnd($args) {
			print('</tr>');
			
			$this->DisplayDoubleLineBreak();
		}
		
		public function Display_TableBodyHeader($args) {
			$header_row = $args['headerrow'];
			$cell_class = $args['cellclass'];
			
			foreach ($header_row as $item) {
				print('<th');
				
				if($cell_class) {
					print(' class="' . $cell_class . '"');
				}
				
				print('>');
				
				$this->DisplayDoubleLineBreak();
				
				print($item);
				
				$this->DisplayDoubleLineBreak();
				
				print('</th>');
				
				$this->DisplayDoubleLineBreak();
			}
		}
		
		public function Display_TableStart($args) {
			$table_class = $args['tableclass'];
			
			print('<table');
			
			if($table_class) {
				print(' class="' . $table_class . '"');
			}
			
			print('>');
			
			$this->DisplayDoubleLineBreak();
		}
		
		public function Display_TableEnd($args) {
			print('</table>');
			
			$this->DisplayDoubleLineBreak();
		}
	}
?>