<?php

class List_Controller extends Base_Controller
{

    public function index($metab = 'all', $tab = 'all')
    {

        $me = L('ME');

        $form_token   = Input::form('form_token');
        $type         = strtolower(Input::form('type'));
        $export_types = ['print', 'csv'];

        if (in_array($type, $export_types)) {
            $form = $_SESSION[$form_token];
            $labs = Q($form['selector']);
            call_user_func([$this, '_export_' . $type], $labs, $form);
        } else {

            $pre_selectors = new ArrayIterator;
            if (!$me->is_allowed_to('查看', 'lab') || $metab == 'me') {
                $pre_selectors['user'] = $me;
            }

            $form_token = Session::temp_token('lab_list_', 300); //生成唯一一个SESSION的key
            $form       = Lab::form(function (&$old_form, &$form) {
                if (!isset($old_form['group_id']) && !isset($form['group_id']) && !Session::get_url_specific('default_search_set')) {
                    // 使用默认的机构筛选
                    $me               = L('ME');
                    $form['group_id'] = $me->default_group_id;
                    Session::set_url_specific('default_search_set', true);
                }
            });
            $form = new ArrayIterator($form);
            Event::trigger('extra_form_value', $form);

            // $secondary_tabs = Widget::factory('tabs');
            $this->layout->body->primary_tabs
                ->add_tab('all', [
                    'url'    => URI::url('!labs/list/index.' . $metab . '.all'),
                    'title'  => I18N::T('labs', '所有实验室'),
                    'weight' => -2000,
                ]);

            if(!People::perm_in_uno()){
                $this->layout->body->primary_tabs->add_tab('unactivated', [
                    'url'    => URI::url('!labs/list/index.' . $metab . '.unactivated'),
                    'title'  => I18N::T('labs', '未激活实验室'),
                    'weight' => -1500,
                ]);
            }

            //GROUP搜索
            $group      = O('tag_group', $form['group_id']);
            $group_root = Tag_Model::root('group');
            if ($group->id && $group->root->id == $group_root->id) {
                $pre_selectors['group'] = "$group";
            } else {
                $group = null;
            }

            $show_hidden_lab = $me->show_hidden_lab();

            $selector = 'lab';

            if (!$show_hidden_lab) {
                $selector .= '[hidden=0]';
            }

            if ($tab == 'unactivated') {
                $selector .= '[atime<=0]';
            }

            if ($form['lab_name']) {
                $lab_name = Q::quote(trim($form['lab_name']));
                $selector .= "[name*=$lab_name|name_abbr*=$lab_name]";
            }

            if ($me->is_allowed_to('查看建立者', 'lab')) {
                if ($form['creator']) {
                    $creator         = Q::quote(trim($form['creator']));
                    $pre_selectors[] = "user[name*=$creator|name_abbr*=$creator]<creator ";
                }
            }

            if ($me->is_allowed_to('查看审批者', 'lab')) {
                if ($form['auditor']) {
                    $auditor         = Q::quote(trim($form['auditor']));
                    $pre_selectors[] = "user[name*={$auditor}|name_abbr*={$auditor}]<auditor ";
                }
            }

            $start    = (int) $form['st'];
            $per_page = Config::get('per_page.lab', 25);
            $start    = $start - ($start % $per_page);

            $query = $form['query_lab'];
            if ($query) {
                $selector .= '[name*=' . Q::quote(trim($query)) . ']';
            }

            // people.index.search.submit 对搜索SELECTOR进行进一步处理
            $new_selector = Event::trigger('lab.index.search.submit', $form, $selector, $pre_selectors);
            if ($new_selector) {
                $selector = $new_selector;
            }

            if (count($pre_selectors) > 0) {
                $selector = '(' . implode(',', (array) $pre_selectors) . ') ' . $selector;
            }

            $sort_by   = $form['sort'];
            $sort_asc  = $form['sort_asc'];
            $sort_flag = $sort_asc ? 'A' : 'D';

			if (Module::is_installed('lab_rank') && !$sort_by) {
				$sort_by = 'rank';
			}
            switch ($sort_by) {
                case 'lab_name':
                    $selector .= ":sort(name_abbr {$sort_flag})";
					break;
				case 'rank':
					$selector .= ":sort(rank {$sort_flag})";
					break;
				default:
					if ($tab == 'unactivated') {
						$selector .= ":sort(ctime D, name_abbr {$sort_flag})";
					}
					else {
						$selector .= ":sort(atime D, name_abbr {$sort_flag})";
					}
			}

            $labs = Q($selector);

            $form['selector']      = $selector;
            $_SESSION[$form_token] = $form;

            $user_selector = $selector . ' user';
            //对课题组和课题组成员的统计
            $lab_count  = $labs->total_count();
            $user_count = Q($user_selector)->total_count();

            $pagination = Lab::pagination($labs, $start, $per_page);

            // 如果不存在lab->group 自动生成其group
            foreach ($labs as $lab) {
                if (!$lab->group->id) {
                    $lab->update_group($group_root)->save();
                }
            }

            $panel_buttons = new ArrayIterator;
            if ($me->is_allowed_to('添加', 'lab')) {
                $panel_buttons[] = [
                    //'url' => URI::url('!labs/lab/add'),
                    'tip'   => I18N::T('labs', '添加课题组'),
                    'text' => I18N::T('labs', '添加'),
                    'extra' => 'q-object="add_lab" q-event="click" q-src="' . URI::url('!labs/lab') .
                    '" q-static="' . H(['form_token' => $form_token]) .
                    '" class="button button_add "',
                ];

            }
            if ($me->is_allowed_to('导出', 'lab')) {

                $panel_buttons[] = [
                    //'url' => URI::url(),
                    'tip'   => I18N::T('labs', '导出Excel'),
                    'text' => I18N::T('labs', '导出'),
                    'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!labs/list') .
                    '" q-static="' . H(['type' => 'excel', 'form_token' => $form_token]) .
                    '" class="button button_save "',

                ];

                $panel_buttons[] = [
                    //'url' => URI::url(),
                    'tip'   => I18N::T('labs', '打印'),
                    'text' => I18N::T('labs', '打印'),
                    'extra' => 'q-object="export" q-event="click" q-src="' . URI::url('!labs/list') .
                    '" q-static="' . H(['type' => 'print', 'form_token' => $form_token]) .
                    '" class="button button_print "',
                ];
            }

            $this->add_css('preview');
            $this->add_js('preview');
            $this->add_css('labs:common');
            $content = V('list', [
                'labs'           => $labs,
                'pagination'     => $pagination,
                'secondary_tabs' => $secondary_tabs,
                'form'           => $form,
                'group'          => $group,
                'group_root'     => $group_root,
                'tab'            => $tab,
                'sort_by'        => $sort_by,
                'sort_asc'       => $sort_asc,
                'tol_count'      => ['lab_count' => $lab_count, 'user_count' => $user_count],
                'panel_buttons'  => $panel_buttons,
            ]);

            $tab_select = $metab == 'all' ? "$tab" : 'my_lab';
            $this->layout->body->primary_tabs
                ->select($tab_select)
                ->set('content', $content);

        }

    }

    //选择完打印列，点击“确定”，执行该打印事件
    public function _export_print($labs, $form)
    {
        $valid_columns   = Config::get('labs.export_columns.labs');
        $visible_columns = Input::form('columns');

        foreach ($valid_columns as $p => $p_name) {
            if (!isset($visible_columns[$p])) {
                unset($valid_columns[$p]);
            }
        }
        $this->layout = V('labs_print', [
            'labs'          => $labs,
            'valid_columns' => $valid_columns,

        ]);

        //记录日志
        $me = L('ME');

        Log::add(strtr('[labs] %user_name[%user_id]打印了实验室列表', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');
    }

    //选择完导出的列，点击“确定”，执行该导出事件
    public function _export_csv($labs, $form)
    {

        $form_token = $form['form_token'];
        $old_form   = (array) $_SESSION[$form_token];
        $new_form   = (array) Input::form();
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $valid_columns   = Config::get('labs.export_columns.labs');
        $visible_columns = $form['columns'];

        foreach ($valid_columns as $p => $p_name) {
            if (!isset($visible_columns[$p])) {
                unset($valid_columns[$p]);
            }
        }

        $csv   = new CSV('php://output', 'w');
        $title = [];
        foreach ($valid_columns as $p => $p_name) {
            $title[] = I18N::T('labs', $valid_columns[$p]);
        }
        $csv->write($title);

        if ($labs->total_count()) {
            foreach ($labs as $lab) {
                $data = [];
                if (array_key_exists('lab_name', $valid_columns)) {
                    $data[] = H($lab->name) ?: '-';
                }
                if (array_key_exists('owner', $valid_columns)) {
                    $owner  = O('user', $lab->owner_id);
                    $data[] = H($owner->name) != '--' ? H($owner->name) : '-';
                }
                if (array_key_exists('lab_contact', $valid_columns)) {
                    $data[] = H($lab->contact) ?: '-';
                }
                if (array_key_exists('group', $valid_columns)) {
                    $anchors = [];
                    if (Config::get('tag.group_limit') >= 0 && $lab->group->id) {
                        $tag      = $lab->group;
                        $tag_root = $lab->group->root;

                        if (!$tag || !$tag->id) {
                            $data[] = '-';
                            continue;
                        };
                        if (!isset($tag_root)) {
                            $tag_root = $tag->root;
                        }

                        if ($tag->id == Tag_Model::root('group')->id) {
                            $data[] = '-';
                            continue;
                        }

                        $found_root = ($tag_root->id == $tag->root->id);

                        foreach ((array) $tag->path as $unit) {
                            list($tag_id, $tag_name) = $unit;
                            if (!$found_root) {
                                if ($tag_id != $tag_root->id) {
                                    continue;
                                }

                                $found_root = true;
                            }
                            $anchors[] = HT($tag_name);
                        }

                        if ($anchors) {
                            $data[] = implode(', ', $anchors);
                        }

                    }
                    if (!$anchors) {
                        $data[] = '-';
                    }
                }
                if (array_key_exists('description', $valid_columns)) {
                    $data[] = H($lab->description) ?: '-';
                }
                if (array_key_exists('creator', $valid_columns)) {
                    $data[] = H($lab->creator->name) != '--' ? H($lab->creator->name) : '-';
                }
                if (array_key_exists('auditor', $valid_columns)) {
                    $data[] = H($lab->auditor->name) != '--' ? H($lab->auditor->name) : '-';
                }
                $csv->write($data);
            }
        }

        $csv->close();
        //记录日志
        $me = L('ME');

        Log::add(strtr('[labs] %user_name[%user_id]以CSV导出了实验室列表', ['%user_name' => $me->name, '%user_id' => $me->id]), 'journal');

    }
}

class List_AJAX_Controller extends AJAX_Controller
{

    /*
    NO.TASK#313(guoping.zhang@2011.01.12)
    列表信息预览功能
     */
    public function index_preview_click()
    {
        $form = Input::form();
        $lab  = O('lab', $form['id']);

        if (!$lab->id) {
            return;
        }

        Output::$AJAX['preview'] = (string) V('labs:lab/preview', ['lab' => $lab]);
    }

    //导出、打印。点击导出、打印链接会触发该事件
    public function index_export_click()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        $type       = $form['type'];
        $columns    = Config::get('labs.export_columns.labs');

        if ($type == 'csv') {
            $title = I18N::T('labs', '请选择要导出CSV的列');
        } elseif ($type == 'print') {
            $title = I18N::T('labs', '请选择要打印的列');
        } elseif ($type = 'excel') {
            $title = I18N::T('labs', '请选择要导出excel的列');
        }
        JS::dialog(V('export_form', [
            'form_token' => $form_token,
            'columns'    => $columns,
            'type'       => $type,
        ]), [
            'title' => I18N::T('labs', $title),
        ]);

    }
    public function index_export_submit()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $type = $form['type'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $file_name_time = microtime(true);
        $file_name_arr  = explode('.', $file_name_time);
        $file_name      = $file_name_arr[0] . $file_name_arr[1];

        if ('excel' == $type) {
            $pid = $this->_export_excel($form['selector'], $form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
                'pid'       => $pid,
            ]), [
                'title' => I18N::T('labs', '导出等待'),
            ]);
        }
    }

    public function _export_excel($selector, $form, $file_name)
    {
        $me              = L('ME');
        $valid_columns   = Config::get('labs.export_columns.labs');
        $visible_columns = $form['columns'] ?: $form['@columns'];

        foreach ($valid_columns as $p => $p_name) {
            if ($visible_columns[$p] == 'null') {
                unset($valid_columns[$p]);
            }
        }

        if (isset($_SESSION[$me->id . '-export'])) {
            foreach ($_SESSION[$me->id . '-export'] as $old_pid => $old_form) {
                $new_valid_form = $form['columns'];

                unset($new_valid_form['form_token']);
                unset($new_valid_form['selector']);
                if ($old_form == $new_valid_form) {
                    unset($_SESSION[$me->id . '-export'][$old_pid]);
                    proc_close(proc_open('kill -9 ' . $old_pid, [], $pipes));
                }
            }
        }

        Log::add(strtr('[labs] %user_name[%user_id]以Excel导出了实验室列表', [
            '%user_name' => $me->name,
            '%user_id'   => $me->id,
        ]), 'journal');

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_labs export ';
        $cmd .= "'" . $selector . "' '" . $file_name . "' '" . json_encode($valid_columns, JSON_UNESCAPED_UNICODE) . "' >/dev/null 2>&1 &";
        $process = proc_open($cmd, [], $pipes);
        $var     = proc_get_status($process);
        proc_close($process);
        $pid        = intval($var['pid']) + 1;
        $valid_form = $form['columns'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id . '-export'][$pid] = $valid_form;
        return $pid;
    }

}
