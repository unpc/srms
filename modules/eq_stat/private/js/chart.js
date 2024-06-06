var $search_button = $('#' + search_button_id);
var $chart_form = $search_button.next('div.chart_form');

$search_button.bind('click', function() {
	$("chart_graph").height("600px");
	if ($search_button.hasClass('button_search')) {
		$(this).html(search_button_toggle_text).removeClass('button_search').addClass('button_stat_close');
		$chart_form.show();
	}
	else {
		$(this).html(search_button_text).addClass('button_search').removeClass('button_stat_close');;
		$chart_form.hide();
	}
	return false;
});


/*
	初始化flash数据,进行重绘图形
 */
var $chart = $("#"+chart_id);
var _old_width = 0;

chart_options.width = $chart.innerWidth();
chart_options.height = Math.floor($chart.innerWidth() * 9 /16);

var chart = new FusionCharts(chart_options.swfUrl, chart_options.id, chart_options.width, chart_options.height);
chart.setDataXML(chart_options.dataSource);

//设置在最底层
chart.setTransparent(1);
chart.render(chart_id);
	

var resizeTimeout;

var _resize = function() {
	if (resizeTimeout) clearTimeout(resizeTimeout);
		resizeTimeout = setTimeout(function(){
			var width = $('body').innerWidth() - $('#sidebar').outerWidth() - 16;
			var last_width = chart.getAttribute('width');
			if (Math.abs(last_width - width) > 15) {	
				chart.setAttribute('width', width);
				chart.setAttribute('height', Math.floor(width * 9 /16));	
				$chart.html(chart.getSWFHTML());
			}	
	}, 300);
}


/*
	保证在屏幕被拖动停止之后，进行flash重绘
*/
_resize();
$(window).resize(_resize);

var $flash_plugin_message = $('#' + flash_plugin_message_id);
if (swfobject.hasFlashPlayerVersion('8.0.0')) {
    $flash_plugin_message.remove();
}
else {
	$flash_plugin_message.show();
}

