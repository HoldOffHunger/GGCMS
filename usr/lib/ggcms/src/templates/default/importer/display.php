<?php

			// Standard Requires

		// -------------------------------------------------------------

	ggreq('modules/spacing.php');

	ggreq('modules/html/text.php');
	$text = new module_text;

	ggreq('modules/html/divider.php');
	$divider = new module_divider;

			// Display Header

		// -------------------------------------------------------------

	ggreq('modules/html/entry-header.php');
	ggreq('modules/html/entry-index-header.php');
	$entryindexheader = new module_entryindexheader([
		'that'=>$this,
		'main_text'=>$this->header_title_text . ' &mdash; Importer',
		'sub_text'=>'New entries, changes and deletions from a file, each one saved exactly as modify.php saves it.',
	]);

	$entryindexheader->Display();

			// Start Top Bar

		// -------------------------------------------------------------

	print('<div class="horizontal-center width-95percent margin-top-5px">');

	ggreq('modules/html/breadcrumbs.php');
	$breadcrumbs = new module_breadcrumbs(['that'=>$this, 'title'=>'Importer']);
	$breadcrumbs->Display();

	ggreq('modules/html/auth.php');
	$auth = new module_auth(['that'=>$this]);
	$auth->Display();

	print('</div>');

	print('<div class="clear-float"></div>');

	$h = function($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };

			// The Report

		// -------------------------------------------------------------

	$report = $this->import_report;

	if($this->import_upload_error) {
		print('<div class="importer-panel importer-error"><p>' . $h($this->import_upload_error) . '</p></div>');
	}

	if($report) {
		print('<div class="importer-panel">');
		print('<h2>' . ($report['apply'] ? 'Import Report' : 'Preview &mdash; nothing was written') . '</h2>');

		print('<p>Table: <b>' . $h($report['table']) . '</b> &bull; Format: <b>' . $h($report['format']) . '</b>');

		if($report['images']) {
			print(' &bull; Images in the zip: <b>' . (int)$report['images'] . '</b>');
		}

		print('</p>');

		foreach($report['fileerrors'] as $error) {
			print('<p class="importer-error"><b>Refused:</b> ' . $h($error) . '</p>');
		}

		if(count($report['counts'])) {
			print('<ul>');

			foreach($report['counts'] as $outcome => $count) {
				print('<li><b>' . $h($outcome) . '</b>: ' . (int)$count . '</li>');
			}

			print('</ul>');
		}

		if(count($report['rows'])) {
			print('<table class="importer-report">');
			print('<tr><th>Line</th><th>What it did</th><th>Details</th></tr>');

			foreach($report['rows'] as $row) {
				$class = 'importer-' . strtolower(str_replace(' ', '-', $row['outcome']));

				print('<tr class="' . $h($class) . '">');
				print('<td>' . $h($row['line']) . '</td>');
				print('<td>' . $h($row['outcome']) . '</td>');
				print('<td>' . implode('<br>', array_map($h, $row['details'])) . '</td>');
				print('</tr>');
			}

			print('</table>');
		}

		if(strlen(trim($report['pagecache']))) {
			print('<p>' . nl2br($h(trim($report['pagecache']))) . '</p>');
		}

		if(!$report['apply'] && strlen($this->import_token) && !count($report['fileerrors'])) {
			print('<form method="POST" action="importer.php">');
			print('<input type="hidden" name="action" value="Import">');
			print('<input type="hidden" name="ImportToken" value="' . $h($this->import_token) . '">');
			print('<input type="hidden" name="ImportTable" value="' . $h($report['table']) . '">');
			print('<input type="hidden" name="ImportLimit" value="' . $h($this->Param('ImportLimit')) . '">');
			print('<p><input type="submit" value="Import these rows for real"> &mdash; the same file, run again with the belt switched on.  Rows that say Error above will say it again.</p>');
			print('</form>');
		}

		print('</div>');
	}

			// The Form

		// -------------------------------------------------------------

	print('<div class="importer-panel">');
	print('<h2>Import a File</h2>');

	print('<form method="POST" action="importer.php" enctype="multipart/form-data">');

	print('<p><label>Table: <select id="importer-table" name="ImportTable">');

	foreach($this->import_tables as $table) {
		$selected = ($table === ($report ? $report['table'] : 'Entry')) ? ' selected' : '';
		print('<option value="' . $h($table) . '"' . $selected . '>' . $h($table) . '</option>');
	}

	print('</select></label> &mdash; what a row is, what <code>id</code> and <code>Delete</code> mean, and what a column with no table in its name belongs to.</p>');

	print('<p>This table\'s columns, as a header line to copy &mdash; tab-delimited, every column optional:</p>');
	print('<pre class="importer-header"><code id="importer-header">Entry_id&#9;Entry_Title</code></pre>');
	print('<div id="importer-fields"></div>');

	print('<p><label>File: <input type="file" name="ImportFile"></label></p>');
	print('<p><label>Images (a .zip, if the file is not already in one): <input type="file" name="ImportImages" accept=".zip"></label></p>');
	print('<p>Or paste it:<br><textarea name="ImportText" rows="10" cols="100" placeholder="Entry_Title&#9;Tag_Tag"></textarea></p>');
	print('<p><label>Rows to attempt: <input type="number" name="ImportLimit" value="' . $h($report ? $this->Param('ImportLimit') : '100') . '" min="0" size="8"></label> (0 for all)</p>');

	print('<p><button type="submit" name="action" value="Preview">Preview</button> ');
	print('<button type="submit" name="action" value="Import" class="importer-danger">Import now, without a preview</button></p>');

	print('</form>');
	print('</div>');

			// The Manual

		// -------------------------------------------------------------

	print('<div class="importer-panel importer-manual">');

	print('<h2>How It Works</h2>');

	print('<p>Every row is put through the same conveyor belt as the Edit form: read, cleansed, validated, prepared, backed up, saved.  A title gets the same smart title case, a text body the same cleaning, a date before the year 1000 the same encoding, a code the same check that no sibling holds it.  An update files every old value in <b>RecordChange</b> before anything is written, exactly as an edit does &mdash; so an import leaves the history an edit leaves.</p>');

	print('<p><b>Preview first.</b>  A preview runs every row through the belt and stops it just before saving: every check is made, every error reported, nothing written.  The Import button beneath a preview runs the same file again for real.</p>');

	print('<h3>The Header</h3>');

	print('<p>The first row names the columns.  A column is <code>Table_Field</code> &mdash; <code>Entry_Title</code>, <code>Tag_Tag</code>, <code>Tag_Language</code>, <code>TextBody_Text</code>.  Choose a table above to see all of its columns.  Case does not matter, and every column is optional: a file may be just <code>Entry_id&#9;Delete</code>, or just <code>Entry_Title</code>.</p>');

	print('<p>A column with no table in its name belongs to the table chosen above, so for a Tag import <code>id&#9;Tag&#9;Language</code> means <code>Tag_id&#9;Tag_Tag&#9;Tag_Language</code>.</p>');

	print('<p><b>A column the importer does not know refuses the whole file</b>, and names the column.  A misspelt <code>Entry_Titel</code> would otherwise be read as nothing and its values thrown away without a word.</p>');

	print('<p>A column may be named twice.  The second <code>Tag_Tag</code> is a second tag, and goes with the second <code>Tag_Language</code> and the second <code>Tag_id</code>.</p>');

	print('<h3>What a Row Does</h3>');

	print('<ul>');
	print('<li><b>New record.</b>  A row with no id makes a new entry, or, for a child table, a new record on the entry it names.</li>');
	print('<li><b>Updated old record.</b>  A row with an id &mdash; <code>Entry_id</code>, or <code>Tag_id</code> for a tag &mdash; changes that record, and only that record.  Only the columns the file gives are changed; everything else the entry has stays exactly as it was.</li>');
	print('<li><b>Deleted old record.</b>  A <code>Delete</code> column of <code>Y</code> or <code>YES</code> deletes the record the row names by its id: the entry for an Entry import, with all its child records and its place in the tree; the tag for a Tag import.  Whatever is deleted is backed up whole in <b>RecordChange</b> first, and if the backup cannot be written nothing is deleted.  A delete row may name nothing but the id.</li>');
	print('<li><b>Unchanged.</b>  The record already says what the row says.  A tag the entry already has is not added twice.</li>');
	print('<li><b>Skipped.</b>  Not done, and not a fault: a new entry whose parent already has one with that title, or a <code>Current_</code> column the record no longer matches.</li>');
	print('<li><b>Error.</b>  Not done, because something is wrong, with every reason the belt gave.  Nothing of that entry is written.</li>');
	print('</ul>');

	print('<p>A blank cell says nothing: it never empties a field.</p>');

	print('<h3>Which Entry a Row Belongs To</h3>');

	print('<p>The belt saves one entry at a time, with all its records, so rows are gathered into entries first.  A row belongs to:</p>');

	print('<ol>');
	print('<li>the entry its <code>Entry_id</code> names, or the entry that owns the <code>Tag_id</code> (or other id) it names;</li>');
	print('<li>otherwise the entry its <code>Entry_Identifier</code> names &mdash; a name of your own, never stored, that makes several rows one entry;</li>');
	print('<li>otherwise the new entry with its <code>Entry_Title</code> beneath the same parent.</li>');
	print('</ol>');

	print('<p>So two rows titled <i>Poetry</i>, one tagged <i>verse</i> and one tagged <i>rhyme</i>, make one entry with two tags.  To make two different entries that happen to share a title, give each its own <code>Entry_Identifier</code>.</p>');

	print('<p>Two rows of one entry may not disagree &mdash; a second title, or a second parent, is an error on the row that brought it.</p>');

	print('<h3>Where a New Entry Goes</h3>');

	print('<ul>');
	print('<li><code>Entry_ParentPath</code> &mdash; the path, as in a URL: <code>/books/poetry/</code>.</li>');
	print('<li><code>Assignment_Parentid</code> &mdash; the parent\'s entry id.</li>');
	print('<li><code>Entry_ParentIdentifier</code> &mdash; another row\'s <code>Entry_Identifier</code>.  That entry is made first, however late in the file it comes, and this one is filed beneath it.  Whole trees import this way.</li>');
	print('<li>None of these, and it goes at the top of the site.</li>');
	print('</ul>');

	print('<p>A new entry titled like one its parent already has is skipped and its twin named &mdash; usually it is the same file run twice.  <code>Option_allow-duplicate-title</code> of 1 makes it anyway.  A new entry is published, as the Add form publishes one, unless <code>Entry_Publish</code> is 0.  An existing entry is never moved by an import.</p>');

	print('<h3>Guarding an Update</h3>');

	print('<p><code>Current_</code> before any field column is the value that field must still have: <code>Entry_id&#9;Current_Entry_Title&#9;Entry_Title</code>.  If someone has edited it since the file was made, the row is skipped and says so &mdash; their edit is newer evidence than the file.</p>');

	print('<h3>Switches</h3>');

	print('<p>The Add form\'s checkboxes, as columns of 1 or 0:</p>');
	print('<ul>');

	foreach($this->EntryQueryOptions() as $option => $description) {
		print('<li><code>Option_' . $h($option) . '</code> &mdash; ' . $h($description) . '.</li>');
	}

	print('<li><code>Option_allow-duplicate-title</code> &mdash; make a new entry even where its parent has one titled alike.</li>');
	print('</ul>');

	print('<p>A new text body is cleaned as the Add form cleans one: URLs stripped, vocabulary Americanized, and HTML formatting as this site does it.  <code>TextBody_StripURLs</code>, <code>TextBody_AmericanizeVocabulary</code>, <code>TextBody_HTMLFormatting</code> and <code>TextBody_SpellCheckImmune</code> of 1 or 0 say otherwise.  An existing text body is posted back with every cleaning off.</p>');

	print('<h3>Images</h3>');

	print('<p>Put the file and its pictures in one .zip, or give the pictures as a second .zip beside the file.  A new image is a row with <code>Image_Title</code> and <code>Image_FileName</code>, and its picture is the file in the zip with that name &mdash; folders inside the zip ignored, case ignored.  Each picture makes one image.  The image is resized and filed exactly as an upload through the Edit form is.</p>');

	print('<h3>Formats</h3>');

	print('<ul>');

	foreach($this->import_formats as $format => $description) {
		print('<li>' . $h($description) . '</li>');
	}

	print('</ul>');

	print('<p>In JSON, a list value is a column named more than once: <code>{"Entry_Title": "Poetry", "Tag_Tag": ["verse", "rhyme"]}</code> is one entry with two tags.  Everything is UTF-8, and cleansed on the way in exactly as a form\'s post is.</p>');

	print('<h3>Examples</h3>');

	print('<p>New entries under a section, each with a tag:</p>');
	print('<pre><code>Entry_ParentPath&#9;Entry_Title&#9;Tag_Tag' . "\n" . '/books/&#9;Arc Lamps&#9;lighting' . "\n" . '/books/&#9;Gas Mantles&#9;lighting</code></pre>');

	print('<p>A section and its children in one file:</p>');
	print('<pre><code>Entry_Identifier&#9;Entry_ParentIdentifier&#9;Entry_Title' . "\n" . 'lesson-1&#9;&#9;Lesson One' . "\n" . 'w1&#9;lesson-1&#9;Hello' . "\n" . 'w2&#9;lesson-1&#9;Goodbye</code></pre>');

	print('<p>Translations added to existing entries:</p>');
	print('<pre><code>Entry_id&#9;EntryTranslation_Language&#9;EntryTranslation_Title' . "\n" . '51774&#9;es&#9;coreano</code></pre>');

	print('<p>A translation corrected, guarded by what it says now (a Translation import):</p>');
	print('<pre><code>EntryTranslation_id&#9;Current_EntryTranslation_Title&#9;EntryTranslation_Title' . "\n" . '90211&#9;Corea&#9;coreano</code></pre>');

	print('<p>A mass delete:</p>');
	print('<pre><code>Entry_id&#9;Delete' . "\n" . '1201&#9;Y' . "\n" . '1202&#9;Y</code></pre>');

	print('<p>From a shell, the same importer is <code>import_entries.php</code>; see Docs/CommandLineTools.md.</p>');

	print('</div>');

?>
