jQuery(function($){
	
	var $table = $("#" + table_name);
	var $collections = $table.find(".collection_name");
	
	$table.find('.show_collection').bind('mouseenter', function(e) {
		var $collection = $(this);
		var $link = $collection.find('a:eq(0)');
		if ($link.length > 0 ) $link.show();
		e.preventDefault();
		return false;
	})
	.bind('mouseleave', function(e) {
		var $link = $(this).find('a:eq(0)');
		if ($link.length > 0 ) $link.hide();
		e.preventDefault();
		return false;
	});
	
	$collections.next('a').bind('click', function(e) {
		var $collection = $(this).prev('.collection_name');
		var input = '<input class="text" value="' + $collection.text() + '" /'+'>';
		$collection.html($(input));
		$collection.find('input').focus().one('blur', function(){
			Q.trigger({
				object:'change_stock_name',
				event:'blur',
				url: url,
				data:{
					collection: $collection.classAttr('collection'),
					selector: "collection_name",
					product_name: $(this).val()
				},
				success: function(data) {
					$collection.text(data.collection_name);
				}
			});
		});
		e.preventDefault();
		return false;
	});
});
