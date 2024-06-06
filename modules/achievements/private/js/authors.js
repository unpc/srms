/*
uniqid：容器id
users_uniqid：可供选择users容器的id
author_uniqid：author容器的id
template_uniqid： 模板的id
src: author的头像地址
*/
jQuery(function($){
	var selector = '';
	
	selector = ['#', uniqid, ' input[name=add_button]'].join('');
	$(selector).click(function(){
		selector = ['#', uniqid, ' input[name=user_id]'].join('');
		$(selector).next('input').attr('value',' ');
	});

	selector = ['#', users_uniqid, ' li div.icon_block'].join('');
	$(selector).livequery(function(){
		var that = $(this);
		that.draggable({
			helper: 'clone'
		});
	});
	
	selector = ['#', author_uniqid, ' li'].join('');
	$(selector).droppable({
		drop: function(event, ui) {
			var $id = ui.draggable.find('input:hidden').val();
			var $icon = ui.draggable.find('img').attr('src');
			var $author_id = $(this).find('.author_user_id').attr('name');
			var $author_name = $(this).find('.author_name').text();
			selector = ['#', template_uniqid, ' .template\\:author_linked div:first-child'].join('');
			var $div = $(selector).clone();
			$div.find('.author_user_id')
				.attr('name',$author_id)
				.val($id);
			$div.find('.author_img').attr('src',$icon);
			$div.find('span.author_name').text($author_name);
			$('> div', this).replaceWith($div);
			$("a.delete_author").bind('click',delete_author);
			
		}
	});
	
	function delete_author(){
		selector = ['#', template_uniqid, ' .template\\:author_unlinked div:first-child'].join('');
		var $un_div = $(selector).clone();
		$parent = $(this).parents('li');
		var author_id = $parent.find('.author_user_id').attr('name');
		var author_name = $parent.find('.author_name').text();
		$un_div.find('.author_user_id').attr('name',author_id);
		$un_div.find('.author_user_id').attr('value','@delete');
		$un_div.find('.author_name').text(author_name);
		$un_div.find('.author_img').attr('src', src);
		$parent.find('div:first-child').replaceWith($un_div);
	}
	
	$("a.delete_author").bind('click',delete_author);
	
});
