<?php

class Apply_Controller extends Base_Controller
{
    public function delete($id = 0)
    {
        $apply = O('service_apply', $id);

        if (!$apply->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');
        if (!$me->is_allowed_to('删除', $apply)) {
            URI::redirect('error/401');
        }

        if ($apply->delete()) {
            $apply_attachments_dir_path = NFS::get_path($apply, '', 'attachments', true);
            File::rmdir($apply_attachments_dir_path);
            Lab::message(Lab::MESSAGE_NORMAL, '删除成功');
        }

        URI::redirect($apply->service->url('apply', null, null, 'view'));
    }

    public function download($id)
    {
        $me = L('ME');
        $apply = O('service_apply', $id);
        if (!$apply->id) {
            URI::redirect('error/404');
        }
        if (!$me->is_allowed_to('下载结果', $apply)) {
            URI::redirect('error/401');
        }
        if ($result_file = Technical_Service::create_apply_result($apply)) {
            Downloader::download($result_file);
        }
    }
}

class Apply_AJAX_Controller extends AJAX_Controller
{
    public function index_add_click()
    {
        $me = L('ME');

        $form = Input::form();
        $service_id = $form['service_id'];
        $service = O('service', $service_id);

        if (!$service->id) URI::redirect('error/404');

        if (!$me->is_allowed_to('预约服务', $service)) {
            URI::redirect('error/401');
        }

        JS::dialog(V('apply/add', [
            'service' => $service,
            'form' => $form,
        ]), ['title' => '添加服务预约']);
    }

    public function index_add_submit()
    {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $service_id = $form['service_id'];
        $service = O('service', $service_id);

        if (!$service->id) URI::redirect('error/404');

        if (!$me->is_allowed_to('预约服务', $service)) {
            URI::redirect('error/401');
        }

        if ($form['submit']) {

            $now = time();
            $form->validate('service_id', 'not_empty', I18N::T('technical_service', '请先选择服务！'));
            $form->validate('dtrequest', 'not_empty', I18N::T('technical_service', '请选择期望完成时间！'));
            $form->validate('dtrequest', "compare(>{$now})", I18N::T('technical_service', '期望完成时间须大于当前时间！'));
            $form->validate('samples', 'not_empty', I18N::T('technical_service', '请填写样品数！'));
            $form->validate('samples', 'compare(>0)', I18N::T('technical_service', '样品数不能为0！'));

            if (!is_numeric($form['samples']) || intval($form['samples']) != $form['samples']) {
                $form->set_error('samples', I18N::T('equipments', '样品数填写有误, 请填写大于0的整数!'));
            }

            Event::trigger('extra.form.validate', $service, 'apply', $form);
            if ($form->no_error) {
                $apply = O('service_apply');
                $apply->user = $me;
                $apply->service = $service;
                $apply->dtrequest = $form['dtrequest'];
                $apply->samples = $form['samples'];
                $apply->status = Service_Apply_Model::STATUS_APPLY;
                $apply->samples_description = $form['samples_description'];

                if ($apply->save()) {

                    Event::trigger('extra.form.post_submit', $apply, $form);

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('technical_service', '申请成功,请等待管理员审核'));
                    JS::redirect($apply->service->url('apply', null, null, 'view'));
                }
            }

        }

        JS::dialog(V('apply/add', [
            'service' => $service,
            'form' => $form,
        ]), ['title' => '添加服务预约']);

    }

    public function index_edit_click()
    {
        $me = L('ME');

        $form = Input::form();
        $apply = O('service_apply', $form['apply_id']);

        if (!$apply->id) URI::redirect('error/404');

        if (!$me->is_allowed_to('修改', $apply)) {
            URI::redirect('error/401');
        }

        JS::dialog(V('apply/edit', [
            'apply' => $apply,
            'service' => $apply->service,
            'form' => $form,
        ]), ['title' => '修改服务预约']);
    }

