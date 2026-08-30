$(document).ready(function(event){
	$(document.body).keydown(function (e) {
	//	console.log("BT: KEY DOWN!" + e.which + "|");
		
		if(e.which === 27) {
			$('#index-adjustment-select').remove();
		}
	});
	
	$(document.body).click(function(e) {
	//	console.log("BT: CLICKY!!!!");
	//	console.log($(e.target).attr('class'));
	//	console.log(e.target);
		if(!$(e.target).hasClass('index-adjustment') && !$(e.target).hasClass('index-selector')) {
			$('#index-adjustment-select').remove();
		}
	});
	
	var selectedindex = false;
	
	$('.index-adjustment').click(function(e) {
	//	console.log(".indexclick!");
		var target = e.target;
		var targetid = $(target).prop('id');
		
		selectedindex = $(target).attr('data-index');
		
		var xposition = e.pageX;
		var yposition = e.pageY;
		
	//	console.log("target");
	//	console.log(target);
	//	console.log("BT: x/y|" + xposition + "/" + yposition + "|");
		
		var indexadjustments = $('.index-adjustment');
	//	console.log(indexadjustments);
	//	die();
		
		indexadjustments = $(indexadjustments).filter(function(index, thing) {
		//	console.log(index);
		//	console.log(thing);
		//	console.log("BT: TYPE!" + $(thing).attr('data-type') + "|");
			return $(thing).attr('data-type') === 'image';
		});
	//	console.log("BT: adjustments!");
	//	console.log(indexadjustments);
	//	console.log(indexadjustments.length);
		
		var selectmenu = $('<select id="index-adjustment-select" class="index-selector"></select>');
		
		$(selectmenu).append($('<option>', {
			value: -1,
			text: 'Swap Image...',
			disabled: true,
			selected: true,
			class: "index-selector",
		}));
		
		for(var i = 0; i < indexadjustments.length; i++) {
	//		console.log("BT: APPEND!" + i + "|");
			var indexadjustment = indexadjustments[i];
			var index = $(indexadjustment).attr('data-index');
			var filename = $('#image_FileName_' + (parseInt(index, 10))).val();
			if(filename) {
				$(selectmenu).append($('<option>', {
					value: index,
					text: 'Image #' + index + ': ' + filename,
					class: "index-selector",
				}));
			}
		}
		
		$(selectmenu).css({
			'position':'absolute',
			'left':xposition + 'px',
			'top':yposition + 'px',
			'z-index':1000,
		});
		
		$(document.body).append(selectmenu);
		
		$(selectmenu).on('click', swapImage);
		
		return true;
	});
	
	function swapImage(e) {
	//	console.log("BT: SWAP IMAGE!");
	//	console.log("BT: BEFORE!" + selectedindex + "|");
		var newindex = $('#index-adjustment-select').val();
	//	var newindex = $(e.target).attr('data-index');
	//	console.log("BT: NOW!" + newindex + "|");
		
		if(!selectedindex || !newindex) {
			return false;
		}
		
		selectedindex = parseInt(selectedindex, 10);
		newindex = parseInt(newindex, 10);
		
	//	console.log("BT: SWAP...|" + selectedindex + "|" + newindex + "|");
		
	//	selectedindex = selectedindex + 1;
	//	newindex = newindex + 1;
		
			// Cache the Old Data
		
		var oldtitle = $('#image_Title_' + selectedindex).val();
		var oldfilename = $('#image_FileName_' + selectedindex).val();
		var olddescription = $('#image_Description_' + selectedindex).val();
		var oldfile = $('#Image_' + selectedindex).val();
		var oldadjustmentval = $('#image_index-adjustment-' + selectedindex).val();
		
		var oldadjustment = oldadjustmentval != 0 ? oldadjustmentval : selectedindex;
		
		if(newindex > selectedindex) {
			for(var i = selectedindex; i < newindex; i++) {
				if($('#image_Title_' + i).length > 0) {
					var nextindex = i + 1;
					
					var nexttitle = $('#image_Title_' + nextindex).val();
					var nextfilename = $('#image_FileName_' + nextindex).val();
					var nextdescription = $('#image_Description_' + nextindex).val();
					var nextfile = $('#Image_' + nextindex).val();
					var nextadjustmentval = $('#image_index-adjustment-' + nextindex).val();
					
					var nextadjustment = nextadjustmentval != 0 ? nextadjustmentval : nextindex;
					
				//	console.log("BT: Next file??? Moove #" + nextindex + "...into...#" + i + "|" + nextfilename + "|" + $('#image_FileName_' + i).val() + "|");
					
					$('#image_Title_' + i).val(nexttitle);
					$('#image_FileName_' + i).val(nextfilename);
					$('#image_Description_' + i).val(nextdescription);
					$('#Image_' + i).val(nextfile);
				//	$('#image_index-adjustment-' + i).val(nextadjustment);
				}
			}
		} else {
			for(var i = selectedindex; i > newindex; i--) {
				if($('#image_Title_' + i).length > 0) {
					var previousindex = i - 1;
					
					var previoustitle = $('#image_Title_' + previousindex).val();
					var previousfilename = $('#image_FileName_' + previousindex).val();
					var previousdescription = $('#image_Description_' + previousindex).val();
					var previousfile = $('#Image_' + previousindex).val();
					var previousadjustmentval = $('#image_index-adjustment-' + previousindex).val();
					
					var previousadjustment = previousadjustmentval != 0 ? previousadjustmentval : previousindex;
					
					$('#image_Title_' + i).val(previoustitle);
					$('#image_FileName_' + i).val(previousfilename);
					$('#image_Description_' + i).val(previousdescription);
					$('#Image_' + i).val(previousfile);
				//	$('#image_index-adjustment-' + i).val(previousadjustment);
				}
			}
		}
		
		$('#image_Title_' + newindex).val(oldtitle);
		$('#image_FileName_' + newindex).val(oldfilename);
		$('#image_Description_' + newindex).val(olddescription);
		$('#Image_' + newindex).val(oldfile);
	//	$('#image_index-adjustment-' + newindex).val(oldadjustment);
		
		selectedindex = newindex = false;
		
		$('#index-adjustment-select').remove();
		
		return true;
		/*
		var newtitle = $('#image_Title_' + newindex).val();
		var newfilename = $('#image_FileName_' + newindex).val();
		var newdescription = $('#image_Description_' + newindex).val();
		var newfile = $('#Image_' + newindex).val();
		var newadjustment = $('#image_index-adjustment-' + newindex).val();
		
	//	console.log("BT: titles...|" + oldtitle + "|");
		
	//	console.log("BT: OLD!" + oldfile + "|");
	//	console.log("BT: NEW!" + newfile + "|");
		
		var newadjustment = newadjustment != 0 ? newadjustment : newindex;
		var oldadjustment = oldadjustment != 0 ? oldadjustment : selectedindex;
		
	//	console.log("BT: ADJUSTST!!!!" + oldadjustment + "|" + newadjustment + "|");
		
			// Swap the Data
			
		$('#image_Title_' + selectedindex).val(newtitle);
		$('#image_FileName_' + selectedindex).val(newfilename);
		$('#image_Description_' + selectedindex).val(newdescription);
		$('#Image_' + selectedindex).val(newfile);
		$('#image_index-adjustment-' + selectedindex).val(newadjustment);
		
		$('#image_Title_' + newindex).val(oldtitle);
		$('#image_FileName_' + newindex).val(oldfilename);
		$('#image_Description_' + newindex).val(olddescription);
		$('#Image_' + newindex).val(oldfile);
		$('#image_index-adjustment-' + newindex).val(oldadjustment);
		
		selectedindex = newindex = false;
		
		$('#index-adjustment-select').remove();
		
	//	console.log(e);
	//	console.log(e.target);
		
		return true;
		*/
	}
});