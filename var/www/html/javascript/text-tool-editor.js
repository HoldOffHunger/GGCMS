/*
	The text tools' boxes (text-tool.php) as CodeMirror editors, with line
	numbers that stay level with their lines at any zoom or screen scaling --
	the one thing the textarea line-number plugins never managed for long.

	CodeMirror 5 comes from cdnjs with its placeholder addon, listed before
	this in each tool's display_javascript.php.  Its stylesheet is fetched
	from here, and the boxes become editors only once it has arrived, so a
	reader never sees a half-styled editor; if it never arrives, or
	CodeMirror did not load, the plain boxes stay and still work.

	The tools' own scripts read and write the boxes with jQuery's val() and
	know nothing of CodeMirror, so the textarea valHook routes val() on a
	box to its editor: $('.input-area').val() reads what the reader typed,
	and $('.output-area').val(result) shows the result.
*/

(function($) {
	var codemirror_css = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.21/codemirror.min.css';

	var existing_hooks = $.valHooks.textarea;

	$.valHooks.textarea = {
		get: function(element) {
			if(element.textToolEditor) {
				return element.textToolEditor.getValue();
			}

			return (existing_hooks && existing_hooks.get) ? existing_hooks.get(element) : undefined;
		},
		set: function(element, value) {
			if(element.textToolEditor) {
				element.textToolEditor.setValue(value === null || value === undefined ? '' : String(value));
				element.textToolEditor.save();
				return true;
			}

			return (existing_hooks && existing_hooks.set) ? existing_hooks.set(element, value) : undefined;
		}
	};

	function makeEditors() {
		$('.tool-pane textarea').each(function() {
			var textarea = this;

			var editor = CodeMirror.fromTextArea(textarea, {
				lineNumbers: true,
				lineWrapping: true,
				mode: null,
				indentWithTabs: true,
				tabSize: 4,
				spellcheck: false
			});

				/*
					Keep the textarea current too, for anything that reads it
					directly; and when the reader types in the first box, tell
					its textarea, whose keyup the tools' scripts listen for to
					work a second after typing stops.  A value set by a script
					is not typing, so it is not passed on.
				*/

			editor.on('change', function(changed, change) {
				editor.save();

				if(change.origin !== 'setValue' && $(textarea).hasClass('input-area')) {
					$(textarea).trigger('keyup');
				}
			});

			textarea.textToolEditor = editor;
			$(textarea).closest('.tool-pane').addClass('has-editor');
		});
	}

	$(function() {
		if(typeof CodeMirror === 'undefined' || !$('.tool-pane textarea').length) {
			return;
		}

			/*
				Imported into the legacy layer, not linked: the site's styles
				are all in cascade layers, and a stylesheet outside them beats
				every layer whatever its specificity, so CodeMirror's own
				height, colours and fonts would win over 90-text-tool.css.
				A style element's load event waits for its import.
			*/

		var style = document.createElement('style');
		style.textContent = '@import url("' + codemirror_css + '") layer(legacy);';
		style.onload = makeEditors;
		document.head.appendChild(style);
	});
})(jQuery);
