<?php

		/*
			The published books that back an entry, as a citation list at
			the foot of its page: the first lines shown, fading under a Show
			more button, enough to tempt a reader who wants to see that the
			entry was checked.  The heading's arrow or the button opens the
			rest.  Without JavaScript the whole list shows, since the script
			is what folds it.  Each line is the
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

			print('<section class="block academic-references" id="references" aria-label="References">');
			print('<button type="button" class="academic-references-toggle" aria-expanded="true" aria-controls="academic-references-body">');
			print('<span class="academic-references-arrow" aria-hidden="true">&#9660;</span> References (' . count($references) . ')');
			print('</button>');
			print('<div class="academic-references-body" id="academic-references-body">');
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
			print('<button type="button" class="academic-references-more" hidden>Show more</button>');
			print('</div>');
			print('</section>');

			$this->DisplayScript();

			return TRUE;
		}

			/*
				Folds the list to its first lines, and opens it from the
				heading or the Show more button.  A list short enough to show
				whole is left open, with no button to press for nothing.
			*/

		public function DisplayScript() {
			print('
				<script>
					(function() {
						var block = document.getElementById("references");
						var toggle = block.querySelector(".academic-references-toggle");
						var arrow = block.querySelector(".academic-references-arrow");
						var body = block.querySelector(".academic-references-body");
						var more = block.querySelector(".academic-references-more");

						function show(open) {
							block.classList.toggle("is-folded", !open);
							toggle.setAttribute("aria-expanded", open ? "true" : "false");
							arrow.innerHTML = open ? "&#9660;" : "&#9654;";
							more.hidden = open;
						}

						block.classList.add("is-folded");

						if(body.scrollHeight <= body.clientHeight + 4) {
							block.classList.remove("is-folded");
							return;
						}

						show(false);

						toggle.addEventListener("click", function() { show(block.classList.contains("is-folded")); });
						more.addEventListener("click", function() { show(true); });
					})();
				</script>
			');

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
