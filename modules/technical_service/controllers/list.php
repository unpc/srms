<?php

class List_Controller extends Base_Controller
{

    public function index($tab = 'all')
    {

        $me = L('ME');

        $form_token = Input::form('form_token');
        $type = strtolower(Input::form('type'));
        $export_types = ['print', 'csv'];

        if (in_array($type, $export_types)) {
            $form = $_SESSION[$form_token];
            $services = Q($form['selector']);
            call_user_func([$this, '_export_' . $type], $services, $form);
        } else {

            $pre_selectors = new ArrayIterator;

            $form_token = Session::temp_token('service_list_', 300);
            $form = Input::form();
            $group = O('tag_group', $form['group_id']);
            $group_root = Tag_Model::root('group');
            if ($group->id && $group->root->id == $group_root->id) {
                $pre_selectors['group'] = "$group";
            } else {
                $group = null;
            }

            $selector = 'service';

            if ($form['name']) {
                $service_name = Q::quote(trim($form['name']));
                $selector .= "[name*={$service_name}|name_abbr*={$service_name}]";
            }
            if ($form['ref_no']) {
                $ref_no = Q::quote(trim($form['ref_no']));
                $selector .= "[ref_no*={$ref_no}]";
            }
            if ($form['service_type'] && $form['service_type'] != Tag_Model::root('service_type')->id) {
                $selector .= "[service_type_id={$form['service_type']}]";
            }
            if ($form['billing_department'] && $form['billing_department'] != -1) {
                $selector .= "[billing_department_id={$form['billing_department']}]";
            }
            if ($form['incharge_id']) {
                $pre_selectors['user'] = "user[id={$form['incharge_id']}]<incharge";
            }

            $start = (int)$form['st'];
            $per_page = Config::get('per_page.service', 25);
            $start = $start - ($start % $per_page);

            if (count($pre_selectors) > 0) {
                $selector = '(' . implode(',', (array)$pre_selectors) . ') ' . $selector;
            }

            $sort_by = $form['sort'];
            $sort_asc = $form['sort_asc'];
            $sort_flag = $sort_asc ? 'A' : 'D';

            switch ($sort_by) {
                case 'name':
                    $selector .= ":sort(name_abbr {$sort_flag})";
                    break;
                case 'ref_no':
                    $selector .= ":sort(ref_no {$sort_flag})";
                    break;
                default:
                    $selector .= ":sort(ctime D, name_abbr {$sort_flag})";
            }

            $services = Q($selector);

            $form['selector'] = $selector;
            $_SESSION[$form_token] = $form;
            $pagination = Lab::pagination($services, $start, $per_page);

            $panel_buttons = new ArrayIterator;
            if ($me->is_allowed_to('添加', 'service')) {
                $panel_buttons[] = [
                    'tip' => I18N::T('technical_service', '添加服务'),
                    'text' => I18N::T('technical_service', '添加'),
                    'extra' => 'q-object="add_service" q-event="click" q-src="' . URI::url('!technical_service/service') .
                        '" q-static="' . H(['form_token' => $form_token]) .
                        '" class="button button_add "',
                ];

            }
            if ($me->is_allowed_to('导出', 'service')) {
                $panel_buttons[] = [
                    'tip' => I18N::T('technical_service', '导出Excel'),
                    'text' => I18N::T('technical_service', '导出'),
                    'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!technical_service/list') .
                        '" q-static="' . H(['type' => 'csv', 'form_token' => $form_token]) .
                        '" class="button button_save "',

                ];
            }

            $content = V('service/list', [
                'services' => $services,
                'pagination' => $pagination,
                'form' => $form,
                'group' => $group,
                'group_root' => $group_root,
                'tab' => $tab,
                'sort_by' => $sort_by,
                'sort_asc' => $sort_asc,
                'panel_buttons' => $panel_buttons,
            ]);

            $this->layout->body->primary_tabs
                ->add_tab('all', [
                    'url' => URI::url('!technical_service/list/index.all'),
                    'title' => I18N::T('technical_service', "服务列表[{$services->total_count()}]"),
                    'weight' => -2000,
                ])
                ->select($tab)
                ->set('content', $content);
        }
    }

}

class List_AJAX_Controller extends AJAX_Controller
{
    public function index_export_click()
    {
        $form = Input::form();
        $form_token = $form['form_token'];
        $type = $form['type'];
        $columns = Config::get('exports.export_columns.service');

        if (!$_SESSION[$form_token]) {
            JS::alert(I18N::T('equipments', '操作超时, 请重试!'));
            return false;
        }

        $columns = Event::trigger('service.get.export.columns', $columns, $type) ?: $columns;

        $old_form = (array)$_SESSION[$form_token];

        if ($type == 'csv' || $type == 'excel') {
            $title = I18N::T('technical_service', '请选择要导出Excel的列');
        } elseif ($type == 'print') {
            $title = I18N::T('technical_service', '请选择要打印的列');
        }

        JS::dialog(V('export_form', [
            'form_token' => $form_token,
            'columns' => $columns,
            'type' => $type,
            'url' => URI::url('!technical_service/list', ['type' => $type, 'form_token' => $form_token]),
            'q_object' => 'export',
            'q_event' => 'submit',
        ]), [
            'title' => I18N::T('technical_service', $title),
        ]);
    }

    public function index_export_submit()
    {
        $form = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('technical_service', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $type = $form['type'];

        $old_form = (array)$_SESSION[$form_token];
        $new_form = (array)$form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $_SESSION[$form_token]['selector'];

        $file_name_time = microtime(true);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0] . $file_name_arr[1];

        if ('csv' == $type) {
            $pid = $this->_export_csv($selector, $form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
                'pid' => $pid,
            ]), [
                'title' => I18N::T('technical_service', '导出等待'),
            ]);
        }
    }

    public function _export_csv($selector, $form, $file_name)
    {
        $me = L('ME');
        $valid_columns = Config::get('exports.export_columns.service');
        $valid_columns = Event::trigger('service.get.export.columns', $valid_columns, 'csv') ?: $valid_columns;
        $visible_columns = (array)$form['columns'];

        foreach ($valid_columns as $p => $p_name) {
            if ($visible_columns[$p] == 'null') {
                unset($valid_columns[$p]);
            }
        }

        unset($valid_columns['-1']);
        unset($valid_columns['-2']);

        if (isset($_SESSION[$me->id . '-export'])) {
            foreach ($_SESSION[$me->id . '-export'] as $old_pid => $old_form) {
                $new_valid_form = $form['form'];

                unset($new_valid_form['form_token']);
                unset($new_valid_form['selector']);
                if ($old_form == $new_valid_form) {
                    unset($_SESSION[$me->id . '-export'][$old_pid]);
                    proc_close(proc_open('kill -9 ' . $old_pid, [], $pipes));
                }
            }
        }

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_service export ';
        $cmd .= "'" . $selector . "' '" . $file_name . "' '" . json_encode($valid_columns, JSON_UNESCAPED_UNICODE) . "'>/dev/null 2>&1 &";
        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        $valid_form = $form['form'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id . '-export'][$pid] = $valid_form;
        return $pid;
    }

}

