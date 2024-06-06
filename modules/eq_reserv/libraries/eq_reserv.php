<?php

class EQ_Reserv
{
    const RECORD_RESERV_USED     = 0;
    const RECORD_RESERV_MISSED   = 1;
    const RECORD_RESERV_ALLOWED  = 2;
    const RECORD_RESERV_OVERTIME = 3;

    public static $record_reserv_options = [
        self::RECORD_RESERV_USED     => '正常使用',
        self::RECORD_RESERV_MISSED   => '爽约',
        self::RECORD_RESERV_ALLOWED  => '非故意爽约',
        self::RECORD_RESERV_OVERTIME => '超时',
    ];

    public static function extra_setting_breadcrumb($e, $equipment, $type)
    {
        if ($type != 'reserv') {
            return;
        }

        $e->return_value = [
            [
                'url'   => $equipment->url(),
                'title' => H($equipment->name),
            ],
            [
                'url'   => $equipment->url(null, null, null, 'edit'),
                'title' => I18N::T('eq_reserv', '设置'),
            ],
            [
                'url'   => $equipment->url('reserv', null, null, 'extra_setting'),
                'title' => I18N::T('eq_reserv', '预约表单设置'),
            ],
        ];
    }

    public static function setup_extra_setting($e, $controller, $method, $params)
    {
        if ($params[1] != 'reserv') {
            return;
        }

        Event::bind('equipment.extra_setting.content', 'EQ_Reserv::extra_setting_content');
    }

    public static function extra_setting_content($e, $equipment, $type)
    {
        if ($type != 'reserv') {
            return;
        }

        $me = L('ME');
        if (!$me->is_allowed_to('查看预约设置', $equipment)) {
            URI::redirect('error/401');
        }

        $e->return_value = (string) V('eq_reserv:extra_setting', [
            'equipment' => $equipment,
        ]);
    }

    public static function setup_index($e, $controller, $method, $params)
    {

        /**
         * 暂时拿掉仪器列表页面的仪器预约link
         * TASK#286 朱洪杰
         */
        //Event::bind('equipment.index.links', 'EQ_Reserv::equipment_links');
        /*
        NO. TASK#302(Cheng.Liu@2010.12.11)
        将负责仪器预约tab从controller中移除的修改
         */
        if ($params[0] != 'reserv' && $params[0] != 'reserv_all') {
            return;
        }
        $me = L('ME');
        if ($params[0] == 'reserv') {
            $length = Q("$me<incharge equipment")->total_count();
            if (!$length) {
                URI::redirect('error/401');
            }
            Event::bind('equipments.primary.tab', 'EQ_Reserv::reserv_primary_tab');
            Event::bind('equipments.primary.content', 'EQ_Reserv::reserv_primary_tab_content', 100, 'reserv');
        } else {
            Event::bind('equipments.primary.tab', 'EQ_Reserv::reserv_all_primary_tab');
            Event::bind('equipments.primary.content', 'EQ_Reserv::reserv_all_primary_tab_content', 100, 'reserv_all');
        }
    }

    public static function setup_profile()
    {
        Event::bind('profile.view.tab', 'EQ_Reserv::index_profile_tab');
        Event::bind('profile.view.content', 'EQ_Reserv::index_profile_content', 0, 'eq_reserv');
        Event::bind('profile.edit.tab', 'EQ_Reserv::edit_profile_tab');
        Event::bind('profile.edit.content', 'EQ_Reserv::edit_profile_content', 0, 'eq_reserv');
    }

    public static function setup_edit()
    {
        Event::bind('equipment.edit.tab', 'EQ_Reserv::edit_reserv_tab');
    }

    public static function setup_view()
    {
        $_SESSION['first_view'] = time();
        Event::bind('equipment.index.tab', 'EQ_Reserv::reserv_tab');
        Event::bind('equipment.index.tab.content', 'EQ_Reserv::reserv_tab_content', 0, 'reserv');
        Event::bind('equipment.index.tab.tool_box', 'EQ_Reserv::_tool_box_reserv', 0, 'reserv');
        Event::bind('equipment.view.dashboard.sections', 'EQ_Reserv::equipment_dashboard_sections');
    }

    public static function setup_lab()
    {
        Event::bind('lab.view.tab', 'EQ_Reserv::lab_tab');
        // Event::bind('lab.view.content', 'EQ_Reserv::lab_tab_content', 0, 'eq_reserv');
        Event::bind('lab.view.content', 'EQ_Reserv::index_lab_content', 0, 'eq_reserv');

    }

    public static function lab_tab($e, $tabs)
    {
        $lab = $tabs->lab;
        $me  = L('ME');
        if ($me->is_allowed_to('列表仪器预约', $lab)) {
            $tabs->add_tab('eq_reserv', [
                'url'   => $lab->url('eq_reserv'),
                'title' => I18N::T('eq_reserv', '仪器预约'),
            ]);
        }
    }

    public static function lab_tab_content($e, $tabs)
    {
        $lab           = $tabs->lab;
        $tabs->content = V('eq_reserv:view/section.user_reserv_profile', ['lab' => $lab]);
    }

    public static function reserv_primary_tab($e, $tabs)
    {
        $me = L('ME');
        $tabs->add_tab('reserv', [
            'url'   => URI::url('!equipments/extra/reserv'),
            'title' => I18N::T('eq_reserv', '%name负责的所有仪器预约情况', ['%name' => $me->name]),
        ]);
    }

    public static function reserv_primary_tab_content($e, $tabs)
    {
        $me         = L('ME');
        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token = Session::temp_token('eq_reserv_', 300);
            $form       = Lab::form(function (&$old_form, &$form) {
                unset($form['type']);
            });
            $form['form_token']    = $form_token;
            $_SESSION[$form_token] = $form;

            if ($form['dtstart']) {
                $start_date = getdate($form['dtstart']);
                $dtstart = mktime(0, 0, 0, $start_date['mon'], $start_date['mday'], $start_date['year']);
            } else {
                $start_date = getdate(strtotime('-1 month'));
                $dtstart = mktime(0, 0, 0, $start_date['mon'], $start_date['mday'], $start_date['year']);
            }

            if ($form['dtend']) {
                $end_date = getdate($form['dtend']);
                $dtend = mktime(23, 59, 59, $end_date['mon'], $end_date['mday'], $end_date['year']);
            } else {
                $end_date = getdate(strtotime('+1 month'));
                $dtend = mktime(23, 59, 59, $end_date['mon'], $end_date['mday'], $end_date['year']);
            }

            $pre_selector = [];

            $selector = "eq_reserv[dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]";

            if (!is_null($form['reserv_status']) && $form['reserv_status'] != -1) {
                $selector .= '[status=' . $form['reserv_status'] . ']';
            }

            if ($form['equipment']) {
                $name                      = Q::quote(trim($form['equipment']));
                $pre_selector['equipment'] = "equipment[name*={$name}]";
            }

            if ($form['equipment_ref']) {
                $equipment_ref = Q::quote(trim($form['equipment_ref']));
                if ($pre_selector['equipment']) {
                    $pre_selector['equipment'] .= "[ref_no*={$equipment_ref}]";
                } else {
                    $pre_selector['equipment'] = "equipment[ref_no*={$equipment_ref}]";
                }
            }

            if ($form['organizer']) {
                $name                      = Q::quote(trim($form['organizer']));
                $pre_selector['organizer'] = "user[name*={$name}]";
            }
        }


        $sort_by   = $form['sort'] ?: 'dtstart';
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        if (!$pre_selector['equipment'] && $sort_by == 'equipment') {
            $pre_selector['equipment'] = "equipment";
        }

        if (!$pre_selector['organizer'] && $sort_by == 'organizer') {
            $pre_selector['organizer'] = "user";
        }

        $pre_selector['incharge_equipment'] = "{$me}<incharge equipment";

        if (count($pre_selector)) {
            $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
        }

        switch ($sort_by) {
            case 'equipment':
                $selector .= ":sort(equipment.name_abbr $sort_flag)";
                break;
            case 'organizer':
                $selector .= ":sort(user.name_abbr $sort_flag)";
                break;
            case 'status':
                $selector .= ":sort($sort_by $sort_flag)";
                break;
            default:
                $selector .= ":sort(dtstart $sort_flag)";
                break;
        }

        $_SESSION[$form_token]['selector'] = $selector;

        $eq_reservs = Q($selector);

        $pagination = Lab::pagination($eq_reservs, (int) $form['st'], 15);

//        $tabs->content = V('eq_reserv:list', [
//            'eq_reservs' => $eq_reservs,
//            'form'       => $form,
//            'sort_by'    => $sort_by,
//            'sort_asc'   => $sort_asc,
//            'form_token' => $form_token,
//            'pagination' => $pagination,
//        ]);

        $me       = L('ME');
        $calendar = O('calendar', ['parent' => $me, 'type' => 'eq_incharge']);
        if (!$calendar->id) {
            $calendar->parent = $me;
            $calendar->type   = 'eq_incharge';
            $calendar->name   = I18N::T('eq_reserv', '%name的非预约时段', ['%name' => $me->name]);
            $calendar->save();
        }

