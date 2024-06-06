<?php

class Technical_Service
{

    public static function setup_index($e, $controller, $method, $params)
    {
        $allow = [
            'record',
            'record_all',
            'record_group',
        ];
        if (!in_array($params[0],$allow)) {
            return;
        }
        $me = L('ME');
        if ($params[0] == 'record') {
            $length = Q("{$me}<incharge service")->total_count();
            if (!$length) {
                URI::redirect('error/401');
            }
        }
        Event::bind('service.primary.tab', "Technical_Service::{$params[0]}_primary_tab");
        Event::bind('service.primary.content', "Technical_Service::record_primary_tab_content", 0, $params[0]);
    }

    public static function record_primary_tab($e, $tabs)
    {
        $me = L('ME');
        $tabs->add_tab('record', [
            'url'   => URI::url('!technical_service/extra/record'),
            'title' => I18N::T('technical_service', '您负责的所有技术服务记录'),
        ]);
    }
    public static function record_all_primary_tab($e, $tabs)
    {
        $me = L('ME');
        $tabs->add_tab('record_all', [
            'url'   => URI::url('!technical_service/extra/record_all'),
            'title' => I18N::T('technical_service', '所有技术服务的记录'),
        ]);
    }
    public static function record_group_primary_tab($e, $tabs)
    {
        $me = L('ME');
        $tabs->add_tab('record_group', [
            'url'   => URI::url('!technical_service/extra/record_group'),
            'title' => I18N::T('technical_service', '下属单位的所有技术服务记录'),
        ]);
    }

    public static function record_primary_tab_content($e, $tabs){
        $me         = L('ME');
        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token = Session::temp_token('technical_service', 300);
            $form       = Lab::form(function (&$old_form, &$form) {
                if (!isset($form['sort'])) {
                    $form['sort']     = 'ctime';
                    $form['sort_asc'] = false;
                }
            });
            $form['form_token']    = $form_token;
            $_SESSION[$form_token] = $form;
            $pre_selector = [];

            $selector       = ' service_apply';

            $group_root     = Tag_Model::root('group');
            $group = O('tag_group', $form['group_id']);
            $lab = is_object($form['lab']) ? $form['lab'] : O('lab', $form['lab']);

            $service_pre_selector = [];
            $has_service_selector = false;

            if ($tabs->selected == 'record') {
                $pre_selector['service'] = "service";
                $service_pre_selector[] = " user[id={$me->id}]<incharge ";
                $has_service_selector = true;
            } else {
                if($me->access('管理所有内容')){
                }elseif($me->access('管理下属机构服务')){
                    $pre_selector['me_group'] = "{$me->group} service ";
                    $group = $me->group->id ? $me->group : O('tag_group');
                    $group_root = $me->group->id ? $me->group : O('tag_group');
                }
            }

            //预约编号、服务名称、服务负责人、所属单位、服务状态、预约者、课题组、联系方式 、预约时间、期望完成时间
            if ($form['ref_no']) {
                $ref = trim($form['ref_no']);
                $selector .= "[ref_no*={$ref}]";
            }
    
            if ($form['service_name']) {
                $pre_selector['service'] = $pre_selector['service'] ?? 'service';
                $service_name = trim($form['service_name']);
                $pre_selector['service'] .= "[name*={$service_name}]";
            }
            
            if ($form['group_id'] && $form['group_id'] != $group_root->id) {
                $pre_selector['service'] = $pre_selector['service'] ?? 'service';
                $service_group = trim($form['group_id']);
                $service_pre_selector[] = "tag_group[id={$service_group}].group";
                $has_service_selector = true;
            }
            if ($form['service_incharge']) {
                $pre_selector['service'] = $pre_selector['service'] ?? 'service';
                $service_incharge = trim($form['service_incharge']);
                $service_pre_selector[] = " user[id={$service_incharge}]<incharge ";
                $has_service_selector = true;
            }
            if($has_service_selector){
                $pre_selector['service'] = "( " . join(', ', $service_pre_selector) . " ) " . $pre_selector['service'];
            }
    
            if ($form['user']) {
                $pre_selector['user'] = "user[id={$form['user']}]";
            }
            if ($form['phone']) {
                $phone = trim($form['phone']);
                $pre_selector['user'] = $pre_selector['user'] ?? 'user';
                $pre_selector['user'] .= "[phone*={$phone}]";
            }
            
            if ($form['lab']) {
                $pre_selector['user'] = $pre_selector['user'] ?? 'user';
                $pre_selector['user'] = " lab[id={$form['lab']}] ".$pre_selector['user'];
            }
            if ($form['ctime_s']) {
                $start_time = $form['ctime_s'];
                $selector .= "[ctime>={$start_time}]";
            }
            if ($form['ctime_e']) {
                $end_time = $form['ctime_e'] + 86399;
                $selector .= "[ctime<={$end_time}]";
            }
            if ($form['dtrequest_s']) {
                $start_time = $form['dtrequest_s'];
                $selector .= "[dtrequest>={$start_time}]";
            }
            if ($form['dtrequest_e']) {
                $end_time = $form['dtrequest_e'] + 86399;
                $selector .= "[dtrequest<={$end_time}]";
            }
            if (isset($form['status']) && $form['status'] != -1) {
                $selector .= "[status={$form['status']}]";
            }
    
            if (!empty($pre_selector)) {
                $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
            }
    
            $applys = Q($selector);
            
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        switch ($sort_by) {
            case 'status':
                $selector .= ":sort(status {$sort_flag})";
                break;
            default:
                $selector .= ':sort(id D)';
                break;
        }

        if (count((array)$pre_selectors)) {
            $selector = '(' . implode(', ', (array)$pre_selectors) . ') ' . $selector;
        }

        $_SESSION[$form_token]['selector'] = $selector;
        $applys                           = Q($selector);

        $pagination = Lab::pagination($applys, (int) $form['st'], 15);

        $panel_buttons   = [];
        $panel_buttons[] = [
            'text' => I18N::T('technical_service', '导出'),
            'tip'   => I18N::T('technical_service', '导出Excel'),
            'extra' => 'q-object="output_apply" q-event="click" q-src="' . H(URI::url('!technical_service/export')) .
            '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token]) .
            '" class="button button_save "',
        ];
        $panel_buttons[] = [
            'text' => I18N::T('eq_smaple', '打印'),
            'tip'   => I18N::T('eq_smaple', '打印'),
            'extra' => 'q-object="output_apply" q-event="click" q-src="' . H(URI::url('!technical_service/export')) .
            '" q-static="' . H(['type' => 'print', 'form_token' => $form_token]) .
            '" class = "button button_print "',
        ];

        $tabs->content = V('technical_service:incharge/applys', [
            'applys'        => $applys,
            'form'          => $form,
            'form_token'    => $form_token,
            'pagination'    => $pagination,
            'sort_by'       => $sort_by,
            'sort_asc'      => $sort_asc,
            'group'         => $group,
            'lab'         => $lab,
            'group_root'    => $group_root,
            'selected' => $tabs->selected,
            'panel_buttons' => $panel_buttons,
        ]);
    }


