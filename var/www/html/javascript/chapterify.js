$(document).ready(function(event){
	//console.log("chapterify!!!!");
	$('#text-source').on('change', function(e) {
		if(this.value === 'this-text') {
			$('#textbody').val($('#textbody-hidden').val());
		} else if(this.value === 'custom') {
			$('#textbody').val('');
		}
		
		return true;
	});
	
	function stripHtml(html){
		let doc = new DOMParser().parseFromString(html, 'text/html');
		return doc.body.textContent || '';
	}
	
	function getChapters(args) {
		const input = args.input;
		const delimiter = args.delimiter;
		const title_preface = args.title_preface;
		
		const input_pieces = input.split(delimiter);
		const input_pieces_count = input_pieces.length;
		
		if(input_pieces_count === 1) {
			$('#results').html('<bold>ERROR!</bold> Your `Match Text` did NOT match anything!');
			return false;
		}
		
		const chapters = [];
		
		for(var i = 0; i < input_pieces_count; i++) {
			const input_piece = input_pieces[i];
			//console.log(input_piece);
			const input_pieces_split = input_piece.split("\n");
			const first_line = input_pieces_split.shift();
			const raw_title = first_line ? delimiter + first_line : '';
			const good_title = stripHtml(raw_title)
				.replace('CHAPTER', 'Chapter')
				.replace('LETTER', 'Letter')
				.replace('PART', 'Part')
				.replace('SECTION', 'Section')
				.replace('VOLUME', 'Volume')
			;
			const good_text = input_pieces_split.join("\n");
			
			const submit_title = good_title.length !== 0 ? title_preface + good_title : 'PREFACE MATERIAL';
			
			chapters.push({
				'original_title':good_title,
				'submit_title':submit_title,
				'text':good_text,
			});
		}
		
		return chapters;
	}
	
	function escapeHtml(unsafe) {
		return unsafe
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	}
	
	$(document).on('submit', '.submit-button', handleSubmitClick);
	
	var handleSubmitClick = function(e) {
		console.log("chapter submit!");
		
	//	this.setAttribute('disabled', 'true');
		const closestdiv = $(this).closest('div');
		$(closestdiv).css('visibility', 'hidden');
		$(closestdiv).css('height', '0px');
	//	$(closestdiv).fadeOut(5000);
		$(closestdiv).after('<p style="border:1px solid black;margin:20px;"><b><i>ADDED ' + this.getAttribute('data-title') + '!</i></b></p>');
		
	//	e.preventDefault();
	//	e.stopPropagation();
		
	//	return false;
		return true;
	}
	
	//$(document).on( 'click', '.submit-button', handleSubmitClick(event) );
	//$('.submit-button').on('click', );
	
	function renderChapters(args) {
		const chapters = args.chapters;
		
		$('#results').empty();
		
		const chapters_length = chapters.length;
		const source = $('#source').val();
		
		for(var i = 0; i < chapters_length; i++) {
			const chapter = chapters[i];
			
			const chapterhtml =
				'<div style="border:1px solid black;">' +
				'<form target="_blank" method="POST" action="modify.php?action=Save">' +
				'<h3>' + chapter.submit_title + '</h3>' +
				'<b>Title:</b> <input type="text" name="Title" value="' + chapter.submit_title + '" /> ' +
				'Smart Title Case: <input type="checkbox" value="1" name="title-smart-title-case" id="title-smart-title-case" checked="CHECKED">' +
				'De-Romanize Numbers: <input type="checkbox" value="1" name="title-de-romanize-numbers" id="title-de-romanize-numbers" CHECKED="CHECKED">' + '<br>' +
				'<b>Subtitle:</b> <input type="text" name="Subtitle" value="" /><br> ' +
				'<b>Text:</b> <textarea name="Text[]" cols="70" rows="15">' + escapeHtml(chapter.text) + '</textarea>' + '<br>' +
				'Strip URLs: <input type="checkbox" name="textbody_StripURLs[]" size="30" maxlength="512" value="1" CHECKED="CHECKED"><br>' +
				'Americanize: <input type="checkbox" name="textbody_AmericanizeVocabulary[]" size="30" maxlength="512" value="1" CHECKED="CHECKED"><BR>' +
				'Language: <input type="text" name="textbody_Language[]" size="30" maxlength="512" value="en"><BR>' +
				'Source: <input type="text" name="textbody_Source[]" size="30" maxlength="512" value="' + source + '"><BR>' +
				'HTML Formatting: <input type="checkbox" name="textbody_HTMLFormatting[]" size="30" maxlength="512" value="1"><br>' +
				'Publish: <input type="checkbox" name="Publish" value="1" checked="CHECKED">' + '<br>' +
				'<input class="submit-button" type="submit" value="Add" data-title="' + chapter.submit_title + '"/>' +
				'</form>' +
				'</div>'
			;
			
		//	$(document.body).append(chapterhtml);
			$('#results').append(chapterhtml);
		//	$(document).on('submit', '.submit-button', handleSubmitClick);
		//	$('.submit-button').on('click', handleSubmitClick);
		}
		
		return true;
	}
	$(document.body).on('click', '.submit-button', handleSubmitClick);
	
	$('#chapterify-button').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		console.log("chapterify go!!!");
		
		const input = $('#textbody').val();
		const delimiter = $('#match-text').val();
		const title_preface = $('#title-preface').val();
		
		const func_args = {
			'input':input,
			'delimiter':delimiter,
			'title_preface':title_preface,
		};
		
		const chapters = getChapters(func_args);
		renderChapters({'chapters':chapters});
		//console.log(input_pieces.length);
		
		return false;
	});
});