<?php

class EQ_Evaluate {

    static function eq_evaluate_save($e, $record, $form) {
        if ($record->user->id == L('ME')->id || L('ME')->access('管理所有内容')) {
            if (Config::get('eq_evaluate')['score.require']) {
                if (!is_numeric($form['score']) || $form['score'] > 5 || $form['score'] < 1) {
                    $form->set_error('score', '评价填写有误!');
                }
            }
            if ($form['score'] && $form['score'] <= Config::get('eq_evaluate')['rate.baseline'] && !$form['content']) {
                $form->set_error('content', '请填写服务评价信息!');
            }
            if (mb_strlen($form['content']) > 240) {
                $form->set_error('content', '评价信息填写有误, 长度不得大于240!');
            }
            if ($form->no_error) {
                $evaluate = $record->evaluate->id ? $evaluate = O('eq_evaluate', $record->evaluate->id):O('eq_evaluate');
                $evaluate->equipment = $record->equipment;
                $evaluate->user = $record->user;
                $evaluate->score = $form['score'];
                $evaluate->content = $form['content'];
                $evaluate->ctime = time();

                if ($evaluate->save()) {
                    $record->evaluate = $evaluate;
                }
            }
        }
    }

    static function eq_record_before_save($e, $record, $new_data) {

        //双向评价和机主评价不兼容（app设计）
        if (Config::get('eq_evaluate')['score.require']) {
            if (!$record->evaluate->id && $record->status != EQ_Record_Model::FEEDBACK_NOTHING) {
                //@update20181101 【定制】RQ182701【哈尔滨工业大学】取消客户端对机主评价,此处直接返回即可。
                $access = Event::trigger("feedback.need_evaluate_by_source",$record);
                if(true === $access){
                    return;
                }
                $record->status = EQ_Record_Model::FEEDBACK_NOTHING;
            }
        }
    }

    static function eq_record_before_delete($e, $record) {
        $evaluate = $record->evaluate;
        $evaluate->delete();
    }

    static function setup() {
        //挪到快捷方式里面，这儿不要了
        //Event::bind('equipments.primary.tab', 'EQ_Evaluate::evaluate_tab', 0, 'evaluate');
        Event::bind('equipments.primary.content', 'EQ_Evaluate::evaluate_equipment_content_all', 0, 'evaluate');
    }

    static function setup_equipment() {
        Event::bind('equipment.index.tab', 'EQ_Evaluate::evaluate_equipment_tab');
        Event::bind('equipment.index.tab.content', 'EQ_Evaluate::evaluate_equipment_content', 0, 'evaluate');
        Event::bind('equipment.index.tab.tool_box', 'EQ_Evaluate::_tool_box_evaluate', 0, 'evaluate');
    }

    static function setup_extra($e, $controller, $method, $params) {
        if ('evaluate_group' != $params[0] && 'evaluate_incharge' != $params[0]) return;
        $me = L('ME');
        if (!$me->id || !$me->is_active()) URI::redirect('error/401');
        switch ($params[0]) {
            case 'evaluate_group':
                if (!($me->access('管理下属机构仪器的使用评价') || $me->access('管理所有仪器的使用评价'))) {
                    URI::redirect('error/401');
                }
                Event::bind('equipments.primary.tab', 'EQ_Evaluate::evaluate_group_tab');
                Event::bind('equipments.primary.content', 'EQ_Evaluate::evaluate_group_content', 100, 'evaluate_group');
                break;
            case 'evaluate_incharge':
                if (!(Q("{$me} equipment.incharge")->total_count() || $me->access('管理所有仪器的使用评价'))) {
                    URI::redirect('error/401');
                }
                Event::bind('equipments.primary.tab', 'EQ_Evaluate::evaluate_incharge_tab');
                Event::bind('equipments.primary.content', 'EQ_Evaluate::evaluate_incharge_content', 100, 'evaluate_incharge');
                break;
            default:
                break;
        }
    }

    static function evaluate_tab($e, $tabs, $module, $tab) {
        $me = L('ME');
        if ($module == "equipment" &&
            $tab == "evaluate" && $me->access('管理所有仪器的使用评价')) {
            $tabs->add_tab('evaluate', [
            'url' => URI::url('!equipments/extra/evaluate'),
            'title' => I18N::T('eq_evaluate', '所有仪器的使用评价'),
            'weight' => 90,
            ]);
        }
    }

