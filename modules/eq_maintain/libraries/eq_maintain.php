<?php

class EQ_Maintain {

    static function setup_equipment() {
        Event::bind('equipment.index.tab', 'EQ_Maintain::maintain_equipment_tab', 0, 'maintain');
        Event::bind('equipment.index.tab.content', 'EQ_Maintain::maintain_equipment_content', 0, 'maintain');
        Event::bind('equipment.index.tab', 'EQ_Maintain::keep_equipment_tab', 0, 'keep');
        Event::bind('equipment.index.tab.content', 'EQ_Maintain::keep_equipment_content', 0, 'keep');
    }

    static function maintain_equipment_tab($e, $tabs) {
        $equipment = $tabs->equipment;
        $me = L('ME');
        if ($me->is_allowed_to('查看维修记录', $equipment)) {
            $tabs->add_tab('maintain', [
                'url' => $equipment->url('maintain'),
                'title' => I18N::T('eq_maintain', '维修信息'),
                'weight' => 51,
            ]);
        }
    }

    static function keep_equipment_tab ($e, $tabs) {
        $equipment = $tabs->equipment;
        $me = L('ME');
        if ($me->is_allowed_to('查看维修记录', $equipment)) {
            $tabs->add_tab('keep', [
                'url' => $equipment->url('keep'),
                'title' => I18N::T('eq_maintain', '保养信息'),
                'weight' => 52,
            ]);
        }
    }

    static function maintain_equipment_content($e, $tabs) {
        $equipment = $tabs->equipment;
        $tabs->content = self::maintain_view($form, $equipment);
    }

    static function keep_equipment_content($e, $tabs) {
        $equipment = $tabs->equipment;
        $tabs->content = self::keep_view ($form, $equipment);
    }

    private static function maintain_view($form, $equipment) {
        $me = L('ME');

        if (!$me->is_allowed_to('查看维修记录', $equipment)) {
            URI::redirect('error/401');
        }

        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        }
        else {
            $form_token = Session::temp_token('eq_maintain_',300);
            $form = Lab::form();
            $form['form_token'] = $form_token;
            $_SESSION[$form_token] = $form;
        }

        $selector = "eq_maintain";
        $selector .= $equipment->id ? "[equipment=$equipment]" : '';

        if($form['dtstart_check']){
            $dtstart = Q::quote($form['dtstart']);
            $dtstart = Date::get_day_start($dtstart);
            $selector .= "[time>=$dtstart]";
        }
        if($form['dtend_check']){
            $dtend = Q::quote($form['dtend']);
            $dtend = Date::get_day_end($dtend);
            $selector .= "[time<=$dtend]";
        }

        $new_selector = Event::trigger('search.condition.selector', $form, $selector);

        if ($new_selector) {
            $selector = $new_selector;
        }

        $selector .= ':sort(time DESC)';
        $maintains = Q($selector);
        $_SESSION[$form_token] = [
            'selector' => $selector,
            'form' => $form,
        ];
        $total_count = $maintains->total_count();
        $start = (int) $form['st'];
        $per_page = 20;
        $start = $start - ($start % $per_page);

        if ($start > 0) {
            $last = floor($total_count / $per_page) * $per_page;
            if ($last == $total_count) $last = max(0, $last - $per_page);
            if ($start > $last) {
                $start = $last;
            }
            $maintains = $maintains->limit($start, $per_page);
        }
        else {
            $maintains = $maintains->limit($per_page);
        }

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start' => $start,
            'per_page' => $per_page,
            'total' => $total_count
        ]);

        $panel_buttons = [];

        if ($me->is_allowed_to('修改维修记录', $equipment)) {
            $panel_buttons[] = [
                'text' => I18N::T('eq_maintain', '添加记录'),
                'extra' => 'q-object="add" q-event="click" q-src="' . URI::url('!eq_maintain/index') . '"
                     q-static="' . H(['id' => $equipment->id]) .'" class="button button_add"',
            ];
        }
        $panel_buttons[] = [
            'text' => I18N::T('eq_maintain', '导出CSV'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_maintain/index') . '"
                 q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) .'" class="button button_save "',
        ];
        $panel_buttons[] = [
            'text' => I18N::T('eq_maintain', '打印'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_maintain/index') . '"
                 q-static="' . H(['form_token' => $form_token, 'type' => 'print']) .'" class="button button_print  middle"',
        ];

        return V('eq_maintain:maintains', [
            'maintains' => $maintains,
            'pagination' => $pagination,
            'panel_buttons' => $panel_buttons,
            'total_count' => $total_count,
            'form' => $form
        ]);
    }

    private static function keep_view ($form, $equipment) {
        $me = L('ME');

        if (!$me->is_allowed_to('查看维修记录', $equipment)) {
            URI::redirect('error/401');
        }

        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        }
        else {
            $form_token = Session::temp_token('eq_keep_',300);
            $form = Lab::form();
            $form['form_token'] = $form_token;
            $_SESSION[$form_token] = $form;
        }

        $selector = "eq_keep";
        $selector .= $equipment->id ? "[equipment=$equipment]" : '';

        if ($form['dtstart_check']) {
            $dtstart = Q::quote($form['dtstart']);
            $dtstart = Date::get_day_start($dtstart);
            $selector .= "[time>=$dtstart]";
        }
        if ($form['dtend_check']) {
            $dtend = Q::quote($form['dtend']);
            $dtend = Date::get_day_end($dtend);
            $selector .= "[time<=$dtend]";
        }

        $selector .= ':sort(time DESC)';
        $keeps = Q($selector);
        $_SESSION[$form_token] = [
            'selector' => $selector,
            'form' => $form,
        ];
        $total_count = $keeps->total_count();
        $start = (int) $form['st'];
        $per_page = 20;
        $start = $start - ($start % $per_page);

        if ($start > 0) {
            $last = floor($total_count / $per_page) * $per_page;
            if ($last == $total_count) $last = max(0, $last - $per_page);
            if ($start > $last) {
                $start = $last;
            }
            $keeps = $keeps->limit($start, $per_page);
        }
        else {
            $keeps = $keeps->limit($per_page);
        }

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start' => $start,
            'per_page' => $per_page,
            'total' => $keeps->total_count()
        ]);

        $panel_buttons = [];

        if ($me->is_allowed_to('修改维修记录', $equipment)) {
            $panel_buttons[] = [
                'text' => I18N::T('eq_maintain', '添加记录'),
                'extra' => 'q-object="add" q-event="click" q-src="' . URI::url('!eq_maintain/keep') . '"
                     q-static="' . H(['id' => $equipment->id]) .'" class="button button_add"',
            ];
        }
        $panel_buttons[] = [
            'text' => I18N::T('eq_maintain', '导出CSV'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_maintain/keep') . '"
                 q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) .'" class="button button_save "',
        ];
        $panel_buttons[] = [
            'text' => I18N::T('eq_maintain', '打印'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_maintain/keep') . '"
                 q-static="' . H(['form_token' => $form_token, 'type' => 'print']) .'" class="button button_print  middle"',
        ];

        return V('eq_maintain:keeps', [
            'keeps' => $keeps,
            'pagination' => $pagination,
            'panel_buttons' => $panel_buttons,
            'total_count' => $total_count,
            'form' => $form
        ]);
    }
}