    public function index_edit_submit()
    {
        $me = L('ME');

        $form = Form::filter(Input::form());
        $apply = O('service_apply', $form['apply_id']);

        if (!$apply->id) URI::redirect('error/404');

        if (!$me->is_allowed_to('修改', $apply)) {
            URI::redirect('error/401');
        }

        if ($form['submit']) {

            $now = time();
            $form->validate('dtrequest', 'not_empty', I18N::T('technical_service', '请选择期望完成时间！'));
            $form->validate('dtrequest', "compare(>{$now})", I18N::T('technical_service', '期望完成时间须大于当前时间！'));
            $form->validate('samples', 'not_empty', I18N::T('technical_service', '请填写样品数！'));
            $form->validate('samples', 'compare(>0)', I18N::T('technical_service', '样品数不能为0！'));

            if (!is_numeric($form['samples']) || intval($form['samples']) != $form['samples']) {
                $form->set_error('samples', I18N::T('equipments', '样品数填写有误, 请填写大于0的整数!'));
            }

            Event::trigger('extra.form.validate', $apply->service, 'apply', $form);
            if ($form->no_error) {

                $apply->user = $me;
                $apply->dtrequest = $form['dtrequest'];
                $apply->samples = $form['samples'];
                $apply->status = Service_Apply_Model::STATUS_APPLY;
                $apply->samples_description = $form['samples_description'];

                if ($apply->save()) {

                    Event::trigger('extra.form.post_submit', $apply, $form);

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('technical_service', '修改成功,请等待管理员审核'));
                    JS::redirect($apply->service->url('apply', null, null, 'view'));
                }
            }

        }

