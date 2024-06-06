jQuery(function($) {
	$('a.extra_links').livequery('click',function(){
		var extra_id = $(this).attr('id');
		$dropdown = $('.' + extra_id);

		var width = $(this).outerWidth();
		var height = $(this).outerHeight(); 
	
		var offset = $(this).position();


        var dropdown_left = offset.left + width - $dropdown.outerWidth();
        var dropdown_top = offset.top + height;
        
        $dropdown.css({
            left: dropdown_left,
            top: dropdown_top
        });

        $dropdown.toggle();

        //点击后把其他的dropdown隐藏
        var $others = $('.sample_dropdown:visible');
        $.each($others, function(k,v){
			if(!$(v).hasClass(extra_id)){
				$(v).hide();
			}
		});

        $dropdown.find('a').click(function(){
        	$dropdown.hide();
        });
        
        return false;
	});

	$('body,a').click(function(e){
		if(!$(e.target).hasClass('extra_links')){
			$('.sample_dropdown:visible').hide();
		}
	});
});
