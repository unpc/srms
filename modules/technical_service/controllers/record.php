<?php

class Record_Controller extends Base_Controller
{

    public function index($tab = 'all')
    {

        $me = L('ME');

        $form_token = Input::form('form_token');
        $type = strtolower(Input::form('type'));
        $export_types = ['print', 'csv'];

        if (in_array($type, $export_types)) {
            // $form = $_SESSION[$form_token];
            // $records = Q($form['selector']);
            // call_user_func([$this, '_export_' . $type], $records, $form);
        } else {

            $pre_selectors = new ArrayIterator;

            $form_token = Session::temp_token('records_list_', 300);
            $form = Input::form();
            $group = O('tag_group', $form['group_id']);
            $group_root = Tag_Model::root('group');
            if ($group->id && $group->root->id == $group_root->id) {
                $pre_selectors['group'] = "$group";
            } else {
                $group = null;
            }

            $service_incharge = Q("{$me}<incharge service")->total_count();
            if($me->access('管理所有内容')){
            }elseif($service_incharge){
                $pre_selectors['service'] = "{$me}<incharge service";
            }else{
                $pre_selectors['charge'] = "{$me}<incharge equipment";
            }

            $selector = "service_apply_record";

            if ($form['ref_no']) {
                $ref_no = trim($form['ref_no']);
                $selector .= "[ref_no*={$ref_no}]";
            }
            if (isset($form['status']) && $form['status'] != -1) {
                $selector .= "[status={$form['status']}]";
            }
            if ($form['user']) {
                $user = O('user', $form['user']);
                $selector .= "[user={$user}]";
            }
            if ($form['lab']) {
                $pre_selectors['lab'] = "lab[name*={$form['lab']}] user";
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
                $end_time = $form['ctime_e'] + 86399;
                $selector .= "[ctime<={$end_time}]";
            }

            if ($form['dtend_s']) {
                $start_time = $form['dtend_s'];
                $selector .= "[dtend>={$start_time}]";
            }
            if ($form['dtend_e']) {
                $end_time = $form['dtend_e'] + 86399;
                $selector .= "[dtend<={$end_time}]";
            }

            $selector .= ":sort(ctime D)";

            if ($form['equipment_name']) {
                $equipment_name = trim($form['equipment_name']);
                $pre_selectors['equipment'] = "equipment[name*={$equipment_name}].equipment";
            }

            if ($form['project_name']) {
                $project_name = trim($form['project_name']);
                $pre_selectors['project'] .= "service_project[name*={$project_name}]<project";
            }
            if ($form['service_name']) {
                $pre_selectors['service'] = $pre_selectors['service'] ?? 'service';
                $servivce_name = trim($form['service_name']);
                $pre_selectors['service'] .= "[name*={$servivce_name}]";
            }

            $start = (int)$form['st'];
            $per_page = Config::get('per_page.record', 25);
            $start = $start - ($start % $per_page);

            if (count($pre_selectors) > 0) {
                $selector = '(' . implode(',', (array)$pre_selectors) . ') ' . $selector;
            }

            $records = Q($selector);
            // error_log($selector);

            $form['selector'] = $selector;

            $_SESSION[$form_token] = $form;
            $pagination = Lab::pagination($records, $start, $per_page);

            $panel_buttons = new ArrayIterator;
            $panel_buttons[] = [
                'text' => I18N::T('technical_service', '导出'),
                'tip'   => I18N::T('technical_service', '导出Excel'),
                'extra' => 'q-object="output_apply_record" q-event="click" q-src="' . H(URI::url('!technical_service/export')) .
                '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token]) .
                '" class="button button_save "',
            ];

            $content = V('record/list', [
                'records' => $records,
                'pagination' => $pagination,
                'form' => $form,
                'group' => $group,
                'group_root' => $group_root,
                'tab' => $tab,
                'panel_buttons' => $panel_buttons,
            ]);

            $this->layout->body->primary_tabs
                ->add_tab('all', [
                    'url' => URI::url('!technical_service/record/index.all'),
                    'title' => I18N::T('technical_service', "项目检测"),
                    'weight' => -2000,
                ])
                ->select($tab)
                ->set('content', $content);
        }
    }
}


