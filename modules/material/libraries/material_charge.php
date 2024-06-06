<?php

class Material_Charge
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
        $charge_template = $equipment->charge_template['material'];
        if ($charge_template == 'no_charge_material') {
            $charge->material_amount = 0;
            $e->return_value = $result;
            return;
        }

        $setting = $equipment->charge_setting['material'];
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
        $materials = json_decode($source->materials, true);
        $fee = 0;
        $description = '<p><span>使用耗材 </span>';
        if (is_array($materials) && !empty($materials)) {
            foreach ($materials as $id => $number) {
                $material = O('material', $id);
                $price = isset($option[$id]) ? $option[$id] : $material->price;
                $fee = $fee + ($number * $price);
                $description .= '<span>'.$material->name.' '.$number.$material->material_unit->name.', 单价'.Config::get('lab.currency_sign').$price.';</span>';
            }
        }
        $fee = (float) round($fee, 2);
        $description .= '<span>共计:'.Config::get('lab.currency_sign').$fee.'</span></p>';

        $charge->material_amount = $fee;
        $result['description'] .= $description;

        Log::add(strtr('[eq_charge] %user_name[%user_id] 生成了仪器 %equipment_name[%equipment_id] 的耗材收费 %fee', [
            '%user_name' => $user->name,
            '%user_id' => $user->id,
            '%equipment_name' => $equipment->name,
            '%equipment_id' => $equipment->id,
            '%fee' => $fee,
        ]), 'journal');

        $e->return_value = $result;
    }

    public static function charges_table_columns($e, $form, $columns, $lab)
    {
        $weight = ($columns['amount']['weight'] ?: 100) + 1;
        $columns['material_amount'] = [
            'title'=>I18N::T('material', '耗材费'),
            'align'=>'left',
            'nowrap'=>TRUE,
            'weight' => $weight,
        ];
        return TRUE;
    }

    public static function charges_table_list_row($e, $row, $charge, $obj)
    {
        $row = $e->return_value ?: $row;
        $row['material_amount'] = V('material:table/data/material_amount', ['charge' => $charge]);

        $e->return_value = $row;
    }

    static function eq_charge_confirm_extra_selector($e, $form, $selector)
    {
        $selector = $e->return_value ?: $selector;
        $selector = preg_replace('/\[amount!=0\]/', '[amount!=0|material_amount!=0]', $selector);
        $e->return_value = $selector;
    }

    public static function charge_table_list_columns($e, $form, $columns)
    {
        $columns['material_amount'] = [
            'title'=>I18N::T('material', '耗材费'),
            'align'=>'left',
            'nowrap'=>TRUE,
            'weight' => 70,
        ];
        return TRUE;
    }

    public static function charge_table_list_row($e, $row, $charge)
    {
        $row = $e->return_value ?: $row;
        $row['material_amount'] = V('material:table/data/material_amount', ['charge' => $charge]);

        $e->return_value = $row;
    }

    public static function eq_charge_primary_content_selector($e, $form, $selector, $preselector)
    {
        $selector = $e->return_value ?: $selector;
        $selector = preg_replace('/\[amount!=0\]/', '[amount!=0|material_amount!=0]', $selector);
        $e->return_value = $selector;
    }
}
