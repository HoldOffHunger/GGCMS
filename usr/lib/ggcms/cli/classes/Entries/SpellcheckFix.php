<?php

	/*
		Puts one reviewed spellcheck fix into a text body's markup, or says why
		it would not.

		The AI Workers spellcheck read a dump that is older than the site, so
		a fix carries the letters either side of the typo in the sentence it
		was judged in, reduced to a-z and 0-9.  An occurrence of the word is
		the one to change only when

		  it stands alone (no letter either side, so "wil" is never found
		  inside "wild"),

		  it is outside every tag, so an attribute or an href is never
		  rewritten, and

		  the letters before and after it, with tags stripped and entities
		  decoded, end and begin with the ones the reviewer saw.

		Exactly one occurrence may pass.  None means the sentence has been
		edited since; more than one means the evidence cannot tell them
		apart.  Either way the text is left as it is.
	*/

	class SpellcheckFix {
		public $compared;

		public function __construct($args) {
			$this->compared = $args['compared'] ?? 16;
		}

		public function Apply($args) {
			$text = $args['text'];
			$word = $args['word'];

			$pattern = '/(?<![\p{L}\p{N}])' . preg_quote($word, '/') . '(?![\p{L}\p{N}])/u';

			if(!preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
				return ['text'=>NULL, 'reason'=>'word not in the text'];
			}

			$found = [];

			foreach($matches[0] as $match) {
				$offset = $match[1];

				if($this->InsideTag(['text'=>$text, 'offset'=>$offset])) {
					continue;
				}

				if(!$this->ContextMatches([
					'text'=>$text,
					'offset'=>$offset,
					'length'=>strlen($word),
					'before'=>$args['before'],
					'after'=>$args['after'],
				])) {
					continue;
				}

				$found[] = $match;
			}

			if(count($found) === 0) {
				return ['text'=>NULL, 'reason'=>'sentence has changed'];
			}

			if(count($found) > 1) {
				return ['text'=>NULL, 'reason'=>count($found) . ' occurrences match'];
			}

			$offset = $found[0][1];
			$original = $found[0][0];
			$replacement = $this->Cased(['original'=>$original, 'fix'=>$args['fix']]);

			return [
				'text'=>substr($text, 0, $offset) . $replacement . substr($text, $offset + strlen($original)),
				'reason'=>'',
				'replacement'=>$replacement,
			];
		}

			/*
				A reviewer who wrote a capital meant it ("Goes", "Fred").  Otherwise
				the fix takes the case the word had in the text, so a typo at the
				start of a sentence stays capitalized.
			*/

		public function Cased($args) {
			$original = $args['original'];
			$fix = $args['fix'];

			if(preg_match('/\p{Lu}/u', $fix)) {
				return $fix;
			}

			if(mb_strlen($original) > 1 && mb_strtoupper($original) === $original) {
				return mb_strtoupper($fix);
			}

			if(preg_match('/^\p{Lu}/u', $original)) {
				return mb_strtoupper(mb_substr($fix, 0, 1)) . mb_substr($fix, 1);
			}

			return $fix;
		}

		public function InsideTag($args) {
			$before = substr($args['text'], 0, $args['offset']);

			$opened = strrpos($before, '<');
			$closed = strrpos($before, '>');

			return $opened !== FALSE && ($closed === FALSE || $closed < $opened);
		}

		public function ContextMatches($args) {
			$text = $args['text'];
			$offset = $args['offset'];

			$before = substr($args['before'], -$this->compared);
			$after = substr($args['after'], 0, $this->compared);

			if($before === '' && $after === '') {
				return FALSE;
			}

			$window = $this->compared * 40;

			$text_before = $this->Squash(['text'=>substr($text, max(0, $offset - $window), min($offset, $window))]);
			$text_after = $this->Squash(['text'=>substr($text, $offset + $args['length'], $window)]);

			if($before !== '' && substr($text_before, -strlen($before)) !== $before) {
				return FALSE;
			}

			if($after !== '' && substr($text_after, 0, strlen($after)) !== $after) {
				return FALSE;
			}

			return TRUE;
		}

			/*
				The same reduction the AI Workers export makes: tags out, entities
				decoded, lower case, and nothing kept but a-z and 0-9.  Accented
				letters and curly quotes drop out on both sides, which is what
				lets a dump with mojibake in it still match the live text.
			*/

		public function Squash($args) {
			$text = $args['text'];

			$text = preg_replace('/<[^>]*>/', ' ', $text);
			$text = preg_replace('/^[^<]*>/', ' ', $text);
			$text = preg_replace('/<[^>]*$/', ' ', $text);
			$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			$text = strtolower($text);

			return preg_replace('/[^0-9a-z]/', '', $text);
		}
	}

?>
