(function ($) {

    var div_add = '<div class="token add"><span class="icon-add"></span></div>';
    var tokenParsers = {
        email: {
            parse: function (str) {
                var pattern = /^\s*([^<>\s]+)\s*<(\S+)>\s*$/;
                var email_pattern = /[a-z0-9!#$%&'*+\/=?\^_`{|}~\-]+(?:\.[a-z0-9!#$%&'*+\/=?\^_`{|}~\-]+)*@(?:[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?\.)+(?:[A-Z]{2}|com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum)\b\s*$/i;
                var parts;

                parts = str.match(pattern);
                if (!parts) {
                    // 检查是不是只是一个email地址
                    parts = str.match(email_pattern);
                    if (!parts) {
                        return null;
                    }

                    return {key: parts[0], value: parts[0]};
                }

                var key = parts[2];
                var value = parts[1];

                // 检查email是否合法
                parts = key.match(email_pattern);
                if (!parts) {
                    return null;
                }

                return {key: key, value: value};
            }
        }
    };
    var $token_uniqid = "";
    $(document).ready(function () {
        $('#cancel').bind('click', function () {
            document
                .forms[0]
                .reset();
            $(".token_box .token").remove();
        });
    })
    $(':visible > input.token').livequery(function () {

        // <input class="token token_autocomplete:http://asdas token_max:10 token_verify
        // token_parser:email" />

        function guid() {
            function S4() {
                return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
            }
            return (S4() + S4() + "-" + S4() + "-" + S4() + "-" + S4() + "-" + S4() + S4() + S4());
        }

        var $input = $(this);

        var autocomplete = $input.classAttr('token_autocomplete') || '';
        var max = $input.classAttr('token_max') || 0;
        var parser = tokenParsers[$input.classAttr('token_parser')] || null;
        var verify = $input.hasClass('token_verify');
        var readonly = $input.classAttr('token_readonly')
            ? true
            : false;
        var box = $input.hasClass('autocomplete_box');
        var token_more = $input.hasClass('token_more');
        if (token_more) {
            $input.attr('token_uniqid', guid())
        }

        var tokens = {},
            tokensCount = 0;
        var origin_tokens = {},
            origin_count = 0;
        var cls = $input.attr('class') || '';
        var new_cls = cls.replace(/\btoken\S*|\btext\b/g, '');
        var token_tip = $input.classAttr('token_tip') || $input.classAttr('tooltip') || $input.attr(
            'q-tooltip'
        );
        var token_tip_position = $input.classAttr('tooltip_position') || 'left';

        var $tokenBox = $('<div class="clearfix token_box ' + (token_more ? ' token_more ' : '') + new_cls + '" />');
        var $tokenInput = $('<input />');

        // hongjie.zhu >>>> select_token jia.huang select_token => readonly
        if (readonly) {
            $tokenInput
                .css({border: 'none', visibility: 'hidden'})
                .attr('readonly', 'true');
        }

        function tokenElement(k, v) {
            var em = false;
            if (arguments.length < 2) {
                if (parser) {
                    var pair = parser.parse(k);
                    if (!pair) {
                        return null;
                    }
                    k = pair.key;
                    v = pair.value;
                } else {
                    v = k;
                }
            }

            if (tokens[k]) {
                return null;
            }

            tokens[k] = v;

            if (typeof(v) == 'object') {
                em = v.em;
                v = v.text;
            }

            var $token = $(
                '<div class="token' + (
                    em
                        ? ' token_em'
                        : ''
                ) + '"><strong/><span class="remove_button" style="visibility: hidden;">&#160;<' +
                '/span></div>'
            );

            $token
                .find('strong')
                .text(v);

            $input.val($.toJSON(tokens));
            $input.trigger('token.input.change')

            $token
                .bind('click', function () {
                    // $tokenInput.insertAfter($token).focus().trigger('_blur.token');
                    // $tokenInput.appendTo($tokenBox).focus().trigger('_blur.token');
                    $tokenInput
                        .appendTo($tokenBox)
                        .focus()
                        .trigger('_blur.token');
                    $token
                        .addClass('token_selected')
                        .siblings('.token')
                        .removeClass('token_selected');
                    return false;
                })
                .bind('unload.token', function () {
                    delete tokens[k];
                    tokensCount--;
                    $token.remove();
                    $input.val($.toJSON(tokens));
                    $input.trigger('token.input.change')
                    if (tokensCount === 0) {
                        $tokenInput.addClass('visible');
                    }
                })
                // flag::用hover无效; 原因未知
                .bind('mouseover', function () {
                    $token
                        .find('span.remove_button')
                        .css('visibility', 'visible');
                })
                .bind('mouseout', function () {
                    $token
                        .find('span.remove_button')
                        .css('visibility', 'hidden');
                })
                .find('span.remove_button')
                .bind('click', function () {
                    if(typeof autoremove_callback === 'function'){autoremove_callback(this);}
                    $token.trigger('unload.token');
                    return false;
                });

            return $token;
        }

        try {

            var i,
                t,
                tmp = $.secureEvalJSON(this.value);

            if (tmp == null) 
                throw(0);

            if (tmp.length) { // 数组
                for (i in tmp) {
                    if (tmp.hasOwnProperty(i)) {
                        t = tokenElement(tmp[i]);
                        if (t) {
                            $tokenBox.append(t);
                            tokensCount++;
                        }
                    }
                }
            } else { // 键值对
                for (i in tmp) {
                    if (tmp.hasOwnProperty(i)) {
                        t = tokenElement(i, tmp[i]);
                        if (t) {
                            $tokenBox.append(t);
                            tokensCount++;
                        }
                    }
                }
            }
        } catch (e) {}

        if (tokensCount < 1) {
            $tokenInput.addClass('visible');
        }

        if (autocomplete && $.fn.autocomplete) {
            var opt = {
                ajax: autocomplete,
                base: $tokenBox
            };
            if (readonly || box) {
                opt.wrapper = '<ul class="token_box autocomplete" />';
            }
            if (token_more) {
                opt.wrapper = '<ul class="autocomplete autocomplete_more"></ul>';
                opt.check = true;
            }

            $tokenInput.autocomplete(opt);
        }

        //根据input调整宽度
        (function () {
            var fakeInput = $input.clone();
            fakeInput
                .show()
                .css({visiblity: 'hidden'})
                .appendTo('body');

            // Clh 2018-09-20 width=>100% $tokenBox.width(fakeInput.innerWidth() - 2);
            $tokenBox.css('width', '400px');
            $tokenBox.css('height', '85px');
            $tokenBox.css('overflow', 'scroll');
            fakeInput.remove();
        })();

        $tokenBox
            .click(function () {
                if (readonly) {
                    $tokenInput.trigger('autocomplete');
                } else {
                    if ($tokenBox.prev().hasClass('token_more')) {
                        $token_uniqid = $tokenBox.prev().attr('token_uniqid')
                    }
                    $tokenInput
                        .appendTo($tokenBox)
                        .focus();
                }
                return false;
            })
            .append($tokenInput);

        $input.after($tokenBox);

        $tokenInput.data('token.focus', false);

        $tokenInput
            .bind('focus.autocomplete', function () {
                if (autocomplete) {
                    setTimeout(function () {
                        $tokenInput.trigger('autocomplete.autocomplete');
                    }, 50);
                }
            })
            .bind('focus.token', function () {
                $tokenInput
                    .siblings('.token')
                    .removeClass('token_selected');
                if ($tokenInput.next().length < 1) {
                    // input at the end
                    var $prev = $tokenInput.prev();
                    var offset;
                    if ($prev.length > 0) {
                        offset = $prev
                            .offset()
                            .left - $tokenBox
                            .offset()
                            .left + $prev.width();
                    } else {
                        offset = 0;
                    }
                    // $tokenInput.width($tokenBox.width() - offset - 10);
                    $tokenInput
                        .addClass('token_input')
                        .width(60);
                } else {
                    $tokenInput.width(40);
                }

                $tokenInput.data('token.focus', true);
                $tokenInput.addClass('visible float_left');

                if (token_tip && $tokenInput.data('token.focus')) {
                    var tooltip = $tokenInput.data('tooltip');
                    if (!tooltip) {
                        tooltip = new Q.Tooltip({
                            content: token_tip,
                            position: token_tip_position,
                            offsetY: parseInt(0, 10),
                            el: $tokenInput
                        });
                    }

                    tooltip.remove();
                    tooltip.show();
                    $tokenInput.data('tooltip', tooltip);
                }

            })
            .bind('_blur.token', function () {
                $tokenInput.data('token.focus', false);
                $tokenInput
                    .val('')
                    .width(0)
                    .removeClass('visible token_input float_left');
            })
            .blur(function () {
                $tokenInput.data('token.focus', false);
                $tokenInput
                    .val('')
                    .appendTo($tokenBox);
                if (tokensCount > 0) {
                    $tokenInput
                        .width(0)
                        .removeClass('visible');
                }
                if (token_tip && !$tokenInput.data('token.focus')) {
                    var tooltip = $tokenInput.data('tooltip');
                    tooltip.remove();
                }
            })
            .keydown(function (e) {
                var focus = $tokenInput.data('token.focus');
                var code = e.which || e.keyCode;
                var $prev,
                    $next;

                switch (code) {
                    case 8: //delete
                        if (!focus || this.value == '') {
                            $prev = $tokenInput.prev('.token');
                            $next = $tokenInput.next('.token');
                            if ($prev.length > 0) {
                                if ($prev.hasClass('token_selected')) {
                                    $prev.trigger('unload.token');
                                    $tokenInput.trigger('focus.token');
                                } else if ($next.length > 0 && $next.hasClass('token_selected')) {
                                    $next.trigger('unload.token');
                                    $tokenInput.trigger('focus.token');
                                } else {
                                    $prev.addClass('token_selected');
                                    $tokenInput.trigger('_blur.token');
                                }
                            }
                            return false;
                        }
                        break;
                    case 37: //left
                        if ($tokenInput.caret().begin === 0) {
                            $prev = $tokenInput.prev('.token');
                            $next = $tokenInput.next('.token');
                            if ($prev.length > 0) {
                                if ($prev.hasClass('token_selected')) {
                                    $prev.removeClass('token_selected');
                                    $tokenInput
                                        .after($prev)
                                        .val('')
                                        .trigger('focus.token');
                                } else if ($next.length > 0 && $next.hasClass('token_selected')) {
                                    $next.removeClass('token_selected');
                                    $tokenInput.trigger('focus.token');
                                } else {
                                    $prev.addClass('token_selected');
                                    $tokenInput.trigger('_blur.token');
                                }
                            } else if (!focus) {
                                $tokenInput.trigger('focus.token');
                            }
                            return false;
                        }
                        break;
                    case 39: //right
                        if ($tokenInput.caret().begin == this.value.length) {
                            $prev = $tokenInput.prev('.token');
                            $next = $tokenInput.next('.token');
                            if ($next.length > 0) {
                                if ($next.hasClass('token_selected')) {
                                    $next.removeClass('token_selected');
                                    $tokenInput
                                        .before($next)
                                        .trigger('focus.token');
                                } else if ($prev.length > 0 && $prev.hasClass('token_selected')) {
                                    $prev.removeClass('token_selected');
                                    $tokenInput.trigger('focus.token');
                                } else {
                                    $next.addClass('token_selected');
                                    $tokenInput.trigger('_blur.token');
                                }
                            } else if (!focus) {
                                $tokenInput.trigger('focus.token');
                            }
                            return false;
                        }
                        break;
                }
                return true;
            })
            .bind('keypress.token_box', function (e) {
                if (max > 0 && tokensCount >= max) {
                    return false;
                }
                if (!$tokenInput.data('token.focus')) {
                    return false;
                }

                var code = e.which || e.keyCode;

                if ((code == 13 || code == 3)) { //enter
                    e.preventDefault();
                    this.value = $.trim(this.value); // BUG #722::token_box 允许为空值(xiaopei.li@2011.06.24)
                    if (this.value != '') {
                        if (verify && autocomplete) {
                            $tokenInput.trigger("autoactivate.autocomplete");
                        } else {
                            var t = tokenElement(this.value);
                            if (t) {
                                // $tokenInput.before(t);
                                $tokenBox.children('.add').before(t);
                                tokensCount++;
                            }
                            $tokenInput.val('').trigger('focus.token');
                        }
                        return false;
                    }
                }

            })
            .bind('autoactivate.autocomplete', function (e, item) {
                if ($(this).parent().hasClass('token_more') 
                    && $token_uniqid && $token_uniqid !== $(this).parent().prev().attr('token_uniqid')) {
                    return;
                }
                if (typeof(item) != 'object') {
                    item = $tokenInput.data('autocomplete.selected');
                }
                $tokenBox
                    .children('.add')
                    .remove();
                // if (typeof(item) == 'object' && item.text) {
                if (typeof(item) == 'object') {
                    var t;
                    if (item.alt) {
                        t = tokenElement(item.alt, item.text || item.tip || item);
                    }else{
                        t = tokenElement(item.text || item);
                    }
                    if (t) {
                        $tokenInput.before(t);
                        tokensCount++;
                    }
                }
                $tokenInput.val('');
                if (!readonly) {
                    $tokenInput.trigger('focus.token');
                }
                /*
			* @Date:2018-10-18 10:56:05
			* @Author: LiuHongbo
			* @Email: hongbo.liu@geneegroup.com
			* @Description:add按钮，可以通过点击该按钮进行添加操作
			*/

                $tokenInput.before(div_add);

            })
            .click(function () {
                return false;
            });

        $input.bind('autoactivate.autocomplete', function (e, item) {
            $tokenInput.trigger('autoactivate.autocomplete', item);
        });

        /*
		* @Date:2018-10-18 11:27:43
		* @Author: LiuHongbo
		* @Email: hongbo.liu@geneegroup.com
		* @Description:首次加载时添加‘添加按钮’
		*/
        $tokenInput.before(div_add);
        origin_tokens = tokens;
        origin_count = tokensCount;
        $('#cancel').on('click', function () {
            tokens = {};
            tokensCount = 0;
            for (i in origin_tokens) {
                var t = tokenElement(i, origin_tokens[i]);
                $tokenInput.before(t);
            }
			$tokenInput.before(div_add);
        })

    }, function () {
        $(this)
            .unbind('autoactivate.autocomplete')
            .next('div.token_box')
            .remove();
    });

})(jQuery);
