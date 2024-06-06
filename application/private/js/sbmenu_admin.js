/*
 * index: 当前可用的自增索引值
 * container_id: 容器id
 * delete_message: 删除时的确认消息
 * undeletable_error: 项中有模块的情况将不可删除，提示错误
 */
jQuery(function($) {

    var $container = $('#' + container_id);
    var $categories = $container.find('.sbmenu_categories');
    var index_token = '@INDEX';
    var token_regex = new RegExp(Q.escape(encodeURIComponent(index_token)) + '|' + Q.escape(index_token), 'g');

    var el = document.getElementById('sbmenu_categories');
    var sortable = new Sortable(el, {
        animation: 150,
        draggable: '.category'
    });

    var items_count = $('#sbmenu_categories .items').length;

    for (var i = 1; i < items_count + 1; i++) {
        if (document.getElementById('items_' + i)) {
            var el = document.getElementById('items_' + i);
            Sortable.create(el, {
                group: {
                    name: 'sbmenu_categories',
                    pull: true,
                    put: true,
                },
                animation: 150,
                forceFallback: true,
                draggable: '.item',
                fallbackClass: 'moving_item',
                onEnd: function (ele) {
                    var $el = $(ele.item)
                    var $from = $(ele.from)
                    var prev_id = $from.attr('id').substring(6)
                    var current_id = $el.parent().attr('id').substring(6)
                    var $inputs = $el.find('input')
                    $inputs.each(function() {
                        $(this).attr('name',$(this).attr('name').replace('categories[' + prev_id + ']', 'categories[' + current_id + ']'))
                    })
                },
            });
        }
    }

    var el = document.getElementById('items_@others');
    Sortable.create(el, {
        group: {
            name: 'sbmenu_categories',
            pull: true,
            put: true
        },
        animation: 150,
        draggable: '.item',
        forceFallback: true,
        fallbackClass: 'moving_item',
        onEnd: function (ele) {
            var $el = $(ele.item)
            var $from = $(ele.from)
            var prev_id = $from.attr('id').substring(6)
            var current_id = $el.parent().attr('id').substring(6)
            var $inputs = $el.find('input')
            $inputs.each(function() {
                $(this).attr('name',$(this).attr('name').replace('categories[' + prev_id + ']', 'categories[' + current_id + ']'))
            })
        },
    });

    /*$categories.sortable({
    	handle: '.category_title',
    	placeholder: 'category_placeholder',
    	forcePlaceholderSize: true,
    	axis: 'y',
    	containment: 'parent'
    });*/

    /*$categories.find('.items').livequery(function(){
    	$(this).sortable({
    		connectWith: '.sbmenu_categories .items',
    		placeholder: 'item_placeholder',
    		forcePlaceholderSize: true,
    		receive: function(e, ui) {
    			var o_id = ui.sender.classAttr('category_id');
    			var n_id = $(this).classAttr('category_id');
    			// 修改隐藏的input内的class和name的值
    			$('[name^="categories"]', ui.item).each(function(){
    				var $el = $(this);
    				$el.attr('name', $el.attr('name').replace('categories['+o_id+']', 'categories['+n_id+']'));
    			});
    		},
    		containment: $categories
    	});
    });*/

    // $container.find('a.button_add').on('click', function(){
    $('a.button_add').on('click', function() {
        return false; // 弃用该操作 转为dialog后台添加
        index++;

        var tpl = $container.find('script.sbmenu_category_template')[0].text;
        var $new = Q.clone(tpl, index, [{
            pattern: token_regex,
            value: index
        }]);

        $categories.append($new);
        return false;
    });

    $container.find('a.button_delete').on('click', function() {
        if ($(this).parents('li.category').eq(0)
            .find('li.item').length > 0) {
            alert(undeletable_error);
            return;
        }

        if (confirm(delete_message)) {
            $(this).parents('li.category').eq(0).remove();
        }

    });
});
