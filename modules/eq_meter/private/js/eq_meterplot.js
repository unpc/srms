jQuery(function($){
	$.jqplot.config.enablePlugins = true;
	var $plot = $('#' + plot_id );
	var $jqplot;
	var $left_container = $('#' + left_container_id);

	var axes = ['xaxis','yaxis','y2axis'];

	//调整图表尺寸
	$plot.css('width', 'auto');
	var $tp = $plot.parents('td:eq(0)');
	var $center = $('#center');
	var suppose;
	var real_height;
	var _resize = function(){
		var height = 'auto';
		if (Q.browser.msie && Q.browser.version < 9.0) {
			height = $center.innerWidth() - $left_container.outerWidth();
		}
		$plot.empty().css('width', '1');
		$plot.empty().css('height', '1');
		suppose = $center.innerHeight() - ($tp.offset().top - $center.offset().top);
		$plot.css('width', height);
		real_height = ($plot.width() * 9 / 16) >= suppose ? ($plot.width() * 9 / 16) : suppose;
		$plot.css('height', real_height);
		if( $jqplot ) {
			$jqplot.replot({resetAxeScale:true});
		}
	};
	_resize();
	$(window).resize(function(){
		setTimeout(_resize, 500);
	});

	// 调整数字显示 {{{
	// e.g. fround(10, 2) => 10.00
	// e.g. fround(10, 4) => 10.0000
	function fround(n, d) {
		var dd = Math.pow(10, d);
		return Math.round(parseFloat(n||0) * dd) / dd;
	}
	// }}}

	// 颜色数组
	var seriesColors = [ "#4bb2c5", "#c5b47f", "#EAA228", "#66cc66", "#cccc66", "#958c12",
        "#953579", "#4b5de4", "#d8b83f", "#ff5800", "#0085cc"];

	// 格式化日期
	Date.prototype.format = function(format) {
	    var o = {
	        "M+" :this.getMonth() + 1, // month
	        "d+" :this.getDate(), // day
	        "h+" :this.getHours(), // hour
	        "m+" :this.getMinutes(), // minute
	        "s+" :this.getSeconds(), // second
	        "q+" :Math.floor((this.getMonth() + 3) / 3), // quarter
	        "S" :this.getMilliseconds()
	    };
	 
	    if (/(y+)/.test(format)) {
	        format = format.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
	    }
	 
	    for ( var k in o) {
	        if (new RegExp("(" + k + ")").test(format)) {
	            format = format.replace(RegExp.$1, RegExp.$1.length == 1 ? o[k]
	                    : ("00" + o[k]).substr(("" + o[k]).length));
	        }
	    }
	    
	    return format;
	}
	// }}}

	// 服务器提交过来的数据
	curves_data = {};
	//

	// 单纯点的数据,就是jqplot的第二个参数 {{{
	points_data = [];
	// }}}

	// 图表数据 {{{
	opt_data  = {
		axes: {
			xaxis:{
				label: '时间',
				show: false,
				labelOptions: {
					enableFontSupport: true,
					fontFamily: $('body').css('font-family'),
					fontSize: '12px'
				},
				numberTicks: 10,
				tickOptions: {
					markSize: 2,
					showLabel: true,
					formatter: function (format, val) {
	
						var dtstart = opt_data.axes.xaxis.min;
						var dtend = opt_data.axes.xaxis.max;

						if(typeof(dtstart) == 'undefined' || typeof(dtend) == 'undefined') return '';

						dtstart = new Date(parseInt(dtstart)*1000);
						dtend = new Date(parseInt(dtend)*1000);

						var format;
						if(dtstart.getFullYear() != dtend.getFullYear()){
							format = 'yyyy/MM/dd ';
						}
						else if(dtstart.getMonth() != dtend.getMonth()){
							format += 'MM/dd ';
						}
						else if(dtstart.getDate() != dtend.getDate()){
							format += 'dd ';
						}
						format += 'hh:mm';

						var date = new Date(parseInt(val)*1000);
						return '<span class="nowrap">' + date.format(format) + '</span>'; 
					}
				}
			},
			yaxis: {
				labelOptions:{
					enableFontSupport: true,
					fontFamily: $('body').css('font-family'),
					fontSize: '12px'
				},
				numberTicks:10,
				pad:0,
				tickOptions: {
					markSize: 2,
					showLabel: true
				},
				markSize:2
			},
			y2axis: {
				labelOptions:{
					enableFontSupport: true,
					fontFamily: $('body').css('font-family'),
					fontSize: '12px'
				},
				numberTicks:10,
				pad:0,
				tickOptions: {
					markSize: 2,
					showLabel: true
				},
				markSize:2
			}
		},
		series:[],
		grid: {
			drawGridLines: true,
			gridLineColor: '#537068',
			shadow: false,
			background: '#29332F',
			borderWidth: 3.5,
			borderColor: '#A1A1A1'
		},
		seriesDefaults: {
			shadow: false,
			lineWidth:1.5,
			markerOptions:{shadow:false, size:4}
		},
		axesDefaults: {
			shadow: false,
			borderWidth: 2
		},
		highlighter: {
			sizeAdjust: 5,
			formatString:'(%1$s, %2$0.4f)',
			yvalues: 5
		},
		cursor:{
			zoom:true, 
			showTooltip:false
		},
		canvasOverlay: {
			show: true,
			objects: [
				{horizontalLine: {
					name: 'fred',
					y: 4,
					lineWidth: 12,
					xminOffset: '8px',
					xmaxOffset: '29px',
					color: 'rgb(50, 55, 30)',
					shadow: false
				}},
				{dashedHorizontalLine: {
					name: 'wilma',
					y: 6,
					lineWidth: 2,
					xOffset: '54',
					color: 'rgb(133, 120, 24)',
					shadow: false
				}}
			]
		 }
	};
	// }}}

	// 从服务器抓取数据 {{{
	var is_init = true;
	function fetchData(xmax, xmin) {
		Q.trigger({
			object: 'plot',
			event: 'fetch_data',
			url: fetch_data_url,
			data:   {
				'equipment_id':equipment_id,
				'xmax'   :xmax,
				'xmin'   :xmin,
				'width'  :$plot.outerWidth()
			},
			global: false,
			success:function(data) {
				curves_data = [];
				points_data = [];

				for(var key in data.curves ) { 
					points_data.push( data.curves[key].data );
					curves_data.push( data.curves[key] );
				}

				if( is_init ) {
					init();
				}
				replot();
			}
		});
	}

	// 初始化函数
	function init() {
		for(var i in axes ) {
			delete opt_data.axes[ axes[i] ].max;
			delete opt_data.axes[ axes[i] ].min;
		}

		opt_data.series = [];
		var name = '';
		for( var key in curves_data ) {
			name += '<div class="nowrap">' + curves_data[key].name + '</div>';
			if (key == 0) {
				opt_data.axes.yaxis.label = name;
				opt_data.series.push({color:'#C5B570',yaxis:'yaxis',show:true});
			}
			else if (key == 1) {
				opt_data.series.push({color:'#7ACC3C',yaxis:'yaxis',show:false});
			}
			else if (key == 2) {
				opt_data.series.push({color:'#8D3581',yaxis:'yaxis',show:false});	
			}
		}
		y1count = points_data.length;
		y2count = 0;
		adjustY('');

		is_init = false;
	}

	// 刷新图标
	function replot() {
		$plot.empty();
		if( points_data.length == 0){
			$jqplot = $.jqplot(plot_id,[[[0,0]]], opt_data);
			return false;
		}
		var num = 0;
		for( var i=0; i<points_data.length; i++ ) {
			if( points_data[i].length != 0 ) {
				continue;
			}
			num++;
		}
		if( num == points_data.length){
			$jqplot = $.jqplot(plot_id,[[[0,0]]], opt_data);
			return false;
		}
		$jqplot = $.jqplot(plot_id, points_data, opt_data);
		syn();
	}

	// 将图表数据同步到操作栏上
	function syn() {
		// 先将图表的数据同步到opt_data上
		for(var i in axes ) {
			opt_data.axes[ axes[i] ].max = $jqplot.axes[ axes[i] ].max;	
			opt_data.axes[ axes[i] ].min = $jqplot.axes[ axes[i] ].min;	
		}
		// 再将opt_data同步到操作栏上
		var $max = $left_container.find('[name="xaxis\[max\]"]').parents('p:eq(0)');
		var $min = $left_container.find('[name="xaxis\[min\]"]').parents('p:eq(0)');
		$max.html('到&#160;&#160;<input class="text date" name="xaxis[max]" value="' + parseInt(opt_data.axes.xaxis.max) + '" />');
		$min.html('从&#160;&#160;<input class="text date" name="xaxis[min]" value="' + parseInt(opt_data.axes.xaxis.min) + '" />');
		$left_container.find('[name="xaxis\[max\]"]').val( opt_data.axes.xaxis.max ).change();
		$left_container.find('[name="xaxis\[min\]"]').val( opt_data.axes.xaxis.min ).change();
		$left_container.find('[name="yaxis\[max\]"]').val( fround(opt_data.axes.yaxis.max,2) );
		$left_container.find('[name="yaxis\[min\]"]').val( fround(opt_data.axes.yaxis.min,2) );
		$left_container.find('[name="y2axis\[max\]"]').val( fround(opt_data.axes.y2axis.max,2) );
		$left_container.find('[name="y2axis\[min\]"]').val( fround(opt_data.axes.y2axis.min,2) );

	}
	//

	// 调整y轴事件 {{{
	var y1count = 0;
	var y2count = 0;
	$left_container.find('[name^="item\["]').change(function(){
		var yname = '';
		var y2name = '';
		var new_y1count = 0;
		var new_y2count = 0;
		/*
		select中值为y1和y2的
		*/
		var select_objs = $left_container.find('option:selected');

		for (var i = 0; i < select_objs.length; i++) {
			if(curves_data.length == 0) continue;
			 
			var select_obj = select_objs[i];
			var item_data = $(select_obj).parent().attr('data');
			var item_name;
			item_name = curves_data[item_data].name;
			var axes_val = $(select_obj).val();
			switch(axes_val) {
				case 'y1':
					yname += '<div class="nowrap">' + item_name + '</div>';
					opt_data.series[i].yaxis = 'yaxis';
					opt_data.series[i].show = true;
					new_y1count++;
					break;
				case 'y2':
					y2name += '<div class="nowrap">' + item_name + '</div>';
					opt_data.series[i].yaxis = 'y2axis';
					opt_data.series[i].show = true;
					new_y2count++;
					break;
				case 'no':
					opt_data.series[i].show = false;
				 	break;
			}
		};
		opt_data.axes.yaxis.label = yname;
		opt_data.axes.y2axis.label = y2name;
		// 对Y轴的min,max做调整
		if(new_y1count > y1count ){
			adjustY('');
		}
		if(new_y2count > y2count ){
			adjustY('2');
		}
		if( new_y2count == 0 ) {
			resetY('2');
		}
		if( new_y1count == 0 ) {
			resetY('');
		}
		y1count = new_y1count;
		y2count = new_y2count;

		replot();
		return false;
	});
	// }}}
	
	// 调整Y轴range {{{
	function adjustY(no) {
		var max = -9999;
		var min = 9999;
		for(var i=0;i<opt_data.series.length;i++){
			if( opt_data.series[i].yaxis == 'y'+no+'axis' && typeof(curves_data[i]) != "undefined"){
				max = (max > curves_data[i].max)?max:curves_data[i].max;
				min = (min < curves_data[i].min)?min:curves_data[i].min;
			}
		}
		opt_data.axes['y'+no+'axis'].max = max;
		opt_data.axes['y'+no+'axis'].min = min;
	}
	function resetY(no) {
		delete opt_data.axes['y'+no+'axis'].max;
		delete opt_data.axes['y'+no+'axis'].min;
	}
	// }}}
	

	$plot
	// 鼠标缩放的事件 {{{
	.bind('jqplotResetZoom jqplotZoom', function() {
		syn();
		fetchData(opt_data.axes.xaxis.max, opt_data.axes.xaxis.min);
	})
	// }}}
	// 重置视图 {{{
	.bind('eq_meterplotResetZoom', function() {
		is_init = true;
		fetchData();
	})
	// }}}
	// 刷新 {{{
	.bind('eq_meterplotChange', function() {
		for(var i in axes ) {
			opt_data.axes[ axes[i] ].min = parseInt( $left_container.find('[name="' + axes[i] + '\[min\]"]').val() );
			opt_data.axes[ axes[i] ].max = parseInt( $left_container.find('[name="' + axes[i] + '\[max\]"]').val() );
		}
		replot();
		fetchData(opt_data.axes.xaxis.max, opt_data.axes.xaxis.min);
	});
	// }}}

	// 页面载入同时载入图表
	is_init = true;
	fetchData();
});
