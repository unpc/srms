<?php
class Eq_Reserv_Notification {
    static function cal_component_saved($e, $component, $old_data, $new_data) {
        /*暂时只能新添加的预约进行消息提醒功能，修正的时候不予处理*/
        $equipment = $component->calendar->parent;
        if ($equipment->accept_reserv) {
            $user = $component->organizer;
            $dtstart = $component->dtstart;
            $dtend = $component->dtend;
            $edit_time = Date::format(time());
            $contacts = Q("{$equipment} user.contact");
            $description = $component->description ?: I18N::T('eq_reserv', '无');
            $link = $equipment->url('reserv', ['st' => $dtstart ]);
            $edit_user = L('ME');
            $users = [];
            $marks = [];

            if ($GLOBALS['preload']['people.multi_lab']) {
                $form = L('COMPONENT_FORM');
                $lab_owner = O('lab_project', $form['project'])->lab->owner;
            }
            else {
                $lab_owner = Q("$user lab")->current()->owner;
            }

            //添加操作时发送的消息提醒
            if(!$old_data['id'] && $new_data['id']){
                $can_send = TRUE;
                $cal_rrule_id = $component->cal_rrule->id;
                if ($cal_rrule_id) {
                    $rrule_key = 'add_cal_can_send_'.$cal_rrule_id;
                    if (L($rrule_key)) {
                        $can_send = FALSE;
                    }
                    else {
                        Cache::L($rrule_key, TRUE);
                    }
                }
                if ($can_send) {
                    //发送给预约用户的消息提醒
                    Notification::send('eq_reserv.user_confirm_reserv', $user, [
                        '%user' => Markup::encode_Q($user),
                        '%equipment' => Markup::encode_Q($equipment),
                        '%dtstart' => Date::format($dtstart),
                        '%dtend' => Date::format($dtend),
                        '%description' => H($description),
                        '%link' => $link
                    ]);

                    //发送给仪器联系人的消息提醒
                    foreach ($contacts as $contact) {
                        Notification::send('eq_reserv.contact_confirm_reserv', $contact, [
                            '%contact' => Markup::encode_Q($contact),
                            '%user' => Markup::encode_Q($user),
                            '%equipment' => Markup::encode_Q($equipment),
                            '%dtstart' => Date::format($dtstart),
                            '%dtend' => Date::format($dtend),
                            '%user_phone' => H($user->phone),
                            '%user_email' => H($user->email),
                            '%description' => H($description),
                            '%link' => $link
                        ]);

                    }

                    if($lab_owner->id){
                        //发送给课题组pi的消息提醒
                        if(!Event::trigger('eq_reserv.add_reserv_notification_pi.custom',$component)){
                            Notification::send('eq_reserv.pi_confirm_reserv', $lab_owner, [
                                '%pi' => Markup::encode_Q($lab_owner),
                                '%user' => Markup::encode_Q($user),
                                '%equipment' => Markup::encode_Q($equipment),
                                '%dtstart' => Date::format($dtstart),
                                '%dtend' => Date::format($dtend),
                                '%user_phone' => H($user->phone),
                                '%user_email' => H($user->email),
                                '%description' => H($description),
                                '%link' => O('lab', ['owner' => $lab_owner])->url('eq_reserv')
                            ]);
                        }
                    }
                }
            }

            //修改操作时发送的消息提醒
            //如果修改了起始时间或结束时间或者预约类型则发送消息
            if ((!isset($old_data['id']) || !isset($new_data['id']))
                && ($old_data['dtstart'] != $new_data['dtstart']
                || $old_data['dtend'] != $new_data['dtend']
                || $old_data['type'] != $new_data['type']
                || $old_data['organizer']->id != $new_data['organizer']->id
                )) {

                $can_send = TRUE;
                $cal_rrule_id = $component->cal_rrule->id;
                if ($cal_rrule_id) {
                    $rrule_key = 'edit_cal_can_send_'.$cal_rrule_id;
                    if (L($rrule_key)) {
                        $can_send = FALSE;
                    }
                    else {
                        Cache::L($rrule_key, TRUE);
                    }
                }
                $edit_content = '';
                if($old_data['organizer']->id != $new_data['organizer']->id){
                    $old_organizer = O('user', $old_data['organizer']->id);
                    $old_organizer_name = Markup::encode_Q($old_organizer);
                    $new_organizer_name = Markup::encode_Q($component->organizer);
                    $edit_content .= "修改前预约者为 : {$old_organizer_name} , 修改后预约者为 : {$new_organizer_name}\n";
                    $organizer_changed = TRUE;
                }
                if(($old_data['dtstart'] != $new_data['dtstart']) || ($old_data['dtend'] != $new_data['dtend'])){
                    $old_dtstart = Date::format($old_data['dtstart']);
                    $old_dtend = Date::format($old_data['dtend']);
                    $new_dtstart = Date::format($dtstart);
                    $new_dtend = Date::format($dtend);
                    $edit_content .= "修改前预约时间 : {$old_dtstart}  - {$old_dtend} , 修改后预约时间 : {$new_dtstart}  - {$new_dtend}\n";

                }

                if ($old_data['type'] != $new_data['type'] && isset($new_data['type'])) {
                    $type_array =[
                            0 => '预约',
                            3 => '非预约',
                        ];
                    $old_type = $type_array[$old_data['type']];
                    $new_type = $type_array[$new_data['type']];
                    $edit_content .= "修改前预约类型为 : {$old_type} , 修改后预约类型为 : {$new_type}";
                }

                //相关人员的联系信息，如果是他人修改的，给出修改者的联系信息
                $edit_user_name = $edit_user->id ? Markup::encode_Q($edit_user) : I18N::T('eq_reserv', '系统');
                $other_content = "用户 {$edit_user_name} 的联系方式:\n电话: {$edit_user->phone}\nEmail: {$edit_user->email}";

                if ($component->edit_remark) {
                    $other_content .= "\n修改说明: {$component->edit_remark}";
                }

                if ($can_send) {
                    if($organizer_changed){
                        //预约者修改后发送给原预约者的消息
                        Notification::send('eq_reserv.user_confirm_edit_reserv', $old_organizer, [
                            '%user' => Markup::encode_Q($user),
                            '%time' => $edit_time,
                            '%edit_user' => $edit_user->id ? Markup::encode_Q($edit_user) : I18N::T('eq_reserv', '系统'),
                            '%edit_content' => $edit_content,
                            '%equipment' => Markup::encode_Q($equipment),
                            '%edit_content' => $edit_content,
                            '%description' => H($description),
                            '%link' => $link,
                            '%other_content' => $edit_user->id != $old_organizer->id ? $other_content : '',
                        ]);
                    }


                    //发送给预约用户的消息提醒
                    Notification::send('eq_reserv.user_confirm_edit_reserv', $user, [
                        '%user' => Markup::encode_Q($user),
                        '%time' => $edit_time,
                        '%edit_user' => $edit_user->id ? Markup::encode_Q($edit_user) : I18N::T('eq_reserv', '系统'),
                        '%edit_content' => $edit_content,
                        '%equipment' => Markup::encode_Q($equipment),
                        '%edit_content' => $edit_content,
                        '%description' => H($description),
                        '%link' => $link,
                        '%other_content' => $edit_user->id != $user->id ? $other_content : '',
                    ]);

                    //发送给预约仪器联系人的消息提醒
                    foreach ($contacts as $contact) {
                        Notification::send('eq_reserv.contact_confirm_edit_reserv', $contact, [
                            '%contact' => Markup::encode_Q($contact),
                            '%user' => Markup::encode_Q($user),
                            '%time' => $edit_time,
                            '%edit_user' => $edit_user->id ? Markup::encode_Q($edit_user) : I18N::T('eq_reserv', '系统'),
                            '%edit_content' => $edit_content,
                            '%equipment' => Markup::encode_Q($equipment),
                            '%description' => H($description),
                            '%user_phone' => H($user->phone),
                            '%user_email' => H($user->email),
                            '%link' => $equipment->url('reserv'),

                        ]);
                    }

                    //发送给课题组pi的消息提醒
                    Notification::send('eq_reserv.pi_confirm_edit_reserv', $lab_owner, [
                        '%pi' => Markup::encode_Q($lab_owner),
                        '%user' => Markup::encode_Q($user),
                        '%time' => $edit_time,
                        '%edit_user' => $edit_user->id ? Markup::encode_Q($edit_user) : I18N::T('eq_reserv', '系统'),
                        '%edit_content' => $edit_content,
                        '%equipment' => Markup::encode_Q($equipment),
                        '%user_phone' => H($user->phone),
                        '%user_email' => H($user->email),
                        '%description' => H($description),
                        '%link' => O('lab', ['owner' => $lab_owner])->url('eq_reserv')
                    ]);
                }
            }
        }
    }