    static function evaluate_incharge_tab($e, $tabs) {
        $me = L('ME');
        $tabs->add_tab('evaluate_incharge', [
            'url' => URI::url('!equipments/extra/evaluate_incharge'),
            'title' => I18N::T('eq_evaluate', '%user负责的所有仪器的使用评价', ['%user'=>$me->name]),
            'weight' => 95,
        ]);
    }

    static function evaluate_group_tab($e, $tabs) {
        $me = L('ME');
        $tabs->add_tab('evaluate_group', [
            'url' => URI::url('!equipments/extra/evaluate_group'),
            'title' => I18N::T('eq_evaluate', $me->group->name .'所有仪器的使用评价'),
            'weight' => 95,
        ]);
    }

    static function evaluate_equipment_tab($e, $tabs) {
        $equipment = $tabs->equipment;
        $me = L('ME');
        if ($me->is_allowed_to('查看评价', $equipment)) {
            $tabs->add_tab('evaluate', [
                'url' => $equipment->url('evaluate'),
                'title' => I18N::T('eq_evalute', '使用评价'),
                'weight' => 31,
            ]);
        }
    }

    static function evaluate_group_content($e, $tabs) {
        $me = L('ME');
        $group = $me->group;
        $eqs = Q("{$group} equipment");

        if (count($eqs)) {
            foreach ($eqs as $eq) {
                $eq_ids[] = $eq->id;
            }
        }
        else {
            $eq_ids[] = 0;
        }
        $id_selector = "equipment[id=$eq_ids[0]";
        for ($i = 1; $i < count($eq_ids); $i++) {
            $id_selector .= ','.$eq_ids[$i];
        }
        $id_selector .= '] ';
        $eq_selector[] = $id_selector;

        if (count($eq_selector)) {
            $pre_selectors[] = join(',', $eq_selector);
        }

        $tabs->content = self::evaluate_view($form, $equipment, $pre_selectors);
    }

    static function evaluate_incharge_content($e, $tabs) {
        $me = L('ME');
        $eqs = Q("{$me} equipment[allow_evaluate=1].incharge");

        if (count($eqs)) {
            foreach ($eqs as $eq) {
                $eq_ids[] = $eq->id;
            }
        }
        else {
            $eq_ids[] = 0;
        }

        $id_selector = "equipment[id=$eq_ids[0]";
        for ($i = 1; $i < count($eq_ids); $i++) {
            $id_selector .= ','.$eq_ids[$i];
        }
        $id_selector .= '] ';
        $eq_selector[] = $id_selector;

        if (count($eq_selector)) {
            $pre_selectors[] = join(',', $eq_selector);
        }

        $tabs->content = self::evaluate_view($form, $equipment, $pre_selectors);
    }

    static function evaluate_equipment_content($e, $tabs) {
        $equipment = $tabs->equipment;
        $tabs->content = self::eq_evaluate_view($form, $equipment, $tabs);
    }

    static function evaluate_equipment_content_all($e, $tabs) {
        $equipment = $tabs->equipment;
        $tabs->content = self::evaluate_view($form, $equipment);
    }

    private static function evaluate_view($form, $equipment, $pre_selectors = []) {
        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        }
        else {
            $form_token = Session::temp_token('eq_evaluate_',300);
            $form = Lab::form();
            $form['form_token'] = $form_token;
            $_SESSION[$form_token] = $form;
        }

        $selector = "eq_evaluate";
        $selector .= $equipment->id ? "[equipment=$equipment]" : '';

        $eq_selector = [];
        $group_root = Tag_Model::root('group');
        $tag_root = Tag_Model::root('equipment');

        if ($form['search']) {
            $pre_selectors = [];
        }

        if ($form['group']) {
            $group = O('tag_group', $form['group']);
            if ($group->id && $group->root->id == $group_root->id && $group->id != $group_root->id) {
                $eq_selector[] = "{$group} equipment";
            }
            else {
                unset($form['group']);
            }
        }

