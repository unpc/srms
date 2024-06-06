jQuery(function($) {
	var $status_select = $('#' + sample_status_id);
	var $form = $status_select.parents('form:eq(0)');
    var $add_tr = $status_select.closest('tr');
	var status = $status_select.find(':selected:eq(0)').val();
	var ajax_show_charge_input = function(status) {
		Q.trigger({
			object: 'status_select',
			event: 'change',
			data: {
				'e_id': (typeof(equipment_id) != 'undefined') ? equipment_id : '',
				'id': sample_id,
				'status': status,
				'can_charge': can_charge,
        'sender_id': $("input[name='sender'][type='hidden']").val(),
			},
			url: trigger_url,
			success: function(data) {
				var $charge_input = $form.find('.charge_input');
				$charge_input.remove();

                $charge_input = $(data.charge_input);
				$add_tr.after($charge_input);
			}
		});
	};

	ajax_show_charge_input(status);

	$status_select.bind(
		'change',
		function(e) {
			// 影响的input有:
			//   取样时间
			//   计费标签
			//   收费金额
			//   计算按钮

			// 申请中 - 全都不可编辑，remove上述input
			// 已批准、已拒绝、因故取消 - 全可编辑
			status = $(this).find(':selected:eq(0)').val();
			ajax_show_charge_input(status);
		}
	);
});
