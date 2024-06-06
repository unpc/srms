<?php

class Approval
{
    public static function orm_model_saved($e, $object, $old_data, $new_data)
    {
        $modules = Config::get('approval.modules');
        if (!in_array($object->name(), $modules)) {
            return true;
        }

        $equipment = $object->equipment;

        
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        $is_reserv_pi_approval = $multi_lab ? $new_data['project']->lab->reserv_approval : Q("{$new_data['user']} lab")->current()->reserv_approval;
        // $new_data['user']->lab->reserv_approval;

        /**
         * 更正一下逻辑，仪器没勾选预约审核的预约记录，就不需要往下走了
         * 而送样审核依然要往下走 for PI审核
         */
        if (!$equipment->need_approval && $object->name() == 'eq_reserv' && !$is_reserv_pi_approval) {
            return true;
        }

        $use_r = $object->name() == 'eq_sample' ? $object->sender : $object->user ;
        $isPi = $multi_lab ? $new_data['project']->lab->owner->id == $use_r->lab->id : Q("{$use_r}<pi lab")->current()->id;

        if($isPi && !$equipment->need_approval && $object->name() == 'eq_reserv'){
            return true;
        }

        switch ($object->name()) {
            case 'eq_reserv':
                //多课题组更换课题组时重新审批
                if ($multi_lab && $old_data['project']->lab->id != $new_data['project']->lab->id) {
                    $approval = O('approval', ['source' => $object]);
                    Q("approved[source=$approval]")->delete_all();
                    $approval->delete();
                }
                //如果是机主或者管理员更改用户
                if ($old_data['user']->id != $new_data['user']->id) {
                    $approval = O('approval', ['source' => $object]);
                    Q("approved[source=$approval]")->delete_all();
                    $approval->delete();
                }

                break;
            case 'eq_sample':
                if ($old_data['sender']->id != $new_data['sender']->id) {
                    $approval = O('approval', ['source' => $object]);
                    Q("approved[source=$approval]")->delete_all();
                    $approval->delete();
                }
                break;
        }

        

        // 创建新的approval, 注意要放在更改用户后..逻辑是先删再新增...
        $approval = O('approval', ['source' => $object]);
        if ($approval->id) {
            $approval->dtstart = $object->dtstart ?: ($approval->dtstart ?: 0);
            $approval->dtend = $object->dtend ?: ($approval->dtend ?: 0);
            $approval->save();
            return true;
        }

        if($isPi){
            $approval->is_skip = 1;
        }

        $approval->source = $object;
        $approval->create();
    }

    public static function orm_model_saved_pass($e, $object, $old_data, $new_data)
    {
        $approval = O('approval', ['source' => $object]);
        if (!$approval->id
            || $approval->flag == 'done'
            || $approval->flag == 'reject'
        ) {
            return;
        }

        switch ($approval->source->name()) {
            case 'eq_sample':
                switch ($new_data['status']) {
                    case EQ_Sample_Model::STATUS_APPROVED:
                    case EQ_Sample_Model::STATUS_TESTED:
                        $approval->pass();
                        break;
                    case EQ_Sample_Model::STATUS_REJECTED:
                    case EQ_Sample_Model::STATUS_CANCELED:
                        $approval->reject();
                        break;
                }
                break;
            case 'ue_training':
                switch ($new_data['status']) {
                    case UE_Training_Model::STATUS_APPROVED:
                        $approval->pass();
                        break;
                    case UE_Training_Model::STATUS_REFUSE:
                    case UE_Training_Model::STATUS_DELETED:
                    case UE_Training_Model::STATUS_OVERDUE:
                        $approval->reject();
                        break;
                }
                break;
        }
    }

    public static function on_approval_saved($e, $approval, $old_data, $new_data)
    {
        $me = L('ME');
        $approved = O('approved', ['source' => $approval, 'flag' => $approval->flag]);
        if (!$approved->id) {
            $approved = O('approved');
            $approved->source = $approval;
            $approved->auditor = $approval->auto ? O('user') : $me;
            $approved->flag = $approval->flag;
            $approved->description = $approval->description;
            $approved->save();
        }

        // 审批驳回会保存approval两次，导致会发两次驳回消息，所以此处驳回不发消息，单独发送。
        if ($approval->flag != 'rejected') {
            //同时发送消息
            Event::trigger("{$approval->flag}.message", $approved);
        }
    }

