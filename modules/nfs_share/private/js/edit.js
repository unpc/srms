jQuery(function($){
	
	var $submit = $('#' + submit_id);
	var $form = $('#' + form_id);
	var $select_all = $('#' + select_all_id);
	var $delete_all = $('#' + delete_all_id);
	
	//批量下载
	$submit.bind('click',function(){
		var $selects = $form.find('input[name="select\[\]"]');
		
		var selected = 0;
		$.each($selects, function(k,v){
			if ($selects.eq(k).is(':checked')) {
				selected += 1;
			}
		});
		
		if (selected > 0) {
			$form.submit();
		}
		
		return false;
	});
	
	//全选按钮
	$select_all
	.bind('click', function(){
		if(Q.browser.msie){
			$(this).change();
		}
	})
	.bind('change', function(){
		if ($select_all.is(':checked')) {
			$form.find('input[name="select\[\]"]').prop('checked',true);
			$select_all.prop('checked', true);
		}
		else {
			$form.find('input[name="select\[\]"]').prop('checked',false);
			$select_all.prop('checked',false);
		}
	});
	
	//批量删除
	$delete_all.bind('click',function(){
		var $selects = $form.find('input[name="select\[\]"]');
		var delete_path = new Array();
		var num = 0;
		$.each($selects, function(k,v){
			if ($selects.eq(k).is(':checked')) {
				delete_path[num] = $(v).val();
				num ++;
			}
		});
		if (delete_path.length > 0) {
			Q.trigger({
				url : submit_url,
				object : 'delete_all',
				event : 'click',
				data : {
					delete_path : delete_path,
					path : path,
					form_token : form_token
				}
		
			});
		}
		return false;
	});
});
