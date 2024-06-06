/*
container: 容器ID
object_name: ajax请求需要传递的oname
object_id: ajax请求需要传递的oid
url: ajax请求的url
*/
jQuery(function($){
	var selector = ['#', container].join('');
	$(selector).parents('form').find('input[name=lab]').change(function(){
		Q.trigger({
			event:'select_lab_change',
			data:{
				container:container,
				project_lab:$(this).val(),
				object_name:object_name,
				object_id:object_id
			},
			url:url
		});
	});
});
