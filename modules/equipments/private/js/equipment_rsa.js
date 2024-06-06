/*
id: 需要点击的按钮的ID
*/
jQuery(function($){
	var selector = ['#', id].join('');
	$(selector).one('click', function(){
		var new_public_key =$('.public_key:first', $(this).parent()).val();
		$('textarea[name=computer_public_key]').val(new_public_key);
		$('.dialog_close').click();
	});
});
