/*
input_errors: 存在error的input数组
errors: error的详细信息
*/
jQuery(function($){

	var $form = $('#form_error_box').parents('form');

	for(var i=0; i<input_errors.length; i++){
		var $input = $('[name="'+input_errors[i]+'"]', $form);
		if($input.hasClass('dropdown')){
			$input.next().addClass('validate_error').nextAll('.require').addClass('error');
		}else{
			$input.addClass('validate_error').nextAll('.require').addClass('error');
		}

        //针对tag_selector 增加 validate_error
        if ($input.parents('div.tag_selector').length) {
            var $tag_selector = $input.parents('div.tag_selector');

            $tag_selector.addClass('validate_error');
            $tag_selector.parents().nextAll('span.require').addClass('error');
        }

        //针对于radio/checkbox封装出来的数据增加error
        var $span = $input.parents('.require_container:eq(0)').find('span.require:not(.error)');
        $span.addClass('error');

		//var $form_error = $input.parent().find('.form_error');
		//$form_error.html([$form_error.html(), errors[i]].join(''));
	}


	$('[type=hidden]').each(function(){
		if($(this).hasClass('validate_error')){
			$(this).next('input').addClass('validate_error');
			$(this).next('input').nextAll('span.require').addClass('error');
		}
	});

    //增加setTimeout防止具有number class的input 无法focus
	$form.bind('Q.form.complete', function(e){
		var $item =  $('.validate_error:visible', $(this)).eq(0);
		
		if (!$item.is('input')) {
			 $item = $item.find('input');
		}	
		
		$item.focus();
	});
	
	/* TODO 暂时用延时来处理，之后可能对form渲染的js进行调整 */
	setTimeout(function(){
		$form.trigger('Q.form.complete');
	}, 500);

});
