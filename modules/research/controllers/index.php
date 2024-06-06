<?php

class Index_Controller extends Base_Controller {

    function index() {
        $me = L('ME');
        $form = Lab::form();
        $group_root = Tag_Model::root('group');
        $preselector = [];
        $selector = 'research';

        if ($form['ref_no']) {
            $ref_no = Q::quote($form['ref_no']);
            $selector .= "[ref_no*={$ref_no}]";
        }
        if ($form['name']) {
            $name = Q::quote($form['name']);
            $selector .= "[name*={$name}]";
        }

        if ($form['group_id']) {
            $group = O('tag', (int)$form['group_id']);
            if ($group->id && $group->root->id == $group_root->id) {
                $preselector[] = "{$group}";
            }
        }
        
        if ($form['contacts']) {
            $contacts = Q::quote($form['contacts']);
            $preselector[] = "user[name*={$contacts}]<contact";
        }

        if (count($preselector)) {
            $selector = "(" . join(', ', $preselector) . ") " . $selector;
        }
        $selector .= ":sort(id D)";
        $researches = Q($selector);

        $form_token = Session::temp_token('research_list_', 300);
        $form['form_token'] = $form_token;
        $form['selector'] = $selector;
        $_SESSION[$form_token] = $form;

        $start = (int) $form['st'];
        $per_page = 20;
        $pagination = Lab::pagination($researches, $start, $per_page);

        $panel_buttons = new ArrayIterator;
        if ($me->is_allowed_to('添加', 'research')) {
            $panel_buttons[] = [
                'url' => URI::url('!research/add'),
                'text' => I18N::T('research', '添加服务'),
                'extra' => 'class="button button_add"'
            ];
        }

        if ($me->is_allowed_to('导出', 'research')) {
            $panel_buttons[] = [
                'text' => I18N::T('research', '导出Excel'),
                'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!research/index') .
                        '" q-static="' . H(['form_token' => $form_token]) .
                        '" class="button button_save"'
            ];
            $panel_buttons[] = [
                'text'=>I18N::T('research','打印'),
                'extra' => 'q-object="print" q-event="click" q-src="' . URI::url('!research/index') .
                    '" q-static="' . H(['form_token' => $form_token]) .
                    '" class="button button_print"',
            ];
        }

        $tabs = $this->layout->body->primary_tabs;

        $tabs->select('list');

        $tabs->content = V('list', [
            'researches' => $researches,
            'group' => $group,
            'group_root' => $group_root,
            'panel_buttons' => $panel_buttons,
            'form' => $form,
            'pagination' => $pagination,
        ]);
    }

    function add()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('添加', 'research')) {
            URI::redirect('error/401');
        } else {
            $this->layout->body->primary_tabs
                ->add_tab('add', [
                    'url'=>URI::url('!research/add'),
                    'title'=>I18N::T('research', '添加服务'),
                ])
                ->select('add');
        }

        $group_root = Tag_Model::root('group');

        $research = O('research');
        if (Input::form('submit')) {
            $form = Form::filter(Input::form());
            $form->validate('ref_no', 'not_empty', I18N::T('research', '服务编号不能为空!'));
            $form->validate('name', 'not_empty', I18N::T('research', '服务名称不能为空!'));
            $form->validate('content', 'not_empty', I18N::T('research', '服务内容不能为空!'));
            $form->validate('charge', 'not_empty', I18N::T('research', '收费标准不能为空!'));
            $form->validate('email', 'not_empty', I18N::T('research', '联系邮箱不能为空!'));
            if ($form['email']) {
                $form->validate('email', 'is_email', I18N::T('people', '联系邮箱填写有误!'));
            }
            $contacts = (array)@json_decode($form['contacts'], true);
            if (count($contacts) == 0) {
                $form->set_error('contacts', I18N::T('research', '请指定至少一名联系人!'));
            }

            $exist = O('research', ['ref_no' => $form['ref_no']]);
            if ($exist->id) {
                $form->set_error('ref_no', I18N::T('research', '您输入的服务编号在系统中已存在!'));
            }

            if ($form->no_error) {
                $research->ref_no = $form['ref_no'];
                $research->name = $form['name'];
                $research->content = $form['content'];
                $research->charge = $form['charge'];
                $research->location = $form['location'];
                $research->phone = $form['phone'];
                $research->email = $form['email'];

                $group = O('tag', $form['group_id']);
                if ($group->id) {
                    $research->group = $group;
                }

                $research->save();

                if ($research->id) {
                    Log::add(strtr('[research] %user_name[%user_id]添加%research_name[%research_id]服务', [
                        '%user_name'=> $me->name,
                        '%user_id'=> $me->id,
                        '%research_name'=> $research->name,
                        '%research_id'=> $research->id
                    ]), 'journal');
                    $group->connect($research);

                    foreach (json_decode($form['contacts']) as $id => $name) {
                        $user = O('user', $id);
                        if (!$user->id) {
                            continue;
                        }
                        $research->connect($user, 'contact');
                    }
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('research', '服务添加成功!'));

                    URI::redirect($research->url(null, null, null, 'view'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('research', '服务添加失败! 请与系统管理员联系。'));
                }
            }
        }

        $this->layout->body->primary_tabs
            ->set('content', V('add', [
                'research' => $research,
                'form' => $form,
                'group_root' => $group_root,
            ]));
    }

    public function export_print() {

        $form = Input::form();
        $token = $form['form_token'];

        try {
            if (!count($_SESSION[$token])) throw new Error_Exception;

            $old_form = (array) $_SESSION[$token];
            $new_form = (array) Input::form();

            if (isset($new_form['columns'])) {
                unset($old_form['columns']);
            }

            $researchs = Q($_SESSION[$token]['selector']);
            $valid_columns = Config::get('research.export_columns.research');
            $visible_columns = Input::form('columns');

            foreach ($valid_columns as $p => $p_name) {
                if (!isset($visible_columns[$p])) {
                    unset($valid_columns[$p]);
                }
            }

            $this->layout = V('research_print', [
                'researchs' => $researchs,
                'valid_columns' => $valid_columns,
            ]);
        }
        catch(Error_Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('research', '操作超时, 请刷新页面后重试!'));
            URI::redirect('!research');
            return FALSE;
        }
    }
}

