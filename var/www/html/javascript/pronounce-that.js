$(document).ready(function(e) {
	var languagecode;
	var utterance = new SpeechSynthesisUtterance();
	var voices = [];
	
	window.speechSynthesis.onvoiceschanged = function () {
		if(voices.length > 0) {
			return false;
		}
		
		updateLanguage();
		setLanguages();
		
		return true;
	}
	
		// the chosen voice, and its own language: a French voice told it is
		// reading English reads it as English
	function updateLanguage() {
		voices = window.speechSynthesis.getVoices();
		for(i = 0; i < voices.length; i++) {
			voice = voices[i];
			if(voice.voiceURI == languagecode) {
				utterance.voice = voice;
				utterance.lang = voice.lang;
				i = voices.length;
			}
		}
	}
	
	function setLanguages() {
		const voiceoptions = [];
		voices = window.speechSynthesis.getVoices();
		
		for(i = 0; i < voices.length; i++) {
			voice = voices[i];
			
			voiceoptions.push({
				'value':voice.voiceURI,
				'display':voice.name,
			});
		}
		
		htmlvoiceoptions = [];
		
		for(i = 0; i < voiceoptions.length; i++) {
			const voiceoption = voiceoptions[i];
			htmlvoiceoptions.push(
				'<option value="' + voiceoption.value + '" data-lang="' + voiceoption.value + '" data-name="' +  voiceoption.display + '">' + voiceoption.display + '</option>'
			);
		}
		
		$('#language').html(htmlvoiceoptions.join(''));

			// start on a voice in the page's own language -- the browser's
			// default one if it has one -- not on whichever it lists first
		var pagelanguage = (document.documentElement.lang || 'en').toLowerCase().split('-')[0];
		var chosen = null;

		for(i = 0; i < voices.length; i++) {
			if(voices[i].lang.toLowerCase().split(/[-_]/)[0] === pagelanguage && (!chosen || voices[i].default)) {
				chosen = voices[i];
			}
		}

		if(chosen) {
			$('#language').val(chosen.voiceURI);
		}
	}

		// a browser that has its voices already -- Firefox, often -- never
		// announces them, so the list is filled now as well as when they arrive
	if(window.speechSynthesis.getVoices().length > 0) {
		updateLanguage();
		setLanguages();
	}

	$('#pronounce-it').click(function(e) {
		listenToPhrase();
	});
	
	function listenToPhrase() {
		utterance = new SpeechSynthesisUtterance();
		var phrase = $('.input-area').val();
		
		languagecode = $('#language').val();
		
			// before the voices arrive the list holds only English
		utterance.lang = 'en-US';

		updateLanguage();

		utterance.text = phrase;
		window.speechSynthesis.speak(utterance);
	}
});