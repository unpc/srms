<?php

class Test_Project_AJAX_Controller extends AJAX_Controller
{
    function index_test_project_cat_click()
    {
        $form = Input::form();

        $equipment = O('equipment', $form['equipment_id']);

        if (!$equipment->id) return;

        if (!L('ME')->is_allowed_to('修改使用设置', $equipment)) return;

        JS::dialog(V('test_project:cat/add', ['equipment'=>$equipment]),[
            'title' => I18N::T('test_project', '添加分类'),
        ]);
    }

    function index_test_project_cat_submit()
    {
        $form = Form::filter(Input::form());
        $me = L('ME');

        $equipment = O('equipment', $form['equipment_id']);
        if (!$equipment->id) return;

        if (!$me->is_allowed_to('修改使用设置', $equipment)) return;

        if (!$form['name']) {
            $form->set_error('name', I18N::T('test_project', '请填写分类名称!'));
        } elseif (mb_strlen($form['name']) > 50) {
            $form->set_error('name', I18N::T('test_project', '分类名称不得大于50个字!'));
        }

        $exists = Q("test_project_cat[equipment={$equipment}][name={$form['name']}]")->total_count();
        if ($exists) {
            $form->set_error('name', I18N::T('test_project', '该分类已存在!'));
        }

        if ($form->no_error) {
            $test_project_cat = O('test_project_cat');
            $test_project_cat->equipment = $equipment;
            $test_project_cat->name = $form['name'];
            if ($test_project_cat->save()) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('test_project', '添加成功!'));
                Log::add(strtr('[test_project] %user_name[%user_id] 给仪器 %equipment_name[%equipment_id] 添加了项目分类 %cat_name', [
                    '%user_name'=> $me->name,
                    '%user_id'=> $me->id,
                    '%equipment_name'=> $equipment->name,
                    '%equipment_id'=> $equipment->id,
                    '%cat_name'=> $test_project_cat->name,
                ]), 'journal');
            }else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('test_project', '添加失败!'));
            }
            JS::refresh();
        }else {
            JS::dialog(V('test_project:cat/add', ['equipment' => $equipment, 'form' => $form]),[
                'title' => I18N::T('test_project', '添加分类'),
            ]);
        }
    }
}
