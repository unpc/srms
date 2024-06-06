<?php
class Announce_Approval{
    public static function announce_primary_tab($e, $tabs)
    {
        if (L('ME')->is_allowed_to('列表审批', 'announce')) {
            Event::bind('announces.primary.content', 'Announce_Approval::announce_primary_content',
                0, 'approval');

            $tabs
                ->add_tab('approval', [
                    'url' => URI::url('!announces/extra/approval'),
                    'title' => I18N::T('announces', '公告审批'),
                    'weight' => 10
                ]);
        }
    }

    public static function announce_primary_content($e, $tabs)
    {
       $me = L('ME');
       $form = Lab::form(function(&$old_form, &$form) {});

       $params = Config::get('system.controller_params');
       $tab = $params[1]?:"approval";

       $secondary_tabs = Widget::factory('tabs');
       $secondary_tabs
            ->add_tab('approval', [
                'url'=>URI::url('!announces/extra/approval.approval'),
                'title'=>I18N::T('people', '待审批'),
                'weight' => 1,
            ])
            ->add_tab('pass', [
                'url' => URI::url('!announces/extra/approval.pass'),
                'title' => I18N::T('people', '已通过'),
                'weight' => 2,
            ])
            ->add_tab('rebut', [
                'url'=>URI::url('!announces/extra/approval.rebut'),
                'title'=>I18N::T('people', '已驳回'),
                'weight' => 3,
            ])
            ->set('class', 'secondary_tabs');;

        $secondary_tabs->select($tab);

        $selector = 'announce[need_approval]';

        if (!$me->is_allowed_to('审批', 'announce')){
            $selector .= "[sender=$me]";
        }

        switch ($tab) {
            case 'pass':
                $selector .= "[flag=1]";
                break;
            case 'rebut':
                $selector .= "[flag=2]";
                break;
            case 'approval':
            default:
                $selector .= "[!flag]";
                break;

        }
        if ($form['query']) {
            $title = Q::quote($form['query']);
            $selector .= "[title *= {$title}]";
        }

        $selector .= ':sort(ctime D)';

        $announces = Q($selector);

        $start = (int) $form['st'];
        $per_page = 15;
        $start = $start - ($start % $per_page);

        $pagination = Lab::pagination($announces, $start, $per_page);


        $tabs->set('content', V('announces_approval:content', [
            'announces'=> $announces,
            'form'=> $form,
            'pagination'=> $pagination,
            'secondary_tabs'=>$secondary_tabs,
        ]));
    }

    static function links($e, $announce, $links, $mode) {
        if ($announce->id && $mode == "approval") {
            $me = L('ME');
            if ($me->is_allowed_to('审批', $announce)) {
                $links['pass'] = [
                    'tip' => I18N::T('people', '通过'),
                    'text' => I18N::T('people', '通过'),
                    'extra' => 'class="blue" q-event="click" q-object="approval_announce"' .
                        ' q-static="' . H(['a_id' => $announce->id, 'approval' => 'pass']) .
                        '" q-src="' . URI::url("!announces_approval/index") . '"',
                ];
                $links['rebut'] = [
                    'tip' => I18N::T('people', '驳回'),
                    'text' => I18N::T('people', '驳回'),
                    'extra' => 'class="blue" q-event="click" q-object="approval_announce"' .
                        ' q-static="' . H(['a_id' => $announce->id, 'approval' => 'rebut']) .
                        '" q-src="' . URI::url("!announces_approval/index") . '"',
                ];
            }
        }
    }


    static function extra_selector($e, $form, $selector) {
        $selector .= "[flag=1]";
        $e->return_value = $selector;
        return FALSE;
    }

    static function on_announce_saved($e, $announce, $old_data, $new_data) {
        $me = L('ME');
        $need_approval = Event::trigger('announces.need.approval', $me);
        if (!$old_data['id'] && $need_approval) {
            $form = Form::filter(Input::form());
            $announce->type = $form['receivers_type'];
            $announce->flag = Announce_Approval_Model::STATUS_APPROVAL;
            $announce->need_approval = 1;
        }
    }


}
