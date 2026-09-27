<?php

/*

			// Stats for the "anarchism" top-parent in revoltlib.com

SELECT

COUNT(DISTINCT Assignment1.id) AS "Generation1ChildCount",
COUNT(DISTINCT Assignment2.id) AS "Generation2ChildCount",
COUNT(DISTINCT Assignment3.id) AS "Generation3ChildCount",
COUNT(DISTINCT Assignment4.id) AS "Generation4ChildCount",
COUNT(DISTINCT Assignment5.id) AS "Generation5ChildCount",
COUNT(DISTINCT Assignment6.id) AS "Generation6ChildCount",
COUNT(DISTINCT Assignment7.id) AS "Generation7ChildCount",

SUM(TextBody1.WordCount) AS "Generation1WordCount",
SUM(TextBody2.WordCount) AS "Generation2WordCount",
SUM(TextBody3.WordCount) AS "Generation3WordCount",
SUM(TextBody4.WordCount) AS "Generation4WordCount",
SUM(TextBody5.WordCount) AS "Generation5WordCount",
SUM(TextBody6.WordCount) AS "Generation6WordCount",
SUM(TextBody7.WordCount) AS "Generation7WordCount",

SUM(TextBody1.CharacterCount) AS "Generation1CharacterCount",
SUM(TextBody2.CharacterCount) AS "Generation2CharacterCount",
SUM(TextBody3.CharacterCount) AS "Generation3CharacterCount",
SUM(TextBody4.CharacterCount) AS "Generation4CharacterCount",
SUM(TextBody5.CharacterCount) AS "Generation5CharacterCount",
SUM(TextBody6.CharacterCount) AS "Generation6CharacterCount",
SUM(TextBody7.CharacterCount) AS "Generation7CharacterCount",

COUNT(DISTINCT Assignment1.id) + 
COUNT(DISTINCT Assignment2.id) + 
COUNT(DISTINCT Assignment3.id) + 
COUNT(DISTINCT Assignment4.id) + 
COUNT(DISTINCT Assignment5.id) + 
COUNT(DISTINCT Assignment6.id) + 
COUNT(DISTINCT Assignment7.id) AS "TotalRecordCount",

SUM(IFNULL(TextBody1.WordCount,0)) +
SUM(IFNULL(TextBody2.WordCount,0)) +
SUM(IFNULL(TextBody3.WordCount,0)) +
SUM(IFNULL(TextBody4.WordCount,0)) +
SUM(IFNULL(TextBody5.WordCount,0)) +
SUM(IFNULL(TextBody6.WordCount,0)) +
SUM(IFNULL(TextBody7.WordCount,0)) AS "TotalWordCount",

SUM(IFNULL(TextBody1.CharacterCount,0)) +
SUM(IFNULL(TextBody2.CharacterCount,0)) +
SUM(IFNULL(TextBody3.CharacterCount,0)) +
SUM(IFNULL(TextBody4.CharacterCount,0)) +
SUM(IFNULL(TextBody5.CharacterCount,0)) +
SUM(IFNULL(TextBody6.CharacterCount,0)) +
SUM(IFNULL(TextBody7.CharacterCount,0)) AS "TotalCharacterCount"

FROM Assignment AS Assignment1
LEFT JOIN Assignment AS Assignment2 ON Assignment2.Parentid = Assignment1.Childid
LEFT JOIN Assignment AS Assignment3 ON Assignment3.Parentid = Assignment2.Childid
LEFT JOIN Assignment AS Assignment4 ON Assignment4.Parentid = Assignment3.Childid
LEFT JOIN Assignment AS Assignment5 ON Assignment5.Parentid = Assignment4.Childid
LEFT JOIN Assignment AS Assignment6 ON Assignment6.Parentid = Assignment5.Childid
LEFT JOIN Assignment AS Assignment7 ON Assignment7.Parentid = Assignment6.Childid

LEFT JOIN TextBody AS TextBody1 ON TextBody1.Entryid = Assignment1.Childid
LEFT JOIN TextBody AS TextBody2 ON TextBody2.Entryid = Assignment2.Childid
LEFT JOIN TextBody AS TextBody3 ON TextBody3.Entryid = Assignment3.Childid
LEFT JOIN TextBody AS TextBody4 ON TextBody4.Entryid = Assignment4.Childid
LEFT JOIN TextBody AS TextBody5 ON TextBody5.Entryid = Assignment5.Childid
LEFT JOIN TextBody AS TextBody6 ON TextBody6.Entryid = Assignment6.Childid
LEFT JOIN TextBody AS TextBody7 ON TextBody7.Entryid = Assignment7.Childid

WHERE Assignment1.Parentid = 4

*/

	class ORMStats {
		public $dbaccessobject;
		
		
			// Construction
			// -------------------------------------------------
		
		public function __construct($args) {
			$this->dbaccessobject = $args['dbaccessobject'];
		}
		
		public function GenerateChildRecordStats($args) {
			$entry = $args['entry'];
			
			$sql = 'SELECT ';
			
			/*
			$sql .=
				'COUNT(DISTINCT Assignment1.id) AS "Generation1ChildCount", ' .
				'COUNT(DISTINCT Assignment2.id) AS "Generation2ChildCount", ' .
				'COUNT(DISTINCT Assignment3.id) AS "Generation3ChildCount", ' .
				'COUNT(DISTINCT Assignment4.id) AS "Generation4ChildCount", ' .
				'COUNT(DISTINCT Assignment5.id) AS "Generation5ChildCount", ' .
				'COUNT(DISTINCT Assignment6.id) AS "Generation6ChildCount", ' .
				'COUNT(DISTINCT Assignment7.id) AS "Generation7ChildCount", ' ;
			
			$sql .=
				'SUM(TextBody1.WordCount) AS "Generation1WordCount", ' .
				'SUM(TextBody2.WordCount) AS "Generation2WordCount", ' .
				'SUM(TextBody3.WordCount) AS "Generation3WordCount", ' .
				'SUM(TextBody4.WordCount) AS "Generation4WordCount", ' .
				'SUM(TextBody5.WordCount) AS "Generation5WordCount", ' .
				'SUM(TextBody6.WordCount) AS "Generation6WordCount", ' .
				'SUM(TextBody7.WordCount) AS "Generation7WordCount", ' ;
			
			$sql .=	
				'SUM(TextBody1.CharacterCount) AS "Generation1CharacterCount", ' .
				'SUM(TextBody2.CharacterCount) AS "Generation2CharacterCount", ' .
				'SUM(TextBody3.CharacterCount) AS "Generation3CharacterCount", ' .
				'SUM(TextBody4.CharacterCount) AS "Generation4CharacterCount", ' .
				'SUM(TextBody5.CharacterCount) AS "Generation5CharacterCount", ' .
				'SUM(TextBody6.CharacterCount) AS "Generation6CharacterCount", ' .
				'SUM(TextBody7.CharacterCount) AS "Generation7CharacterCount", ' ;
			*/
			$sql .=
				'COUNT(DISTINCT Assignment1.id) + ' .
				'COUNT(DISTINCT Assignment2.id) + ' .
				'COUNT(DISTINCT Assignment3.id) + ' .
				'COUNT(DISTINCT Assignment4.id) + ' .
				'COUNT(DISTINCT Assignment5.id) + ' .
				'COUNT(DISTINCT Assignment6.id) + ' .
				'COUNT(DISTINCT Assignment7.id) AS "TotalRecordCount", ' ;
			
			$sql .=
				'SUM(IFNULL(TextBody1.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody2.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody3.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody4.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody5.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody6.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody7.WordCount,0)) AS "TotalWordCount", ' ;
			
			$sql .=
				'SUM(IFNULL(TextBody1.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody2.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody3.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody4.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody5.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody6.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody7.CharacterCount,0)) AS "TotalCharacterCount" ' ;
			
			$sql .=
				'FROM Assignment AS Assignment1 ' . 
				'LEFT JOIN Assignment AS Assignment2 ON Assignment2.Parentid = Assignment1.Childid ' .
				'LEFT JOIN Assignment AS Assignment3 ON Assignment3.Parentid = Assignment2.Childid ' .
				'LEFT JOIN Assignment AS Assignment4 ON Assignment4.Parentid = Assignment3.Childid ' .
				'LEFT JOIN Assignment AS Assignment5 ON Assignment5.Parentid = Assignment4.Childid ' .
				'LEFT JOIN Assignment AS Assignment6 ON Assignment6.Parentid = Assignment5.Childid ' .
				'LEFT JOIN Assignment AS Assignment7 ON Assignment7.Parentid = Assignment6.Childid ' ;
			
			$sql .=
				'LEFT JOIN TextBody AS TextBody1 ON TextBody1.Entryid = Assignment1.Childid ' .
				'LEFT JOIN TextBody AS TextBody2 ON TextBody2.Entryid = Assignment2.Childid ' .
				'LEFT JOIN TextBody AS TextBody3 ON TextBody3.Entryid = Assignment3.Childid ' .
				'LEFT JOIN TextBody AS TextBody4 ON TextBody4.Entryid = Assignment4.Childid ' .
				'LEFT JOIN TextBody AS TextBody5 ON TextBody5.Entryid = Assignment5.Childid ' .
				'LEFT JOIN TextBody AS TextBody6 ON TextBody6.Entryid = Assignment6.Childid ' .
				'LEFT JOIN TextBody AS TextBody7 ON TextBody7.Entryid = Assignment7.Childid ' ;
				
			$sql .= 'LEFT JOIN Entry AS Entry1 ON Entry1.id = Assignment1.Childid ' .
				'LEFT JOIN Entry AS Entry2 ON Entry2.id = Assignment2.Childid ' .
				'LEFT JOIN Entry AS Entry3 ON Entry3.id = Assignment3.Childid ' .
				'LEFT JOIN Entry AS Entry4 ON Entry4.id = Assignment4.Childid ' .
				'LEFT JOIN Entry AS Entry5 ON Entry5.id = Assignment5.Childid ' .
				'LEFT JOIN Entry AS Entry6 ON Entry6.id = Assignment6.Childid ' .
				'LEFT JOIN Entry AS Entry7 ON Entry7.id = Assignment7.Childid ' ;
				
				
			$sql .=
				'WHERE Assignment1.Parentid = ? ';
				
			$sql .=
				'AND (Entry1.id IS NULL || Entry1.Publish = 1) ' .
				'AND (Entry2.id IS NULL || Entry2.Publish = 1) ' .
				'AND (Entry3.id IS NULL || Entry3.Publish = 1) ' .
				'AND (Entry4.id IS NULL || Entry4.Publish = 1) ' .
				'AND (Entry5.id IS NULL || Entry5.Publish = 1) ' .
				'AND (Entry6.id IS NULL || Entry6.Publish = 1) ' .
				'AND (Entry7.id IS NULL || Entry7.Publish = 1) ';
				
			$sql .= ';';
#			print_r($entry['id']);
#			print($sql);
#			print_r($sql);
#			print($entry['id']);
			$fill_arrays_from_db_args = [
				'query'=>$sql,
				'sqlbindstring'=>'i',
				'recordvalues'=>[$entry['id']],
			];
			
			$child_record_stats = $this->dbaccessobject->FillArraysFromDB($fill_arrays_from_db_args)[0];
			
			return $child_record_stats;
		}
		
		public function GenerateAssociatedRecordStats($args) {
			$entry = $args['entry'];
			$ignore_parent = $args['ignore_parent'];
			
			$sql_bind_string = '';
			$sql_bind_values = [];
			
			$sql = 'SELECT ';
			
			$sql .=
				'COUNT(DISTINCT Association1.id) + ' .
				'COUNT(DISTINCT Assignment1.id) + ' .
				'COUNT(DISTINCT Assignment2.id) + ' .
				'COUNT(DISTINCT Assignment3.id) + ' .
				'COUNT(DISTINCT Assignment4.id) + ' .
				'COUNT(DISTINCT Assignment5.id) + ' .
				'COUNT(DISTINCT Assignment6.id) + ' .
				'COUNT(DISTINCT Assignment7.id) AS "TotalRecordCount", ' ;
			
			$sql .=
				'SUM(IFNULL(TextBody0.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody1.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody2.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody3.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody4.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody5.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody6.WordCount,0)) + ' .
				'SUM(IFNULL(TextBody7.WordCount,0)) AS "TotalWordCount", ' ;
			
			$sql .=
				'SUM(IFNULL(TextBody0.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody1.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody2.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody3.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody4.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody5.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody6.CharacterCount,0)) + ' .
				'SUM(IFNULL(TextBody7.CharacterCount,0)) AS "TotalCharacterCount" ' ;
			
			$sql .=
				'FROM Association AS Association1 ' .
				'LEFT JOIN Assignment AS Assignment1 ON Assignment1.Parentid = Association1.Entryid ' .
				'LEFT JOIN Assignment AS Assignment2 ON Assignment2.Parentid = Assignment1.Childid ' .
				'LEFT JOIN Assignment AS Assignment3 ON Assignment3.Parentid = Assignment2.Childid ' .
				'LEFT JOIN Assignment AS Assignment4 ON Assignment4.Parentid = Assignment3.Childid ' .
				'LEFT JOIN Assignment AS Assignment5 ON Assignment5.Parentid = Assignment4.Childid ' .
				'LEFT JOIN Assignment AS Assignment6 ON Assignment6.Parentid = Assignment5.Childid ' .
				'LEFT JOIN Assignment AS Assignment7 ON Assignment7.Parentid = Assignment6.Childid ' ;
			
			$sql .= 'LEFT JOIN TextBody AS TextBody0 ON TextBody0.Entryid = Association1.Entryid ' .
				'LEFT JOIN TextBody AS TextBody1 ON TextBody1.Entryid = Assignment1.Childid ' .
				'LEFT JOIN TextBody AS TextBody2 ON TextBody2.Entryid = Assignment2.Childid ' .
				'LEFT JOIN TextBody AS TextBody3 ON TextBody3.Entryid = Assignment3.Childid ' .
				'LEFT JOIN TextBody AS TextBody4 ON TextBody4.Entryid = Assignment4.Childid ' .
				'LEFT JOIN TextBody AS TextBody5 ON TextBody5.Entryid = Assignment5.Childid ' .
				'LEFT JOIN TextBody AS TextBody6 ON TextBody6.Entryid = Assignment6.Childid ' .
				'LEFT JOIN TextBody AS TextBody7 ON TextBody7.Entryid = Assignment7.Childid ' ;
			
			$sql .= 'LEFT JOIN Entry AS Entry0 ON Entry0.id = Association1.Entryid ' .
				'LEFT JOIN Entry AS Entry1 ON Entry1.id = Assignment1.Childid ' .
				'LEFT JOIN Entry AS Entry2 ON Entry2.id = Assignment2.Childid ' .
				'LEFT JOIN Entry AS Entry3 ON Entry3.id = Assignment3.Childid ' .
				'LEFT JOIN Entry AS Entry4 ON Entry4.id = Assignment4.Childid ' .
				'LEFT JOIN Entry AS Entry5 ON Entry5.id = Assignment5.Childid ' .
				'LEFT JOIN Entry AS Entry6 ON Entry6.id = Assignment6.Childid ' .
				'LEFT JOIN Entry AS Entry7 ON Entry7.id = Assignment7.Childid ' ;
				
			if($ignore_parent) {
				$sql .= 'LEFT JOIN Assignment ChildAssignment ON ChildAssignment.Childid = Entry0.id ';
				$sql .= 'LEFT JOIN Entry EntryParent ON EntryParent.id = ChildAssignment.Parentid ';
				
				$sql .= 'LEFT JOIN Assignment ParentAssignment ON ParentAssignment.Childid = EntryParent.id ';
				$sql .= 'LEFT JOIN Entry EntryGrandParent ON EntryGrandParent.id = ParentAssignment.Parentid ';
				
				$sql .= 'LEFT JOIN Assignment GrandParentAssignment ON GrandParentAssignment.Childid = EntryGrandParent.id ';
				$sql .= 'LEFT JOIN Entry EntryGreatGrandParent ON EntryGreatGrandParent.id = GrandParentAssignment.Parentid ';
			}
				
			$sql .=
				'WHERE Association1.ChosenEntryid = ? ';
			
			$sql .=
				'AND (Entry0.id IS NULL || Entry0.Publish = 1) ' .
				'AND (Entry1.id IS NULL || Entry1.Publish = 1) ' .
				'AND (Entry2.id IS NULL || Entry2.Publish = 1) ' .
				'AND (Entry3.id IS NULL || Entry3.Publish = 1) ' .
				'AND (Entry4.id IS NULL || Entry4.Publish = 1) ' .
				'AND (Entry5.id IS NULL || Entry5.Publish = 1) ' .
				'AND (Entry6.id IS NULL || Entry6.Publish = 1) ' .
				'AND (Entry7.id IS NULL || Entry7.Publish = 1) ';
			
			$sql_bind_string .= 'i';
			$sql_bind_values[] = $entry['id'];
			
			if($ignore_parent) {
				$sql .= 'AND (ISNULL(EntryParent.Code) || EntryParent.Code != ?) ';
				$sql .= 'AND (ISNULL(EntryGrandParent.Code) || EntryGrandParent.Code != ?) ';
				$sql .= 'AND (ISNULL(EntryGreatGrandParent.Code) || EntryGreatGrandParent.Code != ?) ';
				
				$sql_bind_string .= 'sss';
				$sql_bind_values[] = $ignore_parent;
				$sql_bind_values[] = $ignore_parent;
				$sql_bind_values[] = $ignore_parent;
			}
			
			$sql .= ';';
			
			$fill_arrays_from_db_args = [
				'query'=>$sql,
				'sqlbindstring'=>$sql_bind_string,
				'recordvalues'=>$sql_bind_values,
			];
			
		#	print("<!-- BT:!!!\n\n");
			
		#	print_r($sql);
		#	print_r($sql_bind_values);
			
		#	print("-->");
			
			$associated_record_stats = $this->dbaccessobject->FillArraysFromDB($fill_arrays_from_db_args);
			
			return $associated_record_stats[0];
		}
	}

?>