<?php

	trait DBFunctions {
		public function GetAllTablesMySQLSelect() {
			$this->mysql_tables = $this->db_admin->GetTableNames();
			$this->mysql_tables_select = [];
			
			foreach ($this->mysql_tables as $mysql_table_order => $mysql_table)
			{
				$this->mysql_tables_select[] = [
					'optionvalue'=>$mysql_table,
					'optiontitle'=>$mysql_table,
					'optionmouseover'=>'Dump the ' . $mysql_table . ' Table.',
				];
			}
		}
	}

?>