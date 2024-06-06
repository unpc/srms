<?php

class Test_Project_Charge
{
    public static function after_calculate_amount($e, $charge, $result)
    {
        $result = $e->return_value ?: $result;

        $source = $charge->source;
        if (!$source->id || ($source->name() == 'eq_record' && $source->reserv->id)){
            return;
        }

        $user = $charge->user;
        $equipment = $charge->equipment;
        $charge_template = $equipment->charge_template['test_project'];
        if ($charge_template == 'no_charge_test_project') {
            $charge->test_project_amount = 0;
            $e->return_value = $result;
            return;
        }

        $setting = $equipment->charge_setting['test_project'];
        $lua = new EQ_Charge_LUA($charge);
        $user_tags = $lua->user_tags();
        if ($user_tags) foreach ($user_tags as $user_tag) {
            if (isset($setting[$user_tag])) {
                $option = $setting[$user_tag]['unit_price'];
                break;
            }
        }
        if (!isset($option)) {
            $option = $setting['*']['unit_price'];
        }
        $test_projects = json_decode($source->test_projects, true);
        $fee = 0;
        $description = '<p><span>测试项目 </span>';
        foreach ($test_projects as $id => $number) {
            $test_project = O('test_project', $id);
            $price = isset($option[$id]) ? $option[$id] : $test_project->price;
            $fee = $fee + ($number * $price);
            $description .= '<span>'.$test_project->name.' '.$number.'个, 单价'.Config::get('lab.currency_sign').$price.';</span>';
        }
        $fee = (float) round($fee, 2);
        $description .= '<span>共计:'.Config::get('lab.currency_sign').$fee.'</span></p>';

        $charge->test_project_amount = $fee;

        if ($fee > 0) {
            $result['description'] .= $description;
            $result['fee'] += $fee;
        }

        Log::add(strtr('[eq_charge] %user_name[%user_id] 生成了仪器 %equipment_name[%equipment_id] 的项目收费 %fee', [
            '%user_name' => $user->name,
            '%user_id' => $user->id,
            '%equipment_name' => $equipment->name,
            '%equipment_id' => $equipment->id,
            '%fee' => $fee,
        ]), 'journal');

        $e->return_value = $result;
    }

    public static function has_relative_charge($e, $equipment)
    {
        if ($equipment->charge_script['test_project']) {
            $e->return_value = true;
            return true;
        }
    }

    public static function lua_cal_ext_amount($e, $equipment, $lua)
    {
        $result = $lua->run(['fee', 'description']);
        $charge = $lua->_charge;
        $source = $charge->source;
        if ($source->name() == 'eq_record' && $source->reserv->id) {
            $e->return_value = $result;
            return true;
        }

        $user = $charge->user;
        $charge_template = $equipment->charge_template['test_project'];
        if ($charge_template == 'no_charge_test_project') {
            $e->return_value = $result;
            return true;
        }

        $setting = $equipment->charge_setting['test_project'];
        $lua = new EQ_Charge_LUA($charge);
        $user_tags = $lua->user_tags();
        if ($user_tags) foreach ($user_tags as $user_tag) {
            if (isset($setting[$user_tag])) {
                $option = $setting[$user_tag]['unit_price'];
                break;
            }
        }
        if (!isset($option)) {
            $option = $setting['*']['unit_price'];
        }

        $test_projects = json_decode($source->test_projects, true);
        $fee = 0;
        $description = '<p><span>测试项目 </span>';
        foreach ($test_projects as $id => $number) {
            $test_project = O('test_project', $id);
            $price = isset($option[$id]) ? $option[$id] : $test_project->price;
            $fee = $fee + ($number * $price);
            $description .= '<span>'.$test_project->name.' '.$number.'个, 单价'.Config::get('lab.currency_sign').$price.';</span>';
        }
        $fee = (float) round($fee, 2);
        $description .= '<span>共计:'.Config::get('lab.currency_sign').$fee.'</span></p>';
        $charge->test_project_amount = $fee;

        if ($fee > 0) {
            $result['description'] .= $description;
            $result['fee'] += $fee;
        }

        $e->return_value = $result;
        return true;
    }
}
