/*
	Google Identity Services, which replaced platform.js.  Draws Google's
	button into every .g-signin2 on the page and hands the ID token it
	returns to onSignIn() in google-comments.js, which fills
	#google_token_id and submits the form, as it always did.

	The client ID comes from the google-signin-client_id meta tag the
	engine prints beside this script (ClientSideIncludes::Headers()).
	Google calls window.onGoogleLibraryLoad once gsi/client has loaded.
*/

function GGCMS_GoogleSignIn() {
	var meta = document.querySelector('meta[name="google-signin-client_id"]');

	if(!meta || !window.google || !google.accounts || !google.accounts.id) {
		return;
	}

	google.accounts.id.initialize({
		client_id: meta.getAttribute('content'),
		callback: function(response) {
			if(typeof onSignIn === 'function') {
				onSignIn(response);
			}
		},
		auto_select: false,
		cancel_on_tap_outside: true
	});

		// Signed out here: do not sign them straight back in next time.
	if(document.getElementById('logout')) {
		google.accounts.id.disableAutoSelect();
		return;
	}

	var buttons = document.querySelectorAll('.g-signin2');

	for(var i = 0; i < buttons.length; i++) {
		google.accounts.id.renderButton(buttons[i], {
			type: 'standard',
			theme: 'outline',
			size: 'large',
			text: 'signin_with',
			shape: 'rectangular'
		});
	}
}

window.onGoogleLibraryLoad = GGCMS_GoogleSignIn;
