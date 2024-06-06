/* 
   AJAX加载eq_sample自定义计费脚本
   (xiaopei.li@2011.07.20) 
*/
jQuery(function($) {
	var $accept_sample = $('input[name="accept_sample"]');
	var $accept_div = $('#'+uniqid);
	var ajax_show_charge_form = function() {
		Q.trigger({
			object: 'show_charge_form',
			event: 'click',
			data: {
				'id': eq_id,
				'form': form
			},
			url: trigger_url,
			success: function(data) {
				$accept_div.after($(data.charge_form));
			}
		});
	};

	var hide_charge_form = function() {
		$('#sample_charge').remove();
	};

	if ($accept_sample.attr('checked')) {
		ajax_show_charge_form();
	}
	else {					// should default hide
		hide_charge_form();
	}

	$accept_sample.change(
		function(){
			if (this.checked) { // two ways to examine if checked... not well
				ajax_show_charge_form();
			}
			else {
				hide_charge_form();
			}
		});
});
