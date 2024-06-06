jQuery(function($){
	var $form = $('#' + logs_form_id);
	var $select_all = $('#' + select_all_id);
    var $download = $('#' + download_id);
    var $tbody_log_list = $('#' + tbody_log_list_id );

    if ($download.length) {
        $dropdown = $download.siblings('div.log_dropdown');

        $download.bind('click', function() {

        	offset = $download.position();
	        dropdown_left = offset.left;
	        dropdown_top = offset.top + 23;

	        $dropdown.css({
	            left: dropdown_left,
	            top: dropdown_top
	        });

            if ($dropdown.hasClass('hidden')) {
                $dropdown.removeClass('hidden');
            }
            else {
                $dropdown.addClass('hidden');
            }

            return false;
        });

        $dropdown.find('a').bind('click', function() {
            var $selects = $form.find('input[name="select\[\]"]');

            var selected = 0;
            $.each($selects, function(k, v) {
                if ($selects.eq(k).is(':checked')) {
                    selected += 1;
                }
            });

            if (selected > 0) {
                $input = $('<input class="hidden" name="download_type" />');
                $input.val($(this).attr('name'));
                $input.appendTo($form);
                $form.submit();
            }

            return false;
        });

        $('body').bind('click', function() {
            if (!$dropdown.hasClass('hidden')) {
                $dropdown.addClass('hidden');
            }
        });
    }
	
	//全选按钮
	$select_all
	.bind('click', function(){
		if(Q.browser.msie){
			$(this).change();
		}
	})
	.bind('change', function(){
		if ($select_all.is(':checked')) {
			$form.find('input[name="select\[\]"]').prop('checked', true);
			$select_all.prop('checked', true);
		}
		else {
			$form.find('input[name="select\[\]"]').prop('checked',false);
			$select_all.prop('checked',false);
		}
	});
	
	//刷新按钮
	$tbody_log_list.bind('refreshLogList', function(){
		Q.trigger({
			url: submit_url,
			object: 'log_list',
			event: 'refresh',
			global: 'false',
			data: {
				tbody_log_list: tbody_log_list_id ,
			},
		});
	
	});

});
