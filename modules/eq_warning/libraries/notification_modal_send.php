<?php
class Notification_Modal_Send
{
    public static function layout_after_call($e, $controller)
    {
        if (!Auth::logged_in()) {
            return;
        }
        
        $controller->add_js('eq_warning:bind_check_notification_to_heartbeat', false);
    }

    public static function modal_show() {
        $me = L('ME');
        $cache = Cache::factory('redis');
        $notifications = $cache->get('eq_warning_modal_'.$me->id) ? : [];
        
        if (count($notifications)) {
            foreach($notifications as $eqid => $all_params){
                foreach($all_params as $md5 => $params){
                    JS::run(JS::smart()->jQuery->propbox((string)V('eq_warning:dialog/warning', [
                        'params' => $params,
                        'equipment_id' => $eqid,
                        'md5' => $md5,
                    ]), 180, 300, 'right_bottom'));
                }
                $cache->set('eq_warning_modal_'.$me->id, $notifications, 30 * 86400);//一直弹框直到确认
            }
        }
    }

    // public static function login_show_modal($e,$me,$form) {

    //     $cache = Cache::factory('redis');
    //     $notifications = $cache->get('eq_warning_modal_'.$me->id) ? : [];
        
    //     if (count($notifications)) {
    //         foreach($notifications as $eqid => $params){

    //             JS::run(JS::smart()->jQuery->propbox((string)V('eq_warning:dialog/warning', [
    //                 'params' => $params,
    //                 'equipment_id' => $eqid
    //             ]), 180, 300, 'right_bottom'));
    
    //             $cache->set('eq_warning_modal_'.$me->id, $notifications, 30 * 86400);//一直弹框直到确认
    //         }
    //     }
    // }
   
}
