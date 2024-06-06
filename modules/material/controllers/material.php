<?php

class Material_AJAX_Controller extends AJAX_Controller
{
    function index_material_unit_click()
    {
        $form = Input::form();

        $equipment = O('equipment', $form['equipment_id']);

        if (!$equipment->id) return;

        if (!L('ME')->is_allowed_to('修改使用设置', $equipment)) return;

        JS::dialog(V('material:unit/add', ['equipment'=>$equipment]),[
            'title' => I18N::T('material', '添加计量单位'),
        ]);
    }

    function index_material_unit_submit()
    {
        $form = Form::filter(Input::form());
        $me = L('ME');

        $equipment = O('equipment', $form['equipment_id']);
        if (!$equipment->id) return;

        if (!$me->is_allowed_to('修改使用设置', $equipment)) return;

        if (!$form['name']) {
            $form->set_error('name', I18N::T('material', '请填写计量单位!'));
        }
        $exists = Q("material_unit[equipment={$equipment}][name={$form['name']}]")->total_count();
        if ($exists) {
            $form->set_error('name', I18N::T('material', '该单位已存在!'));
        }

        if ($form->no_error) {
            $material_unit = O('material_unit');
            $material_unit->equipment = $equipment;
            $material_unit->name = $form['name'];
            if ($material_unit->save()) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('material', '添加成功!'));
                Log::add(strtr('[material] %user_name[%user_id] 给仪器 %equipment_name[%equipment_id] 添加了耗材计量单位 %unit_name', [
                    '%user_name'=> $me->name,
                    '%user_id'=> $me->id,
                    '%equipment_name'=> $equipment->name,
                    '%equipment_id'=> $equipment->id,
                    '%unit_name'=> $material_unit->name,
                ]), 'journal');
            }else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('material', '添加失败!'));
            }
            JS::refresh();
        }else {
            JS::dialog(V('material:unit/add', ['equipment' => $equipment, 'form' => $form]),[
                'title' => I18N::T('material', '添加计量单位'),
            ]);
        }
    }
}
