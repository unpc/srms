<?php

class Material_Export
{
    static function calendar_extra_export_columns($e, $valid_columns, $form_token)
    {
        $valid_columns = (array)$e->return_value + (array)$valid_columns;

        if ($_SESSION[$form_token] && $_SESSION[$form_token]['equipment_id']) {
            $equipment = O('equipment', $_SESSION[$form_token]['equipment_id']);
            $materials = Q("material[equipment=$equipment]");

            $valid_columns[-5] = '耗材信息';
            foreach ($materials as $material) {
                $valid_columns['material_id_'.$material->id] = $material->name.'('.$material->material_unit->name.')';
            }
        }
        $e->return_value = $valid_columns;
        return TRUE;
    }

    static function calendar_extra_export_columns_checked($e, $key)
    {
        if (strpos($key, 'material_id_') !== false) {
            $e->return_value = true;
            return false;
        }
        return true;
    }

    static function calendar_export_list_csv($e, $object, $data, $valid_columns)
    {
        $data = (array)$e->return_value + (array)$data;
        $materials = json_decode($object->materials, true);
        foreach ($valid_columns as $key => $val) {
            if (strpos($key, 'material_id_') !== false) {
                $id = array_pop(explode('_', $key));
                $data[] = isset($materials[$id]) ? $materials[$id] : 0;
            }
        }
        $e->return_value = $data;
    }

    static function eq_reserv_export_csv($e, $component, $data, $valid_columns)
    {
        $reserv = O('eq_reserv', ['component'=>$component]);
        $data = (array)$e->return_value + (array)$data;
        $materials = json_decode($reserv->materials, true);
        foreach ($valid_columns as $key => $val) {
            if (strpos($key, 'material_id_') !== false) {
                $id = array_pop(explode('_', $key));
                $data[] = isset($materials[$id]) ? $materials[$id] : 0;
            }
        }
        $e->return_value = $data;
    }

    public static function extra_export_columns($e, $valid_columns)
    {
        $valid_columns = (array)$e->return_value + (array)$valid_columns;
        $keys = array_keys($valid_columns);
        $values = array_values($valid_columns);

        $offset = array_search('amount', $keys);
        array_splice($keys, $offset + 1, 0, 'material_amount');
        array_splice($values, $offset + 1 , 0, T('耗材费'));

        $valid_columns = array_combine($keys, $values);

        $form = Input::form();
        $form_token = $form['form_token'];
        if ($_SESSION[$form_token] && $_SESSION[$form_token]['equipment_id']) {
            $equipment = O('equipment', $_SESSION[$form_token]['equipment_id']);
            $materials = Q("material[equipment=$equipment]");

            $valid_columns[-5] = '耗材信息';
            foreach ($materials as $material) {
                $valid_columns['material_id_'.$material->id] = $material->name.'('.$material->material_unit->name.')';
            }
        }
        $e->return_value = $valid_columns;

        return true;
    }

    static function eq_sample_export_csv($e, $sample, $data, $valid_columns)
    {
        $data = (array)$e->return_value + (array)$data;
        $materials = json_decode($sample->materials, true);
        foreach ($valid_columns as $key => $val) {
            if (strpos($key, 'material_id_') !== false) {
                $id = array_pop(explode('_', $key));
                $data[] = isset($materials[$id]) ? $materials[$id] : 0;
            }
        }
        $e->return_value = $data;
    }

    static function get_export_record_columns($e, $columns, $type = 'print')
    {
        $valid_columns = (array)$e->return_value + (array)$columns;

        $keys = array_keys($valid_columns);
        $values = array_values($valid_columns);

        $offset = array_search('charge_amount', $keys);
        array_splice($keys, $offset + 1, 0, 'material_amount');
        array_splice($values, $offset + 1 , 0, T('耗材费'));

        $valid_columns = array_combine($keys, $values);

        $form = Input::form();
        $form_token = $form['form_token'];
        $equipment_id = $_SESSION[$form_token]['form']['equipment_id'];
        if (!$equipment_id) {
            $equipment_id = $form['eid'];
        }
        if ($_SESSION[$form_token] && $equipment_id) {
            $equipment = O('equipment', $equipment_id);
            $materials = Q("material[equipment=$equipment]");

            $valid_columns[-5] = '耗材信息';
            foreach ($materials as $material) {
                $valid_columns['material_id_'.$material->id] = $material->name.'('.$material->material_unit->name.')';
            }
        }

        $e->return_value = $valid_columns;
        return TRUE;
    }

    static function eq_record_export_list_csv($e, $eq_record, $data, $valid_columns)
    {
        $data = (array)$e->return_value + (array)$data;
        $materials = $eq_record->reserv->id ? json_decode($eq_record->reserv->materials, true) : json_decode($eq_record->materials, true);
        foreach ($valid_columns as $key => $val) {
            if (strpos($key, 'material_id_') !== false) {
                $id = array_pop(explode('_', $key));
                $data[] = isset($materials[$id]) ? $materials[$id] : 0;
            }
        }
        $e->return_value = $data;
    }

    public static function get_export_charge_columns($e, $columns)
    {
        $keys = array_keys((array)$columns);
        $values = array_values((array)$columns);

        $offset = array_search('amount', $keys);
        array_splice($keys, $offset + 1, 0, 'material_amount');
        array_splice($values, $offset + 1 , 0, T('耗材费'));

        $columns = array_combine($keys, $values);
        $e->return_value = $columns;
        return true;
    }

}
