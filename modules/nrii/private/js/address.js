jQuery(function($) {
    var $province_select = $('#province select')
        , $city_select = $('#city select')
        , select_change = function ($select, mode){
            var adcode = $select.val();
            if (adcode == '-1') {
                return
            }
            Q.trigger({
                object: 'address',
                event: 'change',
                data: {
                    'adcode': adcode,
                    'mode': mode
                },
                url: trigger_url,
                success: function(data){
                    for (var key in data){
                        var selector = '#' + key + ' select'
                        $($(selector).data('dropdown_container')).remove()
                        $(selector).remove()
                        $('#' + key).prepend('<select name="' + key +'" class="dropdown" size="30" style="display: none;"></select>');
                        if (key == 'city'){
                            $(selector).append('<option value="-1" selected="selected">- 请选择市-</option>');
                        }else{
                            $(selector).append('<option value="-1" selected="selected">- 请选择区-</option>');
                        }
                        for (var k in data[key]){
                            $(selector).append("<option value='" + k + "'>" + data[key][k] + "</option>");
                        }
                        if (key == 'city'){
                            $('#city select').change(function() {select_change($('#city select'))});
                        }
                    }
                }
            })          
        }
    
    $province_select.change(function() {select_change($province_select, 'city')});
    $('#city select').change(function() {select_change($('#city select'), 'area')});
});
