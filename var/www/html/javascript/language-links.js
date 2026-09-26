/*
	language-links.js -- keeps a reader in the language they chose.

	The page cache serves one copy of each page and knows nothing about
	cookies, so the language a reader picks cannot follow them from page to
	page on the server's side.  It can follow them in the links.  This script
	is only included when a page is rendered in a language other than the
	site's default, and it adds ?language= for that language to every link
	that stays on this site, so the next page is asked for in it too.

	Links that already name a language are left alone -- the switcher's own
	links among them -- and so are links to files other than pages, links
	to other sites, and anything that is not http or https.  Crawlers do not
	run it, so they only ever see the plain links.
*/

(function() {
	var language = (document.documentElement.getAttribute('lang') || '').toLowerCase();

	if(!/^[a-z]{2}$/.test(language)) {
		return;
	}

	function isPage(pathname) {
		var last = pathname.split('/').pop();

		return last.indexOf('.') === -1 || /\.php$/i.test(last);
	}

	function carryLanguage(anchor) {
		var url;

		try {
			url = new URL(anchor.getAttribute('href'), window.location.href);
		} catch (error) {
			return;
		}

		if(url.protocol !== 'http:' && url.protocol !== 'https:') {
			return;
		}

		if(url.hostname.replace(/^www\./i, '') !== window.location.hostname.replace(/^www\./i, '')) {
			return;
		}

		if(url.searchParams.has('language') || url.searchParams.has('lang') || !isPage(url.pathname)) {
			return;
		}

		if(url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
			return;
		}

		url.searchParams.set('language', language);

		anchor.setAttribute('href', url.href);
	}

	function carryAll() {
		var anchors = document.querySelectorAll('a[href]');

		for(var i = 0; i < anchors.length; i++) {
			carryLanguage(anchors[i]);
		}
	}

		// Links a page builds after it loads are caught as they are followed.

	document.addEventListener('click', function(event) {
		var anchor = event.target.closest ? event.target.closest('a[href]') : null;

		if(anchor) {
			carryLanguage(anchor);
		}
	}, true);

	if(document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', carryAll);
	} else {
		carryAll();
	}
})();
