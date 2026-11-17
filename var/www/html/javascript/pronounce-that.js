$(document).ready(function(e) {
		// Google reads the phrase, not the browser: speechSynthesis has
		// whatever voices the browser ships, so the same word sounded
		// different in Chrome and in Brave.  pronounce.json answers with
		// the MP3 as base64, and nothing is kept anywhere.

	var audio = new Audio();
	var errors = {
		'empty':'Enter a word or a phrase first.',
		'long':'That is too long to pronounce at once.',
		'language':'Choose a language from the list.',
		'key':'Pronouncing is not available just now.',
		'allowance':'Pronouncing is resting for today. Please come back tomorrow.',
		'detect':'Could not tell the language. Please choose it from the list.',
		'unknown':'That language is not one we pronounce yet. Please choose one from the list.',
		'synthesis':'Google could not pronounce that. Please try again.',
	};

	$('#pronounce-it').click(function(e) {
		listenToPhrase();
	});

	function languageTitle(code) {
		return $('#language option[value="' + code + '"]').text();
	}

	function listenToPhrase() {
		var phrase = $('.input-area').val();
		var language = $('#language').val();

		audio.pause();
		$('#pronounce-it').prop('disabled', true);
		$('#status-text').text('Pronouncing…');

		$.ajax({
			'url':'/pronounce.json',
			'method':'POST',
			'data':{'text':phrase, 'language':language},
			'dataType':'json',
		}).done(function(answer) {
			if(!answer || answer.error !== 'none') {
				$('#status-text').text(errors[answer && answer.error] || errors['synthesis']);
				return;
			}

			$('#status-text').text(language === 'auto' ? 'Detected: ' + languageTitle(answer.language) : languageTitle(answer.language));

			audio.src = 'data:audio/mpeg;base64,' + answer.audio;
			audio.play().catch(function() {
				$('#status-text').text(errors['synthesis']);
			});
		}).fail(function() {
			$('#status-text').text(errors['synthesis']);
		}).always(function() {
			$('#pronounce-it').prop('disabled', false);
		});
	}
});
