<?php

class Research_Controller extends Base_Controller
{

    public function index($id = 0, $tab = 'dashboard')
    {
        $research = O('research', $id);
        $me       = L('ME');
        if (!$research->id) {
            URI::redirect('error/404');
        }

        $this->layout->body->primary_tabs
            ->add_tab('view', [
                'url'   => $research->url(),
                'title' => H($research->name),
            ])
            ->select('view');

        $content           = V('view');
        $content->research = $research;

        Event::bind('research.index.tab.content', [$this, '_index_dashboard'], 0, 'dashboard');
        Event::bind('research.index.tab.content', [$this, '_index_records'], 0, 'records');

        $content->secondary_tabs = Widget::factory('tabs')
            ->set('research', $research)
            ->tab_event('research.index.tab')
            ->content_event('research.index.tab.content');
        $content->secondary_tabs
            ->add_tab('dashboard', [
                'url'    => $research->url('dashboard'),
                'title'  => I18N::T('research', '常规信息'),
                'weight' => 0,
            ]);
        $content->secondary_tabs
            ->add_tab('records', [
                'url'    => $research->url('records'),
                'title'  => I18N::T('research', '使用记录'),
                'weight' => 30,
            ]);

        $content->secondary_tabs->select($tab);
        $this->layout->body->primary_tabs->content = $content;
    }