        $pre_selectors['equipment'] = 'equipment';
        if ($form['equipment_name']) {
            $equipment_name = Q::quote($form['equipment_name']);
            $pre_selectors['equipment'] .= "[name*=$equipment_name]";
        }
        if ($form['equipment_ref']) {
            $equipment_ref = Q::quote($form['equipment_ref']);
            $pre_selectors['equipment'] .= "[ref_no*=$equipment_ref]";
        }
        if ($form['user_name']) {
            $user_name = Q::quote(trim($form['user_name']));
            $pre_selectors['user'] = "user[name*=$user_name]";
        }
        if ($form['lab_name']) {
            $lab_name = Q::quote(trim($form['lab_name']));
            $pre_selectors['lab'] = "lab[name*=$lab_name] user";
        }
        if($form['dtstart']){
            $dtstart = Q::quote($form['dtstart']);
            $selector .= "[ctime>=$dtstart]";
        }
        if($form['dtend']){
            $dtend = Q::quote($form['dtend']);
            $dtend = Date::get_day_end($dtend);
            $selector .= "[ctime<=$dtend]";
        }
        if (isset($form['score']) && $form['score'] != -1) {
            $score = Q::quote($form['score'] + 1);
            $selector .= "[score=$score]";
        }
        if (count($eq_selector)) {
            $pre_selectors['equipment_p'] = join(',', $eq_selector);
        }

        if (!$pre_selectors['equipment'] && !$pre_selectors['equipment_p'] && $form['sort'] == 'equipment_name') {
            $pre_selectors['equipment'] = 'equipment';
        }
        if (!$pre_selectors['user'] && $form['sort'] == 'user_name') {
            $pre_selectors['user'] = 'user';
        }
        if (count($pre_selectors) > 0) {
            $selector = '('.implode(', ', (array)$pre_selectors).') ' . $selector;
        }

        $sort_by = $form['sort'];
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A':'D';
        if ($form['sort'] == 'equipment_name') {
            $selector .= ":sort(equipment.name_abbr {$sort_flag})";
        }
        elseif ($form['sort'] == 'user_name') {
            $selector .= ":sort(user.name_abbr {$sort_flag})";
        }
        elseif ($form['sort'] == 'score') {
            $selector .= ":sort(score {$sort_flag})";
        }
        else {
            $selector .= ':sort(id D)';
        }

        $evaluates = Q($selector);

        $_SESSION[$form_token] = [
            'selector' => $selector,
            'form' => $form,
        ];
        $total_count = $evaluates->total_count();
        if ($total_count == 0){
            $five_star_percent = 0;
            $four_star_percent = 0;
            $three_star_percent = 0;
            $two_star_percent = 0;
            $one_star_percent = 0;
        }else{
            $five_star_percent = round($evaluates->find('[score=5]')->total_count() * 100 / $total_count, 2);
            $four_star_percent = round($evaluates->find('[score=4]')->total_count() * 100 / $total_count, 2);
            $three_star_percent = round($evaluates->find('[score=3]')->total_count() * 100 / $total_count, 2);
            $two_star_percent = round($evaluates->find('[score=2]')->total_count() * 100 / $total_count, 2);
            $one_star_percent = round($evaluates->find('[score=1]')->total_count() * 100 / $total_count, 2);
        }

        $start = (int) $form['st'];
        $per_page = 20;
        $start = $start - ($start % $per_page);

