(function($){
    var collapse = $('[data-toggle="collapse"]')
    collapse.bind('click', function(){
        var collapse_id = $(this).attr('data-static')
        $('.panel-collapse').hide()
        $('#collapse'+collapse_id).toggle()
    })
})(jQuery);