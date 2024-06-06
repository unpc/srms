jQuery(function($){
    var $approve_selected = $('.approve_selected');

    $approve_selected.bind('click',function(){
        var self = $(this);
        var $selects = $('input[name="select\[\]"]');
        var selected_ids = [];
        var num = 0;
        $.each($selects, function(k,v){
            if ($selects.eq(k).is(':checked')) {
                selected_ids[num] = $(v).val();
                num ++;
            }
        });
        if (selected_ids.length > 0) {
            Q.trigger({
                object : 'approve_selected',
                event : 'click',
                data : {
                    selected_ids : selected_ids,
                    form_token : form_token,
                    type : self.data('type')
                }
            });
        }
        else {
            alert(no_checked);
        }
        return false;
    });
});
