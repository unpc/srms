<?php

class CLI_UseNotice
{
    public static function usenotice_message_realtime_check()
    {
        $me = L('ME');
        $in_service = EQ_Status_Model::IN_SERVICE;
        $equipments = Q("equipment[status={$in_service}]");

        $notifications = [
            'notification.equipments.not_use_notice',
            'notification.equipments.use_more_notice',
            'notification.equipments.use_less_notice',
        ];

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

            // 仪器未设置使用预警
            if (!count($notif_keys)) {
                continue;
            }

            // 仪器没有负责人
            $incharges = Q("{$equipment} user.incharge");
            if (!$incharges->total_count()) {
                continue;
            }

            foreach ($notif_keys as $notif_key) {
                $info_key = end(explode('.', $notif_key));

                if ($info_key == 'not_use_notice') {
                    $format = Date::format_interval($equipment->equipment_not_use_notice_mins, $equipment->equipment_not_use_notice_format);
                } elseif ($info_key == 'use_more_notice') {
                    $format = Date::format_interval($equipment->equipment_use_time_more_mins, $equipment->equipment_use_time_more_format);
                } elseif ($info_key == 'use_less_notice') {
                    $format = Date::format_interval($equipment->equipment_use_time_less_mins, $equipment->equipment_use_time_less_format);
                }

                $default_info = (array) Lab::get($notif_key) + (array) Config::get($notif_key);
                $info = $equipment->$info_key;

                // 设置了接收人 默认设置发送给全部机主
                $receivers = (array) $info['receive_by'];
                if (!count($receivers)) {
                    $receivers = $incharges;
                } else {
                    $res = [];
                    foreach ($receivers as $incharge_id => $v) {
                        $res[] = O('user', $incharge_id);
                    }
                    $receivers = $res;
                }

                $info = array_merge($default_info, (array) $info);
                $handlers = $info['send_by'];

                // 允许消息发送
                if ($handlers['messages'][1]) {
                    $record_user_already_send = false;
                    foreach ($receivers as $receiver) {
                        // 检测是否满足发送条件
                        $return_value = Equipments::check_usenotice($equipment, $receiver, $info_key, 'messages');
                        if (!$return_value) {
                            continue;
                        }
                        $return_value = (int) $return_value === 1 ? 0 : $return_value;

                        Notification::send("equipments.{$info_key}|{$equipment->id}", $receiver, [
                            '%incharge' => Markup::encode_Q($receiver),
                            '%equipment' => Markup::encode_Q($equipment),
                            '%time' => $format[0] . Date::units($format[1])[$format[1]],
                        ]);

                        $notice_record = O('usenotice_record');
                        $notice_record->equipment = $equipment;
                        $notice_record->receiver = $receiver;
                        $notice_record->notif_key = $notif_key;
                        $notice_record->short_key = $info_key;
                        $notice_record->type = 'messages';
                        $notice_record->record = O('eq_record', (int) $return_value);
                        $notice_record->ctime = Date::time();
                        $notice_record->save();

                        // 同时给使用者发送消息
                        if ($return_value && !$record_user_already_send) {
                            $record = O('eq_record', (int) $return_value);
                            Notification::send("equipments.{$info_key}_to_user", $record->user, [
                                '%user' => Markup::encode_Q($record->user),
                                '%equipment' => Markup::encode_Q($equipment),
                                '%time' => $format[0] . Date::units($format[1])[$format[1]],
                            ]);
                            $record_user_already_send = true;
                        }
                    }
                } else {
                    // 机主设置不接收相关消息，但是也得给使用者发送相关消息
                    if (in_array($info_key, ['use_more_notice', 'use_less_notice'])) {
                        if ($info_key == 'use_more_notice') {
                            $records = Q("eq_record[equipment={$equipment}][dtend=0]:sort(dtstart D)");
                        } else {
                            $records = Q("eq_record[equipment={$equipment}]:sort(dtend D)");
                        }
                        $record = $records->current();
                        if ($record->id && $record->user->id) {
                            $receiver = $record->user;
                            // 检测是否满足发送条件
                            $return_value = Equipments::check_usenotice($equipment, $receiver, $info_key, 'messages');
                            if (!$return_value) {
                                continue;
                            }
                            $return_value = (int) $return_value === 1 ? 0 : $return_value;

                            if ($return_value) {
                                $record = O('eq_record', (int) $return_value);
                                Notification::send("equipments.{$info_key}_to_user", $record->user, [
                                    '%user' => Markup::encode_Q($record->user),
                                    '%equipment' => Markup::encode_Q($equipment),
                                    '%time' => $format[0] . Date::units($format[1])[$format[1]],
                                ]);

                                $notice_record = O('usenotice_record');
                                $notice_record->equipment = $equipment;
                                $notice_record->receiver = $receiver;
                                $notice_record->notif_key = $notif_key;
                                $notice_record->short_key = $info_key;
                                $notice_record->type = 'messages';
                                $notice_record->record = O('eq_record', (int) $return_value);
                                $notice_record->ctime = Date::time();
                                $notice_record->save();
                            }
                        }
                    }
                }
            }
        }
    }
}
