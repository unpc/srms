/**
 * lims2左侧sidebar点击展开/收缩
 *
 * @author: Clh  lianhui.cao@geneegroup.com
 * @time: 2018-08-24 10:00:00
 **/

jQuery(function($){

	$('.top_sidebar_menu .category_container')
	.on('mouseover', function () {
		var $items = $(this).find('.items');
		if ($items.is(':hidden')) {
			$items.addClass('animal_sidebar_items');
			$items.show();
		} 
	})
	.on('mouseleave', function () {
		var $items = $(this).find('.items');
		if (!$items.is(':hidden')) {
			$items.hide();
			$items.removeClass('animal_sidebar_items');
		}
	})
});
