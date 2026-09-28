<?php

	class HardcodedTableDescriptions {
		public function __construct($args) {
		}
		
		public function HardcodedTable_APIQuota() {
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
				
				'Service' => [
					'Type' => 'varchar(64)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 64,
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '',
					'Extra' => '',
				],
				
				'QuotaDate' => [
					'Type' => 'date',
					'TypeBase' => 'date',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => '0000-00-00',
					'Extra' => '',
				],
				
				'RequestCount' => [
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
		
		public function HardcodedTable_Assignment() {
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
		
		public function HardcodedTable_AssociatedRecordStats() {
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
		
		public function HardcodedTable_Association() {
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
		
		public function HardcodedTable_AvailabilityDateRange() {
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
		
		public function HardcodedTable_ChildRecordStats() {
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
		
		public function HardcodedTable_Comment() {
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
		
		public function HardcodedTable_Definition() {
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
		
		public function HardcodedTable_Description() {
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
		
		public function HardcodedTable_Entry() {
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
		
		public function HardcodedTable_EntryCodeReservation() {
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
		
		public function HardcodedTable_EntryPermission() {
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
		
		public function HardcodedTable_EntryTranslation() {
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
		
		public function HardcodedTable_EventDate() {
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
		
		public function HardcodedTable_Image() {
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
		
		public function HardcodedTable_ImageSearch() {
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
					'Key' => 'UNI',
					'Default' => '',
					'Extra' => '',
				],
				
				'ResultCount' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => '',
					'Default' => 0,
					'Extra' => '',
				],
				
				'LastSearchDate' => [
					'Type' => 'datetime',
					'TypeBase' => 'datetime',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => '0000-00-00 00:00:00',
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
		
		public function HardcodedTable_ImageSearchResult() {
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
				
				'ImageSearchid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
					'Extra' => '',
				],
				
				'URL' => [
					'Type' => 'varchar(2048)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 2048,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'ThumbnailURL' => [
					'Type' => 'varchar(2048)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 2048,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
					'Extra' => '',
				],
				
				'Ordering' => [
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
		
		public function HardcodedTable_ImageTranslation() {
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
		
		public function HardcodedTable_InternalServerError() {
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
		
		public function HardcodedTable_InternalServerErrorInstance() {
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
				
				'Errorid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
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
		
		public function HardcodedTable_InternalServerIssue() {
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
		
		public function HardcodedTable_InternalServerIssueInstance() {
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
				
				'Issueid' => [
					'Type' => 'int',
					'TypeBase' => 'int',
					'TypeAttribute' => '',
					'Null' => 'NO',
					'Key' => 'MUL',
					'Default' => 0,
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
		
		public function HardcodedTable_LikeDislike() {
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
		
		public function HardcodedTable_Link() {
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
		
		public function HardcodedTable_Quote() {
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
		
		public function HardcodedTable_RecordChange() {
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
		
		public function HardcodedTable_Suggestion() {
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
		
		public function HardcodedTable_Tag() {
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
		
		public function HardcodedTable_TextBody() {
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
		
		public function HardcodedTable_User() {
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
				
				'PasswordHash' => [
					'Type' => 'varchar(255)',
					'TypeBase' => 'varchar',
					'TypeAttribute' => 255,
					'Null' => 'NO',
					'Key' => '',
					'Default' => '',
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
		
		public function HardcodedTable_UserAdmin() {
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
		
		public function HardcodedTable_UserPermission() {
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
		
		public function HardcodedTable_UserPermissionType() {
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
		
		public function HardcodedTable_UserSession() {
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