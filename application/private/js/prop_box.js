(function($){
	$.propbox = function(html, h, w, position) {

		if ($('div.prop_box')[0]) return;

		var $div = $('<div class="prop_box"><div class="prop_title"><a class="prop_close">&nbsp;</a></div><div class="prop_content"></div></div>');
		var $content = $div.find('.prop_content');
		$(html).appendTo($content);
		$div.appendTo('body');

		h = h ? h : 200
		w = w ? w : 400

		// 设置Div的长\宽度大小
		$div.css({
			'height': h + 'px',
			'width': w + 'px'
		})

		// 根据position计算初始位置
		position = position ? position : 'right_bottom';
		switch (position) {
			case 'left_top':
				$div.css({
					'left': '0px',
					'top': '-'+h+'px'
				});
				break;
			case 'right_top':
				$div.css({
					'right': '0px',
					'top': '-'+h+'px'
				});
				break;
			case 'left_bottom':
				$div.css({
					'left': '0px',
					'bottom': '-'+h+'px'
				});
				break;
			case 'middle_middle':
				$content.css({
					'height': $content.height() - $div.find('.prop_title').height()
				});
				$div.css({
					'left': (document.body.clientWidth - w) / 2 + 'px',
					'top': (document.body.clientHeight - h) / 2 + 'px'
				});
				break;
			default:
				$div.css({
					'right': '0px',
					'bottom': '-'+h+'px'
				});
				break;
		}

		var close = function () {
			$div.remove();
		};

		var show = function () {
			switch (position) {
				case 'left_top':
				case 'right_top':
					$div.animate({
						top: 0
					}, { queue: false, duration: 500})
					break;
				case 'left_bottom':
				default:
					$div.animate({
						bottom: 0
					}, { queue: false, duration: 500})
					break;
			}

			$div.find('.prop_close').on('click', function(){
                var equal2know = $div.find('#prop_box_close_equal_know').val();
                if(equal2know){
                    $div.find('.'+equal2know).click();
				}
                close();
            })
		};

		show();

	};

	$.propClose = function() {
		$('div.prop_box').remove();
	}
})(jQuery);