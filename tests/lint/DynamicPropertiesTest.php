<?php

	/*
		Every property a class assigns on $this must be declared -- by the
		class, an ancestor or one of its traits.

		PHP 8.2 deprecated creating properties on the fly, and PHP 9 is to
		make it an Error: a 500 on the first request that reaches the
		assignment.  The engine had nearly a thousand of them.  This reads the
		source, so it finds one on a path no test or crawl ever takes.

		It sees literal names only.  A name built at run time --
		$this->$record_type = ... -- is invisible to it; the classes that do
		that by design (every script, through baseformat) carry
		#[AllowDynamicProperties], and the rest name their few properties.

		dep/ is read for inheritance only; it is not ours to fix.  Not in the
		default run, with the rest of lint:

			vendor/bin/phpunit --testsuite lint
	*/

	class DynamicPropertiesTest extends GGCMSTestCase {
		public $types = [];

		public function testEveryAssignedPropertyIsDeclared() {
			$this->types = [];

			foreach([dirname(__DIR__, 2) . '/usr/lib/ggcms', dirname(__DIR__), dirname(GGCMS_CONFIG_DIR, 2)] as $root) {
				foreach($this->phpFiles(['root'=>$root]) as $file) {
					foreach($this->typesInFile(['file'=>$file]) as $type) {
						$this->types[strtolower($type['name'])][] = $type;
					}
				}
			}

			$this->assertSame([], $this->undeclaredProperties());
		}

		public function phpFiles($args) {
			$files = [];

			$iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator($args['root'], FilesystemIterator::SKIP_DOTS),
				function($file) { return !in_array($file->getFilename(), ['.git', 'vendor', 'Development'], TRUE); }
			));

			foreach($iterator as $file) {
				if($file->getExtension() === 'php') {
					$files[] = str_replace('\\', '/', $file->getPathname());
				}
			}

			return $files;
		}

			/*
				One entry per class or trait: its parent, its traits, the names
				it declares, and the names it assigns through $this.  A variable
				at the top level of the body, outside any parentheses, is a
				declaration; one in a parameter list is not.
			*/

		public function typesInFile($args) {
			$source = file_get_contents($args['file']);

			if(stripos($source, 'class') === FALSE && stripos($source, 'trait') === FALSE) {
				return [];
			}

			$tokens = token_get_all($source);
			$count = count($tokens);
			$types = [];

			for($i = 0; $i < $count; $i++) {
				if(!$this->isToken(['token'=>$tokens[$i], 'type'=>[T_CLASS, T_TRAIT]])) {
					continue;
				}

				$before = $this->skipBlank(['tokens'=>$tokens, 'index'=>$i - 1, 'step'=>-1]);
				if($before >= 0 && $this->isToken(['token'=>$tokens[$before], 'type'=>[T_DOUBLE_COLON, T_NEW]])) {
					continue;
				}

				$name_index = $this->skipBlank(['tokens'=>$tokens, 'index'=>$i + 1, 'step'=>1]);
				if(!$this->isToken(['token'=>$tokens[$name_index], 'type'=>[T_STRING]])) {
					continue;
				}

				$type = [
					'file'=>$args['file'],
					'name'=>$tokens[$name_index][1],
					'parent'=>NULL,
					'traits'=>[],
					'declared'=>[],
					'assigned'=>[],
				];

				for($k = $name_index + 1; $k < $count && $tokens[$k] !== '{'; $k++) {
					if($this->isToken(['token'=>$tokens[$k], 'type'=>[T_EXTENDS]])) {
						$parent_index = $this->skipBlank(['tokens'=>$tokens, 'index'=>$k + 1, 'step'=>1]);
						$type['parent'] = ltrim($tokens[$parent_index][1], '\\');
					}
				}

				$depth = 0;
				$parentheses = 0;

				for(; $k < $count; $k++) {
					$token = $tokens[$k];

					if($token === '{' || $this->isToken(['token'=>$token, 'type'=>[T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]])) {
						$depth++;
						continue;
					}

					if($token === '}') {
						$depth--;
						if($depth === 0) {
							break;
						}
						continue;
					}

					if($token === '(') {
						$parentheses++;
					} elseif($token === ')') {
						$parentheses--;
					}

					if($depth === 1 && $parentheses === 0) {
						if($this->isToken(['token'=>$token, 'type'=>[T_USE]])) {
							for($m = $k + 1; $m < $count && $tokens[$m] !== ';' && $tokens[$m] !== '{'; $m++) {
								if($this->isToken(['token'=>$tokens[$m], 'type'=>[T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED]])) {
									$type['traits'][] = ltrim($tokens[$m][1], '\\');
								}
							}
						} elseif($this->isToken(['token'=>$token, 'type'=>[T_VARIABLE]])) {
							$type['declared'][substr($token[1], 1)] = TRUE;
						}
					}

					if($this->isToken(['token'=>$token, 'type'=>[T_VARIABLE]]) && $token[1] === '$this') {
						$assigned = $this->assignedProperty(['tokens'=>$tokens, 'index'=>$k]);
						if($assigned !== NULL && !isset($type['assigned'][$assigned['name']])) {
							$type['assigned'][$assigned['name']] = $assigned['line'];
						}
					}
				}

				$types[] = $type;
				$i = $k;
			}

			return $types;
		}

			/*
				$this->name followed by an assignment, ++ or --, allowing for
				any [...] in between: $this->name[] = ... creates the property
				as surely as $this->name = ... does.
			*/

		public function assignedProperty($args) {
			$tokens = $args['tokens'];
			$index = $args['index'];

			$m = $this->skipBlank(['tokens'=>$tokens, 'index'=>$index + 1, 'step'=>1]);
			if(!$this->isToken(['token'=>$tokens[$m], 'type'=>[T_OBJECT_OPERATOR]])) {
				return NULL;
			}

			$m = $this->skipBlank(['tokens'=>$tokens, 'index'=>$m + 1, 'step'=>1]);
			if(!$this->isToken(['token'=>$tokens[$m], 'type'=>[T_STRING]])) {
				return NULL;
			}

			$name = $tokens[$m][1];
			$line = $tokens[$m][2];

			$m = $this->skipBlank(['tokens'=>$tokens, 'index'=>$m + 1, 'step'=>1]);

			while($tokens[$m] === '[') {
				for($brackets = 0; $m < count($tokens); $m++) {
					if($tokens[$m] === '[') {
						$brackets++;
					} elseif($tokens[$m] === ']') {
						$brackets--;
						if($brackets === 0) {
							$m++;
							break;
						}
					}
				}
				$m = $this->skipBlank(['tokens'=>$tokens, 'index'=>$m, 'step'=>1]);
			}

			$before = $this->skipBlank(['tokens'=>$tokens, 'index'=>$index - 1, 'step'=>-1]);
			$incremented = $before >= 0 && $this->isToken(['token'=>$tokens[$before], 'type'=>[T_INC, T_DEC]]);

			$assignments = [T_CONCAT_EQUAL, T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_MOD_EQUAL, T_COALESCE_EQUAL,
				T_OR_EQUAL, T_AND_EQUAL, T_XOR_EQUAL, T_SL_EQUAL, T_SR_EQUAL, T_POW_EQUAL, T_INC, T_DEC];

			if($incremented || $tokens[$m] === '=' || $this->isToken(['token'=>$tokens[$m], 'type'=>$assignments])) {
				return ['name'=>$name, 'line'=>$line];
			}

			return NULL;
		}

			/*
				Everything a type has declared, through its parents and traits.
				Several per-site classes share one name; a name counts as
				declared only when every definition of it declares it.  A
				built-in parent, such as Exception, is asked by reflection.
			*/

		public function declaredProperties($args) {
			$key = strtolower($args['name']);
			$seen = $args['seen'] ?? [];

			if(isset($seen[$key])) {
				return [];
			}
			$seen[$key] = TRUE;

			if(!isset($this->types[$key])) {
				if(!class_exists($args['name'])) {
					return [];
				}

				$declared = [];
				foreach((new ReflectionClass($args['name']))->getProperties() as $property) {
					$declared[$property->getName()] = TRUE;
				}
				return $declared;
			}

			$sets = [];
			foreach($this->types[$key] as $type) {
				$sets[] = $this->ownAndInheritedProperties(['type'=>$type, 'seen'=>$seen]);
			}

			$declared = array_shift($sets);
			foreach($sets as $set) {
				$declared = array_intersect_key($declared, $set);
			}

			return $declared;
		}

		public function ownAndInheritedProperties($args) {
			$type = $args['type'];
			$declared = $type['declared'];

			if($type['parent']) {
				$declared += $this->declaredProperties(['name'=>$type['parent'], 'seen'=>$args['seen']]);
			}

			foreach($type['traits'] as $trait) {
				$declared += $this->declaredProperties(['name'=>$trait, 'seen'=>$args['seen']]);
			}

			return $declared;
		}

			/*
				"file:line Class::$name" for every assignment to a property
				nothing declares.
			*/

		public function undeclaredProperties() {
			$undeclared = [];

			foreach($this->types as $definitions) {
				foreach($definitions as $type) {
					if(strpos($type['file'], '/dep/') !== FALSE) {
						continue;
					}

					$declared = $this->ownAndInheritedProperties(['type'=>$type, 'seen'=>[]]);

					foreach($type['assigned'] as $name => $line) {
						if(!isset($declared[$name])) {
							$undeclared[] = $type['file'] . ':' . $line . ' ' . $type['name'] . '::$' . $name;
						}
					}
				}
			}

			sort($undeclared);

			return $undeclared;
		}

		public function isToken($args) {
			return is_array($args['token']) && in_array($args['token'][0], $args['type'], TRUE);
		}

		public function skipBlank($args) {
			$tokens = $args['tokens'];
			$index = $args['index'];

			while($index >= 0 && $index < count($tokens) && $this->isToken(['token'=>$tokens[$index], 'type'=>[T_WHITESPACE, T_COMMENT, T_DOC_COMMENT]])) {
				$index += $args['step'];
			}

			return $index;
		}
	}

?>
