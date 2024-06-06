/*
  修改 eq_record 的计费时的一些 js
*/
jQuery(function($) {
	
	var $record_recharge_button = $('.record_button_calculate:eq(0)');
	var $form = $record_recharge_button.closest('form');
	var $record_amount = $form.find('[name="record_amount"]');

	$record_recharge_button.bind(
		'click',
		function(e) {
			e.preventDefault();
			Q.trigger({
				object: 'button_recharge',
				event: 'click',
				data: {
					'record_form': $form.formSerialize()
				},
				url: trigger_url + record_id,
				success: function(data) {
					$record_amount.val(data.auto_amount).change();
				}
			});
		}
	);

	var $reserv_recharge_button = $('.reserv_button_calculate:eq(0)');
	if(!$form.length) {
		var $form = $reserv_recharge_button.closest('form');
	}
	var $reserv_amount = $form.find('[name="reserv_amount"]');

	$reserv_recharge_button.bind(
		'click',
		function(e) {
			e.preventDefault();
			Q.trigger({
				object: 'reserv_button_recharge',
				event: 'click',
				data: {
					'record_form': $form.formSerialize(),
				},
				url: trigger_url + reserv_id,
				success: function(data) {
					$reserv_amount.val(data.auto_amount).change();
				}
			});
		}
	);

	$check_input = $('input.custom_charge');
	$check_input.change(function(){
		$id = $(this).attr('id');
		if($(this).is(':checked')) {
			$('.' + $id).removeAttr('disabled');
		}
		else{
			$('.' + $id).attr('disabled','disabled');
		}
	});
});
