(function($) {

	$.ui = $.ui || {};
	$.ui.autocomplete = $.ui.autocomplete || {};
	$.ui.autocomplete.ext = $.ui.autocomplete.ext || {};

	$.ui.autocomplete.ext.ajax = function(opt) {
		var ajax = opt.ajax;
		var ajaxObject;
		return {
			getList: function(input) {
				input.addClass('autocompleting');
				var $base = opt.base ? $(opt.base) : input;

				var _data = Q.toQueryParams($base.classAttr('static'))||{};
				var selectors=Q.toQueryParams($base.classAttr('dynamic'))||{};
				for(k in selectors){
					_data[k]=$(selectors[k]).val();
				}

				_data.s = input.filter(':not(.hint)').val();

				if (opt.extra) {
					ajax = opt.ajax + $(opt.extra).val()
				}

				if (ajaxObject) ajaxObject.abort();
				ajaxObject = $.ajax({
					url: ajax, 
					data: _data, 
					complete: function() {
						input.removeClass('autocompleting');
						ajaxObject = null;
					},
					success: function(json) {
						input.trigger("updateList", [json]);
					},
					dataType: 'json',
					global: false
				});
			}
		};
	};

	$.ui = $.ui || {}; $.ui.autocomplete = $.ui.autocomplete || {}; var active;

	$.fn.autocompleteMode = function(container, input, size, opt) {
		var original = input.val(); var selected = -1; var self = this;
		$.data(document.body, "autocompleteMode", true);

		$(document).bind("clean.float.view", function() {
			var autocomplete = $(document).find(".autocomplete");
			if (autocomplete.length > 0) {
				input.trigger("cancel.autocomplete"); $(document).trigger("off.autocomplete"); input.val(original);
			}
		});

		$(document).one("cancel.autocomplete", function() {
            if (!container.hasClass('autocomplete_more')) {
                input.trigger("cancel.autocomplete"); $(document).trigger("off.autocomplete"); input.val(original);
            }
		});

		$(document).one("autoactivate.autocomplete", function() {
			if(opt.check) return;
			if(!active || active.length<1)return false;
			if($(active[0]).find('.empty').length || $(active[0]).find('.rest').length) return false;
            input.trigger("autoactivate.autocomplete", [$.data(active[0], "originalObject")]);
			$(document).trigger("off.autocomplete");
		});

		$(document).one("off.autocomplete", function(e, reset) {
            opt.st = 0;
            container.remove();
			$.data(document.body, "autocompleteMode", false);
			input.unbind("keydown.autocomplete");
			$(document).add(window).unbind("click.autocomplete").unbind("cancel.autocomplete").unbind("autoactivate.autocomplete");
		});

        // If a click bubbles all the way up to the window, close the autocomplete
        if (!container.hasClass('autocomplete_more')) {
            $('body').bind("click.autocomplete", function(e) {$(document).trigger("cancel.autocomplete");});
        } else {
            $('body').bind('click.autocomplete', function(e) {
                if (!$(e.target).parents('ul.autocomplete_more').length) {
                    $(document).trigger("off.autocomplete");
                }
            });
        }

		select = function() {
			//active = $("> *", container).removeClass("active").slice(selected, selected + 1).addClass("active");
            active = $("li:not(.special)", container).removeClass("active").slice(selected, selected + 1).addClass("active");

			if(active[0]){
				input.trigger("itemSelected.autocomplete", [$.data(active[0], "originalObject")]);
				//input.val(opt.insertText($.data(active[0], "originalObject")));
				input.data('autocomplete.selected', opt.insertText($.data(active[0], "originalObject")));
			}
		};

		var li = $("li:not(.special)", container);
		li.mouseover(function(e) {
			if(e.target == container[0]) return;
			selected = li.index($(e.target).is('li') ? $(e.target)[0] : $(e.target).parents('li')[0]); select();
        });

        /**
         * 这里改的有点水了 后续扩展也只支持一个页面中只有一个token_more
         * 有checkbox控制，跟事件有点冲突..
         */
        $('.autocomplete_more_confirm').click(function(){
            if (!input.parent().hasClass('token_more')) return;
            $lis = $('ul.autocomplete_more').children();
            $lis.each(function(index, item) {
                if ($(item).find('input[type="checkbox"]').prop('checked')) {
                    input.trigger("autoactivate.autocomplete", [$.data(item, "originalObject")]);
                    $(document).trigger("autocomplete.finish");
                }
            });
            //$(document).trigger("off.autocomplete");
        });
        
        if (!container.hasClass('autocomplete_more')) {
            li.bind("click.autocomplete", function(e) {
                opt.st = 0;
                $(document).trigger("autoactivate.autocomplete");
                $.data(document.body, "suppressKey", false);
            });
        } else {
            li.unbind("click.autocomplete");
            li.bind('click.autocomplete', function(e) {
                if ($(e.target).attr('type') === 'checkbox') return;
                var checkbox = $(this).find('input[type="checkbox"]');
                if (checkbox.prop('checked') == true)
                    checkbox.prop('checked', false);
                else 
                    checkbox.prop('checked', true);
            })
        }

		input
		.bind("keydown.autocomplete", function(e) {
			if(e.which == 27) {
				$(document).trigger("cancel.autocomplete");
			}
			else if(e.which == 13 && active && active.length > 0) {
				$(document).trigger("autoactivate.autocomplete");
			}
			else {
				switch(e.which) {
				case 40:
				case 9:
				case 39:
					selected = selected >= size - 1 ? 0 : selected + 1; break;
				case 38:
				case 37:
					selected = selected <= 0 ? size - 1 : selected - 1; break;
				default:
					return true;
				}
				select();
			}
			$.data(document.body, "suppressKey", true);
			return false;
		});

		select();
	};

	$.fn.autocomplete = function(opt) {
		opt = $.extend({}, {
			timeout: 200,
			st: 0,
			getList: function(input) { input.trigger("updateList", [opt.list]); },
			template: function(item) {
                var autocomplete_item = "<li><div class=\"autocomplete_item\" onclick=\"if(typeof autocomplete_callback === 'function'){autocomplete_callback(this);}\">" + (item.html || item) + "</div></li>";
                if (opt.check) {
					var autocomplete_item = "<li>";
					if (item.alt) autocomplete_item += "<div class=\"inline_block lpadding_2\"><input type=\"checkbox\" style=\"vertical-align: initial;\" value=\"\"></div>";
					autocomplete_item = autocomplete_item + "<div class=\"autocomplete_item inline_block lpadding_3\" onclick=\"if(typeof autocomplete_callback === 'function'){autocomplete_callback(this);}\" >" + (item.html || item) + "</div></li>";
                }
                return autocomplete_item;
            },
			wrapper: "<ul class='autocomplete nowrap'/>",
			insertText: function(item) { return item.text; }
			}, opt);

		if($.ui.autocomplete.ext) {
			for(var ext in $.ui.autocomplete.ext) {
				if(opt[ext]) {
					opt = $.extend(opt, $.ui.autocomplete.ext[ext](opt));
					//delete opt[ext];
				}
			}
		}

        var $input = $(this);

		/*
		 * commented by Jia Huang
		 * 不启用输入法是会造成重复两次keypress事件, 暂时comment掉
		var ime_fix;
		var ime_fix_timeout = 500;

		$input
		.bind('focus.autocomplete', function(){
			var old_val = $input.val();
			var just_focus = true;
			ime_fix = window.setInterval(function(){
				var new_val = $input.val();
				if (just_focus || old_val != new_val) {
					just_focus = false;
					old_val = new_val;
					var e = $.Event("keypress.autocomplete");
					e.charCode = 64; // 模拟一个允许的键值输入
					$input.trigger(e);
					return false;
				}
			}, ime_fix_timeout);
		})
		.bind('blur.autocomplete', function(){
			clearInterval(ime_fix);
		});
		*/

		var $alt;
		if (opt.alt) $alt = $(opt.alt);

		$input
		.bind('change.autocomplete', function(e) {
			if ($alt && $alt.data('autocomplete.text') != $input.val()) {
				$alt.val('');
			}
		})
		.bind('autoactivate.autocomplete', function(e, item) {
			if(typeof item != 'undefined'){
				var text = item.text;
				if (typeof text == 'undefined'){
                    text = item.tip || item;
				}
				$input.val(text);
				if ($alt) {
					$alt.val(item.alt || item).change(); // hidden改变时，触发change事件 (xiaopei.li@2011.05.31)
					$alt.data('autocomplete.text', text);
				}
			}
		})
		/*TODO keypress 暂时用 keyup来取代, 但是目前IE和chrome中仍然在中文输入下触发事件太过于频繁，望有好的解决方案来进行处理，比如说chrome和firefox下用oninput事件，IE下使用onpropertychange事件来进行处理的方式进行解决, 或者查询jquery中是否有相应的事件处理机制，已经融合其中了。列入2.2.1中进行解决。*/
		.bind('keyup.autocomplete', function(e) {
			var eTarget = $ (e.target || this);
			var typingTimeout = $.data(this, "typingTimeout");
			var current_val = eTarget.val();
			if (current_val == eTarget.data('current_val')) {
				e.preventDefault();
				return	false;
			}
			eTarget.data('current_val', current_val);
			
			if(typingTimeout) window.clearInterval(typingTimeout);

			if($.data(document.body, "suppressKey"))
				return $.data(document.body, "suppressKey", false);
			/*
			else if($.data(document.body, "autocompleteMode") && e.charCode < 32 && e.keyCode != 8 && e.keyCode != 46)
				return false;
			*/
			else {
				$.data(this, "typingTimeout", window.setTimeout(function() {
					eTarget.trigger("autocomplete");
				}, opt.timeout));
			}
		})
		.bind('click.autocomplete',function(){
			var clickTimeout = $input.data('clickTimeout');
			if (clickTimeout) {
				clearTimeout(clickTimeout);
				$input.data('clickTimeout', null);
			}

			if(!$('ul.autocomplete').length){

				clickTimeout = setTimeout(function(){
					$input.trigger('autocomplete.autocomplete');
				}, 50);

				$input.data('clickTimeout', clickTimeout);
			}

		})
		.bind("autocomplete.autocomplete", function() {
			var self = $(this);
			self.one("updateList", function(e, list) {

                // if(!$(this).parent().hasClass('toke_more')) 
                $(document).trigger("off.autocomplete");

				if (!list.length){
					return false;
				}
	
				list = $(list)
				.map(function() {
					var node = $(opt.template(this))[0];
					$.data(node, "originalObject", this);
					return node;
				});

				size = list.length;
				
                var container = list.wrapAll(opt.wrapper).parent();
                container.scrollTop = 0;

				if(opt.base){
					obj=$(opt.base);
				}else{
					obj=self;
				}

				//BUG4713 When autocomplete, the autocomplete dialog is wider than the object.
				var width=obj.outerWidth() - 1;
				var height=obj.outerHeight();
				opt.container = container;

                var userAgent = navigator.userAgent.toLowerCase();
                $is_windows = userAgent.indexOf("windows") > -1;
				var top = obj.offset().top + height;
                var left = obj.offset().left;
                left = $is_windows ? parseInt(left) - 8 : left;
				var overflow = 'auto';
				container.css({left : left, top : top, width : width, overflow : overflow});
				container.css('max-height', 225);
                container.scrollTop = 0;	
				container.scroll(function () {
                    /* 加一个系数, 可以实现 当前条数 * 系数 = 每页展示数 */
                    var coefficient = 1;
                    if (container.hasClass('autocomplete_more')) coefficient = 10;
					if(this.scrollTop + this.clientHeight ==  this.scrollHeight
						|| Math.ceil(this.scrollTop + this.clientHeight) ==  this.scrollHeight) {
						if(opt.st > 95 * coefficient) {
							return;
						}
						if(opt.st == 0) {
							opt.st += 10 * coefficient;
						}
						else {
							opt.st += 5 * coefficient;
						}
						var $base = opt.base ? $(opt.base) : $input;

						var _data = Q.toQueryParams($base.classAttr('static'))||{};
						var selectors=Q.toQueryParams($base.classAttr('dynamic'))||{};
					for(k in selectors){
							_data[k]=$(selectors[k]).val();
                        }
                        
						_data.s = $input.filter(':not(.hint)').val();
						_data.st = opt.st;
						if (opt.extra) {
							ajax = opt.ajax + $(opt.extra).val()
						}
						$.ajax({
							url: opt.ajax, 
							data: _data, 
							success: function(json) {
								if(json.length == 0) {
									opt.st -= 5 * coefficient;
									return;
								}

								var new_list = $(json)
									.map(function() {
										var node = $(opt.template(this))[0];
										$.data(node, "originalObject", this);
										return node;
									});

                                container.append(new_list);
								$(document).autocompleteMode(container, self, size, opt);
							},
							dataType: 'json',
							global: false
						});
					}
                });

                container.appendTo('body');
                
				$(document).autocompleteMode(container, self, size, opt);

				$(window).resize(function(){
					if(container.length){
						var width=obj.outerWidth();
						var height=obj.outerHeight();
						var top = obj.offset().top + height;
                        var left = obj.offset().left;
                        left = $is_windows ? parseInt(left) - 8 : left;
                        
						container.css({left : left, top : top, width : width});
					}
				});
			});

			opt.getList(self);
		});
	};


	$(':visible > input:text[class*="autocomplete\\:"], :visible > input:text[q-autocomplete]').livequery(function(){
		var $input = $(this);
		$input
		.autocomplete({
			ajax: $input.classAttr("autocomplete"),
			alt: $input.classAttr("autocomplete_alt")
		});

		$input.bind('focus.autocomplete', function() {
			setTimeout(function(){
				$input.trigger('autocomplete.autocomplete');
			}, 50);
		});

	}, function(){
		var $input = $(this);
		$input.trigger('blur.autocomplete');
		$input.unbind('focus.autocomplete autocomplete.autocomplete change.autocomplete autoactivate.autocomplete focus.autocomplete blur.autocomplete keypress.autocomplete');
    });
    
})(jQuery);
