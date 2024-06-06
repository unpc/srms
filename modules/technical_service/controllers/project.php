<?php

class Project_Controller extends Layout_Controller
{
}

class Project_AJAX_Controller extends AJAX_Controller
{
    public function index_add_project_click()
    {
        $me = L('ME');
        if ($me->is_Allowed_To('管理服务项目', 'technical_service')) {
            JS::dialog(V('project/add'), ['title' => '添加项目']);
        }
    }

    public function index_add_project_submit()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('管理服务项目', 'technical_service')) {
            URI::redirect('error/401');
        }

        if (Input::form('submit')) {

            $form = Form::filter(Input::form())
                ->validate('name', 'not_empty', I18N::T('technical_service', '请填写项目名称！'));

            $exists = Q("service_project[name={$form['name']}]")->total_count();
            if ($exists->id) $form->set_error('name', '已存在该名称的项目');

            if ($form->no_error) {

                $project = O('service_project');
                $project->ref_no = trim($form['ref_no']);
                $project->name = trim($form['name']);
                if ($project->save()) {
                    if ($form['eqs']) {
                        $eqs = $form['eqs'];
                        foreach ($eqs as $eq) {
                            $equipment = O('equipment', $eq);
                            $equipment->id ? $project->connect($equipment) : '';
                        }
                    }
                }

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('technical_service', '项目添加成功!'));
                JS::redirect(URI::url('admin/service.project'));

            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '项目添加失败! 请与系统管理员联系。'));
            }
        }

        JS::dialog(V('project/add', ['form' => $form]), [
            'title' => I18N::T('technical_service', '添加项目'),
        ]);
    }

    public function index_edit_project_click()
    {
        $me = L('ME');
        $form = Input::form();

        $project = O('service_project', $form['project_id']);

        if ($me->is_allowed_To('修改', $project)) {

            $connects = Q("{$project} equipment");
            $eqs = [];
            foreach ($connects as $connect) {
                $eqs[] = $connect->id;
            }

            JS::dialog(V('project/edit', ['project' => $project, 'eqs' => $eqs]), ['title' => '修改项目']);

        }
    }

    public function index_edit_project_submit()
    {
        $me = L('ME');

        $form = Form::filter(Input::form());
        $project = O('service_project', $form['project_id']);

        if (!$me->is_allowed_to('修改', $project)) {
            URI::redirect('error/401');
        }

        if ($form['submit']) {

            $form->validate('name', 'not_empty', I18N::T('technical_service', '请填写项目名称！'));
            $exists = Q("service_project[name={$form['name']}][id!={$project->id}]")->total_count();
            if ($exists->id) $form->set_error('name', '已存在该名称的项目');

            if ($form->no_error) {

                $project->ref_no = trim($form['ref_no']);
                $project->name = trim($form['name']);

                if ($project->save()) {

                    $delete = [];//删除掉的仪器

                    foreach (Q("{$project} equipment") as $equipment) {
                        $project->disconnect($equipment);
                        $delete[$equipment->id] = $equipment;
                    }

                    if ($form['eqs']) {
                        $eqs = $form['eqs'];
                        foreach ($eqs as $eq) {
                            $equipment = O('equipment', $eq);
                            $equipment->id ? $project->connect($equipment) : '';
                            if (isset($delete[$equipment->id])) {
                                unset($delete[$equipment->id]);
                            }
                        }
                    }
                    if (!empty($delete)) {
                        foreach ($delete as $deq) {
                            Q("service_equipment[project={$project}][equipment={$deq}]")->delete_all();
                        }
                    }
                }

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('technical_service', '项目修改成功!'));
                JS::redirect(URI::url('admin/service.project'));

            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '项目修改失败! 请与系统管理员联系。'));
            }
        }

        JS::dialog(V('project/edit', ['form' => $form, 'project' => $project]), [
            'title' => I18N::T('technical_service', '修改项目'),
        ]);
    }

}