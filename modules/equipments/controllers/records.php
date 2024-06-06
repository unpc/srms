<?php

class Records_Controller extends Base_Controller
{
    public function entries($tab)
    {
        $me = L("ME");
        if(!$me->id){
            URI::redirect('error/401');
        }
        
        $tabs = Widget::factory("tabs");
        $this->layout->title = '使用记录';
        $allow_tabs = [];

        if ($me->is_allowed_to('列表仪器使用记录', Q("$me lab")->current())) {
            $tabs
                ->add_tab('lab', [
                    'url'   => URI::url('!equipments/records/entries.lab'),
                    'title' => I18N::T('equipments', '组内使用记录'),
                ]);
            !$tab && $tab = 'lab';
            $allow_tabs[] = 'lab';
            Event::bind('equipment.records.entries.view.content', 'Equipments::lab_view_content', 0, 'lab');
            Event::bind('equipment.records.entries.view.tool_box', 'Equipments::lab_view_tool', 0, 'lab');
        }

        if ($me->is_allowed_to('列表负责仪器使用记录', 'equipment')) {
            $tabs
                ->add_tab('incharge', [
                    'url'   => URI::url('!equipments/records/entries.incharge'),
                    'title' => I18N::HT('equipments', '您负责的所有仪器使用记录', ['%user' => $me->name]),
                ]);
            !$tab && $tab = 'incharge';
            $allow_tabs[] = 'incharge';
        }
        if ($me->is_allowed_to('列表组织机构仪器使用记录', 'equipment')) {
            $tabs
                ->add_tab('group', [
                    'url'   => URI::url('!equipments/records/entries.group'),
                    'title' => I18N::T('equipments', '下属机构所有仪器使用记录'),
                ]);
            !$tab && $tab = 'group';
            $allow_tabs[] = 'group';
        }
        if ($me->is_allowed_to('列表所有仪器使用记录', 'equipment')) {
            $tabs
                ->add_tab('all', [
                    'url'=>URI::url('!equipments/records/entries.all'),
                    'title'=>I18N::T('equipments', '所有仪器的使用记录'),
                ]);
            !$tab && $tab = 'all';
            $allow_tabs[] = 'all';
        }

       
        $this->layout->body->primary_tabs = $tabs;


        if (!in_array($tab, $allow_tabs)) {
            URI::redirect('error/401');
        }

        $type         = strtolower(Input::form('type'));
        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token            = Session::temp_token('eq_record_', 300);
            $form                  = Lab::form(self::_transform());
            $form['form_token']    = $form_token;
            $_SESSION[$form_token] = $form;
        }

        switch ($tab) {
            case "lab":
                $tabs->lab = Q("$me lab")->current();
                
                ;break;
            case "incharge":
                $eqs_incharge = Q("{$me} equipment.incharge");
                $this->_filter_and_set_view($type, $form, $this->_parse_id_selector($eqs_incharge));
                ;break;
            case "group":
                $eqs_under_my_group = Q("{$me->group} equipment");
                $this->_filter_and_set_view($type, $form, $this->_parse_id_selector($eqs_under_my_group), true);
                ;break;
            case "all":
                $this->_filter_and_set_view($type, $form);
                ;break;
        }

