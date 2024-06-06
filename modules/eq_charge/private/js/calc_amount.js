jQuery(function($) {
	var $calc_link = $('#' + button_id);
	var $form = $calc_link.parents('form:eq(0)');
	$calc_link.bind(
		'click',
		function(e) {
			var count = $('input[name="count"]', $form).first().val();
			var tags = [];
			var $checkboxes = $('input:checkbox[name^="sample_charge_tags"][name$="[checked]"]:checked', $form);
			
			$checkboxes.each(function(k, v) {
				var element = {};
				
				var $v = $(v);
				var value_name = $v.attr('name').replace('[checked]', '[value]');
				var value = $('input[name="' + value_name + '"]', $form).val();
				var label = value_name.match(/\[([^\]]*)\]/);
				
				element.key = label[1];
				element.value = value;
				tags.push(element);
			});
            Q.trigger({
                object: 'calc_sample_fee',
                event: 'click',
                data: {
                    'e_id': (typeof(equipment_id) != 'undefined') ? equipment_id : '',
                    'id' : sample_id,
                    'tags' : tags,
                    'count' : count,
                    'sample_form' : $form.formSerialize()
                },
                url: trigger_url,
                success: function(data) {
                	var data = data || [];
                    var fee = data.fee || 0;
                    var $amount_input = $('input[name=sample_amount]', $form);
                    $amount_input.val(fee).change();
                    tags = null;
                }
            });	
		});

	$check_input = $('input.custom_charge');
	$check_input.change(function(){
        $id = $(this).attr('id');
		if($(this).prop('checked')) {
			$('.' + $id).prop('disabled', false);
		}
		else{
			$('.' + $id).prop('disabled', true);
		}
	});
});
