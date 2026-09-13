/*
	humanbeacon.js -- counts people, not requests.

	The request statistics count every fetch, most of which are scrapers, and
	a reader of a cached page never reaches PHP at all.  Scrapers almost never
	run JavaScript, and fewer still move a mouse, turn a wheel, press a key or
	touch the screen.  So this waits for the first of those, then posts one small
	JSON file about the page view to /humanbeacon.php -- a Blob in a FormData,
	sent with XMLHttpRequest, just as a form upload would be.

	Scroll is deliberately not one of them.  A script calling scrollTo fires
	real scroll events, and on its first day the beacon logged Applebot,
	Baiduspider's renderer and a fleet of headless Chromes scrolling their way
	down revoltlib.  A wheel, a pointer, a key and a touch are what a person
	does and a crawler rarely bothers to fake.  Events a page script dispatches
	itself are ignored, and so is a browser that declares itself automated.

	Once per page view.  A visitor who never interacts sends nothing.
*/

(function() {
	var sent = false;
	var started = new Date().getTime();
	var events = ['mousemove', 'wheel', 'keydown', 'touchstart', 'pointerdown'];

	function getTimezone() {
		try {
			return Intl.DateTimeFormat().resolvedOptions().timeZone;
		} catch (error) {
			return '';
		}
	}

	function sendHumanBeacon(event) {
		if(sent || !event.isTrusted) {
			return;
		}

		sent = true;

		for(var i = 0; i < events.length; i++) {
			window.removeEventListener(events[i], sendHumanBeacon, true);
		}

			// define data and connections

		var data = {
			'page': window.location.pathname,
			'referrer': document.referrer.split('?')[0].split('#')[0],
			'screen': screen.width + 'x' + screen.height,
			'language': navigator.language,
			'timezone': getTimezone(),
			'event': event.type,
			'milliseconds': new Date().getTime() - started
		};

		var blob = new Blob([JSON.stringify(data)], {type: 'application/json'});
		var xhr = new XMLHttpRequest();
		xhr.open('POST', '/humanbeacon.php', true);

			// define new form

		var formData = new FormData();
		formData.append('humanbeacon', blob, 'humanbeacon.json');

			// do the uploading

		xhr.send(formData);
	}

	if(navigator.webdriver) {
		return;
	}

	for(var i = 0; i < events.length; i++) {
		window.addEventListener(events[i], sendHumanBeacon, {capture: true, passive: true});
	}
})();
