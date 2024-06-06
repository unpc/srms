<?php

class Index_Controller extends Base_Controller {
    function index($tab = 'approve'){
        if (!L('ME')->is_allowed_to('审核', 'reserv_approve')) {
            $tab = 'mine';
        }

        Event::bind('reserv_approve.index.content', [$this, '_index_mine_content'], 0, 'mine');
        Event::bind('reserv_approve.index.content', [$this, '_index_approve_content'], 0, 'approve');
        Event::bind('reserv_approve.index.content', [$this, '_index_all_content'], 0, 'all');

        $this->layout->body->primary_tabs
            ->content_event('reserv_approve.index.content')
            ->select($tab);
    }

    function _index_mine_content($e, $tabs) {

        $me = L('ME');

        $params = (array)explode('.', Input::arg());
        $param = array_pop($params);
        $tab = array_key_exists($param, Szu_Eq_Reserv_Model::$state) ? $param : Szu_Eq_Reserv_Model::STATE_APPROVE;

        if ($tab == Szu_EQ_Reserv_Model::STATE_REJECT || $tab == Szu_EQ_Reserv_Model::STATE_CANCEL) {
            $selector  = "abandon_reserv";
            $selector .= '[approve_status='.Szu_EQ_Reserv_Model::$state_num[$tab].']';
        }
        else {
            $selector  = "eq_reserv";
            $selector .= '[approve_status='.Szu_EQ_Reserv_Model::$state_num[$tab].']';
        }

        $form = Lab::form(function(&$old_form, &$form) {
            unset($form['type']);
            if (isset($form['date_filter'])) {
                if (!$form['dtstart_check']) unset($old_form['dtstart_check']);
                if (!$form['dtend_check']) unset($old_form['dtend_check']);
                else $form['dtend'] = Date::get_day_end($form['dtend']);
                unset($form['date_filter']);
            }
        });

        $date_value = Reserv_Approve::get_date_value($form);

        $pre_selector = ['approver' => "$me"];
        $selector = Reserv_Approve::make_selector($pre_selector, $selector, $form);

        $selector .= ':sort(dtstart D)';
        $reservs = Q($selector);
        $pagination = Lab::pagination($reservs, (int)$form['st'], 15);

        $content = V('reserv_approve:mine/list', [
            'form' => $form,
            'reservs' => $reservs,
            'date_value' => $date_value,
            'pagination' => $pagination,
        ]);

        $content->secondary_tabs = Widget::factory('tabs');

        foreach (Szu_Eq_Reserv_Model::$state as $key => $value) {
            $content->secondary_tabs
                ->add_tab($key, [
                    'url' => URI::url("!reserv_approve/index.mine.{$key}"),
                    'title' =>I18N::T('reserv_approve', $value),
                    'weight' => 0,
                ])
                ->set('class', 'secondary_tabs');
        }

        $content->secondary_tabs->select($tab);

        $tabs->content = $content;
    }

