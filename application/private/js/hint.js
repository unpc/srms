jQuery(function($){

	$(':input[class*="hint:"], :input[q-hint]').livequery(function(){

		var $el=$(this);
		var hint = $el.classAttr("hint") || $el.attr('hint') || '';

        var changed = false;    //默认未修改

		$el
		.blur()
		.focus(function() {
            if (!changed) {     //如果未修改,清空
                $el.val('');
                $el.removeClass('hint');
            }

		})
		.blur(function() {
            if ($el.val() == '' ) { //失去焦点，如果清空内容，则显示hint
                $el.val(hint);
                $el.addClass('hint');
                changed = false;
            }

        })
		.change(function() {
			changed = true;
		})
        .keyup(function() {
            changed = true; //只要发生按键，说明change了
        });

        if (!$el.val()) {
            $el.val(hint).addClass('hint');
        }
        else {
            changed = true; //说明默认有value，则changed为true
        }
		
        $(':submit, :image', this.form).click(function() {
            if($el.hasClass('hint') && $el.val()==hint) $el.val('').removeClass('hint');
        });

		$(this.form).submit(function() {
            if (!changed) { //未修改，则val为空
                $el.val('').removeClass('hint');
            }
		});
	});
	
});
