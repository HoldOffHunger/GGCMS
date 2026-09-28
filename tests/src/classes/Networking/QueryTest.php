<?php

	class QueryTest extends GGCMSTestCase {
		public function newQuery($args) {
			$_POST = $args['post'] ?? [];
			$_GET = $args['get'] ?? [];

			$handler = new stdClass();
			$handler->cleanser = new HandleInput(['handler'=>NULL]);

			return new Query(['handler'=>$handler]);
		}

		public function testParameter() {
			$query = $this->newQuery(['get'=>['page'=>'2']]);

			$this->assertSame('2', $query->Parameter(['parameter'=>'page']));
		}

		public function testConstruct_Parameters() {
			$query = $this->newQuery(['post'=>['title'=>'posted'], 'get'=>['title'=>'got', 'page'=>'2']]);

			$this->assertSame('posted', $query->Parameter(['parameter'=>'title']), 'POST wins over GET');
			$this->assertSame('2', $query->Parameter(['parameter'=>'page']));
		}

		public function testConstruct_Parameters_POSTData() {
			$this->assertSame('x', $this->newQuery(['post'=>['a'=>'x']])->Parameter(['parameter'=>'a']));
		}

			/*
				POST is loaded first and GET only fills what POST left out.
				"Left out" was tested as falsy, so a POSTed 0, empty string or
				empty list was replaced by whatever the URL the form was posted
				to said -- a flag unticked in the form, set by the query string.
			*/

		public function testConstruct_Parameters_GETData() {
			$this->assertSame('y', $this->newQuery(['get'=>['b'=>'y']])->Parameter(['parameter'=>'b']));

			$query = $this->newQuery(['post'=>['flag'=>'0', 'note'=>'', 'list'=>[]], 'get'=>['flag'=>'1', 'note'=>'from the url', 'list'=>['from the url'], 'page'=>'2']]);

			$this->assertSame('0', $query->Parameter(['parameter'=>'flag']), 'a POSTed 0 stands');
			$this->assertSame('', $query->Parameter(['parameter'=>'note']), 'a POSTed empty string stands');
			$this->assertSame([], $query->Parameter(['parameter'=>'list']), 'a POSTed empty list stands');
			$this->assertSame('2', $query->Parameter(['parameter'=>'page']), 'GET still fills what POST left out');
		}

			/*
				Query is built in the Handler's constructor, for every request.
				a[b][c]=1 used to reach mb_encode_numericentity() as an array:
				a TypeError, and a 500 on any page for anyone who sent it.
			*/

		public function testConstruct_Parameters_AddSingleParameter() {
			$query = $this->newQuery(['get'=>['filter'=>['one', 'two'], 'a'=>['b'=>['c'=>'1']], 'mixed'=>['kept', ['dropped']]]]);

			$this->assertSame(['one', 'two'], $query->Parameter(['parameter'=>'filter']));
			$this->assertSame([], $query->Parameter(['parameter'=>'a']), 'an array inside an array is dropped');
			$this->assertSame(['kept'], $query->Parameter(['parameter'=>'mixed']));
		}
	}

?>
