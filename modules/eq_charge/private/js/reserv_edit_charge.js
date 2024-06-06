/*
  修改 eq_reserv 的计费时的一些 js
*/
jQuery(function($) {
	var $reserv_recharge_button = $('.reserv_button_calculate:eq(0)');
	var $form = $reserv_recharge_button.closest('form');
	var $reserv_amount = $form.find('[name="reserv_amount"]');

	$reserv_recharge_button.bind(
		'click',
		function(e) {
			e.preventDefault();
			Q.trigger({
				object: 'reserv_button_recharge',
				event: 'click',
				data: {
					'reserv_form': $form.formSerialize()
				},
				url: trigger_url,
				success: function(data) {
					$reserv_amount.val(data.auto_amount).change();
				}
			});
		}
	);

	$check_input = $('input.custom_charge');
	$check_input.change(function(){
		$id = $(this).attr('id');
		if($(this).attr('checked')) {
			$('.' + $id).removeAttr('disabled');
		}
		else{
			$('.' + $id).attr('disabled','disabled');
		}
	});
});
