(function($){
    //如果为ie6 7
    //icon的base64进行修正
    if (Q.browser.msie && Q.browser.version < 8) {
        $('img[src*=base64]').livequery(function() {
            //decode_url由php进行设定
            $(this).attr('src', decode_url + "?code=" + encodeURIComponent($(this).attr('src')));
        });
    }
})(jQuery);