class Index_AJAX_Controller extends AJAX_Controller
{
    public function index_export_click()
    {
        $form = Input::form();
        $form_token = $form['form_token'];
        $columns = Config::get('research.export_columns.research');

        if (!$_SESSION[$form_token]) {
            JS::alert(I18N::T('research', '操作超时, 请重试!'));
            return false;
        }

        $old_form = (array) $_SESSION[$form_token];

        $title = I18N::T('research', '请选择要导出Excel的列');

        JS::dialog(V('export_form', [
            'form_token' => $form_token,
            'columns' => $columns,
            'type' => 'csv',
            'url' => URI::url('!research/index')
        ]), [
            'title' => I18N::T('research', $title)
        ]);
    }

    public function index_export_submit()
    {
        $form = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $_SESSION[$form_token]['selector'];

        $file_name_time = microtime(true);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        $pid = $this->_export_csv($selector, $form, $file_name);
        JS::dialog(V('export_wait', [
            'file_name' => $file_name,
            'pid' => $pid
        ]), [
            'title' => I18N::T('equipments', '导出等待')
        ]);
    }

    public function _export_csv($selector, $form, $file_name)
    {
        $me = L('ME');
        $valid_columns = Config::get('research.export_columns.research');
        $visible_columns = (array)$form['columns'];

        foreach ($valid_columns as $p => $p_name) {
            if ($visible_columns[$p] == 'null') {
                unset($valid_columns[$p]);
            }
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

        // 扩充的打印导出默认勾选功能，将 valid_columns 的 value 改为了 array，此处做兼容
        foreach ($valid_columns as $key => $value) {
            if (is_array($value)) {
                unset($valid_columns[$key]);
                $valid_columns[$key] = $value['name'];
            }
        }

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php research export ';
        $cmd .= "'".$selector."' '".$file_name."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."'>/dev/null 2>&1 &";
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

    public function index_print_click()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        $columns    = Config::get('research.export_columns.research');

        if (!$_SESSION[$form_token]) {
            JS::alert(I18N::T('research', '操作超时, 请重试!'));
            return false;
        }

        $title = I18N::T('research', '请选择要导出Excel的列');

        JS::dialog(V('export_form', [
            'form_token' => $form_token,
            'columns'    => $columns,
            'type'       => 'print',
            'url'        => URI::url('!research/index/export_print', ['type'=> 'print' ,'form_token'=>$form_token]),
        ]), [
            'title' => I18N::T('research', $title),
        ]);
    }
}
