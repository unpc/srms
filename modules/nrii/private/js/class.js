jQuery(function($) {
    var $class_lg_select = $('#class_lg select')
        , $class_md_select = $('#class_md select')
        , select_change = function ($select){
            var id = $select.val();
            var class_select = $select.selector;
            if (id == '-1') {
                return
            }

            Q.trigger({
                object: 'class',
                event: 'change',
                data: {
                    'id': id,
                    'class_select': class_select
                },
                url: trigger_url,
                success: function(data){
                    for (var key in data){
                        var selector = '#' + key + ' select'
                        $($(selector).data('dropdown_container')).remove()
                        $(selector).remove()
                        $('#' + key).prepend('<select name="' + key +'" class="dropdown" size="30" style="display: none;"></select>');
                        if (key == 'class_md'){
                            $(selector).append('<option value="-1" selected="selected">-请选择中类-</option>');
                        }else{
                            $(selector).append('<option value="-1" selected="selected">-请选择小类-</option>');
                        }
                        for (var k in data[key]){
                            $(selector).append("<option value='" + k + "'>" + data[key][k] + "</option>");
                        }
                        if (key == 'class_md'){
                            $('#class_md select').change(function() {select_change($('#class_md select'))});
                        }
                    }
                }
            })          
        }
    
    $class_lg_select.change(function() {select_change($class_lg_select)});
    $('#class_md select').change(function() {select_change($('#class_md select'))});
});
