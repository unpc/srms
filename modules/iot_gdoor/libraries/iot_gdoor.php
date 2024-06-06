<?php

class Iot_Gdoor
{
    public static function is_accessible($e, $name)
    {
        if (L('ME')->access('查看门牌模块')) {
            $e->return_value = true;
            return false;
        }
        $e->return_value = false;
        return true;
    }


    public static function setup_equipment()
    {
        Event::bind('equipment.edit.tab', 'Iot_Gdoor::_edit_equipment_tab', 0, 'iot_gdoor');
        Event::bind('equipment.edit.content', 'Iot_Gdoor::_edit_equipment_content', 0, 'iot_gdoor');
    }
    
    public static function _edit_equipment_tab($e, $tabs)
    {
        $equipment = $tabs->equipment;
        if (L('ME')->is_allowed_to('关联门牌', $equipment)) {
            $tabs->add_tab('iot_gdoor', [
                'title' => I18N::T('iot_gdoor', '门牌设置'),
                'url' => $equipment->url('iot_gdoor', null, null, 'edit'),
                'weight' => 90
            ]);
        }
    }
    
    public static function _edit_equipment_content($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $form = Form::filter(Input::form());
        if ($form['submit']) {
            if ((int) $form['slot_card_ahead_time'] < 0 || ! is_numeric($form['slot_card_ahead_time']) || ((int) $form['slot_card_ahead_time'] != $form['slot_card_ahead_time'])) {
                $form->set_error('slot_card_ahead_time', I18N::T('eq_door', '请填写非负整数数字!'));
            }
            if ((int) $form['slot_card_delay_time'] < 0 || ! is_numeric($form['slot_card_delay_time']) || ((int) $form['slot_card_delay_time'] != $form['slot_card_delay_time'])) {
                $form->set_error('slot_card_delay_time', I18N::T('eq_door', '请填写非负整数数字!'));
            }
            if ($form->no_error) {
                $equipment->slot_card_ahead_time = $form['slot_card_ahead_time'];
                $equipment->slot_card_delay_time = $form['slot_card_delay_time'];
                $doors = $form['special_doors'];
                self::door_connect($doors, $equipment, 'iot_gdoor');
                $equipment->save();
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('iot_gdoor', '门牌设置更新成功!'));
            }
        }
        $content = V('iot_gdoor:gdoor/edit.equipment', ['equipment'=>$equipment, 'form'=>$form]);
        $tabs->content = $content;
    }

    public static function operate_gdoor_is_allowed($e, $user, $perm, $equipment, $params)
    {
        switch ($perm) {
            case '关联门牌':
                if ($user->access('管理所有仪器的门牌')) {
                    $e->return_value = true;
                    return false;
                }
                if (Equipments::user_is_eq_incharge($user, $equipment) && $user->access('管理负责仪器的门牌')) {
                    $e->return_value = true;
                    return false;
                }
                break;
        }
    }

    public static function door_connect($subjects, $object, $s_name, $type = 'asso')
    {
        switch ($s_name) {
            case 'iot_gdoor':
                break;
            default:
                return;
        }
        $old_subjects = Q("{$object}<{$type} {$s_name}")->to_assoc('id', 'id');
        if (count($subjects)) {
            foreach ($subjects as $s_id) {
                if (!$s_id) {
                    continue;
                }
                //门牌是否存在，不存在则增加
                $subject = O("$s_name", ["gdoor_id" => "$s_id"]);
                if (!$subject->id) {
                    $subject = O("$s_name");
                    $subject->gdoor_id = "$s_id";
                    $subject->save();
                }
                $subject_id = $subject->id;
                if (!$subject_id) {
                    continue;
                }
                if (in_array($subject_id, $old_subjects)) {
                    unset($old_subjects[$subject_id]);
                    continue;
                }
                if ('iot_gdoor' == $subject->name()) {
                    $subject->connect($object, $type);
                }
                $me = L('ME');
                Log::add(strtr('[iot_gdoor] %user_name[%user_id]关联%subject_name[%subject_id]与%object_name[%object_id]门牌', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%subject_name' => $subject->name,
                    '%subject_id' => $subject->id,
                    '%object_name' => $object->name,
                    '%object_id' => $object->id,
                ]), 'journal');
            }
        }

        if (count($old_subjects)) {
            foreach ($old_subjects as $s_id) {
                $subject = O("$s_name", $s_id);
                if ('iot_gdoor' == $subject->name()) {
                    $subject->disconnect($object, $type);
                }
                $me = L('ME');
                Log::add(strtr('[iot_gdoor] %user_name[%user_id]断开%subject_name[%subject_id]与%object_name[%object_id]的门牌', [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%subject_name' => $subject->name,
                    '%subject_id' => $subject->id,
                    '%object_name' => $object->name,
                    '%object_id' => $object->id,
                ]), 'journal');
            }
        }
    }

    public static function equipment_dashboard_sections($e, $equipment, $sections)
    {
        $doors = Q("{$equipment} iot_gdoor.asso");
        if (count($doors)) {
            $sections[] = V('iot_gdoor:gdoor/equipment.section', ['equipment'=>$equipment, 'doors'=>$doors]);
        }
    }
}
