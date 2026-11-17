	// Google's voice for a Play button, where the page loads this file.
	//
	// listen-to-audio-phrase.js is shared by several sites; only a page that
	// includes this file (EarthFluent's) asks pronounce.json first, and
	// every other page goes on using the browser's speechSynthesis.  Google
	// gives every browser the same voice; the browser's own voices differ
	// from Chrome to Brave.
	//
	// Anything short of a clip that plays -- the day's allowance spent,
	// Google down, a refused autoplay -- calls the fallback, which is the
	// browser's voice, so a lesson never goes quiet.  A phrase heard once
	// on a page is kept for the page, so a learner replaying a word does
	// not spend the allowance again.

var pronounceWithGoogleClips = {};
var pronounceWithGoogleAudio = new Audio();

function pronounceWithGoogle(args) {
	var phrase = $.trim(args.phrase || '');
	var language = (args.locale || '').split('-')[0].toLowerCase();
	var fallback = args.fallback;
	var key = language + '|' + phrase;

	if(!phrase || !language) {
		fallback();
		return;
	}

	function play(clip) {
		pronounceWithGoogleAudio.pause();
		pronounceWithGoogleAudio.src = 'data:audio/mpeg;base64,' + clip;
		pronounceWithGoogleAudio.play().catch(fallback);
	}

	if(pronounceWithGoogleClips[key]) {
		play(pronounceWithGoogleClips[key]);
		return;
	}

	$.ajax({
		'url':'/pronounce.json',
		'method':'POST',
		'data':{'text':phrase, 'language':language},
		'dataType':'json',
	}).done(function(answer) {
		if(!answer || answer.error !== 'none' || !answer.audio) {
			fallback();
			return;
		}

		pronounceWithGoogleClips[key] = answer.audio;
		play(answer.audio);
	}).fail(function() {
		fallback();
	});
}