    function _index_approve_content($e, $tabs) {

        $me = L('ME');

        $params = (array)explode('.', Input::arg());
        $param = array_pop($params);
        $tab = array_key_exists($param, Szu_Eq_Reserv_Model::$approve_state) ? $param : Szu_Eq_Reserv_Model::STATE_APPROVE_NEED;

        if ($tab == Szu_EQ_Reserv_Model::STATE_APPROVE_NEED) {
            $pre_selector = ['approver' => "$me<approver"];
            $selector  = "eq_reserv";
        }
        elseif ($tab == Szu_EQ_Reserv_Model::STATE_APPROVE_DONE) {
            $pre_selector = ['approver' => "$me reserv_approve[status!=".Reserv_Approve_Model::STATUS_INIT."]<reserv"];
            $selector  = "eq_reserv[user!=$me]:not($me<approver eq_reserv)";
        }
        else {
            $pre_selector = ['approver' => "$me<approver"];
            $selector = "abandon_reserv";
            $selector .= '[approve_status='.Szu_EQ_Reserv_Model::$state_num[$tab]."][user!=$me]";
        }

        $form = Lab::form(function(&$old_form, &$form) {
            unset($form['type']);
            if (isset($form['ap_date_filter'])) {
                if (!$form['ap_dtstart_check']) unset($old_form['ap_dtstart_check']);
                if (!$form['ap_dtend_check']) unset($old_form['ap_dtend_check']);
                else $form['ap_dtend'] = Date::get_day_end($form['ap_dtend']);
                unset($form['ap_date_filter']);
            }
        });

        $date_value = Reserv_Approve::get_date_value($form);

        $selector = Reserv_Approve::make_selector($pre_selector, $selector, $form, TRUE);

        $selector .= ':sort(dtstart D)';
        $reservs = Q($selector);
        $pagination = Lab::pagination($reservs, (int)$form['st'], 15);

        $content = V('reserv_approve:approve/list', [
            'tab' => $tab,
            'form' => $form,
            'reservs' => $reservs,
            'date_value' => $date_value,
            'pagination' => $pagination,
        ]);

        $content->secondary_tabs = Widget::factory('tabs');

        foreach (Szu_Eq_Reserv_Model::$approve_state as $key => $value) {
            $content->secondary_tabs
                ->add_tab($key, [
                    'url' => URI::url("!reserv_approve/index.approve.{$key}"),
                    'title' =>I18N::T('reserv_approve', $value),
                    'weight' => 0,
                ])
                ->set('class', 'secondary_tabs');
        }

        $content->secondary_tabs->select($tab);

        $tabs->content = $content;
    }

    function _index_all_content($e, $tabs) {

        $me = L('ME');

        $params = (array)explode('.', Input::arg());
        $param = array_pop($params);
        $tab = array_key_exists($param, Szu_Eq_Reserv_Model::$approve_state) ? $param : Szu_Eq_Reserv_Model::STATE_APPROVE_NEED;

        if ($tab == Szu_EQ_Reserv_Model::STATE_APPROVE_NEED) {
            $pre_selector = ['approver' => "user<approver"];
            $selector  = "eq_reserv";
        }
        elseif ($tab == Szu_EQ_Reserv_Model::STATE_APPROVE_DONE) {
            $pre_selector = ['approver' => "reserv_approve[status=".Reserv_Approve_Model::STATUS_PASS."]<reserv"];
            $selector  = "eq_reserv";
        }
        else {
            $pre_selector = ['approver' => "user<approver"];
            $selector = "abandon_reserv";
            $selector .= '[approve_status='.Szu_EQ_Reserv_Model::$state_num[$tab]."]";
        }

        $form = Lab::form(function(&$old_form, &$form) {
            unset($form['type']);
            if (isset($form['ap_date_filter'])) {
                if (!$form['ap_dtstart_check']) unset($old_form['ap_dtstart_check']);
                if (!$form['ap_dtend_check']) unset($old_form['ap_dtend_check']);
                else $form['ap_dtend'] = Date::get_day_end($form['ap_dtend']);
                unset($form['ap_date_filter']);
            }
        });

        $date_value = Reserv_Approve::get_date_value($form);

        $selector = Reserv_Approve::make_selector($pre_selector, $selector, $form, TRUE);

        $selector .= ':sort(dtstart D)';
        $reservs = Q($selector);
        $pagination = Lab::pagination($reservs, (int)$form['st'], 15);

        $content = V('reserv_approve:approve/list', [
            'tab' => $tab,
            'form' => $form,
            'reservs' => $reservs,
            'date_value' => $date_value,
            'pagination' => $pagination,
        ]);

        $content->secondary_tabs = Widget::factory('tabs');

        foreach (Szu_Eq_Reserv_Model::$approve_state as $key => $value) {
            $content->secondary_tabs
                ->add_tab($key, [
                    'url' => URI::url("!reserv_approve/index.all.{$key}"),
                    'title' =>I18N::T('reserv_approve', $value),
                    'weight' => 0,
                ])
                ->set('class', 'secondary_tabs');
        }

        $content->secondary_tabs->select($tab);

        $tabs->content = $content;
    }
}

