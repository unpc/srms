(function($) {

	var $tag_menus = {};
	var $current_root;
	var removeTimeout = null;
	var ajaxTimeout = null;


	$.fn.tagSelector = function(opt) {
		var $root = $(this);

		opt = opt || {};
		opt.menu = opt.menu || '<div class="tag_selector_menu"><div class="tag_menu_header"></div><table class="content" width="1"></table></div>';
		opt.item = opt.item || '<tr class="tag_item"><td width="100%"><div class="text"/></td><td><div class="flag"/></td></tr>';
		var flag_more = '<span class="icon-right icon-lg"></span>';
		var _zIndex = function($el) {
			var z = $el.css('z-index');
			if (isNaN(z)) z = 0;
			$el.parents().each(function(){
				var myZ = parseInt($(this).css('z-index'));
				if (isNaN(myZ)) myZ = 0;
				z = Math.max(z, myZ);
			});
			return z;
		};

		var _remove_all_menus = function(){
			for (var i in $tag_menus) {
				$tag_menus[i].remove();
			}
		};

		var _render = function() {
			var $form = $root.parents('form');
			var max_width = Math.min(400, $form.innerWidth());
			if ($root.outerWidth() <= max_width) return;

			var $links = $root.find('.tag_selector_link:not(.tag_selector_first, .tag_selector_last, .tag_selector_more)');
			var $placeholder = $('<div class="tag_selector_link tag_selector_placeholder"><a href="#">...</a></div>');
			var w = $root.outerWidth() + $placeholder.outerWidth();
			var $ghost;

			$links.each(function(i) {
				var $l = $(this);
				if (w - $l.outerWidth() <= max_width) {
					$placeholder.find('a').attr('title', $l.find('a').text()).attr('q-tag-id', $l.find('a').attr('q-tag-id'));
					$l.after($placeholder);
					$l.remove();
					return false;
				}
				w -= $l.outerWidth();
				$l.remove();
			});

		};

		var _select_tag = function(tag_id) {
			if (opt.ajax) {
				Q.trigger({
					widget: 'tag_selector',
					object: 'tag',
					event: 'click',
					global: false,
					data: {
						uniqid: opt.uniqid,
						tag_name: opt.tag_name,
						root_id: opt.root_id,
						tag_id: tag_id,
						name: opt.name,
                        i18n: opt.i18n || null
					},
					url: opt.url,
					complete: function() {
                        console.log('_remove_all_menus1');
						_remove_all_menus();
						setTimeout(function(){
							$root.find('[name='+opt.name+']').change();
							// _render();
						}, 20);
					}
				});

			}
			else {
				// 设置隐藏提交元素 并自动提交root所在表单
				var $hidden = $('<input type="hidden" />');
				$hidden.attr('name', opt.name);
				$hidden.val(tag_id);
				$root.parents('form').append($hidden).submit();
			}
		};

		//$(document).bind('click', _remove_all_menus);

		var _menu_offset = -8;
		if (Q.browser.msie && Q.browser.version < 8) {
			_menu_offset = -16;
		}

		var _show_next_for_tag = function(tag_id, parent_tag_id) {
			var $parent_item;
			var $parent_menu;

			var _remove = function($m){
				$m.find('.tag_item').each(function(){

					var $item = $(this);
					var id = $item.data('tag_id');

					if (id) {
						var $el = $tag_menus[id];
						if ($el) {
							_remove($el);
							$el.remove();
							delete $tag_menus[id];
						}
					}
				})
			};

			if (parent_tag_id != undefined) {
				$parent_menu = $tag_menus[parent_tag_id];
				if ($parent_menu) {
					_remove($parent_menu);

					$parent_menu.find('.tag_item').each(function(){
						var $item = $(this);
						var id = $item.data('tag_id');
						if (id == tag_id) {
							$parent_item = $item;
							return false;
						}
					});
				}
			}
			else {
				var $el = $tag_menus[tag_id];
				if ($el) {
					$el.remove();
					delete $tag_menus[tag_id];
				}
			}

			var $menu = $(opt.menu);
			var $menu_content = $menu.find('.content');
            $loading = $('<div class="loading">&#160;</div>');
            $loading.css('width', $root.width() + 22);
			$menu.append($loading);

			if ($parent_menu) {
                $menu.appendTo('body');
                var ioffset = $parent_item.offset();
                $menu.css({'left': ioffset.left + $parent_item.width(), top: $root.offset().top + 30, "min-height": $parent_menu.height() + 22}).css('border-top-right-radius', '3px').css('border-bottom-right-radius', '3px');
                $parent_menu.css('border-top-right-radius', '0').css('border-bottom-right-radius', '0');
			}
			else {
                $menu.hide();
				$menu.find('.tag_menu_header').html('<div style="padding: 0 10px 5px 10px;"><input class="tag_menu_search_input" name="tag_menu_search_input" placeholder="请输入关键字" value="" style="box-sizing: border-box;height: 30px;line-height: 30px;width: 100%;outline: 0;border: 1px solid #E5E5E5;padding: 0 10px;-webkit-border-radius: 3px;-moz-border-radius: 3px;border-radius: 3px;"></div>')
                tag_menu_footer = '<div class="tag_menu_footer"><ul><li class="reset">清空</li></ul></div>';
                $menu.append(tag_menu_footer);
                $menu.appendTo('body');
				$parent_item = $root;
				var ioffset = $parent_item.offset();
				$menu.css({left: ioffset.left, top: ioffset.top + $parent_item.height() + 2, width: $parent_item.width() + 22}).css('border-radius', '3px');
                $menu.slideDown(200);

				$("input[name=tag_menu_search_input]").bind('input propertychange',function(){
					var search_name = $(this).val()
					$menu_content.html('')
					$menu.nextAll('.tag_selector_menu').remove();

					if (ajaxTimeout) {
						clearTimeout(ajaxTimeout);
						ajaxTimeout = null;
					}

					Q.trigger({
						widget:'tag_selector',
						object:'tag',
						event: search_name == '' ? 'mouseover' : 'search',
						global: false,
						data: {
							tag_id: tag_id,
							tag_name: opt.tag_name,
							uniqid: opt.uniqid,
							root_id: opt.root_id,
							status: opt.status,
							i18n: opt.i18n || null,
							search_name: search_name
						},
						url: opt.url,
						complete: function () {
                            console.log('menu.remove');
							if ($tag_menus[tag_id]!=$menu) { $menu.remove(); }
						},
						success: function (data, status) {
							if (data.hasOwnProperty('items')) {
								var items = data.items || {};
								var count;
								$menu.find('.loading').remove();
		
								var sorted = [];
								for (var id in items) {
									var item = items[id];
									item.id = id;
									sorted.push(item);
								}
								
								sorted.sort(function (a, b) {
									if (a.hasOwnProperty('weight')) return a.weight - b.weight;
									else return a.id - b.id;
								});
								
								for (count = 0; count < sorted.length; count++) {
									var t = sorted[count];
									var $t = $(opt.item);
									$t.find('.text').html(t.html);
									$t.data('tag_id', t.id);
									if (t.ccount > 0) {
										$t.find('.flag').append(flag_more);
									} else {
										$t.find('.flag').append('<div style="width: 5px;" />');
									}
									$menu_content.append($t);
									$t.data('children_count', t.ccount);
									$t.mouseenter(function(){
										var $t = $(this);
										$t.addClass('tag_item_active');
										if (ajaxTimeout) {
											clearTimeout(ajaxTimeout);
											ajaxTimeout = null;
										}
										if ($t.data('children_count') > 0) {
											ajaxTimeout = setTimeout(function(){
												_show_next_for_tag($t.data('tag_id'), tag_id);
											}, 50);
										}
										else {
											//如果没有后代元素，把其他的tag的后代移出
											_remove($menu);
										}
									})
									.mouseleave(function(){
										$(this).removeClass('tag_item_active');
									})
									.click(function(){
										var $t = $(this);
										//把当前$menu加入队列 才能选择
										$tag_menus[tag_id] = $menu;
										_select_tag($t.data('tag_id'));
									});
								}
		
								$menu.find('.reset').click(function(){
									_select_tag(0);
								})
		
								if (count > 0) {
									$tag_menus[tag_id] = $menu;
		
									$menu
									.mouseenter(function(){
										if (removeTimeout) {
											clearTimeout(removeTimeout);
											removeTimeout = null;
										}
										$root.data('removeTimeout', null);
									})
									.mouseleave(function(){
										if (removeTimeout) {
											clearTimeout(removeTimeout);
										}
										removeTimeout = setTimeout(_remove_all_menus, 1000);
									});
								}
		
								delete data.items;
							}
						}
					});
				})
				
			}

			Q.trigger({
				widget:'tag_selector',
				object:'tag',
				event:'mouseover',
				global: false,
				data: {
					tag_id: tag_id,
					tag_name: opt.tag_name,
					uniqid: opt.uniqid,
					root_id: opt.root_id,
					status: opt.status,
          			i18n: opt.i18n || null
				},
				url: opt.url,
				complete: function () {
					if ($tag_menus[tag_id]!=$menu) { $menu.remove(); }
				},
				success: function (data, status) {
					if (data.hasOwnProperty('items')) {
						var items = data.items || {};
						var count;
						$menu.find('.loading').remove();

						var sorted = [];
						for (var id in items) {
							var item = items[id];
							item.id = id;
							sorted.push(item);
						}
						
						sorted.sort(function (a, b) {
							if (a.hasOwnProperty('weight')) return a.weight - b.weight;
							else return a.id - b.id;
						});
						
						for (count = 0; count < sorted.length; count++) {
							var t = sorted[count];
							var $t = $(opt.item);
							$t.find('.text').html(t.html);
							$t.data('tag_id', t.id);
							if (t.ccount > 0) {
								$t.find('.flag').append(flag_more);
							} else {
                                $t.find('.flag').append('<div style="width: 5px;" />');
                            }
							$menu_content.append($t);
							$t.data('children_count', t.ccount);

							$t.mouseenter(function(){
								var $t = $(this);
								$t.addClass('tag_item_active');
								if (ajaxTimeout) {
									clearTimeout(ajaxTimeout);
									ajaxTimeout = null;
								}
								if ($t.data('children_count') > 0) {
									ajaxTimeout = setTimeout(function(){
										_show_next_for_tag($t.data('tag_id'), tag_id);
									}, 50);
								}
								else {
									//如果没有后代元素，把其他的tag的后代移出
									_remove($menu);
								}

							})
							.mouseleave(function(){
								$(this).removeClass('tag_item_active');
							})
							.click(function(){
								var $t = $(this);
								//把当前$menu加入队列 才能选择
								$tag_menus[tag_id] = $menu;
								_select_tag($t.data('tag_id'));
							});
						}

                        $menu.find('.reset').click(function(){
                            _select_tag(0);
                        })

						if (count > 0) {
							$tag_menus[tag_id] = $menu;

							$menu
							.mouseenter(function(){
								if (removeTimeout) {
									clearTimeout(removeTimeout);
									removeTimeout = null;
								}
								$root.data('removeTimeout', null);
							})
                            /**
                             * 【案例】20233617 山东大学仪器列表问题 问题1
                             * https://blog.csdn.net/weixin_44706441/article/details/129316127
                             * win10+自带的微软拼音输入法才会触发此BUG
                             * 中文输入按下一个字符时加载软键盘触发mouseleave导致menu remove
                             */
							.mouseleave(function(event){
                                if (event.relatedTarget) {
                                    if (removeTimeout) {
                                        clearTimeout(removeTimeout);
                                    }
                                    removeTimeout = setTimeout(_remove_all_menus, 1000);
                                }
                            });
						}

						delete data.items;
					}
				}
			});

		};

		// $('.tag_selector_more', $root)
		$root
		.livequery('click', function(){
			if (removeTimeout) {
				clearTimeout(removeTimeout);
				removeTimeout = null;
			}

			if ($current_root != $root) {
				_remove_all_menus();
				$current_root = $root;
			}

			var tag_id = $root.find('[name="root_id"]').val();
			// var tag_id = $root.find('[name=' + opt.name + ']').val();
            // var tag_id = 1;
			_show_next_for_tag(tag_id);
		})
		.on('mouseleave', function(){
			if (removeTimeout) {
				clearTimeout(removeTimeout);
			}
			removeTimeout = setTimeout(_remove_all_menus, 1000);
		});

		/*$('.tag_selector_link:not(.tag_selector_more) a', $root)
		.on('click', function(e){
			var tag_id = $(this).attr('q-tag-id') || 0;
			_select_tag(tag_id);
			e.preventDefault();
			return false;
		});*/

		_render();

	};

})(jQuery);
