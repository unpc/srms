<?php

class Approval {

    static function eq_reserv_requirement_extra_view($e, $equipment, $disabled) {
        $me = L('ME');
        if ($me->is_allowed_to('修改预约设置', $equipment)) {

            $untro = O('yiqikong_approval_uncontrol',['equipment'=>$equipment,'approval_type' => 'eq_reserv']);
            $uncontroluser = $untro->uncontroluser ?? '';
            $uncontrollab = $untro->uncontrollab ?? '';
            $uncontrolgroup = $untro->uncontrolgroup ?? '';
            $untroalInfo = [
                'uncontroluser' => $uncontroluser,
                'uncontrollab' => $uncontrollab,
                'uncontrolgroup' => $uncontrolgroup,
            ];
            $e->return_value = V('yiqikong_approval:eq_reserv/edit/extra', ['uncontrolInfo'=>$untroalInfo,'equipment' => $equipment, 'disabled' => $disabled]);

        }
        return FALSE;
    }

    static function eq_reserv_need_approval($e, $form, $equipment) {
        $me = L('ME');
        $accept_reserv = (int)($form['accept_reserv'] == 'on');
        $need_approval = (int)($form['need_approval'] == 'on');
        if ($accept_reserv && $me->is_allowed_to('修改预约设置', $equipment)) {
            $equipment->need_approval = $need_approval;
        }
        $untro = O('yiqikong_approval_uncontrol',['equipment'=>$equipment,'approval_type' => 'eq_reserv']);
        if($need_approval){
            //个别用户设置
            $key = 0;
            $untro->equipment = $equipment;
            $untro->uncontroluser = ($form['select_user_mode_user'][$key] == 'on' && $form['user'][$key] != '{}')
                ? $form['user'][$key] : '';

            $untro->uncontrollab = ($form['select_user_mode_lab'][$key] == 'on' && $form['lab'][$key] != '{}')
                ? $form['lab'][$key] : '';

            $untro->uncontrolgroup = ($form['select_user_mode_group'][$key] == 'on' && $form['group'][$key] != '{}')
                ? $form['group'][$key] : '';
            $untro->save();

        }else{
            if($untro->id)
                $untro->delete();
        }
        return TRUE;
    }

    static function equipment_accept_reserv_change($e, $equipment, $accept_reserv) {
        if (!$accept_reserv) {
            $equipment->need_approval = 0;
        }
        return FALSE;
    }

    static function setup_view() {
        Event::bind('profile.view.tab', 'Approval::approval_tab');
        Event::bind('profile.view.content', 'Approval::approval_tab_content', 0, 'approval');

        Event::bind('equipment.index.tab', 'Approval::eq_approval_tab');
        Event::bind('equipment.index.tab.content', 'Approval::eq_approval_tab_content', 0, 'approval');
        // Event::bind('equipment.index.tab.tool_box', 'Approval::eq_approval_tab_tool_box', 0, 'approval');
    }

    static function approval_tab($e, $tabs) {

        $me = L('ME');

        if ($me->id == $tabs->user->id) {
            $tabs
                ->add_tab('approval', [
                    'url' => $tabs->user->url('approval'),
                    'title' => I18N::T('approval', '预约审批'),
                    'weight' => 102
                ]);
        }
    }

    static function eq_approval_tab($e, $tabs) {
        $equipment = $tabs->equipment;
        $me = L('ME');

        $approvals = Q("approval[equipment=$equipment]")->total_count();
        if ($approvals && $equipment->accept_reserv && $equipment->need_approval && $equipment->status != EQ_Status_Model::NO_LONGER_IN_SERVICE) {
        $tabs
            ->add_tab('approval', [
                'url' => $equipment->url('approval'),
                'title' => I18N::T('approval', '预约审批'),
                'weight' => 102
            ]);
        }
    }

    static function approval_tab_content($e, $tabs) {

        $flow = Config::get('flow.eq_reserv');

        foreach ($flow as $step => $operation) {
            Event::bind('profile.approval.view.tabs', 'Approval::_user_profile_approval_tabs', 0, $step);
        }

        $tabs->content = V('yiqikong_approval:profile/content');

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->set('user', $tabs->user)
            ->tab_event('profile.approval.view.tabs')
            ->content_event('profile.approval.view.content')
            ->select($params[2]);
    }

    static function eq_approval_tab_content($e, $tabs) {

        $flow = Config::get('flow.eq_reserv');

        foreach ($flow as $step => $operation) {
            Event::bind('equipment.approval.view.tabs', 'Approval::_eq_approval_tabs', 0, $step);
        }

        $tabs->content = V('yiqikong_approval:profile/content');

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->set('equipment', $tabs->equipment)
            ->tab_event('equipment.approval.view.tabs')
            ->content_event('equipment.approval.view.content')
            ->select($params[2]);

        self::eq_approval_tab_tool_box(null, $tabs->content->secondary_tabs);
    }