class Index_AJAX_Controller extends AJAX_Controller {
    function index_info_click() {
        $form = Input::form();
        $reserv = O('eq_reserv', $form['reserv_id']);
        if (!$reserv->id) {
            return FALSE;
        }

        $approves = Q("$reserv<reserv reserv_approve:sort(ctime)");

        $dialog_params = [
            'title'=> I18N::T('reserv_approve', '预约审核详情')
        ];

        JS::dialog((string) V('reserv_approve:info/dialog', [
            'reserv' => $reserv, 
            'approves' => $approves
        ]), $dialog_params);
    }

    function index_reject_info_click() {
        $form = Input::form();
        $reserv = O('abandon_reserv', ['old_id' => $form['reserv_id']]);
        if (!$reserv->id) {
            return FALSE;
        }

        $approves = Q("reserv_approve[reserv_id={$form['reserv_id']}]:sort(ctime)");

        $dialog_params = [
            'title'=> I18N::T('reserv_approve', '预约审核详情')
        ];

        JS::dialog((string) V('reserv_approve:info/dialog', [
            'reserv' => $reserv, 
            'approves' => $approves
        ]), $dialog_params);
    }

    function index_approve_click() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $approve = O('reserv_approve', $form['approve_id']);

        try {
            if (!($approve->id
                || ($approve->status == Reserv_Approve_Model::STATUS_INIT && $me->is_allowed_to('机主审核', $approve))
                || ($approve->status == Reserv_Approve_Model::STATUS_INCHARG_APPROVE && $me->is_allowed_to('PI审核', $approve))
                || ($approve->status == Reserv_Approve_Model::STATUS_PI_APPROVE && $me->is_allowed_to('领导审核', $approve))
                )) {
                throw new Exception(I18N::T('reserv_approve', '审核失败，请刷新重试'));
            }
            //弹出dialog编辑送样
            JS::dialog(
                V('reserv_approve:approve/form',
                    [
                        'approve'=>$approve,
                        'message'=>$message
                    ]),
                    ['title'=>I18N::T('eq_sample', '通过审核')]
                );
        } catch (Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, $e->getMessage());
            JS::refresh();
        }
    }

    function index_approve_submit() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $approve = O('reserv_approve', $form['approve_id']);
        try{
            if ($form['submit']) {
                if (!($approve->id
                    || ($approve->status == Reserv_Approve_Model::STATUS_INIT && $me->is_allowed_to('机主审核', $approve))
                    || ($approve->status == Reserv_Approve_Model::STATUS_INCHARG_APPROVE && $me->is_allowed_to('PI审核', $approve))
                    || ($approve->status == Reserv_Approve_Model::STATUS_PI_APPROVE && $me->is_allowed_to('领导审核', $approve))
                    )) {
                    $form->set_error('description', I18N::T('reserv_approve', '审核失败，请刷新重试') );
                }
                if (!$form->no_error) {
                    throw new Error_Exception;
                }
                $description = $form['description'];
                $approve->operate($me, FALSE, $description);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('reserv_approve', '审核通过，可在已批准中查看'));
                JS::refresh();
            }
        } catch (Exception $e) {
            JS::dialog(
                V('reserv_approve:approve/form',
                    [
                        'form'=>$form,
                        'approve'=>$approve,
                        'message'=>$message
                    ]
                ),
                ['title'=>I18N::T('eq_sample', '通过审核')]
            );
        }
    }

    function index_reject_click() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $approve = O('reserv_approve', $form['approve_id']);

        try {
            if (!($approve->id
                || ($approve->status == Reserv_Approve_Model::STATUS_INIT && $me->is_allowed_to('机主审核', $approve))
                || ($approve->status == Reserv_Approve_Model::STATUS_INCHARG_APPROVE && $me->is_allowed_to('PI审核', $approve))
                || ($approve->status == Reserv_Approve_Model::STATUS_PI_APPROVE && $me->is_allowed_to('领导审核', $approve))
                )) {
                throw new Exception(I18N::T('reserv_approve', '审核失败，请刷新重试'));
            }
            //弹出dialog编辑送样
            JS::dialog(
                V('reserv_approve:approve/form',
                    [
                        'reject'=>TRUE,
                        'approve'=>$approve,
                        'message'=>$message
                    ]),
                    ['title'=>I18N::T('eq_sample', '驳回审核')]
                );
        } catch (Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, $e->getMessage());
            JS::refresh();
        }
    }

    function index_reject_submit() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $approve = O('reserv_approve', $form['approve_id']);
        try{
            if ($form['submit']) {
                if (!($approve->id
                    || ($approve->status == Reserv_Approve_Model::STATUS_INIT && $me->is_allowed_to('机主审核', $approve))
                    || ($approve->status == Reserv_Approve_Model::STATUS_INCHARG_APPROVE && $me->is_allowed_to('PI审核', $approve))
                    || ($approve->status == Reserv_Approve_Model::STATUS_PI_APPROVE && $me->is_allowed_to('领导审核', $approve))
                    )) {
                    $form->set_error('description', I18N::T('reserv_approve', '审核失败，请刷新重试') );
                }
                elseif (trim($form['description']) == '') {
                    $form->set_error('description', I18N::T('reserv_approve', '请填写驳回原因') );
                }
                if (!$form->no_error) {
                    throw new Error_Exception;
                }
                $description = $form['description'];
                $approve->operate($me, TRUE, $description);
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('reserv_approve', '审核驳回，可在已驳回中查看'));
                JS::refresh();
            }
        } catch (Exception $e) {
            JS::dialog(
                V('reserv_approve:approve/form',
                    [
                        'form'=>$form,
                        'reject'=>TRUE,
                        'approve'=>$approve,
                        'message'=>$message
                    ]
                ),
                ['title'=>I18N::T('eq_sample', '驳回审核')]
            );
        }
    }

    function index_approve_selected_click() {
        $form = Form::filter(Input::form());
        $selected_ids = $form['selected_ids'];
        $type = $form['type'];

        if ($type == 'reject') {
            $title = I18N::T('eq_sample', '驳回审核');
            $view = V('reserv_approve:approve/batch_form', ['reject' => TRUE, 'selected_ids' => $selected_ids]);
        } else {
            $title = I18N::T('eq_sample', '通过审核');
            $view = V('reserv_approve:approve/batch_form', ['selected_ids' => $selected_ids]);
        }
        JS::dialog($view, ['title' => $title]);
    }

    function index_batch_approve_submit() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $selected_ids = $form['selected_ids'];
        try{
            if ($form['submit']) {
                if (!$form->no_error) {
                    throw new Error_Exception;
                }
                foreach ($selected_ids as $selected_id) {
                    $approve = Q("reserv_approve[reserv_id=$selected_id]:limit(1):sort(id D)")->current();
                    $description = $form['description'];
                    $approve->operate($me, FALSE, $description);
                }
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('reserv_approve', '审核通过，可在已批准中查看'));
                JS::refresh();
            }
        } catch (Exception $e) {
            JS::dialog(
                V('reserv_approve:approve/batch_form',
                    [
                        'form'=>$form,
                        'approve'=>$approve,
                    ]
                ),
                ['title'=>I18N::T('eq_sample', '通过审核')]
            );
        }
    }

    function index_batch_reject_submit() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $selected_ids = $form['selected_ids'];
        try{
            if ($form['submit']) {
                if (trim($form['description']) == '') {
                    $form->set_error('description', I18N::T('reserv_approve', '请填写驳回原因') );
                }
                if (!$form->no_error) {
                    throw new Error_Exception;
                }
                foreach ($selected_ids as $selected_id) {
                    $approve = Q("reserv_approve[reserv_id=$selected_id]:limit(1):sort(id D)")->current();
                    $description = $form['description'];
                    $approve->operate($me, TRUE, $description);
                }
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('reserv_approve', '审核驳回，可在已驳回中查看'));
                JS::refresh();
            }
        } catch (Exception $e) {
            JS::dialog(
                V('reserv_approve:approve/batch_form',
                    [
                        'form'=>$form,
                        'reject'=>TRUE,
                        'selected_ids'=>$selected_ids,
                    ]
                ),
                ['title'=>I18N::T('eq_sample', '驳回审核')]
            );
        }
    }
}
