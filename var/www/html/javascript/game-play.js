$(document).ready(function(event){
		// A button's id names the game; the lessons its words come from are
		// either in the id too, or chosen once for every game in #game-range.
	$('.play-game-button').click(function(e) {
		var url = $('#play-base-url').val() + '?' + $(this).attr('id');
		var range = $('#game-range').val();
		if(range)
		{
			url += '&' + range;
		}
		$('#game-view').attr('src', url);
		$('#game-view').show().focus();
	});
});