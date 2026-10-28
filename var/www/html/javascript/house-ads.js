/*
	House ads: the network's sites advertising one another.

	ClientSideIncludes prints this script, on view pages only, with the
	site's classification and shape as data attributes.  The ads and
	their catalogue are /houseads/, which deploy.sh syncs from the
	private configuration repository; a host without them gets a 404
	for the catalogue and shows nothing.

	Which ads a site may carry is a funnel:

		revolutionary  -- only revolutionary sites and the bookshop.  A
		                  revolutionary site is never a funnel for
		                  commercial ones.
		commercial     -- commercial and revolutionary sites and the
		                  bookshop, but not the one-page tools.
		one-page tool  -- everything, the other one-page tools least.

	A site never advertises itself.  Everything is decided here rather
	than on the server, so a cached page is the same page for everyone.

	Placement: on a page with reading text, a rectangle after the first
	paragraph, floated away from the first Image::N picture (which always
	floats right).  On a one-page tool, or a page with too little text to
	sit in, a banner under the tool or above the footer.

	Each ad plays for at least thirty seconds and is changed at the end
	of a whole loop, so a reader never sees one cut off mid-shot.  Readers
	who ask for reduced motion get the end card as a still and no changes.
	The x hides an ad for the rest of the visit.
*/

(function() {
	var script = document.currentScript;

	if(!script || !window.fetch) {
		return;
	}

	var classification = script.getAttribute('data-classification');
	var shape = script.getAttribute('data-shape');
	var host = location.hostname.replace(/^www\./, '');
	var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	var MINIMUM_MS = 30000;
	var READING_PARAGRAPHS = 4;		// fewer than this and the rectangle would crowd the text
	var HIDDEN_KEY = 'ggcms-hidden-house-ads';

	function weight(ad) {
		if(ad.host === host) {
			return 0;
		}

		if(classification === 'revolutionary') {
			return (ad.tier === 'revolutionary' || ad.tier === 'books') ? 1 : 0;
		}

		if(shape === 'onepage') {
			return ad.shape === 'onepage' ? 1 : 3;
		}

		if(classification === 'commercial') {
			return ad.shape === 'onepage' ? 0 : 1;
		}

		return 0;
	}

	function hiddenCodes() {
		try {
			return JSON.parse(sessionStorage.getItem(HIDDEN_KEY) || '[]');
		} catch(e) {
			return [];
		}
	}

	function hide(code) {
		var codes = hiddenCodes();
		codes.push(code);

		try {
			sessionStorage.setItem(HIDDEN_KEY, JSON.stringify(codes));
		} catch(e) {}
	}

	function pick(catalogue, current) {
		var hidden = hiddenCodes();
		var pool = catalogue.filter(function(ad) {
			return ad.code !== current && hidden.indexOf(ad.code) === -1 && weight(ad) > 0;
		});
		var total = pool.reduce(function(sum, ad) { return sum + weight(ad); }, 0);
		var roll = Math.random() * total;

		for(var i = 0; i < pool.length; i++) {
			roll -= weight(pool[i]);

			if(roll < 0) {
				return pool[i];
			}
		}

		return pool.length ? pool[pool.length - 1] : null;
	}

		/*
			Where the slot goes, and which kind.  The first Image::N picture
			sits inside the reading text, so its container is the text;
			without one, the reading module's own prose block.
		*/

	function placement() {
		var picture = document.querySelector('.document-image-holder');
		var text = picture ? (picture.closest('.prose') || picture.parentElement) : document.querySelector('#textbody .prose, .prose');

		if(shape !== 'onepage' && text) {
			var paragraphs = Array.prototype.filter.call(text.children, function(child) {
				return child.tagName === 'P' && child.textContent.trim().length > 0;
			});

			if(paragraphs.length >= READING_PARAGRAPHS) {
				var away = (picture && picture.classList.contains('document-image-holder-left')) ? 'right' : 'left';

				return {kind: 'rectangle', side: picture ? away : 'right', after: paragraphs[0]};
			}
		}

		var tool = document.querySelector('.tool-bench');

		if(tool) {
			return {kind: 'banner', after: tool};
		}

		var footer = document.querySelector('.site-foot');

		return footer ? {kind: 'banner', before: footer} : null;
	}

	function build(where) {
		var slot = document.createElement('aside');
		slot.className = 'house-ad house-ad-' + where.kind + (where.side ? ' house-ad-' + where.side : '');
		slot.setAttribute('aria-label', 'From our network');
		slot.innerHTML =
			'<a rel="noopener"><img alt="" decoding="async"></a>' +
			'<div class="house-ad-label"><span>From our network</span>' +
			'<button type="button" class="house-ad-close" aria-label="Hide this ad">×</button></div>';

		if(where.after) {
			where.after.parentNode.insertBefore(slot, where.after.nextSibling);
		} else {
			where.before.parentNode.insertBefore(slot, where.before);
		}

		return slot;
	}

	function start(catalogue) {
		var where = placement();
		var first = pick(catalogue, null);

		if(!where || !first) {
			return;
		}

		var slot = build(where);
		var link = slot.querySelector('a');
		var image = slot.querySelector('img');
		var suffix = where.kind === 'banner' ? '.banner' : '';
		var current = null;
		var timer = null;
		var shown = {};

		function show(ad) {
			clearTimeout(timer);

			if(!ad) {
				slot.parentNode.removeChild(slot);
				return;
			}

			current = ad;

				/*
					A picture already seen on this page has finished playing,
					and the browser would show it from memory on its last
					frame; a fresh address starts it again.
				*/

			var file = '/houseads/' + ad.code + suffix + (still ? '.poster.jpg' : '.webp');

			if(shown[ad.code]) {
				file += '?again=' + shown[ad.code];
			}

			shown[ad.code] = (shown[ad.code] || 0) + 1;

			link.href = ad.url;
			image.src = file;
			image.alt = ad.alt;

			if(!still) {
				timer = setTimeout(next, Math.ceil(MINIMUM_MS / ad.loop_ms) * ad.loop_ms);
			}
		}

		function next() {
			if(document.hidden) {
				document.addEventListener('visibilitychange', next, {once: true});
				return;
			}

			var ad = pick(catalogue, current.code);

			if(ad) {
				show(ad);
			}
		}

		slot.querySelector('.house-ad-close').addEventListener('click', function() {
			var hiding = current || first;		// the first may not have started yet

			hide(hiding.code);
			show(pick(catalogue, hiding.code));
		});

			/*
				Nothing is fetched, and nothing starts playing, until the slot
				is near the screen -- an ad that plays out below the fold has
				used up its loops before anyone reaches it.
			*/

		if(!('IntersectionObserver' in window)) {
			show(first);
			return;
		}

		var observer = new IntersectionObserver(function(entries) {
			if(entries[0].isIntersecting) {
				observer.disconnect();
				show(first);
			}
		}, {rootMargin: '200px'});

		observer.observe(slot);
	}

	function begin() {
		fetch('/houseads/catalogue.json').then(function(response) {
			return response.ok ? response.json() : [];
		}).then(start).catch(function() {});
	}

	if(document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', begin);
	} else {
		begin();
	}
})();