        if ($start > 0) {
            $last = floor($total_count / $per_page) * $per_page;
            if ($last == $total_count) $last = max(0, $last - $per_page);
            if ($start > $last) {
                $start = $last;
            }
            $evaluates = $evaluates->limit($start, $per_page);
        }
        else {
            $evaluates = $evaluates->limit($per_page);
        }

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start' => $start,
            'per_page' => $per_page,
            'total' => $evaluates->total_count()
        ]);

        $panel_buttons = [];
        $panel_buttons[] = [
            'tip' => I18N::T('equipment', '导出CSV'),
            'text' => I18N::T('equipment', '导出'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_evaluate/index') . '"
                 q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) .'" class="button button_save "',
        ];
        $panel_buttons[] = [
            'tip'  => I18N::T('equipment', '打印'),
            'text' => I18N::T('equipment', '打印'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_evaluate/index') . '"
                 q-static="' . H(['form_token' => $form_token, 'type' => 'print']) .'" class="button button_print  middle"',
        ];

        return V('eq_evaluate:evaluates', [
            'evaluates' => $evaluates,
            'pagination' => $pagination,
            'panel_buttons' => $panel_buttons,
            'total_count' => $total_count,
            'form' => $form,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
            'five_star_percent' => $five_star_percent,
            'four_star_percent' => $four_star_percent,
            'three_star_percent' => $three_star_percent,
            'two_star_percent' => $two_star_percent,
            'one_star_percent' => $one_star_percent,
        ]);
    }

    private static function eq_evaluate_view($form, $equipment, $tabs, $pre_selectors = []) {

        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        }
        else {
            $form_token = Session::temp_token('eq_evaluate_',300);
            $form = Lab::form();
            $form['form_token'] = $form_token;
            $_SESSION[$form_token] = $form;
        }

        $selector = "eq_evaluate";
        $selector .= $equipment->id ? "[equipment=$equipment]" : '';

        $eq_selector = [];
        if ($form['user_name']) {
            $user_name = Q::quote(trim($form['user_name']));
            $pre_selectors['user'] = "user[name*=$user_name]";
        }
  
        if ($form['lab_name']) {
            $lab_name = Q::quote(trim($form['lab_name']));
            $pre_selectors['lab'] = "lab[name*=$lab_name] user";
        }
  
        if($form['dtstart']){
            $dtstart = Q::quote($form['dtstart']);
            $dtstart = Date::get_day_start($dtstart);
            $selector .= "[ctime>=$dtstart]";
        }
        if($form['dtend']){
            $dtend = Q::quote($form['dtend']);
            $dtend = Date::get_day_end($dtend);
            $selector .= "[ctime<=$dtend]";
        }
        if (isset($form['score']) && $form['score'] != -1) {
            $score = Q::quote($form['score'] + 1);
            $selector .= "[score=$score]";
        }
        if (count($eq_selector)) {
            $pre_selectors[] = join(',', $eq_selector);
        }

        if (!$pre_selector['user'] && $form['sort'] == 'user_name') {
            $pre_selector['user'] = 'user';
        }
        if (count($pre_selectors) > 0) {
            $selector = '('.implode(', ', (array)$pre_selectors).') ' . $selector;
        }

        $sort_by = $form['sort'];
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A':'D';
        if ($form['sort'] == 'user_name') {
            if (!$pre_selectors['user']) {
                $selector = "user " . $selector;
            }
            $selector .= ":sort(user.name_abbr {$sort_flag})";
        }
        elseif ($form['sort'] == 'score') {
            $selector .= ":sort(score {$sort_flag})";
        }
        elseif ($form['sort'] == 'status') {
            $selector .= ":sort(status {$sort_flag})";
        }
        else {
            $selector .= ':sort(id D)';
        }

        $evaluates = Q($selector);

        $_SESSION[$form_token] = [
            'selector' => $selector,
            'form' => $form,
        ];
        $total_count = $evaluates->total_count();
        if ($total_count == 0){
            $five_star_percent = 0;
            $four_star_percent = 0;
            $three_star_percent = 0;
            $two_star_percent = 0;
            $one_star_percent = 0;
        }else{
            $five_star_percent = round($evaluates->find('[score=5]')->total_count() * 100 / $total_count, 2);
            $four_star_percent = round($evaluates->find('[score=4]')->total_count() * 100 / $total_count, 2);
            $three_star_percent = round($evaluates->find('[score=3]')->total_count() * 100 / $total_count, 2);
            $two_star_percent = round($evaluates->find('[score=2]')->total_count() * 100 / $total_count, 2);
            $one_star_percent = round($evaluates->find('[score=1]')->total_count() * 100 / $total_count, 2);
        }
        $start = (int) $form['st'];
        $per_page = 20;
        $start = $start - ($start % $per_page);

        if ($start > 0) {
            $last = floor($total_count / $per_page) * $per_page;
            if ($last == $total_count) $last = max(0, $last - $per_page);
            if ($start > $last) {
                $start = $last;
            }
            $evaluates = $evaluates->limit($start, $per_page);
        }
        else {
            $evaluates = $evaluates->limit($per_page);
        }

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start' => $start,
            'per_page' => $per_page,
            'total' => $evaluates->total_count()
        ]);

        $panel_buttons = [];
        $panel_buttons[] = [
            'tip' => I18N::T('equipment', '导出CSV'),
            'text' => I18N::T('equipment', '导出'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_evaluate/index') . '"
                 q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) .'" class="button button_save "',
        ];
        $panel_buttons[] = [
            'tip' => I18N::T('equipment', '打印'),
            'text' => I18N::T('equipment', '打印'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!eq_evaluate/index') . '"
                 q-static="' . H(['form_token' => $form_token, 'type' => 'print']) .'" class="button button_print  middle"',
        ];

        $tabs->columns = self::get_evaluate_columns($form);
        $tabs->panel_buttons = $panel_buttons;

        $tabs->total_count = $total_count;

        return V('eq_evaluate:eq_evaluates', [
            'evaluates' => $evaluates,
            'pagination' => $pagination,
            'total_count' => $total_count,
            'columns'    => $tabs->columns,
            'form' => $form,
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
            'five_star_percent' => $five_star_percent,
            'four_star_percent' => $four_star_percent,
            'three_star_percent' => $three_star_percent,
            'two_star_percent' => $two_star_percent,
            'one_star_percent' => $one_star_percent,
        ]);
    }

    public static function get_evaluate_columns($form)
    {
        $me = L('me');

        if ($form['dtstart'] || $form['dtend']) {
            $form['date'] = true;
        }

        $columns = [
            'serial_number'=>[
                'title'=>I18N::T('equipments', '编号'),
                'align'=>'left',
                'nowrap'=>TRUE,
            ],
            'equipment_name'=>[
                'title'=>I18N::T('equipments', '仪器'),
                'align'=>'left',
                'nowrap'=>TRUE,
            ],
            'user_name'=>[
                'title'=>I18N::T('equipments', '使用者'),
                'filter'=> [
                    'form' => V('equipments:records_table/filters/user_name', ['user_name'=>$form['user_name']]),
                    'value' => $form['user_name'] ? H($form['user_name']) : NULL
                ],
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
            ],
            'lab_name'=>[
                'title'=>I18N::T('equipments', '实验室'),
                'invisible'=>TRUE,
                'filter'=> [
                    'form' => V('equipments:records_table/filters/lab_name', ['lab_name'=>$form['lab_name']]),
                    'value' => $form['lab_name'] ? H($form['lab_name']) : NULL
                ],
                'nowrap'=>TRUE,
            ],
            'date'=>[
                'title'=>I18N::T('equipments', '时间'),
                'filter'=> [
                    'form' => V('equipments:records_table/filters/date', [
                        'dtstart'=>$form['dtstart'],
                        'dtend'=>$form['dtend']
                    ]),
                    'value' => $form['date'] ? H($form['date']) : NULL,
                    'field'=>'dtstart,dtend',
                ],
                'invisible'=>TRUE,
                'nowrap'=>TRUE,
            ],
            'duty_teacher' => [
                'title' => '值班老师',
                'nowrap' => TRUE,
            ],
            'score'=>[
                'title'=>I18N::T('eq_evaluate', '服务态度'),
                'filter'=> [
                    'form' => V('eq_evaluate:evaluates_table/filters/score', [
                        'score'=>$form['score'],
                    ]),
                    'value' => (isset($form['score']) && $form['score'] != -1) ? Config::get('eq_evaluate')['rate.tip'][$form['score']] : '',
                    'field'=>'score',
                ],
                'align'=>'center',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
            ],
            'content'=>[
                'title' => I18N::T('equipments', '服务评价'),
                'align' => 'left',
                'nowrap' => FALSE,
            ],
        ];

        return $columns;
    }

    public static function _tool_box_evaluate($e, $tabs)
    {
        $tabs->search_box = V('application:search_box', [
            'panel_buttons' => $tabs->panel_buttons,
            'top_input_arr' => ['user_name'],
            'columns' => $tabs->columns,
            'extra_view'    => '<div class="adj statistics middle">'.I18N::T('equipments', '共有 %total_count 条评价记录',
                    ['%total_count' => '<span class="eq_number">'.intval($tabs->total_count).'</span>']).
                '</div>'
        ]);
        unset($tabs->panel_buttons);
    }

    static function equipment_evaluate_ACL($e, $me, $perm_name, $object, $options) {
        $id = $object->id;
        $group = $me->group;
        switch ($perm_name) {
            case '查看评价':
                if ( $me->access('管理所有仪器的使用评价') ) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                elseif ($me->access('管理下属机构仪器的使用评价') && Q("{$group} {$object}")->current()->id) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                elseif ($object->allow_evaluate && Q("{$me} equipment[id={$id}].incharge")->current()->id){
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '设置评价':
                if ( $me->access('管理所有仪器的使用评价') ) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                elseif ($me->access('管理下属机构仪器的使用评价') && Q("{$group} equipment[id={$id}]")->current()->id) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
            default:
                break;
        }
    }

    static function equipments_edit_use_extra_view($e, $equipment) {
        $me = L('ME');
        if ($me->is_allowed_to('设置评价', $equipment)) {
            $e->return_value .= V('eq_evaluate:equipment/edit.extra.use', ['equipment'=>$equipment]);
        }
    }

    static function eq_allow_evaluate_save($e, $equipment, $form) {
        $equipment->allow_evaluate = $form['allow_evaluate'] == 'on' ? 1 : 0;
    }

    static function glogon_offline_logout_record_saved($e, $record, $struct) {

        //@update20181101 【定制】RQ182701【哈尔滨工业大学】取消客户端对机主评价,此处直接返回即可。
        $access = Event::trigger("feedback.need_evaluate_by_source",$record,'glogon');
        if(true === $access){
            return;
        }
        
        $r = $struct->record;
        if ($r['extra'] && $record->id) {
            $form = @json_decode($r['extra'], TRUE);

            $evaluate = O('eq_evaluate');
            $evaluate->equipment = $record->equipment;
            $evaluate->user = $record->user;
            $evaluate->score = $form['score'];
            $evaluate->content = $form['content'];
            $evaluate->ctime = time();

            if ($evaluate->save()) {
                $record->status = $form['status'];
                $record->evaluate = $evaluate;
                $record->save();
            }
        }
    }

    static function veronica_logout_extra($e, $data, $record) {
        //@update20181101 【定制】RQ182701【哈尔滨工业大学】取消客户端对机主评价,此处直接返回即可。
        $access = Event::trigger("feedback.need_evaluate_by_source", $record, 'glogon');
        if(true === $access){
            return;
        }
        
        if ($data && $record->id) {
            $evaluate = O('eq_evaluate');
            $evaluate->equipment = $record->equipment;
            $evaluate->user = $record->user;
            $evaluate->score = $data['score'];
            $evaluate->content = $data['content'];
            $evaluate->ctime = time();
            
            if ($evaluate->save()) {
                $record->status = $data['status'];
                $record->evaluate = $evaluate;
                $record->save();
            }
        }
    }

    static function glogon_switch_to_logout_record_saved($e, $record, $data) {

        //@update20181101 【定制】RQ182701【哈尔滨工业大学】取消客户端对机主评价,此处直接返回即可。
        $access = Event::trigger("feedback.need_evaluate_by_source",$record,'glogon');
        if(true === $access){
            return;
        }

        if ($data['extra'] && $record->id) {
            $form = $data['extra'];

            $evaluate = O('eq_evaluate');
            $evaluate->equipment = $record->equipment;
            $evaluate->user = $record->user;
            $evaluate->score = $form['score'];
            $evaluate->content = $form['content'];
            $evaluate->ctime = time();

            if ($evaluate->save()) {
                $record->status = $form['status'];
                $record->evaluate = $evaluate;
                $record->save();
            }
        }
    }

    static function try_create_record_before_save($e, $reserv, $record) {
        $evaluate = O('eq_evaluate');
        $evaluate->equipment = $record->equipment;
        $evaluate->user = $record->user;
        $evaluate->score = 5;
        $evaluate->content = I18N::T('eq_record', '系统自动生成评价!');
        $evaluate->ctime = time();
        if ($evaluate->save()) {
            $record->evaluate = $evaluate;
        }
    }
}
