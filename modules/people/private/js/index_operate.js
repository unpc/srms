/*
select_all_id: checkbox的唯一标识符，与_top或者_bottom组合，构成selector
batch_form_id: 批量操作的form id
*/
jQuery(function($){
	var $selector = $('#' + select_all_id);
	var $form = $('#' + batch_form_id);

	$selector
	.bind('click', function(){
		if(/msie/.test(navigator.userAgent.toLowerCase())){
			$(this).change();
		}
	})
	.bind('change', function(){
		var $el = $(this);
		
		if ($el.is(':checked')) {
			$('input.'+ select_all_id + '[disabled!=disabled]' ).prop("checked", true).change();
			// 标签中checked="checked"已有，但却不显示打勾的解决办法(第一次打勾，第二次不打勾)，弃用attr改用prop
			// $('input.'+ select_all_id ).attr('checked', 'checked').change();
			// $($selector).attr('checked', 'checked');
		}
		else {
			$('input.'+ select_all_id  + '[disabled!=disabled]' ).prop("checked", false).change();
			// $($selector).removeAttr('checked');
		}
	});

	$('input.' + select_all_id ).change(function(){
		var $fc = $(this);
		var id = $fc.val();
		var $checkbox = $form.find('[name="select\['+id+'\]"]');
		if ($fc.is(':checked')) {
			$checkbox.attr('checked', 'checked');
		}
		else {
			$checkbox.removeAttr('checked');
			$($selector).removeAttr('checked');
		}
	});

	$form.bind('submit', function(e){
		$('input.'+ select_all_id + '[name="select\[\]"]:checked').clone().hide().appendTo($form);
	});

});
