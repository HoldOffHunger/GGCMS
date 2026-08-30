$(document).ready(function(event){
	$(function() {
		$( ".timepicker" ).timepicker({
			timeFormat: 'h:mm p',
			interval: 10,
			dynamic: false,
			dropdown: true,
			scrollbar: true
		});
	});
});