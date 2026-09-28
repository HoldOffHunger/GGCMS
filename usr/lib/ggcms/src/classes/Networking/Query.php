<?php

	class Query {
		public $post_data;
		public $get_data;
		public $handler;
		public $parameter_data;
		
		public function __construct($args) {
			$this->post_data = $_POST;
			$this->get_data = $_GET;
			
			$this->handler = $args['handler'];
			
			$this->Construct_Parameters();
		}
		
			// Construct_Parameters()
			// Tests: QueryTest::testConstruct_Parameters()
			// Test file: tests/src/classes/Networking/QueryTest.php
		public function Construct_Parameters() {
			$this->Construct_Parameters_POSTData();
			$this->Construct_Parameters_GETData();
		}
		
			// Construct_Parameters_POSTData()
			// Tests: QueryTest::testConstruct_Parameters_POSTData()
			// Test file: tests/src/classes/Networking/QueryTest.php
		public function Construct_Parameters_POSTData() {
			foreach ($this->post_data as $key => $value) {
				$add_single_parameter_args = [
					'key'=>$key,
					'value'=>$value,
				];
				$this->Construct_Parameters_AddSingleParameter($add_single_parameter_args);
			}
		}
		
			// Construct_Parameters_GETData()
			// Tests: QueryTest::testConstruct_Parameters_GETData()
			// Test file: tests/src/classes/Networking/QueryTest.php
		public function Construct_Parameters_GETData() {
			foreach ($this->get_data as $key => $value) {
					/*
						Present, not truthy: a POSTed 0 or empty string was
						overwritten by the query string's value.  NULL when
						nothing was POSTed, which array_key_exists() refuses.
					*/
				
				if(!is_array($this->parameter_data) || !array_key_exists($key, $this->parameter_data)) {
					$add_single_parameter_args = [
						'key'=>$key,
						'value'=>$value,
					];
					$this->Construct_Parameters_AddSingleParameter($add_single_parameter_args);
				}
			}
		}
		
			// Construct_Parameters_AddSingleParameter()
			// Tests: QueryTest::testConstruct_Parameters_AddSingleParameter()
			// Test file: tests/src/classes/Networking/QueryTest.php
		public function Construct_Parameters_AddSingleParameter($args) {
			$key = $args['key'];
			$value = $args['value'];
			$convertentities = $args['convertentities'];
			
			if(is_array($value)) {
				$this->parameter_data[$key] = [];
				foreach ($value as $valueoption) {
						/*
							a[b][c]=1 arrives as an array inside an array, and
							CleanseInput_UTF8 takes only a string: a TypeError in
							the Handler's constructor, and a 500 on every page for
							anyone who sent it.  Dropped, as
							HandleInput::CleanseInput_GetQuery drops it, since no
							form here posts one.
						*/
					
					if(is_array($valueoption)) {
						continue;
					}
					
					$cleanse_input_utf8_args = [
						'input'=>$valueoption,
						'convertentities'=>$convertentities,
					];
					
					$cleansed_data = $this->handler->cleanser->utf8_characters->CleanseInput_UTF8($cleanse_input_utf8_args);
					$this->parameter_data[$key][] = $cleansed_data['cleansedinput'];
				}
			} else {
				$cleanse_input_utf8_args = [
					'input'=>$value,
				];
				
				$cleansed_data = $this->handler->cleanser->utf8_characters->CleanseInput_UTF8($cleanse_input_utf8_args);
				$this->parameter_data[$key] = $cleansed_data['cleansedinput'];
			}
		}
		
			// Parameter()
			// Tests: QueryTest::testParameter()
			// Test file: tests/src/classes/Networking/QueryTest.php
		public function Parameter($args) {
			$parameter = $args['parameter'];
			
			return $this->parameter_data[$parameter];
		}
	}

?>