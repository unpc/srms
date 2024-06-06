<?php

class Index_Controller extends Layout_Controller {

    public function entries($tab = '') {
        $me = L("ME");

        if(!$me->id){
            URI::redirect('error/401');
        }
        $this->layout->body = V('body');
        $this->layout->title = '预约记录';
        $tabs = Widget::factory("tabs");
        $allow_tabs = [];

        if ($me->is_allowed_to('列表仪器预约', Q("$me lab")->current())) {
            $tabs
                ->add_tab('lab', [
                    'url'   => URI::url('!eq_reserv/index/entries.lab'),
                    'title' => I18N::T('eq_reserv', '组内预约记录'),
                ]);
            !$tab && $tab = 'lab';
            $allow_tabs[] = 'lab';
            Event::bind('equipment.reservs.entries.view.content', 'EQ_Reserv::index_lab_content', 0, 'lab');
        }
        if (Q("$me<incharge equipment")->total_count() > 0) {
            $tabs
                ->add_tab('incharge', [
                    'url'=>URI::url('!eq_reserv/index/entries.incharge'),
                    'title'=>I18N::T('eq_reserv', '您负责的所有仪器的预约记录'),
                ]);
            !$tab && $tab = 'incharge';
            $allow_tabs[] = 'incharge';
            Event::bind('equipment.reservs.entries.view.content', 'EQ_Reserv::reserv_primary_tab_content', 100, 'incharge');
        }
        if ($me->access('添加/修改下属机构的仪器')) {
            $tabs
                ->add_tab('group', [
                    'url'=>URI::url('!eq_reserv/index/entries.group'),
                    'title'=>I18N::T('eq_reserv', '下属机构所有仪器的预约记录'),
                ]);
            !$tab && $tab = 'group';
            $allow_tabs[] = 'group';
            Event::bind('equipment.reservs.entries.view.content', 'EQ_Reserv::reserv_all_primary_tab_content', 100, 'group');
        }

        if ($me->access('管理所有内容')) {
            $tabs
                ->add_tab('all', [
                    'url'=>URI::url('!eq_reserv/index/entries.all'),
                    'title'=>I18N::T('eq_reserv', '所有仪器的预约记录'),
                ]);
            !$tab && $tab = 'all';
            $allow_tabs[] = 'all';
            Event::bind('equipment.reservs.entries.view.content', 'EQ_Reserv::reserv_all_primary_tab_content', 100, 'all');
        }

        if (!in_array($tab, $allow_tabs)) {
            URI::redirect('error/401');
        }

        switch ($tab) {
            case "lab":
                $tabs->lab = Q("$me lab")->current();
                break;
            case "incharge":;break;
            case "group":        
                $tabs->group = $me->group;
                break;
            case "all":;break;
        }

        $tabs
            ->tab_event('equipment.reservs.entries.view.tab')
            ->content_event('equipment.reservs.entries.view.content')
            ->tool_event('equipment.reservs.entries.view.tool_box')
            ->select($tab);

        $this->layout->body->primary_tabs = $tabs;

    }

    public function me() {
       $me = L('ME');
       $user = O("user",$me->id);
       if (!$user->id) {
            URI::redirect('error/401');
       }
       $this->layout->body = V('body');
       $this->layout->body->primary_tabs = Widget::factory('tabs');
       $this->layout->body->primary_tabs->user = $user;
       EQ_Reserv::index_profile_content(null, $this->layout->body->primary_tabs);
    }

    public function export() {

        $form = Input::form();

        $form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $new_form + $old_form;
        $form['dtstart'] = $old_form['dtstart'];
        $form['dtend'] = $old_form['dtend'];

        $calendar = O('calendar', $form['calendar_id']);

        //错误传值
        if (!$calendar->id || !in_array($form['type'], ['print', 'csv'])) URI::redirect('error/401');

        $dtstart = $form['dtstart'];
        $dtend = $form['dtend'];

        if (!$dtstart) {
            $dtstart = $form['date'] ? : Date::time();
            $date = getdate($dtstart);
            $dtstart = mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']);
            $dtend = $dtstart + 604800 - 1;
        }

        $components = Q("cal_component[calendar={$calendar}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]");

        $new_selector = Event::trigger('calendar.components.get', $calendar, $components, $dtstart, $dtend, 0, $form);
        $new_components = Q($new_selector);
        if ($new_components) {
            $components = $new_components;
        }


        $valid_columns = Config::get('calendar.export_columns.eq_reserv');
        $visible_columns = $form['columns'];

        foreach ($valid_columns as $p => $p_name ) {
            if (!isset($visible_columns[$p])) {
                unset($valid_columns[$p]);
            }
        }

        return call_user_func_array([$this, '_export_'. $form['type']], [$components, $valid_columns, $form]);
    }

