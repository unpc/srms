jQuery(function($) {
    var $cus_fieldset = $('#customs'),
        $cus_switch = $('#cus_switch select')
    if ($cus_switch.val() == 0) {
        $cus_fieldset.prev().hide()
        $cus_fieldset.hide()
    }
    
    $cus_switch.change(function(){
        if ($cus_switch.val() == 0){
            $cus_fieldset.prev().hide()
            $cus_fieldset.hide()
        }else{
            $cus_fieldset.prev().show()
            $cus_fieldset.show()
        }
        
    })
});