    //预约审核的chebox
    public static function eq_reserv_requirement_extra_view($e, $equipment, $disabled)
    {
        $me = L('ME');
        $e->return_value = V('approval_flow:reserv/extra', ['equipment' => $equipment, 'disabled' => $disabled]);
        return false;
    }

    public static function equipment_accept_reserv_change($e, $equipment, $accept_reserv)
    {
        if (!$accept_reserv) {
            $equipment->need_approval = 0;
        }
        return false;
    }

    //审核配置
    public static function eq_reserv_need_approval($e, $form, $equipment)
    {
        $me = L('ME');
        $accept_reserv = (int)($form['accept_reserv'] == 'on');
        $need_approval = (int)($form['need_approval'] == 'on');
        if ($accept_reserv) {
            $equipment->need_approval = $need_approval;
        }
        return true;
    }

    //绑定视图
    public static function setup_view()
    {
        Event::bind('profile.view.tab', 'Approval::approval_tab');
        Event::bind('profile.view.content', 'Approval::approval_tab_content', 0, 'approval');
    }

    //预约审核的tab
    public static function approval_tab($e, $tabs)
    {
        $me = L('ME');
        if ($me->id == $tabs->user->id && $me->can_approval('tab')) {
            $tabs
                ->add_tab('approval', [
                    'url' => $tabs->user->url('approval'),
                    'title' => I18N::T('approval', '预约申请审核'),
                    'weight' => 102
                ]);
        }
    }

    public static function approval_tab_content($e, $tabs)
    {
        $me = L('ME');

        $flow = Config::get('flow.eq_reserv');
        $params = Config::get('system.controller_params');
        foreach ($flow as $key => $v) {
            if ($me->can_approval($key, '')) {
                $default = $key;
                break;
            }
        }
        $status = $params[2] ? : $default;

        Event::bind('profile.approval.view.tabs', 'Approval::_user_profile_approval_tabs', 0, $status);
        Event::bind('profile.approval.view.content', 'Approval::_user_profile_approval_content', 0, $status);
        
        $tabs->content = V('approval_flow:profile/content');

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->set('user', $tabs->user)
            ->tab_event('profile.approval.view.tabs')
            ->content_event('profile.approval.view.content')
            ->select($status);
    }
    
    // 预约审核里的tab
    public static function _user_profile_approval_tabs($e, $tabs)
    {
        $me  = L('ME');
        $user = $tabs->user;
        $params = Config::get('system.controller_params');
        $flow = Config::get('flow.eq_reserv');
        foreach ($flow as $step => $operation) {
            if (!$me->can_approval($step, '')) {
                continue;
            }
            $form = Lab::form(function (&$old_form, &$form) {
                if (isset($form['date_filter'])) {
                    if (!$form['dtstart_check']) {
                        unset($old_form['dtstart_check']);
                    }
                    if (!$form['dtend_check']) {
                        unset($old_form['dtend_check']);
                    } else {
                        $form['dtend'] = Date::get_day_end($form['dtend']);
                    }
                    unset($form['date_filter']);
                }
            });
            
            $selector = Approval_Help::make_selector($form, $step);
            $tabs->add_tab($step, [
                'url' => $user->url('approval.'.$step),
                'title'=> I18N::T('approval', $operation['title'].' (%count)', [
                    '%count'=> Q($selector)->total_count(),
                ]),
                'weight' => 0,
            ])
            ->set('class', 'secondary_tabs');
        }
    }
    //预约审核tab里的内容
    public static function _user_profile_approval_content($e, $tabs)
    {
        $me  = L('ME');
        $params = Config::get('system.controller_params');
        $flows = Config::get('flow.eq_reserv');
        foreach ($flows as $step => $flow) {
            if ($me->can_approval($step, '')) {
                $key = $step;
                break;
            }
        }

        $flag = $params[2] ? : $key;

        $form = Lab::form(function (&$old_form, &$form) {
            if (isset($form['date_filter'])) {
                if (!$form['dtstart_check']) {
                    unset($old_form['dtstart_check']);
                }
                if (!$form['dtend_check']) {
                    unset($old_form['dtend_check']);
                } else {
                    $form['dtend'] = Date::get_day_end($form['dtend']);
                }
                unset($form['date_filter']);
            }
        });

        $selector = Approval_Help::make_selector($form, $flag);
        
        $approval = Q($selector);

        $pagination = Lab::pagination($approval, (int)$form['st'], 20);

        $tabs->content = V('approval_flow:approval/list', [
            'flag' => $flag,
            'approval' => $approval,
            'pagination' => $pagination,
            'form' => $form,
            'sort_by' => $sort_by ? : 'date',
            'sort_asc' => $form['sort_asc'],
        ]);
    }

