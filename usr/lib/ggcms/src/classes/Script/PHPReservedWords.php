<?php

	class PHPReservedWords {
		public $word_hash;
		
		public function isWordReserved($args) {
			$word = $args['word'];
			
			if($this->GetPHPReservedWordsHash()[$word]) {
				return TRUE;
			}
			
			return FALSE;
		}
		
		public function GetPHPReservedWordsHash() {
			if($this->word_hash) {
				return $this->word_hash;
			}
			
			$hash = [];
			
			foreach($this->GetPHPReservedWords() as $word) {
				$hash[$word] = TRUE;
			}
			
			return $this->word_hash = $hash;
		}
		public function GetPHPReservedWords() {
			return [
						# Primary Source: https://www.php.net/manual/en/reserved.php
						# ----------------------------------------------------------
						
					# List 1 of 4: https://www.php.net/manual/en/reserved.keywords.php
					# ----------------------------------------------------------------
			
				'__halt_compiler',
				'abstract',
				'and',
				'array',
				'as',
				'break',
				'callable',
				'case',
				'catch',
				'class',
				'clone',
				'const',
				'continue',
				'declare',
				'default',
				'die',
				'do',
				'echo',
				'else',
				'elseif',
				'empty',
				'enddeclare',
				'endfor',
				'endforeach',
				'endif',
				'endswitch',
				'endwhile',
				'eval',
				'exit',
				'extends',
				'final',
				'finally',
				'fn',			# (as of PHP 7.4)
				'for',
				'foreach',
				'function',
				'global',
				'goto',
				'if',
				'implements',
				'include',
				'include_once',
				'instanceof',
				'insteadof',
				'interface',
				'isset',
				'list',
				'match',		# (as of PHP 8.0)
				'namespace',
				'new',
				'or',
				'print',
				'private',
				'protected',
				'public',
				'readonly',		# (as of PHP 8.1.0)
				'require',
				'require_once',
				'return',
				'static',
				'switch',
				'throw',
				'trait',
				'try',
				'unset()',
				'use',
				'var',
				'while',
				'xor',
				'yield',
				'yield from',
						
					# List 2 of 4: https://www.php.net/manual/en/reserved.classes.php
					# ---------------------------------------------------------------
				
				'Directory',
				'stdClass',
				'__PHP_Incomplete_Class',
				'Exception',
				'ErrorException',
				'php_user_filter',
				'Closure',
				'Generator',
				'ArithmeticError',
				'AssertionError',
				'DivisionByZeroError',
				'Error',
				'Throwable',
				'ParseError',
				'TypeError',
				'self',
				'static',
				'parent',
						
					# List 3 of 4: https://www.php.net/manual/en/reserved.constants.php
					# ---------------------------------------------------------------
					
				'PHP_VERSION',
				'PHP_MAJOR_VERSION',
				'PHP_MINOR_VERSION',
				'PHP_RELEASE_VERSION',
				'PHP_VERSION_ID',
				'PHP_EXTRA_VERSION',
				'PHP_ZTS',
				'PHP_DEBUG',
				'PHP_MAXPATHLEN',
				'PHP_OS',
				'PHP_OS_FAMILY',
				'PHP_SAPI',
				'PHP_EOL',
				'PHP_INT_MAX',
				'PHP_INT_MIN',
				'PHP_INT_SIZE',
				'PHP_FLOAT_DIG',
				'PHP_FLOAT_EPSILON',
				'PHP_FLOAT_MIN',
				'PHP_FLOAT_MAX',
				'DEFAULT_INCLUDE_PATH',
				'PEAR_INSTALL_DIR',
				'PEAR_EXTENSION_DIR',
				'PHP_EXTENSION_DIR',
				'PHP_PREFIX',
				'PHP_BINDIR',
				'PHP_BINARY',
				'PHP_MANDIR',
				'PHP_LIBDIR',
				'PHP_DATADIR',
				'PHP_SYSCONFDIR',
				'PHP_LOCALSTATEDIR',
				'PHP_CONFIG_FILE_PATH',
				'PHP_CONFIG_FILE_SCAN_DIR',
				'PHP_SHLIB_SUFFIX',
				'PHP_FD_SETSIZE',
				'E_ERROR',
				'E_WARNING',
				'E_PARSE',
				'E_NOTICE',
				'E_CORE_ERROR',
				'E_CORE_WARNING',
				'E_COMPILE_ERROR',
				'E_COMPILE_WARNING',
				'E_USER_ERROR',
				'E_USER_WARNING',
				'E_USER_NOTICE',
				'E_RECOVERABLE_ERROR',
				'E_DEPRECATED',
				'E_USER_DEPRECATED',
				'E_ALL',
				'E_STRICT',
				'true',
				'false',
				'null',
				'PHP_WINDOWS_EVENT_CTRL_C',
				'PHP_WINDOWS_EVENT_CTRL_BREAK',
						
					# List 4 of 4: https://www.php.net/manual/en/reserved.other-reserved-words.php
					# ---------------------------------------------------------------
					
				'int',
				'float',
				'bool',
				'string',
				'true',
				'false',
				'null',
				'void',			# (as of PHP 7.1)
				'iterable',		# (as of PHP 7.1)
				'object',		# (as of PHP 7.2)
				'resource',
				'mixed',
				'numeric',
			];
		}
	}

?>