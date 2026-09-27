<?php

	trait DBAccess {
		public $db_link;
		
		/*
			The fourth argument to mysqli is the database, not the host.  It is
			named $host here because CLIAccess::setDomain() stores the first
			label of the domain -- revoltlib, from revoltlib.com -- in
			$this->host, and on this installation that label is the database
			name.

			The test was property_exists($this, $host) with $host still FALSE,
			so it asked whether this object has a property named "", which
			nothing does.  Every tool therefore connected with no database
			selected, whatever domain the operator had just been asked for.

			Tools that name the database in their queries never noticed, which
			is all of them, which is why this survived.
		*/

		public function setMySQLArgs() {
			$database = '';

			if(isset($this->host) && $this->host) {
				$database = $this->host;
			}

			return $this->db_link = new mysqli(
				ini_get("mysqli.default_host"),
				ini_get("mysqli.default_user"),
				ini_get("mysqli.default_pw"),
				$database,
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