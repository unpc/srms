jQuery(function($) {
    var $equipment_input = $('#equipment input:first')
        , input_change = function ($input) {
            var id = $input.val()
            if (id == '0') {
                return
            }
            Q.trigger({
                object: 'equipment',
                event: 'change',
                data: {
                    'id': id,
                },
                url: trigger_url,
                success: function(data){
                    for (var key in data){
                        for (var k in data[key]){
                            if (k == 'beginDateFormate'){
                                $('input[name="beginDate"]').prev().val(data[key][k])
                                $('input[name="beginDate"]').val(data[key]['beginDate'])
                                continue
                            }else if (k == 'technical' || k == 'function') {
                                var selector = 'textarea[name="' + k + '"]'
                                $(selector).val($(selector).val() ? $(selector).val() : data[key][k])
                                continue
                            }else if (k == 'eq_source') {
                                var selector = 'select[name="' + k + '"]'
                                $(selector).find("option").attr("selected",false);
                                $(selector).find("option[value="+data[key][k]+"]").attr("selected",true);
                                $('.eq_source_text > div').text($(selector).find("option[value="+data[key][k]+"]").html());
                                continue
                            }
                            var selector = 'input[name="' + k + '"]'
                            $(selector).val($(selector).val() ? $(selector).val() : data[key][k])
                        }
                    }
                }
            })
        }
    
    $equipment_input.change(function() {input_change($equipment_input)})

    input_change($equipment_input)
});
