// menu_id:  menu的id
// ajax_url: AJAX路径
// mode: list/icon
jQuery(function($){

	var $menu = $('#' + menu_id);

	$menu.find('a.toggle_button_hidden').click(function(){
		var $button = $(this);
		$button.toggleClass("toggle_button_show_hidden");
		var show_hidden = $button.hasClass("toggle_button_show_hidden") ? 0 : 1;

		Q.trigger({
			object:'sbmenu_show_hidden',
			event:'click',
			data: {show_hidden: show_hidden},
			url: ajax_url,
			success: function() {
				if (show_hidden) {
					$menu.find('span.toggle_button').removeClass("toggle_button_collapsed");
					$menu.find('.item_hidden')
					.filter('.icon_item')
						.each(function(){
							if (Q.browser.msie && Q.browser.version < 8) {
								$(this).css('display', 'inline');
							}
							else {
								$(this).css('display', 'inline-block');
							}
						})
					.end()
					.filter('.list_item')
						.show()
					.end()
					.animate({opacity:1}, 100);

					// $menu.find('.items').slideDown(200);
				}
				else {
					$menu.find('.item_hidden').animate({opacity:0}, 100, function(){
						$(this).css('display', 'none');
					});
				}		
			}
		});	
		
		return false;	
	});	
	
	Q.heartbeat.bind('sbmenu.update', [mode]);

});
