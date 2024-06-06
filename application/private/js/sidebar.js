/**
 * lims2左侧sidebar点击展开/收缩
 *
 * @author: Clh  lianhui.cao@geneegroup.com
 * @time: 2018-08-24 10:00:00
 **/

jQuery(function($){

	$('div > .category').livequery('click',
		function () {
			var $this = $(this);
			if ($this.next().is(':hidden')) {
				$this.next().slideDown(300);
				$this.find('.category_image').removeClass('icon-up').addClass('icon-down');
			} else {
				$this.next().slideUp(300);
				$this.find('.category_image').removeClass('icon-down').addClass('icon-up');
			}
		}
    );

	$('.items_color_select').prev().children('.category_image').removeClass('icon-up').addClass('icon-down');
});
