<?php
class Usenotice_Ajax_Controller extends AJAX_Controller
{
    public function index_usenotice_check()
    {
        $me = L('ME');
        $params['me'] = $me;
        $equipments = Q("{$me}<incharge equipment");
        if (!$equipments->total_count()) {
            return;
        }

        $notifications = [
            'notification.equipments.not_use_notice',
            'notification.equipments.use_more_notice',
            'notification.equipments.use_less_notice',
        ];

        $message['not_use_notice'] = '仪器%equipment未使用时间过长.';
        $message['use_more_notice'] = '仪器%equipment使用时间过长.';
        $message['use_less_notice'] = '仪器%equipment使用时间过短.';

        $now = Date::time();
        $day_start = Date::get_day_start($now);
        $day_end = Date::get_day_end($now);

        $eqs = [];
        foreach ($equipments as $equipment) {
            $notif_keys = $notifications;
            if (!$equipment->allow_equipment_not_use_notice) {
                unset($notif_keys[0]);
            }

            if (!$equipment->allow_equipment_use_time_more) {
                unset($notif_keys[1]);
            }

            if (!$equipment->allow_equipment_use_time_less) {
                unset($notif_keys[2]);
            }

            if (!count($notif_keys)) {
                continue;
            }

            foreach ($notif_keys as $notif_key) {
                $info_key = end(explode('.', $notif_key));

                $default_info = (array) Lab::get($notif_key) + (array) Config::get($notif_key);
                $info = $equipment->$info_key;

                // 默认设置 给所有人发送 机主保存 则去接收人中匹配
                $receives = (array) $info['receive_by'];
                if (!in_array($me->id, array_keys($receives)) && $info) {
                    continue;
                }

                $info = array_merge($default_info, (array) $info);

                $handlers = $info['send_by'];

                // 允许系统弹框
                if ($handlers['system'][1]) {
                    // 未满足条件
                    $return_value = Equipments::check_usenotice($equipment, $me, $info_key, 'system');
                    if (!$return_value) {
                        continue;
                    }

                    $return_value = (int) $return_value === 1 ? 0 : $return_value;
                    $usenotice_records = Q("usenotice_record[receiver={$me}][equipment={$equipment}][type=system][short_key={$info_key}][record={$return_value}][ctime={$day_start}~{$day_end}]");
                    if (!$usenotice_records->total_count()) {
                        $notice_record = O('usenotice_record');
                        $notice_record->equipment = $equipment;
                        $notice_record->receiver = $me;
                        $notice_record->notif_key = $notif_key;
                        $notice_record->short_key = $info_key;
                        $notice_record->type = 'system';
                        $notice_record->record = O('eq_record', (int) $return_value);
                        $notice_record->ctime = Date::time();
                        if ($notice_record->save()) {
                            $usenotice_ids[] = $notice_record->id;
                        }
                    } else {
                        $usenotice_ids[] = $usenotice_records->current()->id;
                    }

                    $eqs[$info_key][] = Markup::encode_Q($equipment);
                }
            }

        }

        foreach ($eqs as $info_key => $v) {
            $params['message'][$info_key] = (string) new Markup(strtr($message[$info_key], ['%equipment' => implode(' ', $v)]));
        }

        if (count((array) $params['message'])) {
            $_SESSION['unread_system_usenotice'] = array_unique(array_merge((array) $_SESSION['unread_system_usenotice'], $usenotice_ids));
            JS::run(JS::smart()->jQuery->propbox((string) V('equipments:equipment/prop_box/usenotice', $params), 250, 500, 'right_bottom'));
        }
    }

    public function index_usenotice_read()
    {
        if (count((array) $_SESSION['unread_system_usenotice'])) {
            foreach ((array) $_SESSION['unread_system_usenotice'] as $id) {
                $usenotice_record = O('usenotice_record', $id);
                $usenotice_record->is_read = 1;
                $usenotice_record->save();
            }
        }
        $_SESSION['unread_system_usenotice'] = [];
        JS::run(JS::smart()->jQuery->propClose());
    }
}
