<?php

//RQ191620【通用】预热操作\冷却操作
class Equipment_Preheat_Cooling
{
    // 当前时间符合的规则
    public static function get_preheat_cooling($equipment, $time = 0)
    {
        if (!$time) {
            $time = Date::time();
        }
        $selector = "{$equipment} eq_preheat_cooling[ctime<{$time}]";
        $eq_preheat_cooling_current = Q("{$selector}:sort(ctime D)")->current();
        return $eq_preheat_cooling_current->id ? $eq_preheat_cooling_current : O('eq_preheat_cooling');
    }

    // 仪器基本信息页面 设置预热冷却
    public static function get_view_dashboard_sections($e, $equipment, $sections)
    {
        // 是否设置了预热冷却
        $eq_preheat_cooling_current = self::get_preheat_cooling($equipment);
        if ($eq_preheat_cooling_current->id && ($eq_preheat_cooling_current->preheat_time || $eq_preheat_cooling_current->cooling_time)) {
            $sections[] = V('equipments:preheat_cooling/section.equipment_preheat_cooling', ['eq_preheat_cooling' => $eq_preheat_cooling_current]);
        }
        return $eq_preheat_cooling_current;
    }

    public static function equipment_edit_use_form_validate($e, $equipment, $form)
    {
        if ($form['power_on_preheating'] && isset($form['power_on_preheating_mins']) && (int)$form['power_on_preheating_mins'] <= 0) {
            $form->set_error('power_on_preheating_mins', I18N::T('equipments', '开机预热应该大于0'));
        }
        if ($form['shutdown_cooling'] && isset($form['shutdown_cooling_mins']) && (int)$form['shutdown_cooling_mins'] <= 0) {
            $form->set_error('shutdown_cooling_mins', I18N::T('equipments', '关机冷却时间应该大于0'));
        }
    }

    public static function edit_use($e, $equipment, $form)
    {
        $me = L('ME');
        // 获取第一条预热/冷却当前可用的配置
        $eq_preheat_cooling_current = Equipment_Preheat_Cooling::get_preheat_cooling($equipment);
        $eq_preheat_cooling = O("eq_preheat_cooling");
        // 开机预热
        if ($form['power_on_preheating']) {
            $eq_preheat_cooling->preheat_time = Date::convert_interval($form['power_on_preheating_mins'], $form['power_on_preheating_format']);
            $eq_preheat_cooling->preheat_unit = $form['power_on_preheating_format'];
        } else {
            $eq_preheat_cooling->preheat_time = 0;
        }
        // 关机冷却
        if ($form['shutdown_cooling']) {
            $eq_preheat_cooling->cooling_time = Date::convert_interval($form['shutdown_cooling_mins'], $form['shutdown_cooling_format']);
            $eq_preheat_cooling->cooling_unit = $form['shutdown_cooling_format'];
        } else {
            $eq_preheat_cooling->cooling_time = 0;
        }

        $jude_edit = $eq_preheat_cooling->preheat_time != $eq_preheat_cooling_current->preheat_time
            || $eq_preheat_cooling->cooling_time != $eq_preheat_cooling_current->cooling_time;

        if ($jude_edit) {
            // 配置发生变化、保存新的配置
            $eq_preheat_cooling->equipment = $equipment;
            $eq_preheat_cooling->save();
            Log::add(strtr(
                '[equipment] %user_name[%user_id]设置%equipment_name[%equipment_id]仪器的预热/冷却时间[%eq_preheat_cooling_id]',
                [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%equipment_name' => $equipment->name,
                    '%equipment_id' => $equipment->id,
                    '%eq_preheat_cooling_id' => $eq_preheat_cooling->id
                ]
            ), 'journal');
        }
    }

    // 修改时判断开始、结束时间是否合法
    public static function extra_form_validate($e, $equipment, $type, $form)
    {
        if ($equipment->name() != 'equipment'
            || !$form['dtstart']
            || !$form['dtend']
            || ($form['power_on_preheating'] != 'on' && $form['shutdown_cooling'] != 'on')
        ) {
            return true;
        }

        switch ($type) {
            case 'use':
                $ctime = O('eq_record', $form['record_id'])->ctime;
                break;
            case 'eq_sample':
                $ctime = O('eq_record', $form['record_id'])->ctime;
                break;
            default:
                return;
        }
        $time = 0;
        $eq_preheat_cooling = Equipment_Preheat_Cooling::get_preheat_cooling($equipment, $ctime);
        if ($form['power_on_preheating'] == 'on') {
            $time += $eq_preheat_cooling->preheat_time;
        }
        if ($form['shutdown_cooling'] == 'on') {
            $time += $eq_preheat_cooling->cooling_time;
        }
        if (($form['dtrial_check'] == 'on' && $type == 'eq_sample' || $type != 'eq_sample' ) && $time >= $form['dtend'] - $form['dtstart']) {
            $form->set_error('dtend', I18N::T('eq_charge', '您选择了预热/冷却操作，您的使用时间应大于预热/冷却需要时间'));
            $e->return_value = false;
        }
        return;
    }

    public static function eq_record_form_submit($e, $record, $form)
    {
        $eq_preheat_cooling = self::get_preheat_cooling($record->equipment, $record->ctime);
        if ($eq_preheat_cooling->id) {
            if ($form['power_on_preheating'] === 'on') {
                $record->preheat = $eq_preheat_cooling->preheat_time;
            } else {
                $record->preheat = 0;
            }
            if ($form['shutdown_cooling'] === 'on') {
                $record->cooling = $eq_preheat_cooling->cooling_time;
            } else {
                $record->cooling = 0;
            }
        }
        foreach (Q("{$record}<record eq_sample") as $sample) {
            $sample->preheat = $record->preheat;
            $sample->cooling = $record->cooling;
            $sample->save();
        }
    }

    public static function sample_form_submit($e, $sample, $form)
    {
        if ($sample->record->id) {
            $sample->preheat = $sample->record->preheat;
            $sample->cooling = $sample->record->cooling;
        } else {
            $eq_preheat_cooling = self::get_preheat_cooling($sample->equipment, $sample->ctime);
            if ($form['power_on_preheating'] == 'on') {
                $sample->preheat = $eq_preheat_cooling->preheat_time;
            } else {
                $sample->preheat = 0;
            }
            if ($form['shutdown_cooling'] == 'on') {
                $sample->cooling = $eq_preheat_cooling->cooling_time;
            } else {
                $sample->cooling = 0;
            }
        }
    }
}