    public static function setup_profile()
    {
        Event::bind('profile.view.tab', 'Technical_Service::index_profile_tab');
        Event::bind('profile.view.content', 'Technical_Service::index_profile_content', 0, 'service_apply');
    }

    public static function index_profile_tab($e, $tabs)
    {
        $tabs->add_tab('service_apply', [
            'url' => $tabs->user->url('service_apply'),
            'title' => I18N::T('technical_service', '服务预约'),
            'weight' => 50,
        ]);

        if (L('ME')->is_allowed_to('列表审批', 'service_apply')) {
            $tabs->add_tab('apply_approval', [
                'url' => $tabs->user->url('apply_approval'),
                'title' => I18N::T('technical_service', '服务审批'),
                'weight' => 60,
            ]);
            Event::bind('profile.view.content', 'Technical_Service::index_profile_apply_approval_content', 0, 'apply_approval');
        }
    }

    public static function index_profile_content($e, $tabs)
    {

        $me = $tabs->user;

        $form = Input::form();
        $selector = "service_apply:sort('ctime D')[user={$me}]";
        $pre_selector = [];

        if ($form['ref_no']) {
            $ref = trim($form['ref_no']);
            $selector .= "[ref_no*={$ref}]";
        }

        if ($form['service_name']) {
            $service_name = trim($form['service_name']);
            $pre_selector['service'] = "service[name*={$service_name}]";
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

        if ($form['dtrequest_s']) {
            $start_time = $form['dtrequest_s'];
            $selector .= "[dtrequest>={$start_time}]";
        }
        if ($form['dtrequest_e']) {
            $end_time = $form['dtrequest_e'] + 86399;
            $selector .= "[dtrequest<={$end_time}]";
        }

        if (isset($form['status']) && $form['status'] != -1) {
            $selector .= "[status={$form['status']}]";
        }

        if (!empty($pre_selector)) {
            $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
        }

        $applys = Q($selector);

        $start = (int)Input::form('st');
        $per_page = 20;
        $pagination = Lab::pagination($applys, $start, $per_page);

        $tabs->content = V('technical_service:dashboard/tabs/service_apply', ['applys' => $applys, 'pagination' => $pagination, 'form' => $form]);

    }

    public static function index_profile_apply_approval_content($e, $tabs)
    {
        $user = $tabs->user;

        Event::bind('profile.apply_approval.view.tabs', 'Technical_Service::_apply_approval_applied_tabs', 0, 'applied');
        Event::bind('profile.apply_approval.view.tabs', 'Technical_Service::_apply_approval_approved_tabs', 0, 'approved');
        Event::bind('profile.apply_approval.view.tabs', 'Technical_Service::_apply_approval_reject_tabs', 0, 'reject');

        $tabs->content = V('technical_service:apply/profile/content');

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->set('user', $tabs->user)
            ->tab_event('profile.apply_approval.view.tabs')
            ->content_event('profile.apply_approval.view.content')
            ->select($params[2]);

    }

    //审批-申请中
    public static function _apply_approval_applied_tabs($e, $tabs)
    {

        Event::bind('profile.apply_approval.view.content', 'Technical_Service::_apply_approval_applied_content', 0, 'applied');

        $me = L('ME');
        $user = $tabs->user;
        $status = Service_Apply_Model::STATUS_APPLY;

        $selector = "service_apply[status={$status}]";

        $pre_selector = [];
        if ($me->access('管理所有服务')) {
        } elseif ($me->access('管理下属机构服务')) {
            $pre_selector['service'] = "{$me->group} service";
        } else {
            $pre_selector['service'] = "{$me}<incharge service";
        }
        if (!empty($pre_selector)) {
            $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
        }

        $tabs->add_tab('applied', [
            'url' => $user->url('apply_approval.applied'),
            'title' => I18N::T('technical_service', '申请中 (%count)', [
                '%count' => Q("$selector")->total_count(),
            ]),
        ]);
    }

    public static function _apply_approval_applied_content($e, $tabs)
    {
        $me = $tabs->user;

        $form = Lab::form(function (&$old_form, &$form) {
        });

        $status = Service_Apply_Model::STATUS_APPLY;

        $selector = "service_apply:sort('ctime D')[status={$status}]";

        $pre_selector = [];
        if ($me->access('管理所有服务')) {
        } elseif ($me->access('管理下属机构服务')) {
            $pre_selector['service'] = "{$me->group} service";
        } else {
            $pre_selector['service'] = "{$me}<incharge service";
        }

        if ($form['ref_no']) {
            $ref = trim($form['ref_no']);
            $selector .= "[ref_no*={$ref}]";
        }

        if ($form['service_name']) {
            $service_name = trim($form['service_name']);
            $pre_selector['service'] = "service[name*={$service_name}]";
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

        if ($form['dtrequest_s']) {
            $start_time = $form['dtrequest_s'];
            $selector .= "[dtrequest>={$start_time}]";
        }
        if ($form['dtrequest_e']) {
            $end_time = $form['dtrequest_e'] + 86399;
            $selector .= "[dtrequest<={$end_time}]";
        }

        if (isset($form['status']) && $form['status'] != -1) {
            $selector .= "[status={$form['status']}]";
        }

        if (!empty($pre_selector)) {
            $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
        }

        $applys = Q($selector);

        $start = (int)Input::form('st');
        $per_page = 20;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($applys, $start, $per_page);

        $tabs->content = V('technical_service:dashboard/tabs/service_apply_approval', ['applys' => $applys, 'pagination' => $pagination, 'form' => $form]);

    }

    public static function _apply_approval_approved_tabs($e, $tabs)
    {

        Event::bind('profile.apply_approval.view.content', 'Technical_Service::_apply_approval_approved_content', 0, 'approved');

        $me = L('ME');

        $user = $tabs->user;
        $status = implode(',', [
            Service_Apply_Model::STATUS_PASS,
            Service_Apply_Model::STATUS_DONE,
            Service_Apply_Model::STATUS_SERVING,
        ]);

        $selector = "service_apply[status={$status}]";

        $pre_selector = [];
        if ($user->access('管理所有服务')) {
        } elseif ($user->access('管理下属机构服务')) {
            $pre_selector['service'] = "{$user->group} service";
        } else {
            $pre_selector['service'] = "{$user}<incharge service";
        }
        if (!empty($pre_selector)) {
            $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
        }

        $tabs->add_tab('approved', [
            'url' => $user->url('apply_approval.approved'),
            'title' => I18N::T('technical_service', '已审批 (%count)', [
                '%count' => Q("$selector")->total_count(),
            ]),
        ]);
    }

    public static function _apply_approval_approved_content($e, $tabs)
    {
        $me = $tabs->user;

        $form = Lab::form(function (&$old_form, &$form) {
        });

        $status = implode(',', [
            Service_Apply_Model::STATUS_PASS,
            Service_Apply_Model::STATUS_DONE,
            Service_Apply_Model::STATUS_SERVING,
        ]);

        $selector = "service_apply:sort('ctime D')[status={$status}]";

        $pre_selector = [];
        if ($me->access('管理所有服务')) {
        } elseif ($me->access('管理下属机构服务')) {
            $pre_selector['service'] = "{$me->group} service";
        } else {
            $pre_selector['service'] = "{$me}<incharge service";
        }

        if ($form['ref_no']) {
            $ref = trim($form['ref_no']);
            $selector .= "[ref_no*={$ref}]";
        }

        if ($form['service_name']) {
            $service_name = trim($form['service_name']);
            $pre_selector['service'] = "service[name*={$service_name}]";
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

        if ($form['dtrequest_s']) {
            $start_time = $form['dtrequest_s'];
            $selector .= "[dtrequest>={$start_time}]";
        }
        if ($form['dtrequest_e']) {
            $end_time = $form['dtrequest_e'] + 86399;
            $selector .= "[dtrequest<={$end_time}]";
        }

        if (isset($form['status']) && $form['status'] != -1) {
            $selector .= "[status={$form['status']}]";
        }

        if (!empty($pre_selector)) {
            $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
        }

        $applys = Q($selector);

        $start = (int)Input::form('st');
        $per_page = 20;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($applys, $start, $per_page);

        $tabs->content = V('technical_service:dashboard/tabs/service_apply_approval', ['applys' => $applys, 'pagination' => $pagination, 'form' => $form]);

    }

    public static function _apply_approval_reject_tabs($e, $tabs)
    {

        Event::bind('profile.apply_approval.view.content', 'Technical_Service::_apply_approval_reject_content', 0, 'reject');

        $me = L('ME');

        $user = $tabs->user;
        $status = implode(',', [
            Service_Apply_Model::STATUS_REJECT,
        ]);

        $selector = "service_apply[status={$status}]";

        $pre_selector = [];
        if ($me->access('管理所有服务')) {
        } elseif ($me->access('管理下属机构服务')) {
            $pre_selector['service'] = "{$me->group} service";
        } else {
            $pre_selector['service'] = "{$me}<incharge service";
        }
        if (!empty($pre_selector)) {
            $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
        }

        $tabs->add_tab('reject', [
            'url' => $user->url('apply_approval.reject'),
            'title' => I18N::T('technical_service', '已驳回 (%count)', [
                '%count' => Q("$selector")->total_count(),
            ]),
        ]);
    }

    public static function _apply_approval_reject_content($e, $tabs)
    {
        $me = $tabs->user;

        $form = Lab::form(function (&$old_form, &$form) {
        });

        $status = implode(',', [
            Service_Apply_Model::STATUS_REJECT,
        ]);

        $selector = "service_apply:sort('ctime D')[status={$status}]";

        $pre_selector = [];
        if ($me->access('管理所有服务')) {
        } elseif ($me->access('管理下属机构服务')) {
            $pre_selector['service'] = "{$me->group} service";
        } else {
            $pre_selector['service'] = "{$me}<incharge service";
        }

        if ($form['ref_no']) {
            $ref = trim($form['ref_no']);
            $selector .= "[ref_no*={$ref}]";
        }

        if ($form['service_name']) {
            $service_name = trim($form['service_name']);
            $pre_selector['service'] = "service[name*={$service_name}]";
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

        if ($form['dtrequest_s']) {
            $start_time = $form['dtrequest_s'];
            $selector .= "[dtrequest>={$start_time}]";
        }
        if ($form['dtrequest_e']) {
            $end_time = $form['dtrequest_e'] + 86399;
            $selector .= "[dtrequest<={$end_time}]";
        }

        if (isset($form['status']) && $form['status'] != -1) {
            $selector .= "[status={$form['status']}]";
        }

        if (!empty($pre_selector)) {
            $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
        }

        $applys = Q($selector);

        $start = (int)Input::form('st');
        $per_page = 20;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($applys, $start, $per_page);

        $tabs->content = V('technical_service:dashboard/tabs/service_apply_approval', ['applys' => $applys, 'pagination' => $pagination, 'form' => $form]);

    }

    //个人主页下的
    public static function _index_service_apply_content($e, $tabs)
    {
        $me = L('ME');

        $show_status = [
            Service_Apply_Model::STATUS_APPLY,
            Service_Apply_Model::STATUS_PASS,
            Service_Apply_Model::STATUS_SERVING,
            Service_Apply_Model::STATUS_REJECT,
        ];
        $status = implode(',', $show_status);

        $form = Input::form();
        $selector = "service_apply:sort('ctime D')[user={$me}][status={$status}]";
        $pre_selector = [];

        if ($form['ref_no']) {
            $ref = trim($form['ref_no']);
            $selector .= "[ref_no*={$ref}]";
        }

        if ($form['service_name']) {
            $service_name = trim($form['service_name']);
            $pre_selector['service'] = "service[name*={$service_name}]";
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

        if ($form['dtrequest_s']) {
            $start_time = $form['dtrequest_s'];
            $selector .= "[dtrequest>={$start_time}]";
        }
        if ($form['dtrequest_e']) {
            $end_time = $form['dtrequest_e'] + 86399;
            $selector .= "[dtrequest<={$end_time}]";
        }

        if (isset($form['status']) && $form['status'] != -1) {
            $selector .= "[status={$form['status']}]";
        }

        if (!empty($pre_selector)) {
            $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
        }

        $applys = Q($selector);

        $start = (int)Input::form('st');
        $per_page = 20;
        $pagination = Lab::pagination($applys, $start, $per_page);

        $tabs->content = V('technical_service:dashboard/tabs/service_apply', ['applys' => $applys, 'pagination' => $pagination, 'form' => $form]);

    }

    public static function _index_service_apply_test_content($e, $tabs)
    {
        $me = L('ME');

        $status = implode(',', [Service_Apply_Model::STATUS_SERVING,Service_Apply_Model::STATUS_PASS]);
        $pre_selectors['charge'] = " {$me}<incharge equipment ";
        $pre_selectors['apply'] = " service_apply[status={$status}]<apply";

        $selector = "service_apply_record";

        $form = Input::form();
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

        if ($form['project_name']) {
            $project_name = trim($form['project_name']);
            $pre_selectors['project'] .= "service_project[name*={$project_name}]<project";
        }
        if ($form['service_name']) {
            $servivce_name = trim($form['service_name']);
            $pre_selectors['service'] .= "service[name*={$servivce_name}]<service";
        }
        if ($form['ctime_s']) {
            $start_time = $form['ctime_s'];
            $selector .= "[ctime>={$start_time}]";
        }
        if ($form['ctime_e']) {
            $end_time = $form['ctime_e'] + 86399;
            $selector .= "[ctime<={$end_time}]";
        }

        if ($form['equipment_name']) {
            $equipment_name = trim($form['equipment_name']);
            $pre_selectors['equipment'] = "equipment[name*={$equipment_name}]>equipment";
        }

        $start = (int)$form['st'];
        $per_page = Config::get('per_page.record', 25);
        $start = $start - ($start % $per_page);

        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(',', (array)$pre_selectors) . ') ' . $selector;
        }

        $records = Q($selector);

        $start = (int)Input::form('st');
        $per_page = 20;
        $pagination = Lab::pagination($records, $start, $per_page);

        $tabs->content = V('technical_service:dashboard/tabs/service_apply_record', ['records' => $records, 'pagination' => $pagination, 'form' => $form]);

    }

    public static function _index_service_apply_approval_content($e, $tabs)
    {
        $me = L('ME');

        $show_status = [
            Service_Apply_Model::STATUS_APPLY,
        ];
        $status = implode(',', $show_status);

        $form = Input::form();
        $selector = "service_apply[status={$status}]";

        $pre_selector = [];
        if ($me->access('管理所有服务')) {
        } elseif ($me->access('管理下属机构服务')) {
            $pre_selector['service'] = "{$me->group} service";
        } else {
            $pre_selector['service'] = "{$me}<incharge service";
        }

        if ($form['ref_no']) {
            $ref = trim($form['ref_no']);
            $selector .= "[ref_no*={$ref}]";
        }

        if ($form['service_name']) {
            $service_name = trim($form['service_name']);
            $pre_selector['service'] = $pre_selector['service'] ? ($pre_selector['service'] . "[name*={$service_name}]") : "service[name*={$service_name}]";
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

        if ($form['dtrequest_s']) {
            $start_time = $form['dtrequest_s'];
            $selector .= "[dtrequest>={$start_time}]";
        }
        if ($form['dtrequest_e']) {
            $end_time = $form['dtrequest_e'] + 86399;
            $selector .= "[dtrequest<={$end_time}]";
        }

        if (!empty($pre_selector)) {
            $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
        }

        $applys = Q($selector);

        $start = (int)Input::form('st');
        $per_page = 20;
        $pagination = Lab::pagination($applys, $start, $per_page);

        $tabs->content = V('technical_service:dashboard/tabs/service_apply_approval', ['applys' => $applys, 'pagination' => $pagination, 'form' => $form]);

    }


    public static function setup_equipment()
    {
        Event::bind('equipment.edit.tab', 'Technical_Service::_edit_equipment_tab', 0, 'project');
        Event::bind('equipment.edit.content', 'Technical_Service::_edit_equipment_content', 0, 'project');
    }

    public static function _edit_equipment_tab($e, $tabs)
    {
        $equipment = $tabs->equipment;
        if (L('ME')->is_allowed_to('设置服务项目', $equipment)) {
            $tabs->add_tab('project', [
                'title' => I18N::T('technical_service', '服务项目'),
                'url' => $equipment->url('project', null, null, 'edit'),
                'weight' => 50
            ]);
        }
    }

    public static function _edit_equipment_content($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $form = Form::filter(Input::form());

        $old_connects = Q("{$equipment} service_project");
        foreach ($old_connects as $old_project)
            $old_projects[$old_project->id] = $old_project->name;

        if ($form['submit']) {

            $delete = [];//删除掉

            foreach ($old_connects as $connect){
                $connect->disconnect($equipment);
                $delete[$connect->id] = $connect;
            }

            if ($form['projects']) {
                $post_projects = json_decode($form['projects'], true);
                foreach ($post_projects as $pid => $pname) {
                    $post_project = O('service_project', $pid);
                    $post_project->connect($equipment);
                    if (isset($delete[$post_project->id])) {
                        unset($delete[$post_project->id]);
                    }
                }
            }

            if (!empty($delete)) {
                foreach ($delete as $dep) {
                    Q("service_equipment[project={$dep}][equipment={$equipment}]")->delete_all();
                }
            }

            if ($form->no_error) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('technical_service', '服务项目设置更新成功!'));
            }

        }

        $content = V('technical_service:equipment/edit.project', ['equipment' => $equipment, 'form' => $form, 'old_projects' => json_encode($old_projects)]);
        $tabs->content = $content;

    }

    public static function extra_charge_setting_view($e, $equipment)
    {

        $me = L('ME');

        if (Q("{$equipment} service_project")->total_count() && $me->is_allowed_to('修改计费设置', $equipment)) {
            $e->return_value = V('technical_service:charge/setting', [
                'equipment' => $equipment,
            ]);
        }

    }

    public static function extra_charge_setting_content($e, $form, $equipment)
    {
        $me = L('ME');
        if ($me->is_allowed_to('修改计费设置', $equipment)) {
            if ($form['submit']) {
                $charge_template = $equipment->charge_template;
                $extra = Database::factory()->query("SELECT _extra from equipment where id = {$equipment->id}")->row()->_extra;
                $extra = $extra ? json_decode($extra, true) : [];
                if ($form['service'] != $extra['charge_template']['service']) {
                    $charge_setting = EQ_Charge::get_charge_setting($equipment);
                    $charge_setting['service'] = [];
                    $equipment->charge_setting = $charge_setting;
                    $equipment->save();
                }
                $service_template = $form['service'];
                $charge_template['service'] = $service_template;
                $equipment->charge_template = $charge_template;
            }
        }
    }

    public static function charge_edit_content_tabs($e, $tabs)
    {
        $me = L('ME');

        $equipment = $tabs->equipment;

        if ($me->is_allowed_to('修改计费设置', $equipment) && Q("{$equipment} service_project")->total_count()) {
            if ($equipment->charge_template['service'] &&
                $equipment->charge_template['service'] != 'no_charge_service') {
                $tabs->content->third_tabs
                    ->add_tab('service', [
                        'url' => $equipment->url('charge.service', NULL, NULL, 'edit'),
                        'title' => I18N::T('technical_service', '项目计费'),
                        'weight' => 6,
                    ]);

                Event::bind('equipment.charge.edit.content', 'Technical_Service::edit_charge_service', 6, 'service');
            }
        }
    }

    static function edit_charge_service($e, $tabs)
    {
        $me = L('ME');

        $equipment = $tabs->equipment;

        $charge_type = $equipment->charge_template['service'] ?? 'no_charge_service';

        if ($me->is_allowed_to('修改计费设置', $equipment)) {

            $form = Form::filter(Input::form());

            $projects = Q("{$equipment} service_project");

            if ($form['submit']) {
                $service_setting = [];
                $service_setting['*'] = $form['price'];
                $tags = $form['special_tags'];
                if ($tags) {
                    $root = $equipment->get_root();
                    foreach ($tags as $i => $tag) {
                        if ($tag) {
                            $special_tags = @json_decode($tag, TRUE);
                            if ($special_tags) foreach ($special_tags as $tag) {
                                $equipment_root = O('tag_equipment_user_tags', ['root' => Tag_Model::root('equipment_user_tags'), 'name' => $tag]);
                                if ($root->id || $equipment_root->id) {
                                    $service_setting[$tag] = $form['special_tags_price'][$i];
                                }
                            }
                        }
                    }
                }

                $params = EQ_Lua::array_p2l($service_setting);
                if (EQ_Charge::update_charge_script($equipment, 'service', ['%options' => $params])) {
                    EQ_Charge::put_charge_setting($equipment, 'service', $service_setting);

                    if (Module::is_installed('yiqikong')) {
                        CLI_YiQiKong::update_equipment_setting($equipment->id);
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_charge', '设备送样收费信息已更新'));
                }

            }

            $tabs->content = V('technical_service:charge/' . $charge_type, ['equipment' => $equipment, 'projects' => $projects]);

        }
    }

    public static function calculate_amount($apply_record, $form = [])
    {

        $charge = O('eq_charge');
        $charge->source = $apply_record;
        $charge->equipment = $apply_record->equipment;

        //自定义送样表单传入供lua计算
        if (Module::is_installed('extra') && isset($form['extra_fields'])) {
            $charge->source->extra_fields = (array)$form['extra_fields'];
        }

        $lua = new EQ_Charge_LUA($charge);

        $result = $lua->run(['fee', 'description']);

        return $result;

    }

    public static function service_apply_record_model_saved($e, $record, $old_data, $new_data)
    {

        $apply = $record->apply;
        if (!$apply->id) return;

        if ($record->status == Service_Apply_Record_Model::STATUS_TEST) {
            $apply->status = Service_Apply_Model::STATUS_SERVING;
            $apply->dtstart = $apply->dtstart ?: time();
            $apply->save();
        }

        if ($apply->status != Service_Apply_Model::STATUS_SERVING) return;

        $status = Service_Apply_Record_Model::STATUS_APPLY;
        if (!Q("service_apply_record[apply={$record->apply}][status={$status}]")->total_count()) {
            //全部检测完成
            $apply->status = Service_Apply_Model::STATUS_DONE;
            $apply->dtend = time();
            $apply->save();
        }

    }

    static function eq_sample_prerender_add_form($e, $form, $equipment)
    {
        $me = L('ME');
        $e->return_value .= V('technical_service:record/edit/add', ['form' => $form]);
        return false;
    }

    static function eq_sample_prerender_edit_form($e, $sample, $form, $equipment)
    {
        $me = L('ME');
        $e->return_value .= V('technical_service:record/edit/edit', ['form' => $form, 'sample' => $sample]);
        return false;
    }

    static function sample_form_post_submit($e, $sample, $form)
    {
        if (($form['connect_apply_record'] == 1) && $form['apply_record']) {
            $record = O('service_apply_record', $form['apply_record']);

            /**
             * 28337（3）17Kong/Sprint-320：全面测试3.28：ceshi1编辑送样记录，修改服务申请，再次编辑，显示的是之前的服务
             */
            $records = Q("{$sample} service_apply_record");
            foreach ($records as $r) {
                $r->disconnect($sample);
            }

            $record->connect($sample);
            $record->connect_type = 'eq_sample';
            $record->dtlength = Technical_Service::getDtLength($record, 'eq_sample');
            $record->save();
        } else {
            //如果没有应该取消关联
            $record = Q("{$sample} service_apply_record")->current();
            if ($record->id) {
                $record->disconnect($sample);
                $record->dtlength = Technical_Service::getDtLength($record);
                $record->save();
            }
        }

        return TRUE;

    }

    static function extra_form_validate($e, $equipment, $type, $form)
    {
        $me = L('ME');
        if ($form['connect_apply_record'] && !$form['apply_record']) {
            $form->set_error('apply_record', I18N::T('technical_service', "请选择服务申请!"));
        }
        if ($form['connect_apply_record'] && $form['status'] == EQ_Sample_Model::STATUS_TESTED && (!$form['dtstart'] || $form['dtrial_check'] != 'on')) {
            $form->set_error('dtstart', I18N::T('technical_service', "请选择测样时间!"));
        }
        // if($form['status'] != EQ_Sample_Model::STATUS_TESTED && $form['connect_apply_record'] && $form['apply_record']){
        //     $record = O('service_apply_record',$form['apply_record']);
        //     if($record->status == Service_Apply_Record_Model::STATUS_TEST){
        //         $form->set_error('apply_record', I18N::T('technical_service', "请选择待检测的检测任务!"));
        //     }
        // }
        $e->return_value = FALSE;
        return;
    }

    public static function eq_sample_links_edit($e, $sample, $links, $mode)
    {
        $me = L('ME');
        $record = Q("{$sample} service_apply_record")->current();
        if ($record->id && $me->is_allowed_to('结束检测任务', $record)) {
            $links['result'] = [
                'weight' => 10,
                'url' => $record->url(NULL, NULL, NULL, 'approval'),
                'text' => I18N::T('technical_service', '结束服务'),
                'tip' => I18N::T('technical_service', '结束服务'),
                'extra' => 'class="blue" q-object="result" q-event="click"  q-static="' . H(['apply_record_id' => $record->id,'from'=>'eq_sample']) . '" q-src="' . URI::url('!technical_service/record') . '"',
            ];
        }
        if ($record->id && $me->is_allowed_to('修改检测结果', $record)) {
            $links['result'] = [
                'url' => $record->url(NULL, NULL, NULL, 'approval'),
                'text' => I18N::T('technical_service', '修改结果'),
                'tip' => I18N::T('technical_service', '修改结果'),
                'extra' => 'class="blue" q-object="result" q-event="click"  q-static="' . H(['apply_record_id' => $record->id,'edit'=>1,'from'=>'eq_sample']) . '" q-src="' . URI::url('!technical_service/record') . '"',
            ];
        }
    }

    static function judge_balance($e, $apply, $project, $equipment, $user, $form)
    {
        $record = O('service_apply_record', [
            'apply' => $apply,
            'equipment' => $equipment,
            'project' => $project]);

        $record->apply = $apply;
        $record->equipment = $equipment;
        $record->service = $apply->service;
        $record->user = O('user', $form['user']);
        $record->project = $project;
        $record->samples = $form['samples'];
        $record->success_samples = 0;

        $eq_charge = O('eq_charge', ['source' => $record]);
        $eq_charge->source = $record;
        $eq_charge->equipment = $equipment;
        $eq_charge->user = $record->user;
        $amount_k = "{$apply->id}_{$project->id}_amount";
        if (!isset($form[$amount_k])) {
            $fee = Technical_Service::calculate_amount($record, $form);
            $eq_charge->amount = $fee['fee'];
            $eq_charge->description = $fee['description'];
        } else {
            $eq_charge->amount = $form[$amount_k];
        }
        $fee = $eq_charge->amount;
        $lab = Q("$user lab")->current();
        $account = Q("{$lab} billing_account[department={$equipment->billing_dept}]")->current();
        $balance = $account->balance + $account->credit_line;
        $e->return_value = ($balance < 0 || $balance - $fee < (float)$equipment->record_balance_required);
        return TRUE;
    }

    public static function on_relationship_connect($e, $r1, $r2, $type = '')
    {
//        error_log($r1->name() . $r1->id);
//        error_log($r2->name() . $r2->id);
    }

    //计算当前检测项目的时长
    public static function getDtLength($apply_record, $connect_type = '')
    {
        $lenth = 0;

        $connect_type = $connect_type ?: $apply_record->connect_type;
        if ($apply_record->status == Service_Apply_Record_Model::STATUS_TEST) {
            foreach (Q("{$apply_record} {$connect_type}") as $connect) {
                if ($connect->dtend) $lenth += $connect->dtend - $connect->dtstart;
            }
        }

        return $lenth;

    }

    public function setDtLength($apply_record)
    {
        $lenth = self::getDtLength($apply_record);
        $apply_record->dtlength = $lenth;
        $apply_record->save();
    }

}