/*
form_id: 容器的ID
*/
jQuery(function($){
	$('a[name=calculate]').click(function(){
		var selector = ['#', form_id].join('');
		var $form = $(selector);
		var $unit_price = $('input[name=unit_price]', $form);
		var $quantity = $('input[name=quantity]', $form);
		var $price = $('input[name=price]', $form);	
		var val = parseFloat($unit_price.val()) * parseInt($quantity.val(), 10);			
		$price.val(val).change();
	});

});

