(function($) {
    $(function() {
        $(".view").unbind('click').click(function(e) {
            $(this).removeClass('view');
            alert('本站点相关数据从对应的主站点同步，该选项无法编辑');
            $(this).off('click');
            return false;
        });
    })
})(jQuery);