    //calendar悬浮的时候显示的
    public static function component_info_extra($e, $component)
    {
        $parent = $component->calendar->parent;
        if ($parent->name() == 'equipment') {
            $reserv = O('eq_reserv', ['component' => $component]);
            $e->return_value = V('approval_flow:approval/extra', ['reserv' => $reserv]);
        }
        return;
    }

    //删掉预约的时候 把哪些还是申请中和已通过的审批删掉
    public static function on_object_deleted($e, $object)
    {
        $modules = Config::get('approval.modules');
        if (!in_array($object->name(), $modules)) {
            return true;
        }
        $approval = O('approval', ['source' => $object]);
        if ($approval->flag != 'rejected' && $approval->flag != 'expired') {
            $approved = Q("approved[source=$approval]")->delete_all();
            $approval->delete();
        }
        if ($object->name() == 'eq_sample') {
        }
        if ($object->name() == 'eq_reserv') {
            //多课题组保留历史记录
            if ($GLOBALS['preload']['people.multi_lab']) {
                $history_reserv = O('approved_reject_reserv', ['source' => $object]);
                $history_reserv->source = $object;
                $history_reserv->project = $object->project;
                $history_reserv->ctime = Date::time();
                $history_reserv->save();
            }
        }
    }

    public static function modify_is_allowed($e, $user, $perm_name, $component, $options)
    {
        try {
            $parent = $component->calendar->parent;
            
            if ($parent->name() == 'equipment') {
                $reserv = O('eq_reserv', ['component'=>$component]);
                $approval = O('approval', ['source' => $reserv]);
                $is_admin = (bool)Q("{$user}<incharge $parent")->total_count() || $user->access('管理所有内容');
                $flags = array_keys(Config::get("flow.eq_reserv"));

                $now = Date::time();
                //关联了已经被使用的记录,无法更新 已增加测试用例
                //关联了已经被使用的记录，1，不是自己的预约，不能修改。2，超过预约时间，不能修改
			    if ($reserv->id && ($user->id != $component->organizer->id || $now >= $component->get('dtend', TRUE))) {
				    if (Q("eq_record[reserv={$reserv}][dtend>0]")->total_count() > 0) {
					    $e->return_value = FALSE;
					    return FALSE;
				    }
			    }


                if ($user->access('修改所有仪器的预约')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }

                if ($user->group->id && $user->access('修改下属机构仪器的预约') && $user->group->is_itself_or_ancestor_of($parent->group)) {
                    $e->return_value = TRUE;
                    return FALSE;
                }

                if ($user->access('修改负责仪器的预约') && Equipments::user_is_eq_incharge($user, $parent)) {
                    $e->return_value = TRUE;
                    return FALSE;
                }

                if (!$is_admin && $approval->id && $approval->flag != $flags[0]) {//非机主一旦进入审核流程就不可修改
                    if ($flags[1] == 'approve_incharge' && $flags[1] == $approval->flag) {
                        // 第一步是PI审核 第二步是机主审核 目前到了第二步
                        // 不需要pi审核 可以修改预约, 这个人是PI，可以修改预约
                        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
                        $lab = $multi_lab ? $reserv->project->lab : Q("{$approval->user}<pi lab")->current();
                        if ($lab->reserv_approval && $lab->owner->id != $me->id) {
                            throw new Exception(I18N::T('approval', '已进入审批流程，不能修改！'));
                        }
                    } else {
                        throw new Exception(I18N::T('approval', '已进入审批流程，不能修改！'));
                    }
                }
                if ($approval->id && $approval->flag == 'done') {
                    throw new Exception(I18N::T('approval', '已经审核通过，不能修改！'));
                }
            }
        } catch (Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, $e->getMessage());
            $e->return_value = false;
            return false;
        }
    }

    //创建预约时判断是否需要审核
    public static function model_approval_create($e, $object)
    {
        $me = L('ME');
        switch ($object->name()) {
            case 'eq_reserv':
                $e->return_value = false;
                return false;
                /* $equipment = $object->equipment;
                if ($equipment->need_approval) {
                    $e->return_value = false;
                    return false;
                } */
                break;
            case 'eq_sample':
                $modules = Config::get('approval.modules');
                $e->return_value = !in_array('eq_sample', $modules);
                return;
                break;
            default:
                $e->return_value = true;
                return false;
        }
    }

    public static function model_approval_after_reject($e, $approval)
    {
        switch ($approval->source->name()) {
            case 'eq_reserv':
                $reserv = $approval->source;
                $approval->dtstart = $reserv->dtstart;
                $approval->dtend = $reserv->dtend;
                $approval->reserv_desc = $reserv->component->description;
                if ($approval->save()) {
                    $approved = O('approved', ['source' => $approval, 'flag' => $approval->flag]);
                    Event::trigger("rejected.message", $approved);
                    $reserv->component->delete();
                }
                break;
            case 'eq_sample':
                $sample = $approval->source;
                $approval->dtsubmit = $sample->dtsubmit;
                $approval->count = $sample->count;
                $approval->description = $sample->description;
                $approval->note = $sample->note;
                if ($approval->save()) {
                    $approval->source->status = EQ_Sample_Model::STATUS_REJECTED;
                    $approval->source->save();
                }
                break;
            default:
                break;
        }
        return true;
    }

    public static function eq_reserv_approval_pi_str($e, $reserv)
    {
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        if ($multi_lab) {
            $pi = Q("{$reserv->project->lab}<pi user");
        } else {
            $pi = Q("$reserv->user lab<pi user");
        }
        $e->return_value = I18N::T('approval', '课题组负责人未审核，待%next_user审核', [
                '%next_user' => V('approval_flow:approval/next_user', ['users' => $pi])
            ]);
        return true;
    }

    public static function eq_reserv_approve_incharge_str($e, $reserv)
    {
        $equipment = $reserv->equipment;
        $incharges = Q("$equipment<incharge user");
        $e->return_value = I18N::T('approval', '仪器负责人未审核，待%next_user审核', [
                '%next_user' => V('approval_flow:approval/next_user', ['users' => $incharges])
            ]);
        return true;
    }

    public static function eq_reserv_done_str($e, $reserv)
    {
        $e->return_value = $status = I18N::T('approval', '已通过');
        return true;
    }

    public static function approval_view_expired_str($e, $approved)
    {
        $approval = $approved->source;
        $source = $approval->source->name();
        $flow = Config::get("flow.{$source}");
        $key_flow = array_keys($flow);
        // 从数组中删除done, expired, reject等元素, 使用值匹配删除
        $valid_keys = implode(',', array_diff($key_flow, ['done', 'expired', 'reject']));
        $ap = Q("approved[source={$approved->source}][flag={$valid_keys}][auditor_id=0]:sort(ctime D)")->current();
        $e->return_value = I18N::T('approval', '审核由于'.$flow[$ap->flag]['title'].'审核逾期被删除');
        return true;
    }

    public static function approval_view_rejected_str($e, $approved)
    {
        $e->return_value = I18N::T('approval', '%user驳回了审核 %description', [
            '%user' => V('approval_flow:approval/user', ['user' => $approved->auditor]),
            '%description' => $approved->description ? T('备注：').$approved->description : ''
        ]);
        return true;
    }

    public static function approval_view_done_str($e, $approved)
    {
        $e->return_value = I18N::T('reserv_approve', '%user审核通过 %description', [
            '%user' => V('approval_flow:approval/user', ['user' => $approved->auditor]),
            '%description' => $approved->description ? T('备注：').$approved->description : ''
        ]);
        return true;
    }

    public static function approval_view_approve_incharge_str($e, $approved)
    {
        $e->return_value = self::view_pass_str($approved);
        return true;
    }

    public static function approval_view_approval_pi_str($e, $approved)
    {
        $e->return_value = self::view_pass_str($approved);
        return true;
    }

    public static function view_pass_str($approved)
    {
        return I18N::T('reserv_approve', '%user审核通过 %description', [
            '%user' => V('approval_flow:approval/user', ['user' => $approved->auditor, 'approved' => $approved]),
            '%description' => $approved->description ? T('备注：').$approved->description : ''
        ]);
    }

    public static function approval_approval_pi_pre_selector($e)
    {
        $me = L('ME');
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        if ($multi_lab) { //如果是多课题组就跟项目关联
            $e->return_value = "";
        } else {//但课题组跟人关联
            $e->return_value = "{$me}<pi lab user";
        }
        return true;
    }

    public static function approval_approval_pi_selector($e)
    {
        $me = L('ME');
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        if ($multi_lab) { //如果是多课题组就跟项目关联
            $lab = Q("{$me}<pi lab")->current();
            $projects = Q("lab_project[lab={$lab}]]")->to_assoc('id', 'id');//取出课题组下关联的项目
            $project_ids = join(',', $projects);
            if ($project_ids) {
                $reservs = Q("eq_reserv[project_id={$project_ids}]")->to_assoc('id', 'id');
                $reserv_ids = join(',', $reservs);
            } else {
                $reserv_ids = 0;
            }
            $e->return_value = "approval[flag=approve_pi][source_id={$reserv_ids}][source_name=eq_reserv]";
        } else {
            $e->return_value = " approval[flag=approve_pi][source_name=eq_reserv]";
        }
        return true;
    }

    public static function approval_approve_incharge_pre_selector($e)
    {
        $me = L('ME');
        $e->return_value = "{$me}<incharge equipment ";
        return true;
    }

    public static function approval_approve_incharge_selector($e)
    {
        $me = L('ME');
        $e->return_value = ' approval[flag=approve_incharge][source_name=eq_reserv]';
        return true;
    }

    public static function approval_done_pre_selector($e)
    {
        $e->return_value = "";
        return true;
    }

    public static function approval_done_selector($e)
    {
        $e->return_value = self::make_multi_selector('done');
        return true;
    }

    public static function approval_reject_pre_selector($e)
    {
        $me = L('ME');
        $e->return_value = '';
        return true;
    }

    public static function approval_reject_selector($e)
    {
        $me = L('ME');
        $e->return_value = self::make_multi_selector('rejected');
        return true;
    }

    public static function approval_expired_pre_selector($e)
    {
        $me = L('ME');
        $e->return_value = '';
        return true;
    }

    public static function approval_expired_selector($e)
    {
        $me = L('ME');
        $e->return_value = self::make_multi_selector('expired');
        return true;
    }

    private static function make_multi_selector($flag)
    {
        $me = L('ME');
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        if ($multi_lab) { //如果是多课题组就跟项目关联
            $lab = Q("{$me}<pi lab")->current();
            $projects = Q("lab_project[lab={$lab}]")->to_assoc('id', 'id');//取出课题组下关联的项目
            $project_ids = join(',', $projects);
            $equipments = Q("{$me}<incharge equipment")->to_assoc('id', 'id');
            $equipment_ids = join(',', $equipments);
            if (!$equipment_ids) {
                $equipment_ids = 0;
            }
            if ($project_ids) {
                if ($flag == 'rejected' || $flag == 'expired') {
                    $reservs = Q("approved_reject_reserv[project_id={$project_ids}]")->to_assoc('source_id', 'source_id');
                    $reserv_ids = join(',', $reservs);
                } else {
                    $reservs = Q("eq_reserv[project_id={$project_ids}]")->to_assoc('id', 'id');
                    $reserv_ids = join(',', $reservs);
                }
            } else {
                $reserv_ids = 0;
            }
            return "approval[flag={$flag}][source_name=eq_reserv][source_id=$reserv_ids|equipment_id=$equipment_ids]";
        } else {
            return "approval[flag={$flag}][source_name=eq_reserv]";
        }
    }

    public static function reserv_lab($ap)
    {
        $lab_text = '';
        if ($GLOBALS['preload']['people.multi_lab']) {
            if ($ap->source->project->id) {
                $lab = $ap->source->project->lab;
            } else {
                $lab = O('approved_reject_reserv', ['source_id' => $ap->source_id])->project->lab;
            }
            if ($lab->id) {
                $lab_text = '<a href="'.$lab->url().'">'.$lab->name.'</a>';
            }
        } else {
            foreach (Q("$ap->user lab") as $lab) {
                $lab_text .= '<a href="'.$lab->url().'">'.$lab->name.'</a> ' ;
            }
        }
        return $lab_text;
    }

    public static function pending_count($e, $user) {
        if (!$user->id) return;
        $approval = Q("{$user}<incharge equipment approval[source_name=eq_reserv][flag=approve_incharge]")->total_count();
        $e->return_value = $approval;
    }
}
