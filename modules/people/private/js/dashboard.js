/**
 * 个人主页 JS组件
 *
 * @author: Clh  lianhui.cao@geneegroup.com
 * @time: 2019-12-16 17:00:00
 */

jQuery(function($){

    /**
     * LAB Toggle
     */
    $lab_list = $('#lab_list');
    $('.my_lab').click(function() {
        $lab_list.slideToggle(300);
    })

    $('ul#lab_list li').click(function (){
        $this = $(this);
        $lab_list.slideUp(300);

        var lab_id = $this.attr('lab_id');
        $('.my_lab .lab_title').text($this.text());
        $('.department_list').hide();
        $('.department_list[lab_id="' + lab_id + '"]').show();
    })

    /**
     * Department slideToggle
     */
    $('.slideDepartment').click(function(e) {
        var $this = $(this);
        if ($this.hasClass('disabled')) return false;
        var $department_list = $this.parents('.department_list');
        var $current = $department_list.children('.active');
        var $next;
        var $slideMode;
         
        // console.info($department_list.children('li').last() == $current);

        if ($this.hasClass('slideLeft')) {
            $next = $current.prev();
            $slideMode = 'toRight';
        } else if ($this.hasClass('slideRight')) {
            $next = $current.next();
            $slideMode = 'toLeft';
        } else {
            return false;
        }

        if ($next.length && !$next.hasClass('slideDepartment')) {
            if ($slideMode == 'toRight') {
                $current.css({"position": "absolute"});
                $next.css({"position": "absolute", "left": "-100%", "display": "block", "top": "0"});
                $current.animate({"right": "-100%"}, 500, 'swing', function(){
                    $(this).removeClass('active').addClass('hidden').removeAttr('style');
                });
                $next.animate({"left": "0"}, 500, 'swing', function(){
                    $(this).removeClass('hidden').addClass('active').removeAttr('style');
                });

                $department_list.children('.slideRight').removeClass('disabled');
                if (!$next.prev().get(0) || $next.prev().get(0).tagName != 'LI') $this.addClass('disabled');
            } else {
                $current.css({"position": "absolute"});
                $next.css({"position": "absolute", "right": "-100%", "display": "block", "top": "0"});
                $current.animate({"left": "-100%"}, 500, 'swing', function(){
                    $(this).removeClass('active').addClass('hidden').removeAttr('style');
                });
                $next.animate({"right": "0"}, 500, 'swing', function(){
                    $(this).removeClass('hidden').addClass('active').removeAttr('style');
                });

                $department_list.children('.slideLeft').removeClass('disabled');
                if ($next.next().get(0).tagName != 'LI') $this.addClass('disabled');
            }
        } else {
            return false;
        }
    })

});
