<?php

class Test_Project
{
    public static function setup_edit()
    {
        Event::bind('equipment.edit.tab', 'Test_project::test_project_set_tab');
    }

    public static function test_project_set_tab($e, $tabs)
    {
        $me = L('ME');

        $equipment = $tabs->equipment;

        if ($me->is_allowed_to('修改使用设置', $equipment)) {
            $tabs
                ->add_tab('test_project', [
                    'url' => $equipment->url('test_project', null, null, 'edit'),
                    'title' => I18N::T('test_project', '测试项目'),
                    'weight' => 50
                ]);
            Event::bind('equipment.edit.content', 'Test_project::test_project_set_content', 0, 'test_project');
        }
    }

    public static function test_project_set_content($e, $tabs)
    {
        $me = L('ME');

        $equipment = $tabs->equipment;

        if (!$me->is_allowed_to('修改使用设置', $equipment)) URI::redirect('error/401');

        $test_projects = Q("test_project[equipment=$equipment][!hidden]");
        $ids_remove = $test_projects->to_assoc('id', 'id');
        $test_project_cats = Q("test_project_cat[equipment=$equipment]")->to_assoc('id', 'name');

        $form = Form::filter(Input::form());

        $has_error = FALSE;

        if ($form['submit']) {
            foreach ($form['test_project'] as $key => $value) {
                unset($ids_remove[$form['test_project'][$key]['id']]);

                if (!$value['cat'] || !Q("test_project_cat[equipment=$equipment][id={$value['cat']}]")->total_count()) {
                    $form->set_error("test_project[$key][cat]", I18N::T('test_project', '请选择项目分类!'));
                    $has_error = TRUE;
                }
                if (!$value['name']) {
                    $form->set_error("test_project[$key][name]", I18N::T('test_project', '请填写项目名称!'));
                    $has_error = TRUE;
                } elseif (mb_strlen($value['name']) > 50) {
                    $form->set_error("test_project[$key][name]", I18N::T('test_project', '项目名称不得大于50个字!'));
                    $has_error = TRUE;
                }

                if ($form->no_error) {
                    if ($value['id']) {
                        $test_project = O('test_project', $value['id']);
                    } else {
                        $test_project = O('test_project', [
                            'name' => trim($value['name']),
                            'equipment' => $equipment,
                            'hidden' => 1,
                        ]);
                        if ($test_project->id) {
                            $test_project->hidden = 0;
                        }else {
                            $test_project = O('test_project');
                        }
                    }
                    $test_project->equipment = $equipment;
                    $test_project->test_project_cat = O('test_project_cat', $value['cat']);
                    $test_project->name = trim($value['name']);
                    $test_project->enable_use = $value['enable_use'] == 'on';
                    $test_project->enable_sample = $value['enable_sample'] == 'on';
                    $test_project->save();
                }
            }

            //软删除
            if ($ids_remove) {
                foreach ($ids_remove as $remove_id) {
                    $remove_test_project = O('test_project', $remove_id);
                    if ($remove_test_project->id) {
                        $remove_test_project->hidden = 1;
                        $remove_test_project->save();
                    }
                }
            }

            if (!$has_error) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('test_project', '测试项目设置成功!'));
                URI::redirect();
                return false;
            }
        }

        $tabs->content = V('test_project:test_project/edit', [
            'form' => $form,
            'equipment' => $equipment,
            'test_projects' => $test_projects,
            'test_project_cats' => $test_project_cats,
        ]);

    }

    public static function extra_charge_setting_view($e, $equipment)
    {
        $me = L('ME');
        if ($me->is_allowed_to('修改计费设置', $equipment)) {
            $e->return_value .= V('test_project:charge/setting',[
                'equipment' => $equipment,
            ]);
        }
    }

    public static function extra_charge_setting_content($e, $form, $equipment)
    {
        $me = L('ME');

        if ($form['submit']) {
            $test_project_template = $form['test_project'];
            $charge_template = $equipment->charge_template;
            $charge_template['test_project'] = $test_project_template;
            $equipment->charge_template = $charge_template;

            if ($test_project_template == 'no_charge_test_project') {
                $template_standard = $equipment->template_standard;
                $template_standard['test_project'] = null;
                $equipment->template_standard = $template_standard;

                $charge_script = $equipment->charge_script;
                $charge_script['test_project'] = null;
                $equipment->charge_script = $charge_script;
            }
        }
    }

    public static function charge_edit_content_tabs($e, $tabs)
    {
        $me = L('ME');

		$equipment = $tabs->equipment;

        if ($me->is_allowed_to('修改计费设置', $equipment)) {
            if ($equipment->charge_template['test_project'] &&
                $equipment->charge_template['test_project'] != 'no_charge_test_project') {
                $tabs->content->third_tabs
                    ->add_tab('test_project', [
                        'url' => $equipment->url('charge.test_project', NULL, NULL, 'edit'),
                        'title'  => I18N::T('test_project', '项目计费'),
                        'weight' => 95,
                    ]);

                Event::bind('equipment.charge.edit.content', 'Test_project::edit_charge_test_project', 95, 'test_project');
            }
        }
    }

    public static function edit_charge_test_project($e, $tabs)
    {
        $equipment = $tabs->equipment;

        $form = Form::filter(Input::form());

        $script_view = Event::trigger('template[test_project_count].setting_view', $equipment, $form);
        $tabs->content = $script_view;
    }

    static function template_test_project_count_setting_view($e, $equipment, $form)
    {
        $charge_type = $equipment->charge_template['test_project'];
        $template = Config::get('eq_charge.template')[$charge_type];
        $charge_title = $template['title'];
        $i18n_module = $template['i18n_module'];
        $charge_default_setting = $template['content']['test_project']['params']['%options'];
        $test_projects = Q("test_project[equipment=$equipment][!hidden]");

        if($form['submit']){
            if (!L('ME')->is_allowed_to('修改计费设置', $equipment)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '项目计费信息更新失败'));
                URI::redirect();
            }

            $test_project_setting['*']['test_project_items'] = $equipment->get_test_project_items();

            foreach ($form['price'] as $id => $price) {
                $test_project = O('test_project', $id);
                $test_project->price = $price;
                $test_project->save();
                $test_project_setting['*']['unit_price'][$id] = max(round($price, 2), 0);
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
                                foreach ($prices as $test_project_id => $price) {
                                    $test_project_setting[$tag]['unit_price'][$test_project_id] = round($price[$i], 2);
                                }
                            }
                        }
                    }
                }
            }
            $params = EQ_Lua::array_p2l($test_project_setting);

            if(EQ_Charge::update_charge_script($equipment, 'test_project', ['%options'=>$params])){
                EQ_Charge::put_charge_setting($equipment, 'test_project', $test_project_setting);

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('test_project', '项目计费信息已更新'));
            }
        }

        $e->return_value = V('test_project:charge/test_project', ['equipment' => $equipment,
            'charge_title' => I18N::T($i18n_module, $charge_title),
            'charge_default_setting' => $charge_default_setting,
            'test_projects' => $test_projects,
            ]);
        return FALSE;
    }

    public static function extra_form_validate($e, $equipment, $type, $form)
    {
        if ($type != 'eq_sample') {
            return;
        }

        foreach ($form['test_project'] as $id => $val) {
            if ($val == 'on' && $form['test_project_number'][$id] < 0 ) {
                $form->set_error("test_project_number[$id]", I18N::T('test_project', "选用项目数量不能小于0"));
            }
        }
    }

    public static function get_enable_use_test_projects_by_eq($equipment)
    {
        $test_projects = $equipment->id ? Q("test_project[equipment=$equipment][enable_use=1][!hidden]") : [];
        $test_projects_by_cat = [];
        //按项目分类整理
        foreach ($test_projects as $test_project) {
            $test_projects_by_cat[$test_project->test_project_cat->id][] = $test_project;
        }
        ksort($test_projects_by_cat);
        return $test_projects_by_cat;
    }

    public static function get_enable_sample_test_projects_by_eq($equipment)
    {
        $test_projects = $equipment->id ? Q("test_project[equipment=$equipment][enable_sample=1][!hidden]") : [];
        $test_projects_by_cat = [];
        //按项目分类整理
        foreach ($test_projects as $test_project) {
            $test_projects_by_cat[$test_project->test_project_cat->id][] = $test_project;
        }
        ksort($test_projects_by_cat);
        return $test_projects_by_cat;
    }

    public static function get_test_project_items($e, $equipment, $params)
    {
        $result = [];
        $test_project_items = Q("test_project[equipment={$equipment}][!hidden]");
        foreach($test_project_items as $item) {
            $result[$item->id] = $item->name;
        }
        $e->return_value = $result;
    }

}
