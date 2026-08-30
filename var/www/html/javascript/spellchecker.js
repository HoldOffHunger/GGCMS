$(document).ready(function(event){
					// Globals
					// ---------------------------------------------------------
					// ---------------------------------------------------------
					// ---------------------------------------------------------
			
		/* getTimeOutDelay()
		
			Get the delay of the timeout for typing.
		
		*/
			
	function getTimeOutDelay() {
		return 2000;	// 2 seconds
	}
	
		/* processingText()
			
			Get the text to display when the misspelling list generator is thinking.
			
		*/
	
	function processingText() {
		return 'Processing';
	}
	
		/* waitingForUserText()
		
			Get the text to display when the misspelling list generator is waiting.
		
		*/
	
	function waitingForUserText() {
		return 'Waiting for User';
	}
	
		/* getResultSeparator()
		
			Get the text that separates a misspelling from another result.
		
		*/
		
	function getResultSeparator() {
		return "\n";
	}
	
		/* getResultSectionSeparator()
		
			Get the text that separates a result section from other sections.
		
		*/
		
	function getResultSectionSeparator() {
		return "\n\n";
	}

					// Event and Element Handlers
					// ---------------------------------------------------------
					// ---------------------------------------------------------
					// ---------------------------------------------------------
		
		/* $('.input-area').keyup(function(e) {...})
		
			Run spellCheck() only after so much time has delayed from keyup movements.
		
		*/
		
	var timeout = false;
	
	var shiftkeyheld = false;
	var altkeyheld = false;
	var ctrlkeyhold = false;
	
	var ignoredkeys = {
		16:true,	// shift
		17:true,	// alt
		18:true,	// ctrl
		19:true,	// pause/break
		20:true,	// caps lock
		27:true,	// escape
		33:true,	// pageup
		34:true,	// pagedown
		35:true,	// end
		36:true,	// home
		37:true,	// left
		38:true,	// up
		39:true,	// right
		40:true,	// down
		45:true,	// insert
		46:true,	// delete
		91:true,	// left system key
		92:true,	// right system key
		93:true,	// select key
		112:true,	// f1
		113:true,	// f2
		114:true,	// f3
		115:true,	// f4
		116:true,	// f5
		117:true,	// f6
		118:true,	// f7
		119:true,	// f8
		120:true,	// f9
		121:true,	// f10
		122:true,	// f11
		123:true,	// f12
		144:true,	// num lock
		145:true,	// scroll lock
		182:true,	// "my computer" (multimedia keyboard)
		183:true,	// "my calculator" (multimedia keyboard)
	};
	
	$('.input-area').keydown(function(e) {
		if(e.which === 16) {
			shiftkeyheld = true;
		} else if(e.which === 17) {
			altkeyheld = true;
		} else if(e.which === 18) {
			ctrlkeyheld = true;
		}
	});
	
	$('.input-area').keyup(function(e) {
		if(e.which === 16) {
			shiftkeyheld = false;
		} else if(e.which === 17) {
			altkeyheld = false;
		} else if(e.which === 18) {
			ctrlkeyheld = false;
		}
	
		if(e.which === 45 && shiftkeyheld) {
			// shift+insert, yes, a change is occuring
		} else if(e.which === 9 && altkeyheld) {
			// alt+tab, no, a change is not occuring
			return false;
		} else if(e.which === 99 && ctrlkeyheld) {
			// ctrl+c, no, a change is not occuring
			return false;
		} else if(e.which === 67 && ctrlkeyheld) {
			// ctrl+C, no, a change is not occuring
			return false;
		} else if(ignoredkeys[e.which]) {
			return false;
		}
		
		$('#status-text').text(processingText());
		if(timeout) {
			clearTimeout(timeout);
		}
		timeout = setTimeout(function() {
			spellCheck();
		}, getTimeOutDelay());
		return true;
	});
	
		/* $('.input-area').click(function(e) {...})
		
			Clear the input area of its default instruction set when input area is clicked.
		
		*/
	
	$('.input-area').click(function(e) {
		return initiateApp();
	});
	
		/* $('.function-button').click(function(e) {...})
		
			User clicked the "Find Errors" button.
			
			Regenerate the errors list.
		
		*/
	
	$('#function-button').click(function(e) {
		if(timeout) {
			clearTimeout(timeout);
		}
		return spellCheck();
	});
	
		/* $('.strip-html').click(function(e) {...})
		
			User changed the option of stripping HTML from the input.
			
			Regenerate the error list.
		
		*/
	
	$('.strip-html').click(function(e) {
		return spellCheck();
	});
	
		/* initiateApp()
		
			Clear the input area of its default instruction set.
			
			Bound to many event handlers.
		
		*/
		
	var started = false;
	
	function initiateApp() {
		if(!started) {
			$('.input-area').val('');
			started = true;
		}
		
		return true;
	}
					// Application
					// ---------------------------------------------------------
					// ---------------------------------------------------------
					// ---------------------------------------------------------

function onlyUnique(value, index, self) { 
    return self.indexOf(value) === index;
}

	function spellCheck() {
		const errors = getSpellCheckErrors();
		displayErrors({'errors':errors});
		$('#status-text').text(waitingForUserText());
		return true;
	}
	
	function getSpellCheckErrors() {
		var input = cleanupInput();
		const regexline = '/\\b' + words.join('\\b|\\b') + '\\b/';
		const errors = input.match(new RegExp(regexline, 'gi'));
		
		if(!errors) {
			return [];
		}
		
		const realerrors = getRealErrors({'errors':errors, 'input':input});
		
		return realerrors;
	}
	
	function getRealErrors(args) {
		const errors = args.errors.filter(onlyUnique);
		const input = args.input;
		
		const errorhash = {};
		
		for(var error in errors) {
			var fullerror= errors[error];
			if(fullerror) {
				errorhash[fullerror] = true;
			}
		}
		
		realerrors = Object.keys(errorhash);
		return realerrors;
	}
	
	function displayErrors(args) {
		$('#output-area').val(args.errors.join("\n"));
		return true;
	}
	
		/* cleanupInput(args)
			
			Cleanup the input from the user.
			
		*/
	
	function cleanupInput() {
		var inputtext = $('.input-area').val();
		var input = formatInput({'input':inputtext});
		
		return input;
	}
	
		/* formatInput(args)
		
			Format the input from the user.
			
			Specifically, lowercase and, if necessary, strip HTML.
		
		*/
	
	function formatInput(args) {
		var input = args.input;
		
		input = input.toLowerCase();
		
		if($('.strip-html').is(':checked')) {
			input = $('<div/>').html(input).text();
		}
		
		return input;
	}
});