<?php

	trait DBAccess {
		public function setMySQLArgs() {
			$host = false;
			if(property_exists($this, $host)) {
				$host = $this->host;
			}
			
			return $this->db_link = new mysqli(
				ini_get("mysqli.default_host"),
				ini_get("mysqli.default_user"),
				ini_get("mysqli.default_pw"),
				$host,
				ini_get("mysqli.default_port")
			);
		}
		
		public function runQuery($args) {
			$query = $args['query'];
			
			$statement = $this->db_link->prepare($query);
			$objects = [];
			
#			print_r($query);
			$statement->execute();
			$result = $statement->get_result();
			
			if($result) {
				while ($row = $result->fetch_assoc()) {
					$format_row_args = [
						'row'=>$row,
					];
					$objects[] = $this->FillArraysFromDB_FormatRow($format_row_args);
				}
			} else {
				print('ERROR ON QUERY!');
			}
			
			return $objects;
		}
		
		public function FillArraysFromDB_FormatRow($args) {
			$row = $args['row'];
			
			$sub_tables = [];
			
			foreach ($row as $field_name => $field_value) {
				$first_row_field_name_char = substr($field_name, 0, 1);
				
				if($first_row_field_name_char === '.') {
					$row_explosion = explode('.', $field_name);
					
					$joined_table_name = $row_explosion[1];
					$joined_table_field = $row_explosion[2];
					
					if(!$sub_tables[$joined_table_name]) {
						$sub_tables[$joined_table_name] = [];
					}
					
					$sub_tables[$joined_table_name][$joined_table_field] = $field_value;
					unset($row[$field_name]);
				}
			}
			
			foreach ($sub_tables as $sub_table => $sub_table_fields) {
				$row[strtolower($sub_table)] = $sub_table_fields;
			}
			
			return $row;
		}
	}

?>