    static function eq_approval_tab_tool_box($e, $tabs)
    {
        $form = Lab::form();
        $columns = [
            /*'equipment'=> [
                'title' => I18N::T('yiqikong_approval', '仪器名称'),
                'filter' => [
                    'form' => V('yiqikong_approval:approval_table/filters/equipment', ['equipment' => $form['equipment']]),
                    'value' => $form['equipment'] ? H($form['equipment']) : NULL
                ],
                'align' => 'left',
                'nowrap' => TRUE
            ],*/
            'user'=> [
                'title' => I18N::T('yiqikong_approval', '申请人'),
                'filter' => [
                    'form' => V('yiqikong_approval:approval_table/filters/user', ['user' => $form['user']]),
                    'value' => $form['user'] ? O('user', H($form['user']))->name : NULL
                ],
                'align' => 'left',
                'nowrap' => TRUE
            ],
            /*'date' => [
                'title' => I18N::T('yiqikong_approval', '预约起止时间'),
                'align' => 'left',
                'nowrap' =>TRUE,
                'filter' => [
                    'form' => V('yiqikong_approval:approval_table/filters/date', ['form' => $form]),
                    'value' => $form['date'] ? H($form['date']) : NULL,
                    'field' => 'dtstart_check,dtend_check,dtstart,dtend'
                ]
            ],*/
        ];
        $tabs->search_box = V('application:search_box', ['top_input_arr' => ['user'], 'columns' => $columns]);
    }

