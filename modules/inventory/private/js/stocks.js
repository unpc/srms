jQuery(function($) {
	// 转码 barcode(xiaopei.li@2011.10.21)
	// TODO 增加对 barcode 版本的处理
	$('div.barcode').livequery(function(){
		var $el = $(this);
		var val = $.trim($el.text());
		if (val) {
			$el.barcode(val, "code128", {
				bgColor: '',
				barHeight: 30
			});
		}
	});
});