    static function on_status_saved($e, $status, $old_data, $new_data) {

        $equipment = $status->equipment;
        if ($equipment->accept_reserv) {
            $contact_ids = Q("$equipment user.contact")->to_assoc('id', 'id');
            $dtstart = $status->dtstart;
            $dtend = $status->dtend;
            $now = time();
            if ($new_data['dtend']) {
                $dtstart = max($dtstart, $now);
                $users = [];
                $marks = [];
                foreach (Q("eq_reserv[equipment=$equipment][dtstart>=$dtstart]") as $r) {
                    if (in_array($r->user->id, $contact_ids)) continue;
                    $users[$r->user->token] = $r->user;
                }

                foreach ($users as $user) {
                    Notification::send('eq_reserv.in_service', $user, [
                            '%user' => Markup::encode_Q($user),
                            '%equipment' => Markup::encode_Q($equipment),
                    ]);
                }
            }
            elseif ($new_data['status'] == EQ_Status_Model::OUT_OF_SERVICE) {
                $dtend = max($dtend, $now);
                $users = [];
                $marks = [];
                //获取仪器的所有预约的预约者（非仪器联系人）
                foreach (Q("eq_reserv[equipment=$equipment][dtstart>=$dtstart]") as $r) {
                    if (in_array($r->user->id, $contact_ids)) continue;
                    $users[$r->user->token] = $r->user;
                }
                foreach ($users as $user) {
                    Notification::send('eq_reserv.out_of_service', $user, [
                            '%user' => Markup::encode_Q($user),
                            '%equipment' => Markup::encode_Q($equipment),
                    ]);
                }
            }
        }
    }

