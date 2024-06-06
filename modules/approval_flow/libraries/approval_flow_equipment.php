<?php
class Approval_Flow_Equipment
{
    public static function setup_approval_tab()
    {
        Event::bind('equipment.index.tab', 'Approval_Flow_Equipment::eq_approval_tab', '1');
    }

    public static function eq_approval_tab($e, $tabs)
    {
        $me = L('ME');
        $equipment = $tabs->equipment;

        if (!$me->is_allowed_to('查看审核', $equipment)) {
            return true;
        }
        if ($equipment->need_approval) {
            Event::bind('equipment.index.tab.content', 'Approval_Flow_Equipment::eq_approval_content', 0, 'reserv_approval');
            $tabs->add_tab('reserv_approval', [
                'url'=>$equipment->url('reserv_approval'),
                'title'=>I18N::T('billing', '预约审批'),
                'weight' => 110
            ]);
        }
    }

    public static function eq_approval_content($e, $tabs)
    {
        $me = L('ME');
        $equipment = $tabs->equipment;

        $flow = Config::get('flow.eq_reserv');
        foreach ($flow as $key => $v) {
            if ($me->can_approval($key, '')) {
                break;
            }
        }
        // type: reserv_approval , sample_approval
        $type = $tabs->selected;
        // status: approve_pi, done, reject ...
        $params = Config::get('system.controller_params');
        $status = $params[2] ? : $key;
        Event::bind('equipment.approval.view.tabs', 'Approval_Flow_Equipment::_equipment_approval_tabs', 0, $status);
        Event::bind('equipment.approval.view.content', 'Approval_Flow_Equipment::_equipment_approval_content', 0, $status);

        $tabs->content = V('approval_flow:profile/content');
        $tabs->content->secondary_tabs = Widget::factory('tabs')
            ->set('class', 'secondary_tabs')
            ->set('equipment', $equipment)
            ->set('type', $type)
            ->tab_event('equipment.approval.view.tabs')
            ->content_event('equipment.approval.view.content')
            ->select($status);
    }

    public static function _equipment_approval_tabs($e, $tabs)
    {
        $me  = L('ME');
        $equipment = $tabs->equipment;
        $flow = Config::get('flow.eq_reserv');
        foreach ($flow as $step => $operation) {
            $criteria = [
                'equipment' => $equipment,
                'flag' => $step,
                'source_name' => 'eq_reserv'
            ];
            $selector = Approval_Help::make_tab_selector($criteria);
            $tabs->add_tab($step, [
                'url'=>$equipment->url("{$tabs->type}.{$step}"),
                'title'=> I18N::T('approval', $operation['title'].' (%count)', [
                    '%count'=> Q($selector)->total_count(),
                ]),
                'weight' => 0,
            ])
            ->set('class', 'secondary_tabs');
        }
    }

    public static function _equipment_approval_content($e, $tabs)
    {
        $me  = L('ME');
        $equipment = $tabs->equipment;
        $flows = Config::get('flow.eq_reserv');
        foreach ($flows as $step => $flow) {
            if ($me->can_approval($step, '')) {
                $key = $step;
                break;
            }
        }

        $flag = $tabs->selected ? : $key;

        $form = Lab::form(Approval_Help::date_filter_handler());
        $criteria = [
            'equipment' => $equipment,
            'flag' => $flag,
            'source_name' => 'eq_reserv'
        ];
        $form = array_merge($form, $criteria);
        $selector = Approval_Help::make_tab_selector($form);
        $approval = Q($selector);

        $pagination = Lab::pagination($approval, (int)$form['st'], 20);

        $tabs->content = V("approval_flow:approval/list_eq_reserv_equipment", [
            'flag' => $flag,
            'approval' => $approval,
            'pagination' => $pagination,
            'form' => $form,
            'sort_by' => $sort_by ? : 'date',
            'sort_asc' => $form['sort_asc'],
        ]);
    }
}
