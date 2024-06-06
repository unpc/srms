<?php

class Material_Reserv
{
    static function eq_reserv_prerender_component($e, $view, $form)
    {
        $form = $e->return_value ?: $form;
        $component = $view->component;

        $form['material'] = [
            'label' => I18N::T('material', '选用耗材'),
            'path' => ['form' => 'material:view/'],
            'component' => $component,
        ];

        $form['#categories']['reserv_info']['items'][] = 'material';
        $e->return_value = $form;
        return TRUE;
    }

    static function component_form_submit($e, $form, $component)
    {
        $parent = $component->calendar->parent;
        if ($parent->name() != 'equipment') return;

        foreach ((array) $form['material'] as $id => $val) {
            if ($val == 'on' && $form['material_number'][$id] < 0 ) {
                $form->set_error("material_number[$id]", I18N::T('material', "选用耗材数量不能小于0"));
            }
        }
    }

    static function component_form_post_submit($e, $component, $form)
    {
        $parent = $component->calendar->parent;
        if ($parent->name() != 'equipment') return;
        $selected_material = [];
        foreach ((array) $form['material'] as $id => $val) {
            if ($val == 'on') {
                $selected_material[$id] = $form['material_number'][$id];
            }
        }
        $eq_reserv = O('eq_reserv', ['component' => $component]);
        $eq_reserv->materials = json_encode($selected_material);
        $eq_reserv->save();
    }

}
