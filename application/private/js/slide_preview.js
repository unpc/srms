/*
NO.TASK#313(guoping.zhang@2011.01.12)
列表信息预览功能
*/
jQuery(function($){

	var $preview_container = $('.slide_preview_container');
	if($preview_container.size() == 0 ){

		$preview_container = $('<div class="slide_preview_container" ><div class="preview_content"/></div>');
		$preview_container.appendTo( $('body') ).css({top: -10000, right:0});
	}

	var $document = $(document);

	var closeTimeout = null;
	var showTimeout = null;
	var click_on_preview;
	var timeout = 200;

	//显示preview
	function show_preview(parent) {
		click_on_preview = false;
		var el = $preview_container.data('preview_element');
		if (!el) return;
		parent.css({'overflow' : 'hidden'});

		var offset = parent.offset()
		var scrollTop = $document.scrollTop();
		var height = $(window).height();
		var top = 0;
		var right = '-300px';

		$preview_container.appendTo(parent)

		top = scrollTop <= offset.top ? top : scrollTop - offset.top;
		height = scrollTop <= offset.top ? height - (offset.top - scrollTop) : height;

		$preview_container.css({
			top: top + 'px',
			right: right,
			height: height + 'px',
			width: '300px'
		})

		$preview_container.find('.preview_content').addClass('preview_loading').empty();
		$preview_container.show().animate({
			right: '0px'
		}, 500);

	}

	function unbind_preview_event() {
		$document.unbind('click.preview');
		$preview_container
			.unbind('mouseenter.preview')
			.unbind('mouseleave.preview')
			.unbind('click.preview');
	}

	//关闭previw,将各项css恢复初始值.
	function close_preview() {
		unbind_preview_event();
		$preview_container.hide().appendTo( $('body') ).css({top: -10000, right:0});
		$preview_container.find('.preview_content').empty();
		$preview_container.data('preview_element', null);
	}

	function reset_close_timeout() {
		if (closeTimeout) {
			clearTimeout(closeTimeout); 
			closeTimeout = null;
		}
	}

	function reset_show_timeout() {
		if (showTimeout) {
			clearTimeout(showTimeout);
			showTimeout = null;
		}
	}

	function timeout_close_preview() {
		closeTimeout = setTimeout(function() {
			close_preview();
		}, 1000);
	}


	//绑定mouseenter/mouseleave事件
	$('[q-slide-preview]')
	.off('mouseenter.preview')
	.off('mouseleave.preview')
	.on('mouseenter.preview', function(e){
		close_preview();
		reset_close_timeout();
		reset_show_timeout();

		var me = $(this);
		var curr_el = $preview_container.data('preview_element');
		var el = me[0];
		if (el == curr_el) {
			return;
		}
		$preview_container.data('preview_element', el);

		var parent = me.attr('q-slide-parent') ? $(me.attr('q-slide-parent')) : $('body');

		$document
		.bind('click.preview', function(){
			if (!click_on_preview) {
				reset_show_timeout();
				reset_close_timeout();
				close_preview();
				$(this).unbind('click.preview');
			}
			click_on_preview = false;
		});

		$preview_container
		.bind('mouseenter.preview', function(e) {
			reset_close_timeout();
		})
		.bind('mouseleave.preview', function(e) {
			reset_close_timeout();
			timeout_close_preview();
		})
		.bind('click.preview', function(e) {
			click_on_preview = true;
		});

		show_preview(parent);

		showTimeout = setTimeout(function() {
			//获取传递过来的q-static参数
			var str = me.attr('q-static');
			var data = Q.toQueryParams(str) || {};
			Q.trigger({
				object: 'slide_preview',
				event: 'click',
				data: data,
				url: me.attr('q-slide-preview'),
				global: false,
				success: function(data, status) {
					if (data.preview) {
						$preview_container.find('.preview_content').removeClass('preview_loading').html(data.preview);
						delete data.preview;
					}
				}
			});
		}, timeout);

	})
	.on('mouseleave.preview', function(e){
		reset_close_timeout();
		timeout_close_preview();
	});
});
