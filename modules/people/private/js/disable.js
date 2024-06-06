jQuery(function($){
	var $disable_selected = $('#disable_selected');

	$disable_selected.bind('click',function(){
		var $selects = $('input[name="select\[\]"]');
		var selected_ids = new Array();
		var num = 0;
		$.each($selects, function(k,v){
			if ($selects.eq(k).is(':checked')) {
				selected_ids[num] = $(v).val();
				num ++;
			}
		});
		if (selected_ids.length > 0) {
			Q.trigger({
				object : 'disable_selected',
				event : 'click',
				data : {
					selected_ids : selected_ids,
					form_token : form_token
				}
			});
		}
        else {
            alert(no_checked);
        }
		return false;
	});
});
