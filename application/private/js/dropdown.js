(function($){

	$("select:not('.selectpicker'):not('.no_dropdown')").livequery(function() {

		String.prototype.h = function() {
			return (this || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
		}

        var $select = $(this);
		var container = $select.data('dropdown_container');
		if (container) return;

		var cls = $select.attr('class') || '';
		var new_cls = cls.replace(/\bdropdown\S*|\btext\b/g, '');
		var selected_text = $.trim($select.find("option[selected], option[selected='selected']").text().h()) || '&#160;';
		var iefix = (Q.browser.msie && Q.browser.version < 9) ? '<div/>' : '';

		// var dropdown = ['<div class="dropdown_container ', new_cls, '"><div class="dropdown_text">',selected_text, '</div>', iefix, '<span class="icon-down"></span></div>'].join('');
        var $dropdown = $select.next();
        // $dropdown.find('.dropdown_text').append(selected_text);


		$select.data('dropdown_container', $dropdown[0]);

		var $text = $dropdown.find('.dropdown_text');
		var $menu = $('<div class="dropdown_menu"/>').appendTo('body').hide();

		var html='';

		$select.children().each(function(){
			var $opt = $(this);
			if ($opt.is('optgroup')) {
				html += '<div class="dropdown_group"><div class="label">' + $opt.attr('label') + '</div>';
				$opt.children('option').each(function() {
					var $o = $(this);
					html += '<a class="dropdown_item" href="#" value="' + $o.attr('value') + '">' + $o.text().h() + '</a>';
				});
				html += '</div>';
			}
			else {
				html += '<a class="dropdown_item" href="#" value="' + $opt.attr('value') + '">' + $opt.text().h() + '</a>';
			}

		});
		html += iefix;
		$menu.html(html);

		$select.after($dropdown);

		// 自动调整text 和 menu的尺寸
        var w = $dropdown.width();

		// $menu.css('width', w - 15);
		$menu.css('width', w + 22);

        // Clh 下拉按钮触发下拉列表
        var $span = $($dropdown.find('span')[0]);
        // $span.click(function(){$dropdown.click();});

		var hideTimeout = null;
		var intID = 0;


		function disableScroll(element) {
			var element = element || window;
			// Get the current page scroll position
			scrollTop = element.pageYOffset || element.scrollTop;
			scrollLeft = element.pageXOffset || element.scrollLeft,
				// if any scroll is attempted, set this to the previous value
				element.onscroll = function() {
					element.scrollTo(scrollLeft, scrollTop);
				};
		}
		function enableScroll(element) {
			var element = element || window;
			element.onscroll = function() {};
		}

		$dropdown.click(function() {
			$menu.css('width', $dropdown.width() + 22);

			disableScroll();
			disableScroll($('.dialog_block').first().get(0));
			// check if select is readonly status
			if ($select.is(':disabled')) return false;
			if (hideTimeout) {
				clearTimeout(hideTimeout);
				hideTimeout = null;
			}
			$menu.appendTo('body');

			var o = $text.offset();

			var _zIndex = function($el) {
				var z = $el.css('z-index');
				$el.parents().each(function(){
					z = Math.max(z, $(this).css('z-index'));
				});
				return z;
			};

			$menu.css({left: o.left - 12, top: o.top + 22, zIndex: _zIndex($text)}).slideToggle(200);

            $(document).click(function(){
				enableScroll();
				enableScroll($('.dialog_block').first().get(0));
				$menu.hide();
			});

			intID = setInterval(function(){
				if ($select.parents('body').length == 0) {
					$menu.remove();
					clearInterval(intID);
				}
			},2000);
			return false;
		});


		$text.add($menu)
		.mouseenter(function(){
			//check if select is readonly status
			if ($select.is(':disabled')) return false;
			if (hideTimeout) {
				clearTimeout(hideTimeout);
				hideTimeout = null;
			}
		})
		.mouseleave(function(){
			if ($select.is(':disabled')) return false;
			if (hideTimeout) {
				clearTimeout(hideTimeout);
			}
			hideTimeout = setTimeout(function(){
				enableScroll();
				enableScroll($('.dialog_block').first().get(0));
				$menu.hide();
			}, 500);
		});

		var $items = $('a.dropdown_item', $menu);
		$items
		.click(function(e) {
			var $link = $(this);
			$text.text($link.text());
			$select.val($link.attr('value')).change();
			enableScroll();
			enableScroll($('.dialog_block').first().get(0));
			$menu.hide();
			e.preventDefault();
			return false;
		});

	}, function() {
		var $select = $(this);
		$($select.data('dropdown_container')).remove();
		$select.data('dropdown_container', null);
	});

	$('select:disabled').livequery(function(){
		$($(this).data('dropdown_container')).addClass('readonly');
	}, function() {
		$($(this).data('dropdown_container')).removeClass('readonly');
	});

})(jQuery);
