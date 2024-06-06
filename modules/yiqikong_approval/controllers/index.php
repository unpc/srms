<?php

class Index_AJAX_Controller extends AJAX_Controller {

    public function index_view_click() {
        $form = Form::filter(Input::form());
        $approval = O('approval', $form['approval_id']);
        $reserv = O('eq_reserv', $approval->source->id);
        if (!$reserv->id) {
            $reserv = new stdClass();
            $reserv->equipment = $approval->equipment;
            $reserv->user = $approval->user;
            $reserv->project = $approval->user;
            $reserv->dtstart = $approval->dtstart;
            $reserv->dtend = $approval->dtend;
        }

        $approved = Q("approved[source=$approval]:sort(ctime D)");

        $dialog_params = [
            'title'=> I18N::T('yiqikong_approval', '预约审核进度')
        ];

        JS::dialog((string) V('yiqikong_approval:approval/dialog', [
            'reserv' => $reserv,
            'approval' => $approval,
            'approved' => $approved,
            'type' => $form['type'],
        ]), $dialog_params);
    }

    public function index_pass_click() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $approval = O('approval', $form['approval_id']);

        try {
            if (!($approval->id) || !$me->is_allowed_to('机主审核', 'approval')) {
                throw new Exception(I18N::T('yiqikong_approval', '审核失败，请刷新重试'));
            }

            JS::dialog(
                V('yiqikong_approval:approval/form',
                    [
                        'approval' => $approval,
                        'action' => 'pass'
                    ]),
                    ['title' => I18N::T('yiqikong_approvals', '通过审核')]
                );
        } catch (Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, $e->getMessage());
            JS::refresh();
        }
    }

    public function index_pass_submit() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $approval = O('approval', $form['approval_id']);

        try{
            if ($form['submit']) {
                if (!($approval->id) || !$me->is_allowed_to('机主审核', 'approval')) {
                    $form->set_error('description', I18N::T('yiqikong_approval', '审核失败，请刷新重试') );
                }
                if (!$form->no_error) {
                    throw new Error_Exception;
                }
                $approval->description = $form['description'];
                $approval->pass();
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('yiqikong_approval', '审核通过，可在已通过中查看'));
                JS::refresh();
            }
        } catch (Exception $e) {
            JS::dialog(
                V('yiqikong_approval:approval/form',
                    [
                        'form' => $form,
                        'approval' => $approval,
                        'action' => 'pass'
                    ]
                ),
                ['title' => I18N::T('approval', '通过审核')]
            );
        }
    }

    public function index_new_pass_click() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $approval = O('approval', $form['approval_id']);

        if (JS::confirm(I18N::T('yiqikong_approval', '确认审核通过吗？'))) {
            try{
                if (!($approval->id) || !$me->is_allowed_to('机主审核', $approval)) {
                    $form->set_error('description', I18N::T('yiqikong_approval', '审核失败，请刷新重试') );
                }
                if (!$form->no_error) {
                    throw new Error_Exception;
                }
                $approval->description = $form['description'];
                $approval->pass();
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('yiqikong_approval', '审核通过，可在已通过中查看'));
                JS::refresh();
            } catch (Exception $e) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('yiqikong_approval', '审核失败，请刷新重试'));
                JS::refresh();
            }
        }
    }

    public function index_reject_click() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $approval = O('approval', $form['approval_id']);

        try {
            if (!($approval->id) || !$me->is_allowed_to('机主审核', 'approval')) {
                throw new Exception(I18N::T('yiqikong_approval', '审核失败，请刷新重试'));
            }

            JS::dialog(
                V('yiqikong_approval:approval/form',
                    [
                        'approval' => $approval,
                        'action' => 'reject'
                    ]),
                    ['title' => I18N::T('yiqikong_approval', '驳回审核')]
                );
        } catch (Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, $e->getMessage());
            JS::refresh();
        }
    }

    public function index_reject_submit() {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $approval = O('approval', $form['approval_id']);
        try{
            if ($form['submit']) {
                if (!($approval->id) || !$me->is_allowed_to('机主审核', 'approval')) {
                    $form->set_error('description', I18N::T('yiqikong_approval', '审核失败，请刷新重试') );
                } elseif (trim($form['description']) == '') {
                    $form->set_error('description', I18N::T('yiqikong_approval', '请填写驳回原因') );
                }
                if (!$form->no_error) {
                    throw new Error_Exception;
                }

                $approval->description = $form['description'];
                $approval->reject();
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('yiqikong_approval', '审核驳回，可在已驳回中查看'));
                JS::refresh();
            }
        } catch (Exception $e) {
            JS::dialog(
                V('yiqikong_approval:approval/form',
                    [
                        'form' => $form,
                        'approval' => $approval,
                        'action' => 'reject'
                    ]
                ),
                ['title' => I18N::T('approval', '驳回审核')]
            );
        }
    }

    public function index_batch_approval_submit() {
        $form = Input::form();
        $me = L('ME');
        $submit = $form['submit'];

        switch ($submit) {
            case "pass":
                $message = '您确定要批量通过吗?';
                break;
            case "reject":
            default:
                $message = '您确定要批量驳回吗?';
        }

        if (JS::confirm(I18N::T('yiqikong_approval', $message))) {
            foreach($form['select'] as $key => $value) {
                if ($value == 'on') {
                    $approval = O('approval', $key);
                    if (!$approval->id || !$me->is_allowed_to('机主审核', $approval)) continue;
                    
                    if ($submit == 'pass') {
                        $approval->pass();
                    }

                    if ($submit == 'reject') {
                        $approval->reject();
                    }
                    
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('yiqikong_approval', '批量操作完成'));
                    JS::refresh();
                }
            }

            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '批量操作完成'));
            JS::refresh();
        }
    }
}