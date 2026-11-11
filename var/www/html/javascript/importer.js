/*
	The importer's header builder.

	Choosing a table asks the server for that table's columns --
	importer.json?action=Fields&table=Tag -- and shows them two ways: as
	one tab-delimited header line in a <code> block, ready to copy into a
	spreadsheet's first row, and as a list saying what each column does.
	Every column is optional; delete the ones you do not need.
*/

	// MDN's replacement: encodeURIComponent() leaves ! ' ( ) * alone.  See Development/Conventions.md.

function fixedEncodeURIComponent(str) {
	return encodeURIComponent(str).replace(/[!'()*]/g, function(c) {
		return '%' + c.charCodeAt(0).toString(16).toUpperCase();
	});
}

function importerShowFields(table) {
	var header = document.getElementById('importer-header');
	var list = document.getElementById('importer-fields');

	if(!header || !list) {
		return;
	}

	header.textContent = 'Reading the ' + table + ' table…';

	fetch('importer.json?action=Fields&table=' + fixedEncodeURIComponent(table), {credentials: 'same-origin'})
		.then(function(response) {
			return response.json();
		})
		.then(function(answer) {
			if(!answer || !answer.fields) {
				header.textContent = 'The server did not say what the ' + table + ' table holds.';
				list.textContent = '';
				return;
			}

			header.textContent = answer.header;

			var rows = document.createElement('table');
			rows.className = 'importer-fields';

			Object.keys(answer.fields).forEach(function(name) {
				var row = rows.insertRow();
				var column = row.insertCell();
				var code = document.createElement('code');
				code.textContent = name;
				column.appendChild(code);
				row.insertCell().textContent = answer.fields[name];
			});

			list.textContent = '';
			list.appendChild(rows);
		})
		.catch(function() {
			header.textContent = 'The server could not be asked for the ' + table + ' table\'s columns.';
		});
}

document.addEventListener('DOMContentLoaded', function() {
	var select = document.getElementById('importer-table');

	if(!select) {
		return;
	}

	select.addEventListener('change', function() {
		importerShowFields(select.value);
	});

	importerShowFields(select.value);
});