class Record_AJAX_Controller extends AJAX_Controller
{
    public function index_result_click()
    {
        $me = L('ME');

        $form = Input::form();
        $apply_record_id = $form['apply_record_id'];
        $record = O('service_apply_record', $apply_record_id);

        if (!$record->id) URI::redirect('error/404');

        if (!$me->is_allowed_to('结束检测任务', $record) && !$me->is_allowed_to('修改检测结果', $record)) {
            URI::redirect('error/401');
        }

        $connects = ['connect_records' => [], 'connect_samples' => []];
        if ($record->connect_type == 'eq_record') {
            $eq_records = Q("$record {$record->connect_type}");
            foreach ($eq_records as $eq_record) {
                $connects['connect_records'][$eq_record->id] = Number::fill($eq_record->id, 6) . "  " . date('Y/m/d H:m:s', $eq_record->dtstart) . "  " . date('Y/m/d H:m:s', $eq_record->dtend);
            }
        } elseif ($record->connect_type == 'eq_sample') {
            $eq_samples = Q("$record {$record->connect_type}");
            foreach ($eq_samples as $eq_sample) {
                $connects['connect_samples'][$eq_sample->id] = Number::fill($eq_sample->id, 6) . "  " . $eq_sample->sender->name . "(" . $eq_sample->count . ")";
            }
        } else {

        }

        $connects['connect_records'] = json_encode($connects['connect_records']);
        $connects['connect_samples'] = json_encode($connects['connect_samples']);

        $form['from'] = $form['from'] ?? '';
        switch($form['from']){
            case 'eq_sample':
                $view = 'record/result_eq_sample';
                break;
            default:
                $view = 'record/result';
                break;     
        }
        JS::dialog(V($view, [
            'connects' => $connects,
            'record' => $record,
            'equipment' => $record->equipment,
            'form' => $form
        ]), ['title' => "项目检测结果"]);

    }

    public function index_result_submit()
    {
        $me = L('ME');

        $form = Form::filter(Input::form());
        $apply_record_id = $form['apply_record_id'];
        $record = O('service_apply_record', $apply_record_id);

        if (!$record->id) URI::redirect('error/404');

        if (!$me->is_allowed_to('结束检测任务', $record) && !$me->is_allowed_to('修改检测结果', $record)) {
            URI::redirect('error/401');
        }

        $show_confirm = false;

        if ($form['submit']) {

            $form->validate('success_samples', 'compare(>=1)', I18N::T('technical_service', '样品数需大于0！'));
            if($form['success_samples'] > $record->apply->samples){
                $form->set_error('success_samples','样品数量不能大于申请检测的样品数');
            }
            $form->validate('success_samples', 'compare(>=1)', I18N::T('technical_service', '样品数需大于0！'));

            if ($form['connect_type']) {
                switch ($form['connect_type']) {
                    case "eq_record":
                        $connect_records = $form['connect_records'];
                        if (!count($connect_records)) {
                            $form->set_error('connect_records', I18N::T('eq_record', '请选择关联使用记录!'));
                        }
                        foreach ($connect_records as $rid) {
                            $check_record = O('eq_record', $rid);
                            if (!$check_record->id) {
                                $form->set_error('connect_records', I18N::T('eq_record', '请选择关联使用记录!'));
                                break;
                            }
                            if (Q("{$check_record} service_apply_record[id!={$record->id}]")->total_count()) {
                                $form->set_error('connect_records', I18N::T('eq_record', "使用记录{$rid}已关联，请重新选择其他记录!"));
                            } elseif (Q("eq_sample {$check_record}")->total_count()) {
                                $form->set_error('connect_records', I18N::T('eq_record', "使用记录{$rid}已关联送样记录，请重新选择其他记录!"));
                            }
                        }
                        break;
                    case "eq_sample":
                        $connect_samples = $form['connect_samples'];
                        if (!count($connect_samples)) {
                            $form->set_error('connect_samples', I18N::T('eq_record', '请选择关联送样记录!'));
                        }
                        foreach ($connect_samples as $rid) {
                            $check_record = O('eq_sample', $rid);
                            if (!$check_record->id) {
                                $form->set_error('connect_samples', I18N::T('eq_record', '请选择关联送样记录!'));
                                break;
                            }
                            if (Q("{$check_record} service_apply_record[id!={$record->id}]")->total_count()) {
                                $form->set_error('connect_samples', I18N::T('eq_record', "送样记录{$rid}已关联，请重新选择其他记录!"));
                                continue;
                            } elseif (Q("{$check_record} eq_record")->total_count()) {
                                $form->set_error('connect_samples', I18N::T('eq_record', "送样记录{$rid}已关联使用记录，请重新选择其他记录!"));
                            }
                            if ($check_record->status != EQ_Sample_Model::STATUS_TESTED) {
                                $show_confirm = true;
                            }
                        }
                        break;
                }
            }

            if ($show_confirm) {

                $sample_status = implode(',', [
                    EQ_Sample_Model::STATUS_TESTED,
                ]);

                $connects = Q("{$record} eq_sample[status!={$sample_status}] ");

                //因为只存在未申请送样主动绑定，所以肯定已经connect到了apply_record上
                JS::dialog(V('record/result_confirm', [
                    'connects' => $connects,
                    'record' => $record,
                    'equipment' => $record->equipment,
                    'form' => $form
                ]), ['title' => "项目检测结果"]);

                return;
            }

            if ($form->no_error) {

                switch ($form['connect_type']) {
                    case "eq_record":
                        $records = Q("$record eq_record")->to_assoc('id', 'id');
                        $sids = (array)$form['connect_records'];
                        foreach ($sids as $sid) {
                            if (isset($records[$sid])) {
                                unset($records[$sid]);
                            } else {
                                $eq_record = O('eq_record', $sid);
                                if ($eq_record->id) {
                                    $eq_record->connect($record);
                                }
                            }
                        }
                        foreach ($records as $s) {
                            O('eq_record', $s)->disconnect($record);
                        }
                        break;
                    case "eq_sample":
                        $samples = Q("$record eq_sample")->to_assoc('id', 'id');
                        $sids = (array)$form['connect_samples'];
                        foreach ($sids as $sid) {
                            if (isset($samples[$sid])) {
                                unset($samples[$sid]);
                            } else {
                                $eq_sample = O('eq_sample', $sid);
                                if ($eq_sample->id) {
                                    $eq_sample->connect($record);
                                }
                            }
                        }
                        foreach ($samples as $s) {
                            O('eq_sample', $s)->disconnect($record);
                        }
                        break;
                }

                if ($record->connect_type && $form['connect_type'] != $record->connect_type) {
                    foreach (Q("{$record} {$record->connect_type}") as $oriconnect)
                        $oriconnect->disconnect($record);
                }

                //关于检测任务的样品数，这块需求更改为每条被绑定的送样和预约都有各自的样品数，应该多建一张关系表，但目前时间有限，暂时按以下逻辑存取
                //被绑定的object（eq_sample，eq_record）下没有apply_record_samples值的话，取改object对应的apply_record下的success_samples
                $record->success_samples = $form['success_samples'] ?: $record->apply->samples;//产品要求只做记录,不参与计费
                $record->connect_type = $form['connect_type'];
                $record->result = $form['result'] ?? '';
                $record->operator = $me;
                $record->dtend = $record->dtend ?: time();
                $record->status = Service_Apply_Record_Model::STATUS_TEST;
                $record->dtlength = Technical_Service::getDtLength($record);
                $record->save();

                if(isset($form['edit'])){
                    Log::add(strtr('[services] %user_name[%user_id]修改了技术服务[%record_id]', [
                        '%user_name' => $me->name,
                        '%user_id' => $me->id,
                        '%record_id' => $record->id
                    ]), 'journal');
    
                    Lab::message(Lab::MESSAGE_NORMAL, '修改成功');
                    JS::redirect();
                }else{
                    Log::add(strtr('[services] %user_name[%user_id]结束了技术服务[%record_id]', [
                        '%user_name' => $me->name,
                        '%user_id' => $me->id,
                        '%record_id' => $record->id
                    ]), 'journal');
    
                    Lab::message(Lab::MESSAGE_NORMAL, '已结束服务');
                    JS::redirect(URI::url("!technical_service/record/index"));    
                }
                
                return;

            }

        }

        $form['from'] = $form['from'] ?? '';
        switch($form['from']){
            case 'eq_sample':
                $view = 'record/result_eq_sample';
                break;
            default:
                $view = 'record/result';
                break;     
        }

        JS::dialog(V($view, [
            'record' => $record,
            'equipment' => $record->equipment,
            'form' => $form
        ]), ['title' => "项目检测结果"]);

    }

