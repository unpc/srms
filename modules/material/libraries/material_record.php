<?php

class Material_Record
{
    static function record_edit_view($e, $record, $form, $sections)
    {
        $equipment = $record->equipment;
        $sections['material'] = V('material:view/eq_record/edit', ['record'=>$record, 'equipment' => $equipment, 'form'=>$form]);
    }

    public static function record_post_form_submit($e, $record, $form)
    {
        $selected_material = [];
        foreach ($form['material'] as $id => $val) {
            if ($val == 'on') {
                $selected_material[$id] = $form['material_number'][$id];
            }
        }
        if ($record->reserv->id) {
            $reserv = $record->reserv;
            $reserv->materials = json_encode($selected_material);
            $reserv->save();
        }else {
            $record->materials = json_encode($selected_material);
        }
        return true;
    }

    static function eq_record_list_columns ($e, $form, $columns, $current_page)
    {
        $weight = ($columns['charge_amount']['weight'] ?: 100) + 1;
        $columns['material_amount'] = [
            'title'=>I18N::T('material', '耗材费'),
            'align'=>'left',
            'nowrap'=>TRUE,
            'weight' => $weight,
        ];
        return TRUE;
    }

    static function eq_record_list_row ($e, $row, $record, $current_page)
    {
        $source = $record->reserv->id ? $record->reserv : $record;
        $charge = O('eq_charge', ['source' => $source]);
        $row['material_amount'] = V('material:table/data/material_amount', ['charge' => $charge]);

        $e->return_value = $row;
    }

}
