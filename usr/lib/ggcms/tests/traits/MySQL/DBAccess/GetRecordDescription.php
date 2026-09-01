<?php
	trait GetRecordDescriptionTrait {
							// RUN TESTS
							// --------------------------------------------
							// --------------------------------------------
							// --------------------------------------------
							// --------------------------------------------
							
		public function testGetRecordDescription() {
			$_SERVER['HTTP_HOST'] = 'localhost';
			$_SERVER['SERVER_NAME'] = 'localhost';
			$_SERVER['REQUEST_URI'] = '/image/uuuuuuuu';
			$_GET['domain'] = 'sortwords.com';
			
			require(GGCMS_DIR . 'classes/StandardLibraries.php');
			$handler = new Handler();
			$handler->HandleRequest();
			
			$this->handler = $handler;
			
			/*
+-----------------------+
| Tables_in_revoltlib   |
+-----------------------+
| Assignment            |
| AssociatedRecordStats |
| Association           |
| AvailabilityDateRange |
| ChildRecordStats      |
| Comment               |
| Definition            |
| Description           |
| Entry                 |
| EntryCodeReservation  |
| EntryPermission       |
| EntryTranslation      |
| EventDate             |
| Image                 |
| ImageTranslation      |
| InternalServerError   |
| InternalServerIssue   |
| LikeDislike           |
| Link                  |
| LookupList            |
| LookupListItem        |
| Quote                 |
| RecordChange          |
| Suggestion            |
| Tag                   |
| TextBody              |
| User                  |
| UserAdmin             |
| UserPermission        |
| UserPermissionType    |
| UserSession           |

*/
			
			$this->recordDescriptionTest_Assignment();
			$this->recordDescriptionTest_AssociatedRecordStats();
			$this->recordDescriptionTest_Association();
			$this->recordDescriptionTest_AvailabilityDateRange();
			$this->recordDescriptionTest_ChildRecordStats();
			$this->recordDescriptionTest_Comment();
			$this->recordDescriptionTest_Definition();
			$this->recordDescriptionTest_Description();
			$this->recordDescriptionTest_Entry();
			$this->recordDescriptionTest_EntryCodeReservation();
			$this->recordDescriptionTest_EntryPermission();
			$this->recordDescriptionTest_EntryTranslation();
			$this->recordDescriptionTest_EventDate();
			$this->recordDescriptionTest_Image();
			$this->recordDescriptionTest_ImageTranslation();
			$this->recordDescriptionTest_InternalServerError();
			$this->recordDescriptionTest_InternalServerIssue();
			$this->recordDescriptionTest_LikeDislike();
			$this->recordDescriptionTest_Link();
			$this->recordDescriptionTest_Quote();
			$this->recordDescriptionTest_RecordChange();
			$this->recordDescriptionTest_Suggestion();
			$this->recordDescriptionTest_Tag();
			$this->recordDescriptionTest_TextBody();
			$this->recordDescriptionTest_User();
			$this->recordDescriptionTest_UserAdmin();
			$this->recordDescriptionTest_UserPermission();
			$this->recordDescriptionTest_UserPermissionType();
			$this->recordDescriptionTest_UserSession();
			
			return TRUE;
		}
							// INDIVIDUAL TABLE TESTS
							// --------------------------------------------
							// --------------------------------------------
							// --------------------------------------------
							// --------------------------------------------
					
					// Assignment
					// ************************************************************
		
		public function recordDescriptionTest_Assignment() {
			$assignment_hardcoded = $this->hardcodedTable_Assignment();
			
			$assignment_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'Assignment',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$assignment_hardcoded,
				$assignment_description,
				'Assignment Description Invalid',
			);
		}
					
					// AssociatedRecordStats
					// ************************************************************
		
		public function recordDescriptionTest_AssociatedRecordStats() {
			$associated_record_stats_hardcoded = $this->hardcodedTable_AssociatedRecordStats();
			
			$associated_record_stats_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'AssociatedRecordStats',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$associated_record_stats_hardcoded,
				$associated_record_stats_description,
				'AssociatedRecordStats Description Invalid',
			);
		}
					
					// Association
					// ************************************************************
		
		public function recordDescriptionTest_Association() {
			$association_hardcoded = $this->hardcodedTable_Association();
			
			$association_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'Assignment',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$association_hardcoded,
				$association_description,
				'Association Description Invalid',
			);
		}
					
					// AvailabilityDateRange
					// ************************************************************
		
		public function recordDescriptionTest_AvailabilityDateRange() {
			$availabilitydaterange_hardcoded = $this->hardcodedTable_AvailabilityDateRange();
			
			$availabilitydaterange_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'AvailabilityDateRange',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$availabilitydaterange_hardcoded,
				$availabilitydaterange_description,
				'AvailabilityDateRange Description Invalid',
			);
		}
					
					// ChildRecordStats
					// ************************************************************
		
		public function recordDescriptionTest_ChildRecordStats() {
			$childrecordstats_hardcoded = $this->hardcodedTable_ChildRecordStats();
			
			$childrecordstats_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'ChildRecordStats',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$childrecordstats_hardcoded,
				$childrecordstats_description,
				'ChildRecordStats Description Invalid',
			);
		}
					
					// Comment
					// ************************************************************
		
		public function recordDescriptionTest_Comment() {
			$comment_hardcoded = $this->hardcodedTable_Comment();
			
			$comment_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'Comment',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$comment_hardcoded,
				$comment_description,
				'Comment Description Invalid',
			);
		}
					
					// Definition
					// ************************************************************
		
		public function recordDescriptionTest_Definition() {
			$definition_hardcoded = $this->hardcodedTable_Definition();
			
			$definition_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'Definition',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$definition_hardcoded,
				$definition_description,
				'Definition Description Invalid',
			);
		}
					
					// Description
					// ************************************************************
		
		public function recordDescriptionTest_Description() {
			$description_hardcoded = $this->hardcodedTable_Description();
			
			$description_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'Description',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$description_hardcoded,
				$description_description,
				'`Description` Description Invalid',
			);
		}
					
					// Entry
					// ************************************************************
		
		public function recordDescriptionTest_Entry() {
			$entry_hardcoded = $this->hardcodedTable_Entry();
			
			$entry_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'Entry',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$entry_hardcoded,
				$entry_description,
				'Entry Description Invalid',
			);
		}
					
					// EntryCodeReservation
					// ************************************************************
		
		public function recordDescriptionTest_EntryCodeReservation() {
			$entrycodereservation_hardcoded = $this->hardcodedTable_EntryCodeReservation();
			
			$entrycodereservation_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'EntryCodeReservation',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$entrycodereservation_hardcoded,
				$entrycodereservation_description,
				'EntryCodeReservation Description Invalid',
			);
		}
					
					// EntryPermission
					// ************************************************************
		
		public function recordDescriptionTest_EntryPermission() {
			$entrypermission_hardcoded = $this->hardcodedTable_EntryPermission();
			
			$entrypermission_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'EntryPermission',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$entrypermission_hardcoded,
				$entrypermission_description,
				'EntryPermission Description Invalid',
			);
		}
					
					// EntryTranslation
					// ************************************************************
		
		public function recordDescriptionTest_EntryTranslation() {
			$entrytranslation_hardcoded = $this->hardcodedTable_EntryTranslation();
			
			$entrytranslation_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'EntryTranslation',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$entrytranslation_hardcoded,
				$entrytranslation_description,
				'EntryTranslation Description Invalid',
			);
		}
					
					// EventDate
					// ************************************************************
		
		public function recordDescriptionTest_EventDate() {
			$eventdate_hardcoded = $this->hardcodedTable_EventDate();
			
			$eventdate_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'EventDate',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$eventdate_hardcoded,
				$eventdate_description,
				'EventDate Description Invalid',
			);
		}
					
					// Image
					// ************************************************************
		
		public function recordDescriptionTest_Image() {
			$image_hardcoded = $this->hardcodedTable_Image();
			
			$image_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'Image',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$image_hardcoded,
				$image_description,
				'Image Description Invalid',
			);
		}
					
					// ImageTranslation
					// ************************************************************
		
		public function recordDescriptionTest_ImageTranslation() {
			$imagetranslation_hardcoded = $this->hardcodedTable_ImageTranslation();
			
			$imagetranslation_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'ImageTranslation',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$imagetranslation_hardcoded,
				$imagetranslation_description,
				'ImageTranslation Description Invalid',
			);
		}
					
					// InternalServerError
					// ************************************************************
		
		public function recordDescriptionTest_InternalServerError() {
			$internalservererror_hardcoded = $this->hardcodedTable_InternalServerError();
			
			$internalservererror_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'InternalServerError',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$internalservererror_hardcoded,
				$internalservererror_description,
				'InternalServerError Description Invalid',
			);
		}
					
					// InternalServerIssue
					// ************************************************************
		
		public function recordDescriptionTest_InternalServerIssue() {
			$internalserverissue_hardcoded = $this->hardcodedTable_InternalServerIssue();
			
			$internalserverissue_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'InternalServerIssue',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$internalserverissue_hardcoded,
				$internalserverissue_description,
				'InternalServerIssue Description Invalid',
			);
		}
					
					// LikeDislike
					// ************************************************************
		
		public function recordDescriptionTest_LikeDislike() {
			$likedislike_hardcoded = $this->hardcodedTable_LikeDislike();
			
			$likedislike_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'LikeDislike',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$likedislike_hardcoded,
				$likedislike_description,
				'LikeDislike Description Invalid',
			);
		}
					
					// Link
					// ************************************************************
		
		public function recordDescriptionTest_Link() {
			$link_hardcoded = $this->hardcodedTable_Link();
			
			$link_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'Link',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$link_hardcoded,
				$link_description,
				'Link Description Invalid',
			);
		}
					
					// Quote
					// ************************************************************
		
		public function recordDescriptionTest_Quote() {
			$quote_hardcoded = $this->hardcodedTable_Quote();
			
			$quote_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'Quote',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$quote_hardcoded,
				$quote_description,
				'Quote Description Invalid',
			);
		}
					
					// RecordChange
					// ************************************************************
		
		public function recordDescriptionTest_RecordChange() {
			$recordchange_hardcoded = $this->hardcodedTable_RecordChange();
			
			$recordchange_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'RecordChange',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$recordchange_hardcoded,
				$recordchange_description,
				'RecordChange Description Invalid',
			);
		}
					
					// Suggestion
					// ************************************************************
		
		public function recordDescriptionTest_Suggestion() {
			$suggestion_hardcoded = $this->hardcodedTable_Suggestion();
			
			$suggestion_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'Suggestion',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$suggestion_hardcoded,
				$suggestion_description,
				'Suggestion Description Invalid',
			);
		}
					
					// Tag
					// ************************************************************
		
		public function recordDescriptionTest_Tag() {
			$tag_hardcoded = $this->hardcodedTable_Tag();
			
			$tag_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'Tag',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$tag_hardcoded,
				$tag_description,
				'Tag Description Invalid',
			);
		}
					
					// TextBody
					// ************************************************************
		
		public function recordDescriptionTest_TextBody() {
			$textbody_hardcoded = $this->hardcodedTable_TextBody();
			
			$textbody_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'TextBody',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$textbody_hardcoded,
				$textbody_description,
				'TextBody Description Invalid',
			);
		}
					
					// User
					// ************************************************************
		
		public function recordDescriptionTest_User() {
			$user_hardcoded = $this->hardcodedTable_User();
			
			$user_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'User',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$user_hardcoded,
				$user_description,
				'User Description Invalid',
			);
		}
					
					// UserAdmin
					// ************************************************************
		
		public function recordDescriptionTest_UserAdmin() {
			$useradmin_hardcoded = $this->hardcodedTable_UserAdmin();
			
			$useradmin_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'UserAdmin',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$useradmin_hardcoded,
				$useradmin_description,
				'UserAdmin Description Invalid',
			);
		}
					
					// UserPermission
					// ************************************************************
		
		public function recordDescriptionTest_UserPermission() {
			$userpermission_hardcoded = $this->hardcodedTable_UserPermission();
			
			$userpermission_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'UserPermission',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$userpermission_hardcoded,
				$userpermission_description,
				'UserPermission Description Invalid',
			);
		}
					
					// UserPermissionType
					// ************************************************************
		
		public function recordDescriptionTest_UserPermissionType() {
			$userpermissiontype_hardcoded = $this->hardcodedTable_UserPermissionType();
			
			$userpermissiontype_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'UserPermissionType',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$userpermissiontype_hardcoded,
				$userpermissiontype_description,
				'UserPermissionType Description Invalid',
			);
		}
					
					// UserSession
					// ************************************************************
		
		public function recordDescriptionTest_UserSession() {
			$usersession_hardcoded = $this->hardcodedTable_UserSession();
			
			$usersession_description = $this->handler->db_access->GetRecordDescription([
				'type'=>'UserSession',
			]);
			
			return $this->assertEqualsCanonicalizing(
				$usersession_hardcoded,
				$usersession_description,
				'UserSession Description Invalid',
			);
		}
							// HARDCODED TABLES
							// --------------------------------------------
							// --------------------------------------------
							// --------------------------------------------
							// --------------------------------------------
					
					// Assignment
					// ************************************************************
		
		public function hardcodedTable_Assignment() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Parentid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Childid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// AssociatedRecordStats
					// ************************************************************
		
		public function hardcodedTable_AssociatedRecordStats() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'AssociatedRecordCount' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'AssociatedWordCount' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'AssociatedCharacterCount' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'IgnoreParents' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// Association
					// ************************************************************
		
		public function hardcodedTable_Association() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Parentid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Childid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// AvailabilityDateRange
					// ************************************************************
		
		public function hardcodedTable_AvailabilityDateRange() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'AvailabilityStart' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'AvailabilityEnd' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0',
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// ChildRecordStats
					// ************************************************************
		
		public function hardcodedTable_ChildRecordStats() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'ChildRecordCount' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'ChildWordCount' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'ChildCharacterCount' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// Comment
					// ************************************************************
		
		public function hardcodedTable_Comment() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Userid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Approved' => [
					'Type' => 'tinyint(1)',
					'TypeBase' => 'tinyint',
					'TypeAttribute' => 1,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Rejected' => [
					'Type' => 'tinyint(1)',
					'TypeBase' => 'tinyint',
					'TypeAttribute' => 1,
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Language' => [
					'Type' => 'varchar(32)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 32,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'IPAddress' => [
					'Type' => 'varchar(39)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 39,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Comment' => [
					'Type' => 'text',
					'TypeBase' => 'text',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// Definition
					// ************************************************************
		
		public function hardcodedTable_Definition() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Term' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'AlternateSpelling' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => '255',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Pronunciation' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'PartOfSpeech' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Etymology' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Definition' => [
					'Type' => 'text',
					'TypeBase' => 'text',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Dictionaryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// Description
					// ************************************************************
		
		public function hardcodedTable_Description() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Description' => [
					'Type' => 'varchar(512)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 512,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Source' => [
					'Type' => 'varchar(512)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 512,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Language' => [
					'Type' => 'varchar(32)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 32,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// Entry
					// ************************************************************
		
		public function hardcodedTable_Entry() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => 0,
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Title' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Publish' => [
					'Type' => 'tinyint(1)',
					'TypeBase' => 'tinyint',
					'TypeAttribute' => 1,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Subtitle' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'ListTitle' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'ListTitleSortKey' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Code' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'ChildAdjective' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'ChildNoun' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'ChildNounPlural' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'ChildAction' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'GrandChildAdjective' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'GrandChildNoun' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'GrandChildNounPlural' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'GrandChildAction' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'OriginalEntryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// EntryCodeReservation
					// ************************************************************
		
		public function hardcodedTable_EntryCodeReservation() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Assignmentid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Code' => [
					'Type' => 'varchar(510)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 510,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// EntryPermission
					// ************************************************************
		
		public function hardcodedTable_EntryPermission() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Userid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// EntryTranslation
					// ************************************************************
		
		public function hardcodedTable_EntryTranslation() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Title' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Subtitle' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'ListTitle' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'ListTitleSortKey' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Language' => [
					'Type' => 'varchar(32)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 32,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// EventDate
					// ************************************************************
		
		public function hardcodedTable_EventDate() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment', 
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'EventDateTime' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'Title' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Description' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Language' => [
					'Type' => 'varchar(32)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 32,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Approximate' => [
					'Type' => 'tinyint(1)',
					'TypeBase' => 'tinyint',
					'TypeAttribute' => 1,
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// Image
					// ************************************************************
		
		public function hardcodedTable_Image() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Title' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Description' => [
					'Type' => 'varchar(1023)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 1023,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'FileName' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'FileDirectory' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'IconFileName' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'StandardFileName' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'PixelWidth' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'PixelHeight' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'IconPixelWidth' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'IconPixelHeight' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'StandardPixelWidth' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'StandardPixelHeight' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// ImageTranslation
					// ************************************************************
		
		public function hardcodedTable_ImageTranslation() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Title' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Description' => [
					'Type' => 'varchar(1023)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 1023,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'FileName' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Language' => [
					'Type' => 'varchar(32)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 32,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],

			];
		}
					
					// InternalServerError
					// ************************************************************
		
		public function hardcodedTable_InternalServerError() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Signature' => [
					'Type' => 'char(64)',
					'TypeBase' => 'char',
					'TypeAttribute' => 64,
					'Null' => 'NO',
					'Key' => 'UNI',
					'Default' => '',
					'Extra' => '',
				],
				
				'Script' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'IncidentCount' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 1,
					'Extra' => '',
				],
				
				'Resolved' => [
					'Type' => 'tinyint(1)',
					'TypeBase' => 'tinyint',
					'TypeAttribute' => 1,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'ErrorMessage' => [
					'Type' => 'text',
					'TypeBase' => 'text',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'URL' => [
					'Type' => 'varchar(1024)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 1024,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'ServerVariable' => [
					'Type' => 'text',
					'TypeBase' => 'text',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'PostVariable' => [
					'Type' => 'text',
					'TypeBase' => 'text',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'GetVariable' => [
					'Type' => 'text',
					'TypeBase' => 'text',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'EnvironmentVariables' => [
					'Type' => 'text',
					'TypeBase' => 'text',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],

			];
		}
					
					// InternalServerIssue
					// ************************************************************
		
		public function hardcodedTable_InternalServerIssue() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Signature' => [
					'Type' => 'char(64)',
					'TypeBase' => 'char',
					'TypeAttribute' => 64,
					'Null' => 'NO',
					'Key' => 'UNI',
					'Default' => '',
					'Extra' => '',
				],
				
				'Script' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'IncidentCount' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 1,
					'Extra' => '',
				],
				
				'IssueType' => [
					'Type' => 'varchar(512)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 512,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'URL' => [
					'Type' => 'varchar(1024)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 1024,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Description' => [
					'Type' => 'varchar(2048)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 2048,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Resolved' => [
					'Type' => 'tinyint(1)',
					'TypeBase' => 'tinyint',
					'TypeAttribute' => 1,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'ServerVariable' => [
					'Type' => 'text',
					'TypeBase' => 'text',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'PostVariable' => [
					'Type' => 'text',
					'TypeBase' => 'text',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'GetVariable' => [
					'Type' => 'text',
					'TypeBase' => 'text',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// LikeDislike
					// ************************************************************
		
		public function hardcodedTable_LikeDislike() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'LikeOrDislike' => [
					'Type' => 'tinyint(1)',
					'TypeBase' => 'tinyint',
					'TypeAttribute' => 1,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Userid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// Link
					// ************************************************************
		
		public function hardcodedTable_Link() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Title' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'URL' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Language' => [
					'Type' => 'varchar(32)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 32,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => ''
				],
			];
		}
					
					// Quote
					// ************************************************************
		
		public function hardcodedTable_Quote() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Quote' => [
					'Type' => 'varchar(2048)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 2048,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Source' => [
					'Type' => 'varchar(512)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => '512',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Language' => [
					'Type' => 'varchar(32)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 32,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
			];
		}
					
					// RecordChange
					// ************************************************************
		
		public function hardcodedTable_RecordChange() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Userid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'RecordField' => [
					'Type' => 'varchar(512)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 512,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Recordid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'RecordType' => [
					'Type' => 'varchar(512)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 512,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OldValue' => [
					'Type' => 'mediumtext',
					'TypeBase' => 'mediumtext',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],

			];
		}
					
					// Suggestion
					// ************************************************************
		
		public function hardcodedTable_Suggestion() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Userid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'SuggestionType' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Suggestion' => [
					'Type' => 'varchar(512)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 512,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Explanation' => [
					'Type' => 'varchar(1024)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 1024,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Approved' => [
					'Type' => 'tinyint(1)',
					'TypeBase' => 'tinyint',
					'TypeAttribute' => 1,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Rejected' => [
					'Type' => 'tinyint(1)',
					'TypeBase' => 'tinyint',
					'TypeAttribute' => 1,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Language' => [
					'Type' => 'varchar(32)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 32,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'IPAddress' => [
					'Type' => 'varchar(39)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 39,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],

			];
		}
					
					// Tag
					// ************************************************************
		
		public function hardcodedTable_Tag() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Tag' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Language' => [
					'Type' => 'varchar(32)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 32,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],

			];
		}
					
					// TextBody
					// ************************************************************
		
		public function hardcodedTable_TextBody() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Text' => [
					'Type' => 'mediumtext',
					'TypeBase' => 'mediumtext',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Source' => [
					'Type' => 'varchar(512)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 512,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Language' => [
					'Type' => 'varchar(32)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 32,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'WordCount' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'CharacterCount' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'Entryid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],

			];
		}
					
					// User
					// ************************************************************
		
		public function hardcodedTable_User() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Username' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Password' => [
					'Type' => 'binary(32)',
					'TypeBase' => 'binary',
					'TypeAttribute' => '32',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0x30',
					'Extra' => '',
				],
				
				'EmailAddress' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],

			];
		}
					
					// UserAdmin
					// ************************************************************
		
		public function hardcodedTable_UserAdmin() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Userid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],

			];
		}
					
					// UserPermission
					// ************************************************************
		
		public function hardcodedTable_UserPermission() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Usernameid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'PermissionTypeid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OwnedTable' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'Ownedid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],

			];
		}
					
					// UserPermissionType
					// ************************************************************
		
		public function hardcodedTable_UserPermissionType() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Permission' => [
					'Type' => "enum('View','Edit')",
					'TypeBase' => 'enum',
					'TypeAttribute' => "'View','Edit'",
					'Null' => 'NO',
					'Key' => '',
					'Default' => 'View',
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],

			];
		}
					
					// UserSession
					// ************************************************************
		
		public function hardcodedTable_UserSession() {
			return [
				'id' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'PRI',
					'Default' => '',
					'Extra' => 'auto_increment',
				],
				
				'Userid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'CookieToken' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'LastAccess' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'OriginalCreationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],
				
				'LastModificationDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00 00:00:00',
					'Extra' => '',
				],

			];
		}
	}
?>