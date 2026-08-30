$(document).ready(function(event){
	$('#ReplicateLastAddedOptions').click(function(event){
		if(ReplicateLastAddedOptions) {
			$('.textbody_Source').val(ReplicateLastAddedOptions.textbody[0].Source);
			$('.ChosenEntryid').val(ReplicateLastAddedOptions.association[0].ChosenEntryid);
			$('.association_Type').val(ReplicateLastAddedOptions.association[0].Type);
			$('.association_SubType').val(ReplicateLastAddedOptions.association[0].SubType);
		}
	});
});