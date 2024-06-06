<?php

class Notification_AJAX_Controller extends AJAX_Controller
{
    public function index_read_dialog_submit()
    {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $cache = Cache::factory('redis');
        $notifications = $cache->get('eq_warning_modal_'.$me->id);
        $equipment_id = $form['equipment_id'] ?? 0;
        $md5 = $form['md5'] ?? '';
        $key = 'eq_warning_modal_'.$me->id;
        if(isset($notifications[$equipment_id])){
            unset($notifications[$equipment_id][$md5]);
            $cache->set('eq_warning_modal_'.$me->id, $notifications, 30 * 86400);//一直弹框直到确认
        }
        JS::refresh();
    }
}
