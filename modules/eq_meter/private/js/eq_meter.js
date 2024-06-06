 $(window).load(function(){

	var $left_container = $(".bucket_box");
	var orignal_index = [1,2];
	$.each(orignal_index, function(index, val) {
		var $obj = $(".dropdown_menu:eq("+val+")");
		$obj.find('[value="y1"]').css({'visibility':'hidden', 'height':'0px', 'padding':'0px'});
	});

	$left_container.find('[name^="item\["]').change(function(){
		var select_dropdown = $(this).val();
		var item_index = $(this).attr('data');
		var attr_name = $(this).attr('name');
		var indexes = [0,1,2];
		var action_items = new Array();
		//最新操作的在最底下
		action_items.push(0,1);

		if ( select_dropdown == 'y1' ) {
			$.each(action_items, function(index, val) {
				var $dropdown_menu = $(".dropdown_menu:eq("+val+")");
				$dropdown_menu.find('[value="y1"]').css({'visibility':'hidden', 'height':'0px', 'padding':'0px'});
			});
		}
		else if ( select_dropdown == 'y2') {
			$.each(action_items, function(index, val) {
				var $dropdown_menu = $(".dropdown_menu:eq("+val+")");
				$dropdown_menu.find('[value="y2"]').css({'visibility':'hidden', 'height':'0px', 'padding':'0px'});
			});
		}

		if ($('select[name^="item\["]').find('option[value="y1"]:selected').length== 0) {
			$(".dropdown_menu").find('[value="y1"]').css({'visibility':'visible','height':'18px','padding':'2px 4px'});
		}
		if ($('select[name^="item\["]').find('option[value="y2"]:selected').length== 0) {
			$(".dropdown_menu").find('[value="y2"]').css({'visibility':'visible','height':'18px','padding':'2px 4px'});
		}
	});
});