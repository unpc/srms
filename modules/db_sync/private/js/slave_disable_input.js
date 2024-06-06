(function($) {
    $(function() {
        $(":input").prop('disabled', true);
        $('form .button').hide() // 隐藏相关按钮
        $('.remove_button').bind('click', function () {
            e.preventDefault();
            return false;
        });
        // $('.button:not(.button_redirect_master)').hide();
    })
})(jQuery);