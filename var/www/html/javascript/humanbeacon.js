/*
	humanbeacon.js -- counts people, not requests.

	The request statistics count every fetch, most of which are scrapers, and
	a reader of a cached page never reaches PHP at all.  Scrapers almost never
	run JavaScript, and fewer still move a mouse, scroll, press a key or touch
	the screen.  So this waits for the first of those, then posts one small
	JSON file about the page view to /humanbeacon.php -- a Blob in a FormData,
	sent with XMLHttpRequest, just as a form upload would be.

	Once per page view.  A visitor who never interacts sends nothing.
*/

(function() {
	var sent = false;
	var started = new Date().getTime();
	var events = ['mousemove', 'scroll', 'keydown', 'touchstart', 'pointerdown'];

	function getTimezone() {
		try {
			return Intl.DateTimeFormat().resolvedOptions().timeZone;
		} catch (error) {
			return '';
		}
	}

	function sendHumanBeacon(event) {
		if(sent) {
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

	for(var i = 0; i < events.length; i++) {
		window.addEventListener(events[i], sendHumanBeacon, {capture: true, passive: true});
	}
})();
