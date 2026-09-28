/*
	Upvote and downvote, for a signed-in reader.  The buttons are
	module_entrylikes'; a vote is marked with the is-on class, and a count
	of nought carries is-zero, which the stylesheet hides.

	Signed out, the votes are links to the sign-in page and nothing here
	binds: #userid is in every page with a comment form, so it is its value,
	not its presence, that says someone is signed in.
*/

$(document).ready(function(event){
	id_token = $('#google_token_id').val();
	if($('#userid').val()) {
		xmlhttp = undefined;
		if (window.XMLHttpRequest) {
			xmlhttp = new XMLHttpRequest();
		} else {  // code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}

		var liked = 0;
		var disliked = 0;

		$('#thumbs-up-button-container').click(function(e){
			if(liked) {
				undoLike();

				decrementLikes();

				xmlhttp.open('POST','view.json',true);
				xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
				xmlhttp.send('action=undoupvote&google_token_id=' + id_token);
			} else {
				if(disliked) {
					decrementDisLikes();
				}
				undoDisLike();
				doLike();

				incrementLikes();

				xmlhttp.open('POST','view.json',true);
				xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
				xmlhttp.send('action=upvote&google_token_id=' + id_token);
			}
		});

		$('#thumbs-down-button-container').click(function(e){
			if(disliked) {
				undoDisLike();

				decrementDisLikes();

				xmlhttp.open('POST','view.json',true);
				xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
				xmlhttp.send('action=undodownvote&google_token_id=' + id_token);
			} else {
				if(liked) {
					decrementLikes();
				}
				undoLike();
				doDisLike();

				incrementDisLikes();

				xmlhttp.open('POST','view.json',true);
				xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
				xmlhttp.send('action=downvote&google_token_id=' + id_token);
			}
		});

		function doLike() {
			liked = 1;
			$('#thumbs-up-button-container').addClass('is-on').attr('aria-pressed', 'true');
		}

		function undoLike() {
			liked = 0;
			$('#thumbs-up-button-container').removeClass('is-on').attr('aria-pressed', 'false');
		}

		function doDisLike() {
			disliked = 1;
			$('#thumbs-down-button-container').addClass('is-on').attr('aria-pressed', 'true');
		}

		function undoDisLike() {
			disliked = 0;
			$('#thumbs-down-button-container').removeClass('is-on').attr('aria-pressed', 'false');
		}

		function changeCount(selector, by) {
			var count = parseInt(($(selector).html() || '0').replace(/,/gi, ''), 10) || 0;
			count = Math.max(0, count + by);
			$(selector).html(count).toggleClass('is-zero', count === 0);
		}

		function incrementLikes() {
			changeCount('#total-likes', 1);
		}

		function decrementLikes() {
			changeCount('#total-likes', -1);
		}

		function incrementDisLikes() {
			changeCount('#total-dislikes', 1);
		}

		function decrementDisLikes() {
			changeCount('#total-dislikes', -1);
		}

		if($('#likeordislike') && $('#likeordislike').attr('id')) {
			if(parseInt($('#likeordislike').val()))
			{
				doLike();
			}
			else
			{
				doDisLike();
			}
		}
	}
});
