<?php

class Material_Sample
{
    static function eq_sample_prerender_add_form($e, $form, $equipment)
    {
        $e->return_value .= V('material:view/eq_sample/edit', ['form' => $form, 'equipment' => $equipment]);
        return true;
    }

    static function eq_sample_prerender_edit_form($e, $sample, $form, $user)
    {
        $equipment = $sample->equipment;
        $e->return_value .= V('material:view/eq_sample/edit', ['form' => $form, 'sample' => $sample, 'equipment' => $equipment]);
        return true;
    }

    static function eq_sample_form_submit($e, $sample, $form)
    {
        $selected_material = [];
        foreach ($form['material'] as $id => $val) {
            if ($val == 'on') {
                $selected_material[$id] = $form['material_number'][$id];
            }
        }
        $sample->materials = json_encode($selected_material);
    }

    static function sample_table_list_columns($e, $form, $columns)
    {
        $columns['material_amount'] = [
            'title'=>I18N::T('material', '耗材费'),
            'align'=>'left',
            'nowrap'=>TRUE,
            'weight' => 95,
        ];
        return TRUE;
    }

    static function sample_table_list_row ($e, $row, $sample)
    {
        $charge = O('eq_charge', ['source' => $sample]);
        $row['material_amount'] = V('material:table/data/material_amount', ['charge' => $charge]);

        $e->return_value = $row;
    }

    static function sample_extra_print($e, $sample)
    {
        $e->return_value .= V("material:sample/extra_print", [
            'sample' => $sample,
        ]);
        return FALSE;
    }

}
