#!/usr/bin/env php

<?php

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$time = Date::time();
$status = EQ_Status_Model::IN_SERVICE;
$eq_ids = Config::get('equipment.check_late_equipments');
foreach($eq_ids as $eq_id ) {
    $equipment = O('equipment', $eq_id);
    if ($equipment->id) {
        $eq_reserv = Q("eq_reserv[equipment=$equipment][dtstart<$time][dtend>$time]:limit(1)")->current(); //一台仪器不存在重复预约
        if ($eq_reserv->id) {
            $late_time = $eq_reserv->dtstart + 30*60; // 时间为预约开始后的三十分钟之内
            $eq_records =  Q("eq_record[dtstart<$late_time][dtend=0|reserv={$eq_reserv}][equipment={$equipment}]");
            if (count($eq_records) == 0) {
                $edit_user_id = Config::get('equipment.default_eq_notification_user');
                $edit_user = O('user', $edit_user_id);
                $me = L('ME');
                Cache::L('ME', $edit_user);
                //有预约，但是三十分钟内没有使用记录
                $user = $eq_reserv->user; //预约者
                $user->reserv_late_times += 1;
                $user->save();
                $reserv_dtstart = $eq_reserv->dtstart;
                $reserv_dtend = $eq_reserv->dtend;
                $component = $eq_reserv->component;
                if (O('cal_component', $component->id)->delete() && $eq_reserv->delete()) { //删除成功 发送消息提醒给仪器联系人

                    Cache::L('ME', $me);
                    $contacts = Q("{$equipment} user.contact");
                    foreach ($contacts as $contact) {
                        Notification::send('eq_reserv.reserv_canceled_to_contact', $contact, [
                                                '%contact' => Markup::encode_Q($contact), 
                                                '%user' => Markup::encode_Q($user),
                                                '%equipment' => Markup::encode_Q($equipment),
                                                '%dtstart' => Date::format($reserv_dtstart),
                                                '%dtend' => Date::format($reserv_dtend),
                                           ]);
                    }
                    Notification::send('eq_reserv.reserv_canceled_to_user', $user, [
                                            '%user' => Markup::encode_Q($user),
                                            '%equipment' => Markup::encode_Q($equipment),
                                            '%dtstart' => Date::format($reserv_dtstart),
                                            '%dtend' => Date::format($reserv_dtend),
                                       ]);

                }
                $late_times_limit = Config::get('equipment.late_times_limit');
                if ($user->reserv_late_times >= $late_times_limit) {
                    $eq_banned = O('eq_banned', ['user'=>$user, 'equipment_id'=>0]);
                    if (!$eq_banned->id) {
                        $eq_banned->user = $user;
                        $eq_banned->ctime = $time;
                        if ($eq_banned->save()) {
                            Notification::send('eq_reserv.reserv_late_banned_to_user', $user, [
                                            '%user' => Markup::encode_Q($user),
                                            '%equipment' => Markup::encode_Q($equipment),
                                            '%times' => $user->reserv_late_times,
                                       ]);
                        }
                    }
                }
            }
        }
    }
}
?>