    private function _export_print($components, $columns, $form) {
        $calendar = O('calendar', $form['calendar_id']);
        if (
            ($calendar->parent->name() == 'equipment' && $calendar->type == 'eq_reserv')
            ||
            ($calendar->parent->name() == 'user' && $calendar->type == 'eq_incharge')
            ) {
                $this->layout = V('eq_reserv:calendar/print', ['columns'=> $columns, 'form'=> $form, 'components'=> $components, 'calendar'=> $calendar]);
        }

    }

    private function _export_csv($components, $columns, $form) {

        $calendar = O('calendar', $form['calendar_id']);
        if (
            ($calendar->parent->name() == 'equipment' && $calendar->type == 'eq_reserv')
            ||
            ($calendar->parent->name() == 'user' && $calendar->type == 'eq_incharge')
            ) {
            $csv = new CSV('php://output', 'w');
            $valid_columns = [];
			foreach($columns as $k => $v ){
				$valid_columns[$k] = I18N::T('eq_reserv', $v);
			}
            $csv->write($valid_columns);
            $csv->close();
        }
     }
}

class Index_AJAX_Controller extends AJAX_Controller {

	function index_preview_click() {
		$form = Input::form();
        $component = O('cal_component', $form['component_id']);
        $infos = [];
        $infos[] = (string) V('eq_reserv:view/calendar/component_info', ['component' => $component]);
		Output::$AJAX['preview'] = (string) V('eq_reserv:view/calendar/preview', [
												'infos' => $infos,
                                                'component' => $component
											]);
    }
    
    function index_slide_preview_click() {
        $form = Input::form();
        $component = O('cal_component', $form['component_id']);
        $infos = [];
        $infos[] = (string) V('eq_reserv:view/calendar/component_info', ['component' => $component]);
        Output::$AJAX['preview'] = (string) V('eq_reserv:view/calendar/preview', [
                                                'infos' => $infos,
                                                'component' => $component
                                            ]);
    }


    public function index_export_components_click() {
        $form = Input::form();
        $calendar = O('calendar', $form['calendar_id']);

        //错误传值
        if (!$calendar->id) return FALSE;
        if (!in_array($form['type'], ['print', 'csv'])) return FALSE;

        $view_params  = [
            'type'=> $form['type'],
            'dtstart'=> $form['dtstart'],
            'dtend'=> $form['dtend'],
            'calendar_id'=> $form['calendar_id'],
            'form_token'=> $form['form_token']
        ];

        $dialog_params = [
            'title'=> I18N::T('eq_reserv', $form['type'] == 'print' ? '请选择要打印的列' : '请选择要导出的列')
        ];

        JS::dialog((string) V('eq_reserv:calendar/dialog', $view_params), $dialog_params);
    }

    public function index_eq_reserv_organizer_change() {
	    $form = Input::form();
        if (Module::is_installed('labs')) {
            $user = O('user', $form['user_id']);
            $lab = O('lab', $form['project_lab']);
            $equipment = O('equipment', $form['equipment_id']);
            $tr_lab_id = $form['tr_lab_id'] ? : ('tr_lab_' . uniqid());
            $tr_project_id = $form['tr_project_id'] ? : ('tr_project_' . uniqid());
            if ($form['tr_lab_id']) {
                Output::$AJAX["#" . $form['tr_lab_id']] = [
                    'data' => (string)V('eq_reserv:view/calendar/project_lab', [
                                    'user' => $user,
                                    'equipment' => $equipment,
                                    'tr_lab_id' => $tr_lab_id,
                                ]),
                    'mode' => 'replace',
                ];
            }
            elseif ($form['tr_project_id']) {
                Output::$AJAX["#" . $form['tr_project_id']] = [
                    'data' => (string)V('eq_reserv:view/calendar/project', [
                                    'user' => $user,
                                    'lab' => $lab,
                                    'flag' => 'edit',
                                    'tr_project_id' => $tr_project_id,
                                ]),
                    'mode' => 'replace',
                ];
            }
        }
        Event::trigger('index.eq_reserv.organizer.change', $user, $lab, $equipment);
    }

