(function($) {
    
    var $document = $(document);

    var supportFix = Q.supportFix();

    var makeStickyHeader = function() {
        var $body = $(document);
        var $table = $(this);
        var $originalHeader = $table.find('thead.header');
    
        var $header = $originalHeader.clone(true).insertBefore(this)
            .wrap('<table class="calendar_week_header"/>')
            .parent()
            .css({
                zIndex: 499,
                visibility:"hidden",
            });

        if (supportFix) {
            $header.css({position:"fixed", top:-10000});
        }
        else {
            $header.css({position:"absolute", top:-10000});
        }

        $header.find('.check_hover').trigger('mouseleave');

        var altered = true;
        var vLength = 0;

        //更新浮动表头位置
        function tracker() {
            if (altered) {
                altered = false;
                var $tableHeaders = $('th', $originalHeader);
                $header.width($table.width());
                $header.find('th').each(function(i) {
                    $(this).width($tableHeaders.eq(i).width());
                });

                vLength = $table.height();
            }

            var header_height = 0;
            var layout_width  = 70;
            if ($('.layout-header').height()) {
                header_height = 48;
                layout_width  = 270;
            }
            var vOffset =  $body.scrollTop() + 48 + header_height - $originalHeader.offset().top;
            if (vOffset > 0  && vLength > 40) {
                if (supportFix) {
                    $header.show().css({left: $originalHeader.offset().left - $body.scrollLeft() + 1, top: (48 + header_height), visibility:"visible"});
                    $('.display_with_calendar_week_header_left').css({left: $originalHeader.offset().left - $body.scrollLeft() + 1, top: (2 + header_height), visibility:"visible", position: "fixed", zIndex: 500});
                    
                    $('.display_with_calendar_week_header_content').css({
                        position: "fixed",
                        width: "100%",
                        left: 220,
                        top: 50,
                        visibility: "visible",
                        zIndex: 499,
                        height: 50
                    })

                    var o = document.getElementById("layout_body");
                    var w = o.clientWidth || o.offsetWidth;

                    $('.display_with_calendar_week_header_right').css({left: $originalHeader.offset().left - $body.scrollLeft() + 1 + w  - layout_width - $('.display_with_calendar_week_header_right').find('#toggle_button').width() + 4,
                        top: (2 + header_height), width: $('.display_with_calendar_week_header_right').find('#toggle_button').width(), visibility:"visible", position: "fixed", zIndex: 500});
                }
                else {
                    $header.css({top: $body.scrollTop() - 36,  visibility:"visible"}); // 为什么要减36 不明白
                }
            }
            else {
                if (supportFix) {
                    $header.css({visibility:"hidden"});
                    $('.display_with_calendar_week_header_left').css({position: "unset"});
                    $('.display_with_calendar_week_header_right').css({position: "unset", width: ''});
                    $('.display_with_calendar_week_header_content').css({position: "unset", height: 0});
                }
                else {
                    $header.css({visibility:"hidden", top:-10000});
                }
            }
    
        }
    
        //响应滚屏
        $(window).unbind('scroll.calendar_week_scrollStickyHeader').bind('scroll.calendar_week_scrollStickyHeader', tracker);
    
        //响应窗口大小变更
        var sizing = false;
        var timeout = null;

        $originalHeader
        .resize(function() {
            if (timeout) clearTimeout(timeout);
            timeout = setTimeout(function() {
                altered = true;
                tracker();
            }, 100);
        });

        tracker();

    };

    var Week = function (node) {
        var $table = $(node);
        if(!$table.is('table')) { $table = $table.parents('table:first'); }
        node = $table[0];

        var week = $table.data('Q.Calendar.Week');
        if (week) { return week; }

        $table.data('Q.Calendar.Week', this);

        this.$table = $table;
        this.url = $table.parents('div[src]:first').attr('src');
        
        this.dtStart = parseInt(this.$table.classAttr('datetime')||0, 10);
        this.ajaxObject = this.$table.classAttr('object') || '';
        
        this.$container = $table.find('.hour_components');

        makeStickyHeader.apply(node);
    };

    Week.Component = function (node) {
        var $block =  $(node);
        if (!$block.hasClass('block')) { $block = $block.parents('.block:first'); }
        var component = $block.data('Q.Calendar.Week.Component');
        return component;
    };

    var _guid=0;
    
    Week.RENDER_HOVER = 2;
    Week.RENDER_MOVING = 1;
    Week.RENDER_DEFAULT = 0;

    var hover_component;


    Week.prototype.getComponent = function () {
        var parent = this;
        var $container = this.$container;
        var $blocks = [];
        var args = arguments;
        
        if(args.length == 1 && args[0].id) {
            var components = this.components;
            for (var i in components) {
                if (components[i].id == args[0].id) {
                    $.extend(components[i], args[0] || {});
                    return components[i];
                }
            }
        }

        function Component() {
            this.parent = parent;
            this.guid = _guid ++;
            parent.components =  parent.components || {};
            parent.components[this.guid] = this;
            //重新生成数组
            parent.comp_arr = [];
            for(var guid in parent.components) {
                if (parent.components.hasOwnProperty(guid)) {
                    parent.comp_arr.push(guid);
                }
            }
            
            if(args.length == 1) {
                $.extend(this, args[0] || {});
            }
            
            this.dtStart = parseInt(this.dtStart, 10);
            this.dtEnd = parseInt(this.dtEnd, 10);
        }

        Component.prototype.getBlock = function () {
            var $block = $('<div class="block" onclick="void(0)" style="overflow: hidden"><div class="content"/></div>').prependTo($container);
            /*
            $block.hover(
            //移上
            function () {
                $block.css({zIndex:200});
            },
            //移出
            function () {
                $block.css({zIndex:$block.data('Q.Calendar.Week.Component.zIndex')});
            });
            */
            return $block;
        };
        
        Component.prototype.setZ = function (zindex) {
            for (var i = 0; i<$blocks.length; i++) {
                if (this.color == 9) {
                    $blocks[i].data('Q.Calendar.Week.Component.zIndex', 0);
                    $blocks[i].css({zIndex: 0});
                } else {
                    $blocks[i].data('Q.Calendar.Week.Component.zIndex', zindex);
                    $blocks[i].css({zIndex: zindex});
                }
            }
        };

        Component.prototype.resetIndent = function () {
            var pos1 = parent.getDayYFromDateTime(this.dtStart);
            var indent = this.indent || 0;
            for (var i=0; i < $blocks.length; i++) {
                $blocks[i].css({
                    left: parent.dayX[pos1.day] + indent,
                    width: parent.dayW[pos1.day++] * 0.9 - indent
                });
            }
        };

        Component.prototype.hide = function() {
            for (var i=0; i < $blocks.length; i++) {
                $blocks[i].hide();
            }
        };

        Component.prototype.show = function() {
            for (var i=0; i < $blocks.length; i++) {
                $blocks[i].show();
            }
        };

        
        Component.prototype.render = function (type) {
            var dtStart, dtEnd;

            if (this.dtStart > this.dtEnd) {
                dtStart = this.dtEnd;
                dtEnd = this.dtStart;
            } else {
                dtStart = this.dtStart;
                dtEnd = this.dtEnd;
            }

            var pos1 = parent.getDayYFromDateTime(dtStart);
            var pos2 = parent.getDayYFromDateTime(dtEnd);
            var rect = parent.hourGridRect;

            var count = pos2.day - pos1.day + 1;

            if ($blocks.length < count) {
                var n = count - $blocks.length;
                while (n--) {
                    $blocks.push(this.getBlock());
                }
            }
            else if($blocks.length > count) {
                var n = $blocks.length - count;
                while (n--) {
                    $blocks.pop().remove();
                }
            }
            
            $blocks[0].find('.content').html(this.content);

            var clsPrefix = 'block';
            
            if (arguments.length === 0) type = Week.RENDER_DEFAULT;
            switch(type) {
            case Week.RENDER_MOVING:    //moving
                this.indent=0;
                clsPrefix += ' block_rect';
                break;
            case Week.RENDER_HOVER:    //hover
                this.indent=0;
                clsPrefix += ' block_hover';
                this.setZ(49);
                break;
            default:
                clsPrefix += ' block_default';
                if (this.color !== undefined) {
                    clsPrefix += ' block_'+this.color;
                }
                if (!this.id) {
                    clsPrefix += ' block_fixed';
                };
                if (this.extra_class !== undefined) {
                    clsPrefix +=  ' '+ this.extra_class;
                }
            
                (function () {
                    var components = parent.components;
                    
                    var abs = function(x) { return x > 0 ? x : x * -1; };
                    
                    //排序
                    function componentSort(i1, i2) {
                        var c1 = components[i1];
                        var c2 = components[i2];
                        if (!c1 || !c2) { return 0; }
                        var delta = c1.dtStart - c2.dtStart;
                        if (abs(delta)< 1800) {
                            delta = c2.dtEnd - c1.dtEnd;
                            //var distance = abs(delta);
                            //if (distance < 1800) {
                                //if (c1.overlapped && c1.overlapped[c2.guid]) return c1.indent - c2.indent;
                                //两者相近
                                var i;
                                if (c1.overlapped) {
                                    for (i in c1.overlapped) {
                                        if ( abs(components[i].dtStart - c2.dtStart) > 1800) {
                                            return components[i].dtStart - c2.dtStart;
                                        }
                                    }
                                }
                                
                                if (c2.overlapped) {
                                    for (i in c2.overlapped) {
                                        if ( abs(c1.dtStart - components[i].dtStart) > 1800) {
                                            return c1.dtStart - components[i].dtStart;
                                        }
                                    }
                                }
                                
                                c1.overlapped = c2.overlapped = $.extend({}, c1.overlapped || {}, c2.overlapped || {});
    
                                var overlapped = c1.overlapped;
                                //确认每个overlapped的节点都与当前节点重合
                                overlapped[c1.guid] = c1.guid;
                                overlapped[c2.guid] = c2.guid;                            
                                
                                var j;
                                var oarr = [];
                                for (j in overlapped) {
                                    if (overlapped.hasOwnProperty(j)) {
                                        oarr.push(j);
                                    }
                                }
                                oarr.sort(function (j1, j2) {
                                    return (components[j2].dtEnd - components[j2].dtStart) - (components[j1].dtEnd - components[j1].dtStart);
                                });
                                //重设所有indent
                                var indent = 1;
                                for (j in oarr) {
                                    if (oarr.hasOwnProperty(j)) {
                                        components[oarr[j]].indent = indent;
                                        indent += 15;
                                    }
                                }
                                
                                return c1.indent - c2.indent;
                            //}
                        }
                        
                        if (c1.overlapped && c1.overlapped[c2.guid]) {
                            delete c1.overlapped[c2.guid];
                        }
                        if (c2.overlapped && c2.overlapped[c1.guid]) {
                            delete c2.overlapped[c1.guid];
                        }
                        return delta;
                    }
    
                    var carr = parent.comp_arr;
                    carr.sort(componentSort);                
    

                    var carr_no_ids = [];    
                    var carr_ids = [];      
                    for (var i in carr) {
                        if (carr.hasOwnProperty(i)) {
                            var c = components[carr[i]];
                            if (!c) { continue; }
                            if(c.id) carr_ids.push(carr[i]);
                            else carr_no_ids.push(carr[i]);
                        }
                    }
                    for( var i in carr_ids) carr_no_ids.push(carr_ids[i]);
                    carr = carr_no_ids;

                    for (var i in carr) {
                        if (carr.hasOwnProperty(i)) {
                            var c = components[carr[i]];
                            if (!c) { continue; }
                            c.setZ(56 + parseInt(i, 10));
                        }
                    }
                
                })();
                
            }
            
            var day = pos1.day, y = pos1.y;
            
            var i, indent = this.indent || 0;
            
            for (i = 0; i < $blocks.length; i++) {

                if (day < pos2.day) {
                    h = rect.height - y;
                } else {
                    h = pos2.y - y + 1;
                }

                if ( h < 12) { h = 12; }

                var cls = clsPrefix;

                if (day == pos1.day) {
                    cls += ' block_top';
                    if ($blocks[i].find('.block_top_resizer').length < 1) {
                        $blocks[i].prepend('<div class="block_top_resizer">&#160;</div>');
                    }
                }

                if (day == pos2.day) {
                    cls += ' block_bottom';
                    if ($blocks[i].find('.block_bottom_resizer').length < 1) {
                        $blocks[i].prepend('<div class="block_bottom_resizer">&#160;</div>');
                    }
                }

                cls += ' block_left';
                if ($blocks[i].find('.block_left_resizer').length < 1) {
                    $blocks[i].prepend('<div class="block_left_resizer">&#160;</div>');
                }

                $blocks[i]
                .attr('class', cls)
                .data('Q.Calendar.Week.Component', this);
                
                if (day < 0 || day > 6) {
                    $blocks[i].hide();
                }
                else {
                    w = parent.dayW[day] - 1;
                    if (type == Week.RENDER_DEFAULT) {
                        w = Math.floor(w * 0.98);    //留出5% 用于避免对hour_cell的覆盖
                    }
                    $blocks[i]
                    .css({
                        left: parent.dayX[day] + indent,
                        top: y,
                        width: w - indent,
                        height: h
                    })
                    .show();
                }

                day ++;
                y = 0;
            }

            var overlapped = this.overlapped;
            if (overlapped) {
                for (i in overlapped) {
                    if (overlapped.hasOwnProperty(i)) {
                        var c = parent.components[i];
                        if (!c || c == this) { continue; }
                        c.resetIndent();
                    }
                }
            }

        };

        Component.prototype.exists = function() {
            return parent.components[this.guid] == this;
        };

        Component.prototype.remove = function () {
            $.each($blocks, function (i, $block) {$block.remove();});
            delete parent.components[this.guid];
        };

        Component.prototype.insert = (Week.IComponent && Week.IComponent.insert) || function () {
            //AJAX调用
            Q.trigger({
                object: parent.ajaxObject,
                event: 'insert_component',
                data: {id: this.id, dtstart:this.dtStart, dtend:this.dtEnd},
                url: parent.url
            });

            if (!this.is_hover) this.remove();
        };

        Component.prototype.select = (Week.IComponent && Week.IComponent.select) || function () {
            //AJAX调用
            if (this.id) {
                var c = this;
                Q.trigger({
                    object: parent.ajaxObject,
                    event: 'select_component',
                    data: {id: this.id, dtstart:this.dtStart, dtend:this.dtEnd},
                    complete: function () {
                        c.render();
                    },
                    url: parent.url
                });
            }
        };

        Component.prototype.update = (Week.IComponent && Week.IComponent.update) || function () {
            //AJAX调用
            var c = this;
            Q.trigger({
                object:parent.ajaxObject,
                event: 'update_component',
                data: {id: this.id, dtstart:this.dtStart, dtend:this.dtEnd},
                complete: function () {
                    c.render();
                },
                url: parent.url
            });
        };

        return new Component();
    };

    Week.prototype.getLine = function() {
        var parent = this;      //parent 便于拓展
        var args = arguments;   //arguments
        var $container = this.$container;
        //lines理解为单例模式中进行存储所有已创建的对象的数组
        var lines = [];

        //所有line的view的合集
        var $views = [];

        //单例模式 由于line可能出现多个id相同的，还需要对time进行比对
        if (args.length == 1 && args[0].id && args[0].time) {
            for (var i in lines) {
                if (lines[i].id == args[0].id && lines[i].time == args[0].time) {
                    $.extend(lines[i], args[0] || {});
                    return lines[i];
                }
            }
        }

        function Line() {
            this.parent = parent;
            this.guid = _guid++;
            $.extend(this, args[0] || {});

            //存储lines lines理解为单例模式中进行存储所有已创建的对象的数组
            parent.lines = parent.lines || [];
            parent.lines[this.guid] = this;
        }

        //创建line view
        Line.prototype.getView = function() {
            $line = $('<div class="line">').prependTo($container);

            //对于原生dom进行id和time绑定，便于获取line对象
            $line[0].id = this.id;
            $line[0].time = this.time;

            return $line;
        };

        //事件绑定函数，会在line对象初始化后自动触发
        Line.prototype.bindEvent = function() {

            //便于进行l对象的事件触发,直接使用l获取this
            var l = this;

            //以下为举例
            /* this.$view为这个line对象对应的view对象，是jquery对象，
            (this.$view).bind('click', function() { //进行click事件绑定
                l.sayId(); //触发后调用l，即this的sayId方法
            });
            */
        };

        /* 初始化sayId方法
        Line.prototype.sayId = function() {
            console.log(this.id); //终端显示this对象的id
        };
        */

        //view显示
        Line.prototype.render = function () {

            color_css = 'line_' + this.color_type;

            var pos = parent.getDayYFromDateTime(this.time);
            var day = pos.day;
            var y = pos.y;

            //resize
            if (!this.$view) {
                this.$view = this.getView();
                this.bindEvent();

                $views[this.guid] = this.$view;
            }

            this.$view
                .css({
                    width: parent.dayW[day],
                    left: parent.dayX[day],
                    top: y
                })
                .addClass(color_css)
                .append($(this.view));
        };

        Line.prototype.exists = function() {
            return parent.lines[this.guid] == this;
        };

        Line.prototype.remove = function() {
            $views[this.guid].remove();
            delete parent.lines[this.guid];
        };

        /* 部分基本方法定义
        Line.prototype.insert = function() {
        };

        Line.prototype.select = function() {
        };

        Line.prototype.update = function() {
        };
        */

        return new Line();
    };
    
    Week.prototype.getDayYFromDateTime = function (dt) {
        var rect = this.hourGridRect;
        var dtOffset = dt - this.dtStart;
        var day = Math.floor(dtOffset / 86400);
        var y = Math.floor((dtOffset % 86400) * rect.height / 86400);
        return {day:day, y:y};
    };

    Week.prototype.getDateTimeFromXY = function (x, y) {
        var rect = this.hourGridRect;

        var sec = Math.floor((y - rect.top) * 86400 / rect.height);
        sec = Math.max(Math.min(86399, sec), 0);
        
        var wday = Math.floor((x - rect.left) * 7 / rect.width);
        wday = Math.max(Math.min(6, wday), 0);

        return wday * 86400 + sec + this.dtStart;

    };

    Week.prototype.bindLineEvent = function () {

        var week = this;

        var $hour_grid = week.$table.find('.hour_grid').eq(0);
        var $tick = $hour_grid.find('.tick:first');
        var $columns = $('tr:first td:not(.tick)', $hour_grid);

        var scrollRect = function() {
            week.hourGridRect = {
                left : $hour_grid.offset().left + $tick.outerWidth(),
                top : $hour_grid.offset().top,
                width : $hour_grid.innerWidth() - $tick.outerWidth(),
                height : $hour_grid.innerHeight()
            };
        };

        var lineResizeColumns = function () {
            scrollRect();

            var left = week.hourGridRect.left;
            week.dayX = [];
            week.dayW = [];

            $columns.each(function (i) {
                var $column = $(this);
                week.dayX[i] = $column.offset().left - left;
                week.dayW[i] = $column.width();
            });

            week.unitWidth = week.dayX[i] - week.dayX[0] + 1;

            var lines = week.lines;

            for (var i in lines) {
                if (lines.hasOwnProperty(i)) {
                    lines[i].render();
                }
            }
        };

        $(window)
            .bind('resize.calendar_week_resizeColumns', lineResizeColumns)

        lineResizeColumns();
    };
    
    Week.prototype.bindBlockEvent = function (blocks, submit_tip) {
        
        var is_block = blocks ? true : false;
        /*通过传入submit_tip来判断预约在移动时是否需要弹出提示确认*/
        var need_tip = submit_tip ? true : false;
        
        var week = this;

        var $hour_grid = week.$table.find('.hour_grid').eq(0);
        var $tick = $hour_grid.find('.tick:first');
        var $columns = $('tr:first td:not(.tick)', $hour_grid);

        var scrollRect = function() {
            week.hourGridRect = {
                left : $hour_grid.offset().left + $tick.outerWidth(),
                top : $hour_grid.offset().top,
                width : $hour_grid.innerWidth() - $tick.outerWidth(),
                height : $hour_grid.innerHeight()
            };
        };

        var resizeBlockColumns = function () {

            scrollRect();

            var left = week.hourGridRect.left;
            week.dayX = [];
            week.dayW = [];
            $columns.each(function (i) {
                var $column = $(this);
                week.dayX[i] = $column.offset().left - left;
                week.dayW[i] = $column.width();
            });
            
            week.unitWidth = week.dayX[1] - week.dayX[0] + 1;

            var components = week.components;
            for (var i in components) {
                if (components.hasOwnProperty(i)) {
                    components[i].render();
                }
            }

            var lines = week.lines;
            for (var i in lines) {
                if (lines.hasOwnProperty(i)) {
                    lines[i].render();
                }
            }

        };

        $(window)
            .bind('resize.calendar_week_resizeColumns', resizeBlockColumns);
        //初始化尺寸
        resizeBlockColumns();
        /*
            NO.BUG#105 (Jia.huang@2010.11.09)
            rect查找不到，初始化过程太晚。提前初始化
        */
        // window.setTimeout(resizeColumns, 20);
        
        //查找scrollable element
        /*
        (function(){
            var $parent = week.$table;
            while ($parent.length > 0 && !$parent.is('body')) {
                if ($parent.css('overflow') == 'auto') {
                    //绑定scroll
                    $parent.scroll(scrollRect);
                }
                $parent = $parent.parent();
            }
        })(); */
        
        if (is_block) {
    
            var scroll_timeout = null;
            $(document).scroll(function(){
                hover_component.disable_mousemove = true;
                if (scroll_timeout) clearTimeout(scroll_timeout);
                scroll_timeout = setTimeout(function(){
                    hover_component.disable_mousemove = false;
                }, 100);
            });

            /*
                获取当前起始点的0：00坐标位置
            */
            Week.prototype.getCurrentDateStart = function(time) {
                var now = new Date(parseInt(time)*1000);
                var year = now.getFullYear();
                var month = now.getMonth();
                var day = now.getDate();
                var start = new Date(year, month, day);
                return start.getTime() / 1000;
            }
            /*
                List1:根据当前鼠标所在的位置而获取到该鼠标停留块状区域的一系列数据：
                format['interval'] : 块状预约的最小时间
                format['align'] : 对齐时间的时间值
                format['align_start'] : 对齐时间的起始值
                format['align_end'] : 对齐时间的结束值
                format['start'] : 当前块所在设定预约块的起始时间
                format['end'] : 当前块所在设定预约块的结束时间
            */
            Week.prototype.getFormatBlock = function (time) {
                var format = {};
                var date = new Date(parseInt(time)*1000);
                var year = date.getFullYear();
                var month = date.getMonth();
                var day = date.getDate();
                /*
                    默认对齐时间的起始时间为当前鼠标所在天的0：00
                    getCurrentDateStart方法即是用来获取对齐起始时间，可以通过修改align_start的获取值来更改对齐的起始时间。
                */
                format['align_start'] = this.getCurrentDateStart(time);
                format['align_end'] = format['align_start'] + 86400;
                format['start'] = format['align_start'];
                format['end'] = format['align_end'];
                for (var i in blocks) {
                    if (!blocks[i].dtstart) break;
                    var dtstart = blocks[i].dtstart;
                    var dtend = blocks[i].dtend;
                    var start_date = new Date(year, month, day, dtstart.h, dtstart.i, 0);
                    var end_date = new Date(year, month, day, dtend.h, dtend.i, 0);
                    var start = start_date.getTime();
                    var old_end_date = new Date(year, month, day, dtend.h, dtend.i, 0);
                    var end = end_date.getTime();
                    var interval = parseInt(blocks[i].interval_time);
                    var align = parseInt(blocks[i].align_time);
                    format['interval'] = interval ? interval : 0;
                    format['align'] = align ? align : 0;
                    if (start > end) {
                        /*
                            当开始小时数大于结束小时数时，默认为结束时间为第二天时间，因此需要判断2种情况
                        */
                        end_date.setDate(day + 1);
                        end = end_date.getTime();
                        if (time*1000 >= start && time*1000 < end) {
                            format['start'] = parseInt(start/1000);
                            format['end'] = parseInt(end/1000);
                            return format;
                        }
                        else {
                            if (time*1000 >= format['start']*1000 && time*1000 < start) {
                                format['end'] = parseInt(start/1000);
                            }
                        }
                        //如果时间当月是最后一天
                        if (old_end_date.getMonth() != end_date.getMonth()) {
                            end_date = old_end_date;
                        }
                        end_date.setDate(day);
                        start_date.setDate(day - 1);
                        start = start_date.getTime();
                        end = end_date.getTime();
                    }
                    if (time*1000 >= start && time*1000 < end) {
                        format['start'] = parseInt(start/1000);
                        format['end'] = parseInt(end/1000);
                        return format;
                    }
                    else {
                        if (time >= format['start'] && time*1000 < start) {
                            format['end'] = parseInt(start/1000);
                        }
                        if (time*1000 >= end && time < format['end']) {
                            format['start'] = parseInt(end/1000);
                        }
                    }
                }
                var default_interval = parseInt(blocks['default'].interval_time);
                var default_align = parseInt(blocks['default'].align_time);
                format['interval'] = default_interval || 0;
                format['align'] = default_align || 0;
                return format;
            }
            
            /*
                List2 : 获取当前鼠标所在位置应该停留的可预约块的真正起始时间(考虑到了跨块现象的问题)
                this.dtStart : 当前视图的起始点
                wday : 当前天之前的天数
                sec : 当前鼠标距离当前天的0：00的距离
                align : 当前块的对齐时间间距
                align_start : 当前块的对齐时间起始时间
                delta : 当前鼠标所在点离当前所需块的距离
            */
            Week.prototype.getRealStart = function (x, y) {
                var rect = this.hourGridRect;

                var sec = Math.floor((y - rect.top) * 86400 / rect.height);
                var format = week.getFormatBlock(week.getDateTimeFromXY(x, y));
                var align = format['align'];
                var align_start = format['align_start'];
                
                var wday = Math.floor((x - rect.left) * 7 / rect.width);
                wday = Math.max(Math.min(6, wday), 0);
                
                var current = week.getDateTimeFromXY(x, y);
                var delta = (current - align_start ) % align;
                var real_start = current - delta;
                var real_end = real_start + format['interval'] - 60;
                if (real_end >= format['start'] && real_end < format['end']) {
                    /*如若按最小块标准计算的结束时间与开始时间处于同一个块状区域，则直接返回当前真正起始时间*/
                    return real_start;
                }
                else {
                    /*如果按照最小标准计算的结束时间与开始时间不处于同一个块状区域，则需要返回块状区域的结束点为结束值往上推算最小标准的块的开始时间*/
                    return format['end'] - format['interval'];
                }
                
            };
    
            //初始化浮动框    
            hover_component = week.getComponent();
            hover_component.disable_mousemove = false;
            hover_component.dtStart = 0;
            hover_component.dtEnd = 0;
            hover_component.is_hover = true;

            var _mousemove_timeout = null;
            var _mousemove = function (e) {
                e.preventDefault();
                
                var component = hover_component;        
                if (component.disable_mousemove) return true;

                var start = week.getDateTimeFromXY(e.pageX, e.pageY);
                var real_start = week.getRealStart(e.pageX, e.pageY);
                var format = week.getFormatBlock(start);
                var interval = format['interval'];
                
                if (component.dtStart != real_start) {
                    component.dtStart = real_start;
                    component.dtEnd = real_start + interval;
                }

                component.render(Week.RENDER_HOVER);
                component.show();
            };
        
            week.$table
            .livequery('mousemove', _mousemove);
            
            $('.block:not(.block_rect)', week.$table)
            .livequery('mouseenter', function(e){
                var $block = $(this);
                if ($block.hasClass('block_default')) {
                    hover_component.hide();
                }
                e.preventDefault();
                return false;
            })
            .livequery('mousemove', function(e){
                var $block = $(this);
                if ($block.hasClass('block_hover')) {
                    $block.bind('mousemove', _mousemove);
                }
                e.preventDefault();
                return false;
            });
        
        }
        else {
            //拖拽新建日程, 不适用于touch device
            week.$table
            .find('td.hour_cell')
            .livequery('mousedown', function (e) {
                var component = week.getComponent();
                component.dtStart = component.dtEnd = week.getDateTimeFromXY(e.pageX, e.pageY);
                component.render(Week.RENDER_MOVING);
            
                var _dragmove = function(e) {

                    component.dtEnd = week.getDateTimeFromXY(e.pageX, e.pageY);
                    component.render(Week.RENDER_MOVING);
                    e.preventDefault();
                    return false;    
                };
            
                var _dragend = function(e) {

                    if(e.button != 2) {
                        if(component.dtEnd < component.dtStart) {
                            var tmp = component.dtStart;
                            component.dtStart = component.dtEnd;
                            component.dtEnd = tmp;
                        }
                    
                        component.insert();
                    }
                    else {
                        component.remove();
                    }

                    $document.unbind('mousemove', _dragmove);
                    e.preventDefault();            
                    return false;    
                };
            
                $document
                .bind('mousemove', _dragmove)
                .one('mouseup', _dragend);

                e.preventDefault();
                return false;
            });        
        
        }
        //拖拽修改日程起始时间, 不适用于touch device
        week.$table
        .find('.block:not(.block_fixed) .block_top_resizer')
        .livequery('mousedown', function (e) {
    
            var component = new Week.Component(this);
            var component_start = component.dtStart;
            if (is_block) {
                hover_component.disable_mousemove = true;
                var format = week.getFormatBlock(component.dtStart);
            }
            else {
                component.dtStart = week.getDateTimeFromXY(e.pageX, e.pageY);
            }
            component.render(Week.RENDER_MOVING);
            
            /*当块状状态下进行的移动拖动形式*/
            var _block_move = function (format, current) {
                var block_start = format['start'];
                var block_end = format['end'];
                var current_height = component.dtEnd - current;
                var min_height = format['interval'] - 60;
                var dtstart = component.dtStart;
                if (current < block_start || current >= block_end || current_height < min_height) return;
                if ((dtstart - current) >= format['align'] / 2) {
                    component.dtStart = dtstart - format['align'];
                }
                if ((current - dtstart) >= format['align'] / 2) {
                    component.dtStart = dtstart + format['align'];
                }
                
            }
            
            var _dragmove = function (e) {
                var current = week.getDateTimeFromXY(e.pageX, e.pageY);
                current.init_current = component.init_current || current;

                if (is_block && format['align']) {
                    _block_move(format, current);
                }
                else {
                    component.dtStart = current;
                }
                component.render(Week.RENDER_MOVING);
                e.preventDefault();                
                return false;        
            };
            
            var _dragend = function(e) {
                if (e.button != 2) {
                    if(component.dtEnd < component.dtStart) {
                        var tmp = component.dtStart;
                        component.dtStart = component.dtEnd;
                        component.dtEnd = tmp;
                    }
                    if (is_block) {
                        /*在结束拖拽时进行判断限制，防止产生的块内时间小于最小值*/
                        if ((component.dtEnd - component.dtStart) < (format['interval'] - 60)) {
                            component.dtStart = component_start;
                            component.render();
                        }
                        else {
                            if (component.init_current - component.dtStart > component.dtEnd - component.init_current) {

                                component.init_current = false;

                                if (component.id)
                                    need_tip ? component.select() : component.update();
                                else
                                    component.insert();

                            }
                        }
                    }
                    else {
                        need_tip ? component.select() : component.update();
                    }
                }
                
                //再AJAX回应之前 保持原状
                component.dtStart = component_start;

                $document.unbind('mousemove', _dragmove);
                if (is_block) {
                    hover_component.disable_mousemove = false;
                }
                e.preventDefault();    
                return false;        
            };
            
            $document
            .bind('mousemove', _dragmove)
            .one('mouseup', _dragend);

            e.preventDefault();
            return false;
        });
    
        //拖拽修改日程结束时间, 不适用于touch device
        week.$table
        .find('.block:not(.block_fixed) .block_bottom_resizer')
        .livequery('mousedown', function (e) {
            var component = new Week.Component(this);
            if (is_block) {
                hover_component.disable_mousemove = true;
                var format = week.getFormatBlock(component.dtStart);
            }
            else {
                component.dtEnd = week.getDateTimeFromXY(e.pageX, e.pageY);
            }
            component.render(Week.RENDER_MOVING);
            var component_end = component.dtEnd;
            
            var _block_move = function (format, current) {
                var block_end = format['end'] || format['align_end'];
                var block_start = format['start'] || format['align_start'];
                var current_height = current - component.dtStart;
                var min_height = format['interval'] - 60;
                var dtend = component.dtEnd;
                if (current < block_start || current >= block_end || current_height < min_height) return;
                if ((current - dtend) >= format['align'] / 2) {
                    component.dtEnd = dtend + format['align'];
                }
                if ((dtend - current) >= format['align'] / 2) {
                    component.dtEnd = dtend - format['align'];
                }
            }
            
            var _dragmove = function (e) {
                var current = week.getDateTimeFromXY(e.pageX, e.pageY);
                component.init_current = component.init_current || current;

                if (is_block && format['align']) {
                    _block_move(format, current);
                }
                else {
                    component.dtEnd = current;
                }
                component.render(Week.RENDER_MOVING);
                e.preventDefault();
                return false;
            };
            
            var _dragend = function(e) {
                if (e.button != 2) {
                    
                    if(component.dtEnd < component.dtStart) {
                        /*  tmp2 "再AJAX回应之前 保持原状" 作为标识 */
                        var tmp2 = component.dtStart;
                        component.dtStart = component.dtEnd;
                        component.dtEnd = tmp2;
                    }
                    if (is_block) {
                        /*在结束拖拽时进行判断限制，防止产生的块内时间小于最小值*/
                        if ((component.dtEnd - component.dtStart) < (format['interval'] - 60)) {
                            component.dtEnd = component_end;
                            component.render();
                        }
                        else {
                            if (component.dtEnd - component.init_current >  component.init_current - component.dtStart) {
                                component.init_current = false;

                                if (component.id)
                                    need_tip ? component.select() : component.update();
                                else
                                    component.insert();
                            }
                        }
                    }
                    else {
                        need_tip ? component.select() : component.update();
                    }
                }
    
                //再AJAX回应之前 保持原状
                if(tmp2 == null){
                    component.dtEnd = component_end;
                }    
                else {
                    component.dtStart = component.dtEnd;
                    component.dtEnd = component_end;
                }
                $document.unbind('mousemove', _dragmove);
                if (is_block) {
                    hover_component.disable_mousemove = false;
                }
                e.preventDefault();            
                return false;        
            };
            
            $document
            .bind('mousemove', _dragmove)
            .one('mouseup', _dragend);

            e.preventDefault();
            return false;
        });
        
        //拖拽修改日程时间 支持touch device
        week.$table
        .find('.hour_components .block:not(.block_fixed)')
        .livequery('mousedown touchstart', function (e) {  
            e = Q.event(e);
            var isTouch = e.isTouch;
            
            var $target = $(this);
            var component = Week.Component(this);

            if (is_block) {
                if ($target.is('.block_hover')) {
                    $target.find('.block_bottom_resizer').trigger('mousedown');
                    $target.find('.block_top_resizer').trigger('mousedown');
                    e.preventDefault();
                    return false;
                }
                
                hover_component.disable_mousemove = true;
            }


            var dtStart = component.dtStart;
            var dtEnd = component.dtEnd;
    
            var rect = week.hourGridRect;
            
            var bw = week.dayW[0];
            var x = rect.left + Math.floor((e.pageX - rect.left)  / bw ) * bw;
            var y = e.pageY;
    
            //component.render(Week.RENDER_MOVING);
            var moved = false;
            
            var _dragmove = function (e) {    
                moved = true;
                e = Q.event(e);
                
                var x2 = e.pageX, y2 = e.pageY;
                x2 = Math.max(0, Math.min(x2, rect.left + rect.width - 1));
                y2 = Math.max(0, Math.min(y2, rect.top + rect.height - 1));
    
                var delta = Math.floor((x2 - x) / bw) * 86400;
                delta += Math.floor((y2 - y) * 86400 / rect.height);

                if ( week.dtStart > dtEnd + delta) {
                    delta = week.dtStart - dtEnd;
                }
    
                else if ( dtStart + delta > week.dtStart + 604799) {
                    delta = week.dtStart + 604799 - dtStart;
                }

                if (is_block) {
                    var format = week.getFormatBlock(component.dtStart);
                    if (format['align']) {
                        var step = Math.round(delta / format['align']);
                        var old_step = week.$table.data('calendar.block.step');
                        if (step != old_step) {
                            var target_start = dtStart + step * format['align'];
                            var target_end = dtEnd + step * format['align'];
                            var block_start = format['start'] || format['align_start'];
                            var block_end = format['end'] || format['align_end'];
                            if (target_start >= block_start && target_end <= block_end) {
                                component.dtStart = target_start;
                                component.dtEnd = target_end;
                            }
                            week.$table.data('calendar.block.step', step);
                        }
                    }
                }
                else {
                    component.dtStart = dtStart + delta;
                    component.dtEnd = dtEnd + delta;
                }

                component.render(Week.RENDER_MOVING);

                e.preventDefault();
                return false;        
            };
            
            var _dragend = function(e) {
                
                if (isTouch) {
                    $target.unbind('touchmove', _dragmove);
                }
                else {
                    $document.unbind('mousemove', _dragmove);
                }

                if (moved) {
                    e = Q.event(e);
    
                    if (isTouch || e.button != 2) {
                        if (component.id){
                            need_tip ? component.select() : component.update();
                        }
                        else {
                            component.insert();
                        }
                    }
                    
                    //再AJAX回应之前 保持原状
                    component.dtStart = dtStart;
                    component.dtEnd = dtEnd;
                }
                if (is_block) {
                    hover_component.disable_mousemove = false;
                }
                e.preventDefault();
                return false;
            };
    
            if (isTouch) {
                $target
                .bind('touchmove', _dragmove)
                .one('touchend', _dragend);
            }
            else {
                $document
                .bind('mousemove', _dragmove)
                .one('mouseup', _dragend);
            }


            e.preventDefault();
            return false;
        })
        .livequery('dblclick', function(e) {
            var component = Week.Component(this);
            var $target = $(this);
            if (!$target.is('.block_default')) {
                component.insert();
            }
            else {
                component.render();
                component.select();
            }
            e.preventDefault();
            return false;
        });
    };

    Q.Calendar = Q.Calendar || {};
    Q.Calendar.Week = Week;
    
})(jQuery);