    public function index_result_view_click()
    {
        $me = L('ME');

        $form = Input::form();
        $apply_record_id = $form['apply_record_id'];
        $record = O('service_apply_record', $apply_record_id);

        if (!$record->id) URI::redirect('error/404');

        if (!$me->is_allowed_to('查看结果', $record)) {
            URI::redirect('error/401');
        }

        $connects = ['connect_records' => [], 'connect_samples' => []];
        if ($record->connect_type == 'eq_record') {
            $eq_records = Q("$record {$record->connect_type}");
            foreach ($eq_records as $eq_record) {
                $connects['connect_records'][$eq_record->id] = Number::fill($eq_record->id, 6) . "  " . date('Y/m/d H:m:s', $eq_record->dtstart) . "  " . date('Y/m/d H:m:s', $eq_record->dtend);
            }
        } elseif ($record->connect_type == 'eq_sample') {
            $eq_samples = Q("$record {$record->connect_type}");
            foreach ($eq_samples as $eq_sample) {
                $connects['connect_samples'][$eq_sample->id] = Number::fill($eq_sample->id, 6) . "  " . $eq_sample->sender->name . "(" . $eq_sample->count . ")";
            }
        } else {

        }

        $connects['connect_records'] = json_encode($connects['connect_records']);
        $connects['connect_samples'] = json_encode($connects['connect_samples']);

        JS::dialog(V('record/result_view', [
            'connects' => $connects,
            'record' => $record,
            'equipment' => $record->equipment,
            'form' => $form
        ]), ['title' => "项目检测结果"]);

    }

}