    static function _user_profile_approval_tabs($e, $tabs) {

        $me  = L('ME');

        $user = $tabs->user;

        $flow = Config::get('flow.eq_reserv');

        foreach ($flow as $step => $operation) {

             $form = Lab::form(function(&$old_form, &$form) {
                if (isset($form['date_filter'])) {
                    if (!$form['dtstart_check']) unset($old_form['dtstart_check']);
                    if (!$form['dtend_check']) unset($old_form['dtend_check']);
                    else $form['dtend'] = Date::get_day_end($form['dtend']);
                    unset($form['date_filter']);
                }
            });

            $selector = Approval_Help::make_selector($form, $step, $tabs->user);

            Event::bind('profile.approval.view.content', 'Approval::_user_profile_approval_content', 0, $step);

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

    static function _eq_approval_tabs($e, $tabs) {

        $me  = L('ME');

        $equipment = $tabs->equipment;

        $flow = Config::get('flow.eq_reserv');

        foreach ($flow as $step => $operation) {

                $form = Lab::form(function(&$old_form, &$form) {
                if (isset($form['date_filter'])) {
                    if (!$form['dtstart_check']) unset($old_form['dtstart_check']);
                    if (!$form['dtend_check']) unset($old_form['dtend_check']);
                    else $form['dtend'] = Date::get_day_end($form['dtend']);
                    unset($form['date_filter']);
                }
            });

            $selector = Approval_Help::make_selector($form, $step, $tabs->equipment);

            Event::bind('equipment.approval.view.content', 'Approval::_eq_approval_content', 0, $step);

            $tabs->add_tab($step, [
                'url' => $equipment->url('approval.'.$step),
                'title'=> I18N::T('approval', $operation['title'].' (%count)', [
                    '%count'=> Q($selector)->total_count(),
                ]),
                'weight' => 0,
            ])
            ->set('class', 'secondary_tabs');
        }


    }

    static function _user_profile_approval_content($e, $tabs) {

        $me  = L('ME');

        $params = Config::get('system.controller_params');

        $flag = $params[2] ? : 'approve';

        $form = Lab::form(function(&$old_form, &$form) {
            if (isset($form['date_filter'])) {
                if (!$form['dtstart_check']) unset($old_form['dtstart_check']);
                if (!$form['dtend_check']) unset($old_form['dtend_check']);
                else $form['dtend'] = Date::get_day_end($form['dtend']);
                unset($form['date_filter']);
            }
        });

        $selector = Approval_Help::make_selector($form, $flag, $tabs->user);

        $approval = Q($selector);

        $pagination = Lab::pagination($approval, (int)$form['st'], 20);

        $tabs->content = V('yiqikong_approval:approval/list', [
            'type' => 'people',
            'flag' => $flag,
            'approval' => $approval,
            'pagination' => $pagination,
            'form' => $form,
            'sort_by' => $sort_by ? : 'date',
			'sort_asc' => $form['sort_asc'],
        ]);
    }

    static function _eq_approval_content($e, $tabs) {
        $me  = L('ME');

        $params = Config::get('system.controller_params');

        $flag = $params[2] ? : 'approve';

        $form = Lab::form(function(&$old_form, &$form) {
            if (isset($form['date_filter'])) {
                if (!$form['dtstart_check']) unset($old_form['dtstart_check']);
                if (!$form['dtend_check']) unset($old_form['dtend_check']);
                else $form['dtend'] = Date::get_day_end($form['dtend']);
                unset($form['date_filter']);
            }
        });

        $selector = Approval_Help::make_selector($form, $flag, $tabs->equipment);

        $approval = Q($selector);

        $pagination = Lab::pagination($approval, (int)$form['st'], 20);

        $tabs->content = V('yiqikong_approval:approval/list', [
            'type' => 'equipment',
            'flag' => $flag,
            'approval' => $approval,
            'pagination' => $pagination,
            'form' => $form,
            'sort_by' => $sort_by ? : 'date',
            'sort_asc' => $form['sort_asc'],
        ]);
    }

    static function eq_reserv_approval_create($e, $reserv) {
        $me = L('ME');
        $equipment = $reserv->equipment;
        $is_incharge = Equipments::user_is_eq_incharge($reserv->user, $equipment);
        if ($equipment->need_approval && !$is_incharge) {
            $e->return_value = TRUE;
            return FALSE;
        }
        $e->return_value = FALSE;
        return FALSE;
    }

    static function eq_reserv_approval_create_once($e, $approval) {
        Approval_Message::create($approval);
    }

    static function eq_reserv_approval_after_pass($e, $approval) {
        Approval_Message::result('通过', $approval);

        $reserv = $approval->source;
        $reserv->approval = Approval_Model::RESERV_APPROVAL_PASS;
        $reserv->save();

        return FALSE;
    }

    static function eq_reserv_approval_after_reject($e, $approval) {
        $reserv = $approval->source;
        $approval->dtstart = $reserv->dtstart;
        $approval->dtend = $reserv->dtend;
        $approval->sample_count = $reserv->sample_count;
        $approval->reserv_desc = $reserv->component->description;
        $approval->project_id = $approval->source->project->id;
        if ($approval->save()) {

            $reserv->approval = Approval_Model::RESERV_APPROVAL_REJECT;
            $reserv->save();

            $reserv->component->delete();
            Approval_Message::result('驳回', $approval);
        }
        return FALSE;
    }

    static function on_eq_reserv_saved($e, $reserv, $old_data, $new_data) {
        $equipment = $reserv->equipment;
        if (!!$equipment->need_approval == FALSE) {
            $approval = O('approval', ['source' => $reserv, 'flag' => 'approve']);
            if (Q("approved[source=$approval]")->delete_all()) {
                $approval->delete();
            }
        } else {
            if ($old_data['approval'] == 0 && ($old_data['approval'] != $new_data['approval'])) {
                $approval = O('approval', ['source' => $reserv, 'flag' => 'approve']);
                if ($new_data['approval'] == Approval_Model::RESERV_APPROVAL_PASS) {
                    $done = O('approval', ['source' => $reserv, 'flag' => 'done']);
                    if (!$done->id) {
                        $approval->description = $reserv->component->approval_description;
                        $approval->pass();
                    }                    
                } else if ($new_data['approval'] == Approval_Model::RESERV_APPROVAL_REJECT) {
                    $rejected = O('approval', ['source' => $reserv, 'flag' => 'rejected']);
                    if (!$rejected->id) {
                        $approval->description = $reserv->component->approval_description;
                        $approval->reject();
                    }
                }
            }
        }
    }
    
    static function on_eq_reserv_deleted($e, $reserv) {

        $approval = O('approval', ['source' => $reserv]);

        if ($approval->flag != 'rejected' && $approval->flag != 'expired') {
            $approved = Q("approved[source=$approval]")->delete_all();
            $approval->delete();
        }
    }

    static function modify_is_allowed($e, $user, $perm_name, $component, $options) {
        try {
            $parent = $component->calendar->parent;
            if ($parent->name() == 'equipment') {
                $reserv = O('eq_reserv', ['component'=>$component]);
                $approval = O('approval', ['source' => $reserv]);

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

                if($approval->id && $approval->flag == 'done') {
                    throw new Exception(I18N::T('approval', '已经审核通过，不能修改！'));
                }
            }
        } catch (Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, $e->getMessage());
            $e->return_value = FALSE;
            return FALSE;
        }
    }

    static function component_info_extra($e, $component) {
        $parent = $component->calendar->parent;
         if ($parent->name() == 'equipment') {
            $reserv = O('eq_reserv', ['component' => $component]);
            $e->return_value = V('yiqikong_approval:approval/extra', ['reserv' => $reserv]);
        }
        return;
    }

    static function orm_model_saved($e, $object, $old_data, $new_data) {
        $modules = Config::get('approval.modules');
        if (!in_array($object->name(), $modules)) {
			return TRUE;
		}

        if ($object->name() == 'eq_reserv' && $object->equipment->need_approval) {
            $approval = O('approval', ['source' => $object]);
            if (!$approval->id) {
                $approval = O('approval');
            }
            $approval->source = $object;
            $approval->create($object);
        }
    }

    static function on_approval_saved($e, $approval, $old_data, $new_data) {
        $me = L('ME');
        $approved = O('approved', ['source' => $approval, 'flag' => $approval->flag]);
        if (!$approved->id) {
            $approved = O('approved');
            $approved->source = $approval;
            $approved->auditor = $me;
            $approved->flag = $approval->flag;
            $approved->save();
        }
    }

    public static function pending_count($e, $user) {
        if (!$user->id) return;

        $approval = Q("{$user}<incharge equipment approval[flag=approve]")->total_count();

        $e->return_value = $approval;
    }
}
