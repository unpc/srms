<?php

class Index_AJAX_Controller extends AJAX_Controller
{
    public function index_view_click()
    {
        $form = Form::filter(Input::form());
        $approval = O('approval', $form['approval_id']);
        $approved = Q("approved[source=$approval]:sort(id)");
        $source = $approval->source;

        switch ($source->name()) {
            case 'eq_reserv':
                $dialog_params = [
                    'title'=> I18N::T('approval', '预约审核详情')
                ];
                if (!$source->id) {
                    $source = new stdClass();
                    $source->equipment = $approval->equipment;
                    $source->user = $approval->user;
                    $source->project = $approval->project;
                    $source->dtstart = $approval->dtstart;
                    $source->dtend = $approval->dtend;
                }
                $info = V('approval_flow:approval/reserv', ['reserv' => $source, 'approval' => $approval]);
                break;
            case 'eq_sample':
                $dialog_params = [
                    'title'=> I18N::T('approval', '送样审核详情')
                ];
                if (!$source->id) {
                    $source = new stdClass();
                    $source->dtsubmit = $approval->dtsubmit;
                    $source->count = $approval->count;
                    $source->note = $approval->note;
                    $source->description = $approval->description;
                }
                $info = V('approval_flow:approval/sample', ['sample' => $source, 'approval' => $approval]);
                break;
        }

        JS::dialog((string) V('approval_flow:approval/dialog', [
            'info' => $info,
            'approval' => $approval,
            'approved' => $approved,
            'type' => $form['type'],
        ]), $dialog_params);
    }

    public function index_pass_click()
    {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $approval = O('approval', $form['approval_id']);

        try {
            if (!$approval->id || !$me->can_approval($approval->flag, $approval)) {
                throw new Exception(I18N::T('approval', '审核失败，请刷新重试'));
            }

            JS::dialog(
                V(
                    'approval_flow:approval/form',
                    [
                        'approval_ids' => [$approval->id],
                        'action' => 'pass'
                    ]
                ),
                ['title' => I18N::T('approval', '通过审核')]
                );
        } catch (Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, $e->getMessage());
            JS::refresh();
        }
    }

    public function index_pass_submit()
    {
        $me = L('ME');
        $form = Form::filter(Input::form());
        if ($form['submit']) {
            foreach($form['approval_ids'] as $id) {
                $approval = O('approval', $id);
                if (!$approval->id || !$me->can_approval($approval->flag, $approval)) {
                    continue;
                }

                if (!$form->no_error) {
                    JS::dialog(
                        V(
                            'approval_flow:approval/form',
                            [
                                'form' => $form,
                                'approval_ids' => $form['approval_ids'],
                                'action' => 'pass'
                            ]
                        ),
                        ['title' => I18N::T('approval', '通过审核')]
                    );
                    return;
                }

                $approval->description = $form['description'] ? : '';
                $approval->pass();
            }
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('approval', '审核通过'));
            JS::refresh();
        }
    }

    public function index_reject_click()
    {
        $me = L('ME');
        $form = Form::filter(Input::form());
        $approval = O('approval', $form['approval_id']);

        try {
            if (!$approval->id || !$me->can_approval($approval->flag, $approval)) {
                throw new Exception(I18N::T('approval', '审核失败，请刷新重试'));
            }

            JS::dialog(
                V(
                    'approval_flow:approval/form',
                    [
                        'approval_ids' => [$approval->id],
                        'action' => 'reject'
                    ]
                ),
                ['title' => I18N::T('approval', '驳回审核')]
                );
        } catch (Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, $e->getMessage());
            JS::refresh();
        }
    }

    public function index_reject_submit()
    {
        $me = L('ME');
        $form = Form::filter(Input::form());

        if ($form['submit']) {
            if (trim($form['description']) == '') {
                $form->set_error('description', I18N::T('approval_flow', '请填写驳回原因'));
            }
            foreach($form['approval_ids'] as $id) {
                $approval = O('approval', $id);
                if (!$approval->id || !$me->can_approval($approval->flag, $approval)) {
                    continue;
                }

                if (!$form->no_error) {
                    JS::dialog(
                        V(
                            'approval_flow:approval/form',
                            [
                                'form' => $form,
                                'approval_ids' => $form['approval_ids'],
                                'action' => 'reject'
                            ]
                        ),
                        ['title' => I18N::T('approval', '通过审核')]
                    );
                    return;
                }

                $approval->description = $form['description'] ? : '';
                $approval->reject();
            }
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('reserv_approve', '审核驳回，可在已驳回中查看'));
            JS::refresh();
        }
    }

    function index_batch_action_submit() {
        $me = L('ME');
        $form = Form::filter(Input::form());

        // if (!$me->can_approval($form['flag'])) {
        //     JS::refresh();
        //     return;
        // }
        $ids = [];
        foreach($form['select'] as $id => $v) {
            if ($v == 'on') {
                $ids[] = $id;
            }
        }
        if (!count($ids)) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('reserv_approve', '请至少选择一项审批'));
            JS::refresh();
            return;
        }

        JS::dialog(
            V(
                'approval_flow:approval/form',
                [
                    'approval_ids' => $ids,
                    'action' => $form['submit']
                ]
            ),
            ['title' => I18N::T('approval', $form['submit'] == 'pass' ? '通过审核' : '驳回审核')]
            );
    }
}