        JS::dialog(V('apply/edit', [
            'apply' => $apply,
            'service' => $apply->service,
            'form' => $form,
        ]), ['title' => '修改服务预约']);

    }

    public function index_approval_click()
    {
        $me = L('ME');

        $form = Input::form();
        $apply_id = $form['apply_id'];
        $apply = O('service_apply', $apply_id);

        if (!$apply->id) URI::redirect('error/404');

        if (!$me->is_allowed_to('审批', $apply)) {
            URI::redirect('error/401');
        }

        $status = EQ_Status_Model::IN_SERVICE;
        $projects = [];
        foreach (Q("equipment[status={$status}].equipment service_equipment[service={$apply->service}]") as $connect) {
            if (!$connect->project->id) continue;
            $projects[$connect->project->id][$connect->equipment->id] = $connect->equipment->name;
        }

        JS::dialog(V('apply/approval', [
            'apply' => $apply,
            'service' => $apply->service,
            'form' => $form,
            'projects' => $projects,
        ]), ['title' => '服务预约审批']);

    }

    public function index_approval_submit()
    {
        $me = L('ME');

        $form = Form::filter(Input::form());
        $apply_id = $form['apply_id'];
        $apply = O('service_apply', $apply_id);

        if (!$apply->id) URI::redirect('error/404');

        if (!$me->is_allowed_to('审批', $apply)) {
            URI::redirect('error/401');
        }

        $connects = [];

        if ($form['submit'] == 'reject') {
            $apply->status = Service_Apply_Model::STATUS_REJECT;
            $apply->approval_user = $me;
            $apply->approval_time = time();
            $apply->save();
            Lab::message(Lab::MESSAGE_NORMAL, '已审批驳回');
            JS::redirect(URI::url("!technical_service/service/index.{$apply->service->id}.apply"));
            return;
        }

        if ($form['submit'] && JS::confirm('是否确认该申请单，提交后将无法修改？')) {

            $form->validate('user', 'not_empty', I18N::T('technical_service', '请选择预约者！'));
            $form->validate('samples', 'compare(>=1)', I18N::T('technical_service', '样品数需大于0！'));
            $form->validate('dtrequest', 'not_empty', I18N::T('technical_service', '请选择期望完成时间！'));

            if (!is_numeric($form['samples']) || intval($form['samples']) != $form['samples']) {
                $form->set_error('samples', I18N::T('equipments', '样品数填写有误, 请填写大于0的整数!'));
            }

            if ($form['dtrequest'] <= time()) {
                $form->set_error('dtrequest', '期望完成时间需大于当前时间');
            }

            if (in_array(-1, $form['project_equipment'])) {
                $k = array_search(-1, $form['project_equipment']);
                $form->set_error("project_equipment[{$k}]", '请选择仪器');
            }

            foreach ($form['project_equipment'] as $project_id => $equipment_id) {
                $project = O('service_project', $project_id);
                $equipment = O('equipment', $equipment_id);
                if ($equipment->status != EQ_Status_Model::IN_SERVICE) {
                    $form->set_error("project_equipment[{$k}]", I18N::T('equipments', '请选择在用的仪器!'));
                }
                $user = O('user', $form['user']);
                $amount_k = "{$apply->id}_{$project->id}_amount";
                if (Event::trigger('apply.judge.balance', $apply, $project, $equipment, $user, $form)) {
                    $form->set_error("$amount_k", I18N::T('equipments', '使用者所在课题组余额不足!'));
                }
            }
            if ($form->no_error) {

                $total_amount = 0;

                foreach ($form['project_equipment'] as $project_id => $equipment_id) {
                    $amount_k = "{$apply->id}_{$project_id}_amount";
                    $project = O('service_project', $project_id);
                    $equipment = O('equipment', $equipment_id);
                    $record = O('service_apply_record', ['apply' => $apply, 'equipment' => $equipment, 'project' => $project]);
                    $record->apply = $apply;
                    $record->equipment = $equipment;
                    $record->service = $apply->service;
                    $record->user = O('user', $form['user']);
                    $record->project = $project;
                    $record->samples = $form['samples'];
                    $record->success_samples = 0;
                    if ($record->save()) {
                        $eq_charge = O('eq_charge', ['source' => $record]);
                        $eq_charge->source = $record;
                        $eq_charge->equipment = $equipment;
                        $eq_charge->user = $record->user;
                        $fee = Technical_Service::calculate_amount($record, $form);
                        if ($form[$amount_k] == $fee['fee']) {
                            $eq_charge->custom = 0;
                            $eq_charge->amount = (double)$fee['fee'];
                            $eq_charge->auto_amount = (double)$fee['fee'];
                            $eq_charge->description = $fee['description'];
                        } else {
                            $eq_charge->custom = 1;
                            $eq_charge->amount = $form[$amount_k];
                            $eq_charge->auto_amount = (double)$fee['fee'];
                            $eq_charge->description = (double)$fee['description'];
                        }
                        $eq_charge->save();
                        $total_amount += $eq_charge->amount;
                    }
                }

                $apply->user = $form['user'] ? O('user', $form['user']) : $apply->user;
                $apply->approval_user = $me;
                $apply->status = Service_Apply_Model::STATUS_PASS;
                $apply->approval_time = time();
                $apply->amount = $total_amount;
                $apply->dtrequest = $form['dtrequest'];
                $apply->samples = $form['samples'];
                $apply->samples_description = $form['samples_description'];
                $apply->save();
                Event::trigger('extra.form.post_submit', $apply, $form);

                Lab::message(Lab::MESSAGE_NORMAL, '已审批通过');
                JS::redirect(URI::url("!technical_service/service/index.{$apply->service->id}.apply"));
                return;

            }

            foreach ($form['project_equipment'] as $project_id => $equipment_id) {
                if ($equipment_id && $equipment_id != -1) {
                    $project = O('service_project', $project_id);
                    $equipment = O('equipment', $equipment_id);
                    $connect = Q("service_equipment[service={$apply->service}][project={$project}][equipment={$equipment}]")->current();
                    $connects[] = [
                        'connect' => $connect,
                    ];
                }
            }
        }

        $projects = [];
        $status = EQ_Status_Model::IN_SERVICE;
        foreach (Q("equipment[status={$status}].equipment service_equipment[service={$apply->service}]") as $connect) {
            $projects[$connect->project->id][$connect->equipment->id] = $connect->equipment->name;
        }

        JS::dialog(V('apply/approval', [
            'apply' => $apply,
            'connects' => $connects,
            'service' => $apply->service,
            'form' => $form,
            'projects' => $projects,
        ]), ['title' => $apply->service->name . '"的预约审批']);

    }


    public function index_select_project_equipment_click()
    {
        $form = Input::form();
        $project = O('service_project', $form['project_id']);
        $equipment = O('equipment', $form['equipment_id']);
        $apply = O('service_apply', $form['apply_id']);
        $user = $form['user_id'] ? O('user', $form['user_id']) : $apply->user;
        $sample_counts = $form['samples'] ?? $apply->samples;

        if (!$project->id || !$equipment->id || !$apply->id) {
            Output::$AJAX['complete'] = [
                'html' => (string)V('apply/edit/detail_amount', ['error' => true, 'messages' => '缺少关键信息,请刷新页面重试']),
                'special' => TRUE
            ];
            return;
        }

        $connect = Q("service_equipment[service={$apply->service}][project={$project}][equipment={$equipment}]")->current();
        $apply_record = O('service_apply_record');
        $apply_record->equipment = $connect->equipment;
        $apply_record->user = $user;
        $apply_record->project = $project;
        $apply_record->apply = $apply;
        $apply_record->samples = $sample_counts;
        $fee_result = Technical_Service::calculate_amount($apply_record, $form);

        Output::$AJAX['complete'] = [
            'html' => (string)V('apply/edit/project_amount', [
                'connect' => $connect,
                'apply' => $apply,
                'fee_result' => $fee_result,
            ]),
            'special' => TRUE
        ];

    }

    public function index_edit_projects_item_click()
    {
        $form = Input::form();
        $project = O('service_project', $form['value']);
        $service = O('service', $form['service_id']);
        $eqs = [];
        if ($project->id) {
            //先查当前服务的仪器
            $service_projects = Q("service_equipment[project={$project}][service={$service}]");
            if ($service_projects->total_count()) {
                $eqs[$service_projects->equipment->id] = $service_projects->equipment->name;
            } else {
                foreach (Q("{$project} equipment") as $eq) {
                    $eqs[$eq->id] = $eq->name;
                }
            }
        }

        Output::$AJAX['complete'] = [
            'html' => (string)V('service/edit.project.tr', ['flexform_index' => $form['flexform_index'], 'eqs' => $eqs]),
            'special' => TRUE
        ];
    }

    public function index_detail_click()
    {
        $me = L('ME');

        $form = Input::form();
        $apply_id = $form['apply_id'];
        $apply = O('service_apply', $apply_id);

        if (!$apply->id) URI::redirect('error/404');

        if (!$me->is_allowed_to('查看', $apply)) {
            URI::redirect('error/401');
        }

        $records = Q("service_apply_record[apply={$apply}]");

        JS::dialog(V('apply/detail', [
            'apply' => $apply,
            'service' => $apply->service,
            'records' => $records,
        ]), ['title' => '服务项目进程']);

    }

    public function index_delete_click()
    {
        $me = L('ME');
        $form = Input::form();
        $apply = O('service_apply', $form['apply_id']);
        if (!$apply->id) URI::redirect('error/404');

        if (!$me->is_allowed_to('删除', $apply)) {
            URI::redirect('error/401');
        }

        if (JS::confirm('确定删除申请吗?删除之后无法恢复!')) {
            if ($apply->delete()) {
                //删除预约后 如果是已审核状态 删除关联的项目检测
                Event::trigger('service_apply.delete', $apply);
                $apply_attachments_dir_path = NFS::get_path($apply, '', 'attachments', true);
                File::rmdir($apply_attachments_dir_path);
                Lab::message(Lab::MESSAGE_NORMAL, '预约删除成功');
            } else {
                Lab::message(Lab::MESSAGE_ERROR, '预约删除失败,请稍后重试');
            }

            JS::refresh();
        }
    }

    function index_export_result_click()
    {

        $form = Input::form();

        $id = $form['id'];

        $apply = O('service_apply', $id);

        if (!L('ME')->is_allowed_to('下载结果', $apply)) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('technical_service', '您没有权限进行此操作!'));
            JS::redirect();
            return FALSE;
        }

        try {

            $file_name_time = microtime(TRUE);
            $file_name_arr = explode('.', $file_name_time);
            $file_name = $file_name_arr[0] . $file_name_arr[1];

            $this->_export_result($form, $file_name);
            JS::dialog(V('export_wait_zip', [
                'file_name' => $file_name,
                'ext' => 'zip',
            ]), [
                'title' => I18N::T('calendars', '导出等待')
            ]);

        } catch (Error_Exception $e) {

            Lab::message(Lab::MESSAGE_ERROR, I18N::T('technical_service', '操作超时, 请刷新页面后重试!'));
            JS::redirect();
            return FALSE;

        }

    }

    function _export_result($form, $file_name)
    {
        $me = L('ME');
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_apply export_results ';
        $cmd .= "'" . json_encode($form, JSON_UNESCAPED_UNICODE) . "' '" . $file_name . "' >/dev/null 2>&1 &";
        error_log($cmd);
        exec($cmd, $output);
    }

}

