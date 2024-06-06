(function($) {
    $(function() {
        $(".button").unbind('click').click(function(e) {
            if ($(this).hasClass('button_redirect_master')) return true; // 友军
            // $(this).removeClass('button');
            alert('本站点相关数据从对应的主站点同步，该选项无法编辑');
            $(this).off('click');
            return false;
            });
        })
})(jQuery);