<?php

		/*
			The DB panel's links, as module_hideable lists them.
			Required by module_mastercpanel::Menu(), so $this is the panel.
		*/

			// Column #3, DB : List Args
		
		// -------------------------------------------------------------

	return [
		[
			'text'=>'BCE Database Tools',
			'link'=>' ',
			'mouseover'=>'BCE Database Tools',
			[
				'text'=>'Dump Tables',
				'link'=>'dbstatus.php?action=DumpTables',
				'mouseover'=>'Dump Selected Table(s) Records',
			],
			[
				'text'=>'Show Tables List (Names)',
				'link'=>'dbstatus.php?action=ShowTablesListNames',
				'mouseover'=>'View Raw Table Data',
			],
			[
				'text'=>'Show Tables List (Schemas)',
				'link'=>'dbstatus.php?action=ShowTablesListSchemas',
				'mouseover'=>'View Raw Table Data',
			],
			[
				'text'=>'View MySQL Equivalent of DB',
				'link'=>'dbstatus.php?action=MySQLDBEquivalent',
				'mouseover'=>'Generate MySQL Equivalent of Current DB Schema',
			],
		],
		[
			'text'=>'MySQL Information Functions',
			'link'=>' ',
			'mouseover'=>'MySQL STATUS Tables',
			[
				'text'=>'View MySQL Databases',
				'link'=>'dbstatus.php?action=ViewMySQLDatabases',
				'mouseover'=>'View MySQL Databases',
			],
			[
				'text'=>'View MySQL INFORMATION_SCHEMA Tables',
				'link'=>'dbstatus.php?action=ViewMySQLInformationSchemaTables',
				'mouseover'=>'View MySQL INFORMATION_SCHEMA Tables',
			],
			[
				'text'=>'View MySQL Character Sets Table',
				'link'=>'dbstatus.php?action=ViewMySQLCharacterSetsTable',
				'mouseover'=>'View MySQL Character Sets Table',
			],
			[
				'text'=>'View MySQL Collations Table',
				'link'=>'dbstatus.php?action=ViewMySQLCollationsTable',
				'mouseover'=>'View MySQL Collations Table',
			],
			[
				'text'=>'View MySQL Collation Character Set Applicability Table',
				'link'=>'dbstatus.php?action=ViewMySQLCollationCharacterSetApplicabilityTable',
				'mouseover'=>'View MySQL Collation Character Set Applicability Table',
			],
			[
				'text'=>'View MySQL Columns Table',
				'link'=>'dbstatus.php?action=ViewMySQLColumnsTable',
				'mouseover'=>'View MySQL Columns Table',
			],
			[
				'text'=>'View MySQL Column Privileges Table',
				'link'=>'dbstatus.php?action=ViewMySQLColumnPrivilegesTable',
				'mouseover'=>'View MySQL Column Privileges Table',
			],
			[
				'text'=>'View MySQL Engines Table',
				'link'=>'dbstatus.php?action=ViewMySQLEnginesTable',
				'mouseover'=>'View MySQL Engines Table',
			],
			[
				'text'=>'View MySQL Events Table',
				'link'=>'dbstatus.php?action=ViewMySQLEventsTable',
				'mouseover'=>'View MySQL Events Table',
			],
			[
				'text'=>'View MySQL Files Table',
				'link'=>'dbstatus.php?action=ViewMySQLFilesTable',
				'mouseover'=>'View MySQL Files Table',
			],
			[
				'text'=>'View MySQL Status Table',
				'link'=>'dbstatus.php?action=ViewMySQLStatusTable',
				'mouseover'=>'View MySQL Status Table',
			],
			[
				'text'=>'View MySQL Session Status Table',
				'link'=>'dbstatus.php?action=ViewMySQLSessionStatusTable',
				'mouseover'=>'View MySQL Session Status Table',
			],
			[
				'text'=>'View MySQL Global Status Table',
				'link'=>'dbstatus.php?action=ViewMySQLGlobalStatusTable',
				'mouseover'=>'View MySQL Global Status Table',
			],
			[
				'text'=>'View MySQL Global Variables Table',
				'link'=>'dbstatus.php?action=ViewMySQLGlobalVariablesTable',
				'mouseover'=>'View MySQL Global Variables Table',
			],
			[
				'text'=>'View MySQL Optimizer Trace Table',
				'link'=>'dbstatus.php?action=ViewMySQLOptimizerTraceTable',
				'mouseover'=>'View MySQL Optimizer Trace Table',
			],
			[
				'text'=>'View MySQL Session Variables Table',
				'link'=>'dbstatus.php?action=ViewMySQLSessionVariablesTable',
				'mouseover'=>'View MySQL Session Variables Table',
			],
			[
				'text'=>'View MySQL Key Column Usage Table',
				'link'=>'dbstatus.php?action=ViewMySQLKeyColumnUsageTable',
				'mouseover'=>'View MySQL Key Column Usage Table',
			],
			[
				'text'=>'View MySQL Parameters Table',
				'link'=>'dbstatus.php?action=ViewMySQLParametersTable',
				'mouseover'=>'View MySQL Parameters Table',
			],
			[
				'text'=>'View MySQL Partitions Table',
				'link'=>'dbstatus.php?action=ViewMySQLPartitionsTable',
				'mouseover'=>'View MySQL Parameters Table',
			],
			[
				'text'=>'View MySQL Plugins Table',
				'link'=>'dbstatus.php?action=ViewMySQLPluginsTable',
				'mouseover'=>'View MySQL Plugins Table',
			],
			[
				'text'=>'View MySQL Process List Table',
				'link'=>'dbstatus.php?action=ViewMySQLProcessListTable',
				'mouseover'=>'View MySQL Process List Table',
			],
			[
				'text'=>'View MySQL Profiling Table',
				'link'=>'dbstatus.php?action=ViewMySQLProfilingTable',
				'mouseover'=>'View MySQL Profiling Table',
			],
			[
				'text'=>'View MySQL Referential Constraints Table',
				'link'=>'dbstatus.php?action=ViewMySQLReferentialConstraintsTable',
				'mouseover'=>'View MySQL Referential Constraints Table',
			],
			[
				'text'=>'View MySQL Routines Table',
				'link'=>'dbstatus.php?action=ViewMySQLRoutinesTable',
				'mouseover'=>'View MySQL Routines Table',
			],
			[
				'text'=>'View MySQL Schema Privileges Table',
				'link'=>'dbstatus.php?action=ViewMySQLSchemaPrivilegesTable',
				'mouseover'=>'View MySQL Schema Privileges Table',
			],
			[
				'text'=>'View MySQL Schemata',
				'link'=>'dbstatus.php?action=ViewMySQLSchemataTable',
				'mouseover'=>'View MySQL Schemata Table',
			],
			[
				'text'=>'View MySQL Statistics Table',
				'link'=>'dbstatus.php?action=ViewMySQLStatisticsTable',
				'mouseover'=>'View MySQL Schema Statistics Table',
			],
			[
				'text'=>'View MySQL Tables Table',
				'link'=>'dbstatus.php?action=ViewMySQLTablesTable',
				'mouseover'=>'View MySQL Schema Tables Table',
			],
			[
				'text'=>'View MySQL Table Spaces Table',
				'link'=>'dbstatus.php?action=ViewMySQLTableSpacesTable',
				'mouseover'=>'View MySQL Schema TableSpaces Table',
			],
			[
				'text'=>'View MySQL Table Constraints Table',
				'link'=>'dbstatus.php?action=ViewMySQLTableConstraintsTable',
				'mouseover'=>'View MySQL Schema Table Constraints Table',
			],
			[
				'text'=>'View MySQL Table Privileges Table',
				'link'=>'dbstatus.php?action=ViewMySQLTablePrivilegesTable',
				'mouseover'=>'View MySQL Schema Table Privileges Table',
			],
			[
				'text'=>'View MySQL Triggers Table',
				'link'=>'dbstatus.php?action=ViewMySQLTriggersTable',
				'mouseover'=>'View MySQL Schema Triggers Table',
			],
			[
				'text'=>'View MySQL User Privileges Table',
				'link'=>'dbstatus.php?action=ViewMySQLUserPrivilegesTable',
				'mouseover'=>'View MySQL User Privileges Table',
			],
			[
				'text'=>'View MySQL Views Table',
				'link'=>'dbstatus.php?action=ViewMySQLViewsTable',
				'mouseover'=>'View MySQL Views Table',
			],
			[
				'text'=>'View MySQL Function Status Table',
				'link'=>'dbstatus.php?action=ViewMySQLFunctionStatusTable',
				'mouseover'=>'View MySQL Function Status Table',
			],
			[
				'text'=>'View MySQL Collation Table',
				'link'=>'dbstatus.php?action=ViewMySQLCollationTable',
				'mouseover'=>'View MySQL Collation Table',
			],
			[
				'text'=>'View MySQL Open Tables Table',
				'link'=>'dbstatus.php?action=ViewMySQLOpenTablesTable',
				'mouseover'=>'View MySQL Open Tables Table',
			],
			[
				'text'=>'View MySQL Procedure Status Table',
				'link'=>'dbstatus.php?action=ViewMySQLProcedureStatusTable',
				'mouseover'=>'View MySQL Procedure Status Table',
			],
			[
				'text'=>'View MySQL Table Status Table',
				'link'=>'dbstatus.php?action=ViewMySQLTableStatusTable',
				'mouseover'=>'View MySQL Table Status Table',
			],
			[
				'text'=>'View MySQL Variables Table',
				'link'=>'dbstatus.php?action=ViewMySQLVariablesTable',
				'mouseover'=>'View MySQL Variables Table',
			],
		],
			
			/*
			
* http://dev.mysql.com/doc/refman/5.5/en/status-table.html
http://dev.mysql.com/doc/refman/5.5/en/innodb-i_s-tables.html
http://dev.mysql.com/doc/refman/5.5/en/mysql-cluster-i_s-tables.html
* http://dev.mysql.com/doc/refman/5.5/en/extended-show.html
http://dev.mysql.com/doc/refman/5.5/en/innodb-i_s-tables.html
https://dev.mysql.com/doc/refman/5.0/en/information-functions.html#function_version
https://dev.mysql.com/doc/refman/5.0/en/installation-version.html
http://stackoverflow.com/questions/1493722/mysql-command-for-showing-current-configuration-variables
http://dev.mysql.com/doc/refman/5.7/en/server-options.html
http://dev.mysql.com/doc/refman/5.7/en/server-configuration-defaults.html
http://dev.mysql.com/doc/refman/5.7/en/server-status-variables.html
			
			*/
		[
			'text'=>'MySQL Schema Information',
			'link'=>' ',
			'mouseover'=>'MySQL Schema Information',
			[
				'text'=>'Dump Tables',
				'link'=>'dbstatus.php?action=DumpTables',
				'mouseover'=>'Dump Selected Table(s) Records',
			],
		],
	];

?>
