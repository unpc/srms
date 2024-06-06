<?php

class Service_Controller extends Base_Controller
{

    public function index($id = 0, $tab = 'info')
    {
        $service = O('service', $id);
        $me = L('ME');
        if (!$service->id) {
            URI::redirect('error/404');
        }
        if (!$me->is_allowed_to('查看', $service)) {
            URI::redirect('error/401');
        }

        $this->layout->body->primary_tabs
            ->add_tab('view', ['url' => $service->url(), 'title' => H($service->name)])
            ->select('view');

        $content = V('technical_service:service/view');
        $content->service = $service;

        Event::bind('service.index.tab.content', [$this, '_index_info'], 0, 'info');
        Event::bind('service.index.tab.content', [$this, '_index_apply'], 0, 'apply');
        Event::bind('service.index.tab.content', [$this, '_index_equipment'], 0, 'equipment');

        $this->layout->body->primary_tabs
            = Widget::factory('tabs')
            ->set('service', $service)
            ->tab_event('service.index.tab')
            ->content_event('service.index.tab.content')
            ->tool_event('service.index.tab.tool_box');

        $this->layout->body->primary_tabs
            ->add_tab('info', [
                'url' => $service->url('info'),
                'title' => I18N::T('technical_service', '服务详情'),
                'weight' => 0,
            ])->add_tab('apply', [
                'url' => $service->url('apply'),
                'title' => I18N::T('technical_service', '服务预约'),
                'weight' => 60,
            ])->add_tab('equipment', [
                'url' => $service->url('equipment'),
                'title' => I18N::T('technical_service', '关联设备'),
                'weight' => 70,
            ]);


        $this->layout->body->primary_tabs->select($tab);

        $breadcrumbs = [
            [
                'url' => '!technical_service/list/index',
                'title' => I18N::T('technical_service', '技术服务'),
            ],
            [
                'title' => $service->name,
            ]
        ];

        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);
        $this->layout->header_content = V('service/header_content', ['service' => $service]);
        $this->layout->title = I18N::T('service', '');
    }

    public function _index_info($e, $tabs)
    {
        $service = $tabs->service;
        $me = L("ME");
        $sections = new ArrayIterator;
        $sections[] = V('technical_service:service/info_base')
            ->set('service', $service);

        $tabs->content = V('service/info', ['sections' => $sections]);
    }

    public function _index_equipment($e, $tabs)
    {
        $service = $tabs->service;

        $me = L("ME");
        $form = Input::form();
        $start = (int)$form['st'];
        $per_page = Config::get('per_page.service', 25);
        $start = $start - ($start % $per_page);

        $sql = "SELECT * FROM `service_equipment` WHERE service_id={$service->id} GROUP BY equipment_id ORDER BY `project_id` DESC limit {$start},{$per_page}";
        $connects = Database::factory()->query($sql)->rows();

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start' => $start,
            'per_page' => $per_page,
            'total' => count($connects),
        ]);
        $tabs->content = V('technical_service:service/info_equipment', [
            'service' => $service,
            'connects' => $connects,
            'pagination' => $pagination,
        ]);

    }

    public function _index_apply($e, $tabs)
    {
        $service = $tabs->service;

        $me = L("ME");
        $form = Input::form();

        $selector = "service_apply:sort('ctime D')";
        $pre_selector = [];

        if ($service->id) {
            $pre_selector['service'] = "{$service}";
        }

        if ($form['ref_no']) {
            $ref = trim($form['ref_no']);
            $selector .= "[ref_no*={$ref}]";
        }

        if ($form['user']) {
            $user = O('user', $form['user']);
            $selector .= "[user={$user}]";
        }

        if ($form['ctime_s']) {
            $start_time = $form['ctime_s'];
            $selector .= "[ctime>={$start_time}]";
        }
        if ($form['ctime_e']) {
            $end_time = $form['ctime_e'];
            $selector .= "[ctime<={$end_time}]";
        }

        if ($form['dtrequest_s']) {
            $start_time = $form['dtrequest_s'];
            $selector .= "[dtrequest>={$start_time}]";
        }
        if ($form['dtrequest_e']) {
            $end_time = $form['dtrequest_e'];
            $selector .= "[dtrequest<={$end_time}]";
        }

        if (isset($form['status']) && $form['status'] != -1) {
            $selector .= "[status={$form['status']}]";
        }
        if (!$me->is_allowed_to('修改', $service)) {
            $selector .= "[user={$me}]";
        }

        if (!empty($pre_selector)) {
            $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
        }

        $applys = Q($selector);

        $start = (int)$form['st'];
        $per_page = Config::get('per_page.service', 25);
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($applys, $start, $per_page);

        $tabs->content = V('technical_service:apply/list', [
            'service' => $service,
            'applys' => $applys,
            'form' => $form,
            'pagination' => $pagination,
        ]);

    }

    public function edit($id = 0, $tab = 'info')
    {

        $service = O('service', $id);
        $me = L('ME');

        if (!$service->id) {
            URI::redirect('error/404');
        }

        if (!$me->is_allowed_to('修改', $service)) {
            URI::redirect('error/401');
        }

        Event::bind('service.edit.content', [$this, '_edit_info'], 0, 'info');
        Event::bind('service.edit.content', [$this, '_edit_photo'], 0, 'photo');
        Event::bind('service.edit.content', [$this, '_edit_projects'], 0, 'project');
        Event::bind('service.edit.content', [$this, '_edit_extra'], 0, 'extra');

        $content = V('service/edit', ['service' => $service]);
        $content->service = $service;

        $this->layout->body->primary_tabs
            = Widget::factory('tabs')
            ->add_tab('info', [
                'url' => $service->url('info', null, null, 'edit'),
                'title' => I18N::T('technical_service', '基本信息'),
            ])->add_tab('project', [
                'url' => $service->url('project', null, null, 'edit'),
                'title' => I18N::T('technical_service', '项目设置'),
            ])->add_tab('extra', [
                'url' => $service->url('extra', null, null, 'edit'),
                'title' => I18N::T('technical_service', '预约表单'),
            ]);

        $this->layout->title = H($service->name);
        $breadcrumbs = [
            [
                'url' => '!technical_service/index',
                'title' => I18N::T('technical_service', '技术服务'),
            ],
            [
                'url' => $service->url('view'),
                'title' => $service->name,
            ],
            [
                'title' => '修改',
            ],
        ];
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);
        $this->layout->body->primary_tabs
            ->set('service', $service)
            ->tab_event('service.edit.tab')
            ->content_event('service.edit.content')
            ->select($tab);
    }

    public function _edit_info($e, $tabs)
    {

        $service = $tabs->service;
        $me = L('ME');

        $form = Form::filter(Input::form());

        if (Input::form('submit') == '上传图标') {
            $this->_edit_photo($e, $tabs);
            return;
        }

        if ($form['submit']) {
            $form->validate('name', 'not_empty', I18N::T('technical_service', '请填写服务名称！'));
            $form->validate('ref_no', 'not_empty', I18N::T('technical_service', '请填写服务编号！'));
            $exists = Q("service[ref_no={$form['ref_no']}][id!={$service->id}]")->current();
            if ($exists->id) $form->set_error('ref_no', '服务编号已存在');
            $form->validate('billing_department', 'not_empty', I18N::T('technical_service', '请选择收费平台！'));
            $form->validate('service_type', 'not_empty', I18N::T('technical_service', '请选择服务分类！'));
            if ($form['service_type'] == Tag_Model::root('service_type')->id)
                $form->set_error('service_type', '请选择服务分类！');
            $incharges = $form['incharges'] ? @json_decode($form['incharges'], true) : [];
            if ($me->is_allowed_to('修改负责人',$service) && count($incharges) == 0) {
                $form->set_error('incharges', I18N::T('technical_service', '请选择负责人!'));
            }
            $form->validate('phones', 'not_empty', I18N::T('technical_service', '请填写联系方式！'));
            $form->validate('emails', 'not_empty', I18N::T('technical_service', '请填写联系邮箱！'));
            $form->validate('intervals', 'compare(>=0)', I18N::T('technical_service', '服务周期不能小于0'));

            if ($form->no_error) {

                $service->name = trim($form['name']);
                $service->ref_no = trim($form['ref_no']);
                $service->billing_department_id = $form['billing_department'];
                $service->service_type_id = $form['service_type'];
                $service->group_id = $form['group_id'];
                $service->intervals = $form['intervals'];
                $service->intervals_format = $form['intervals_format'];
                $service->description = $form['description'];
                $service->attentions = $form['attentions'];
                $service->sample_requires = $form['sample_requires'];
                $service->charge_settings = $form['charge_settings'];
                $service->phones = $form['phones'];
                $service->emails = $form['emails'];
                $service->creator = L('ME');

                if ($service->save()) {
                    if ($me->is_allowed_to('修改负责人',$service) && isset($form['incharges'])) {
                        foreach (Q("{$service}<incharge user") as $u) {
                            $u->disconnect($service, 'incharge');
                        }
                        $incharges = $form['incharges'] ? json_decode($form['incharges'], true) : [];
                        foreach ($incharges as $charge_id => $cname)
                            O('user', $charge_id)->connect($service, 'incharge');
                    }
                    $group_root = Tag_Model::root('group');
                    $group_root->disconnect($service);
                    $group = O('tag_group', $form['group_id']);
                    $group->id ? $group->connect($service) : '';
                }

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('technical_service', '修改成功'));
                URI::redirect();

            }
        }

        $tabs->content = V('service/edit.info', ['service' => $service, 'form' => $form]);

    }

    public function _edit_projects($e, $tabs)
    {

        $service = $tabs->service;

        $form = Form::filter(Input::form());

        if ($form['submit']) {
            error_log(print_r($form,true));
            foreach ($form['project_id'] as $index => $project_id) {
                $project = O('service_project', $project_id);
                if (!$project->id){
                    $form->set_error("project_id[<?= $project_id?>]", '请选择项目');
                    continue;
                }
                $eqs = $form['eqs'][$index] ?? [];
                if (empty($eqs)) $form->set_error("project_id[<?= $project_id?>]", $project->name .'请选择至少一台仪器');
                foreach ($eqs as $eqid) {
                    $equipment = O('equipment', $eqid);
                    //检测如果没有绑定项目，则不让增加
                    if (!Q("{$equipment} {$project}")->total_count()) {
                        $form->set_error("project_id[<?= $project_id?>]", $equipment->name.'不支持检测'.$project->name);
                    }
                }
            }

            if ($form->no_error) {
                Q("service_equipment[service={$service}]")->delete_all();
                foreach ($form['project_id'] as $index => $project_id) {
                    $project = O('service_project', $project_id);
                    $eqs = $form['eqs'][$index] ?? [];
                    foreach ($eqs as $eqid) {
                        $equipment = O('equipment', $eqid);
                        $connect = O('service_equipment', ['service' => $service, 'project' => $project, 'equipment' => $equipment]);
                        $connect->service = $service;
                        $connect->project = $project;
                        $connect->equipment = $equipment;
                        $connect->save();
                    }
                }

                Lab::message(Lab::MESSAGE_NORMAL, '保存成功');

                URI::redirect();

            }

        }

        $tabs->content = V('service/edit.projects', ['service' => $service, 'form' => $form]);

    }

    public function _edit_extra($e, $tabs)
    {

        $service = $tabs->service;

        $form = Form::filter(Input::form());
        if ($form['submit']) {
        }

        $tabs->content = V('service/edit.extra', ['service' => $service, 'form' => $form]);

    }

    public function _edit_photo($e, $tabs)
    {
        $service = $tabs->service;

        if (Input::form('submit')) {
            $file = Input::file('file');
            if ($file['tmp_name']) {
                try {
                    $ext = File::extension($file['name']);
                    $image = Image::load($file['tmp_name'], $ext);
                    $service->save_icon($image);
                    $me = L('ME');
                    Log::add(strtr('[technical_service] %user_name[%user_id]修改了服务项目%service_name[%service_id]的图标', ['%user_name' => $me->name, '%user_id' => $me->id, '%service_name' => $service->name, '%service_id' => $service->id]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('technical_service', '服务项目图标已更新'));
                } catch (Error_Exception $e) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '服务项目图标更新失败!'));
                }
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '请选择您要上传的服务项目图标文件!'));
            }
        }
        $tabs->content = V('service/edit.photo');
    }

    public function delete_photo($id = 0)
    {
        $service = O('service', $id);
        if (!L('ME')->is_allowed_to('修改', $service)) {
            URI::redirect('error/401');
        }
        if (!$service->id) {
            URI::redirect('error/401');
        }

        $service->delete_icon();
        $me = L('ME');

        Log::add(strtr('[technical_service] %user_name[%user_id]修改了服务项目%service_name[%service_id]的图标', ['%user_name' => $me->name, '%user_id' => $me->id, '%service_name' => $service->name, '%service_id' => $service->id]), 'journal');

        URI::redirect('!technical_service/service/edit.' . $service->id . '.photo');
    }

    public function delete($id = 0)
    {
        $service = O('service', $id);

        if (!$service->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');
        if (!$me->is_allowed_to('删除', $service)) {
            URI::redirect('error/401');
        }

        if ($service->delete()) {
            $service_attachments_dir_path = NFS::get_path($service, '', 'attachments', true);
            File::rmdir($service_attachments_dir_path);
            Lab::message(Lab::MESSAGE_NORMAL, '删除成功');
        }

        URI::redirect('!technical_service/list');

    }

}

