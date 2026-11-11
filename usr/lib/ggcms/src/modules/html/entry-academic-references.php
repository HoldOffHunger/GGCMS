<?php

		/*
			The published books that back an entry, as a citation list at
			the foot of its page: shut by default, there for a reader who
			wants to see that the entry was checked.  Each line is the
			book -- authors, title, edition, publisher, year, ISBN, from its
			Publication record -- and where in it: volume, chapter, page and
			headword.  The book's own text is never printed.

			Only a reference whose verdict is supports or mentions is shown.
			A conflicts is a book that disagrees with the entry, kept for
			whoever reviews it rather than offered to a reader as proof.

			Nothing prints where the site has AcademicReference switched off
			or the entry has none to show.
		*/

	class module_entryacademicreferences extends module_spacing {
		public $that;

		public function __construct($args) {
			$this->that = $args['that'];
		}

		public function Display() {
			$references = $this->ShownReferences();

			if(!count($references)) {
				return TRUE;
			}

			$books = $this->Books(['references'=>$references]);

			print('<details class="block academic-references" id="references">');
			print('<summary>References (' . count($references) . ')</summary>');
			print('<ol class="academic-reference-list">');

			foreach($references as $reference) {
				$book = $books[(int)$reference['ReferenceEntryid']] ?? NULL;

				if(!$book) {
					continue;
				}

				print('<li class="academic-reference">');
				print($this->Citation(['book'=>$book, 'reference'=>$reference]));
				print('</li>');
			}

			print('</ol>');
			print('</details>');

			return TRUE;
		}

		public function ShownReferences() {
			$references = [];

			if(!$this->that->handler->abstractglobals->child_types->AcademicReference_enabled()) {
				return $references;
			}

			foreach((array)$this->that->entry['academicreference'] as $reference) {
				if($reference && (int)$reference['ReferenceEntryid'] && in_array($reference['Verdict'], ['supports', 'mentions'], TRUE)) {
					$references[] = $reference;
				}
			}

			return $references;
		}

			// The cited books, by id, each with its permalink's assignment and its Publication.

		public function Books($args) {
			$ids = array_values(array_unique(array_map(function($reference) { return (int)$reference['ReferenceEntryid']; }, $args['references'])));
			$marks = implode(', ', array_fill(0, count($ids), '?'));

			$books = [];

			$rows = $this->that->handler->db_access->RunQuery([
				'sql'=>'SELECT Entry.id, Entry.Title, Entry.Subtitle, MIN(Assignment.id) AS Assignmentid FROM Entry ' .
					'LEFT JOIN Assignment ON Assignment.Childid = Entry.id WHERE Entry.id IN (' . $marks . ') AND Entry.Publish = 1 GROUP BY Entry.id',
				'args'=>$ids,
			]);

			foreach((array)$rows as $row) {
				$books[(int)$row['id']] = $row + ['publication'=>NULL];
			}

			if(count($books) && $this->that->handler->abstractglobals->child_types->Publication_enabled()) {
				$publications = $this->that->handler->db_access->RunQuery([
					'sql'=>'SELECT * FROM Publication WHERE Entryid IN (' . $marks . ') ORDER BY id',
					'args'=>$ids,
				]);

				foreach((array)$publications as $publication) {
					$id = (int)$publication['Entryid'];

					if(isset($books[$id]) && !$books[$id]['publication']) {
						$books[$id]['publication'] = $publication;
					}
				}
			}

			return $books;
		}

			/*
				One line, as a bibliography prints it: Authors, Title:
				Subtitle, ed. Editors, trans. Translators, Edition, Publisher,
				Year, ISBN -- then the place in it.  The title links to the
				book's own page.
			*/

		public function Citation($args) {
			$book = $args['book'];
			$reference = $args['reference'];
			$publication = $book['publication'] ?: [];
			$text = function($value) { return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8'); };

			$parts = [];

			if(strlen(trim((string)($publication['Authors'] ?? '')))) {
				$parts[] = $text($publication['Authors'] ?? '');
			}

			$title = $text($book['Title']) . (strlen(trim((string)$book['Subtitle'])) ? ': ' . $text($book['Subtitle']) : '');

			if($book['Assignmentid']) {
				$title = '<a href="/?id=' . (int)$book['Assignmentid'] . '">' . $title . '</a>';
			}

			$parts[] = '<cite>' . $title . '</cite>';

			foreach(['Editors'=>'ed. ', 'Translators'=>'trans. ', 'Edition'=>'', 'Publisher'=>'', 'Year'=>''] as $field => $label) {
				if(strlen(trim((string)($publication[$field] ?? '')))) {
					$parts[] = $label . $text($publication[$field] ?? '');
				}
			}

			if(strlen(trim((string)($publication['ISBN'] ?? '')))) {
				$parts[] = 'ISBN ' . $text($publication['ISBN'] ?? '');
			}

			$place = [];

			if(strlen(trim((string)($reference['VolumeNumber'] ?? ''))) || strlen(trim((string)($reference['VolumeTitle'] ?? '')))) {
				$place[] = trim('vol. ' . $text($reference['VolumeNumber']) . (strlen(trim((string)($reference['VolumeTitle'] ?? ''))) ? ' (' . $text($reference['VolumeTitle']) . ')' : ''));
			}

			if(strlen(trim((string)($reference['ChapterNumber'] ?? ''))) || strlen(trim((string)($reference['ChapterTitle'] ?? '')))) {
				$place[] = trim('ch. ' . $text($reference['ChapterNumber']) . (strlen(trim((string)($reference['ChapterTitle'] ?? ''))) ? ' &ldquo;' . $text($reference['ChapterTitle']) . '&rdquo;' : ''));
			}

			if(strlen(trim((string)($reference['Page'] ?? '')))) {
				$place[] = 'p. ' . $text($reference['Page']);
			}

			if(strlen(trim((string)($reference['Headword'] ?? '')))) {
				$place[] = 's.v. &ldquo;' . $text($reference['Headword']) . '&rdquo;';
			}

			$citation = implode(', ', $parts) . '.';

			if(count($place)) {
				$citation .= ' ' . ucfirst(implode(', ', $place)) . '.';
			}

			if(strlen(trim((string)($reference['Note'] ?? '')))) {
				$citation .= ' <span class="academic-reference-note">' . $text($reference['Note']) . '</span>';
			}

			return $citation;
		}
	}

?>
