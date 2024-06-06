(function($){
    $('select.selectpicker').livequery(function() {
        $select = $(this);
        $select.attr('multiple', 'multiple');

        // 添加selectpicker
        // var selectpicker = '<div class="selectpicker width_200"></div>';
        // var check_more = '<div class="check_more float_right"><span class="icon-down"></span></div>'

        $selectpicker = $select.next();
        // $(selectpicker);

        // $select.after($selectpicker);
        // $selectpicker.append(check_more);

        // 添加select_menu
        var selectpicker_container = '<div class="selectpicker_container"><ul>';
        var check_on_flag = '<span class="icon-check float_right"></span>';
        var $options = $select.children('option');
        $options.each(function(i, val) {
            var value = $(this).val();
            if (value== '-1') return true; // continue;

            if ($(this).prop('selected')) {
                // var value = '<div class="picker_item_value">' + $(this).text() + '</div>';
                // $('.check_more').before(value);

                selectpicker_container += '<li class="picker_item picker_item_active"><div class="float_left" val="'+$(val).val()+'">' + $(val).text() + '</div>' + check_on_flag + '</li>';
            } else {
                selectpicker_container += '<li class="picker_item"><div class="float_left" val="'+$(val).val()+'">' + $(val).text() + '</div>' + check_on_flag + '</li>';
            }
        });

        selectpicker_container += '</ul></div>';
        $selectpicker_container = $(selectpicker_container);

        $selectpicker_container.appendTo('body');

        // 点击展示下拉菜单
        $('div.selectpicker').livequery('click', function(e) {
            e.stopPropagation();
            var offset = $selectpicker.offset();
            $selectpicker_container.css({top: offset.top + 30, left: offset.left, width: $selectpicker.width() + 20});
            $selectpicker_container.slideToggle();
        });

        // 选择菜单
        $('.picker_item').livequery('click', function(e) {
            e.stopPropagation();
            var $this = $(this);
            var val = $this.find('div[val]').attr('val');
            var $text = $selectpicker.text();

            if ($this.hasClass('picker_item_active')) {
                // 1.picker_item 取消选中状态
                $this.removeClass('picker_item_active');

                // 2.selectpicker中移除对应选中参数
                $('.picker_item_value').each(function(){
                    if ($(this).text() == $this.find('div[val]').text())
                        $(this).remove();
                });

                // 3.移除select中的selected
                $options.each(function() {
                    if ($(this).val() == val) {
                        $(this).removeAttr('selected');
                    }
                });


                if (!$('select.selectpicker').val()) $('select.selectpicker').find('option').first().prop('selected', true);


            } else {
                // 1.picker_item 为选中状态
                $this.addClass('picker_item_active');

                // 2.selectpicker中添加对应选中参数
                var value = '<div class="picker_item_value">' + $this.find('div[val]').text() + '</div>';
                $('.check_more').before(value);

                // 3.标记select中的selected
                $options.each(function(){
                   if ($(this).val() == val) {
                       $(this).attr('selected', 'selected');
                   }
                });
            }
        });

        $(document).click(function() {$('.selectpicker_container').hide();});

    });
})(jQuery);