    public function index_reserv_export_submit() {
        $me = L('ME');
        $form = Input::form();
        $form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
           unset($old_form['columns']);
        }

        $form = $new_form + $old_form;
        $old_form['dtstart'] && $form['dtstart'] = $old_form['dtstart'];
        $old_form['dtend'] && $form['dtend'] = $old_form['dtend'];

        $calendar = O('calendar', $form['calendar_id']);

        //错误传值
        if (!$calendar->id || !in_array($form['type'], ['print', 'csv'])) URI::redirect('error/401');

        $dtstart = $form['dtstart'] ? : $form['st'];
        $dtend = $form['dtend'] ? : $form['ed'];

        /*if (!$dtstart) {
            $dtstart = $form['date'] ? : Date::time();
            $date = getdate($dtstart);
            $dtstart = mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']);
            $dtend = $dtstart + 604800 - 1;
        }*/

        $selector = "cal_component[calendar={$calendar}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]";
        $components = Q($selector);

        $new_components = Event::trigger('calendar.components.get', $calendar, $components, $dtstart, $dtend, 0, $form, $mode = 'list');
        if ($new_components) {
            $components = $new_components;
            $selector = $_SESSION['reserv_export_'.$me->id.'_'.$calendar->id];
            unset($_SESSION['reserv_export_'.$me->id.'_'.$calendar->id]);
        }

        $valid_columns = Config::get('calendar.export_columns.eq_reserv');
        $valid_columns = Event::trigger('calendar.extra.export_columns', $valid_columns, $form['form_token'])?:$valid_columns;
        $visible_columns = $form['columns'];

        foreach ($valid_columns as $p => $p_name ) {
            if (!isset($visible_columns[$p]) || $visible_columns[$p] == 'null') {
                unset($valid_columns[$p]);
            }
        }

        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        $pid = $this->_export_csv($selector, $valid_columns, $form, $file_name);
        JS::dialog(V('export_wait', [
            'file_name' => $file_name,
            'pid' => $pid
        ]), [
            'title' => I18N::T('calendars', '导出等待')
        ]);
    }
    
    public function index_tab_content_click() {
        $_SESSION['fake_key'] = time();
        return TRUE;
    }

    private function _export_csv($selector, $columns, $form, $file_name) {
        $me = L('ME');
        $calendar = O('calendar', $form['calendar_id']);
        if (
            ($calendar->parent->name() == 'equipment' && $calendar->type == 'eq_reserv')
            ||
            ($calendar->parent->name() == 'user' && $calendar->type == 'eq_incharge')
            ) {
            $valid_columns = [];
			foreach($columns as $k => $v ){
				$valid_columns[$k] = I18N::T('eq_reserv', $v);
			}
            if (isset($_SESSION[$me->id.'-export'])) {
    			foreach ($_SESSION[$me->id.'-export'] as $old_pid => $old_form) {
    				$new_valid_form = $form['form'];

    				unset($new_valid_form['form_token']);
    				unset($new_valid_form['selector']);
    				if ($old_form == $new_valid_form) {
    					unset($_SESSION[$me->id.'-export'][$old_pid]);
    					proc_close(proc_open('kill -9 '.$old_pid, [], $pipes));
    				}
    			}
    		}
            putenv('Q_ROOT_PATH=' . ROOT_PATH);
            $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_reserv export ';
            $cmd .= "'".$selector."' '".$file_name."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
            // exec($cmd, $output);
            $process = proc_open($cmd, [], $pipes);
			$var = proc_get_status($process);
			proc_close($process);
			$pid = intval($var['pid']) + 1;
			$valid_form = $form['form'];
			unset($valid_form['form_token']);
			unset($valid_form['selector']);
			$_SESSION[$me->id.'-export'][$pid] = $valid_form;
			return $pid;
        }
    }

    public function index_get_captcha_click() {
        $me = L('ME');
        $config = Config::get('rpc.servers')['reserv-server'];

        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => Config::get("calendar.server", [])["url"],
                'http_errors' => FALSE,
                'timeout' => 5000,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                ]
            ]);

            $data = $client->get('captcha', [
                'query' => [
                    'userId' => $me->id,
                ]
            ])->getBody()->getContents();
        }
        catch (Exception $e) { 
            $data = '无法显示验证码，请联系系统管理员！';
        }

        Output::$AJAX['data'] = $data;
    }
}
