/*
	The site bar's night-reading toggle.  The choice is the reader's own,
	kept in their browser: the page cache serves one page to everyone, so
	it cannot come from the server.  The inline script at the top of
	<head> (HTML::StartHTML_Head_NightReading) applies it before the page
	is drawn; this changes it.
*/

(function() {
	function isDark() {
		var chosen = document.documentElement.getAttribute('data-theme');

		if(chosen) {
			return chosen === 'dark';
		}

		return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
	}

	function sync() {
		var buttons = document.querySelectorAll('[data-night-toggle]');

		for(var i = 0; i < buttons.length; i++) {
			buttons[i].setAttribute('aria-pressed', isDark() ? 'true' : 'false');
		}
	}

	document.addEventListener('click', function(event) {
		var button = event.target.closest ? event.target.closest('[data-night-toggle]') : null;

		if(!button) {
			return;
		}

		var next = isDark() ? 'light' : 'dark';

		document.documentElement.setAttribute('data-theme', next);

		try {
			localStorage.setItem('ggcms-theme', next);
		} catch(e) {}

		sync();
	});

	if(document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', sync);
	} else {
		sync();
	}
})();
