<?php

	/*
		Byte counts, rendered for a person.

		This used to explode() the number against its own width, print_r the
		pieces, and return the number it was given.  check_free_space.php has
		therefore been printing a raw byte count and a stray Array dump since
		it was written, which is why the disk filling was noticed by the disk
		filling rather than by the tool built to notice it.

		The unit divisor is 1024.  df, du and disk_free_space all count binary
		units on this host, and a summary that disagrees with the command the
		operator will run next to check it is worse than no summary.
	*/

	trait ByteDisplay {
			// formatBytes()
			// Tests: ByteDisplayTest::testFormatBytes()
			// Test file: tests/cli/traits/ByteDisplayTest.php
		public function formatBytes($args) {
			$number = (float)$args['number'];

			$precision = array_key_exists('precision', $args) ? (int)$args['precision'] : 1;

			$negative = $number < 0;

			if($negative) {
				$number = 0 - $number;
			}

			$units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
			$unit_index = 0;
			$last_unit = count($units) - 1;

			while($number >= 1024 && $unit_index < $last_unit) {
				$number = $number / 1024;
				$unit_index++;
			}

				/*
					Bytes are whole things.  Printing "923.0 B" reads like a
					rounding of something larger, which it is not.
				*/

			if($unit_index === 0) {
				$precision = 0;
			}

			$formatted = number_format($number, $precision) . ' ' . $units[$unit_index];

			return ($negative ? '-' : '') . $formatted;
		}

			// formatBytesPadded()
			// Tests: ByteDisplayTest::testFormatBytesPadded()
			// Test file: tests/cli/traits/ByteDisplayTest.php
			/*
				For columns.  A table of sizes is read by comparing rows, and
				that only works when the numbers line up, so this pads to a
				fixed width rather than letting each row size itself.
			*/

		public function formatBytesPadded($args) {
			$width = array_key_exists('width', $args) ? (int)$args['width'] : 10;

			return str_pad($this->formatBytes($args), $width, ' ', STR_PAD_LEFT);
		}

			// formatSavedPercent()
			// Tests: ByteDisplayTest::testFormatSavedPercent()
			// Test file: tests/cli/traits/ByteDisplayTest.php
			/*
				Percentages saved, guarded against a zero original.  A file of
				no bytes cannot be shrunk, and dividing by it would take the
				whole report down.
			*/

		public function formatSavedPercent($args) {
			$before = (float)$args['before'];
			$after = (float)$args['after'];

			if($before <= 0) {
				return '0.0%';
			}

			return number_format((($before - $after) / $before) * 100, 1) . '%';
		}
	}

?>
