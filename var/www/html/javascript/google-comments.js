var id_token;

	/*
		Called by google-signin.js with Google Identity Services' response,
		whose credential is the ID token API\Google::VerifyIdToken() checks.
	*/

function onSignIn(response) {
	id_token = response && response.credential;

	if(id_token && !$('#logout').val() && !$('#userid').val()) {
		$('#google_token_id').val(id_token);
		$('#submit').click();		// We hate you, Google.
		return true;
	}
}

$(document).ready(function(event){
		/*
			The old library kept its own Google session, and a sign-out had
			to wait for Google to end it before leaving.  Identity Services
			keeps none: the site's own sign-out is already done, so go.
		*/

	if($('#logout').val()) {
		var redirect = $('#redirect').val();

		if(redirect && redirect.length > 0) {
			window.location.href = redirect;
		}
	}


	$('#comment-form').submit(function(e) {
		if($('#userid').attr('id') && $('#userid').val() && !$('#google_token_id').val()) {
			if(!$('#Comments').val() || ($('#Username').prop('id') && !$('#Username').val())) {
				$('#error-box').prop('hidden', false);
				
				var fields = [];
				
				if(!$('#Comments').val()) {
					fields.push('Comments');
				}
				
				if($('#Username').prop('id') && !$('#Username').val()) {
					fields.push('Username');
				}
				
				$('#validation-error-message').html('<p>There was an error.  You are missing: ' + fields.join(', ') + '</p>');
				return false;
			}
		}
		return true;
	});
});