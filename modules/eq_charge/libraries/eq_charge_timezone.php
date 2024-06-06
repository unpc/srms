<?php

//前置后置时间RQ191620
class Eq_Charge_Timezone
{


    public static function allow_timezone($equipment)
    {
        if (!$equipment->id && !$equipment->control_mode) return false;
        $allow = [
            'computer',
            'power',
            'veronica',
        ];
        if (in_array($equipment->control_mode, $allow) && L('ME')->is_allowed_to('修改计费设置', $equipment)) {
            return true;
        }
        return false;
    }

    static function edit_charge_timezone($e, $tabs)
    {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $equipment = $tabs->equipment;
        if ($form['submit']) {
            if (!L('ME')->is_allowed_to('修改计费设置', $equipment)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '前置后置设置更新失败'));
                URI::redirect();
            }
            if (!Eq_Charge_Timezone::allow_timezone($equipment)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '当前控制方式不支持前置后置时间'));
                URI::redirect();
            }
            if ($form['need_lead_time'] == 'on' && (!is_numeric($form['lead_time']) || $form['lead_time'] <= 0)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '前置时间须为大于0整数'));
            }
            if ($form['need_post_time'] == 'on' && (!is_numeric($form['post_time']) || $form['post_time'] <= 0)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '后置时间须为大于0整数'));
            }

            if (count(Lab::messages(Lab::MESSAGE_ERROR))) {
                URI::redirect();
            }

            $timezone = O('eq_timezone');
            $timezone->equipment = $equipment;
            $timezone->user = $me;
            $timezone->ctime = time();
            $timezone->lead_time = $form['need_lead_time'] == 'on' ? $form['lead_time'] : 0;
            $timezone->post_time = $form['need_post_time'] == 'on' ? $form['post_time'] : 0;
            $timezone->save();

            $equipment->need_lead_time = $form['need_lead_time'] == 'on';
            $equipment->need_post_time = $form['need_post_time'] == 'on';
            $equipment->save();

            if (count(Lab::messages(Lab::MESSAGE_ERROR))) {
                URI::redirect();
            }

            $zone = O('eq_timezone');
            $zone->lead_time = 0;

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '前置后置设置更新成功'));
        }

        $tabs->content = V('eq_charge:edit/timezone', ['equipment' => $equipment, 'form' => $form]);
    }

    /**
     * 获取最新的设置
     * @param $e
     * @param $equipment
     */
    static function timezone($e, $equipment)
    {
        $default = [
            'lead_time' => 0,
            'post_time' => 0,
        ];
        if (!$equipment->need_lead_time && !$equipment->need_post_time) {
            $e->return_value = $default;
            return $default;
        }
        $timezone = Q("eq_timezone[equipment={$equipment}]:sort(ctime D)")->current();
        $e->return_value = ($timezone->id ?
            [
                'lead_time' => $timezone->lead_time,
                'post_time' => $timezone->post_time,
            ] : $default);
        return true;
    }

    static function extra_form_validate($e, $equipment, $type, $form)
    {
        $me = L('ME');
        if (in_array($type, ['use', 'eq_reserv']) && $equipment->name() == 'equipment' && ($equipment->need_lead_time || $equipment->need_post_time)) {
            $timezone = $equipment->timezone();
            $lead_time = $timezone['lead_time'] * 60;
            $post_time = $timezone['post_time'] * 60;
            $time_length = $form['dtend'] - $form['dtstart'];
            if ($post_time + $lead_time >= $time_length) {
                $limit = ($post_time + $lead_time) / 60;
                $form->set_error('dtstart', I18N::T('eq_charge', '本机前置/后置时间要求最小预约/使用时长大于' . $limit . '分钟'));
                $e->return_value = TRUE;
            }
        }
        return TRUE;
    }
}