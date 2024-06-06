(function($) {

	Number.prototype.Fixed = function(n) {
		var s = this.toFixed(n+1) + ''
			, str = s.substring(0, s.indexOf(".") + n + 1)
		return str
	};

	$('input.number').livequery(function(){
		
		var $input = $(this);
		
		$input
		.change(function(){
			var val = parseFloat(this.value);

			//当设置了默认值时, 用户填写了不合法的number数据, 使用默认值, 否则使用0
			var default_val = $input.attr('q-number_default_value');
			$input.val( !isNaN(val) ? val : ( default_val != undefined ? default_val : '0') );
		})
		.change();
	
	});
	
	
	$(':visible > input.currency').livequery(function(){
	
		var $input = $(this);
		var prefix = $input.attr('sign') || '';
		var $hidden = $('<input type="hidden" />');

		$hidden.attr('name', $input.attr('name'));
		$input.removeAttr('name').after($hidden);
		
		$hidden.bind('change.currency', function(){
			var val = parseFloat(this.value);
			$input.val(prefix + (val ? val.Fixed(2) :'0.00'));
			$input.attr('defaultValue', $input.val());
		});
	
		$input
		.bind('focus.currency', function () {
            //对于readonly的input，不予focus
            if ($(this).hasClass('readonly'))  return;
			var val = parseFloat( $input.val().replace(prefix, ''));
			$input.val(val);
			setTimeout(function(){
				$input.select();
			},0);
			$hidden.data('old_value', $hidden.val());
			return false;
		})
		.bind('change.currency', function (){
			var val = parseFloat(this.value.replace(prefix, '')) || 0;
			$hidden.val(val).change();
		})
		.bind('blur.currency', function() {
			$input.change();
			$hidden.trigger('blur');
		})
		.change();
		
		$hidden.data('old_value', $hidden.val());
		
	}, function(){
		var $input = $(this);
		var $hidden = $(this).next('input[type=hidden]');
		$input.attr('name', $hidden.attr('name'));
		$hidden.remove();
		$input.unbind('focus.currency change.currency blur.currency');
	});


})(jQuery);