    public function all()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('管理所有科研服务记录', 'research')) {
            URI::redirect('error/401');
        }
        $form = Lab::form(function (&$old_form, &$form) {
            if (isset($form['date_filter'])) {
                if (!$form['dtstart_check']) {
                    unset($old_form['dtstart_check']);
                }

                if (!$form['dtend_check']) {
                    unset($old_form['dtend_check']);
                } else {
                    $form['dtend'] = Date::get_day_end($form['dtend']);
                }
                unset($form['date_filter']);
            }
        });

        $pre_selectors             = [];
        $pre_selectors['research'] = 'research';
        $selector                  = 'research_record';

        if ($form['research_name']) {
            $research_name = Q::quote(trim($form['research_name']));
            $pre_selectors['research'] .= "[name*={$research_name}]";
        }
        if ($form['id']) {
            $id = Q::quote($form['id']);
            $selector .= "[id={$id}]";
        }
        if ($form['lab_ref_no']) {
            $lab_ref_no           = Q::quote(trim($form['lab_ref_no']));
            $pre_selectors['lab'] = "lab[ref_no*={$lab_ref_no}]";
        }
        if ($form['lab']) {
            $lab = Q::quote(trim($form['lab']));
            if ($pre_selectors['lab']) {
                $pre_selectors['lab'] .= "[name*={$lab}]";
            } else {
                $pre_selectors['lab'] = "lab[name*={$lab}]";
            }
        }
        if ($form['company_name']) {
            $company_name = Q::quote(trim($form['company_name']));
            if ($pre_selectors['lab']) {
                $pre_selectors['lab'] .= "[company_name*={$company_name}]";
            } else {
                $pre_selectors['lab'] = "lab[company_name*={$company_name}]";
            }
        }
        if ($pre_selectors['lab']) {
            $pre_selectors['lab'] .= " user";
        }
        if ($form['name']) {
            $name                  = Q::quote(trim($form['name']));
            $pre_selectors['user'] = "user[name*={$name}]";
        }
        if (isset($form['status']) && $form['status'] != -1) {
            $status = Q::quote($form['status']);
            $selector .= "[charge_status={$status}]";
        }
        if ($form['dtstart_check']) {
            $dtstart = Q::quote(Date::get_day_start($form['dtstart']));
            $selector .= "[dtend>=$dtstart]";
        }
        if ($form['dtend_check']) {
            $dtend = Q::quote(Date::get_day_end($form['dtend']));
            $selector .= "[dtend<=$dtend]";
        }

        if (!empty($pre_selectors)) {
            $selector = '(' . join(', ', $pre_selectors) . ') ' . $selector;
        }

        $sort_by   = $form['sort'] ?: 'id';
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        $selector .= ":sort({$sort_by} {$sort_flag})";

        $form_token            = Session::temp_token('research_record_all_', 300);
        $_SESSION[$form_token] = ['selector' => $selector, 'form' => $form];
        $form['form_token']    = $form_token;

        $records = Q($selector);

        $start      = (int) $form['st'];
        $per_page   = 20;
        $pagination = Lab::pagination($records, $start, $per_page);

        $panel_buttons = [];
        if ($me->is_allowed_to('管理所有科研服务记录', 'research')) {
            $panel_buttons[] = [
                'text'  => I18N::T('research', '导出Excel'),
                'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!research/research') .
                '" q-static="' . H(['form_token' => $form_token]) .
                '" class="button button_save middle"',
            ];
        }

        $tabs = $this->layout->body->primary_tabs;
        $tabs->select('all');
        $tabs->content = V('research/all', [
            'form'          => $form,
            'records'       => $records,
            'pagination'    => $pagination,
            'panel_buttons' => $panel_buttons,
            'sort_by'       => $sort_by,
            'sort_asc'      => $sort_asc,
        ]);
    }

    public function _index_dashboard($e, $tabs)
    {
        $research   = $tabs->research;
        $me         = L("ME");
        $sections   = new ArrayIterator;
        $sections[] =
        V('research:research/info')
            ->set('research', $research);

        $tabs->content = V('research/dashboard', ['sections' => $sections]);
    }

    public function _index_records($e, $tabs)
    {
        $me       = L('ME');
        $research = $tabs->research;

        $form = Lab::form(function (&$old_form, &$form) {
            if (isset($form['date_filter'])) {
                if (!$form['dtstart_check']) {
                    unset($old_form['dtstart_check']);
                }

                if (!$form['dtend_check']) {
                    unset($old_form['dtend_check']);
                } else {
                    $form['dtend'] = Date::get_day_end($form['dtend']);
                }
                unset($form['date_filter']);
            }
        });

        $pre_selectors = [
            'research' => "$research",
        ];
        $selector = 'research_record';

        if ($form['id']) {
            $id = Q::quote($form['id']);
            $selector .= "[id={$id}]";
        }
        if ($form['lab_ref_no']) {
            $lab_ref_no           = Q::quote($form['lab_ref_no']);
            $pre_selectors['lab'] = "lab[ref_no*={$lab_ref_no}]";
        }
        if ($form['lab']) {
            $lab = Q::quote($form['lab']);
            if ($pre_selectors['lab']) {
                $pre_selectors['lab'] .= "[name*={$lab}]";
            } else {
                $pre_selectors['lab'] = "lab[name*={$lab}]";
            }
        }
        if (Q("{$me} tag[root=" . Tag_Model::root('group') . "][name*=校外]")->total_count()) {
            $lab = Q("{$me} lab")->current();
            if ($pre_selectors['lab']) {
                $pre_selectors['lab'] .= "[ref_no={$lab->ref_no}]";
            } else {
                $pre_selectors['lab'] = "{$lab}";
            }
        }
        if ($pre_selectors['lab']) {
            $pre_selectors['lab'] .= " user";
        }
        if ($form['name']) {
            $name                  = Q::quote($form['name']);
            $pre_selectors['user'] = "user[name*={$name}]";
        }
        if (isset($form['status']) && $form['status'] != -1) {
            $status = Q::quote($form['status']);
            $selector .= "[charge_status={$status}]";
        }
        if ($form['dtstart_check']) {
            $dtstart = Q::quote(Date::get_day_start($form['dtstart']));
            $selector .= "[dtend>=$dtstart]";
        }
        if ($form['dtend_check']) {
            $dtend = Q::quote(Date::get_day_end($form['dtend']));
            $selector .= "[dtend<=$dtend]";
        }

        $selector = '(' . join(', ', $pre_selectors) . ') ' . $selector;

        $sort_by   = $form['sort'] ?: 'id';
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        $selector .= ":sort({$sort_by} {$sort_flag})";

        $form_token            = Session::temp_token('research_record_', 300);
        $form['research_id']   = $research->id;
        $_SESSION[$form_token] = ['selector' => $selector, 'form' => $form];
        $form['form_token']    = $form_token;

        $records = Q($selector);

        $panel_buttons = [];
        if ($me->is_allowed_to('管理使用记录', $research)) {
            $panel_buttons[] = [
                'url'   => '',
                'text'  => I18N::T('research', '添加记录'),
                'extra' => 'class="button button_add middle view object:add_record event:click static:id=' . $research->id . '&oname=research"',
            ];
        }
        if ($me->is_allowed_to('导出使用记录', $research)) {
            $panel_buttons[] = [
                'text'  => I18N::T('research', '导出Excel'),
                'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!research/research') .
                    '" q-static="' . H(['form_token' => $form_token]) .
                    '" class="button button_save middle"',
            ];
            $panel_buttons[] = [
                'text'  => I18N::T('research', '打印'),
                'extra' => 'q-object="print" q-event="click" q-src="' . URI::url('!research/research') .
                    '" q-static="' . H(['form_token' => $form_token]) .
                    '" class="button button_save middle"',
            ];
        }

        $tabs->content = V('research/records', [
            'panel_buttons' => $panel_buttons,
            'form'          => $form,
            'research'      => $research,
            'records'       => $records,
            'sort_by'       => $sort_by,
            'sort_asc'      => $sort_asc,
        ]);

        $start                     = (int) $form['st'];
        $per_page                  = 20;
        $pagination                = Lab::pagination($records, $start, $per_page);
        $tabs->content->pagination = $pagination;
    }

    public function edit($id = 0, $tab = 'info', $stab = 'dashboard')
    {
        $me       = L('ME');
        $research = O('research', $id);
        if (!$research->id) {
            URI::redirect('error/404');
        }
        if (!$me->is_allowed_to('修改', $research)) {
            URI::redirect('error/401');
        }

        $content = V('edit');

        $content->secondary_tabs = Widget::factory('tabs');
        Event::bind('research.edit.content', [$this, '_edit_info'], 0, 'info');
        Event::bind('research.edit.content', [$this, '_edit_photo'], 0, 'photo');
        $content->secondary_tabs
            ->add_tab('info', [
                'url'    => $research->url('info', null, null, 'edit'),
                'title'  => I18N::T('research', '基本信息'),
                'weight' => 0,
            ])
            ->add_tab('photo', [
                'url'    => $research->url('photo', null, null, 'edit'),
                'title'  => I18N::T('research', '设备图标'),
                'weight' => 10,
            ]);
        $content->secondary_tabs->set('class', 'secondary_tabs')
            ->set('research', $research)
            ->tab_event('research.edit.tab')
            ->content_event('research.edit.content')
            ->select($tab);

        $breadcrumb = [
            [
                'url'   => $research->url(),
                'title' => H($research->name),
            ],
            [
                'url'   => $research->url('info', null, null, 'edit'),
                'title' => I18N::HT('research', '设置'),
            ],
        ];

        $this->layout->body->primary_tabs
            ->add_tab('edit', ['*' => $breadcrumb])
            ->select('edit')
            ->content = $content;
    }

    public function _edit_info($e, $tabs)
    {

        $research = $tabs->research;
        $me       = L('ME');

        $group_root = Tag_Model::root('group');

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
            $contacts = (array) @json_decode($form['contacts'], true);
            if ($me->is_allowed_to('修改联系人', $research)) {
                if (count($contacts) == 0) {
                    $form->set_error('contacts', I18N::T('research', '请指定至少一名联系人!'));
                }
            }

            $exist = O('research', ['ref_no' => $form['ref_no']]);
            if ($exist->id && $exist->id != $research->id) {
                $form->set_error('ref_no', I18N::T('research', '您输入的服务编号在系统中已存在!'));
            }

            if ($form->no_error) {
                if (isset($form['ref_no'])) {
                    $research->ref_no = $form['ref_no'];
                }

                if (isset($form['name'])) {
                    $research->name = $form['name'];
                }

                if (isset($form['content'])) {
                    $research->content = $form['content'];
                }

                if (isset($form['charge'])) {
                    $research->charge = $form['charge'];
                }

                if (isset($form['location'])) {
                    $research->location = $form['location'];
                }

                if (isset($form['phone'])) {
                    $research->phone = $form['phone'];
                }

                if (isset($form['email'])) {
                    $research->email = $form['email'];
                }

                if (isset($form['group_id'])) {
                    $group_root->disconnect($research);
                    $research->group = null;
                    $group           = O('tag', $form['group_id']);
                    if ($group->id) {
                        $group->connect($research);
                        $research->group = $group;
                    }
                }

                if ($me->is_allowed_to('修改联系人', $research)) {
                    if (isset($form['contacts'])) {
                        foreach (Q("$research<contact user") as $contacts) {
                            $research->disconnect($contacts, 'contact');
                        }

                        foreach (json_decode($form['contacts']) as $id => $name) {
                            $user = O('user', $id);
                            if (!$user->id) {
                                continue;
                            }

                            $research->connect($user, 'contact');
                        }
                    }
                }

                if ($research->save()) {
                    Log::add(strtr('[research] %user_name[%user_id]修改%research_name[%research_id]服务的基本信息: %form', [
                        '%user_name'     => $me->name,
                        '%user_id'       => $me->id,
                        '%research_name' => $research->name,
                        '%research_id'   => $research->id,
                        '%form'          => @json_encode($form, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    ]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('research', '服务信息已更新'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('research', '服务信息更新失败! 请与系统管理员联系。'));
                }
            }
        } elseif (Input::form('delete')) {
            if (!$me->is_allowed_to('删除', $research)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('research', '该服务已有使用记录，不可删除'));
                URI::redirect($research->url('info', null, null, 'edit'));
            }
            if ($research->delete()) {
                Log::add(strtr('[research] %user_name[%user_id]删除%research_name[%research_id]服务', [
                    '%user_name'     => $me->name,
                    '%user_id'       => $me->id,
                    '%research_name' => $research->name,
                    '%research_id'   => $research->id,
                ]), 'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('research', '服务信息已删除'));
                URI::redirect('!research');
            }
        }

        $tabs->content = V('edit.info', [
            'group_root' => $group_root,
            'form'       => $form,
            'research'   => $research,
        ]);
    }

    public function _edit_photo($e, $tabs)
    {
        $research = $tabs->research;

        if (Input::form('submit')) {
            $file = Input::file('file');
            if ($file['tmp_name']) {
                try {
                    $ext   = File::extension($file['name']);
                    $image = Image::load($file['tmp_name'], $ext);
                    $research->save_icon($image);

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('research', '设备图标已更新'));
                } catch (Error_Exception $e) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('research', '设备图标更新失败!'));
                }
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('research', '请选择您要上传的设备图标文件。'));
            }
        }

        $tabs->content = V('edit.photo');
    }

    public function delete_photo($id = 0)
    {
        $me       = L('ME');
        $research = O('research', $id);
        if (!$me->is_allowed_to('修改', $research)) {
            URI::redirect('error/401');
        }

        $research->delete_icon();

        URI::redirect($research->url('photo', null, null, 'edit'));
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

            $records = Q($_SESSION[$token]['selector']);
            $valid_columns = Config::get('research.export_columns.research_record');
            $visible_columns = Input::form('columns');

            foreach ($valid_columns as $p => $p_name) {
                if (!isset($visible_columns[$p])) {
                    unset($valid_columns[$p]);
                }
            }

            $this->layout = V('research/records_print', [
                'records' => $records,
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

class Research_AJAX_Controller extends AJAX_Controller
{

    public function index_research_select_click()
    {
        $form  = Input::form();
        $uniq  = $form['type'] == 'research' ? "research{$form['research']}" : '--';
        $k     = "batch_record_{$uniq}.ids";
        $array = $_SESSION[$k] ?: [];
        foreach ($form['ids'] as $key => $item) {
            if ($item > 0) {
                $array[$key] = true;
            } else {
                unset($array[$key]);
            }
        }
        $_SESSION[$k] = $array;
        return $array;
    }

    public function index_research_handle_click()
    {
        $form = Input::form();
        $uniq = $form['type'] == 'research' ? "research{$form['research']}" : '--';
        $k    = "batch_record_{$uniq}.ids";

        if (!isset($_SESSION[$k]) || empty($_SESSION[$k])) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('research', '请选择记录'));
            JS::refresh();
            exit;
        }

        if (!isset($form['ac']) || !in_array($form['ac'], ['research'])) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('research', '您无法进行此操作'));
            JS::refresh();
            exit;
        }

        switch ($form['ac']) {
            case 'research':
                $view  = 'research:research/batch_action/research_batch_confirm';
                $title = '';
                break;
        }

        JS::dialog(V($view, [
            'form'  => $form,
            'ac'    => $form['ac'],
            'title' => $title,
        ]), [
            'title' => I18N::T('research', '操作确认'),
        ]);
    }

    public function index_confirm_batch_submit()
    {
        $me   = L('ME');
        $form = Input::form();
        if (!isset($form['research']) || !$form['research']) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('research', '操作失败, 请刷新重试'));
            JS::refresh();
            return;
        }

        if ($form['research'] != 'all') {
            $research = O('research', $form['research']);
        }

        $k             = "batch_record_research{$form['research']}.ids";
        $records       = Q('research_record[id=' . join(',', array_keys($_SESSION[$k])) . ']');
        $charge_status = (int) $form['charge_status'];

        $cnt = 0;
        if ($form['type'] == 'research') {
            if ($form['ac'] == 'pass') {
                $perm_name = '管理使用记录';
                foreach ($records as $record) {
                    if ($me->is_allowed_to($perm_name, $record->research)) {
                        if ($record->charge_status == $charge_status) {
                            continue;
                        }

                        $record->charge_status = $charge_status;
                        if ($record->save()) {
                            $cnt++;
                        }

                    }
                }

                if ($charge_status == 0) {
                    $msg = sprintf('科研服务使用记录成功设置%s条收费为未计费状态', $cnt);
                } else {
                    $msg = sprintf('科研服务使用记录成功设置%s条收费为已计费状态', $cnt);
                }
                Lab::message(LAB::MESSAGE_NORMAL, I18N::T('research', $msg));
            } else {
                $msg = sprintf('您选择的操作有误，请重新确认!', $cnt);
                Lab::message(LAB::MESSAGE_ERROR, I18N::T('research', $msg));
            }

            JS::refresh();
            unset($_SESSION[$k]);
        }
    }

    public function index_add_record_click($id = 0)
    {
        $me       = L('ME');
        $research = O('research', $id);

        if (!$me->is_allowed_to('添加使用记录', $research)) {
            return;
        }

        $record           = O('research_record');
        $record->research = $research;
        $record->user     = $me;
        JS::dialog(V('research/record.edit', [
            'record'   => $record,
            'form'     => $form,
            'research' => $research,
        ]), ['title' => I18N::T('research', '添加使用记录')]);
    }

    public function index_edit_record_click()
    {
        $me     = L('ME');
        $form   = Input::form();
        $record = O('research_record', $form['record_id']);

        if (!$record->id || !$me->is_allowed_to('编辑', $record)) {
            return;
        }
        JS::dialog(V('research/record.edit', [
            'record'   => $record,
            'form'     => $form,
            'research' => $record->research,
        ]), ['title' => I18N::T('research', '编辑使用记录')]);
    }

    public function index_edit_record_submit()
    {
        $form     = Form::filter(Input::form());
        $record   = O('research_record', (int) $form['record_id']);
        $research = O('research', (int) $form['research_id']);

        $me = L('ME');

        if ($record->id && $form['submit'] == 'delete') {
            if (!JS::confirm(I18N::T('research', '您确定删除该记录吗? 记录一旦删除，不可恢复'))) {
                return;
            }

            $record->delete();

            Log::add(strtr('[research] %user_name[%user_id]删除%research_name[%research_id]服务的使用记录[%record_id]', [
                '%user_name'     => $me->name,
                '%user_id'       => $me->id,
                '%research_name' => $record->research->name,
                '%research_id'   => $record->research->id,
                '%record_id'     => $record->id,
            ]), 'journal');
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('research', '删除使用成功!'));
            JS::refresh();
            return;
        } elseif ($form['submit'] == 'submit') {
            $title = $record->id ? '修改使用记录' : '添加使用记录';
            $form->validate('quantity', 'not_empty', I18N::T('research', '服务数量不能为空!'));
            $user = O('user', $form['user_id']);
            if (!$user->id) {
                $form->set_error('user_id', I18N::T('research', '使用者不能为空!'));
            }
            $form->validate('price', 'number(>=0)', I18N::T('research', '请输入正确的服务单价!'));
            $form->validate('amount', 'number(>=0)', I18N::T('research', '请输入正确的服务总金额!'));
            $form->validate('discount', 'number(>=0)', I18N::T('research', '请输入正确的折扣(0~100)!'));
            $form->validate('discount', 'number(<=100)', I18N::T('research', '请输入正确的折扣(0~100)!'));
            if ($form->no_error) {
                $record->research      = $research;
                $record->research_no   = $form['research_no'];
                $record->user          = $user;
                $record->quantity      = $form['quantity'];
                $record->price         = $form['price'];
                $record->amount        = $form['amount'];
                $record->discount      = $form['discount'];
                $record->dtstart       = $form['dtstart'];
                $record->dtend         = $form['dtend'];
                $record->charge_status = $form['charge_status'];
                $record->description   = $form['description'];
                $record->save();
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('research', $title . '成功!'));
                JS::refresh();
            } else {
                JS::dialog(V('research/record.edit', [
                    'record'   => $record,
                    'form'     => $form,
                    'research' => $research,
                ]), ['title' => I18N::T('research', $title)]);
            }
        }
    }

    public function index_export_click()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        $columns    = Config::get('research.export_columns.research_record');

        if (!$_SESSION[$form_token]) {
            JS::alert(I18N::T('research', '操作超时, 请重试!'));
            return false;
        }

        $old_form = (array) $_SESSION[$form_token];

        $title = I18N::T('research', '请选择要导出Excel的列');

        JS::dialog(V('export_form', [
            'form_token' => $form_token,
            'columns'    => $columns,
            'type'       => 'csv',
            'url'        => URI::url('!research/research'),
        ]), [
            'title' => I18N::T('research', $title),
        ]);
    }

    public function index_export_submit()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('research', '操作超时, 请重试!'));
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
        $file_name_arr  = explode('.', $file_name_time);
        $file_name      = $file_name_arr[0] . $file_name_arr[1];

        $pid = $this->_export_csv($selector, $form, $file_name);
        JS::dialog(V('export_wait', [
            'file_name' => $file_name,
            'pid'       => $pid,
        ]), [
            'title' => I18N::T('research', '导出等待'),
        ]);
    }

    public function _export_csv($selector, $form, $file_name)
    {
        $me              = L('ME');
        $valid_columns   = Config::get('research.export_columns.research_record');
        $visible_columns = (array) $form['columns'];

        foreach ($valid_columns as $p => $p_name) {
            if ($visible_columns[$p] == 'null') {
                unset($valid_columns[$p]);
            }
        }

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

        // 扩充的打印导出默认勾选功能，将 valid_columns 的 value 改为了 array，此处做兼容
        foreach ($valid_columns as $key => $value) {
            if (is_array($value)) {
                unset($valid_columns[$key]);
                $valid_columns[$key] = $value['name'];
            }
        }

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php research export_record ';
        $cmd .= "'" . $selector . "' '" . $file_name . "' '" . json_encode($valid_columns, JSON_UNESCAPED_UNICODE) . "'>/dev/null 2>&1 &";
        $process = proc_open($cmd, [], $pipes);
        $var     = proc_get_status($process);
        proc_close($process);
        $pid        = intval($var['pid']) + 1;
        $valid_form = $form['form'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id . '-export'][$pid] = $valid_form;
        return $pid;
    }

    public function index_print_click()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        $columns    = Config::get('research.export_columns.research_record');

        if (!$_SESSION[$form_token]) {
            JS::alert(I18N::T('research', '操作超时, 请重试!'));
            return false;
        }

        $title = I18N::T('research', '请选择要导出Excel的列');

        JS::dialog(V('export_form', [
            'form_token' => $form_token,
            'columns'    => $columns,
            'type'       => 'print',
            'url'        => URI::url('!research/research/export_print', ['type'=> 'print' ,'form_token'=>$form_token]),
        ]), [
            'title' => I18N::T('research', $title),
        ]);
    }
}