        $tabs->content = V('eq_reserv:incharge/calendar', ['calendar' => $calendar]);
    }

    public static function reserv_all_primary_tab($e, $tabs)
    {
        $me = L('ME');
        $tabs->add_tab('reserv_all', [
            'url'   => URI::url('!equipments/extra/reserv_all'),
            'title' => I18N::T('eq_reserv', '所有仪器的预约情况'),
        ]);
    }

    public static function reserv_all_primary_tab_content($e, $tabs)
    {
        $me         = L('ME');
        $form_token = Input::form('form_token');
        if ($form_token && isset($_SESSION[$form_token])) {
            $form = $_SESSION[$form_token];
        } else {
            $form_token = Session::temp_token('eq_reserv_', 300);
            $form       = Lab::form(function (&$old_form, &$form) {
                unset($form['type']);
            });
            $form['form_token']    = $form_token;
            $_SESSION[$form_token] = $form;

            if ($form['dtstart']) {
                $start_date = getdate($form['dtstart']);
                $dtstart = mktime(0, 0, 0, $start_date['mon'], $start_date['mday'], $start_date['year']);
            } 

            if ($form['dtend']) {
                $end_date = getdate($form['dtend']);
                $dtend = mktime(23, 59, 59, $end_date['mon'], $end_date['mday'], $end_date['year']);
            }

            $pre_selector = new ArrayIterator;

            $selector = "eq_reserv";

             if ($dtstart && $dtend) {
                $selector = "eq_reserv[dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]";
            } elseif ($dtstart) {
                $selector = "eq_reserv[dtend>={$dtstart}]";
            } elseif ($dtend) {
                $selector = "eq_reserv[dtstart<={$dtend}]";
            }

            if (!is_null($form['reserv_status']) && $form['reserv_status'] != -1) {
                $selector .= '[status=' . $form['reserv_status'] . ']';
            }

            if ($form['equipment']) {
                $name                      = Q::quote(trim($form['equipment']));
                $pre_selector['equipment'] = "equipment[name*={$name}]";
            }

            if ($form['equipment_ref']) {
                $equipment_ref = Q::quote(trim($form['equipment_ref']));
                if ($pre_selector['equipment']) {
                    $pre_selector['equipment'] .= "[ref_no*={$equipment_ref}]";
                } else {
                    $pre_selector['equipment'] = "equipment[ref_no*={$equipment_ref}]";
                }
            }

            if ($form['organizer']) {
                $name                      = Q::quote(trim($form['organizer']));
                $pre_selector['organizer'] = "user[name*={$name}]";
            }
        }

        $sort_by   = $form['sort'] ?: 'dtstart';
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        if (!$pre_selector['equipment'] && $sort_by == 'equipment') {
            $pre_selector['equipment'] = "equipment";
        }

        if (!$pre_selector['organizer'] && $sort_by == 'organizer') {
            $pre_selector['organizer'] = "user";
        }

    	//增加下属机构范围,selector有点傻，但是时间不允许
        if($me->access('管理所有内容')){
        }elseif($me->access('添加/修改下属机构的仪器')){
            $pre_selector['me_group'] = "{$me->group} equipment ";
        }
        if ($tabs->group->id) {
            $pre_selector['me_group'] = "{$tabs->group} equipment ";
        }
        //GROUP搜索
        $group = O('tag_group', $form['group_id']);
        $group_root = Tag_Model::root('group');

        if ($group->id && $group->root->id == $group_root->id) {
            $pre_selector['group'] = "$group equipment";
        } else {
            $group = null;
        }

        //end

        $selector = Event::trigger('eq_reserv.search.filter.submit', $form, $selector, $pre_selector) ?: $selector;

        if (count($pre_selector)) {
            $selector = "( " . join(', ', (array) $pre_selector) . " ) " . $selector;
        }

        switch ($sort_by) {
            case 'equipment':
                $selector .= ":sort(equipment.name_abbr $sort_flag)";
                break;
            case 'organizer':
                $selector .= ":sort(user.name_abbr $sort_flag)";
                break;
            case 'status':
                $selector .= ":sort($sort_by $sort_flag)";
                break;
            default:
                $selector .= ":sort(dtstart $sort_flag)";
                break;
        }

        $_SESSION[$form_token]['selector'] = $selector;

        $eq_reservs = Q($selector);

        $pagination = Lab::pagination($eq_reservs, (int) $form['st'], 15);

        $tabs->content = V('eq_reserv:list', [
            'eq_reservs' => $eq_reservs,
            'group_root'=>$group_root,
            'group' => $group,
            'form'       => $form,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
            'form_token' => $form_token,
            'pagination' => $pagination,
        ]);
    }

    public static function cal_component_get_color($e, $component, $calendar)
    {
        $parent_name = $calendar->parent->name();
        $cal_type    = $calendar->type;

        $return = null;
        // 某台仪器的预约显示
        if ($parent_name == 'equipment') {
            if ($component->type == Cal_Component_Model::TYPE_VFREEBUSY) {
                $return = 7;
            } else {
                $return = (int) ($component->organizer->id % 6);
            }
        }
        // 负责仪器的预约显示
        elseif ($parent_name == 'user' && $cal_type == 'eq_incharge') {
            if ($component->type == Cal_Component_Model::TYPE_VFREEBUSY) {
                $return = 7;
            } else {
                $return = (int) ($component->calendar->parent->id % 6);
            }
        }

        $e->return_value = $return;
        return;
    }

    /**
     * @param $mode view|list 
     * view表明视图 只有dtstart和dtend两个条件
     * list表明预约记录列表 允许各种搜索条件 
     * 原先没作区分，view页用列表条件导致非预约记录无法展示
     */
    static function calendar_components_get($e, $calendar, $components, $dtstart, $dtend, $limit = 0, $form = [], $mode = 'view'){
        $me = L('ME');
        $parent = $calendar->parent;
        
        //预约时，查出当前calendar下的预约和繁忙的component
        if (!$calendar->id) {
            return;
        }

        if ($form['dtstart']) {
            $start_date = getdate($form['dtstart']);
            $dtstart    = mktime(0, 0, 0, $start_date['mon'], $start_date['mday'], $start_date['year']);
        }

        if ($form['dtend']) {
            $end_date = getdate($form['dtend']);
            $dtend    = mktime(23, 59, 59, $end_date['mon'], $end_date['mday'], $end_date['year']);
        }

        $prefix = [];
        if ($form['organizer']) {
            $name           = Q::quote(trim($form['organizer']));
            $prefix['user'] = "user<organizer[name*=$name|name_abbr*=$name]";
        }

        if ($form['lab']) {
            $name           = Q::quote(trim($form['lab']));
            $pre_selector['lab'] = "lab[name*={$name}|name_abbr*={$name}] user";
        }

        $group = O('tag_group', $form['eq_reserv_group']);
        if ($group->id && $group->root->id && $group->id != $group->root->id) {
            $prefix['eq_reserv_group'] = "$group user<organizer";
        }
        //BUG 4472 LIMS中没有课题组
        if (Module::is_installed('labs')) {
            if ($form['lab']) {
                $lab           = Q::quote(trim($form['lab']));
                $prefix['lab'] = "lab[name*=$lab|name_abbr*=$lab] user<organizer";
            }
        }

        if (!is_null($form['reserv_status']) && $form['reserv_status'] != -1) {
            $reserv_filter['status'] = $form['reserv_status'];
        }
        
        // 这代码 太久远了 不好拆了 本身就是个hook 先写在这里 大侠饶命
        if (!is_null($form['use_way']) && $form['use_way'] != -1) {
            $reserv_filter['use_way'] = $form['use_way'];
        }

        // 案例：#20191715 修复视图不显示非预约记录的bug
        if ($mode == 'list') {
            $reserv_selector = 'eq_reserv';
            
            if ($form['id']) {
                $reserv_selector .= "[id*={$form['id']}]";
            }
            
            if ($reserv_filter) {
                foreach ($reserv_filter as $key => $value) {
                    $reserv_selector .= "[$key=$value]";
                }
            }
            
            $prefix['reserv'] = $reserv_selector .= '<component';
        }

        $vevent = Cal_Component_Model::TYPE_VFREEBUSY;
        if ($parent->name() == 'equipment') {
            $calendar_ids                = Q("$parent<incharge user<parent calendar[type=eq_incharge]")->to_assoc('id', 'id');
            $calendar_ids[$calendar->id] = $calendar->id;
            $cids                        = implode(',', $calendar_ids);

            $selector = "cal_component[calendar_id=$cids][type=$vevent|calendar_id={$calendar->id}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]";
            if ($dtend == 0) {
                $selector = "cal_component[calendar_id=$cids][type=$vevent|calendar_id={$calendar->id}][dtstart~dtend={$dtstart}|dtstart>={$dtstart}]";
            }

            $sort_by   = $form['sort'] ?: 'dtstart';
            $sort_asc  = $form['sort_asc'];
            $sort_flag = $sort_asc ? 'A' : 'D';

            if (!$prefix['user'] && $sort_by == "organizer") {
                $prefix['user'] = "user<organizer";
            }

            if (!$prefix['reserv'] && $sort_by == "status") {
                $prefix['reserv'] = "eq_reserv<component";
            }

            if (count($prefix)) {
                $selector = '(' . implode(', ', $prefix) . ') ' . $selector;
            }

            switch ($sort_by) {
                case 'organizer':
                    $selector .= ":sort(user.name_abbr $sort_flag)";
                    break;
                case 'status':
                    $selector .= ":sort(eq_reserv.status $sort_flag)";
                    break;
                default:
                    $selector .= ":sort(dtstart $sort_flag)";
                    break;
            }

            $_SESSION['reserv_export_' . $me->id . '_' . $calendar->id] = $selector;
            $e->return_value                                            = Q($selector);
            //$components = Q($selector);
            //if ($limit > 0) $components = $components->limit($limit);
            //$e->return_value = $components;
        }
        //查看仪器情况时，查出当前用户所负责的所有仪器的预约情况和他的繁忙状态
        elseif ($calendar->type == 'eq_incharge' && $parent->name() == 'user') {
            $equipment_selector = 'equipment';
            if ($form['equipment_ref']) {
                $equipment_ref = Q::quote(trim($form['equipment_ref']));
                $equipment_selector = "equipment[ref_no*={$equipment_ref}]";
            }

            $normal_event        = Cal_Component_Model::TYPE_VEVENT;
            $calendar_ids        = Q("$parent<incharge {$equipment_selector}<parent calendar")->to_assoc('id', 'id');
            $ccid                = $calendar->id;
            $calendar_ids[$ccid] = $ccid;
            $cids                = implode(',', $calendar_ids);

            $selector = "cal_component[calendar_id=$cids][type=$normal_event|calendar_id=$ccid][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]";
            if ($dtend == 0) {
                $selector = "cal_component[calendar_id=$cids][type=$vevent|calendar_id={$calendar->id}][dtstart~dtend={$dtstart}|dtstart>={$dtstart}]";
            }
            if (count($prefix)) {
                $selector = '(' . implode(', ', $prefix) . ') ' . $selector;
            }

            $prefix    = [];
            $sort_by   = $form['sort'] ?: 'dtstart';
            $sort_asc  = $form['sort_asc'];
            $sort_flag = $sort_asc ? 'A' : 'D';

            if (!$prefix['user'] && $sort_by == "organizer") {
                $prefix['user'] = "user<organizer";
            }
            if (!$prefix['reserv'] && $sort_by == "status") {
                $prefix['reserv'] = "eq_reserv<component";
            }
            if (!$prefix['equipment'] && $sort_by == "equipment") {
                $prefix['reserv'] = "equipment<parent calendar";
            }

            if (count($prefix)) {
                $selector = '(' . implode(', ', $prefix) . ') ' . $selector;
            }

            switch ($sort_by) {
                case 'organizer':
                    $selector .= ":sort(user.name_abbr $sort_flag)";
                    break;
                case 'status':
                    $selector .= ":sort(eq_reserv.status $sort_flag)";
                    break;
                case 'equipment':
                    $selector .= ":sort(equipment.name_abbr $sort_flag)";
                    break;
                default:
                    $selector .= ":sort(dtstart A)";
                    break;
            }

            $_SESSION['reserv_export_' . $me->id . '_' . $calendar->id] = $selector;
            $e->return_value                                            = Q($selector);

            $param = [
                'is_offset' => true,
                'top_input_arr' => ['organizer', 'equipment_ref'],
                'columns' => $calendar->list_columns($form),
            ];
            $calendar->search_box = V('application:search_box', $param);
        }
    }
    public static function component_content_render($e, $component, $current_calendar = null, $mode = 'week')
    {
        $calendar = $component->calendar;
        $parent   = $calendar->parent;
        if ($calendar->id &&
            $parent->name() == 'equipment'
            || ($calendar->type == 'eq_incharge' && $parent->name() == 'user')) {
            $e->return_value = V('eq_reserv:view/calendar/component_content', ['component' => $component, 'current_calendar' => $current_calendar, 'mode' => $mode]);
        }
    }

    public static function component_list_render($e, $component)
    {
        $calendar = $component->calendar;
        $parent   = $calendar->parent;
        if ($calendar->id &&
            $parent->name() == 'equipment'
            || ($calendar->type == 'eq_incharge' && $parent->name() == 'user')) {
            $e->return_value = V('eq_reserv:view/calendar/component_list', ['component' => $component]);
        }
    }

    public static function search_filter_view($e, $form, $search_filters)
    {
        $search_filters[] = V('eq_reserv:view/section.filter', ['filter_selected' => $form['is_reserv']]);
    }

    public static function index_profile_tab($e, $tabs)
    {
        $user = $tabs->user;
        $me   = L('ME');

        if (($user->id == $me->id)
            || (($me->access('查看本实验室成员的预约情况') && Q("($me , $user) lab")->total_count()))
            || (($me->access('查看负责实验室成员的预约情况') && Q("($me<pi , $user) lab")->total_count()))
            || ($me->access('管理所有内容'))
            || $me->group->id && $me->group->is_itself_or_ancestor_of($user->group) && $me->access('查看下属机构成员的预约情况')
        ) {
            $tab_data = [
                'url'   => $user->url('eq_reserv'),
                'title' => I18N::T('eq_reserv', '仪器预约'),
            ];

            $dtstart = time();
            $dtnext = $dtstart + 604800;
            $type = Cal_Component_Model::TYPE_VEVENT;
            // if (Module::is_installed('db_sync') && DB_SYNC::is_slave()) {
                // $components = Q("equipment[site=" . LAB_ID . "]<parent calendar[parent_name=equipment] cal_component[type=$type][organizer=$user][dtstart~dtend={$dtstart}|dtstart~dtend={$dtnext}|dtstart={$dtstart}~{$dtnext}]:sort(dtstart)");
            // }else{
                $components = Q("calendar[parent_name=equipment] cal_component[type=$type][organizer=$user][dtstart~dtend={$dtstart}|dtstart~dtend={$dtnext}|dtstart={$dtstart}~{$dtnext}]:sort(dtstart)");
            // }

            $components_total_count = $components->total_count();
            if ($components_total_count > 0) {
                $tab_data['number'] = $components_total_count;
            }

            $tabs->add_tab('eq_reserv', $tab_data);
        }
    }
    public static function index_lab_content($e,$tabs){
        $form = Lab::form();
        $lab = $tabs->lab;
        $eq_late_count = 0;
        $eq_leave_early_count = 0;
        $eq_overtime_count = 0;
        $eq_miss_count = 0;
        
        $users_v = Q("$lab user user_violation");
        foreach($users_v as $v){
            $eq_late_count += $v->eq_late_count;
            $eq_leave_early_count += $v->eq_leave_early_count;
            $eq_overtime_count += $v->eq_overtime_count;
            $eq_miss_count += $v->eq_miss_count;
        }
        $count_map = [
            'eq_late_count'=>$eq_late_count,
            'eq_leave_early_count'=>$eq_leave_early_count,
            'eq_overtime_count'=>$eq_overtime_count,
            'eq_miss_count'=>$eq_miss_count
        ];
       
        $type = Cal_Component_Model::TYPE_VEVENT;
        $pre_selectors = new ArrayIterator;
        // $pre_selectors['calendar'] = 'calendar[parent_name=equipment]';
        $pre_selectors['lab'] = "$lab ";
        $selector = " cal_component[type=$type]";

        if ($form['dtstart']) {
            $dtstart = $form['dtstart'];
            $selector .= "[dtstart>={$dtstart}]";
        }

        if ($form['dtend']) {
            $dtend = $form['dtend'];
            $dtend += 86400;
            $selector .= "[dtend<={$dtend}]";
        }

        if ($form['organizer']) {
            $organizer = $form['organizer'];
            $pre_selectors['lab'] .= "user[name*={$organizer}]<organizer ";
        }else{
            $pre_selectors['lab'] .= "user<organizer ";
        }

        //equipment[name*={$name}] eq_reserv[status={$form['reserv_status']}]<component
        if ($form['equipment'] || (!is_null($form['reserv_status']) && $form['reserv_status'] != -1)) {
            $reserv_selector = 'eq_reserv';
            if (!is_null($form['reserv_status']) && $form['reserv_status'] != -1) {
                $reserv_selector .= "[status={$form['reserv_status']}]";
            }
            if ($form['equipment']) {
                $name = Q::quote($form['equipment']);
                $reserv_selector = "equipment[name*={$name}] " . $reserv_selector;
            }
            $reserv_selector .= "<component";
            $pre_selectors['reserv'] = $reserv_selector;
        }

        if ($form['equipment_ref']) {
            $equipment_ref = Q::quote(trim($form['equipment_ref']));
            if ($pre_selectors['equipment']) {
                $pre_selectors['equipment'] .= "[ref_no*={$equipment_ref}]";
            } else {
                $pre_selectors['equipment'] = "equipment[ref_no*={$equipment_ref}]";
            }
        }

        $selector .= ":sort(dtstart D)";
        if (count($pre_selectors) > 0) {
            $selector = '('.implode(', ', (array) $pre_selectors).') ' . $selector;
        }

        //(lab#1 user<organizer, equipment[name*="计算器"] eq_reserv<component)  cal_component[type=0]:sort(dtstart D)
        $components = Q($selector);
        
        
        //分页查找
        $start = (int) $form['st'];
        $per_page = 15;
        $start = $start - ($start % $per_page);
        $pagination = Lab::pagination($components, $start, $per_page);

        $tabs->content = V('eq_reserv:view/section.lab_reserv_profile', [
            // 'user' => $user,
            // 'user_v' => $user_v,
            'count_map'=>$count_map,
            'lab' => $lab,
            'components' => $components,
            'pagination' => $pagination,
            'form'=>$form,
        ]);
    }
    public static function index_profile_content($e, $tabs)
    {
        $form = Lab::form();

        $user = $tabs->user;
        $user_v = O('user_violation', ['user' => $user]);
        $type = Cal_Component_Model::TYPE_VEVENT;
        $pre_selectors = new ArrayIterator;
        $pre_selectors['calendar'] = 'calendar[parent_name=equipment]';

        $selector = "cal_component[type=$type][organizer=$user]";

        if ($form['dtstart']) {
            $dtstart = $form['dtstart'];
            $selector .= "[dtstart>={$dtstart}]";
        }

        if ($form['dtend'] == 'on') {
            $dtend = $form['dtend'];
            $selector .= "[dtend<={$dtend}]";
        }

        if ($form['equipment'] || (!is_null($form['reserv_status']) && $form['reserv_status'] != -1)) {
            $reserv_selector = 'eq_reserv';
            if (!is_null($form['reserv_status']) && $form['reserv_status'] != -1) {
                $reserv_selector .= "[status={$form['reserv_status']}]";
            }
            if ($form['equipment']) {
                $name = Q::quote($form['equipment']);
                $reserv_selector = "equipment[name*={$name}] " . $reserv_selector;
            }
            $reserv_selector .= "<component";
            $pre_selectors['reserv'] = $reserv_selector;
        }

        $selector .= ":sort(dtstart D)";
        if (count($pre_selectors) > 0) {
            $selector = '('.implode(', ', (array) $pre_selectors).') ' . $selector;
        }
        $components = Q($selector);
        //分页查找
        $start = (int) $form['st'];
        $per_page = 15;
        $start = $start - ($start % $per_page);
        $pagination = Lab::pagination($components, $start, $per_page);

        $tabs->content = V('eq_reserv:view/section.user_reserv', [
            'user' => $user,
            'user_v' => $user_v,
            'components' => $components,
            'pagination' => $pagination,
            'form'=>$form,
        ]);
    }

    public static function edit_profile_tab($e, $tabs)
    {
        $user = $tabs->user;
        if (L('ME')->is_allowed_to('修改预约违规次数', $user)) {
            $tabs
                ->add_tab('eq_reserv', [
                    'url'   => $user->url('eq_reserv', null, null, 'edit'),
                    'title' => I18N::T('eq_reserv', '仪器预约'),
                ]);
        }
    }

    public static function edit_profile_content($e, $tabs)
    {
        $user   = $tabs->user;
        $user_v = O('user_violation', ['user' => $user]);
        if (Input::form('submit')) {
            $form = Form::filter(Input::form())
                ->validate('eq_miss_count', 'number(>=0)', I18N::T('eq_reserv', '请输入大于或等于零的值!'))
            //->validate('overtime_duration', 'number(>=0)', I18N::T('eq_reserv', '请输入大于零的值'))
                ->validate('eq_leave_early_count', 'number(>=0)', I18N::T('eq_reserv', '请输入大于或等于零的值!'))
                ->validate('eq_overtime_count', 'number(>=0)', I18N::T('eq_reserv', '请输入大于或等于零的值!'))
                ->validate('eq_late_count', 'number(>=0)', I18N::T('eq_reserv', '请输入大于或等于零的值!'))
                ->validate('eq_violate_count', 'number(>=0)', I18N::T('eq_reserv', '请输入大于或等于零的值!'));
            if ($form->no_error) {
                $user_v->user                 = $user;
                $user_v->eq_miss_count        = $form['eq_miss_count'];
                $user_v->eq_leave_early_count = $form['eq_leave_early_count'];
                $user_v->eq_overtime_count    = $form['eq_overtime_count'];
                $user_v->eq_late_count        = $form['eq_late_count'];
                $user_v->eq_violate_count = $form['eq_violate_count'];
                if ($user_v->save()) {
                    /* 记录日志 */
                    Log::add(strtr('[eq_reserv] %operator_name[%operator_id]修改了用户%user_name[%user_id]的预约信息', [
                        '%operator_name' => L('ME')->name,
                        '%operator_id'   => L('ME')->id,
                        '%user_name'     => $user->name,
                        '%user_id'       => $user->id]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_reserv', '信息修改成功'));
                }
            }
        }
        $tabs->content = V('eq_reserv:view/tab.profile.reserv', ['user' => $user, 'user_v' => $user_v, 'form' => $form]);

    }

    public static function on_banned_deleted($e, $banned)
    {
        //清空用户的黑名单相关记录
        $user_v = O('user_violation', ['user' => $banned->user]);
        if ($user_v->id) {
            $user_v->eq_miss_count = 0;
            $user_v->eq_leave_early_count = 0;
            $user_v->eq_late_count = 0;
            $user_v->eq_overtime_count = 0;
            $user_v->eq_violate_count = 0;
            $user_v->save();
        }
    }
    /**
     * 暂时拿掉仪器列表页面的仪器预约link
     * TASK#286 朱洪杰
     */
    /*
    static function equipment_links($e, $equipment, $links, $mode='index') {
    $me = L('ME');

    Lab::enable_message(FALSE);

    if ($equipment->accept_reserv) {
    switch ($mode) {
    case 'index':
    default:
    $links['equipment.reserv'] = array(
    'url'=>$equipment->url('reserv'),
    'tip'=>I18N::T('eq_reserv', '预约'),
    'extra'=>'class="blue"',
    'weight'=>-1,
    );
    }
    }

    Lab::enable_message(TRUE);
    }
     */
    public static function equipment_dashboard_sections($e, $equipment, $sections)
    {
        if ($equipment->accept_reserv && $equipment->status != EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            $sections[] = V('eq_reserv:view/section.equipment_reserv_setting')
                ->set('equipment', $equipment);
            $sections[] = V('eq_reserv:view/section.current_user')
                ->set('equipment', $equipment);
        }
    }

    public static function prerender_component($e, $view)
    {
        $parent = $view->component->calendar->parent;
        $me     = L('ME');

        if ($parent->name() == 'equipment' ||
            ($parent->name() == 'user' &&
                $view->component->calendar->type == 'eq_incharge')) {
            $form = $view->component_form;

            $form['#categories'] = [];

            $form['name']['default_value']               = I18N::T('eq_reserv', '仪器使用预约');
            $form['#categories']['reserv_info']['title'] = I18N::T('eq_reserv', '预约信息');

            $form['#categories']['reserv_info']['items'][] = 'name';

            if ($parent->name() == 'user' && $view->component->calendar->type == 'eq_incharge') {
                $form['name']['default_value'] = I18N::T('eq_reserv', '非预约时段');
            }

            if ($parent->name() == 'equipment') {
                $form['equipment_name'] = [
                    'label'         => I18N::T('eq_reserv', '仪器名称'),
                    'default_value' => H($parent->name),
                    'path'          => [
                        'info' => 'eq_reserv:view/calendar/',
                    ],
                    'weight'        => 20,
                ];

                $form['#categories']['reserv_info']['items'][] = 'equipment_name';
                if (Module::is_installed('labs')) {
                    $form['project_name'] = [
                        'label'  => I18N::T('eq_reserv', '关联项目'),
                        'path'   => [
                            'info' => 'eq_reserv:view/calendar/',
                        ],
                        'weight' => 30,
                    ];
                    $form['#categories']['reserv_info']['items'][] = 'project_name';
                }
            }

            $form['description']['path']['form'] = 'eq_reserv:view/calendar/';

            if (Module::is_installed('labs')) {
                $multi_lab = $GLOBALS['preload']['people.multi_lab'];
                if ($multi_lab) {
                    $form['project_lab'] = [
                        'path'      => ['form' => 'eq_reserv:view/calendar/'],
                        'component' => $view->component,
                        'weight'    => 30,
                    ];
                }
                $form['project'] = [
                    'path'      => ['form' => 'eq_reserv:view/calendar/'],
                    'component' => $view->component,
                ];
            }

            /*
            guoping.zhang@2010.11.29
             */

            $is_admin = $me->is_allowed_to('修改预约', $parent);
            if ($is_admin) {
                $form['type'] = [
                    'label'         => I18N::T('calendars', '类型'),
                    'default_value' => [
                        'value' => Cal_Component_Model::TYPE_VEVENT,
                        'types' => [
                            Cal_Component_Model::TYPE_VEVENT    => I18N::T('eq_reserv', '预约'),
                            Cal_Component_Model::TYPE_VFREEBUSY => I18N::T('eq_reserv', '非预约时段'),
                        ],
                    ],
                    'weight'        => 11,
                ];
                $form['organizer']['label'] = I18N::T('calendars', '预约者');

                $form['#categories']['reserv_info']['items'][] = 'type';
                $form['#categories']['reserv_info']['items'][] = 'organizer';
            } else {
                unset($form['organizer']);
            }

            if ($parent->name() == 'equipment' && Config::get('eq_reserv.use_eq_captcha', FALSE)) {
                $form['captcha'] = [
                    'label' => I18N::T('eq_reserv', '验证码'),
                    'path' => [
                        'form' => 'eq_reserv:view/calendar/'
                    ],
                    'weight' => 500
                ];
                $form['#categories']['reserv_info']['items'][] = 'captcha';
            }
            
            //guoping.zhang@2011.01.15
            #ifdef(calendars.enable_repeat_event)
            if ($GLOBALS['preload']['calendars.enable_repeat_event']) {
                /* cal_rrule (xiaopei.li@2010.11.24)*/
                if ($me->is_allowed_to('添加重复规则', $view->component->calendar)) {
                    if ($view->component->id) {
                        $type = $view->component->type;
                        if ($type == Cal_Component_Model::TYPE_VEVENT) {
                            $label = I18N::T('eq_reserv', '预约');
                        } elseif ($type == Cal_Component_Model::TYPE_VFREEBUSY) {
                            $label = I18N::T('eq_reserv', '非预约时段');
                        }
                        if ($label) {
                            $form['rrule'] = [
                                'label'  => $label,
                                'weight' => 1,
                            ];
                        }
                    }
                }
            }
            #endif

            $form['#categories']['reserv_info']['items'][] = 'project';

            $form['#categories']['reserv_info']['items'][]               = 'dtstart';
            $form['#categories']['reserv_info']['items'][]               = 'dtend';
            $multi_lab && $form['#categories']['reserv_info']['items'][] = 'project_lab';

            $new_form = Event::trigger('eq_reserv.prerender.component', $view, $form);

            $form = count((array) $new_form) ? $new_form : $form;

            $form['#categories']['reserv_info']['items'][] = 'description';

            uasort($form, 'Cal_Component_Model::cmp');

            if (!$is_admin && !$view->component->id && $parent->require_training && !$parent->reserv_require_training) {
                $training = O('ue_training', ['equipment' => $parent, 'user' => L('ME'), 'status' => UE_Training_Model::STATUS_APPROVED]);
                if (!$training->id) {
                    $view->tip .= I18N::T('eq_reserv', '您目前似乎还未通过培训, 即便预约也无法使用仪器.\n如确实需要预约, 请注明您的预约原因.\n');
                }
            }

            if ($parent->status == EQ_Status_Model::OUT_OF_SERVICE) {
                $view->form->set_error('*', I18N::T('eq_reserv', '目前仪器暂时故障，您预约的时段可能无法正常使用，但仍然会产生计费。请您谨慎操作！'));
            }

            if ($view->component->id) {
                $object = O('eq_reserv', ['component' => $view->component]);
            }

            if ($view->component->id && $me->id != $view->component->organizer->id
                && Config::get('eq_reserv.delete_edit_remark')) {
                $form['edit_remark'] = [
                    'label'     => I18N::T('eq_reserv', '编辑说明'),
                    'path'      => ['form' => 'eq_reserv:view/calendar/'],
                    'component' => $view->component,
                    'weight'    => 100,
                ];
                $form['#categories']['reserv_info']['items'][] = 'edit_remark';
            }

            $form['extra'] = [
                'path'      => ['form' => 'eq_reserv:extra/display'],
                'component' => $view->component,
                'form'      => $view->form,
                'extra'     => Extra_Model::fetch($parent, 'eq_reserv'),
                'values'    => $object->id ? O('extra_value', ['object' => $object])->values : [],
            ];

            $form['#categories']['reserv_info']['items'][] = 'extra';

            $view->component_form = $form;
        }
        //当calendar的parent为incharge时
        elseif ($parent instanceof User_Model && $view->component->calendar->type == 'eq_incharge') {
            $form = $view->component_form;
            unset($form['organizer']);
            unset($form['name']);

            //guoping.zhang@2011.01.15
            #ifdef(calendars.enable_repeat_event)
            if ($GLOBALS['preload']['calendars.enable_repeat_event']) {
                /* cal_rrule (xiaopei.li@2010.11.24)*/
                if ($me->is_allowed_to('添加重复规则', $view->component->calendar)) {
                    if ($view->component->id) {
                        $form['rrule'] = [
                            'label'  => I18N::T('eq_reserv', '非预约时段'),
                            'weight' => 1,
                        ];
                    }
                }
            }
            #endif

            uasort($form, 'Cal_Component_Model::cmp');
            $view->component_form = $form;
            $view->tip .= I18N::T('eq_reserv', '* 设置您负责的所有仪器的非预约时段\n');
        }
    }

    public static function reserv_tab($e, $tabs)
    {
        // 设备的 预约 tab
        $equipment = $tabs->equipment;
        Lab::enable_message(false);

        if ($equipment->accept_reserv && $equipment->status != EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            $tabs
                ->add_tab('reserv', [
                    'url'=>Event::trigger('db_sync.transfer_to_master_url', "!equipments/equipment/index.{$equipment->id}.reserv") ?: $equipment->url('reserv'),
                    'title'=>I18N::T('eq_reserv', '使用预约'),
                    'weight' => 10
                ]);
        }
        Lab::enable_message(true);
    }

    public static function get_reserv_columns()
    {
        $columns = new ArrayIterator;

        $columns['organizer'] = [
            'align'  => 'left',
            'title'  => I18N::HT('calendars', '组织者'),
            'nowrap' => true,
        ];

        $columns['name'] = [
            'align'  => 'left',
            'title'  => I18N::HT('calendars', '主题'),
            'nowrap' => true,
        ];
        $columns['date'] = [
            'align'  => 'left',
            'title'  => I18N::HT('calendars', '时间'),
            'nowrap' => true,
            'filter' => [
                'form'  => V('calendar_table/filters/date'),

                'value' => $form['date_check'] ? Date::format($form['date'], T('Y/m/d')) : null,
                'field' => 'date,date_check',
            ],
        ];

        return (array) $columns;
    }

    /* public static function _reserv_tab_content_list($e, $tabs)
    {
    $form=$tabs->form;
    $tabs->content = V('eq_reserv:calendar/list', $form);

    Controller::$CURRENT->add_css('preview');
    Controller::$CURRENT->add_js('preview');
    }
     */

    public static function _reserv_tab_content_calendar($e, $tabs)
    {
        $form          = $tabs->form;
        $tabs->content = V('eq_reserv:calendar/calendar', $form);
        Controller::$CURRENT->add_css('preview');
        Controller::$CURRENT->add_js('preview');
    }

    ///使用预约的tabcontent
    public static function reserv_tab_content($e, $tabs)
    {
        $me = L('ME');
        // Event::bind('eq_reserv.fourth_tabs.content', 'Calendar::_index_list', 0, 'list');//list剔除之前
        Event::bind('eq_reserv.fourth_tabs.content', 'Eq_Reserv::_reserv_tab_content_list', 10, 'list'); //list剔除之后
        Event::bind('eq_reserv.fourth_tabs.content', 'Eq_Reserv::_reserv_tab_content_calendar', 0, 'calendar');
        Event::Trigger('reserv.tab.content.validate', $equipment);
        if (!$me->id) {
            URI::rdirect('error/401');
        }
        $tabs->content = V('eq_reserv:view/tab.reserv');
        $equipment     = $tabs->equipment;
        $calendar      = O('calendar', ['parent' => $equipment, 'type' => 'eq_reserv']);
        if (!$calendar->id) {
            $calendar         = O('calendar');
            $calendar->parent = $equipment;
            $calendar->type   = 'eq_reserv';
            $calendar->name   = I18N::T('eq_reserv', '%equipment的预约', ['%equipment' => $equipment->name]);
            $calendar->save();
        }
        $tabs->calendar = $calendar;

        // $now = time();
        // $dtstart = Input::form('st') ?: $now;
        // $date=getdate($dtstart);
        // $dtstart = mktime(0, 0, 0, $date['mon'], $date['mday']-$date['wday'], $date['year']);
        // $dtend = $dtstart + 604800;
        $browser_id = 'browser_' . uniqid();

        $form = Lab::form(function (&$old_form, &$form) {});
        $form_token = Session::temp_token('eq_reserv_', 300);
        $form += [
            'browser_id'   => $browser_id,
            'calendar_id'  => $calendar->id,
            'equipment_id' => $equipment->id,
            'equipment'    => $equipment,
            'calendar'     => $calendar,
            'form_token'   => $form_token,
            'hidden_tabs'  => true,
            'dtstart'      => $dtstart,
            //  'st'=>$dtstart,
            'ed'           => $dtend,
        ];

        $params = Config::get('system.controller_params', $params);

        // 再向reserv-server进行请求 获取对应的token
        Event::Trigger('reserv.tab.content.validate', $tabs->equipment);
        if (!$me->id) URI::redirect('error/401');
        $tabs->content = V('eq_reserv:view/tab.reserv');

        $tabs->content->fourth_tabs = Widget::factory('tabs')
            ->set('class', 'fourth_tabs float_left')->set('form', $form)
            ->add_tab('calendar', [
                'url'    => $equipment->url('reserv.calendar'),
                'title'  => I18N::T('eq_reserv', '日历'),
                'weight' => 20,
            ])
            ->add_tab('list', [
                'url'    => $equipment->url('reserv.list'),
                'title'  => I18N::T('eq_reserv', '列表'),
                'weight' => 30,
            ])
            ->tab_event('calendar.secondary_tabs.tab')
            ->set('equipment', $equipment)
            ->set('status', $params[3])
            ->set('calendar', $calendar)
            ->content_event('eq_reserv.fourth_tabs.content')
            ->tool_event('equipment.index.tab.tool_box')
            ->select($params[2]);
    }

    public static function _tool_box_reserv_panel_buttons($calendar, $form){
        $me            = L('ME');
        $panel_buttons = [];
        if ($calendar->parent->name() == 'equipment') {
            $equipment = $calendar->parent;
        }
        if ($me->is_allowed_to('添加事件', $calendar) && !$equipment->accept_block_time) {
            $panel_buttons[] = [
                'tip'   => I18N::HT(
                    'eq_reserve',
                    '添加使用预约'
                ),
                'extra' => 'q-object="just_show_insert_component" q-event="click" q-src="' . H(URI::url('!calendars/calendar')) .
                    '" q-static="' . H(['id' => $calendar->id]) .
                    '" class="button_add eq_reserv_button"',
                'text' => I18N::HT('eq_reserve','添加预约'),
            ];
        }
        $panel_buttons[] = [
            'tip'   => I18N::T('eq_reserve', '预约资格自检'),
            'extra' => 'q-object="permission_check" q-event="click"' .
                ' q-src="' . H(URI::url('!calendars/calendar')) . '"' .
                ' q-static="' . H(['id' => $calendar->id]) . '"' .
                ' class="middle button button_precheck prevent_default"',
            'text' => I18N::HT('eq_reserve','预约资格自检'),
        ];
        // 这里无法取到 dtstart | dtend 的值
        $panel_buttons[] = [
            'tip'   => I18N::T('eq_reserve', '导出Excel'),
            'extra' => 'q-object="export_components" q-event="click" q-src="' . H(URI::url('!eq_reserv/')) .
                '" q-static="' . H(['type' => 'csv', 'dtstart' => $dtstart, 'dtend' => $dtend, 'form_token' => $form['form_token'], 'calendar_id' => $calendar->id]) .
                '" class="eq_reserv_button button_save reserv_calendar_left_nav_anchor prevent_default"',
            'text' => I18N::HT('eq_reserve','导出'),
        ];

        $panel_buttons[] = [
            'tip'   => I18N::T('eq_reserve', '打印'),
            'extra' => '" class="button button_print middle reserv_calendar_left_nav_anchor prevent_default"',
            'extra' => 'q-object="export_components" q-event="click" q-src="' . H(URI::url('!eq_reserv/')) .
                '" q-static="' . H(['type' => 'print', 'dtstart' => $dtstart, 'dtend' => $dtend, 'form_token' => $form['form_token'], 'calendar_id' => $calendar->id]) .
                '" class="eq_reserv_button button_print reserv_calendar_left_nav_anchor prevent_default"',
            'text' => I18N::HT('eq_reserve','打印'),
        ];

        return $panel_buttons;

    }

    public static function _tool_box_reserv($e, $tabs)
    {
        $calendar      = $tabs->content->fourth_tabs->calendar;
        $form          = $tabs->content->fourth_tabs->form;

        $panel_buttons = self::_tool_box_reserv_panel_buttons($calendar, $form);

        if ($tabs->content->fourth_tabs->selected == 'list') {
            $columns = $calendar->list_columns($form);
            $tabs->content->fourth_tabs->set('columns', $columns);
            $tabs->content->fourth_tabs->search_box = V('application:search_box', ['top_input_arr' => [' group', 'organizer'], 'columns' => $columns]);
            // $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => [' group', 'organizer'], 'columns' => $columns]);
        } else {
            // $tabs->panel_buttons = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);
        }
    }
    public static function get_calendar_left_content($e, $calendar)
    {
        if ($calendar->id && (($calendar->parent_name == 'equipment' && $calendar->type == 'eq_reserv') ||
                ($calendar->parent_name == 'user' && $calendar->type == 'schedule'))) {
            $e->return_value = (string) V('eq_reserv:calendar/mode_left_content');
            return false;
        }
    }

    public static function get_calendar_right_content($e, $calendar, $form)
    {
        //获取到操作钮
        $panel_buttons = self::_tool_box_reserv_panel_buttons($calendar, $form);
        $e->return_value = V('application:panel_buttons', ['panel_buttons' => $panel_buttons]);
        return false;
    }

    public static function edit_reserv_tab($e, $tabs)
    {
        $equipment = $tabs->equipment;
        $me        = L('ME');
        if ($me->is_allowed_to('查看预约设置', $equipment)) {
            $tabs
                ->add_tab('reserv', [
                    'url'    => URI::url('!equipments/equipment/edit.' . $equipment->id . '.reserv'),
                    'title'  => I18N::T('eq_reserv', '预约设置'),
                    'weight' => 40,
                ]);
            Event::bind('equipment.edit.content', 'EQ_Reserv::edit_reserv_content', 0, 'reserv');
        }
    }

    /*
     * @Date:2018-10-10 15:43:51
     * @Author: LiuHongbo
     * @Email: hongbo.liu@geneegroup.com
     * @Description:此处为使用预约表格，将其从calendar中分开，改为同步加载方式
     */
    public static function _reserv_tab_content_list($e, $tabs)
    {

        $calendar = $tabs->calendar;

        $form     = $tabs->form;
        $sort_asc = $form['sort_asc'];
        $sort_by  = $form['sort'];
        $dtstart  = $form['dtstart'];
        $dtend    = $form['dtend'];

        /*
         * @Date:2018-10-11 13:01:40
         * @Author: LiuHongbo
         * @Email: hongbo.liu@geneegroup.com
         * @Description:表格改为分页显示模式，此处有关时间的代码应该可以不要了
         */
        //    $dtstart = $form['date'] ? : Date::time();
        /*   $date = getdate($dtstart);
        $fday = $date['mday'];
        $dtstart = mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']);
        $dtend = mktime(0, 0, 0, $date['mon'], $fday + 7, $date['year']) - 1;
        $dtprev = mktime(0, 0, 0, $date['mon'], $fday - 7, $date['year']);
        $dtnext = mktime(0, 0, 0, $date['mon'], $fday + 7, $date['year']);
        $dtyes = mktime(0, 0, 0, $date['mon'], $fday - 1, $date['year']);
        $dttom = mktime(0, 0, 0, $date['mon'], $fday + 1, $date['year']);

        $stamp_of_now = getdate(time());
        $now = mktime(0, 0, 0, $stamp_of_now['mon'], $stamp_of_now['mday'], $stamp_of_now['year']);
         */
        //hook here
        if ($calendar->id) {
            $components = Q("cal_component[calendar=$calendar][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart D)");
        } else {
            $components = Q("cal_component:empty");
        }
        $form           = new ArrayIterator($form);
        $new_components = Event::trigger('calendar.components.get', $calendar, $components, $dtstart, $dtend, 0, $form, 'list');
        $form           = (array) $form;
        if ($new_components) {
            $components = $new_components;
        }

        $form_token            = $form['form_token'] ?: Session::temp_token();
        $_SESSION[$form_token] = $form;

        $view = V('eq_reserv:calendar/list', [
            'form'       => $form,
            'form_token' => $form_token,
            'sort_asc'   => $sort_asc,
            'sort_by'    => $sort_by,
        ]);

        // $components= !$sort_by ? Calendar::sort_components($components, $sort_asc) : $components;

        $pagination       = Lab::pagination($components, (int) $form['st'], 20);
        $view->components = $components;

        $view->pagination = $pagination;
        $tabs->content    = $view;
    }

    public static function edit_reserv_content($e, $tabs)
    {
        $equipment  = $tabs->equipment;
        $properties = Properties::factory($equipment);
        $me         = L('ME');
        $form       = Form::filter(Input::form());

        //TODO: 设备预约相关设置提交保存
        if ($form['submit']) {

            if (!L('ME')->is_allowed_to('修改预约设置', $equipment)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '设备预约设置更新失败!'));
                URI::redirect();
            }

            $accept_reserv = (int) ($form['accept_reserv'] == 'on');

            if ($equipment->accept_reserv != $accept_reserv){
                Event::trigger('equipment.accept_reserv.change', $equipment, $accept_reserv);
            }
            $equipment->accept_reserv = $accept_reserv;

            $equipment->accept_reserv_manually = true;
            Event::trigger('equipment[edit].reserv.post_submit', $form, $equipment);

            $equipment->save();

            if (!isset($form['reserv_type'])) {
                $form['reserv_type'] = 'time';
            }

            switch ($form['reserv_type']) {

                case 'time': //时间预约, 默认预约规则

                    if ($form['accept_block_time']) {
                        if ($form['interval_time'] <= 0) {
                            $form->set_error('interval_time', I18N::T('eq_reserv', '块状预约长度对齐时间必须大于0!'));
                        }

                        if ($form['align_time'] <= 0) {
                            $form->set_error('align_time', I18N::T('eq_reserv', '块状预约起始对齐时间必须大于0!'));
                        }

                        // var_dump($form['block_time']);exit();
                        if (count($form['block_time'])) {
                            foreach ($form['block_time'] as $key => $block) {
                                if ((date('H', $block['start']) == date('H', $block['end'])) && (date('i', $block['start']) == date('i', $block['end']))) {
                                    $form->set_error("block_time[$key][start]", I18N::T('eq_reserv', '块状预约个别时段时间段不能相同!'));
                                }

                                if (Date::convert_interval($block['align_time'], $block['align_format']) <= 0) {
                                    $form->set_error("block_time[$key][interval_time]", I18N::T('eq_reserv', '块状预约个别时段时间长度对齐间隔必须大于0!'));
                                }

                                if (Date::convert_interval($block['align_time'], $block['align_format']) <= 0) {
                                    $form->set_error("block_time[$key][align_time]", I18N::T('eq_reserv', '块状预约个别时段时间起始对齐间隔必须大于0!'));
                                }
                            }
                        }
                        $kk = $key.$block['start'].$block['end'];
                        foreach ($settingBlocks as $k => $setting){
                            if(!Config::get('eq_reserv.block_day_cross')) continue;
                            if($k != $kk && $block['start'] <= $setting['start'] && ($block['start'] <= $setting['end'] && $setting['end'] <= $setting['start'] ||$block['end'] >= $setting['start'])){
                                $form->set_error("block_time[$key][end]", I18N::T('eq_reserv', '块状预约个别时段不允许交叉!'));
                            }
                            if($k != $kk && $block['start'] >= $setting['start'] && $block['start'] >= $setting['end'] && $setting['end'] < $setting['start']){
                                $form->set_error("block_time[$key][end]", I18N::T('eq_reserv', '块状预约个别时段不允许交叉!'));
                            }
                            if(Config::get('eq_reserv.block_day_cross')){
                                //检测每个块的时间长度
                                $dsend = $setting['end'] < $setting['start'] ? $setting['end'] + 86400 : $setting['end'];
                                $dstart = $setting['start'];
                                $z = $dsend - $dstart;
                                $zz = $setting['interval_format'] == 'i' ? 60 : 3600;
                                $sz = $setting['interval_time'] * $zz;
                                if($z < $sz) $form->set_error("block_time[$key][end]", I18N::T('eq_reserv', '块状预约时间长度对齐间距超出区间总长度!'));
                            }
                        }
                    }

                    if ($form['add_resrev_earliest_format'] && $form['add_reserv_earliest_format'] == 'i') {
                        $tmp_value = $form['add_resrev_earliest_format'];
                        if ((float) $tmp_value > (int) $tmp_value) {
                            $form->set_error('add_reserv_earliest_format', I18N::T('eq_reserv', '添加预约最早提前时间精确到“分”时，请填写整数值'));
                        }
                    }

                    if ($form['add_reserv_latest_format'] && $form['add_reserv_latest_format'] == 'i') {
                        $tmp_value = $form['add_reserv_latest_format'];
                        if ((float) $tmp_value > (int) $tmp_value) {
                            $form->set_error('add_reserv_latest_format', I18N::T('eq_reserv', '添加预约最晚提前时间精确到“分”时，请填写整数值'));
                        }
                    }

                    if ($form['modify_reserv_latest_format'] && $form['modify_reserv_latest_format'] == 'i') {
                        $tmp_value = $form['modify_reserv_latest_format'];
                        if ((float) $tmp_value > (int) $tmp_value) {
                            $form->set_error('modify_reserv_latest_format', I18N::T('eq_reserv', '修改 / 删除预约最晚提前时间精确到“分”时，请填写整数值'));
                        }
                    }

                    $special_tags = $form['special_tags'];
                    if ($special_tags) {
                        foreach ($special_tags as $i => $tags) {
                            $tags = @json_decode($tags, true);
                            if ($tags) {
                                foreach ($tags as $tag) {
                                    if ($form['specific_add_earliest_limit'][$i] == 'customize') {
                                        $tmp_value  = $form['specific_add_earliest_time'][$i];
                                        $tmp_format = $form['specific_add_earliest_format'][$i];
                                        if ($tmp_format == 'i') {
                                            if ((float) $tmp_value > (int) $tmp_value) {
                                                $form->set_error("specific_add_earliest_limit[$i]", I18N::T('eq_reserv', '添加预约最早提前时间精确到“分”时，请填写整数值'));
                                            }
                                        }
                                    }
                                    if ($form['specific_add_latest_limit'][$i] == 'customize') {
                                        $tmp_value  = $form['specific_add_latest_time'][$i];
                                        $tmp_format = $form['specific_add_latest_format'][$i];
                                        if ($tmp_format == 'i') {
                                            if ((float) $tmp_value > (int) $tmp_value) {
                                                $form->set_error("specific_add_latest_limit[$i]", I18N::T('eq_reserv', '添加预约最晚提前时间精确到“分”时，请填写整数值'));
                                            }
                                        }
                                    }
                                    if ($form['modify_add_latest_limit'][$i] == 'customize') {
                                        $tmp_value  = $form['modify_add_latest_time'][$i];
                                        $tmp_format = $form['modify_add_latest_format'][$i];
                                        if ($tmp_format == 'i') {
                                            if ((float) $tmp_value > (int) $tmp_value) {
                                                $form->set_error("specific_modify_latest_limit[$i]", I18N::T('eq_reserv', '修改 / 删除预约最晚提前时间精确到“分”时，请填写整数值'));
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($form['accept_merge_reserv'] && $form['merge_reserv_interval'] && (!is_numeric($form['merge_reserv_interval']) || $form['merge_reserv_interval'] < 0)) {
                        $form->set_error('merge_reserv_interval', I18N::T('eq_reserv', '连续预约的最大间距输入有误'));
                    }

                    if ($form['reserv_arrival_limit']) {
                        $form->validate('reserv_arrival_limit_mins', 'number(>=0)', I18N::T('eq_reserv', '提前提醒用户预约结束的分钟数, 请填写整数值'));
                    }

                    if ($form['accept_leave_early']) {
                        $form->validate('allow_leave_early_mins', 'number(>=0)', I18N::T('eq_reserv', '用户提前结束仪器使用的分钟数, 请填写整数值'));
                    }

                    if ($form['accept_late']) {
                        $form->validate('allow_late_mins', 'number(>=0)', I18N::T('eq_reserv', '超出预约未登录使用仪器的分钟数, 请填写整数值'));
                    }

                    if ($form['accept_overtime']) {
                        $form->validate('allow_overtime_mins', 'number(>=0)', I18N::T('eq_reserv', '超过预约时间继续使用的分钟数, 请填写整数值'));
                    }

                    if ($form['reserv_minimum_duration_format'] && $form['reserv_minimum_duration_format'] == 'i') {
                        $tmp_value = $form['reserv_minimum_duration'];
                        if ((float)$tmp_value>(int)$tmp_value) {
                            $form->set_error('reserv_minimum_duration', I18N::T('eq_reserv', '用户最低起约时长精确到“分”时，请填写整数值'));
                        }
                    }

                    if ($form->no_error) {
                        $success = true;

                        $reserv_script = ($form['enable_reserv_script'] == 'on') ? $form['reserv_script'] : null;

                        if ($reserv_script) {
                            Event::trigger('equipment.custom_content',$reserv_script,$form,$equipment);
                            //lua脚本是否非genee用户可见
                            $script_array = $equipment->display_reserv_script;
                            if (!is_array($script_array)) $script_array = [];
                            if ($form['display_reserv_script'] == 'on') $script_array['eq_reserv'] = 1;
                            else $script_array['eq_reserv'] = 0;

                            Event::trigger("eq_reserv_script_visualization",$reserv_script,$form,$equipment);

                            //check没有通过
                            if (!EQ_Reserv_Lua::check_syntax($reserv_script, $err)) {
                                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '预约脚本问题: %lua', ['%lua' => $err]));
                                $success = false;
                            } else {
                                $equipment->reserv_script = $reserv_script;
                                //lua脚本是否非genee用户可见
                                $script_array = $equipment->display_reserv_script;
                                if (!is_array($script_array)) $script_array = [];
                                if ($form['display_reserv_script'] == 'on') $script_array['eq_reserv'] = 1;
                                else $script_array['eq_reserv'] = 0;
                                $equipment->display_reserv_script = $script_array;
                            }
                        } else {
                            //清空
                            $equipment->reserv_script = null;
                            $equipment->visual_html = NULL;
                            $equipment->visual_vars = NULL;
                            Event::trigger('equipment.custom_content_empty',$equipment,'eq_reserv');
                        }

                        $equipment->accept_block_time = ($form['accept_block_time'] == 'on');

                        $equipment->reserv_require_training = ($form['reserv_require_training'] == 'on');

                        // 同一用户同一时间只能预约同一仪器
                        if (Config::get('eq_reserv.single_equipemnt_reserv')) {
                            $equipment->single_equipemnt_reserv = (int) ($form['single_equipemnt_reserv'] == 'on');
                        }

                        $equipment->unbind_reserv_time = ($form['unbind_reserv_time'] == 'on');

                        //bug#13984 转需求： 仅在相同关联项目合并
                        $equipment->merge_in_same_project = ($form['merge_in_same_project'] == 'on');

                        if ($me->is_allowed_to('锁定预约', $equipment)) {
                            $equipment->reserv_lock = $form['reserv_lock'];
                        }

                        if (!$equipment->accept_reserv) {
                            $properties->set('add_reserv_earliest_limit', null, '*');
                            $properties->set('add_reserv_latest_limit', null, '*');
                            $properties->set('modify_reserv_latest_limit', null, '*');
                            $properties->set('delete_reserv_latest_limit', null, '*');
                        } else {
                            if ($form['accept_merge_reserv']) {
                                $equipment->accept_merge_reserv   = true;
                                $equipment->merge_reserv_interval = Date::convert_interval($form['merge_reserv_interval'], 'i');
                            } else {
                                $equipment->accept_merge_reserv   = false;
                                $equipment->merge_reserv_interval = false;
                            }

                            if ($form['advance_use_is_allowed']) {
                                $equipment->advance_use_is_allowed = true;
                                $equipment->advance_use_time = Date::convert_interval($form['advance_use_time'], 'i');
                            } else {
                                $equipment->advance_use_is_allowed = false;
                                $equipment->advance_use_time = FALSE;
                            }

                            /*modify_time_limit
                            >0: 取消预约的最小提前时间
                            =0: 不可取消
                            <0: 不限制.
                            NULL:默认
                             */
                            /*reserv_max_time
                            >0: 添加预约的最大可提前时间
                            =0: 不可取消
                            <0: 不限制
                            NULL：默认
                             */
                            if ($form['default_add_earliest'] == 'customize') {
                                $equipment->add_reserv_earliest_limit = Date::convert_interval($form['add_reserv_earliest_time'], $form['add_reserv_earliest_format']);
                            } else {
                                $equipment->add_reserv_earliest_limit = null;
                            }

                            if ($form['default_add_latest'] == 'customize') {
                                $equipment->add_reserv_latest_limit = Date::convert_interval($form['add_reserv_latest_time'], $form['add_reserv_latest_format']);
                            } else {
                                $equipment->add_reserv_latest_limit = null;
                            }

                            if ($form['default_modify_latest'] == 'customize') {
                                $equipment->modify_reserv_latest_limit = Date::convert_interval($form['modify_reserv_latest_time'], $form['modify_reserv_latest_format']);
                            } else {
                                $equipment->modify_reserv_latest_limit = null;
                            }

                            if ($form['default_delete_latest'] == 'customize') {
                                $equipment->delete_reserv_latest_limit = Date::convert_interval($form['delete_reserv_latest_time'], $form['delete_reserv_latest_format']);
                            } else {
                                $equipment->delete_reserv_latest_limit = null;
                            }

                            if($form['default_minimum_duration'] == 'customize'){
                                $equipment->reserv_minimum_duration = Date::convert_interval($form['reserv_minimum_duration'], $form['reserv_minimum_duration_format']);
                            }
                            else{
                                $equipment->reserv_minimum_duration = NULL;
                            }

                            $default_add_reserv_earliest_limit  = Lab::get('equipment.add_reserv_earliest_limit');
                            $default_add_reserv_latest_limit    = Lab::get('equipment.add_reserv_latest_limit');
                            $default_modify_reserv_latest_limit = Lab::get('equipment.modify_reserv_latest_limit');
                            $default_delete_reserv_latest_limit = Lab::get('equipment.delete_reserv_latest_limit');

                            //用于清空所有@TAG的配置
                            $properties->set('specific_add_earliest_limit', null, '*');
                            $properties->set('specific_add_latest_limit', null, '*');
                            $properties->set('specific_modify_latest_limit', null, '*');
                            $properties->set('specific_delete_latest_limit', null, '*');

                            $properties->set('foo', null, '*');

                            $special_tags = $form['special_tags'];

                            if ($special_tags) {
                                foreach ($special_tags as $i => $tags) {
                                    $tags = @json_decode($tags, true);

                                    if ($tags) {
                                        foreach ($tags as $tag) {
                                            $customize_flag = false;
                                            //取消预约的最小提前时间
                                            //specific_add_earliest_limit
                                            if ($form['specific_add_earliest_limit'][$i] == 'customize') {
                                                $specific_add_earliest_limit = Date::convert_interval($form['specific_add_earliest_time'][$i], $form['specific_add_earliest_format'][$i]);
                                                $properties->set('specific_add_earliest_limit', $specific_add_earliest_limit, $tag);
                                                $customize_flag = true;
                                            } else {
                                                $properties->set('specific_add_earliest_limit', null, $tag);
                                            }

                                            if ($form['specific_add_latest_limit'][$i] == 'customize') {
                                                $specific_add_latest_limit = Date::convert_interval($form['specific_add_latest_time'][$i], $form['specific_add_latest_format'][$i]);
                                                $properties->set('specific_add_latest_limit', $specific_add_latest_limit, $tag);
                                                $customize_flag = true;
                                            } else {
                                                $properties->set('specific_add_latest_limit', null, $tag);
                                            }

                                            if ($form['specific_modify_latest_limit'][$i] == 'customize') {
                                                $specific_modify_latest_limit = Date::convert_interval($form['specific_modify_latest_time'][$i], $form['specific_modify_latest_format'][$i]);
                                                $properties->set('specific_modify_latest_limit', $specific_modify_latest_limit, $tag);
                                                $customize_flag = true;
                                            } else {
                                                $properties->set('specific_modify_latest_limit', null, $tag);
                                            }

                                            if ($form['specific_delete_latest_limit'][$i] == 'customize') {
                                                $specific_delete_latest_limit = Date::convert_interval($form['specific_delete_latest_time'][$i], $form['specific_delete_latest_format'][$i]);
                                                $properties->set('specific_delete_latest_limit', $specific_delete_latest_limit, $tag);
                                                $customize_flag = true;
                                            } else {
                                                $properties->set('specific_delete_latest_limit', null, $tag);
                                            }
                                            //如果没有reserv_max_time和 modify_time_limit 则值都为null, 所以需要占位，让数组有值，才能保存
                                            if ($customize_flag) {
                                                $properties->set('foo', null, $tag);
                                            } else {
                                                $properties->set('foo', 'bar', $tag);
                                            }
                                        }
                                    }
                                }
                            }

                            /*
                            针对于预约块状化所做的属性保存
                             */
                            if (!$equipment->accept_block_time) {
                                unset($equipment->reserv_interval_time);
                                unset($equipment->reserv_align_time);
                                unset($equipment->reserv_block_data);
                            } else {
                                $equipment->reserv_interval_time = Date::convert_interval($form['interval_time'], $form['interval_time_format']);
                                $equipment->reserv_align_time    = Date::convert_interval($form['align_time'], $form['align_time_format']);
                                $block_times                     = $form['block_time'];
                                $data                            = [];
                                if (count($block_times)) {
                                    Q("eq_reserv_time[equipment={$equipment}]")->delete_all();
                                    foreach ($block_times as $key => $block) {
                                        $data[$key]['dtstart']       = ['h' => date('H', $block['start']), 'i' => date('i', $block['start'])];
                                        $data[$key]['dtend']         = ['h' => date('H', $block['end']), 'i' => date('i', $block['end'])];
                                        $data[$key]['interval_time'] = Date::convert_interval($block['interval_time'], $block['interval_format']);
                                        $data[$key]['align_time']    = Date::convert_interval($block['align_time'], $block['align_format']);
                                    }
                                }
                                $equipment->reserv_block_data = $data;
                            }
                        }

                        $equipment->reserv_require_pc = intval(($form['accept_reserv'] == 'on') && ($form['reserv_require_pc'] == 'on'));

                        if (Lab::get('eq_reserv.glogon_arrival')) {
                            $reserv_arrival_limit = ($form['reserv_arrival_limit'] == 'on');
                            if ($equipment->accept_reserv && $reserv_arrival_limit) {
                                $equipment->reserv_arrival_limit      = $reserv_arrival_limit;
                                $equipment->reserv_arrival_limit_time =
                                    intval($form['reserv_arrival_limit_mins']) * 60;
                            } else {
                                $equipment->reserv_arrival_limit      = null;
                                $equipment->reserv_arrival_limit_time = null;
                            }
                        }

                        $accept_leave_early = ($form['accept_leave_early'] == 'on');
                        if ($equipment->accept_reserv && $accept_leave_early) {
                            $equipment->accept_leave_early     = $accept_leave_early;
                            $equipment->allow_leave_early_time = intval($form['allow_leave_early_mins']) * 60;
                        } else {
                            $equipment->accept_leave_early     = null;
                            $equipment->allow_leave_early_time = null;
                        }

                        $accept_late = ($form['accept_late'] == 'on');
                        if ($equipment->accept_reserv && $accept_late) {
                            $equipment->accept_late     = $accept_late;
                            $equipment->allow_late_time = intval($form['allow_late_mins']) * 60;
                            $equipment->late_use        = $form['late_use'];
                        } else {
                            $equipment->accept_late     = null;
                            $equipment->allow_late_time = null;
                            $equipment->late_use        = null;
                        }

                        $accept_overtime = ($form['accept_overtime'] == 'on');
                        if ( $equipment->accept_reserv && $accept_overtime ) {
                            $equipment->accept_overtime = $accept_overtime;
                            $equipment->allow_over_time =  intval($form['allow_overtime_mins']) * 60;
                        }
                        else {
                            $equipment->accept_overtime = NULL;
                            $equipment->allow_over_time = NULL;
                        }

                        $accept_late = ($form['accept_late'] == 'on');
                        if ( $equipment->accept_reserv && $accept_late ) {
                            $equipment->accept_late = $accept_late;
                            $equipment->allow_late_time =  intval($form['allow_late_mins']) * 60;
                            $equipment->late_use = $form['late_use'];
                        }
                        else {
                            $equipment->accept_late = NULL;
                            $equipment->allow_late_time = NULL;
                            $equipment->late_use = NULL;
                        }

                        //爽约记录自动创建对应使用记录功能
                        $equipment->auto_create_record = (boolean) ($form['auto_create_record'] == 'on');

                        $equipment->ban_status_settings = $form['ban_status_settings'] ?? [];

                        Event::trigger('eq_reserv.equipment_edit_time_form_submit', $equipment, $form);

                        //properties和equipment需要分开进行save
                        $equipment->save();
                        $properties->save();

                        if ($success) {

                            if (Module::is_installed('app')) {
                                CLI_YiQiKong::update_equipment($equipment->id);
                            }

                            Log::add(strtr('[equipments] %user_name[%user_id]修改%equipment_name[%equipment_id]仪器的预约设置', [
                                '%user_name'      => $me->name,
                                '%user_id'        => $me->id,
                                '%equipment_name' => $equipment->name,
                                '%equipment_id'   => $equipment->id]), 'journal');

                            // 同一用户同一时间只能预约同一仪器
                            if (Config::get('eq_reserv.single_equipemnt_reserv')) {
                                Log::add(strtr('[equipments] %user_name[%user_id]修改%equipment_name[%equipment_id]仪器的同一用户同一时间只能预约同一仪器设置为%value', [
                                    '%user_name' => $me->name,
                                    '%user_id' => $me->id,
                                    '%equipment_name' => $equipment->name,
                                    '%equipment_id' => $equipment->id,
                                    '%value' => $equipment->single_equipemnt_reserv ? 'true' : 'false',
                                ]), 'journal');
                            }

                            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_reserv', '设备预约设置已更新!'));
                        }
                    }

                    break;
                default:
                    //其他进行 Trigger
                    Event::trigger('eq_reserv.edit_form.submit', $form, $equipment);
            }

            Event::trigger('eq_reserv.equipment_edit_form_submit', $equipment, $form);

            if ($form->no_error) {
                $equipment->reserv_type = Input::form('reserv_type');
                $equipment->save();
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '设备预约设置更新失败!'));
            }

        }

        if (!is_null($properties->get('add_reserv_earliest_limit', '@'))) {
            list($add_reserv_earliest_time, $add_reserv_earliest_format) = Date::format_interval($properties->get('add_reserv_earliest_limit', '@'), 'hid');
        }

        if (!is_null($properties->get('add_reserv_latest_limit', '@'))) {
            list($add_reserv_latest_time, $add_reserv_latest_format) = Date::format_interval($properties->get('add_reserv_latest_limit', '@'), 'hid');
        }

        if (!is_null($properties->get('modify_reserv_latest_limit', '@'))) {
            list($modify_reserv_latest_time, $modify_reserv_latest_format) = Date::format_interval($properties->get('modify_reserv_latest_limit', '@'), 'hid');
        }

        if (!is_null($properties->get('delete_reserv_latest_limit', '@'))) {
            list($delete_reserv_latest_time, $delete_reserv_latest_format) = Date::format_interval($properties->get('delete_reserv_latest_limit', '@'), 'hid');
        }

        if(!is_null($properties->get('reserv_minimum_duration', '@'))){
            list($reserv_minimum_duration, $reserv_minimum_duration_format) = Date::format_interval($properties->get('reserv_minimum_duration','@'), 'ih');
        }

        // $times = $form->times; 会导致页面多出一份times
        $sample_times = Q("eq_reserv_time[equipment={$equipment}]");
        foreach ($sample_times as $key => $value) {
            $time                 = [];
            $time['id']           = $value->id;
            $time['equipment']    = $value->equipment->id;
            $time['startdate']    = $value->ltstart;
            $time['enddate']      = $value->ltend;
            $time['starttime']    = $value->dtstart;
            $time['endtime']      = $value->dtend;
            $time['rtype']        = $value->type;
            $time['rnum']         = $value->num;
            $time['days']         = explode(',', $value->days);
            $time['controlall']   = $value->controlall;
            $time['controluser']  = $value->controluser;
            $time['controllab']   = $value->controllab;
            $time['controlgroup'] = $value->controlgroup;
            $times[]              = $time;
        }
        
        // 上面和下面现在这种情况需要调整一下，现在预约编辑页面内容越来越多，这种形式有点蠢
        $eq_time_counts = Q("eq_time_counts[equipment={$equipment}]");
        foreach ($eq_time_counts as $key => $value) {
            $eq_time_count['id'] = $value->id;
            $eq_time_count['equipment'] = $value->equipment->id;
            $eq_time_count['startdate'] = $value->ltstart;
            $eq_time_count['enddate'] = $value->ltend;
            $eq_time_count['rtype'] = $value->type;
            $eq_time_count['rnum'] = $value->num;
            $eq_time_count['days'] = $value->days;
            $eq_time_count['controlall'] = $value->controlall;
            $eq_time_count['controluser'] = $value->controluser;
            $eq_time_count['controllab'] = $value->controllab;
            $eq_time_count['controlgroup'] = $value->controlgroup;
            $eq_time_count['per_reserv_time'] = $value->per_reserv_time;
            $eq_time_count['total_reserv_counts'] = $value->total_reserv_counts;
            $eq_times[] = $eq_time_count;
        }

        $tabs->content = V('eq_reserv:edit/reserv', [
            'add_reserv_earliest_time'    => $add_reserv_earliest_time,
            'add_reserv_latest_time'      => $add_reserv_latest_time,
            'add_reserv_earliest_format'  => $add_reserv_earliest_format,
            'add_reserv_latest_format'    => $add_reserv_latest_format,
            'modify_reserv_latest_time'   => $modify_reserv_latest_time,
            'modify_reserv_latest_format' => $modify_reserv_latest_format,
            'delete_reserv_latest_time'   => $delete_reserv_latest_time,
            'delete_reserv_latest_format' => $delete_reserv_latest_format,
            'reserv_minimum_duration'=>$reserv_minimum_duration,
            'reserv_minimum_duration_format'=>$reserv_minimum_duration_format,
            'form'                        => $form,
            'times'                       => $times,
            'eq_times'                    => $eq_times,
        ]);
    }

    //检测该component所选区间是否有繁忙阶段
    public static function _check_busy_handler($equipment, $component)
    {
        $dtstart = $component->dtstart;
        $dtend   = $component->dtend;

        $components = Q("$equipment user.incharge<parent calendar cal_component[dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]");
        if ($components->total_count() > 0) {
            return true;
        }
        return false;
    }

    public static function check_create_time($equipment, $component)
    {
        $user = L('ME');
        $now  = Date::time();

        $dtstart = $component->get('dtstart', true);

        $newStart = $component->dtstart;
        $newEnd   = $component->dtend;

        /*
         *
         * 预约的起始结束时间均不得早于当前时间，因为普通用户无法增加该类过去时间预约
         * Cheng Liu <cheng.liu@geneegroup.com>
         * Release-2.9.5中调整预约限制时间增加限制处理
         */
        if ($newStart && $newStart < $now) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '过去时段不能创建预约!'));
            return false;
        }

        $time_limit = self::get_time_limit($user, $equipment);

        $add_reserv_earliest_limit = $time_limit['add_reserv_earliest_limit'];
        $add_reserv_latest_limit   = $time_limit['add_reserv_latest_limit'];

        list($add_earliest_time, $add_earliest_format) = Date::format_interval($add_reserv_earliest_limit, 'hid');
        $add_earliest_str                              = $add_earliest_time . I18N::T('eq_reserv', Date::unit($add_earliest_format));

        list($add_latest_time, $add_latest_format) = Date::format_interval($add_reserv_latest_limit, 'hid');
        $add_latest_str                            = $add_latest_time . I18N::T('eq_reserv', Date::unit($add_latest_format));

        $judgeEndLimit   = $add_reserv_latest_limit;
        $judgeStartLimit = $add_reserv_earliest_limit;

        /**
         *
         *  $now    $newStart  $judgeEndLimit        $judgeStartLimit      $newEnd
         *  |___________*_____________|______________________|________________*_____________|
         *
         *  创建时间不得超越最早和最晚时间
         *
         **/

        if (($judgeStartLimit != 0 && $newEnd - $now > $judgeStartLimit) ||
            ($judgeEndLimit != 0 && $newStart - $now < $judgeEndLimit)) {
            $message = [];

            if ($add_earliest_str != 0) {
                $message[earliest] = I18N::T('eq_reserv', '此仪器创建预约的最早提前时间是 %start;', [
                    '%start' => $add_earliest_str,
                ]);
            }

            if ($add_latest_str != 0) {
                $message[latest] = I18N::T('eq_reserv', '此仪器最晚提前预约时间是 %end;', [
                    '%end' => $add_latest_str,
                ]);
            }

            if (count($message)) {
                $message[extra] = I18N::T('eq_reserv', '请选择有效时间段!');
                Lab::message(Lab::MESSAGE_ERROR, join("\n", $message));
            }

            return false;
        }

        //进行script判断
        if ($equipment->reserv_script && !self::check_script($component)) {
            return false;
        }

        return true;
    }

    public static function check_edit_time($equipment, $component)
    {
        $user = L('ME');

        $now = Date::time();

        $dtstart = $component->get('dtstart', true);

        if ($component->dtstart && $component->dtstart < $now) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '过去时段不能创建预约!'));
            return false;
        }

        $time_limit                 = self::get_time_limit($user, $equipment);
        $modify_reserv_latest_limit = $time_limit['modify_reserv_latest_limit'];
        $delete_reserv_latest_limit = $time_limit['delete_reserv_latest_limit'];

        list($modify_latest_time, $modify_latest_format) = Date::format_interval($modify_reserv_latest_limit, 'hid');
        list($delete_latest_time, $delete_latest_format) = Date::format_interval($delete_reserv_latest_limit, 'hid');

        /*
         *
         *  $now       $dtstart           $judgeEndLimit
         *  |______________*____________________|
         *
         *  已有的修改预约开始时间在最晚预约时间设定之前
         */

        if (($modify_reserv_latest_limit != 0 && $dtstart - $now < $modify_reserv_latest_limit)
            || ($modify_reserv_latest_limit == 0 && $dtstart < $now)) {
            error_log('此预约记录已生效，不可修改');
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '此预约记录已生效，不可修改!'));
            return false;
        }

        return true;
    }

    public static function check_delete_time($equipment, $component)
    {
        $user = L('ME');

        $now = Date::time();

        $dtstart = $component->get('dtstart', true);

        if ($component->dtstart && $component->dtstart < $now) {
            return false;
        }

        $time_limit                 = self::get_time_limit($user, $equipment);
        $delete_reserv_latest_limit = $time_limit['delete_reserv_latest_limit'];

        list($delete_latest_time, $delete_latest_format) = Date::format_interval($delete_reserv_latest_limit, 'hid');

        /*
         *
         *  $now       $dtstart           $judgeEndLimit
         *  |______________*____________________|
         *
         *  已有的修改预约开始时间在最晚预约时间设定之前
         */

        if ($delete_reserv_latest_limit != 0 && $dtstart - $now < $delete_reserv_latest_limit) {
            error_log('此预约记录已生效，不可修改');
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '此预约记录已生效，不可修改!'));
            return false;
        }

        return true;
    }

    // 同一用户同一时间只能使用一台仪器
    static function check_single_time($user, $equipment, $component) {
        $dtstart = $component->dtstart;
        $dtend = $component->dtend;
        if ($equipment->single_equipemnt_reserv) {
            /* 如果预约已经和别的预约冲突了，则给予提示 */
            $count = Q("eq_reserv<component cal_component[organizer={$user}][id!={$component->id}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]")->total_count();
            if ($count > 0) return false;
        }
        else {
            $count = Q("equipment[single_equipemnt_reserv=1] eq_reserv<component cal_component[organizer={$user}][id!={$component->id}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]")->total_count();
            if ($count > 0) return false;
        }
        return true;
    }

    public static function get_time_limit($user, $equipment)
    {
        $user = L('ME');
        //用户标签分两个 系统用户标签/仪器自己的用户标签
        $tag_table_name = ['tag', 'tag_equipment_user_tags'];
        foreach($tag_table_name as $table_name){
            if ($table_name == "tag"){
                $root = $equipment->tag_root;
                if(!$root->id) continue;
            }
            if ($table_name == "tag_equipment_user_tags"){
                $root = Tag_Model::root('equipment_user_tags');
                if(!$root->id) continue;
            }
            $current_tag = Q("$user {$table_name}[root=$root]:sort(weight A):limit(1)")->current();

            $labs = Q("$user lab");
            if ($labs->total_count()) {
                $lab_ids = implode(',', $labs->to_assoc('id', 'id'));
                $weight  = $current_tag->weight;
                if ($weight != null) {
                    $tag = Q("lab[id={$lab_ids}] {$table_name}[root=$root][weight<{$weight}]:sort(weight A):limit(1)")->current();
                    if ($tag->id && $tag->weight < $weight) {
                        $current_tag = $tag;
                    }
                } else {
                    $current_tag = Q("lab[id={$lab_ids}] {$table_name}[root=$root]:sort(weight A):limit(1)")->current();
                }
            }

            foreach ($labs as $lab) {
                $group = $lab->group;
                if (!$group->id) {
                    continue;
                }
                $groot = Tag_Model::root('group');
                foreach(Q("{$table_name}[root=$root] tag_group[root=$groot]") as $g) {
                    if (!$g->is_itself_or_ancestor_of($group)) {
                        continue;
                    }
                    $weight = $current_tag->weight;
                    if ($weight != null) {
                        $tag = Q("$g {$table_name}[root=$root][weight<{$weight}]:sort(weight A):limit(1)")->current();
                        if ($tag->id && $tag->weight < $weight) {
                            $current_tag = $tag;
                        }
                    } else {
                        $current_tag = Q("$g {$table_name}[root=$root]:sort(weight A):limit(1)")->current();
                    }
                }
            }

            $group = $user->group;
            if ($group->id) {
                $groot = $group->root;
                if (!$groot->id) {
                    $groot = Tag_Model::root('group');
                }
                foreach(Q("{$table_name}[root=$root] tag_group[root=$groot]") as $g) {
                    if (!$g->is_itself_or_ancestor_of($group)) {
                        continue;
                    }
                    $weight = $current_tag->weight;
                    if ($weight != null) {
                        $tag = Q("$g {$table_name}[root=$root][weight<{$weight}]:sort(weight A):limit(1)")->current();
                        if ($tag->id && $tag->weight < $weight) {
                            $current_tag = $tag;
                        }
                    } else {
                        $current_tag = Q("$g {$table_name}[root=$root]:sort(weight A):limit(1)")->current();
                    }
                }
            }

            //按照weight进行排序只获取最优先匹配的那个Tag
            // TODO: 我觉得这里按name取肯定有BUG！ 但是没时间改了
            if ($current_tag->id) {
                $current_tag = $current_tag->name;
            }

            //如果当前用户有对应用个别预约设置
            $tagged = (array) P($equipment)->get('@TAG', '@');
            if ($tagged && count($tagged[$current_tag])) {
                //tag中存储的，都是以specific_为前缀的
                $reserv_time_limits         = $tagged[$current_tag];
                $add_reserv_earliest_limit  = $reserv_time_limits['specific_add_earliest_limit']; //最早提前预约时间
                $add_reserv_latest_limit    = $reserv_time_limits['specific_add_latest_limit']; //最晚提前预约的时间
                $modify_reserv_latest_limit = $reserv_time_limits['specific_modify_latest_limit']; //最晚提前修改时间
                $delete_reserv_latest_limit = $reserv_time_limits['specific_delete_latest_limit']; //最晚提前修改时间
            }

            if (is_numeric($add_reserv_earliest_limit)
                || is_numeric($add_reserv_latest_limit)
                || is_numeric($delete_reserv_latest_limit)
                || is_numeric($modify_reserv_latest_limit))
            {
                break;
            }
        }

        // 将没有找到的预约设置用仪器的预约设置
        if (!is_numeric($add_reserv_earliest_limit)) $add_reserv_earliest_limit = $equipment->add_reserv_earliest_limit;
        if (!is_numeric($add_reserv_latest_limit)) $add_reserv_latest_limit = $equipment->add_reserv_latest_limit;
        if (!is_numeric($modify_reserv_latest_limit)) $modify_reserv_latest_limit = $equipment->modify_reserv_latest_limit;
        if (!is_numeric($delete_reserv_latest_limit)) $delete_reserv_latest_limit = $equipment->delete_reserv_latest_limit;

        // 如果还有没有的预约设置则找全局的个别预约设置
        if (is_numeric($add_reserv_earliest_limit) &&
            is_numeric($add_reserv_latest_limit) && is_numeric($modify_reserv_latest_limit) && is_numeric($delete_reserv_latest_limit)) {
            goto output;
        }
        $tagged = array_filter((array) Lab::get('@TAG'), function ($v, $k) {
            return !!array_filter((array) $v, function ($v, $k) {
                return is_numeric($v);
            }, ARRAY_FILTER_USE_BOTH);
        }, ARRAY_FILTER_USE_BOTH);
        $group = $user->group;
        while ($group->id != $group->root->id) {
            if (!array_key_exists($group->name, $tagged)) {
                $group = $group->parent;
                continue;
            }

            $reserv_time_limits = $tagged[$group->name];
            if (!is_numeric($add_reserv_earliest_limit)) {
                $add_reserv_earliest_limit = $reserv_time_limits['equipment.add_reserv_earliest_limit'];
            }
            if (!is_numeric($add_reserv_latest_limit)) {
                $add_reserv_latest_limit = $reserv_time_limits['equipment.add_reserv_latest_limit'];
            }
            if (!is_numeric($modify_reserv_latest_limit)) {
                $modify_reserv_latest_limit = $reserv_time_limits['equipment.modify_reserv_latest_limit'];
            }
            if (!is_numeric($delete_reserv_latest_limit)) {
                $delete_reserv_latest_limit = $reserv_time_limits['equipment.delete_reserv_latest_limit'];
            }
            break;
        }

        // 再没有就用全局的预约设置
        if (is_numeric($add_reserv_earliest_limit) &&
            is_numeric($add_reserv_latest_limit) && is_numeric($modify_reserv_latest_limit) && is_numeric($delete_reserv_latest_limit)) {
            goto output;
        }
        if (!is_numeric($add_reserv_earliest_limit)) {
            $add_reserv_earliest_limit = Lab::get('equipment.add_reserv_earliest_limit');
        }
        if (!is_numeric($add_reserv_latest_limit)) {
            $add_reserv_latest_limit = Lab::get('equipment.add_reserv_latest_limit');
        }
        if (!is_numeric($modify_reserv_latest_limit)) {
            $modify_reserv_latest_limit = Lab::get('equipment.modify_reserv_latest_limit');
        }
        if (!is_numeric($delete_reserv_latest_limit)) {
            $delete_reserv_latest_limit = Lab::get('equipment.delete_reserv_latest_limit');
        }

        output:
        return [
            'add_reserv_earliest_limit'  => max(0, $add_reserv_earliest_limit),
            'add_reserv_latest_limit'    => max(0, $add_reserv_latest_limit),
            'modify_reserv_latest_limit' => max(0, $modify_reserv_latest_limit),
            'delete_reserv_latest_limit' => max(0, $delete_reserv_latest_limit),
        ];
    }

    public static function check_script($component)
    {
        if (!$component->id) {
            $organizer            = $component->organizer;
            $component->organizer = $organizer->id ? $organizer : L('ME');
        }
        $reserv_lua = new EQ_Reserv_LUA($component);
        //需要的参数数组
        $need_args   = ['can_reserv', 'err_msg'];
        $collections = $reserv_lua->run($need_args);

        if (!$collections['can_reserv']) {
            Lab::message(Lab::MESSAGE_ERROR, isset($collections['err_msg']) ? $collections['err_msg'] : I18N::T('eq_reserv', '自定义脚本未知错误！'));
            return false;
        }

        return true;
    }

    public static function cal_component_deleted($e, $component)
    {
        $parent = $component->calendar->parent;
        if ($parent->name() != 'equipment') {
            return;
        }
        $reserv = O('eq_reserv', ['component' => $component]);
        $reserv->delete();

        Log::add(strtr('[eq_reserv] %user_name[%user_id] 于 %date 删除了仪器 %equipment_name[%equipment_id] 的仪器预约[%component_id]', [
            '%user_name'      => L('ME')->name,
            '%user_id'        => L('ME')->id,
            '%date'           => Date::format(Date::time()),
            '%equipment_name' => $reserv->equipment->name,
            '%equipment_id'   => $reserv->equipment->id,
            '%component_id'   => $component->id]), 'journal');
    }

    //删除record后更新reserv->status
    public static function on_record_deleted($e, $record)
    {
        $dtstart = $record->dtstart;
        $dtend   = $record->dtend;

        /* 使用记录开始/结束时间比计费流水锁定还早, 就不更新了 详见20200867温州大学杨云课题组收费问题 */
        $locked_deadline = Lab::get('transaction_locked_deadline');
        if ($record->dtstart <= $locked_deadline || $record->dtend <= $locked_deadline) {
            return;
        }

        $equipment = $record->equipment;

        $status = EQ_Reserv_Model::PENDING;
        //对所有非pending的状态, 进行更新
        foreach (Q("eq_reserv[equipment={$equipment}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}|dtend={$dtstart}~{$dtend}][status!={$status}]") as $reserv) {
            $reserv->status = $reserv->get_status(true);
            $reserv->save();
        }

        if ($record->reserv->id) {
            $connected_records = Q("eq_record[reserv=$record->reserv]");
            foreach ($connected_records as $r) {
                $r->flag = $r->reserv->get_status(false, $r);
                $r->save();
            }
        }
    }

    public static function on_record_saved($e, $record, $new_data, $old_data)
    {
        if ($record->dtstart && $record->dtend && !$record->is_missed) {
            if ($new_data['dtstart']) {
                $dtstart = min($old_data['dtstart'], $new_data['dtstart']);
            } else {
                $dtstart = $record->dtstart;
            }

            if ($new_data['dtend']) {
                $dtend = max($old_data['dtend'], $new_data['dtend']);
            } else {
                $dtend = $record->dtend;
            }

            $user = $record->user;

            $status    = EQ_Reserv_Model::PENDING;
            $equipment = $record->equipment;
            //对所有当前仪器内的该用户所属下的有时间关联的非pending的状态的预约, 进行更新
            foreach (Q("eq_reserv[user={$user}][equipment={$equipment}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}|dtend={$dtstart}~{$dtend}][status!={$status}]") as $reserv) {
                $new_status = $reserv->get_status(true, $record);
                if ($new_status != $reserv->status && !$reserv->last_status_judge_time) {
                    //预约状态发生改变 清空该预约关联的credit_record 下面会重新生成新的credit_record
                    Event::trigger('after_reserv_status_changed', $reserv);
                    $latest_record = Q("eq_record[reserv={$reserv}][dtend>0]:limit(1)")->current();
                    switch ($new_status) {
                        case EQ_Reserv_Model::MISSED:
                            CLI_EQ_Reserv::miss_reserv($user, $equipment, $reserv);
                            break;
                        case EQ_Reserv_Model::LEAVE_EARLY:
                            CLI_EQ_Reserv::leave_early_reserv($user, $equipment, $latest_record, $reserv);
                            break;
                        case EQ_Reserv_Model::LATE_LEAVE_EARLY:
                            CLI_EQ_Reserv::late_leave_early_reserv($user, $equipment, $latest_record, $reserv);
                            break;
                        case EQ_Reserv_Model::OVERTIME:
                            CLI_EQ_Reserv::overtime_reserv($user, $equipment, $reserv);
                            break;
                        case EQ_Reserv_Model::LATE:
                            CLI_EQ_Reserv::late_reserv($user, $equipment, $reserv);
                            break;
                        case EQ_Reserv_Model::LATE_OVERTIME:
                            CLI_EQ_Reserv::late_and_overtime_reserv($user, $equipment, $reserv);
                            break;
                        case EQ_Reserv_Model::NORMAL :
                            Event::trigger('trigger_scoring_rule', $user, 'reserv', $equipment, $reserv);
                        default :
                            break;
                    }
                    $reserv->status = $new_status;
                    $reserv->credit_count = 1;
                    $reserv->save();
                }
            }

            if ((($old_data['id'] && !$new_data['id']) || ($old_data['user'] != $new_data['user'])) && $record->reserv->id) {
                $connected_records = Q("eq_record[reserv=$record->reserv]");
                foreach ($connected_records as $r) {
                    $r->flag = $r->reserv->get_status(false, $r);
                    $r->save();
                }
            }
            //对关联预约的关联项目, 进行更新
            foreach (Q("eq_reserv[user={$user}][equipment={$equipment}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}|dtend={$dtstart}~{$dtend}]") as $reserv) {
                if ($record->project->id && $record->project->id != $reserv->project->id) {
                    $reserv->project = $record->project;
                    $reserv->save();
                }
            }
        }
    }

    /*
     * @Description:弹出层提交后会trigger该方法
     */
    public static function component_form_submit($e, $form, $component, $var = [])
    {
        $me = L('ME');
        $need_reserv_description = Lab::get('equipment.need_reserv_description', Config::get('equipment.need_reserv_description', '@'));
        $parent                  = $component->calendar->parent;
        if ('on' == $need_reserv_description && !$form['description'] && $parent->id && $parent->name() == 'equipment') {
            $form->set_error('description', I18N::T('eq_reserv', '请填写备注信息！'));
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '请填写备注信息!'));
        }
        if ($parent->name() == 'user' && $component->calendar->type == 'eq_incharge') {
            $component->type = Cal_Component_Model::TYPE_VFREEBUSY;
            $component->name = I18N::T('eq_reserv', '非预约时段');
        } elseif ($parent->name() == 'equipment') {

            /* 除管理员外的用户没生成component时，没有设定默认的type类型 */

            $type      = $component->type;
            $equipment = $parent;

            $component->type = isset($type) ? $type : Cal_Component_Model::TYPE_VEVENT;

            //验证表单
            $valid_token_result = EQ_Reserv_Access::valid_access_token($form['reserv_form_token']??'');
            EQ_Reserv::cache_reserv_log($form['reserv_form_token'],'submit_form',$form);
            EQ_Reserv_Access::cache_used_token($form['reserv_form_token']);

            //把form_token更新
            $form['form_token'] = $form['reserv_form_token'];

            if($valid_token_result['code'] != 200){
                $form->set_error('organizer', I18N::T('eq_reserv', $valid_token_result['error']));
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', $valid_token_result['error']));
            }

            if (!L('ME')->is_allowed_to('修改预约', $parent)) {
                $old = $component->get('organizer', true);
                if ($old != $component->organizer) {
                    $component->organizer = $old->id ? $old : L('ME');
                }
            } else {
                if ($form['organizer']) {
                    $organizer = O('user', $form['organizer']);
                    if (!$organizer->id) {
                        $form->set_error('organizer', I18N::T('eq_reserv', '请填写有效的预约者信息!'));
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '请填写有效的预约者信息!'));
                    }
                }
            }

            if (Module::is_installed('labs')) {
                $must_connect_project = Config::get('eq_reserv.must_connect_lab_project');
                if ($must_connect_project && !$form['project']) {
                    $form->set_error('project', I18N::T('eq_reserv', '"关联项目" 不能为空!'));
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '"关联项目" 不能为空!'));
                }
                if ($GLOBALS['preload']['people.multi_lab'] && !$form['project_lab']) {
                    $form->set_error('project_lab', I18N::T('eq_reserv', '"实验室" 不能为空!'));
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '"实验室" 不能为空!'));
                }
            }

            $dtstart = $form['dtstart'];
            $dtend   = $form['dtend'];

            $source_component = O('cal_component', $component->id);
            if ($component->id && $me->id != $source_component->organizer->id
                && Config::get('eq_reserv.delete_edit_remark')) {
                $reserv           = O('eq_reserv', ['component' => $component]);
                if ($form['type'] != $source_component->type
                    || $form['organizer'] != $reserv->user->id
                    || $form['dtstart'] != $reserv->dtstart
                    || $form['dtend'] != $reserv->dtend) {
                    $form->validate('edit_remark', 'not_empty', I18N::T('eq_reserv', '请填写编辑说明!'));
                    $component->edit_remark = $form['edit_remark'];
                }
            }

            //check working time
            if (Module::is_installed('eq_empower')) {
                $check_workingtime = Event::trigger('eq_empower.check_add_workingtime', $equipment, $dtstart, $dtend, $organizer);
                if (!$check_workingtime) {
                    $form->set_error('dtstart', I18N::T('eq_reserv', '非工作时间内不允许添加使用预约!'));
                }
            }

            if (Module::is_installed('test_project')) {
                foreach ((array) $form['test_project'] as $id => $val) {
                    if ($val == 'on' && $form['test_project_number'][$id] < 0 ) {
                        $form->set_error("test_project_number[$id]", I18N::T('test_project', "测试项目数量不能小于0"));
                    }
                }
            }

            Event::trigger('extra.form.validate', $equipment, 'eq_reserv', $form);
            if(Config::get('eq_record.tag_duty_teacher')) {
                $reserv           = O('eq_reserv', ['component' => $component]);
                Event::trigger('extra.form.validate_duty_teacher', $equipment,$reserv, $form,'reserv');
            }

            //不再纠正时间块,直接报错提示
            self::validate_block_time($e, $me, $equipment, $form);
            return true;
            if ($equipment->accept_block_time) {
                $interval = $equipment->reserv_interval_time;
                $align    = $equipment->reserv_align_time;
                $blocks   = $equipment->reserv_block_data;
                if ($interval || $align || count($blocks)) {

                    /*
                     * $dtstart为用于辅助计算component->dtstart的值
                     * $dtend为用于辅助计算component->dtend的值
                     * $form['dtstart'] $form['dtend']表单传入值，该值在最后被修正
                     */
                    if ($form['dtend'] < $form['dtstart']) {
                        list($form['dtstart'], $form['dtend']) = [$form['dtend'], $form['dtstart']];
                    }

                    //如果跨天
                    if (!Config::get('eq_reserv.block_day_cross') &&
                        ((date('d', $form['dtend']) != date('d', $form['dtstart']))
                        ||
                        ($form['dtend'] - $form['dtstart'] >= 86400))
                    ) {
                        $form->set_error('dtstart', I18N::T('eq_reserv', '时间对齐的预约不允许跨零点使用!'));
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '时间对齐的预约不允许跨零点使用!'));
                        return false;
                    }

                    /*
                     * 前台js中已经进行矫正，但如果用户强硬的手动改写块状数字， 则我们需要在后台进行矫正。
                     */

                    //起始时间format, 为便于获取，不设定为start_format
                    $format = self::get_format_block($equipment, $form['dtstart']);

                    /*
                     * 第一步，修改、创建的component进行block位置判定，设定align interval
                     */

                    //设定align interval为dtstart所在的时段的align interval
                    $align    = $format['align'];
                    $interval = $format['interval'];

                    /*
                     * 2,先纠正dtstart位置
                     */

                    //块起始时间
                    $block_start = $format['start'];
                    $block_end   = $format['end'];
                    /*BUG #4984 when $dtstart divides $align is an interge, it's not necessary to correct!*/
                    if ($dtstart % $align != 0) {
                        $dtstart = $block_start + round(($dtstart - $block_start) / $align) * $align;
                    }

                    //如果位移后超出block，向前移动
                    if ($dtstart >= $block_end) {
                        $dtstart -= $align;
                    }

                    /*
                     * 3，纠正dtend位置，如果错位，还原到块状规矩的时间点
                     */

                    $dtend = $dtstart + round(min($dtend - $dtstart, $block_end - $dtstart) / $interval) * $interval;

                    /*
                     * 4，结束时间超过了块结束时间,纠正开始时间
                     */
                    if ($dtend > $block_end) {
                        $dtstart = $dtstart - round(($dtend - $block_end) / $align) * $align;
                        $dtend = $block_end - 1;
                    }
                    if ($dtstart < $block_start) {
                        $dtstart = $dtstart + round(($dtend - $block_end + 1) / $align) * $align;
                    }
                    //再次计算结束时间
                    $dtend = $dtstart + round(min($dtend - $dtstart, $block_end - $dtstart) / $interval) * $interval;
                    //如果校正后dtend 还是超过块状结束时间，无法生成component
                    if ($dtend > $block_end) {
                        //如果校正后超过了 块，无法提交
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '时间长度不满足预约规则!'));
                        $form->set_error('$dtend', I18N::T('eq_reserv', '时间长度不满足预约规则!'));
                    }

                    //如果校正后dtend 小于dtstart，无法生成component
                    if ($dtend <= $dtstart) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '不满足最小块状预约规则!'));
                        $form->set_error('dtstart', I18N::T('eq_reserv', '不满足最小块状预约规则!'));
                        $dtend = $dtstart;
                    }

                    //将处理过的dtstart dtend 赋值给form
                    $form['dtstart'] = $dtstart;
                    $form['dtend']   = $dtend;
                }
            }
        }
    }

    public static function validate_block_time($e, $user, $equipment, $form)
    {
        if (!is_object($form)) $form = Form::filter($form);
        if ($equipment->accept_block_time) {
            $dtstart = $form['session'] == 'ctrl-reserve' ? strtotime($form['dtstart']) : $form['dtstart'];
            $dtend   = $form['session'] == 'ctrl-reserve' ? strtotime($form['dtend']) : $form['dtend'];
            $interval = $equipment->reserv_interval_time;
            $align    = $equipment->reserv_align_time;
            $blocks   = $equipment->reserv_block_data;
            if ($interval || $align || count($blocks)) {
                if ($dtend < $dtstart) {
                    list($dtstart, $dtend) = [$dtend, $dtstart];
                }

                //如果跨天
                if (!Config::get('eq_reserv.block_day_cross')
                    && date('d',($dtend -1) ) != date('d', $dtstart)
                    &&
                    ((date('d', $dtend) != date('d', $dtstart))
                        ||
                        ($dtend - $dtstart >= 86400))
                ) {
                    if ($form['session'] == 'ctrl-reserve') {
                        throw new Error_Exception(I18N::T('eq_reserv', '时间对齐的预约不允许跨零点使用!'));
                    }else {
                        $form->set_error('dtstart', I18N::T('eq_reserv', '时间对齐的预约不允许跨零点使用!'));
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '时间对齐的预约不允许跨零点使用!'));
                    }
                    return false;
                }

                //组装规则消息
                list($interval_time, $interval_time_format) = Date::format_interval($interval, 'ih');
                list($align_time, $align_time_format) = Date::format_interval($align, 'ih');
                $error_message = I18N::T('eq_reserv', '请调整预约时间，预约块规则为：').'<br/>';
                $error_message .= I18N::T('eq_reserv', '时间长度对齐间距是'). $interval_time . Date::units('ih')[$interval_time_format] . ',';
                $error_message .= I18N::T('eq_reserv', '时间起始对齐间距是'). $align_time . Date::units('ih')[$align_time_format] . ';';
                foreach ((array)$blocks as $block) {
                    list($block_interval_time, $block_interval_time_format) = Date::format_interval($block['interval_time'], 'ih');
                    list($block_align_time, $block_align_time_format) = Date::format_interval($block['align_time'], 'ih');
                    $error_message .= '<br/>' . I18N::T('eq_reserv', '时间段'). $block['dtstart']['h'].':'.$block['dtstart']['i'] . ':00' . '-' . $block['dtend']['h'].':'.$block['dtend']['i'] . ':00,';
                    $error_message .= I18N::T('eq_reserv', '时间长度对齐间距是'). $block_interval_time . Date::units('ih')[$block_interval_time_format] . ',';
                    $error_message .= I18N::T('eq_reserv', '时间起始对齐间距是'). $block_align_time . Date::units('ih')[$block_align_time_format] . ';';
                }

                //起始时间format, 为便于获取，不设定为start_format
                $format = self::get_format_block($equipment, $dtstart);

                //设定align interval为dtstart所在的时段的align interval
                $align    = $format['align'];
                $interval = $format['interval'];
                $block_start = $format['start'];
                $block_end   = $format['end'];

                if ($dtend > $block_end || $dtstart < $block_start || ($align &&  $dtstart % $align != 0) || ($interval &&  ($dtend - $dtstart) % $interval != 0)) {
                    if ($form['session'] == 'ctrl-reserve') {
                        throw new Error_Exception(strip_tags($error_message));
                    }else {
                        Lab::message(Lab::MESSAGE_ERROR, $error_message);
                        $form->set_error('dtstart', $error_message);
                    }
                }
            }
        }

        $dtend = $dtend ?: $form['dtend'];
        $dtstart = $dtstart ?: $form['dtstart'];

        $dtend = strpos($dtend,'-') ? strtotime($dtend) : $dtend;
        $dtstart = strpos($dtstart,'-') ? strtotime($dtstart) : $dtstart;

        if ($equipment->reserv_minimum_duration && ($dtend - $dtstart < $equipment->reserv_minimum_duration)) {
            list($reserv_minimum_duration, $reserv_minimum_duration_format) = Date::format_interval($equipment->reserv_minimum_duration, 'ih');
            $reserv_minimum_duration .= ($reserv_minimum_duration_format == 'i') ? '分钟' : '小时';
            $error_message = "请调整预约时间，用户最低起约时长为{$reserv_minimum_duration}，请添加不低于{$reserv_minimum_duration}的预约时段";
            if ($form['session'] == 'ctrl-reserve') {
                throw new Error_Exception($error_message);
            } else {
                Lab::message(Lab::MESSAGE_ERROR, $error_message);
                $form->set_error('dtstart', I18N::T('eq_reserv', $error_message));
            }
        }
    }

    public static function component_form_before_delete($e, $form, $component)
    {
        $me     = L('ME');
        $reserv = O('eq_reserv', ['component' => $component]);
        if ($reserv->id && $me->id != $reserv->user->id && Config::get('eq_reserv.delete_edit_remark')) {
            if (!$form['delete_remark']) {
                JS::dialog(V('eq_reserv:calendar/delete_remark', [
                    'component' => $component,
                ]), ['title' => I18N::T('eq_remark', '请填写删除说明'), 'width' => 300]);
                $e->return_value = true;
                return true;
            } else {
                $component->delete_remark = $form['delete_remark'];
                $component->save();
                $e->return_value = false;
                return true;
            }
        }
    }

    public static function component_form_post_submit($e, $component, $form)
    {
        $parent = $component->calendar->parent;
        //equipment下进行extra属性save
        if ($parent->name() == 'equipment') {
            $reserv = O('eq_reserv', ['component' => $component]);

            if ($reserv->id && isset($form['project'])) {
                $reserv->project = O('lab_project', $form['project']);
                Cache::L('YiQiKongReservNotSendMessage', TRUE);
                $reserv->save();
            }
            // 唉，有点蠢
            if (isset($form['count']) && !isset($form['extra_fields']['count'])) {
                $form['extra_fields']['count'] = $form['count'];
            }
            Event::trigger('extra.form.post_submit', $reserv, $form);
            Event::trigger('eq_reserv.component.form.post.submit', $component, $form);
        }
    }

    public static function rrule_sub_component_saved($e, $component, $key_component)
    {
        $parent = $component->calendar->parent;

        if ($parent->name() == 'equipment') {
            $extra = O('extra', [
                'type'   => 'eq_reserv',
                'object' => $parent,
            ]);

            $kreserv = O('eq_reserv', ['component' => $key_component]);

            $kextra_value = O('extra_value', ['object' => $kreserv]);

            if ($kextra_value->id) {
                //尝试获取
                $oreserv = O('eq_reserv', ['component' => $component]);

                $extra_value = O('extra_value', ['object' => $oreserv]);

                if (!$extra_value->id) {
                    $extra_value = O('extra_value');
                }

                //无论是否获取到，直接赋值object
                $extra_value->object = $oreserv;

                //同步复制extra数据
                $extra_value->values = $kextra_value->values;

                $extra_value->save();
            }

            //同步project
            if ($kreserv->project->id) {
                $reserv          = O('eq_reserv', ['component' => $component]);
                $reserv->project = $kreserv->project;
                $reserv->save();
            }
        }
    }

    public static function cal_component_before_save($e, $component, $new_data)
    {
        $calendar = $component->calendar;
        $parent   = $calendar->parent;
        if ($parent->name() != 'equipment') {
            return;
        }

        if ($component->type == Cal_Component_Model::TYPE_VFREEBUSY) {
            $component->name = I18N::T('eq_reserv', '非预约时段');
        }

        if ($component->dtend < $component->dtstart) {
            $e->return_value = false;
            return false;
        }

        //按规则生成的预约，如果修改预约，断开该预约的关联
        $equipment = $component->calendar->parent;
        if ($component->id && $component->cal_rrule->id) {
            if ($component->get('dtstart', true) != $component->dtstart || $component->get('dtend', true) != $component->dtend || $component->get('organizer', true) != $component->organizer || $component->get('type', true) != $component->type) {
                $component->cal_rrule = null;
            }
        }

        //开始合并预约
        if ($parent->accept_merge_reserv) {
            $merge        = false;
            $calendar_id  = $component->calendar->id;
            $organizer    = $component->organizer;
            $interval     = $parent->merge_reserv_interval;
            $component_id = $component->id;

            $dtend_max        = $component->dtend + $interval;
            $after_component = Q("cal_component[calendar={$calendar}][dtstart={$component->dtend}~$dtend_max]:not({$component}):sort(dtstart A)")->current();
            if ($after_component->id && $after_component->organizer->id == $organizer->id) {
                if (self::merge_reserv($component, $after_component)) {
                    Cache::L('MERGE_COMPONENT_ID', $after_component->id);
                    Cache::L('REMOVE_COMPONENT_IDS', (array) L('REMOVE_COMPONENT_IDS') + [$component_id]);
                    $merge = true;
                }
            }

            $dtstart_min       = $component->dtstart - $interval;
            $before_component = Q("cal_component[calendar={$calendar}][dtend=$dtstart_min~{$component->dtstart}]:not({$component}):sort(dtstart D)")->current();
            if ($before_component->id && $before_component->organizer->id == $organizer->id) {
                if (self::merge_reserv($component, $before_component)) {
                    Cache::L('MERGE_COMPONENT_ID', $before_component->id);
                    Cache::L('REMOVE_COMPONENT_IDS', (array) L('REMOVE_COMPONENT_IDS') + [$component_id]);
                    $merge = true;
                }
            }

            if ($merge) {
                $e->return_value = false;
                return false;
            }
        }
    }

    // 合并预约，合并不成功则返回false
    public static function merge_reserv($source, $target)
    {
        if ($source->organizer->id != $target->organizer->id) {
            return false;
        }

        if ((int) $source->type != $target->type) {
            return false;
        }

        if (!self::check_reserv($target)) {
            return false;
        }

        if (Event::trigger('eq_reserv.merge_reserv.extra', $source, $target)) {
            return false;
        }

        /*当该预约所属仪器预约，且该仪器为块状预约时，需要增加不同时间块预约的判断*/
        $parent = $source->calendar->parent;

        if ($parent->name() == 'equipment' && $source->type == Cal_Component_Model::TYPE_VEVENT
            && $parent->accept_block_time
            && !self::check_block_reserv($source, $target)) {
            return false;
        }

        if ($source->id) {
            if (!self::check_reserv($source)) {
                return false;
            }
            $source->delete();
            $rel   = Input::form('cal_week_rel');
            $cdata = json_encode([
                'id' => $source->id,
            ]);
            JS::run("(new Q.Calendar.Week($('{$rel}')[0])).getComponent({$cdata}).remove();");
        }
        // 此处完全不用考虑使用记录的问题，只要参数正确，component保存时会自动调整record。
        $target->dtend   = max($source->dtend, $target->dtend);
        $target->dtstart = min($source->dtstart, $target->dtstart);

        $description = $target->description;
        if ($source->description && $description) {
            $description .= "\n";
        }
        $description .= $source->description;

        $target->description = $description;

        $target->save();

        $source->set_data($target->get_data());
        return true;
    }

    // 判断一条预约是否可以合并
    public static function check_reserv($component)
    {
        // 有锁定的记录
        if (Q("eq_record[is_reserv=1][reserv_id={$component->id}][is_locked!=0]")->total_count()) {
            return false;
        }
        return true;
    }

    /*
    c1 : 需要合并的2个块状预约的第一个块
    c2 : 需要合并的2个块状预约的第二个块
     */
    public static function check_block_reserv($c1, $c2)
    {
        $equipment = $c1->calendar->parent;
        /*
        f1 : 获取到c1块所处的时段区间
        f2 : 获取到c2块所处的时段区间
         */
        $f1 = self::get_format_block($equipment, $c1->dtstart);
        $f2 = self::get_format_block($equipment, $c2->dtstart);
        if (count(array_diff($f1, $f2))) {
            return false;
        }
        return true;
    }

    public static function cal_component_saved($e, $component, $old_data, $new_data)
    {
        $equipment = $component->calendar->parent;
        $form = L('add_component_form');
        $me        = L('ME');
        if ($old_data['type'] == Cal_Component_Model::TYPE_VEVENT && $new_data['type'] == Cal_Component_Model::TYPE_VFREEBUSY &&
            $equipment->id && $equipment->name() == 'equipment') {
            /* BUG 13151 【win7,Firefox】17kong/Sprint-55:仪器目录，非预约时段的预约记录不能保存用途，关联项目，时长一直是0 */
            $reserv = O('eq_reserv', ['component' => $component]);
            /* 新创建的reserv需要将equipment和component的值都赋予上 */
            if (!$reserv->id) {
                $reserv->equipment = $equipment;
                $reserv->component = $component;
            }

            // 测试项目
            // 如果不放在此处，预估会不准，进而影响预约免审金额
            if (Module::is_installed('test_project')) {
                $selected_test_project = [];
                foreach ((array) $form['test_project'] as $id => $val) {
                    if ($val == 'on') {
                        $selected_test_project[$id] = $form['test_project_number'][$id];
                    }
                }
                $reserv->test_projects = json_encode($selected_test_project);
            }

            /* reserv的时间和人员值一旦发生变化，需要赋予新的值 */
            $reserv->user    = $component->organizer;
            $reserv->dtstart = $component->dtstart;
            $reserv->dtend   = $component->dtend;
            $reserv->save();
        }
        if (($component->type == Cal_Component_Model::TYPE_VEVENT || $component->type == Cal_Component_Model::TYPE_VFREEBUSY) && $equipment->id && $equipment->name() == 'equipment' &&
            (
                $old_data['organizer']->id != $new_data['organizer']->id ||
                $old_data['dtstart'] != $new_data['dtstart'] ||
                $old_data['dtend'] != $new_data['dtend'] ||
                !is_null("custom_charge_{$component}")
            )) {
            $type_array = [
                0 => '预约',
                3 => '非预约',
            ];
            $reserv_type = $type_array[$component->type];
            if ($new_data['id']) {
                Log::add(strtr('[eq_reserv] %user_name[%user_id] 于 %date 成功创建 %equipment_name[%equipment_id] 的新的仪器预约[%component_id], 预约类型 %reserv_type, 预约开始时间: %dtstart, 结束时间: %dtend', [
                    '%user_name'      => $me->name,
                    '%user_id'        => $me->id,
                    '%date'           => Date::format(Date::time()),
                    '%equipment_name' => $equipment->name,
                    '%equipment_id'   => $equipment->id,
                    '%component_id'   => $component->id,
                    '%reserv_type'    => $reserv_type,
                    '%dtstart'        => Date::format($component->dtstart),
                    '%dtend'          => Date::format($component->dtend),
                ]), 'journal');
            } else {
                Log::add(strtr('[eq_reserv] %user_name[%user_id] 于 %date 成功修改 %equipment_name[%equipment_id] 的仪器预约[%component_id],修改后 预约类型 %reserv_type, 预约开始时间: %dtstart, 结束时间: %dtend', [
                    '%user_name'      => $me->name,
                    '%user_id'        => $me->id,
                    '%date'           => Date::format(Date::time()),
                    '%equipment_name' => $equipment->name,
                    '%equipment_id'   => $equipment->id,
                    '%component_id'   => $component->id,
                    '%reserv_type'    => $reserv_type,
                    '%dtstart'        => Date::format($component->dtstart),
                    '%dtend'          => Date::format($component->dtend),
                ]), 'journal');
            }

            $reserv = O('eq_reserv', ['component' => $component]);
            /* 新创建的reserv需要将equipment和component的值都赋予上 */
            if (!$reserv->id) {
                $reserv->equipment = $equipment;
                $reserv->component = $component;
            }

            // 测试项目
            // 如果不放在此处，预估会不准，进而影响预约免审金额
            if (Module::is_installed('test_project')) {
                $selected_test_project = [];
                foreach ((array) $form['test_project'] as $id => $val) {
                    if ($val == 'on') {
                        $selected_test_project[$id] = $form['test_project_number'][$id];
                    }
                }
                $reserv->test_projects = json_encode($selected_test_project);
            }

            /* reserv的时间和人员值一旦发生变化，需要赋予新的值 */
            $reserv->user    = $component->organizer;
            $reserv->dtstart = $component->dtstart;
            $reserv->dtend   = $component->dtend;
            // Yiqikong_API::actionUpdateComponent($data) 当中，由于只有$component->save()
            // reserve 的 approval字段只好跟着$component 转一圈这会儿再存了

            $reserv->approval = $component->approval;
            //预约样品数保存
            if (isset($form['count'])) {
                $reserv->count = (int) $form['count'];
            }
            $reserv->save();
        }
    }

    public static function before_equipment_delete($e, $equipment)
    {
        // 删除与仪器关联的日程表
        Q("calendar[parent=$equipment]")->delete_all();
    }

    public static function on_equipment_saved($e, $equipment)
    {
        if ($equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            //报废的设备，当前时间之后的所有预约，全都删除
            $now = time();
            Q("calendar[parent=$equipment] cal_component[dtstart>$now]")->delete_all();
        }
    }

    public static function cannot_access_equipment($e, $equipment, $params)
    {
        if (L('skip_cannot_access_hook')) {
            $e->return_value = false;
            return true;
        }

        $me      = $params[0];
        $dtstart = (int) $params[1];

        if ((int) $params[2] == 0) {
            $dtend = $dtstart;
        } else {
            $dtend = (int) $params[2];
        }

        $from_door = isset($params[3]) && $params[3] == 'door' ? true : false;

        //设备需要预约
        if ($equipment->accept_reserv) {
            //设置预约迟到后, 不允许用户使用
            if ($equipment->accept_late && ($equipment->late_use == EQ_Reserv_Model::LATE_USE_BANISH)) {
                //迟到了, 并且没有未迟到的使用记录
                $eq_reserv = Q("eq_reserv[equipment={$equipment}][user={$me}][dtstart~dtend={$dtstart}]:limit(1)")->current();
                if ($eq_reserv->id && ($eq_reserv->dtstart + $equipment->allow_late_time) < $dtstart) {
                    $reserv_dtstart = $eq_reserv->dtstart;

                    $reserv_late_dtend = $reserv_dtstart + $equipment->allow_late_time;

                    $eq_record_count = Q("eq_record[equipment={$equipment}][user={$me}][dtstart~dtend={$reserv_dtstart}|dtstart={$reserv_dtstart}~{$reserv_late_dtend}]")->total_count();
                    if (!$eq_record_count) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '您迟到已超过%mins分钟, 不可使用该仪器', [
                            '%mins' => (int) ($equipment->allow_late_time / 60),
                        ]));
                        $e->return_value = true;
                        return false;
                    }
                }
            }

            $busy_status = Cal_Component_Model::TYPE_VFREEBUSY;
            $calendar    = O('calendar', ['parent' => $equipment]);
            if (Q("cal_component[calendar={$calendar}][dtstart<=$dtend][dtend>=$dtstart][type=$busy_status]")->total_count()) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '当前时间为非预约时段, 您不可使用该仪器'));
                $e->return_value = true;
                return false;
            }

            // 不允许用户在他人预约时段使用仪器（非预约时段除外）
            if (!$equipment->unbind_reserv_time) {
                // 预约允许提前{advance_use_time}分钟上机
                if ($equipment->advance_use_is_allowed) {
                    // 当前已在预约时段内
                    if (Q("calendar[parent=$equipment] cal_component[organizer=$me][dtstart<=$dtend][dtend>=$dtstart]")->total_count()) {
                        // 直接上机
                    } else {
                        // 判断有没有到允许提前上机时间
                        $advance_use_time = $equipment->advance_use_time;
                        // 可以提前上机，所有开始时间在未来的$advance_use_time内有任意一条预约都可以上机
                        $advance_use_time_start = $advance_use_time + $dtstart;
                        //$advance_use_time_end = (int) $params[2] == 0 ? $advance_use_time_start : $dtend;
                        $advance_use_time_end = $dtend;
                        if (Q("calendar[parent={$equipment}] cal_component[organizer={$me}][dtstart<={$advance_use_time_start}][dtend>={$advance_use_time_end}]")->total_count() == 0) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '未到允许提前上机时间, 不可使用该仪器'));
                            $e->return_value = TRUE;
                            return FALSE;
                        }
                    }
                } elseif (Q("calendar[parent=$equipment] cal_component[organizer=$me][dtstart<=$dtend][dtend>=$dtstart]")->total_count() == 0) {
                    // 没有预约
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '您没有预约, 不可使用该仪器'));
                    $e->return_value = true;
                    return false;
                }
            } else { // 允许用户在他人预约时段使用仪器
                if (Config::get('eq_reserv.use_eq_after_reserv')) {
                    // 允许用户在他人预约时段使用仪器, 但是必须本人预约才允许开门
                    if ($from_door && !Q("calendar[parent=$equipment] cal_component[organizer=$me][dtstart<=$dtend][dtend>=$dtstart]")->total_count()) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '您没有预约, 不可使用该仪器'));
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                    // 预约允许提前{advance_use_time}分钟上机
                    if ($equipment->advance_use_is_allowed) {
                        $advance_use_time = $equipment->advance_use_time;
                        // 可以提前上机，所有开始时间在未来的$advance_use_time内有任意一条预约都可以上机
                        $advance_use_time_start = $params[1];
                        $advance_use_time_end = ($params[2] == 0 ? $params[1] : $params[2]) + $advance_use_time;
                        if (Q("calendar[parent={$equipment}] cal_component[dtstart<={$advance_use_time_end}][dtend>={$advance_use_time_start}]")->total_count() == 0) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv',
                                Date::format($advance_use_time_end, 'H:i')."~".Date::format($advance_use_time_start, 'H:i').
                                ' 没有任何人预约, 不可使用该仪器'));
                            $e->return_value = TRUE;
                            return FALSE;
                        }
                    } else {
                        if (Q("calendar[parent=$equipment] cal_component[dtstart<=$dtend][dtend>=$dtstart]")->total_count() == 0) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '没有任何人预约, 不可使用该仪器'));
                            $e->return_value = true;
                            return false;
                        }
                    }
                }
            }

            //检查当前是否为busy阶段
            if (Q("$equipment user.incharge<parent calendar[type=eq_incharge] cal_component[dtstart<=$dtend][dtend>=$dtstart]")->total_count()) {
                $e->return_value = true;
                return false;
            }
        }
    }

    public static function cannot_reserv_equipment($e, $equipment, $params)
    {
        if ($equipment->require_exam) {
            $user = $params[0];
            if ($user->id && !$user->gapper_id) {
                $lousers = (new LoGapper())->get('users', ['email'=> $user->email]);
                $louser = @current($lousers['items']);
                if ($louser['id']) {
                    $user->gapper_id = $louser['id'];
                    $user->save();
                }
            }
            $exam = Q("$equipment exam")->current();
            if ($user->gapper_id) $result = (new HiExam())->get("user/{$user->gapper_id}/exam/{$exam->remote_id}/result");
            if (!$result || !isset($result['status']) || $result['status']!='通过') {
                $url = $exam->getRemoteUrl();
                if (L('ME')->id!=$user->id) {
                    Lab::confirm(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '用户 :username 您需要通过理论考试及培训后，方可预约，请先进行理论考试。您可先进行预约资格自检，了解是否具备全部预约资格。', [
                        ':username'=> $user->name
                    ]), $url, I18N::T('eq_reserv', '去考试'));
                } else {
                    Lab::confirm(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '您需要通过理论考试及培训后，方可预约，请先进行理论考试。您可先进行预约资格自检，了解是否具备全部预约资格。'), $url, '去考试');
                }
                $e->return_value = TRUE;
                return FALSE;
            }
        }

        if (!$equipment->reserv_require_training) {
            Cache::L('skip_training_check', true);
        }

        Cache::L('skip_cannot_access_hook', true);
        $cannot_access = $equipment->cannot_access($params[0], $params[1]);
        Cache::L('skip_cannot_access_hook', false);

        Cache::L('skip_training_check', false);

        if ($cannot_access) {
            $e->return_value = true;
            return false;
        }
    }

    public static function record_description($e, $record)
    {
        if ($record->reserv->id) {
            $e->return_value[] = V('eq_reserv:view/description', ['record' => $record, 'reserv' => $record->reserv]);
        }
    }

    public static function record_description_csv($e, $record)
    {
        if ($record->reserv->id) {
            $reserv      = $record->reserv;
            $description = I18N::T('eq_reserv', '预约时间') . ' ';
            $description .= Date::range($reserv->dtstart, $reserv->dtend);
            $titles        = [];
            $reserv_status = EQ_Reserv_Model::$reserv_status;
            switch ($reserv->get_status()) {
                case EQ_Reserv_Model::MISSED:
                    $titles['missed'] = $reserv_status[EQ_Reserv_Model::MISSED];
                    break;
                case EQ_Reserv_Model::OVERTIME:
                    $titles['overtime'] = $reserv_status[EQ_Reserv_Model::OVERTIME];
                    break;
                case EQ_Reserv_Model::LATE:
                    $titles['late'] = $reserv_status[EQ_Reserv_Model::LATE];
                    break;
                case EQ_Reserv_Model::LATE_OVERTIME:
                    $titles['late']     = $reserv_status[EQ_Reserv_Model::LATE];
                    $titles['overtime'] = $reserv_status[EQ_Reserv_Model::OVERTIME];
                    break;
                default:
                    break;
            }

            foreach ($titles as $class => $title) {
                $description .= ' ' . I18N::T('eq_reserv', $title);
            }
            $e->return_value[] = $description;
        }
    }

    public static function nofeedback_record($e, $user, $equipment = null)
    {
        if ($user->id && $user->is_active() && !$user->is_allowed_to('管理使用', $equipment)) {
            $now = time();
            if ($equipment->id) {
                $e->return_value = Q("eq_record[equipment={$equipment}][user=$user][is_reserv=0][dtend>0][dtend<$now][status=0]:sort(dtend D):limit(1)")->current();
            } else {
                $e->return_value = Q("eq_record[user=$user][dtend>0][dtend<$now][is_reserv=0][status=0]:sort(dtend D):limit(1)")->current();
            }
        }
        return false;
    }

    //EQ_Record操作
    public static function before_record_save($e, $record, $new_data)
    {
        $equipment = $record->equipment;
        // 如果仪器不可预约 并且记录不存在已有的预约属性
        if (!$equipment->accept_reserv) {
            return;
        }

        $user = $record->user;

        //编辑代开记录
        if ($record->id && $new_data['user']) {
            $rid     = $record->id;
            $dtstart = $record->dtstart;

            // 绑定代开用户相应的预约记录
            $reserv         = Q("eq_reserv[equipment={$equipment}][user={$user}][dtstart~dtend=$dtstart][dtend!=$dtstart]:sort(dtstart A):limit(1)")->current();
            $record->reserv = $reserv->id ? $reserv : null;
        }

        if (!$record->reserv->id && $new_data['dtend']) {
            //直接添加或者是使用结束
            $dtstart = $new_data['dtstart'] ?: $record->dtstart;
            $dtend   = $new_data['dtend'];

            $reserv = Q("eq_reserv[equipment={$equipment}][user={$user}][dtstart=$dtstart~$dtend|dtstart~dtend=$dtstart][dtend!=$dtstart]:sort(dtstart A):limit(1)")->current();
            if ($reserv->id) {
                $record->reserv = $reserv;
                if (!$record->samples || $record->samples == 1 || !Config::get('equipment.feedback_show_samples', 0)) $record->samples = (int) $reserv->count;
            }
        }

        $reserv = $record->reserv;
        //修改使用记录后，使用时间与原有对应预约时间无交集合后脱离关系，并产生新的未使用的使用记录, 与之关联
        if ($record->id && $record->dtend && $reserv->id &&
            (
                (isset($new_data['dtend']) && $reserv->dtstart > $new_data['dtend']) ||
                (isset($new_data['dtstart']) && $reserv->dtend < $new_data['dtstart'])
            )
        ) {

            //断开链接
            $record->reserv    = null;
            $record->equipment = $equipment;
        }

        if (!$new_data['dtend'] && !$record->dtend) {
            //清空reserv
            $record->reserv = null;
        }

        if ($reserv->id) {
            $flag = $reserv->get_status(true, $record, $new_data);
            if (L('clear_leave_early_flag')) {
                if ($flag == EQ_Reserv_Model::LEAVE_EARLY) {
                    $flag = EQ_Reserv_Model::NORMAL;
                } elseif ($flag == EQ_Reserv_Model::LATE_LEAVE_EARLY) {
                    $flag = EQ_Reserv_Model::LATE;
                }
            } else {
                if ($record->clear_leave_early) {
                    $record->clear_leave_early = false;
                }
            }
            Cache::L('clear_leave_early_flag', null);
            $record->flag = $flag;
        } else {
            $record->flag = EQ_Reserv_Model::NORMAL;
        }
    }

    public static function on_reserv_saved($e, $reserv, $old_data, $new_data)
    {
        $equipment = $reserv->equipment;
        $user      = $reserv->user;

        if ($new_data['dtstart'] || $new_data['dtend']) {
            $dtstart = $new_data['dtstart'] ?: $reserv->dtstart;
            $dtend   = $new_data['dtend'] ?: $reserv->dtend;

            //找到有交错的使用记录
            $records = Q("eq_record[equipment={$equipment}][user={$user}][dtstart=$dtstart~$dtend|dtstart~dtend=$dtstart]");

            foreach ($records as $record) {
                //已经关联预约的不做处理
                if ($record->reserv->id) {
                    continue;
                }
                $record->reserv = $reserv;
                $record->save();
            }
        }
    }

    public static function on_reserv_deleted($e, $reserv)
    {
        foreach (Q("eq_record[reserv=$reserv]") as $record) {
            // 如果使用记录不在预约范围内
            $record->reserv = O('eq_reserv'); //空对象
            $record->save();
        }
    }

    public static function get_eq_record_update_parameter($e, $object, array $old_data = [], array $new_data = [])
    {
        $difference     = array_diff_assoc($new_data, $old_data);
        $old_difference = array_diff_assoc($old_data, $new_data);
        $data           = $e->return_value;
        $arr            = array_keys($difference);

        if (!count($difference) || !$object->is_reserv) {
            $e->return_value = $data;
            return;
        }

        //针对于修改所做的操作
        if (!in_array('equipment', $arr)) {
            $equipment = $object->equipment;
            $user      = $old_data['user'];
            $updates   = Q("update[object={$equipment}][subject={$user}]");
            foreach ($updates as $update) {
                $data = json_decode($update->new_data, true);
                if ($data['record_id'] == $object->id) {
                    if (in_array('user', $arr)) {
                        $update->subject = $difference['user'];
                    }
                    if (in_array('dtstart', $arr)) {
                        $data['dtstart'] = Date::format($difference['dtstart']);
                    }
                    if (in_array('dtend', $arr)) {
                        $data['dtend'] = Date::format($difference['dtend']);
                    }
                    $update->new_data = json_encode($data, true);
                    $update->save();
                    break;
                }
            }
            $e->return_value = false;
            return;
        }

        //针对于添加所做的操作
        $delta            = [];
        $subject          = L('ME');
        $delta['subject'] = $subject;
        $delta['object']  = $difference['equipment'];
        $delta['action']  = 'reserv_equipment';
        if (in_array('dtstart', $arr)) {
            $difference['dtstart'] = Date::format($difference['dtstart']);
        }
        if (in_array('dtend', $arr)) {
            $difference['dtend'] = Date::format($difference['dtend']);
        }
        if (in_array('user', $arr)) {
            $delta['subject'] = $difference['user'];
        }
        $difference['record_id'] = $object->id;

        $delta['new_data'] = $difference;
        $delta['old_data'] = $old_difference;

        $key        = Misc::key((string) $delta['subject'], $delta['action'], (string) $object);
        $data[$key] = (array) $data[$key];

        Misc::array_merge_deep($data[$key], $delta);

        $e->return_value = $data;
    }

    public static function get_update_parameter($e, $object, array $old_data = [], array $new_data = [])
    {
        if ($object->name() != 'user' && $object->name() != 'equipment') {
            return;
        }
        $difference       = array_diff_assoc($new_data, $old_data);
        $old_difference   = array_diff_assoc($old_data, $new_data);
        $arr              = array_keys($difference);
        $data             = $e->return_value;
        $delta            = [];
        $subject          = L('ME');
        $delta['subject'] = $subject;
        $delta['object']  = $object;
        $delta['action']  = 'edit_reserv';
        if ($object->name() == 'user') {
            $keys = array_keys(EQ_Reserv::$user_reserv);
            if (!count(array_intersect($arr, $keys))) {
                return;
            }
        } elseif ($object->name() == 'equipment') {
            $keys = array_keys(EQ_Reserv::$equipment_reserv);
            if (!count(array_intersect($arr, $keys))) {
                return;
            }
            if (in_array('accept_reserv', $arr)) {
                $accept                      = $difference['accept_reserv'];
                $difference['accept_reserv'] = $accept ? '是' : '否';
            }
            if (in_array('reserv_require_training', $arr)) {
                $require                               = $difference['reserv_require_training'];
                $difference['reserv_require_training'] = $require ? '是' : '否';
            }
        } else {
            return;
        }
        $key               = Misc::key((string) $subject, $delta['action'], (string) $object);
        $data[$key]        = (array) $data[$key];
        $delta['new_data'] = $difference;
        $delta['old_data'] = $old_difference;
        Misc::array_merge_deep($data[$key], $delta);

        $e->return_value = $data;
    }

    public static $user_reserv = [
        'eq_miss_count'        => '预约爽约次数',
        'eq_overtime_count'    => '用户超时使用次数',
        'eq_late_count'        => '预约迟到次数',
        'eq_leave_early_count' => '预约早退次数',
    ];

    public static $equipment_reserv = [
        'accept_reserv'              => '是否需要预约',
        'reserv_require_training'    => '是否通过培训才能预约',
        'add_reserv_earliest_limit'  => '添加预约最早可提前',
        'add_reserv_latest_limit'    => '添加预约最晚可提前',
        'modify_reserv_latest_limit' => '修改预约最晚可提前',
        'delete_reserv_latest_limit' => '删除预约最晚可提前',
    ];

    public static $equipment_reserved = [
        'dtstart' => '开始时间',
        'dtend'   => '结束时间',
    ];

    public static function get_update_message($e, $update)
    {
        if ($update->object->name() != 'user' && $update->object->name() != 'equipment') {
            return;
        }
        $action = $update->action;
        if ($action != 'edit_reserv' && $action != 'reserv_equipment') {
            return;
        }
        $me       = L('ME');
        $subject  = $update->subject->name;
        $old_data = json_decode($update->old_data, true);
        $object   = $old_data['name'] ? $old_data['name'] : $update->object->name;
        if ($update->object->name() == 'user') {
            /*
            if ($me->id == $update->subject->id && $me->id == $update->object->id) {
            $subject = I18N::T('eq_reserv', '我');
            $object = I18N::T('eq_reserv', '自己');
            }
            elseif ($me->id == $update->subject->id) {
            $subject = I18N::T('eq_reserv', '我');
            }
            elseif ($me->id == $update->object->id) {
            $object = I18N::T('eq_reserv', '我');
            }
            else if ($update->object->id == $update->subject->id) {
            $subject = $object;
            }*/
            $config = 'eq_reserv.reserv.user.msg.model';
        } elseif ($update->object->name() == 'equipment') {
            /*
            if ($me->id == $update->subject->id) {
            $subject = I18N::T('eq_reserv', '我');
            }*/

            if ($action == 'edit_reserv') {
                $config = 'eq_reserv.reserv.equipment.msg.model';
            } else {
                $config = 'eq_reserv.equipment.reserved.msg';
            }
        } else {
            return;
        }

        $opt = Lab::get($config);
        $msg = I18N::T('eq_reserv', $opt['body'], [
            '%subject'   => URI::anchor($update->subject->url(), $subject, 'class="blue label"'),
            '%date'      => '<strong>' . Date::fuzzy($update->ctime, 'TRUE') . '</strong>',
            '%user'      => URI::anchor($update->object->url(), $object, 'class="blue label"'),
            '%equipment' => URI::anchor($update->object->url(), $object, 'class="blue label"'),
        ]);
        $e->return_value = $msg;
        return false;
    }

    public static function get_update_message_view($e, $update)
    {
        $action     = $update->action;
        $properties = [];
        if ($action != 'edit_reserv' && $action != 'reserv_equipment') {
            return;
        }
        if ($action == 'edit_reserv') {
            $user_keys      = array_keys(EQ_Reserv::$user_reserv);
            $equipment_keys = array_keys(EQ_Reserv::$equipment_reserv);
            $keys           = json_decode($update->new_data, true);
            $keys           = array_keys($keys);
            if (count(array_intersect($user_keys, $keys))) {
                $properties = EQ_Reserv::$user_reserv;
            }
            if (count(array_intersect($equipment_keys, $keys))) {
                $properties = EQ_Reserv::$equipment_reserv;
            }
        } elseif ($action == 'reserv_equipment') {
            $properties = EQ_Reserv::$equipment_reserved;
        }
        $e->return_value = V('eq_reserv:update/show_msg', ['update' => $update, 'properties' => $properties]);
    }
    /**
     *为仪器添加预约的状态
     */
    public static function equipment_status_tag($e, $equipment)
    {
        if ($equipment->accept_reserv) {
            $url = $equipment->url('reserv');
            $e->return_value .= '<a href="' . $url . '" class="prevent_default  status_tag status_tag_normal">' . I18N::HT('eq_reserv', '预约') . '</a> ';
        }
    }

    public static function get_equipments_updates_configs($e)
    {
        $configs        = $e->return_value;
        $reserv_configs = [
            'eq_reserv.reserv.equipment.msg.model',
            'eq_reserv.equipment.reserved.msg',
        ];
        $e->return_value = array_merge((array) $configs, $reserv_configs);
    }

    public static function get_people_updates_configs($e)
    {
        $configs        = $e->return_value;
        $reserv_configs = [
            'eq_reserv.reserv.user.msg.model',
        ];
        $e->return_value = array_merge((array) $configs, $reserv_configs);
    }

    public static function on_enumerate_user_perms($e, $user, $perms)
    {
        if (!$user->id) {
            return;
        }
        //取消现默认赋予给pi的权限
//        if (Q("$user<pi lab")->total_count()) {
//            $perms['查看负责实验室成员的预约情况'] = 'on';
//        }
    }

    public static function before_user_save_message($e, $user)
    {
        if (Q("eq_reserv[user={$user}]")->total_count()) {
            $e->return_value = I18N::T('eq_reserv', '该用户关联了相应的预约记录!');
            return false;
        }
    }

    static private function get_format_block($equipment, $time) {
        $block_day_cross = Config::get('eq_reserv.block_day_cross');
        $blocks = (array)$equipment->reserv_block_data;
        $year = date('Y', $time);
        $month = date('m', $time);
        $day = date('d', $time);

        $day_start = mktime(0, 0, 0, $month, $day, $year);
        $day_end   = $day_start + 86400;

        //系统设定的跨天的块进行拆分处理
        $temp_blocks = [];

        foreach ($blocks as $block) {
            $b = [
                'start'    => mktime($block['dtstart']['h'], $block['dtstart']['i'], 0, $month, $day, $year),
                'end'      => mktime($block['dtend']['h'], $block['dtend']['i'], 0, $month, $day, $year),
                'align'    => $block['align_time'],
                'interval' => $block['interval_time'],
            ];

            if ($b['start'] > $b['end']) {
                if(!$block_day_cross){
                    $nb = $b;
                    $nb['start'] = $day_start;
                    $temp_blocks[] = $nb;

                    $nb = $b;
                    $nb['end'] = $day_end;
                    $temp_blocks[] = $nb;
                }else{
                    $nb = $b;
                    $nb['end'] = $nb['end'] + 86400;
                    $temp_blocks[] = $nb;
                }
            }else {
                $temp_blocks[] = $b;
            }
        }

        $blocks = $temp_blocks;

        //计算非系统设定块外的系统默认块
        $default_blocks = [
            [
                'start'    => $day_start,
                'end'      => $day_end,
                'interval' => (int) $equipment->reserv_interval_time,
                'align'    => (int) $equipment->reserv_align_time,
            ],
        ];

        foreach ($blocks as $block) {
            $start = $block['start'];
            $end   = $block['end'];

            $temp_array = [];
            foreach ($default_blocks as $dblock) {
                if ($start > $dblock['start'] && $end < $dblock['end']) {
                    //如果block在dblock中间, 拆分为左右的两个block
                    $nb           = $dblock;
                    $nb['end']    = $start;
                    $temp_array[] = $nb;

                    $nb           = $dblock;
                    $nb['start']  = $end;
                    $temp_array[] = $nb;
                } elseif ($start > $dblock['start'] && $start < $dblock['end'] && $end >= $dblock['end']) {
                    //如果block截取dblock的后侧
                    !$block_day_cross ? $dblock['end'] = $start : '';
                    $temp_array[] = $dblock;
                }
                elseif ($end > $dblock['start'] && $end < $dblock['end'] && $start <= $dblock['start']) {
                    //如果block截取dblock的前侧
                    !$block_day_cross ? $dblock['start'] = $end : '';
                    $temp_array[] = $dblock;
                }
                elseif (!($start <= $dblock['start'] && $end >= $dblock['end'])) {
                    $temp_array[] = $dblock;
                }
            }
            $default_blocks = $temp_array;
        }

        $blocks = array_merge($blocks, $default_blocks);

        //进行块状匹配
        foreach ($blocks as $b) {
            if ($time >= $b['start'] && $time < $b['end']) {
                //如果时间在块内, start end 为块时间
                return $b;
            }
        }
    }

    public static function calendar_list_columns($e, $calendar, $columns, $form)
    {
        $pname = $calendar->parent->name();
        if ($pname == 'equipment' || ($pname == 'user' && $calendar->type == 'eq_incharge')) {
            unset($columns['name']);
            unset($columns['date']);
            unset($columns['organizer']);

            $root_group = Tag_Model::root('group');
            $group = O('tag_group', $form['eq_reserv_group']);
            if (!$group->id || !$group->root->id) {
                unset($group);
            }
            $sort_fields = Config::get('eq_reserv.eq_reserv.sortable_columns') ?: [];

            $columns['group'] = [
                'title'     => I18N::T('eq_reserv', '组织机构'),
                'sortable'  => in_array('group', $sort_fields),
                'invisible' => true,
                'filter'    => [
                    'form'  => V('eq_reserv:calendar_list/tables/filters/group', ['form' => $form]),
                    'value' => $group->id ? H($group->name) : null,
                    'field' => 'eq_reserv_group',
                ],
            ];

            $columns['organizer'] = [
                'title'    => I18N::T('eq_reserv', '预约者'),
                'sortable' => in_array('organizer', $sort_fields),
                'nowrap'   => true,
                'align'    => 'left',
                'filter'   => [
                    'form'  => V('eq_reserv:calendar_list/tables/filters/organizer', ['organizer' => $form['organizer']]),
                    'value' => H($form['organizer']),
                ],
            ];

            $equipment = O('equipment', $calendar->parent_id);
            $me = L('ME');
            if ($me->is_allowed_to('查看相关人员联系方式', $equipment)) {
                $columns['email'] = [
                    'title'    => I18N::T('eq_reserv', '邮箱'),
                    'sortable' => in_array('organizer', $sort_fields),
                    'nowrap'   => true,
                    'align'    => 'left',
                ];

                $columns['phone'] = [
                    'title'    => I18N::T('eq_reserv', '电话'),
                    'sortable' => in_array('organizer', $sort_fields),
                    'nowrap'   => true,
                    'align'    => 'left',
                ];
            }

            if ($pname != 'equipment') {
                $columns['equipment'] = [
                    'sortable' => true,
                    'title'    => I18N::T('eq_reserv', '预约仪器'),
                    'align'    => 'left',
                    'nowrap'   => true,
                ];

                $columns['equipment_ref'] = [
                    'title'    => I18N::T('eq_reserv', '仪器编号'),
                    'sortable' => in_array('equipment_ref', $sort_fields),
                    'nowrap'   => true,
                    'align'    => 'left',
                    'invisible' => true,
                    'filter'   => [
                        'form'  => V('eq_reserv:reservs_table/filters/equipment_ref', ['form' => $form]),
                        'value' => $form['equipment_ref'] ? H($form['equipment_ref']) : null,
                    ]
                ];
            }

            $columns['description'] = [
                'title'  => I18N::T('eq_reserv', '备注'),
                'align'  => 'left',
                'nowrap' => true,
            ];

            // BUG 4472 LIMS中没有课题组
            if (Module::is_installed('labs')) {
                $columns['lab'] = [
                    'title'     => I18N::T('eq_reserv', '实验室'),
                    'invisible' => true,
                    'filter'    => [
                        'form'  => V('eq_reserv:calendar_list/tables/filters/lab', ['form' => $form]),
                        'value' => H($form['lab']),
                    ],
                ];
            }

            $columns['status'] = [
                'title'    => I18N::T('eq_reserv', '状态'),
                'sortable' => in_array('status', $sort_fields),
                'align'    => 'left',
                'nowrap'   => true,
                'filter'   => [
                    'form'  => V('eq_reserv:calendar_list/tables/filters/status', ['form' => $form]),
                    'value' => H(EQ_Reserv_Model::$reserv_status[$form['reserv_status']]),
                    'field' => 'reserv_status',
                ],
            ];

            $columns['count'] = [
                'title'    => I18N::T('eq_reserv', '样品数'),
                'align'    => 'left',
                'nowrap'   => true,
            ];

            if ($form['dtstart'] || $form['dtend']) {
                $date_value = true;
            }

            $columns['date'] = [
                'title'    => I18N::T('eq_reserv', '时间'),
                'sortable' => in_array('date', $sort_fields),
                'align'    => 'left',
                'nowrap'   => true,
                'filter'   => [
                    'form'  => V('eq_reserv:calendar_list/tables/filters/date', ['form' => $form]),
                    'value' => $date_value,
                    'field' => 'dtstart,dtend',
                ],
            ];
        }
    }

    public static function calendar_list_row($e, $calendar, $component, $row)
    {
        $pname = $calendar->parent->name();
        if ($pname == 'equipment' || ($pname == 'user' && $calendar->type == 'eq_incharge')) {
            $parent = $component->calendar->parent;
            $cpname = $parent->name();
            if ($cpname == 'equipment') {
                $equipment = $parent;
                if (!$equipment->id) {
                    $e->return_value = false;
                    return false;
                }
                $row['equipment'] = V('eq_reserv:calendar_list/equipment', ['equipment' => $equipment]);
            } else {
                $row['equipment'] = I18N::HT('eq_reserv', '非预约时段');
            }
            $row['organizer']   = V('eq_reserv:calendar_list/organizer', ['component' => $component]);
            $row['email']   = V('eq_reserv:calendar_list/email', ['component' => $component]);
            $row['phone']   = V('eq_reserv:calendar_list/phone', ['component' => $component]);
            $row['description'] = V('eq_reserv:calendar_list/description', ['component' => $component]);
            $row['status']      = V('eq_reserv:calendar_list/status', ['component' => $component]);
            $row['count'] = V('eq_reserv:calendar_list/count', ['component' => $component]);
        }
    }

    public static function calendar_list_empty_message($e, $calendar)
    {
        $pname = $calendar->parent->name();
        if ($pname == 'equipment'
            || ($pname == 'user' && $calendar->type == 'eq_incharge')
        ) {
            return I18N::T('eq_reserv', '本周无预约!');
        }
    }

    public static function add_equipment_notification_config($e, $options)
    {
        $configs = [
            'notification.eq_reserv.user_confirm_reserv',
            'notification.eq_reserv.user_confirm_edit_reserv',
            'notification.eq_reserv.user_confirm_delete_reserv',
            'notification.eq_reserv.contact_confirm_reserv',
            'notification.eq_reserv.contact_confirm_edit_reserv',
            'notification.eq_reserv.contact_confirm_delete_reserv',
            'notification.eq_reserv.pi_confirm_reserv',
            'notification.eq_reserv.pi_confirm_edit_reserv',
            'notification.eq_reserv.pi_confirm_delete_reserv',
            //'notification.eq_reserv.member_late.to_pi',
            'notification.eq_reserv.overtime',
            'notification.eq_reserv.misstime',
            'notification.eq_reserv.overtime.self',
            'notification.eq_reserv.misstime.self',
            //'notification.eq_reserv.late.self',
            'notification.eq_reserv.out_of_service',
            'notification.eq_reserv.in_service',

        ];
        $e->return_value = array_merge((array) $e->return_value, $configs);
    }

    public static function equipments_add_logs($e, $type)
    {
        if (!$type) {
            return;
        }

        switch ($type) {
            case 'notification.out_of_service':
            case 'notification.in_service':
                $category = '状态修改';
                break;
            default:
                return false;
                break;
        }

        $e->return_value = $category;
        return false;
    }

    public static function is_check_overlap($e, $component)
    {
        $calendar = $component->calendar;
        $parent = $calendar->parent;
        $dtstart = $component->dtstart + 1;
        $dtend = $component->dtend - 1;

        if ($parent->name() == 'equipment') {
            /* 如果预约已经和别的预约冲突了，则给予提示 */
            if (Q("cal_component[calendar={$calendar}][id!={$component->id}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]")->total_count()) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '您预约的时段与其他预约时段冲突!'));
                $e->return_value = true;
                return false;
            }

            /* 如果预约会重复覆盖到其他的使用记录，则给予提示 */
            $reserv = O('eq_reserv', ['component' => $component]);
            if (Q("eq_record[equipment={$parent}][reserv_id>0][dtstart~dtend=$dtstart|dtstart~dtend=$dtend|dtstart=$dtstart~$dtend]:not($reserv<reserv eq_record)")->total_count()) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '您预约的时段与其他记录冲突!'));
                $e->return_value = true;
                return false;
            }
        }
    }

    private static $reserv            = null;
    private static $last_reserv_mtime = 0;
    public static function device_computer_keep_alive($e, $device)
    {
        if (!$device->is_ready) {
            return;
        }

        $now = Date::time();
        if (self::$last_reserv_mtime + 30 >= $now) {
            return;
        }
        self::$last_reserv_mtime = $now;

        $agent = $device->agent(0);
        if (!$agent) {
            return;
        }

        $equipment = $agent->object;
        if (!$equipment->id) {
            return;
        }

        // 找到当前的使用记录
        $record = Q("eq_record[dtend=0][dtstart<=$now][equipment=$equipment]:limit(1)")->current();
        if ($record->id && $record->reserv->id) {
            if (is_null(self::$reserv)
                || self::$reserv->dtstart != $record->reserv->dtstart
                || self::$reserv->dtend != $record->reserv->dtend) {
                self::$reserv = (object) [
                    'dtstart' => $record->reserv->dtstart,
                    'dtend'   => $record->reserv->dtend,
                ];

                $device->post_command('update_reserv', [
                    'dtstart' => self::$reserv->dtstart,
                    'dtend'   => self::$reserv->dtend,
                ]);
            }
        }
    }

    public static function calendar_export_columns_print($e, $component, $type)
    {
        $calendar = $component->calendar;
        if (
            ($calendar->parent->name() == 'equipment' && $calendar->type == 'eq_reserv')
            ||
            ($calendar->parent->name() == 'user' && $calendar->type == 'eq_incharge')
        ) {
            $equipment = $calendar->parent;

            switch ($type) {
                case 'equipment':
                    $return = H($equipment->name);
                    break;
                case 'eq_ref_no':
                    $return = H($equipment->ref_no);
                    break;
                case 'eq_cf_id':
                    $return = $equipment->id;
                    break;
                case 'eq_group':
                    $return = H($equipment->group->name);
                    break;
                case 'organizer':
                    $return = H($component->organizer->name);
                    break;
                case 'group':
                    $return = H($component->organizer->group->name);
                    break;
                case 'lab':
                    $eq_reserv = O('eq_reserv', ['component' => $component]);
                    $lab       = $eq_reserv->project->lab->id ?
                    $eq_reserv->project->lab :
                    Q("{$component->organizer} lab")->current();
                    $return = H($lab->name);
                    break;
                case 'time':
                    $return = Date::format($component->dtstart, 'Y/m/d H:i:s') . ' - ' . Date::format($component->dtend, 'Y/m/d H:i:s');
                    break;
                case 'duration':
                    $return = I18N::T('eq_reserv', '%duration小时', [
                        '%duration' => round(($component->dtend - $component->dtstart) / 3600, 2)]);
                    break;
                case 'name':
                    $return = H($component->name);
                    break;
                case 'phone':
                    $return = H($component->organizer->phone);
                    break;
                case 'reserv_type':
                    $ctype = $component->type;
                    if ($ctype == Cal_Component_Model::TYPE_VEVENT) {
                        $return = I18N::T('eq_reserv', '预约');
                    } elseif ($ctype == Cal_Component_Model::TYPE_VFREEBUSY) {
                        $return = I18N::T('eq_reserv', '非预约时段');
                    }
                    break;
                case 'description':
                    $return = H($component->description);
                    break;
                default:
            }
        }
        $e->return_value = $return;
    }

    public static function calendar_export_columns_csv($e, $component, $type)
    {
        $calendar = $component->calendar;
        if (
            ($calendar->parent->name() == 'equipment' && $calendar->type == 'eq_reserv')
            ||
            ($calendar->parent->name() == 'user' && $calendar->type == 'eq_incharge')
        ) {
            $equipment = $calendar->parent;

            switch ($type) {
                case 'equipment':
                    $return = $equipment->name;
                    break;
                case 'eq_ref_no':
                    $return = $equipment->ref_no;
                    break;
                case 'eq_cf_id':
                    $return = $equipment->id;
                    break;
                case 'eq_group':
                    $return = $equipment->group->name;
                    break;
                case 'organizer':
                    $return = $component->organizer->name;
                    break;
                case 'group':
                    $return = $component->organizer->group->name;
                    break;
                case 'lab':
                    $eq_reserv = O('eq_reserv', ['component' => $component]);
                    $lab       = $eq_reserv->project->lab->id ?
                    $eq_reserv->project->lab :
                    Q("{$component->organizer} lab")->current();
                    $return = H($lab->name);
                    break;
                case 'time':
                    $return = Date::format($component->dtstart, 'Y/m/d H:i:s') . ' - ' . Date::format($component->dtend, 'Y/m/d H:i:s');
                    break;
                case 'duration':
                    $return = I18N::T('eq_reserv', '%duration小时', [
                        '%duration' => round(($component->dtend - $component->dtstart) / 3600, 2)]);
                    break;
                case 'name':
                    $return = $component->name;
                    break;
                case 'phone':
                    $return = H($component->organizer->phone);
                    break;
                case 'reserv_type':
                    $ctype = $component->type;
                    if ($ctype == Cal_Component_Model::TYPE_VEVENT) {
                        $return = I18N::T('eq_reserv', '预约');
                    } elseif ($ctype == Cal_Component_Model::TYPE_VFREEBUSY) {
                        $return = I18N::T('eq_reserv', '非预约时段');
                    }
                    break;
                case 'description':
                    $return = $component->description;
                    break;
                default:
            }
        }
    }

    public static function empty_eq_reserv_message($e, $calendar)
    {
        if ($calendar->type == 'eq_reserv' && $calendar->parent_name == 'equipment') {
            $e->return_value = I18N::T('eq_reserv', '没有该仪器的预约记录');
            return true;
        } elseif ($calendar->type == 'eq_incharge' && $calendar->parent_name == 'user') {
            $e->return_value = I18N::T('eq_reserv', '您负责的仪器没有符合条件的预约记录');
            return true;
        }
    }
    //bug3463, 尝试创建仪器预约，记录日志
    public static function get_equipment_calendar_log($e, $form, $component, $calendar)
    {
        if ($calendar->id && $calendar->parent_name == 'equipment'
            && $calendar->type == 'eq_reserv' && $calendar->parent->id) {
            $type_array = [
                0 => '预约',
                3 => '非预约',
            ];
            $equipment   = $calendar->parent;
            $msg         = '';
            $me          = L('ME');
            $reserv_type = $type_array[$component->type];
            if ($component->id) {
                $msg = strtr('[eq_reserv]%user_name[%user_id] 于 %date 尝试修改仪器 %equipment_name[%equipment_id] 的预约[%component_id], 预约类型 %reserv_type, 预约开始时间: %dtstart, 结束时间: %dtend', [
                    '%user_name'      => $me->name,
                    '%user_id'        => $me->id,
                    '%date'           => Date::format(Date::time()),
                    '%equipment_name' => $equipment->name,
                    '%equipment_id'   => $equipment->id,
                    '%component_id'   => $component->id,
                    '%reserv_type'    => $reserv_type,
                    '%dtstart'        => Date::format($component->dtstart),
                    '%dtend'          => Date::format($component->dtend),
                ]);
            } else {
                $msg = strtr('[eq_reserv]%user_name[%user_id] 于 %date 尝试创建新的仪器 %equipment_name[%equipment_id] 的预约, 预约类型 %reserv_type, 预约开始时间: %dtstart, 结束时间: %dtend', [
                    '%user_name'      => $me->name,
                    '%user_id'        => $me->id,
                    '%date'           => Date::format(Date::time()),
                    '%equipment_name' => $equipment->name,
                    '%equipment_id'   => $equipment->id,
                    '%reserv_type'    => $reserv_type,
                    '%dtstart'        => Date::format($component->dtstart),
                    '%dtend'          => Date::format($component->dtend),
                ]);
            }
            $e->return_value = $msg;
        }
        return true;
    }

    public static function default_extra_setting_view($e, $uniqid, $field)
    {
        $e->return_value = (string) V('eq_reserv:extra/setting/' . $uniqid, ['field' => $field]);
        return false;
    }

    public static function is_reserv_locked($e, $reserv)
    {

        //有可能传递无id的reserv
        if ($reserv->id) {
            $dtend     = $reserv->component->dtend;
            $lock_time = Lab::get('transaction_locked_deadline');

            if ($dtend < $lock_time) {
                $e->return_value = true;
                return false;
            } else {
                $e->return_value = false;
                return false;
            }
        }
    }

    /*
     *根据RQ133761, 与付建坤商量决定判断预约不根据预约和使用记录是否关联, 而是按照预约和使用记录的相对时间
     *根据预约返回预约的状态
     *$reserv: 需要判断状态的预约
     */
    public static function get_status($e, $reserv, $params)
    {
        // true: reserv_status
        // false: record_status
        $refetch  = $params[0];
        $record   = $params[1];
        $new_data = $params[2];
        $now      = Date::time();

        try {
            if ($record->flag == EQ_Reserv_Model::MISSED && (isset($new_data['status']) || isset($new_data['is_locked']))) {
                $e->return_value = EQ_Reserv_Model::MISSED;
                throw new Error_Exception;
            }
            //基础属性获取
            $equipment          = $reserv->equipment;
            $user               = $reserv->user;
            $dtstart            = $reserv->dtstart;
            $dtend              = $reserv->dtend;
            $accept_leave_early = $equipment->accept_leave_early;
            $leave_early_time = $dtend - $equipment->allow_leave_early_time;
            $accept_late = $equipment->accept_late;
            $late_time = $equipment->allow_late_time + $dtstart;
            $accept_overtime = $equipment->accept_overtime;
            $over_time = $equipment->allow_over_time + $dtend;

            //如果预约结束时间为当前时间之后
            //不予返回状态
            if ($reserv->dtend > Date::time()) {
                $e->return_value = EQ_Reserv_Model::PENDING;
                throw new Error_Exception;
            }

            // 20181934 天津大学机主设置非预约时间段生成了爽约
            if ($reserv->component->type == Cal_Component_Model::TYPE_VFREEBUSY) {
                $e->return_value = EQ_Reserv_Model::NORMAL;
                throw new Error_Exception;
            }

            // 兰州大学需要未控制仪器且未设置自动生成记录的，不爽约.
            if ($st = Event::trigger('eq_reserv.status_render',$equipment,$reserv)) {
                $e->return_value = $st;
                throw new Error_Exception;
            }

            //不控制的仪器为正常使用
            //BUG 9171
            if (!Config::get('eq_reserv.nocontrol_auto_create_record', false) //RQ161221配置化不控制仪器是否自动生成爽约记录
                 && (!$equipment->control_mode || $equipment->control_mode == 'nocontrol')) {
                $e->return_value = EQ_Reserv_Model::NORMAL;
                throw new Error_Exception;
            }

            //如果不为pending
            //并且不为refetch
            //直接返回当前状态
            if ($reserv->status != EQ_Reserv_Model::PENDING && !$refetch) {
                $e->return_value = $reserv->status;
                throw new Error_Exception;
            }

            $connected_records = Q("eq_record[reserv={$reserv}][dtend>0]");

            $connected_records_count = Q("eq_record[reserv={$reserv}][dtend>0]")->total_count();

            $latest_record = Q("eq_record[reserv={$reserv}][dtend>0]:limit(1):sort(dtend D)")->current();

            if ($record->id && $record->flag == EQ_Reserv_Model::MISSED && (isset($new_data['status']) || isset($new_data['is_locked']))) {
                $e->return_value = EQ_Reserv_Model::MISSED;
                throw new Error_Exception;
            }

            if ($record->id && $record->dtend > $latest_record->dtend) {
                $latest_record = $record;
            }

            $record_dtend = $new_data['dtend'] ?: $latest_record->dtend;

            $is_latest_record = $latest_record->id ? $record->id == $latest_record->id : true;

            $is_first_record = $connected_records->current()->id ? $record->id == $connected_records->current()->id : true;

            //具有关联的使用记录
            //以关联的使用记录为比对对象
            if ($connected_records->total_count() || ($record->id && $record->reserv->id)) {
                $r           = $connected_records->current()->id ? $connected_records->current() : $record;
                $min_dtstart = $r->dtstart;
                $max_dtend   = $r->dtend;
                //获取起始时间
                //获取结束时间

                $clear_leave_early = false;

                $first_record = $connected_records->current();

                foreach ($connected_records as $r) {
                    $clear_leave_early = $clear_leave_early || $r->clear_leave_early;
                    $min_dtstart       = min($min_dtstart, $r->dtstart);
                    $max_dtend         = max($max_dtend, $r->dtend, $record->dtend);
                }

                $min_dtstart = ($new_data['dtstart'] ? $new_data['dtstart'] : $min_dtstart);
                $max_dtend   = ($new_data['dtend'] ? $new_data['dtend'] : $max_dtend);

                if ($accept_late) {
                    //迟到
                    if ($min_dtstart > $late_time && $is_first_record) {
                        if ($max_dtend > $dtend && $is_latest_record && $record_dtend > $over_time && $accept_overtime) {
                            $e->return_value = EQ_Reserv_Model::LATE_OVERTIME;
                        } elseif ($accept_leave_early && $is_latest_record && $record_dtend < $leave_early_time) {
                            $e->return_value = EQ_Reserv_Model::LATE_LEAVE_EARLY;
                        } else {
                            $e->return_value = EQ_Reserv_Model::LATE;
                        }
                    }
                    else {
                        if ($max_dtend > $dtend && $is_latest_record && $record_dtend > $over_time && $accept_overtime) {
                            $e->return_value = EQ_Reserv_Model::OVERTIME;
                        } elseif ($accept_leave_early && $is_latest_record && $record_dtend < $leave_early_time) {
                            $e->return_value = EQ_Reserv_Model::LEAVE_EARLY;
                        } else {
                            $e->return_value = EQ_Reserv_Model::NORMAL;
                        }
                    }
                } else {
                    //不迟到
                    //如果超时
                    if ($max_dtend > $dtend && $record_dtend > $over_time && $accept_overtime) {
                        $e->return_value = EQ_Reserv_Model::OVERTIME;
                    } elseif ($accept_leave_early && $is_latest_record && $record_dtend < $leave_early_time) {
                        $e->return_value = EQ_Reserv_Model::LEAVE_EARLY;
                    }
                    //如果不超时 //正常
                    else {
                        $e->return_value = EQ_Reserv_Model::NORMAL;
                    }
                }
                if ($refetch) {
                    $lated       = 0;
                    $out_time    = 0;
                    $leave_early = 0;

                    if ($min_dtstart > $late_time && $accept_late) {
                        $lated = 1;
                    }
                    if ($max_dtend > $dtend && $record_dtend > $over_time && $accept_overtime) {
                        $out_time = 1;
                    } elseif ($max_dtend < $leave_early_time && $accept_leave_early && !$clear_leave_early) {
                        $leave_early = 1;
                    }
                    //迟到
                    if ($lated) {
                        $e->return_value = EQ_Reserv_Model::LATE;
                    }

                    if ($out_time) {
                        if ($lated) {
                            $e->return_value = EQ_Reserv_Model::LATE_OVERTIME;
                        } else {
                            $e->return_value = EQ_Reserv_Model::OVERTIME;
                        }
                    }

                    if ($leave_early) {
                        if ($lated) {
                            $e->return_value = EQ_Reserv_Model::LATE_LEAVE_EARLY;
                        } else {
                            $e->return_value = EQ_Reserv_Model::LEAVE_EARLY;
                        }
                    }
                }
            } else {
                $using_record = Q("eq_record[user={$user}][equipment={$equipment}][dtstart<{$dtend}][dtend=0]:limit(1)")->current();

                //使用中的使用记录
                //只判断是否迟到
                if ($using_record->id) {
                    //允许迟到
                    if ($accept_late) {
                        //迟到
                        if ($using_record->dtstart > $late_time) {
                            if ($now > $dtend && $accept_overtime && $using_record->dtend > $over_time) $e->return_value = EQ_Reserv_Model::LATE_OVERTIME;
                            else $e->return_value = EQ_Reserv_Model::LATE;
                        }
                        else {
                            //不迟到判断当前时间
                            if ($now > $dtend && $accept_overtime && $using_record->dtend > $over_time)  $e->return_value = EQ_Reserv_Model::OVERTIME;
                            else $e->return_value = EQ_Reserv_Model::NORMAL;
                        }
                    } else {
                        //不迟到
                        if ($now > $dtend && $accept_overtime && $using_record->dtend > $over_time) $e->return_value = EQ_Reserv_Model::OVERTIME;
                        else $e->return_value = EQ_Reserv_Model::NORMAL;
                    }
                } else {
                    //无使用中使用记录
                    $records = Q("eq_record[user={$user}][equipment={$equipment}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}|dtend={$dtstart}~{$dtend}][reserv_id=0]:sort(dtstart)");

                    if ($records->total_count()) {

                        //迟到
                        //不存在迟到时间之前的使用记录
                        //则迟到
                        $late = !(bool) $records->find("[dtstart<{$late_time}]")->total_count();
                        //超时
                        $overtime = (bool) $records->find("[dtend>{$over_time}]")->total_count();

                        if ($accept_late) {
                            if (!$late && !$overtime) $e->return_value = EQ_Reserv_Model::NORMAL;
                            elseif ($late && $overtime) $e->return_value = EQ_Reserv_Model::LATE_OVERTIME;
                            elseif ($late && !$overtime) $e->return_value = EQ_Reserv_Model::LATE;
                            else $e->return_value = EQ_Reserv_Model::OVERTIME;
                        }
                        elseif ($accept_overtime) {
                            if ($overtime) $e->return_value = EQ_Reserv_Model::OVERTIME;
                            else $e->return_value = EQ_Reserv_Model::NORMAL;
                        } else {
                            $e->return_value = EQ_Reserv_Model::NORMAL;
                        }
                    } else {
                        //无使用记录

                        //无论是否允许使用, 当前预约都无关联, 所以状态应该为 MISSED 未使用

                        // 临时修改，如果预约有关联的使用记录(反向，reserv表没有record_id，所以需要通过预约的时间查找对应的使用记录)，就不算爽约
                        $wrecords = Q("eq_record[user={$user}][equipment={$equipment}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}|dtend={$dtstart}~{$dtend}]:sort(dtstart)")->current();
                        if (!$wrecords->id) {
                            $e->return_value = EQ_Reserv_Model::MISSED;
                        }
                    }
                }
            }

            $return_value = $e->return_value;
            $ban_status_settings = $equipment->ban_status_settings;

            if ($ban_status_settings) {
                switch ($return_value) {
                    case EQ_Reserv_Model::LATE_OVERTIME:
                         if (in_array(EQ_Reserv_Model::LATE, $ban_status_settings) && !in_array(EQ_Reserv_Model::OVERTIME, $ban_status_settings)) {
                            $e->return_value = EQ_Reserv_Model::LATE;
                         } else if (!in_array(EQ_Reserv_Model::LATE, $ban_status_settings) && in_array(EQ_Reserv_Model::OVERTIME, $ban_status_settings)) {
                            $e->return_value = EQ_Reserv_Model::OVERTIME;
                         } else if (!in_array(EQ_Reserv_Model::LATE, $ban_status_settings) && !in_array(EQ_Reserv_Model::OVERTIME, $ban_status_settings)) {
                            $e->return_value = EQ_Reserv_Model::NORMAL;
                         }
                         break;
                    case EQ_Reserv_Model::LATE_LEAVE_EARLY:   
                         if (in_array(EQ_Reserv_Model::LATE, $ban_status_settings) && !in_array(EQ_Reserv_Model::LEAVE_EARLY, $ban_status_settings)) {
                            $e->return_value = EQ_Reserv_Model::LATE;
                         } else if (!in_array(EQ_Reserv_Model::LATE, $ban_status_settings) && in_array(EQ_Reserv_Model::LEAVE_EARLY, $ban_status_settings)) {
                            $e->return_value = EQ_Reserv_Model::LEAVE_EARLY;
                         } else if (!in_array(EQ_Reserv_Model::LATE, $ban_status_settings) && !in_array(EQ_Reserv_Model::LEAVE_EARLY, $ban_status_settings)) {
                            $e->return_value = EQ_Reserv_Model::NORMAL;
                         }
                         break;
                    default:
                        if (!in_array($return_value, $ban_status_settings)) {
                            $e->return_value = EQ_Reserv_Model::NORMAL;
                        }
                }                
            } else {
                $e->return_value = EQ_Reserv_Model::NORMAL;
            }

            throw new Error_Exception;
        } catch (Error_Exception $e) {
            return false;
        }
    }

    public static function insert_component_title($e, $calendar)
    {
        try {
            //没有设定calendar->id，不予判断
            if (!$calendar->id) {
                throw new Error_Exception;
            }

            //calendar类型不为eq_reserv下可判断的类型
            if (!($calendar->type == 'eq_reserv' || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user'))) {
                throw new Error_Exception;
            }
        } catch (Error_Exception $e) {
            //进行判断，有问题跳出
            return;
        }

        $e->return_value = I18N::T('eq_reserv', '添加使用预约');
    }

    public static function edit_component_title($e, $calendar)
    {
        try {
            //没有设定calendar->id，不予判断
            if (!$calendar->id) {
                throw new Error_Exception;
            }

            //calendar类型不为eq_reserv下可判断的类型
            if (!($calendar->type == 'eq_reserv' || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user'))) {
                throw new Error_Exception;
            }
        } catch (Error_Exception $e) {
            //进行判断，有问题跳出
            return;
        }

        $e->return_value = I18N::T('eq_reserv', '修改预约');
    }

    public static function select_view_component_title($e, $calendar)
    {
        try {
            //没有设定calendar->id，不予判断
            if (!$calendar->id) {
                throw new Error_Exception;
            }

            //calendar类型不为eq_reserv下可判断的类型
            if (!($calendar->type == 'eq_reserv' || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user'))) {
                throw new Error_Exception;
            }
        } catch (Error_Exception $e) {
            //进行判断，有问题跳出
            return;
        }

        $e->return_value = I18N::T('eq_reserv', '查看预约');
    }

    public static function extra_check_field_title($e, $field, $extra)
    {
        if ($extra->type == 'eq_reserv') {

            //存储系统默认locale
            $default_locale = $_SESSION['system.locale'];

            $self_fields = Config::get('extra.equipment.eq_reserv');

            foreach ($self_fields as $category => $fields) {
                unset($fields['#i18n_module']);
                foreach ($fields as $f) {
                    $_title = $f['title'];

                    foreach (Config::get('system.locales') as $locale => $name) {
                        //清除自身模块I18N
                        I18N::clear_cache('eq_sample');
                        //设定locale
                        I18N::set_locale($locale);

                        //如果I18N后发现现有翻译已存在该传入title, 发现问题 break;
                        if (I18N::T('eq_sample', $_title) == $title) {
                            //纠正
                            Config::set('system.locale', $default_locale);
                            I18N::set_locale($default_locale);
                            $e->return_value = true;
                            return false;
                        }
                    }
                }
            }

            //纠正
            Config::set('system.locale', $default_locale);
            I18N::set_locale($default_locale);
        }
    }

    public static function record_get_date($e, $record)
    {
        //如果为爽约, 清空date显示
        if ($record->is_missed) {
            $e->return_value = null;
            return false;
        }
    }

    public static function record_get_samples($e, $record)
    {
        //如果为爽约, 清空samples显示
        if ($record->is_missed) {
            $e->return_value = 0;
            return false;
        }
    }

    public static function record_get_duration($e, $record)
    {
        //如果为爽约, 清空duration
        if ($record->is_missed) {
            $e->return_value = 0;
            return false;
        }
    }

    public static function record_get_total_time($e, $record)
    {
        //如果为爽约, 清空total_time
        if ($record->is_missed) {
            $e->return_value = 0;
            return false;
        }
    }

    public static function record_cannot_lock_samples($e, $record)
    {
        if ($record->is_missed) {
            $e->return_value = true;
            return false;
        }
    }

    public static function on_user_disconnect_lab($e, $user, $lab)
    {
        $user->delete_reserv($lab);
    }

    public static function pending_count($e, $user)
    {
        if (!$user->id) {
            return;
        }

        $now = Date::time();

        $reserv = Q("eq_reserv[dtend>$now][user=$user]")->total_count();

        $e->return_value = $reserv;
    }

    static function calendar_extra_export_columns($e, $valid_columns, $form_token) {
        //在单台仪器的送样、预约、使用记录的导出和打印字段选择中，
        //增加自定义表单字段（默认不勾选），
        //可在导出/ 打印预约记录、送样记录、使用记录时一并导出/ 打印；
        if ( $_SESSION[$form_token] ) {
            $setting = O('extra',['object_name'=>'equipment','object_id'=> $_SESSION[$form_token]['equipment_id'],
                'type'=>'eq_reserv']);
            if($setting->id){
                $valid_columns[-4] = '自定义表单';
                //获取自定义表单
                $extra = json_decode($setting->params_json, TRUE);
                foreach ($extra as $key => $fields) {
                    foreach ($fields as $name => $field) {
                        if ($name != 'count' && $name != 'description')
                            $valid_columns['extra_setting_'.$name] = $field['title'];
                    }
                }
            }
        }
        $e->return_value = $valid_columns;
        return TRUE;
    }
    static function calendar_export_list_csv($e, $sample, $data, $valid_columns) {
        $setting = O('extra',['object'=>$sample->equipment,'type'=>'eq_reserv']);
        if ($e->return_value) $data = $e->return_value;
        if($setting->id){
            $extra = json_decode($setting->params_json, TRUE);
            $extra_value = @json_decode(O('extra_value', ['object' => $sample])->values_json, TRUE) ?? [];
            foreach ($extra as $key => $fields) {
                foreach ($fields as $name => $field) {
                    if (array_key_exists('extra_setting_'.$name, $valid_columns))
                    {
                        switch ($field['type']) {
                            case Extra_Model::TYPE_CHECKBOX:
                                $value = [];
                                foreach ($extra_value[$name] as $key => $item ) {
                                    if($item == 'on'){
                                        $value[] = $key;
                                    }
                                }
                                $data[] = implode(",", $value)?:'--';
                                break;
                            case Extra_Model::TYPE_RANGE:
                                $data[] = implode("~", $extra_value[$name])?:'--';
                                break;
                            case Extra_Model::TYPE_DATETIME:
                                $data[] = $extra_value[$name] ? date('Y-m-d H:i:s',$extra_value[$name]) : '--';
                                break;
                            case Extra_Model::TYPE_SELECT:
                                $data[] = $extra_value[$name] != -1?$extra_value[$name]:'--';
                                break;
                            default:
                                $data[] = $extra_value[$name]?:'--';
                        }
                    }
                }
            }
        }
        $e->return_value = $data;
    }
    static function eq_reserv_export_csv($e, $component, $data, $valid_columns) {
        $reserv = O('eq_reserv', ['component'=>$component]);

        $setting = O('extra',['object'=>$reserv->equipment,'type'=>'eq_reserv']);
        if ($e->return_value) $data = $e->return_value;
        if($setting->id){
            $extra = json_decode($setting->params_json, TRUE);
            $extra_value = @json_decode(O('extra_value', ['object' => $reserv])->values_json, TRUE) ?? [];
            foreach ($extra as $key => $fields) {
                foreach ($fields as $name => $field) {
                    if (array_key_exists('extra_setting_'.$name, $valid_columns)){
                        switch ($field['type']) {
                            case Extra_Model::TYPE_CHECKBOX:
                                $value = [];
                                foreach ($extra_value[$name] as $key => $item ) {
                                    if($item == 'on'){
                                        $value[] = $key;
                                    }
                                }
                                $data[] = implode(",", $value)?:'--';
                                break;
                            case Extra_Model::TYPE_RANGE:
                                $data[] = implode("~", $extra_value[$name])?:'--';
                                break;
                            case Extra_Model::TYPE_DATETIME:
                                $data[] = date('Y-m-d H:i:s',$extra_value[$name])?:'--';
                                break;
                            case Extra_Model::TYPE_SELECT:
                                $data[] = $extra_value[$name] != -1?$extra_value[$name]:'--';
                                break;
                            default:
                                $data[] = $extra_value[$name]?:'--';
                        }
                    }

                }
            }
        }
        $e->return_value = $data;
    }

    public static function record_edit_view($e, $record, $form, $sections)
    {
        $reserv = $record->reserv;
        if($reserv->id){
            $sections[] = V('eq_reserv:edit/record_section', ['record' => $record, 'form' => $form]);
        }
    }
    
    static function cache_reserv_log($key,$step,$form=[]){
		$cache = Cache::factory('redis');
        $value = $cache->get($key);
        if($value) $value = json_decode($value,true);
        $remote_addr = $_SERVER['REMOTE_ADDR'] ?: '--';
        $value[$step] = array_merge(['time'=>time(),'uid'=>L('ME')->id,'remote_addr'=>$remote_addr],(array)$form);
		$cache->set($key,json_encode($value),3600);
	}

    static function flush($token){
        $cache = Cache::factory('redis');
        $value = $cache->get($token);
        if($value) $value = json_decode($value,true);

        //先获取基础信息，便于后期日志查询
        $equipment = O('equipment');
        $user = O('user');
        $dtstart = $dtend = $ctime = 0;

        foreach($value as $step => $params){
            // $params = json_decode($params,true);
            if(!$equipment->id && isset($params['eqid'])) $equipment = O('equipment',$params['eqid']);
            if(!$equipment->id && isset($params['calendar_id'])) $equipment = O('calendar',$params['calendar_id'])->parent;
            if(!$user->id && isset($params['uid'])) $user = O('user',$params['uid']);
            if(!$dtstart && isset($params['dtstart'])) $dtstart = $params['dtstart'];
            if(!$dtend && isset($params['dtend'])) $dtend = $params['dtend'];
            if(!$ctime && isset($params['ctime'])) $ctime = $params['ctime'];
        }

        foreach($value as $step => $params){
            //存入数据库，
            $log = O('reserv_log');
            $log->form_token = $token;
            $log->equipment = $equipment;
            $log->user = $user;
            $log->step = $step;
            $log->dtend = $dtend;
            $log->dtstart = $dtstart;
            $log->ctime = $ctime;
            $log->form = json_encode($params,JSON_UNESCAPED_UNICODE);
            $log->save();
        }

        $cache->remove($token);

    }

}

