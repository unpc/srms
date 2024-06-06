$(function() {
	$form = $('form');
	$unit = $form.find('input[name=unit]');
	$tr   = $unit.closest('tr');
	$sensor_unit = $form.find('span.sensor_unit');
	$unit.bind('keyup', function() {
		$sensor_unit.text($(this).val());
	});

	$('#common_unit').click(function(e){
		if( $('.common_units').size() ) {
			$('.common_units').parent('tr:eq(0)').remove();
			return false;
		}

		Q.trigger({
			object: 'common_unit',
			event:  'click',
			url: url,
			global: false,
			success:function(data) {
				$tr.after(data.common_unit);
			}
		});
		
		e.preventDefault();
		return false;
	});

	$('table').on(
		'click','.common_units span',
		function(){
			$a = $(this);
			$sensor_unit.text( $a.html() );
			$unit.val( $a.html() );
			$a.closest('tr').remove();
		}
	);

});
