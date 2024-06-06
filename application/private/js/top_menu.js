/**
 * lims2顶部top_menu JS组件
 *
 * @author: Clh  lianhui.cao@geneegroup.com
 * @time: 2018-08-24 10:00:00
 **/

jQuery(function($){

    // 默认角色选择
    $switch_default_role = $('a#switch_default_role');
    /* $switch_default_role.livequery('click', function() {
        var $switch_default_role_ul = $('ul.switch_default_role_ul');
        if ($switch_default_role_ul.is(':hidden')) {
            $switch_default_role_ul.slideDown(300);
        } else {
            $switch_default_role_ul.slideUp(300);
        }
    }) */

    $switch_default_role.mouseover(function (e) {
        var $switch_default_role_ul = $('ul.switch_default_role_ul');
        $switch_default_role_ul.slideDown(300);
    })
    .mouseleave(function (e) {
        var $switch_default_role_ul = $('ul.switch_default_role_ul');
        if ($.contains($('.switch_default_role_ul')[0], e.relatedTarget) || $('.switch_default_role_ul')[0] == e.relatedTarget) {
            return;
        } else {
            $switch_default_role_ul.slideUp(300);
        }
    });

    $('ul.switch_default_role_ul').mouseleave(function (e) {
        if ($.contains($('.switch_default_role_ul')[0], e.relatedTarget) || $('.switch_default_role_ul')[0] == e.relatedTarget) {
            return;
        } else {
            $(this).hide();
            $('ul.extra-list').hide();
        }
    })

    // shortcut 快捷方式按钮
    $('.shortcut_extra > .shortcut_extra_title').livequery('click', function() {
        var $right_content_menus = $(this).parent().find('.right_content_menu');
        if ($right_content_menus.is(":hidden")) {
            $right_content_menus.slideDown(300);

        } else {
            $right_content_menus.slideUp(300);
            var $other_right_content_fields = $(this).parent().find('.right_content_fields');
            $other_right_content_fields.slideUp(300);
            $(this).parent().find('.right_shortcut').removeClass('active');
        }
    });

    $('.shortcut_extra > .right_content_menu > .right_shortcut').livequery('click', function() {
        var $other_right_content_fields = $(this).parent().find('.right_content_fields');
        $other_right_content_fields.slideUp(0);
        $(this).parent().find('.right_shortcut').removeClass('active');
        var $right_content_fields = $(this).find('.right_content_fields');
        if ($right_content_fields.is(":hidden")) {
            $(this).addClass('active');
            $right_content_fields.slideDown(300);
            var top_height = $(this).offset().top - $(window).scrollTop();
            var _width = $(this).parent().outerWidth(true) + 10;
            $right_content_fields.css({top: top_height + "px", 'margin-right':  _width + "px"});
        } else {
            $right_content_fields.slideUp(300);
        }
    });

    // icon-avatar 点击头像打开扩展字段
    $('.icon-avatar').mouseover(function (e) {
        var $span = $('.icon-avatar').find("span.icon");
        $('ul.extra-list').slideDown(300);
    })
    .mouseleave(function (e) {
        var $span = $('.icon-avatar').find("span.icon");
        if ($.contains($('ul.extra-list')[0], e.relatedTarget) || $('ul.extra-list')[0] == e.relatedTarget) {
            return;
        } else if($.contains($('ul.switch_default_role_ul')[0], e.relatedTarget) || $('ul.switch_default_role_ul')[0] == e.relatedTarget) {
            return;
        } else {
            $('ul.extra-list').slideUp(300);
        }
        // if ($.contains($('ul.extra-list')[0], e.target) || $('ul.extra-list')[0] == e.target) return
        // else $('ul.extra-list').slideUp(300)
    })

    $('ul.extra-list').mouseleave(function (e) {
        var $span = $('.icon-avatar').find("span.icon");
        if ($.contains($('.icon-avatar')[0], e.relatedTarget) || $('.icon-avatar')[0] == e.relatedTarget) {
            return;
        } else if($.contains($('ul.switch_default_role_ul')[0], e.relatedTarget) || $('ul.switch_default_role_ul')[0] == e.relatedTarget) {
            return;
        } else {
            $(this).slideUp(300);
        }
    })
    
});
