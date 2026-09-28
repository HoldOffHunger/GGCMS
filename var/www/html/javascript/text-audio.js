$(document).ready(function(e) {
	window.speechSynthesis.cancel();	// clean the buffer
	var synth = window.speechSynthesis;
	var utterance = new SpeechSynthesisUtterance();
	var voices = [];
	
	var myTimeout;
	function myTimer() {
		window.speechSynthesis.pause();
		window.speechSynthesis.resume();
		myTimeout = setTimeout(myTimer, 10000);
	}
	
	window.speechSynthesis.onvoiceschanged = function () {
	//	if(voices.length > 0) {
	//		return false;
	//	}
		
		updateLanguage();
		setLanguages();
		
		return true;
	}
	
	function updateLanguage() {
		languagecode = $('#voice-selection').val();
		voices = window.speechSynthesis.getVoices();
		for(i = 0; i < voices.length; i++) {
			voice = voices[i];
			if(voice.voiceURI == languagecode) {
				utterance.voice = voice;
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
		var favoritelanguage = false;
		
		for(i = 0; i < voiceoptions.length; i++) {
			const voiceoption = voiceoptions[i];
			if(voiceoption.value === 'Microsoft Zira - English (United States)') {
				favoritelanguage = voiceoption.value;
			}
			htmlvoiceoptions.push(
				'<option value="' + voiceoption.value + '" data-lang="' + voiceoption.value + '" data-name="' +  voiceoption.display + '">' + voiceoption.display + '</option>'
			);
		}
		
		$('#voice-selection').html(htmlvoiceoptions.join(''));
		
		if(favoritelanguage) {
			$('#voice-selection').val(favoritelanguage);
		} else {
			$('#voice-selection').val('Microsoft Zira - English (United States)');
		}
		return true;
	}
	
	function setWordCount() {
		$('#total-words').html(getPhrase().split(' ').length.toLocaleString());
	}
	
	var currentwordnumber = 1;
	
	var wordtimeout = false;
	
	function incrementWordNumber() {
	//	currentwordnumber += 1;
		currentwordnumber += .120;
	//	currentwordnumber += .100;
	//	currentwordnumber += .125;
		if(currentwordnumber > phrase_wordcount) {
			currentwordnumber = phrase_wordcount;
		}
		$('#start-on').val(Math.ceil(currentwordnumber));
		
		updateProgressBar();
		
		return true;
	}
	
	function incrementWordNumberInterval() {
	//	return 400;
		return 50;
	}
	
	
	
	function updateProgressBar() {
		var fulllength = getFullPhrase().split(' ').length;
		var startvalue = getStartOnValue();
	//	console.log("BT: FULL!" + startvalue + " / " + fulllength + "|");
		var diff = (startvalue / fulllength) * 100;
		$('#reading-progress-bar').prop('value', diff);
		
		return true;
	}
	
	function pauseFunction(e) {
		if(paused) {
			lastpause = Date.now();
			window.speechSynthesis.resume();
			$(this).text('Pause');
			
			myTimeout = setTimeout(myTimer, 10000);	// without this, the speaking stops after 30 seconds, wtf?
			utterance.onend = function() {clearTimeout(myTimeout); }
			
			wordtimeout = setInterval(function() {
				incrementWordNumber();
			}, incrementWordNumberInterval());
		} else {
			clearTimeout(myTimeout);
			currenttime += Date.now() - lastpause;
			window.speechSynthesis.pause();
			lastpause = Date.now();
			
			clearInterval(wordtimeout);
/*
			var seconds = currenttime / 1000;
			
			var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
			
			if(isSafari) {
				seconds *= 1000;
			}
			var speakingrate = 150 / 60;	// 150 words per minute
			
			var currentword = Math.ceil(seconds * speakingrate);
			
			if(currentword < 1) {
				currentword = 1;
			}
			
			
			$('#start-on').val(currentword);*/
			$(this).text('Resume');
		}
		
		//$('#start-on')
		
		//console.log("BT: REAL!" + currenttime);
		
		
	//	console.log("BT: REAL!" + currentword);
		
	//	diff = currenttime - subtracttime;
		
	//	console.log("BT: PAUSE, real time?" + diff + "|");
		paused = !paused;
	}

	var listening = false;
	var paused = false;
	var currenttime = 0;
	
	var starttime = 0;
	var subtracttime = 0;
	var lastpause = 0;
	$('#play-text-as-audio').click(function(e) {
		var testutt = new SpeechSynthesisUtterance('');
		synth.speak(testutt);
		window.speechSynthesis.cancel();	// clean the buffer
		if(!listening) {
			listening = true;
			$(this).text('Stop');
			var pausebutton = $('<button type="button" id="pause-button" class="btn btn-line">Pause</button>');
			pausebutton.insertAfter(this);
			pausebutton.on('click', pauseFunction);
			listenToPhrase();
			
			starttime = Date.now();
			lastpause = Date.now();
			currentwordnumber = getStartOnValue();
			wordtimeout = setInterval(function() {
				incrementWordNumber();
			}, incrementWordNumberInterval());
			
			$('#start-on').prop('readonly', true);
		//	$('#start-on').prop('disabled', true);
		} else {
			$('#start-on').val(1);
			currenttime = 0;
			listening = false;
			resetPlayButton();
			$('#pause-button').remove();
			window.speechSynthesis.cancel();
			clearInterval(wordtimeout);
			currentwordnumber = 1;
			$('#start-on').prop('readonly', false);
		//	$('#start-on').prop('disabled', false);
		}
	});
	
	function resetPlayButton() {
		$('#play-text-as-audio').text('Listen');
	}
	
	resetPlayButton();
	
	// BT: Uh, yeah, this doesn't work on Google voices. wtf2
	/*
	utterance.onpause = function(event) {
	    console.log(event);
	    console.log('Speech paused after ' + event.elapsedTime + ' seconds.');
	    console.log("BT: Real current time???" + currenttime + "|");
	    return true;
	}
	
	utterance.onresume = function(event) {
	    console.log(event);
	    console.log('Speech resumed after ' + event.elapsedTime + ' seconds.');
	    return true;
	}
	*/
	function listenToPhrase() {
		setLanguageConfig();
		utterance.text = getPhrase();
			// is this needed?  it's killing pausing from working and seems unnecessary
		myTimeout = setTimeout(myTimer, 10000);	// without this, the speaking stops after 30 seconds, wtf?
		utterance.onend = function() {clearTimeout(myTimeout); }
		
		synth.speak(utterance);
	}
	
	var phrase = '';
	var phrase_wordcount = 0;
	
	var fullphrase = '';
	
	function getFullPhrase() {
		if(fullphrase.length > 0) {
			return fullphrase;
		}
		
		var textsource = $('.text-to-play-as-audio').clone();
		divchildren = $(textsource).find('div');
		
		if(divchildren.length) {
			$(divchildren).remove();
		}
		
		fullphrase = $(textsource).text();
		
		return fullphrase;
	}
	
	function getPhrase() {
	/*	if(phrase.length > 0) {			// cannot cache this, different start-on seconds mean diff shit
			return phrase;
		}*/
		
		var textsource = $('.text-to-play-as-audio').clone();
		divchildren = $(textsource).find('div');
		
		if(divchildren.length) {
			$(divchildren).remove();
		}
		
		phrase = $(textsource).text();
		words = getStartOnValue() - 1;
		
		phrasepieces = phrase.split(' ');
		
		if(phrase_wordcount === 0) {
			phrase_wordcount = phrasepieces.length;
		}
		
		while(words > 0) {
			words--;
			phrasepieces.shift();
		}
		
		phrase = phrasepieces.join(' ');
		
		return phrase;
	}
	
	function getStartOnValue() {
		return parseInt($('#start-on').val(), 10);
	}
	
	function setLanguageConfig() {
		languagecode = $('#voice-selection').val();
		updateLanguage();
		
		if(languagecode && languagecode.match(' UK ')) {
			utterance.lang = 'en-UK';
		} else {
			utterance.lang = 'en-US';
		}
		
		return true;
	}
	
	/*utterance.onboundary = function(event) {
		console.log("ONBOUNDARY!");
		var word = getWordAt(getPhrase(),event.charIndex);
		console.log("BT: WORD!" + word + "|");
	};*/
	
//utterance.onboundary = function(event) {
//    console.log(event.name + ' boundary reached after ' + event.elapsedTime + ' seconds.');
//  }
	
function getWordAt(str, pos) {
    // Perform type conversions.
    str = String(str);
    pos = Number(pos) >>> 0;

    // Search for the word's beginning and end.
    var left = str.slice(0, pos + 1).search(/\S+$/),
        right = str.slice(pos).search(/\s/);

    // The last word in the string is a special case.
    if (right < 0) {
        return str.slice(left);
    }
    // Return the word, using the located bounds to extract it from the string.
    return str.slice(left, right + pos);
}
	
	setWordCount();
}); 