        $tabs
            ->tab_event('equipment.records.entries.view.tab')
            ->content_event('equipment.records.entries.view.content')
            ->tool_event('equipment.records.entries.view.tool_box')
            ->select($tab);
        
        
    }

    public function me()
    {
        $me = L("ME");

        if(!$me->id){
            URI::redirect('error/401');
        }

        $user = O("user",$me->id);
        $this->layout->body->primary_tabs = Widget::factory("tabs");
        $this->layout->body->primary_tabs->user = $user;
        $this->layout->title = null;
        
        Equipments::user_record_content(null, $this->layout->body->primary_tabs);
        Equipments::_tool_box_records(null, $this->layout->body->primary_tabs);
    }

    public function profile($id = 0)
    {
        $form = (array) Form::filter(Input::form());
        $type = strtolower($form['type']);
        unset($form['type']);
        if ($form['form_token'] && isset($_SESSION[$form['form_token']])) {
            $form = $_SESSION[$form['form_token']];
        }

        $now  = time();
        $user = O('user', $id);
        if (!$user->id) {
            URI::redirect('error/404');
        }
        $selector = "eq_record[dtend<=$now][user={$user}]";

        $pre_selectors = new ArrayIterator;
        if ($form['equipment_name']) {
            $equipment_name  = Q::quote($form['equipment_name']);
            $pre_selectors[] = "equipment[name*=$equipment_name]";
        }
        if ($form['lab_name']) {
            $lab_name        = Q::quote($form['lab_name']);
            $pre_selectors[] = "lab[name*=$lab_name]<lab user";
        }

        //按时间搜索
        if ($form['dtstart']) {
            $dtstart = Q::quote($form['dtstart']);
            $selector .= "[dtend>=$dtstart]";
        }

        if ($form['dtend']) {
            $dtend = Q::quote($form['dtend']);
            $dtend = Date::get_day_end($dtend);
            $selector .= "[dtend<=$dtend]";
        }

        /*if (!$form['dtstart_check'] && !$form['dtend_check']) {
        $form['dtend'] = Date::get_day_end(Date::time());
        $form['dtstart'] = Date::prev_time($form['dtend'], 1, 'm') + 1;
        }*/

        if (isset($form['lock_status'])) {
            $is_locked = !!$form['lock_status'] ? 1 : 0;
            $selector .= "[is_locked=$is_locked]";
        }

        //Hook
        $new_selector = Event::trigger('eq_record.search_filter.submit', $form, $selector);

        if (!is_null($new_selector)) {
            $selector = $new_selector;
        }

        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(', ', (array) $pre_selectors) . ') ' . $selector;
        }

        $selector .= ':sort(dtstart D)';

        $records = Q($selector);
        if (isset($type) && $type == 'print') {
            $this->_index_records_print($records, $form);
        } elseif (isset($type) && $type == 'csv') {
            $this->_index_records_csv($records);
        }
    }

    public function index()
    {
        $me = L('ME');
        if (!$me->is_allowed_to('列表所有仪器使用记录', 'equipment')) {
            URI::redirect('error/401');
        }

        $type = strtolower(Input::form('type'));

        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token            = Session::temp_token('eq_record_', 300);
            $form                  = Lab::form(self::_transform());
            $form['form_token']    = $form_token;
            $_SESSION[$form_token] = $form;
        }
        $this->layout->body->primary_tabs
            ->add_tab('records', [
                    'url'=>URI::url('!equipments/records/index'),
                    'title'=>I18N::T('equipments', '所有仪器的使用记录'),
                ])
            ->select('records');

        $this->_filter_and_set_view($type, $form);
    }

    /**
     * xiaopei.li@2011.02.21
     * 组织机构负责人可查看其组织机构拥有的所有仪器的使用记录
     *
     * @return
     */
    public function index_group()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('列表组织机构仪器使用记录', 'equipment')) {
            URI::redirect('error/401');
        }

        // I'm in charge of one group
        $group              = $me->group;
        $eqs_under_my_group = Q("{$group} equipment");

        $type       = strtolower(Input::form('type'));
        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token            = Session::temp_token('eq_record_', 300);
            $form                  = Lab::form(self::_transform());
            $form['form_token']    = $form_token;
            $_SESSION[$form_token] = $form;
        }

        $tag_title = I18N::HT('equipments', '%group所有仪器的使用记录', ['%group' => $group->name]);
        $this->layout->body->primary_tabs
            ->add_tab('group_records', [
                'url'   => URI::url('!equipments/records/index_group'),
                'title' => $tag_title,
            ])
            ->select('group_records');

        $this->_filter_and_set_view($type, $form, $this->_parse_id_selector($eqs_under_my_group), true);
    }

    /**
     * xiaopei.li@2011.02.21
     * 仪器负责人可查看所有自己负责的仪器的使用记录
     *
     * @return
     */
    public function index_incharge()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('列表负责仪器使用记录', 'equipment')) {
            // no rights
            URI::redirect('error/401');
        }

        // I'm in charge of some equipments
        $eqs_incharge = Q("{$me} equipment.incharge");
        $type         = strtolower(Input::form('type'));
        $form_token   = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token            = Session::temp_token('eq_record_', 300);
            $form                  = Lab::form(self::_transform());
            $form['form_token']    = $form_token;
            $form['page']          = 'incharge_records';
            $_SESSION[$form_token] = $form;
        }

        $this->layout->body->primary_tabs
            ->add_tab('incharge_records', [
                'url'   => URI::url('!equipments/records/index_incharge'),
                'title' => I18N::HT('equipments', '%user负责的所有仪器的使用记录', ['%user' => $me->name]),
            ])
            ->select('incharge_records');

        $this->_filter_and_set_view($type, $form, $this->_parse_id_selector($eqs_incharge));
    }

    /**
     * xiaopei.li@2011.02.22
     * lambda function cannot be property
     * so use a function to return it
     *
     * @return
     */
    private static function _transform()
    {
        return function (&$old_form, &$form) {
            unset($form['type']);
        };
    }

    /**
     * xiaopei.li@2011.02.21
     *
     * @param type
     * @param form
     * @param pre_pre_selector
     *
     * @return
     */
    private function _filter_and_set_view($type, $form, $pre_pre_selector = '', $hide_group_select = false)
    {
        $selector      = '';
        $pre_selectors = new ArrayIterator;

        /* TASK #1063::CF中所有仪器使用记录和机主的使用记录搜索栏增加组织机构和分类标签查询(xiaopei.li@2011.06.13) */
        $eq_selector = [];
        $group_root  = Tag_Model::root('group');
        $tag_root    = Tag_Model::root('equipment');

        //仪器组织机构
		if ($form['group_id']) {
			$group = O('tag_group', $form['group_id']);

            if ($group->id && $group->root->id == $group_root->id && $group->id != $group_root->id) {
                $eq_selector[] = "{$group} equipment";
            } else {
                unset($form['group_id']);
            }
        }
        //仪器分类
		if ($form['tag_id']) {
			$tag = O('tag_equipment', $form['tag_id']);

            if ($tag->id && $tag->root->id == $tag_root->id && $tag->id != $tag_root->id) {
                $eq_selector[] = "{$tag} equipment";
            } else {
                unset($form['tag_id']);
            }
        }

        //课题组组织机构
        if ($form['lab_group']) {
            $lab_group = O('tag_group', $form['lab_group']); 
            if ($lab_group->id && $lab_group->root->id == $group_root->id && $lab_group->id != $group_root->id) {
                $eq_selector[] = "{$lab_group} lab user";
            } else {
                unset($form['lab_group']);
            }
        }

        if ($pre_pre_selector) {
            $eq_selector[] = $pre_pre_selector;
        } else {
            if (count($eq_selector)) {
                $eq_selector[] = 'equipment';
            }
        }

        if (count($eq_selector)) {
            $pre_selectors[] = join(',', $eq_selector);
        }

        if ($form['equipment_name']) {
            $equipment_name  = Q::quote(trim($form['equipment_name']));
            $pre_selectors['equipment'] = "equipment[name*=$equipment_name]";
        }

        if ($form['equipment_ref']) {
            $equipment_ref = Q::quote(trim($form['equipment_ref']));
            if ($form['equipment_name']) {
                $pre_selectors['equipment'] .= "[ref_no*={$equipment_ref}]";
            } else {
                $pre_selectors['equipment'] = "equipment[ref_no*={$equipment_ref}]";
            }
        }

        if ($form['user_name']) {
            $user_name       = Q::quote(trim($form['user_name']));
            $pre_selectors[] = "user[name*=$user_name]";
        }
        if ($form['lab_name']) {
            $lab_name        = Q::quote(trim($form['lab_name']));
            $pre_selectors[] = "lab[name*=$lab_name] user";
        }

        $selector .= ' eq_record';
        $new_selector = Event::trigger('eq_record.selector.modify', $selector, $form);

        if ($new_selector) {
            $selector = $new_selector;
        } else {
            if ($form['dtstart']) {
                $dtstart = Q::quote($form['dtstart']);
                $selector .= "[dtend>=$dtstart]";
            }

            if ($form['dtend']) {
                $dtend = Q::quote($form['dtend']);
                // $dtend = Date::get_day_end($dtend);
                $selector .= "[dtend<=$dtend]";
            }

            /*if (!$form['dtstart_check'] && !$form['dtend_check']) {
        $dtend_date = getdate(time());
        $form['dtend'] = mktime(23, 59, 59, $dtend_date['mon'], $dtend_date['mday'], $dtend_date['year']);
        $form['dtstart'] = Date::prev_time($form['dtend'], 1, 'm') + 1;
        }*/
        }

        if (isset($form['lock_status']) && $form['lock_status'] != -1) {
            $is_locked = $form['lock_status'] == 1 ? 1 : 0;
            $selector .= "[is_locked=$is_locked]";
        }
        if ($form['id']) {
            $id = Q::quote($form['id']);
            $selector .= "[id=$id]";
        }

        $now = time();
        $selector .= "[dtstart<=$now]"; //[dtstart<=$now]

        /* TASK #1063::打印和导出CSV时如不填写时间，默认打印1个月之类的数据(xiaopei.li@2011.06.16) */
        $export_types = ['print', 'csv'];
        if (in_array($type, $export_types) &&
            !($form['dtstart_check'] || $form['dtend_check'])) {
            $dt_from = strtotime('midnight -1 month');
            $selector .= "[dtstart>=$dt_from]";
        }

        if (Config::get('eq_record.duty_teacher') && $form['duty_teacher']) {
            $duty_teacher = Q::quote($form['duty_teacher']);
            $selector .= "[duty_teacher_id={$duty_teacher}]";
        }

        if ($form['use_type']) {
            $use_type = Q::quote($form['use_type']);
            $selector .= "[use_type=$use_type]";
        }

        $pre_selectors = Event::trigger('eq_record.extra_search.pre_selector', $form, $pre_selectors) ? : $pre_selectors;
        $new_selector = Event::trigger('eq_record.search_filter.submit', $form, $selector, $pre_selectors);
        if (null !== $new_selector) {
            $selector = $new_selector;
        }
        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(', ', (array) $pre_selectors) . ') ' . $selector;
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        $sort_str  = ':sort(dtstart D)';

        $new_sort_str = Event::trigger('eq_record.sort_str_factory', $form, $sort_str, $type);
        if (null !== $new_sort_str) {
            $sort_str = $new_sort_str;
        }

        $selector .= $sort_str;
        $form['selector'] = $selector;
        $records          = Q($selector);

        if (!in_array($type, $export_types)) {
            $type = 'html';
        }

        if ($hide_group_select && $type == 'html') {
            $this->_index_records_html($records, $form, $per_page, $hide_group_select);
        } else {
            call_user_func([$this, '_index_records_' . $type], $records, $form, $per_page);
        }
    }

    /**
     * xiaopei.li@2011.02.21
     *
     * @param eqs
     *
     * @return
     */
    private function _parse_id_selector($eqs)
    {
        if (count($eqs)) {
            foreach ($eqs as $eq) {
                $eq_ids[] = $eq->id;
            }
            $id_selector = "equipment[id=$eq_ids[0]";
            for ($i = 1; $i < count($eq_ids); $i++) {
                $id_selector .= ',' . $eq_ids[$i];
            }
            $id_selector .= '] ';
            return $id_selector;
        } else {
            return 'equipment[id=0]';
        }
    }

    private function _index_records_print($records, $form)
    {
        $this->layout = Event::trigger('print.equipment.records', $records, $form, true);
        /* 记录日志 */
        $me = L('ME');
        Log::add(strtr('[equipments] %user_name[%user_id]打印了仪器的使用记录', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');
    }

    private function _index_records_html($records, $form, $per_page, $hide_group_select = false)
    {
        $total_count = $records->total_count();
        $start       = (int) $form['st'];
        $per_page    = 20;
        $start       = $start - ($start % $per_page);

        if ($start > 0) {
            $last = floor($records->total_count() / $per_page) * $per_page;
            if ($last == $records->total_count()) {
                $last = max(0, $last - $per_page);
            }
            if ($start > $last) {
                $start = $last;
            }
            $records = $records->limit($start, $per_page);
        } else {
            $records = $records->limit($per_page);
        }

        $pagination = Widget::factory('pagination');
        $pagination->set([
            'start'    => $start,
            'per_page' => $per_page,
            'total'    => $records->total_count(),
        ]);
        /*
        NO.BUG#108（guoping.zhang@2010.11.12)
        因为打印按钮和导出CSV按钮，用Widg添加记录et显示
        所以panel_buttons键结构是url，text，extra
         */
        $panel_buttons         = [];
        $form_token            = $form['form_token'];
        $_SESSION[$form_token] = ['selector' => $form['selector'], 'form' => $form];
        $panel_buttons[]       = [
            'tip'   => I18N::T('equipments', '导出Excel'),
            'text' => I18N::T('equipments', '导出'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!equipments/equipment') .
            '" q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) .
            '" class="button button_save "',
        ];
        $panel_buttons[] = [
            'tip'   => I18N::T('equipments', '打印'),
            'text' => I18N::T('equipments', '打印'),
            'extra' => 'q-object="output" q-event="click" q-src="' . URI::url('!equipments/equipment') .
            '" q-static="' . H(['form_token' => $form_token, 'type' => 'print']) .
            '" class = "button button_print  middle"',
        ];
        $new_panel_buttons = Event::trigger('eq_record_lab_use.panel_buttons', $panel_buttons, $form_token);
        $panel_buttons     = $new_panel_buttons ? $new_panel_buttons : $panel_buttons;

        $search_filters = new ArrayIterator;
        Event::trigger('eq_record.search_filter.view', $form, $search_filters);
        $content = V(
            'records',
            [
                'records'           => $records,
                'search_filters'    => $search_filters,
                'form'              => $form,
                'pagination'        => $pagination,
                'panel_buttons'     => $panel_buttons,
                'hide_group_select' => $hide_group_select,
                'total_count'       => $total_count,
            ]
        );

        $this->layout->body->primary_tabs
            ->set('content', $content);
    }

    /*
    2010.11.04 by cheng.liu
    仪器实用统计功能，没有对应的tab显示
    而且没有任何地方链接过来，暂时隐藏，
    需要时再打开
     */
    /*
    function stat(){
    $this->layout->body->primary_tabs->select('stat');
    $this->layout->body->primary_tabs->content = V('stat');
    }
     */

    private function _index_records_csv($records)
    {
        Event::trigger('export.equipment.records', $records);
    }

    public function nofeedback()
    {
        $me = L('ME');
        if ($me->id) {
            $now     = Date::time();
            $status  = EQ_Record_Model::FEEDBACK_NOTHING;
            $records = Q("eq_record[user=$me][dtend!=0][dtend<$now][status=$status]");
            foreach ($records as $record) {
                $dtstart = $record->dtstart;
                $dtend   = $record->dtend;

                $db  = Database::factory();
                $sql = "SELECT `project_id` FROM `eq_reserv`
				WHERE `dtstart` BETWEEN $dtstart AND $dtend
				OR `dtend` BETWEEN $dtstart AND $dtend
				OR (`dtstart` > $dtstart AND `dtend` < $dtend) LIMIT 1";
                $project = O('lab_project', $db->value($sql));

                $record->project = $project;
                $record->status  = EQ_Record_Model::FEEDBACK_NORMAL;
                $record->save();
            }
        }
        URI::redirect($_SERVER['HTTP_REFERER']);
    }
}

class Records_AJAX_Controller extends AJAX_Controller
{
    public function index_record_edit_click()
    {
        $form   = Form::filter(Input::form());
        $record = O('eq_record', $form['record_id']);
        $me = L('ME');
        if (!$me->is_allowed_to('修改', $record)) {
            return;
        }

        $sections = new ArrayIterator;

        Event::trigger('eq_record.edit_view', $record, $form, $sections);

        if ($me->is_allowed_to('修改', $record) || count($sections)) {
            JS::dialog(V('record.edit', [
                'record'    => $record,
                'sections'  => $sections,
                'equipment' => $record->equipment,
            ]), [
                'title' => '修改使用记录',
            ]);
        }
    }

    function index_record_edit_submit() {
		$form = Form::filter(Input::form());
		$form->validate('record_id', 'is_numeric', I18N::T('equipments', '操作有误!'));
		$record = O('eq_record', $form['record_id']);

        //设定手动修改record
        //Cache::L('modify_record_manually', TRUE);
		
		$me = L('ME');		

		//记录原来record的相关信息，用于对比是否修改了record
		if ($record->id){
			$old_user = $record->user;
			$old_dtstart = $record->dtstart;
			$old_dtend = $record->dtend ?: 0;
			# 新增字段消息发送
			$old_extra = Event::trigger('eq_record.old.extra.field', $record) ? : 0;
			$old_field = Event::trigger('eq_record.extra.field', $record, 'origin') ? : 0;
			$old_samples = $record->samples;
		}

		if ($GLOBALS['preload']['people.multi_lab']) {
			$lab = $record->project->lab;
		}
		else {
		    $u = $form['user_id'] ? O('user',$form['user_id']) : $me;
			$lab = Q("$u lab")->current();
		}
		$lab_owner = $lab->owner;

		if ($record->id && $form['submit'] == 'delete') {
			if (!JS::confirm(I18N::T('equipments', '您确定删除该记录吗?'))) return;
			if ($me->is_allowed_to('删除', $record)) {

                Log::add(strtr('[equipments] %user_name[%user_id]删除%equipment_name[%equipment_id]仪器的使用记录[%record_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $record->equipment->name, '%equipment_id'=> $record->equipment->id, '%record_id'=> $record->id]), 'journal');

				$record_attachments_dir_path = NFS::get_path($record, '', 'attachments', TRUE);
				
				Event::trigger('equipments.record_form.delete', $form, $record);
				$user = $record->user;
				$equipment = $record->equipment;
				$contacts = Q("$equipment user.contact");
				$edit_time = Date::format(time());
				$edit_user = $me;
				$dtstart = $record->dtstart;
				$dtend = $record->dtend ? Date::format($record->dtend) : I18N::T('equipments', '使用中') ;
				$old_field = Event::trigger('eq_record.extra.field', $record) ? : 0;
				$link = $equipment->url('records');
				$samples = $record->samples;

                $samples = Q("$record eq_sample");
                foreach ($samples as $s) {
                    $s->disconnect($record);

                    $sc = O('eq_charge', ['source' => $s]);
                    if (!$sc->id && !$equipment->charge_script['sample']) {
                        continue;
                    }

                    if (!$sc->source->id) {
                        $sc->source = $s;
                    }

                    $sc->user      = $s->sender;
                    $sc->lab       = $GLOBALS['preload']['people.multi_lab'] ? $s->project->lab : Q("$s->sender lab")->current();
                    $sc->equipment = $s->equipment;
                    $sc->calculate_amount()->save();
                    $s->save();
                }

				$record->delete();	
				File::rmdir($record_attachments_dir_path);

				Event::trigger('equipments.record_form.after_delete', $form, $record);
				//用token判断用户不为临时用户
                if ($GLOBALS['preload']['people.multi_lab']) {
                    $lab = $record->project->lab;
                }
                else {
                    $lab = Q("$user lab")->current();
                }
                $lab_owner = $lab->owner;
				if($user->token){
					Notification::send('equipments.delete_record_to_user', $user, [
							'%user' => Markup::encode_Q($user),
							'%equipment' => Markup::encode_Q($equipment),
							'%time' => $edit_time,
							'%edit_user' => Markup::encode_Q($edit_user),
							'%record_id' => Number::fill($record->id, 6),
							'%old_dtstart' => Date::format($dtstart),
							'%old_dtend' => $dtend,
							'%old_field' => $old_field,
							'%old_sample_num'=>$record->samples,
							'%contact_phone'=>$edit_user->phone,
							'%contact_email'=>$edit_user->email,
					]);
				}

				//发送给仪器联系人
				foreach($contacts as $contact){
					Notification::send('equipments.delete_record_to_contact', $contact, [
						'%contact'=> Markup::encode_Q($contact),
						'%user' => Markup::encode_Q($user),
						'%equipment' => Markup::encode_Q($equipment),
						'%time' => $edit_time,
						'%edit_user' => Markup::encode_Q($edit_user),
						'%record_id' => Number::fill($record->id, 6),
						'%old_dtstart' => Date::format($dtstart),
						'%old_dtend' => $dtend,
						'%old_field' => $old_field,
						'%old_sample_num'=>$record->samples,
					]);
				}

				if($lab_owner->id){
					Notification::send('equipments.delete_record_to_pi', $lab_owner, [
						'%pi'=> Markup::encode_Q($lab_owner),
						'%user' => Markup::encode_Q($user),
						'%equipment' => Markup::encode_Q($equipment),
						'%time' => $edit_time,
						'%edit_user' => Markup::encode_Q($edit_user),
						'%record_id' => Number::fill($record->id, 6),
						'%old_dtstart' => Date::format($dtstart),
						'%old_dtend' => $dtend,
						'%old_field' => $old_field,
						'%old_sample_num'=>$record->samples,
					]);
				}

				Event::trigger('operation.after.record.delete', $user, $equipment, $edit_time, $edit_user, $record, $dtstart, $dtend, $samples);

				JS::refresh();
			}
			else {
				JS::alert(I18N::T('equipments', '您无权操作该时间段内的使用记录!'));
			}
			return;
		}
		
		if (!$record->id) {
			$record->equipment = O('equipment', $form['equipment_id']);
		}

		$dtstart = $form['dtstart'] ?: ($record->dtstart ?: Date::time());
		$dtend   = isset($form['dtend_check']) ? ($form['dtend_check'] == 'on' ? $form['dtend'] : 0 ) : ($form['dtend'] ?: $record->dtend);
            
		if ($dtend > 0 && $dtstart > $dtend) {
        	list($dtstart, $dtend) = [$dtend, $dtstart];
		}

        if ($dtend > 0) {
            $str_end = date("s", $dtend);
            $dtend = $str_end == "00" ? $dtend - 1 : $dtend;
        }
		
		$record->dtstart = $dtstart;
		$record->dtend = $dtend;

        //1.使用记录之间不能有交集
        //2.一个仪器同时间只能有一条在使用中的记录
        //3.使用中的记录 它的开始时间一定要晚于所有记录的结束时间
        //4.提交的使用记录的结束时间必须大于锁定流水时间。
		if ($record->is_timespan_locked($dtstart, $dtend)) {
			JS::alert(I18N::T('equipments', '您设置的时段已被锁定!'));
			return;
		}
        //当提交的结束时间<锁定时间时
		if (!$me->is_allowed_to('修改', $record)) {
			JS::alert(I18N::T('equipments', '您无权操作该时间段内的使用记录!'));
			return;
		}
		$equipment = $record->equipment;

		$has_new_user = FALSE;
		if ($me->is_allowed_to('管理仪器临时用户', $equipment)) {
			if ($form['user_option'] == 'new_user') {
				$has_new_user = TRUE;
				//$user = O('user', array('name'=>$form['user_name']));

				$form->validate('user_name', 'not_empty', I18N::T('equipments', '用户姓名 不能为空!'));

                if (!$form['user_email']) {
                    $form->set_error('user_email', I18N::T('equipments', '电子邮箱 不能为空!'));
                }
                else {
                    $form->validate('user_email', 'is_email', I18N::T('equipments', '电子邮箱 填写有误!'));

                    //如果user_email都没错
                    //比对是否系统中包含
                    if (!count($form->errors['user_email'])) {
                        //系统中存在已有该user_email的用户了
                        if (O('user', ['email'=> trim($form['user_email'])])->id) {
                            $form->set_error('user_email', I18N::T('equipments', '电子邮箱 已存在!'));
                        }
                    }
                }

                $form
                    ->validate('phone', 'not_empty', I18N::T('equipments', '联系电话 不能为空!'))
                    ->validate('user_org', 'not_empty', I18N::T('equipments', '单位名称 不能为空!'));

                if (Config::get('people.temp_user.tax_no.required', FALSE)) {
                    $form->validate('tax_no', 'not_empty', I18N::T('equipments', '税务登记号 不能为空!'));
                }

				$user = O('user');
				$user->name = $form['user_name'];
				$user->organization = $form['user_org'];
				$user->email = $form['user_email'];
				$user->creator = $me;	// 添加用户的添加人
				$user->ref_no = NULL;
				$user->phone = $form['phone'];
				$user->tax_no = $form['tax_no'];

				Event::trigger('extra.form.validate', $equipment, 'use', $form);
                if(Config::get('eq_record.tag_duty_teacher')) {
                    Event::trigger('extra.form.validate_duty_teacher', $equipment,$record, $form);
                }
				Event::trigger('equipments.record.create_user_before_save', $user, $form);

				if ($form->no_error) {
					$user->save();
					if (!$user->id) $form->set_error('user_email', I18N::T('equipments', '该电子邮箱已经被他人使用!'));
					else {
						$user->connect(Equipments::default_lab());
	
						Log::add(strtr('[equipments] %admin_name[%admin_id] 为 %equipment_name[%equipment_id] 添加临时用户 %user_name[%user_id]', ['%admin_name'=> $me->name, '%admin_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%user_name'=> $user->name, '%user_id'=> $user->id]), 'admin');
					}
				}
			}
		}

		if ($form->no_error) {
			Event::trigger('extra.form.validate', $equipment, 'use', $form);
            if(Config::get('eq_record.tag_duty_teacher')) {
                Event::trigger('extra.form.validate_duty_teacher', $equipment,$record, $form);
            }
		}
		
		if (!$has_new_user && $form['user_id']) {
			$user = O('user', $form['user_id']);
			if (!$user->id) { //对于用户指定的不存在的用户，将用户其设为零，报错
				$form->set_error('user_id', I18N::T('equipments', '请选择有效的用户!'));
			}
			if (!$GLOBALS['preload']['people.multi_lab'] && !$lab->id) {
				$form->set_error('user_id', I18N::T('equipments', '请确认用户有实验室!'));
			}	
		}

        /**
         * 0 在这里被is_numeric通过了.. 
         * 导致样品数选0没报错，数据也没变
         * 先别改，改了下面的trigger也得改..
         */
		if($record->id && !$record->cannot_lock_samples() && !$record->samples_lock && isset($form['samples']) && !is_numeric($form['samples']) ) {
			$form->set_error('samples', I18N::T('equipments', '请输入有效的样品数!'));
			
			if (Config::get('equipment.feedback_samples_allow_zero', FALSE)) {
				if (intval($form['samples']) < 0) {
					$form->set_error('samples',  I18N::T('equipments', '样品数填写有误, 请填写大于0的整数!'));
				}
			} else {
				if (intval($form['samples']) <= 0) {
					$form->set_error('samples',  I18N::T('equipments', '样品数填写有误, 请填写大于或等于0的整数!'));
				}
			}
		}

        if (($form['dtend_check'] == 'on' && $form['dtstart'] == $form['dtend'])
			||(!$me->access('管理所有内容') && !Event::trigger('equipments.allow_incharge_edit_dtend', $me, $equipment) && $form['dtstart'] && $form['dtend'] && $form['dtstart'] == $form['dtend'])
		) {
            $form->set_error('dtend', I18N::T('equipments', '结束时间不能与开始时间相同!'));
        }

		if (Config::get('eq_record.duty_teacher') && !$form['duty_teacher'] && $record->equipment->require_dteacher) {
            if(!Config::get('eq_record.tag_duty_teacher')) {
                $form->set_error('duty_teacher', I18N::T('equipments', '请选择值班老师!'));
            }
		}
		
        if (Config::get('equipment.enable_use_type') && !$form['use_type']) {
            $form->set_error('use_type', I18N::T('equipments', '请选择使用类型!'));
        }

        if (Config::get('equipment.enable_use_type') && mb_strlen($form['use_type_desc']) > 100) {
            $form->set_error('use_type_desc', I18N::T('equipments', '备注不能超过 100 个字符!'));
        }

		//加入工作时间设置后，添加使用记录时需要检查时间是否在设置时间之内
		$w = date('w', $dtstart);
		$weekday_begindate = $dtstart - $w*86400;
		$weekday_start = mktime(0,0,0, date('m', $weekday_begindate), date('d', $weekday_begindate), date('Y', $weekday_begindate));
		$workingtime = Event::trigger('eq_empower.get_workingtime_week', $equipment->id, $weekday_start, $user);
		$dtstart_time = mktime(date('H', $dtstart), date('i', $dtstart), date('s', $dtstart), '01', '01', '1971');
		if (isset($workingtime)) {
			if(array_key_exists($w, (array)$workingtime)) {
				if($dtstart_time < $workingtime[$w]['starttime'] || $dtstart_time > $workingtime[$w]['endtime']) {
					$form->set_error('dtstart', I18N::T('equipments', '非工作时间内不允许添加使用记录'));
				}
			}	
		}

        $uncontroluser = [];
		if($empower->uncontroluser) $uncontroluser = array_keys(json_decode($empower->uncontroluser, TRUE));
		if($empower->uncontrollab) $uncontrollab = array_keys(json_decode($empower->uncontrollab, TRUE));
		if($empower->uncontrolgroup) $uncontrolgroup = array_keys(json_decode($empower->uncontrolgroup, TRUE));
		if (!isset($uncontrollab)) {
			$uncontrollab = [];
		}
		if (!isset($uncontrolgroup)) {
			$uncontrolgroup = [];
		}
		if( !in_array($user->id, (array)$uncontroluser) && 
			!in_array($lab->id, (array)$uncontrollab) && 
			!in_array($lab->group->id, (array)$uncontrolgroup)){
			$in_rules = true;	
		}
		else {
			$form->set_error('dtstart', I18N::T('equipments', '非工作时间内不允许添加使用记录'));
		}

		if ($form->no_error) {

            if(isset($form['reserv_status']) && $record->reserv->status != $form['reserv_status']){
                $record->reserv->status = $form['reserv_status'];
                $record->reserv->save();
                $record->flag = $form['reserv_status'];
            }

            /**
             * 在一些场景下可以将使用记录的测样数调整为0 -> 西交大修改类型为"保养维修"，测样数为0
             */
            if (Event::trigger('eq_record.edit_samples', $record, $form) === 0) {
                $record->samples = 0;
            }

			//没有被锁定, 进行修改
            if (isset($form['samples']) && !$record->samples_lock) {
                $record->samples = (int)max($form['samples'], 0);
			}

			if ($user->id && $user->id != $record->user->id) {
				//清除仪器负责人关闭设备时的状态
				if (!$record->agent->id) $record->agent = $record->user;
				$record->user = $user;
			}

			if ($form['agent_id'] && $record->agent->id
				&& $me->is_allowed_to('修改代开者', $record)) {
				// 由于修改普通记录时直接修改agent条件不易判断
				// 所以暂时仅记录有agent时，可修改agent
				// (xiaopei.li@2011.09.13)
				$agent = O('user', $form['agent_id']);
				if ($agent->id) {
					$record->agent = $agent;
				}
			}

			if (!$record->user->id) $record->user = $me;

			if ($record->id) {
                $action = '修改';
			}
			else {
                $action = '添加';
			}

            Event::trigger('eq_record.edit_submit', $record, $form);

            if ($record->id) {
                Event::trigger('eq_record_notification', O('eq_record', $record->id), $form);
            }
            //自定义使用表单存储供lua计算
            if (Module::is_installed('extra')) {
                $record->extra_fields = $form['extra_fields'];
            }

			if (Config::get('eq_record.duty_teacher') && $record->equipment->require_dteacher) {
				$duty_teacher = O('user', $form['duty_teacher']);
                $record->duty_teacher = $duty_teacher;
			}
			
            if (Config::get('equipment.enable_use_type')) {
                $record->use_type = $form['use_type'];
                $record->use_type_desc = $form['use_type_desc'];
			}
			
			//RQ181907-机主编辑使用记录时有一个可以填写的备注框（备注非必填）
			if (Config::get('eq_record.charge_desc')) {
				$record->charge_desc = $form['charge_desc'];
			}

            $record->save();
            if ($GLOBALS['preload']['people.multi_lab']) {
                $lab = $record->project->lab;
            }
            else {
                $lab = Q("$record->user lab")->current();
            }
            $lab_owner = $lab->owner;
			Event::trigger('extra.form.post_submit', $record, $form);

			$equipment = $record->equipment;
			$contacts = Q("$equipment user.contact");
			$edit_time = Date::format(time());
			$edit_user = $me;
			$dtstart = $record->dtstart;
			$dtend = $record->dtend ? Date::format($record->dtend) : I18N::T('equipments', '使用中');
			$field = Event::trigger('eq_record.extra.field', $record) ? : 0;
			$link = $equipment->url('records');

			if($action == '添加'){
				$in_control = $equipment->control_mode && $equipment->control_mode!=='nocontrol';
				if ( !$in_control 
					|| !$record->dtend ) { 
					$status = I18N::T('eq_reserv', EQ_Reserv_Model::$reserv_status[EQ_Reserv_Model::NORMAL]);
				}
				else {
					$reserv = $record->reserv ;
					if ( !$reserv->id ) {
						$status = I18N::T('eq_reserv', EQ_Reserv_Model::$reserv_status[EQ_Reserv_Model::NORMAL]);
					} 
					else {
						if ( $reserv->dtend < $record->dtend ) {
							$status = I18N::T('eq_reserv', EQ_Reserv_Model::$reserv_status[EQ_Reserv_Model::OVERTIME]);
						}
						else {
							 $status = I18N::T('eq_reserv', EQ_Reserv_Model::$reserv_status[EQ_Reserv_Model::NORMAL]);
						}
					}
				}
				
				//发消息给非临时用户
				if(!$has_new_user){
					//发送给使用者					
					Notification::send('equipments.add_record_to_user', $user, [
						'%user' => Markup::encode_Q($user),
						'%equipment' => Markup::encode_Q($equipment),
						'%time' => $edit_time,
						'%edit_user' => Markup::encode_Q($edit_user),
						'%record_id' => Number::fill($record->id, 6),
						'%dtstart' => Date::format($dtstart),
						'%dtend' => $dtend,
						'%field' => $field,
						'%sample_num'=>$record->samples,
						'%link' => $link,
						'%contact_phone'=>$edit_user->phone,
						'%contact_email'=>$edit_user->email,
					]);
				}

				//发送给仪器联系人
				foreach($contacts as $contact){
					Notification::send('equipments.add_record_to_contact', $contact, [
						'%contact'=> Markup::encode_Q($contact),
						'%user' => Markup::encode_Q($user),
						'%equipment' => Markup::encode_Q($equipment),
						'%time' => $edit_time,
						'%edit_user' => Markup::encode_Q($edit_user),
						'%record_id' => Number::fill($record->id, 6),
						'%dtstart' => Date::format($dtstart),
						'%dtend' => $dtend,
						'%field' => $field,
						'%sample_num'=>$record->samples,
						'%link' => $link,
					]);
				}
			
				if($lab_owner->id){
					$link = $lab->url('eq_record');
					Notification::send('equipments.add_record_to_pi', $lab_owner, [
						'%pi'=> Markup::encode_Q($lab_owner),
						'%user' => Markup::encode_Q($user),
						'%equipment' => Markup::encode_Q($equipment),
						'%time' => $edit_time,
						'%edit_user' => Markup::encode_Q($edit_user),
						'%record_id' => Number::fill($record->id, 6),
						'%dtstart' => Date::format($dtstart),
						'%dtend' => $dtend,
						'%field' => $field,
						'%sample_num'=>$record->samples,
						'%link' => $link,
					]);
				}
			}
			elseif($action == '修改'){

				$field_edit_content = Event::trigger('eq_record.edit.notification', $record, $old_field, $old_extra) ? : '';
				$field = Event::trigger('eq_record.extra.field', $record, 'origin') ? : 0;
				//如果时间，用户，样品数，使用状态改变
				if($old_dtstart != $record->dtstart
					|| $old_dtend != $record->dtend
					|| $old_samples != $record->samples
					|| $old_user->id != $record->user->id
					|| ($old_field && $old_field != $field)
					){

					$edit_content = '';
					if($old_user->id != $record->user->id){
						$old_user_name = Markup::encode_Q($old_user);
						$new_user_name = Markup::encode_Q($record->user);
						$edit_content .= I18N::T('equipments', "修改前使用者为: %old_user_name, 修改后使用者为: %new_user_name\n", [
							'%old_user_name' => $old_user_name ,
							'%new_user_name' => $new_user_name
						
						]);
						$user_changed = TRUE;
					}	

					if($old_dtstart != $record->dtstart || $old_dtend != $record->dtend){
						$old_dtstart = Date::format($old_dtstart);
						$old_dtend = Date::format($old_dtend);
						$new_dtstart = Date::format($record->dtstart);
						$new_dtend = Date::format($record->dtend);
						$edit_content .= I18N::T('equipments', "修改前使用时间为: %old_dtstart - %old_dtend, 修改后使用时间为: %new_dtstart - %new_dtend\n", [
							'%old_dtstart' => $old_dtstart,
							'%old_dtend' => $old_dtend,
							'%new_dtstart' => $new_dtstart,
							'%new_dtend' => $new_dtend,
							
						]);
					}
					if($old_samples != $record->samples){
						$edit_content .= I18N::T('equipments', "修改前样品数为: %old_samples, 修改后样品数为: %new_samples\n", [
							'%old_samples' => $old_samples,
							'%new_samples' => $record->samples,
						]);
					}
					$edit_content .= $field_edit_content;
					
					//发消息给非临时用户,用token判断是否为临时用户
					if($user_changed && $old_user->token){
						//发送给使用者					
						Notification::send('equipments.edit_record_to_user', $old_user, [
							'%user' => Markup::encode_Q($old_user),
							'%equipment' => Markup::encode_Q($equipment),
							'%time' => $edit_time,
							'%edit_user' => Markup::encode_Q($edit_user),
							'%record_id' => Number::fill($record->id, 6),
							'%edit_content'=>$edit_content,
							'%link' => $link,
							'%contact_phone'=>$edit_user->phone,
							'%contact_email'=>$edit_user->email,
						]);
					}

					if($record->user->token){
						//发送给使用者					
						Notification::send('equipments.edit_record_to_user', $record->user, [
							'%user' => Markup::encode_Q($user),
							'%equipment' => Markup::encode_Q($equipment),
							'%time' => $edit_time,
							'%edit_user' => Markup::encode_Q($edit_user),
							'%record_id' => Number::fill($record->id, 6),
							'%edit_content'=>$edit_content,
							'%link' => $link,
							'%contact_phone'=>$edit_user->phone,
							'%contact_email'=>$edit_user->email,
						]);
					}

					//发送给仪器联系人
					foreach($contacts as $contact){
						Notification::send('equipments.edit_record_to_contact', $contact, [
							'%contact'=> Markup::encode_Q($contact),
							'%user' => Markup::encode_Q($user),
							'%equipment' => Markup::encode_Q($equipment),
							'%time' => $edit_time,
							'%edit_user' => Markup::encode_Q($edit_user),
							'%record_id' => Number::fill($record->id, 6),
							'%edit_content'=>$edit_content,
        					'%user_phone' => $user->phone,
							'%user_email' => $user->email,
							'%link' => $link,
						]);
					}

					if($lab_owner->id){
						$link = $lab->url('eq_record');
						Notification::send('equipments.edit_record_to_pi', $lab_owner, [
							'%pi'=> Markup::encode_Q($lab_owner),
							'%user' => Markup::encode_Q($user),
							'%equipment' => Markup::encode_Q($equipment),
							'%time' => $edit_time,
							'%edit_user' => Markup::encode_Q($edit_user),
							'%edit_content'=>$edit_content,
							'%record_id' => Number::fill($record->id, 6),
							'%link' => $link,
						]);
					}

					Event::trigger('operation.after.record.edit', $user, $equipment, $edit_time, $edit_user, $record, $edit_content, $link);

				}
			}	

            switch($action) {
                case '添加' :
                    Log::add(strtr('[equipments] %user_name[%user_id]添加了%equipment_name[%equipment_id]仪器的使用记录[%record_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%record_id'=> $record->id]), 'journal');
                    break;
                case '修改' :
                    Log::add(strtr('[equipments] %user_name[%user_id]修改了%equipment_name[%equipment_id]仪器的使用记录[%record_id]', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%record_id'=> $record->id]), 'journal');
                    break;
            }

			if ($record->dtstart > time()) {
				JS::close_dialog();
				JS::alert(I18N::T('equipments', '新添加的使用记录在未来的某个时间，目前暂时不会显示在使用记录列表。'));
			}
			else {
				JS::refresh();
			}
		}
		else {
		
			$sections = new ArrayIterator;
			
			Event::trigger('eq_record.edit_view', $record, $form, $sections);
			
			if($me->is_allowed_to('修改', $record) || count($sections)){
				JS::dialog(V('record.edit',[
					'record'=>$record, 
					'form'=>$form, 
					'sections'=>$sections,
					'equipment'=>$equipment,
				]));
			}
		}

        //Cache::L('modify_record_manually', NULL);
	}

    public function index_download_attachments_click()
    {
        $record = O('eq_record', Input::form('id'));
        if ($record->id) {
            JS::dialog(V('equipments:record.attachments', [
                'record' => $record,
            ]), [
                /* 如果用url去解决的话那么我们之前的通用插件均需要进行调整，url抓取时间会在dialog-show之前进行，但是目前样式存在问题，所以还是需要进行后续矫正后进行处理*    'url' => URI::url() */
                // div-load之后会自动运用window.resize, 将dialog中的调整大小绑定相应的windowsize事件，但是为了效果上没影响默认增加个最小的width
                'width' => 150,
            ]);
        }
    }

    public function index_lock_click($id = 0)
    {
        $lock_no_confirm = Config::get('eq_record.lock_no_confirm'); //是否提示
        if ($lock_no_confirm || JS::confirm(I18N::T('equipments', '您确定锁定该条使用记录吗?'))) {
            $form    = Input::form();
            $element = $form['ajax_id'];
            $record  = O('eq_record', $id);
            if ($record->id && L('ME')->is_allowed_to('锁定', $record)) {
                $record->is_locked   = true;
                $record->locked_flag = true;
                $record->save();

                $ajax_id = uniqid();
                $links   = $record->links('index', $ajax_id);
                /*Output::$AJAX['#'.$element] = [
                'data' => (string)V('equipments:records_table/data/rest', ['links' => $links, 'ajax_id' => $ajax_id ] ),
                'mode'=>'replace'
                ];*/
                JS::refresh();
            }
        }
    }

    public function index_unlock_click($id = 0)
    {
        $lock_no_confirm = Config::get('eq_record.lock_no_confirm'); //是否提示
        if ($lock_no_confirm || JS::confirm(I18N::T('equipments', '您确定解锁该条使用记录吗?'))) {
            $form    = Input::form();
            $element = $form['ajax_id'];
            $record  = O('eq_record', $id);
            if ($record->id && L('ME')->is_allowed_to('锁定', $record)) {
                $record->is_locked   = 0;
                $record->locked_flag = false;
                $record->save();

                $ajax_id = uniqid();
                $links   = $record->links('index', $ajax_id);
                Event::trigger('eq_record.unlock.click', $record);
                JS::refresh();
            }
        }
    }

    public function index_samples_lock_click()
    {
        $form   = Input::form();
        $record = O('eq_record', $form['rid']);

        $me = L('ME');
        if (!$me->is_allowed_to('锁定送样数', $record)) {
            return false;
        }

        if (!is_numeric($form['samples'])) {
            JS::alert(I18N::T('equipments', '请输入有效的样品数!'));
            return false;
        } else {
            $samples = $form['samples'] > 0 ? (int) $form['samples'] : 1;
            $record->lock_samples($samples);

            Output::$AJAX['data'] = (string) V('equipments:record/samples_lock', ['id' => $form['id'], 'record' => ORM_Model::refetch($record)]);
        }
    }

    public function index_samples_unlock_click()
    {
        $form   = Input::form();
        $record = O('eq_record', $form['rid']);

        $me = L('ME');
        if (!$me->is_allowed_to('锁定送样数', $record)) {
            return false;
        }

        $record->unlock_samples();

        Output::$AJAX['data'] = (string) V('equipments:record/samples_lock', ['id' => $form['id'], 'record' => ORM_Model::refetch($record)]);
    }

    // 为了弹出超过24小时给出提示所做的垃圾关闭功能
    public function index_time_check_close_click()
    {
        // 增加标示让系统知道这个用户当前登录状态下不再接受此类消息
        $me = L('ME');
        if (!$me->id) {
            return;
        }
        $form = Input::form();
        $key  = "time_check_user_{$me->id}";
        Lab::set($key, (array) $form['eids']);
        JS::refresh();
    }

    public function index_clear_leave_early_click()
    {
        $me     = L('ME');
        $form   = Input::form();
        $record = O('eq_record', $form['record_id']);

        if ($record->flag == EQ_Reserv_Model::LEAVE_EARLY) {
            $record->flag = EQ_Reserv_Model::NORMAL;
        } elseif ($record->flag == EQ_Reserv_Model::LATE_LEAVE_EARLY) {
            $record->flag = EQ_Reserv_Model::LATE;
        }

        if ($record->reserv->id) {
            $reserv = $record->reserv;
            if ($reserv->status == EQ_Reserv_Model::LEAVE_EARLY) {
                $reserv->status = EQ_Reserv_Model::NORMAL;
            } elseif ($reserv->status == EQ_Reserv_Model::LATE_LEAVE_EARLY) {
                $reserv->status = EQ_Reserv_Model::LATE;
            }
        }

		$record->clear_leave_early = TRUE;
		Cache::L('clear_leave_early_flag', TRUE);
		$record->save();
		$reserv->save();
		JS::refresh();
	}

	public static function index_batch_feedback_select(){

		$me = L('ME');
        $form = Form::filter(Input::form());
        $ids  = $form['ids'];

		$k = 'batch_feedback_select';
		$array = $_SESSION[$k] ?: [];

        foreach ((array)$ids as $key => $item) {
            if ($item) $array[$key] = true;
            else unset($array[$key]);
        }
		$_SESSION[$k] = $array;
		
	}

    public static function index_batch_feedback_click()
    {
        $me = L('ME');
		$ids = $_SESSION['batch_feedback_select'] ?: [];
		if(empty($ids)){
			JS::alert(I18N::T('equipments', '请先选择至少一条记录!'));
			return;
		}
		JS::dialog(V('equipments:record/add.feedback_batch', [
			'form' => $form
		]), [
			'width' => 150,
			'title' => '批量反馈'
		]);
    }

    public static function index_batch_feedback_submit() {
        $me = L('ME');
        $array = $_SESSION['batch_feedback_select'] ? : [];
		if(empty($array)){
			JS::alert(I18N::T('equipments', '请先选择至少一条记录!'));
			return;
		}
		$form = Form::filter(Input::form());

		if(!$form['record_status']){
			JS::alert(I18N::T('equipments', '请选择运行状态!'));
			return;
		}
		if ($form['record_status'] == EQ_Record_Model::FEEDBACK_PROBLEM && !$form['feedback']) {
			JS::alert(I18N::T('equipments', '请认真填写反馈信息!'));
			return;
        }

        if (Config::get('equipment.feedback_show_samples', 0)) {
            $is_feedback_problem = ($form['record_status'] == EQ_Record_Model::FEEDBACK_PROBLEM);
            if (Config::get('equipment.feedback_samples_allow_zero', FALSE)) {
                if (!is_numeric($form['samples']) || intval($form['samples'])<0 || intval($form['samples'])!=$form['samples']) {
                    JS::alert(I18N::T('equipments', '样品数填写有误, 请填写大于或等于0的整数!'));
                    return;
                }
            } else {
                if ($is_feedback_problem) {
                    if (!is_numeric($form['samples']) || intval($form['samples'])<0 || intval($form['samples'])!=$form['samples']) {
                        JS::alert(I18N::T('equipments', '样品数填写有误, 请填写大于或等于0的整数!'));
                        return;
                    }
                } else {
                    if (!is_numeric($form['samples']) || intval($form['samples'])<=0 || intval($form['samples'])!=$form['samples']) {
                        JS::alert(I18N::T('equipments', '样品数填写有误, 请填写大于0的整数!'));
                        return;
                    }
                }
            }
        }
        
		foreach($array as $rid => $v){
			$record = O('eq_record',$rid);

            if (Config::get('equipment.feedback_show_samples', 0)) {
                if ($record->samples != 1) {
                    JS::alert(I18N::T('equipments', $record->id.'记录不满足条件, 只能反馈样品数是 1 的使用记录!'));
                    return;
                }
            }

			$equipment = $record->equipment;
			if (!$equipment->id || $equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) continue;
			if (!$me->is_allowed_to('反馈', $record)) continue;
            Event::trigger('feedback.form.submit', $record, $form);

            if(!$form->no_error){
                JS::dialog(V('equipments:record/add.feedback_batch', [
                    'form' => $form
                ]), [
                    'width' => 150,
                    'title' => '批量反馈'
                ]);
                return ;
            }
           
			$record->status = $form['record_status'];
			$record->feedback = trim($form['feedback']);
            if (!$record->samples_lock && (isset($form['samples']) && $form['samples'] >= 0)) {
                $record->samples = (int) $form['samples'];
            }
			$record->save();
            
			Log::add(strtr('[equipments] %user_name[%user_id]通过批量反馈填写了%equipment_name[%equipment_id]仪器的使用记录[%record_id]反馈', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%record_id'=> $record->id]), 'journal');
		}
		$_SESSION['batch_feedback_select'] = [];
		Lab::message(Lab::MESSAGE_NORMAL, I18N::T('equipments', '批量反馈成功!'));
		JS::refresh();

    }

}
