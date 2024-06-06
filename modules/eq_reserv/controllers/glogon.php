<?php

class Glogon_Controller extends Layout_Controller {

    //登录 view 页面
    public function arrival() {
        $id = Input::form('id');
        $reserv = O('eq_reserv', $id);
        $user = $reserv->user;
        $equipment = $reserv->equipment;
        Config::set('system.locale', $equipment->device['lang']);

		I18N::shutdown();
        I18N::setup();
        
        $this->layout = V('eq_reserv:glogon/arrival', [
            'equipment' => $equipment,
            'reserv' => $reserv
        ]);
    }
    
}