    static function cal_component_deleted($e, $component) {

        $parent = $component->calendar->parent;
        if ($parent->name() != 'equipment') return;

        $user = $component->organizer;
        $equipment = $component->calendar->parent;
        $dtstart = $component->dtstart;
        $dtend = $component->dtend;
        $edit_user = L('ME');
        $edit_time = Date::format(time());
        $contacts = Q("{$equipment} user.contact");
        $description = $component->description ?: I18N::T('eq_reserv', '无');
        $link = $equipment->url('reserv', ['st' => $dtstart ]);
        Q("$user lab")->total_count() == 1 && $lab_owner = Q("$user lab")->current()->owner;

        //相关人员的联系信息，如果是他人删除的，给出修改者的联系信息
        $edit_user_name = $edit_user->id ? Markup::encode_Q($edit_user) : I18N::T('eq_reserv', '系统');
        if ($edit_user->id) {
            $other_content = "用户 {$edit_user_name} 的联系方式:\n电话: {$edit_user->phone}\nEmail: {$edit_user->email}";
        }
        else {
            $other_content = '';
        }

        if ($component->delete_remark) {
            $other_content .= "\n删除说明: {$component->delete_remark}";
        }

        $can_send = TRUE;
        $cal_rrule_id = $component->cal_rrule->id;
        if ($cal_rrule_id) {
            $rrule_key = 'delete_cal_can_send_'.$cal_rrule_id;
            if (L($rrule_key)) {
                $can_send = FALSE;
            }
            else {
                Cache::L($rrule_key, TRUE);
            }
        }

        if ($can_send) {
            //发送给预约用户的消息提醒
            Notification::send('eq_reserv.user_confirm_delete_reserv', $user, [
                '%user' => Markup::encode_Q($user),
                '%equipment' => Markup::encode_Q($equipment),
                '%time' => $edit_time,
                '%edit_user' => $edit_user->id ? Markup::encode_Q($edit_user) : I18N::T('eq_reserv', '系统'),
                '%old_dtstart' => Date::format($dtstart),
                '%old_dtend' => Date::format($dtend),
                '%link' => $link,
                '%other_content' => $user->id != $edit_user->id ? $other_content : '',
            ]);

            //发送给仪器联系人的消息提醒
            foreach ($contacts as $contact) {
                Notification::send('eq_reserv.contact_confirm_delete_reserv', $contact, [
                    '%contact' => Markup::encode_Q($contact),
                    '%user' => Markup::encode_Q($user),
                    '%equipment' => Markup::encode_Q($equipment),
                    '%time' => $edit_time,
                    '%edit_user' => $edit_user->id ? Markup::encode_Q($edit_user) : I18N::T('eq_reserv', '系统'),
                    '%old_dtstart' => Date::format($dtstart),
                    '%old_dtend' => Date::format($dtend),
                    '%user_phone' => H($user->phone),
                    '%user_email' => H($user->email),
                    '%description' => H($description),
                    '%link' => $link,
                ]);
            }

            if($lab_owner->id){
                //发送给课题组pi的消息提醒
                Notification::send('eq_reserv.pi_confirm_delete_reserv', $lab_owner, [
                    '%pi' => Markup::encode_Q($user),
                    '%user' => Markup::encode_Q($user),
                    '%equipment' => Markup::encode_Q($equipment),
                    '%time' => $edit_time,
                    '%edit_user' => $edit_user->id ? $edit_user->id ? Markup::encode_Q($edit_user) : I18N::T('eq_reserv', '系统') : I18N::T('eq_reserv', '系统'),
                    '%old_dtstart' => Date::format($dtstart),
                    '%old_dtend' => Date::format($dtend),
                    '%user_phone' => H($user->phone),
                    '%user_email' => H($user->email),
                    '%description' => H($description),
                    '%link' => $link,
                ]);
            }
        }
    }
}