class Service_AJAX_Controller extends AJAX_Controller
{
    public function index_add_service_click()
    {
        $me = L('ME');
        if (!$me->is_allowed_to('添加', 'service')) {
            URI::redirect('error/401');
        }

        JS::dialog(V('service/add'), ['title' => '添加技术服务']);
    }

    public function index_add_service_submit()
    {
        $me = L('ME');
        if (!$me->is_allowed_to('添加', 'service')) {
            URI::redirect('error/401');
        }

        $form = Form::filter(Input::form());

        if ($form['submit']) {
            $form->validate('name', 'not_empty', I18N::T('technical_service', '请填写服务名称！'));
            $form->validate('ref_no', 'not_empty', I18N::T('technical_service', '请填写服务编号！'));
            $exists = Q("service[ref_no={$form['ref_no']}]")->current();
            if ($exists->id) $form->set_error('ref_no', '服务编号已存在');
            $form->validate('billing_department', 'not_empty', I18N::T('technical_service', '请选择收费平台！'));
            $form->validate('service_type', 'not_empty', I18N::T('technical_service', '请选择服务分类！'));
            if ($form['service_type'] == Tag_Model::root('service_type')->id)
                $form->set_error('service_type', '请选择服务分类！');

            $incharges = $form['incharges'] ? @json_decode($form['incharges'], true) : [];
            if (count($incharges) == 0) {
                $form->set_error('incharges', I18N::T('technical_service', '请选择负责人!'));
            }
            $form->validate('phones', 'not_empty', I18N::T('technical_service', '请填写联系方式！'));
            $form->validate('emails', 'not_empty', I18N::T('technical_service', '请填写联系邮箱！'));
            $form->validate('intervals', 'compare(>=0)', I18N::T('technical_service', '服务周期不能小于0'));

            if ($form->no_error) {
                $service = O('service');
                $service->name = trim($form['name']);
                $service->ref_no = trim($form['ref_no']);
                $service->billing_department_id = $form['billing_department'];
                $service->service_type_id = $form['service_type'];
                $service->group_id = $form['group_id'];
                $service->intervals = $form['intervals'];
                $service->intervals_format = $form['intervals_format'];
                $service->description = $form['description'];
                $service->attentions = $form['attentions'];
                $service->sample_requires = $form['sample_requires'];
                $service->charge_settings = $form['charge_settings'];
                $service->phones = $form['phones'];
                $service->emails = $form['emails'];
                $service->creator = $me;
                if ($service->save()) {
                    $incharges = $form['incharges'] ? json_decode($form['incharges'], true) : [];
                    foreach ($incharges as $charge_id => $cname)
                        O('user', $charge_id)->connect($service, 'incharge');
                    $group = O('tag_group', $form['group_id']);
                    $group->id ? $group->connect($service) : '';
                }

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('technical_service', '添加成功,请设置项目'));
                JS::redirect(URI::url("!technical_service/service/edit.{$service->id}.project"));
            }

        }

        JS::dialog(V('service/add', ['form' => $form]), ['title' => '添加技术服务']);

    }

    public function index_edit_projects_item_click()
    {
        $form = Input::form();
        $project = O('service_project', $form['value']);
        $service = O('service', $form['service_id']);
        $eqs = [];
        if ($project->id) {
            //先查当前服务的仪器
            $status = EQ_Status_Model::IN_SERVICE;
            $service_projects = Q("equipment[status={$status}].equipment service_equipment[project={$project}][service={$service}]");
            if ($service_projects->total_count()) {
                foreach ($service_projects as $service_project) {
                    $eqs[$service_project->equipment->id] = $service_project->equipment->name;
                }
            } else {
                foreach (Q("{$project} equipment") as $eq) {
                    $eqs[$eq->id] = $eq->name;
                }
            }
        }
        $eqs = @json_encode($eqs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        Output::$AJAX['complete'] = [
            'html' => (string)V('service/edit.project.tr', ['flexform_index' => $form['flexform_index'], 'eqs' => $eqs]),
            'special' => TRUE
        ];
    }

}

