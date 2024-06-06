/*
no_longer_in_service: 报废仪器的状态值
confirm_scrap: 确定报废的警告
confirm_update: 确定更新的警告
*/
jQuery(function($){
	$('[name=status]').bind('change',function(){
		var conf;
		if(no_longer_in_service==$(this).val()){
			conf = confirm_scrap;
		}
		else{
			conf = confirm_update;
		}
		$(this).parents('form').find('[name=submit]').attr('confirm', conf);
	});
});
