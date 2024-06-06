<?php

class Material
{
    public static function setup_edit()
    {
        Event::bind('equipment.edit.tab', 'Material::material_set_tab');
    }

    public static function material_set_tab($e, $tabs)
    {
        $me = L('ME');

        $equipment = $tabs->equipment;

        if ($me->is_allowed_to('修改使用设置', $equipment)) {
            $tabs
                ->add_tab('material', [
                    'url' => $equipment->url('material', null, null, 'edit'),
                    'title' => I18N::T('material', '耗材设置'),
                    'weight' => 55
                ]);
            Event::bind('equipment.edit.content', 'Material::material_set_content', 0, 'material');
        }
    }

    public static function material_set_content($e, $tabs)
    {
        $me = L('ME');

        $equipment = $tabs->equipment;

        if (!$me->is_allowed_to('修改使用设置', $equipment)) URI::redirect('error/401');

        $materials = Q("material[equipment=$equipment][!hidden]");
        $ids_remove = $materials->to_assoc('id', 'id');
        $material_units = Q("material_unit[equipment=$equipment]")->to_assoc('id', 'name');

        $form = Form::filter(Input::form());

        $has_error = FALSE;

        if ($form['submit']) {
            foreach ($form['material'] as $key => $value) {
                unset($ids_remove[$form['material'][$key]['id']]);

                if (!$value['unit'] || !Q("material_unit[equipment=$equipment][id={$value['unit']}]")->total_count()) {
                    $form->set_error("material[$key][unit]", I18N::T('material', '请选择计量单位!'));
                    $has_error = TRUE;
                }
                if (!$value['name']) {
                    $form->set_error("material[$key][name]", I18N::T('material', '请填写耗材名称!'));
                    $has_error = TRUE;
                }
                if (!$value['price'] || $value['price'] <= 0) {
                    $form->set_error("material[$key][price]", I18N::T('material', '请填写单价并且大于零!'));
                    $has_error = TRUE;
                }

                if ($form->no_error) {
                    if ($value['id']) {
                        $material = O('material', $value['id']);
                    } else {
                        $material = O('material', [
                            'name' => trim($value['name']),
                            'equipment' => $equipment,
                            'hidden' => 1,
                        ]);
                        if ($material->id) {
                            $material->hidden = 0;
                        }else {
                            $material = O('material');
                        }
                    }
                    $material->equipment = $equipment;
                    $material->material_unit = O('material_unit', $value['unit']);
                    $material->name = trim($value['name']);
                    $material->price = trim($value['price']);
                    $material->enable_use = $value['enable_use'] == 'on';
                    $material->enable_sample = $value['enable_sample'] == 'on';
                    $material->save();
                }
            }

            //软删除
            if ($ids_remove) {
                foreach ($ids_remove as $remove_id) {
                    $remove_material = O('material', $remove_id);
                    if ($remove_material->id) {
                        $remove_material->hidden = 1;
                        $remove_material->save();
                    }
                }
            }

            if (!$has_error) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('material', '耗材设置成功!'));
                URI::redirect();
                return false;
            }
        }

        $tabs->content = V('material:material/edit', [
            'form' => $form,
            'equipment' => $equipment,
            'materials' => $materials,
            'material_units' => $material_units,
        ]);

    }

    public static function extra_charge_setting_view($e, $equipment)
    {
        $me = L('ME');
        if ($me->is_allowed_to('修改计费设置', $equipment)) {
            $e->return_value .= V('material:charge/setting',[
                'equipment' => $equipment,
            ]);
        }
    }

    public static function extra_charge_setting_content($e, $form, $equipment)
    {
        $me = L('ME');

        if ($me->is_allowed_to('修改计费设置', $equipment)) {
            if ($form['submit']) {
                $material_template = $form['material'];
                $charge_template = $equipment->charge_template;
                $charge_template['material'] = $material_template;
                $equipment->charge_template = $charge_template;
            }
        }
    }

    public static function charge_edit_content_tabs($e, $tabs)
    {
        $me = L('ME');

		$equipment = $tabs->equipment;

        if ($me->is_allowed_to('修改计费设置', $equipment)) {
            if ($equipment->charge_template['material'] &&
                $equipment->charge_template['material'] != 'no_charge_material') {
                $tabs->content->third_tabs
                    ->add_tab('material', [
                        'url' => $equipment->url('charge.material', NULL, NULL, 'edit'),
                        'title'  => I18N::T('material', '耗材计费'),
                        'weight' => 99,
                    ]);

                Event::bind('equipment.charge.edit.content', 'Material::edit_charge_material', 99, 'material');
            }
        }
    }

    public static function edit_charge_material($e, $tabs)
    {
        $equipment = $tabs->equipment;

        $form = Form::filter(Input::form());

        $script_view = Event::trigger('template[material_count].setting_view', $equipment, $form);
        $tabs->content = $script_view;
    }

    static function template_material_count_setting_view($e, $equipment, $form)
    {
        $charge_type = $equipment->charge_template['material'];
        $template = Config::get('eq_charge.template')[$charge_type];
        $charge_title = $template['title'];
        $i18n_module = $template['i18n_module'];
        $charge_default_setting = $template['content']['material']['params']['%options'];
        $materials = Q("material[equipment=$equipment][!hidden]");

        if($form['submit']){
            if (!L('ME')->is_allowed_to('修改计费设置', $equipment)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '耗材收费信息更新失败'));
                URI::redirect();
            }

            foreach ($form['price'] as $id => $price) {
                $material = O('material', $id);
                $material->price = $price;
                $material->save();
                $material_setting['*']['unit_price'][$id] = max(round($price, 2), 0);
            }
            $root = $equipment->get_root();
            $tags = $form['special_tags'];
            $prices = $form['special_unit_price'];

            if ($tags) {
                foreach ($tags as $i => $tag) {
                    if ($tag) {
                        $special_tags = @json_decode($tag, TRUE);

                        if ($special_tags) foreach($special_tags as $tag) {
                            //限制该仪器设定的收费标签必须是仪器root下真实存在的标签
                            $t = O('tag', ['root'=>$root, 'name'=>$tag]);
                            $tt = O('tag_equipment_user_tags', ['root'=> Tag_Model::root('equipment_user_tags'), 'name'=> $tag]);
                            if ($t->id || $tt->id) {
                                foreach ($prices as $material_id => $price) {
                                    $material_setting[$tag]['unit_price'][$material_id] = round($price[$i], 2);
                                }
                            }
                        }
                    }
                }
            }
            $params = EQ_Lua::array_p2l($material_setting);

            if(EQ_Charge::update_charge_script($equipment, 'material', ['%options'=>$params])){
                EQ_Charge::put_charge_setting($equipment, 'material', $material_setting);

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('material', '耗材收费信息已更新'));
            }
        }

        $e->return_value = V('material:charge/material', ['equipment' => $equipment,
            'charge_title' => I18N::T($i18n_module, $charge_title),
            'charge_default_setting' => $charge_default_setting,
            'materials' => $materials,
            ]);
        return FALSE;
    }

    public static function extra_form_validate($e, $equipment, $type, $form)
    {
        if ($type != 'eq_sample' && $type != 'use') {
            return;
        }

        foreach ($form['material'] as $id => $val) {
            if ($val == 'on' && $form['material_number'][$id] < 0 ) {
                $form->set_error("material_number[$id]", I18N::T('material', "选用耗材数量不能小于0"));
            }
        }
    }

    public static function get_enable_use_materials_by_eq($equipment)
    {
        return $equipment->id ? Q("material[equipment=$equipment][enable_use=1][!hidden]") : [];
    }

    public static function get_enable_sample_materials_by_eq($equipment)
    {
        return $equipment->id ? Q("material[equipment=$equipment][enable_sample=1][!hidden]") : [];
    }

}
