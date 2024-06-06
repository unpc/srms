<?php

class Index_Controller extends Base_Controller
{

    public function index($tab = 'eq')
    {
        Event::bind('eq_ban.index.content', [$this, '_index_admin_content'], 0, 'admin');
        Event::bind('eq_ban.index.content', [$this, '_index_group_content'], 0, 'group');
        Event::bind('eq_ban.index.content', [$this, '_index_eq_content'], 0, 'eqs');
        Event::bind('eq_ban.index.content', [$this, '_index_violation_content'], 0, 'violation');
        Event::bind('eq_ban.index.content', [$this, '_index_eq_violation_content'], 0, 'eq_violation');

        $this->layout->body->primary_tabs
            ->content_event('eq_ban.index.content')
            ->select($tab);
    }

    public function _index_admin_content($e, $tabs)
    {

        $me   = L('ME');
        $form = Lab::form();

        $panel_buttons = new ArrayIterator;

        if (isset($form['unsealing']) && $form['unsealing']) {
            $this->_index_admin_content_record($tabs);
            return true;
        }

        if ($me->is_allowed_to('添加全局', 'eq_banned')) {
            $panel_buttons = [
                'add' => [
                    'text' => I18N::T('eq_ban', '添加封禁用户'),
                    'tip'   => I18N::T('eq_ban', '添加封禁用户'),
                    'extra' => 'class="button button_add" q-object="add_ban_admin" q-event="click" q-src="' . H(URI::url('!eq_ban/index')) . '"',
                    'url'   => "#",
                ],
            ];
        }

        $selector     = 'eq_banned[!object_name]';
        $pre_selector = [];

        $root = Tag_Model::root('group');

        if ($form['group_id'] && $form['group_id'] != $root->id) {
            $tag = O('tag_group', $form['group_id']);
            $pre_selector['tag_user'] = "$tag user";
        }

        if ($form['name']) {
            $name                 = Q::quote($form['name']);
            $pre_selector['user'] = "user[name*=$name]";
        }

        /**
         * @bugfix 24794 经最终讨论, 筛选课题组检索封禁课题组 & 课题组下成员
         */
        if ($form['lab']) {
            $lab = O('lab', (int) $form['lab']);
            $labId = Q::quote($form['lab']);
            $users = Q("{$lab} user")->to_assoc('id', 'id');
            $user_ids = implode(',', $users);
            $selector .= "[lab_id={$labId}|user_id={$user_ids}]";
        }

        if ($form['dtstart']) {
            $dtstart = Date::get_day_start($form['dtstart']);
            $selector .= "[atime>={$dtstart}]";
        }
        if ($form['dtend']) {
            $dtend = Date::get_day_end($form['dtend']);
            $selector .= "[atime<=$dtend]";
        }
        if ($form['ctstart']) {
            $ctstart = Date::get_day_start($form['ctstart']);
            $selector .= "[ctime>=$ctstart]";
        }
        if ($form['ctend']) {
            $ctend = Date::get_day_end($form['ctend']);
            $selector .= "[ctime<=$ctend]";
        }

        if (!count($pre_selector) && $form['sort'] == 'name') {
            $pre_selector['user'] = 'user';
        }

        if (count($pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        if ($form['sort'] == 'name') {
            $selector .= ":sort(user.name_abbr {$sort_flag})";
        } elseif ($form['sort'] == 'ctime') {
            $selector .= ":sort(ctime {$sort_flag})";
        } elseif ($form['sort'] == 'atime') {
            $selector .= ":sort(atime {$sort_flag})";
        } else {
            $selector .= ':sort(ctime D)';
        }
        
        $bans = Q($selector);

        $pagination = Lab::pagination($bans, (int) $form['st'], 30);

        $columns    = self::get_ban_field($form, $tag, $root);
        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['name', 'group', 'lab'], 'columns' => $columns]);

        $tabs->content = V('eq_ban:admin/list', [
            'form'       => $form,
            'bans'       => $bans,
            'tag'        => $tag,
            'root'       => $root,
            'pagination' => $pagination,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
            'columns'    => $columns,
        ]);
    }

    /**
     * 解封记录方法
     * 这里还是拆开写吧。不写在原来方法里里。万一需要扩展搜索条件，反而代码更加乱
     */
    public function _index_admin_content_record($tabs)
    {
        $me           = L('ME');
        $form         = Lab::form();
        $selector     = 'eq_banned_record[!object_name]';
        $pre_selector = [];

        $root = Tag_Model::root('group');
        if ($form['group_id'] && $form['group_id'] != $root->id) {
            $tag = O('tag_group', $form['group_id']);
            $pre_selector['tag_user'] = "$tag user";
        }

        if ($form['name']) {
            $name                 = Q::quote(trim($form['name']));
            $pre_selector['user'] = "user[name*=$name]";
        }
        if ($form['unsealing_user']) {
            if ('系统' == $form['unsealing_user']) {
                $selector .= '[!unsealing_user]';
            } else {
                $name = Q::quote($form['unsealing_user']);
                $pre_selector['unsealing_user'] = "user[name*=$name]<unsealing_user";
            }
        }
        if ($form['lab']) {
            $lab = O('lab', (int) $form['lab']);
            $labId = Q::quote($form['lab']);
            $users = Q("{$lab} user")->to_assoc('id', 'id');
            $user_ids = implode(',', $users);
            $selector .= "[lab_id={$labId}|user_id={$user_ids}]";
        }
        if ($form['dtstart']) {
            $dtstart = Date::get_day_start($form['dtstart']);
            $selector .= "[atime>=$dtstart]";
        }
        if ($form['dtend']) {
            $dtend = Date::get_day_end($form['dtend']);
            $selector .= "[atime<=$dtend]";
        }
        if ($form['ctstart']) {
            $ctstart = Date::get_day_start($form['ctstart']);
            $selector .= "[ctime>=$ctstart]";
        }
        if ($form['ctend']) {
            $dtend = Date::get_day_end($form['ctend']);
            $selector .= "[ctime<=$dtend]";
        }

        if (!count($pre_selector) && $form['sort'] == 'name') {
            $pre_selector['user'] = 'user';
        }

        if (count($pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }

        $new_selector = Event::trigger('eq_ban.list.selector', $form, $selector, $pre_selector);

        if ($new_selector) {
            $selector = $new_selector;
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        if ($form['sort'] == 'name') {
            $selector .= ":sort(user.name_abbr {$sort_flag})";
        } elseif ($form['sort'] == 'ctime') {
            $selector .= ":sort(ctime {$sort_flag})";
        } elseif ($form['sort'] == 'atime') {
            $selector .= ":sort(atime {$sort_flag})";
        } elseif ($form['sort'] == 'unsealing_ctime') {
            $selector .= ":sort(unsealing_ctime {$sort_flag})";
        } else {
            $selector .= ':sort(ctime D)';
        }
        $bans          = Q($selector);
        $panel_buttons = [];
        if ($me->is_allowed_to('添加全局', 'eq_banned')) {
            if (Config::get('eq_ban.show_export')) {
                $form_token            = Session::temp_token('eq_ban_list_', 300);
                $form['form_token']    = $form_token;
                $_SESSION[$form_token] = $selector;
                $panel_buttons += [
                    'export' => [
                        'text' => I18N::T('eq_ban', '导出封禁用户'),
                        'extra' => 'class="button button_save" q-object="export" q-event="click" q-src="' . H(URI::url('!eq_ban/index', ['form_token' => $form_token, 'type' => 'export_ban_unseal'])) . '"',
                        'url'   => "#",
                    ],
                ];
            }
        }

        $per_page = Config::get('per_page.eq_banned', 25);
        $pagination = Lab::pagination($bans, (int) $form['st'], $per_page);

        $columns = [
            'group'=>[
                'weight'=>10,
                'title'=>I18N::T('eq_ban', '用户机构'),
                'invisible'=>TRUE,
                'filter' => [
                    'form' => V('eq_ban:eq_ban_table/filters/user_group', [
                        'name'=>'group_id',
                        'tag'=> $tag,
                        'root'=>$root,
                    ]),
                    'value' => $tag->id ? H($tag->name) : NULL,
                    'field' => 'group_id'
                ]
            ],
            'name'=>[
                'weight'=>20,
                'title'=>I18N::T('eq_ban', '姓名'),
                'align'=>'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                'filter'=> [
                    'form' => V('eq_ban:eq_ban_table/filters/text', ['value' => $form['name']]),
                    'value' => $form['name'] ? H($form['name']) : NULL
                ],
            ],
            'unsealing_user'=>[
                'weight'=>20,
                'title'=>I18N::T('eq_ban', '解封操作人'),
                'align'=>'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                'filter'=> [
                    'form' => V('eq_ban:eq_ban_table/filters/text', ['name'=>'unsealing_user', 'value'=>$form['unsealing_user']]),
                    'value' => $form['unsealing_user'] ? H($form['unsealing_user']) : NULL
                ],
            ],
            'unsealing_ctime' => [
                'weight' => 20,
                'title'=>I18N::T('eq_ban', '解封时间'),
                'align' => 'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
            ],
            'lab'=>[
                'weight'=>25,
                'title'=>I18N::T('labs', '实验室'),
                'invisible'=>TRUE,
                'filter' => [
                    'form'=>Widget::factory('labs:lab_selector', [
                        'name'=>'lab',
                        'selected_lab'=> $lab,
                        'all_labs'=>TRUE,
                        'no_lab'=>TRUE,
                        'size'=>30,
                    ]),
                    'value'=> $lab->id ? H($lab->name) : NULL,
                ]
            ],
            'reason'=>[
                'weight'=>30,
                'title'=>I18N::T('eq_ban', '封禁原因'),
                'align' => 'left',
                'nowrap'=>TRUE,
            ],
            'ctime' => [
                'weight' => 40,
                'title'=>I18N::T('eq_ban', '封禁时间'),
                'align' => 'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                'filter' => [
                    'form' => V('eq_ban:eq_ban_table/filters/ctime', [
                            'form'=>$form,
                            'field_title' => '封禁时间',
                        ]),
                        'field' => 'ctstart,ctend',
                        'value' => $form['ctstart'] || $form['ctend']
                ]
            ],
            'atime'=>[
                'weight'=>50,
                'title'=>I18N::T('eq_ban', '到期时间'),
                'align' => 'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                'filter' => [
                    'form' => V('eq_ban:eq_ban_table/filters/time', [
                        'form'=>$form,
                    ]),
                    'field'=>'dtstart,dtend',
                    'value' => $form['dtstart'] || $form['dtend']
                ]
            ],
            'rest'=>[
                'weight'=>60,
                'align'=>'right',
                'nowrap'=>TRUE,
            ]
        ];

        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['name'], 'columns' => $columns]);

        $tabs->content = V('eq_ban:admin/record', [
            'columns'       => $columns,
            'form'          => $form,
            'bans'          => $bans,
            'tag'           => $tag,
            'root'          => $root,
            'panel_buttons' => $panel_buttons,
            'pagination'    => $pagination,
            'sort_by'       => $sort_by,
            'sort_asc'      => $sort_asc,
        ]);
    }

    public function get_ban_field($form, $tag, $root)
    {
        if ($form['dtstart'] || $form['dtend']) {
            $form['time'] = true;
        }

        $lab = is_object($form['lab']) ? $form['lab'] : O('lab', $form['lab']);

        $columns = [
            'group'  => [
                'weight'    => 10,
                'title'     => I18N::T('eq_ban', '用户机构'),
                'invisible' => true,
                'filter'    => [
                    'form'  => V('eq_ban:eq_ban_table/filters/user_group', [
                        'name' => 'group_id',
                        'tag'  => $tag,
                        'root' => $root,
                        'field_title' => I18N::T('eq_ban', '请选择组织机构'),
                    ]),
                    'value' => $tag->id ? H($tag->name) : null,
                    'field' => 'group_id',
                ],
                'input_type' => 'select'
            ],
            'name'   => [
                'weight'   => 20,
                'title'    => I18N::T('eq_ban', '姓名'),
                'align'    => 'left',
                'nowrap'   => true,
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_ban:eq_ban_table/filters/text', ['value' => $form['name']]),
                    'value' => $form['name'] ? H($form['name']) : null,
                ],
            ],
            /* 'unsealing_user'=>[
                'weight'=>20,
                'title'=>I18N::T('eq_ban', '解封操作人'),
                'align'=>'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                'filter'=> [
                    'form' => V('eq_ban:eq_ban_table/filters/text', ['name'=>'unsealing_user', 'value'=>$form['unsealing_user']]),
                    'value' => $form['unsealing_user'] ? H($form['unsealing_user']) : NULL
                ],
            ],
            'unsealing_ctime' => [
                'weight' => 20,
                'title'=>I18N::T('eq_ban', '解封时间'),
                'align' => 'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
            ], */
            'lab'    => [
                'weight'    => 25,
                'title'     => I18N::T('labs', '实验室'),
                'invisible' => true,
                'filter'    => [
                    'form'  => Widget::factory('labs:lab_selector', [
                        'name'         => 'lab',
                        'selected_lab' => $lab,
                        'all_labs'     => true,
                        'no_lab'       => true,
                        'size'         => 30,
                    ]),
                    'value' => $lab->id ? H($lab->name) : null,
                ],
                'input_type' => 'select'
            ],
            'reason' => [
                'weight' => 30,
                'title'  => I18N::T('eq_ban', '封禁原因'),
                'align'  => 'left',
                'nowrap' => true,
            ],
            'ctime'  => [
                'weight'   => 40,
                'title'    => I18N::T('eq_ban', '封禁时间'),
                'align'    => 'left',
                'nowrap'   => true,
                'sortable' => true,
                'filter' => [
                    'form' => V('eq_ban:eq_ban_table/filters/ctime', [
                            'form'=>$form,
                            'field_title' => '封禁时间',
                        ]),
                        'field' => 'ctstart,ctend',
                        'value' => $form['ctstart'] || $form['ctend']
                ]
            ],
            'atime'  => [
                'weight'   => 50,
                'title'    => I18N::T('eq_ban', '到期时间'),
                'align'    => 'left',
                'nowrap'   => true,
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_ban:eq_ban_table/filters/time', [
                        'form' => $form,
                        'field_title' => '到期时间',
                    ]),
                    'field' => 'dtstart,dtend',
                    'value' => $form['dtstart'] || $form['dtend']
                ],
                'input_type' => 'select'
            ],
            'rest'   => [
                'title'  => '操作',
                'weight' => 60,
                'align'  => 'right',
                'nowrap' => true,
            ],
        ];

        $columns = new ArrayIterator($columns);
        Event::trigger('eq_ban.list.columns', $form, $columns);

        return (array)$columns;
    }

    public function _index_group_content($e, $tabs)
    {

        $me   = L('ME');
        $form = Lab::form();

        if (isset($form['unsealing']) && $form['unsealing']) {
            $this->_index_group_content_record($tabs);
            return true;
        }

        $panel_buttons = new ArrayIterator;

        if ($me->is_allowed_to('添加机构', 'eq_banned')) {
            $panel_buttons = [
                'add' => [
                    'text' => I18N::T('eq_ban', '添加封禁用户'),
                    'tip'   => I18N::T('eq_ban', '添加封禁用户'),
                    'extra' => 'class="button button_add" q-object="add_ban_group" q-event="click" q-src="' . H(URI::url('!eq_ban/index')) . '"',
                    'url'   => "#",
                ],
            ];
        }

        $selector     = 'eq_banned[object_name=tag_group]';
        $pre_selector = [];

        $root = Tag_Model::root('group');
        if ($form['group_id'] && $form['group_id'] != $root->id) {
            $tag = O('tag_group', $form['group_id']);
            $pre_selector['tag_user'] = "$tag user";
        }
        if (!$me->is_allowed_to('查看全局', 'eq_banned')) {
            $tag                 = $me->group;
            $pre_selector['tag'] = "$tag<object";
        }

        if ($form['name']) {
            $name                 = Q::quote($form['name']);
            $pre_selector['user'] = "user[name*=$name]";
        }
        if ($form['lab']) {
            $lab = O('lab', (int) $form['lab']);
            $labId = Q::quote($form['lab']);
            $users = Q("{$lab} user")->to_assoc('id', 'id');
            $user_ids = implode(',', $users);
            $selector .= "[lab_id={$labId}|user_id={$user_ids}]";
        }

        if ($form['dtstart']) {
            $selector .= "[atime>={$form['dtstart']}]";
        }

        if ($form['dtend']) {
            $selector .= "[atime<={$form['dtend']}]";
        }

        if ($form['ctstart']) {
            $ctstart = Date::get_day_start($form['ctstart']);
            $selector .= "[ctime>=$ctstart]";
        }
        if ($form['ctend']) {
            $ctend = Date::get_day_end($form['ctend']);
            $selector .= "[ctime<=$ctend]";
        }

        if (!$pre_selector['user'] && !$pre_selector['tag_user'] && $form['sort'] == 'name') {
            $pre_selector['user'] = 'user';
        }

        if (count($pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }

        $new_selector = Event::trigger('eq_ban.list.selector', $form, $selector, $pre_selector);

        if ($new_selector) $selector = $new_selector;

        $sort_by = $form['sort'];
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        if ($form['sort'] == 'name') {
            $selector .= ":sort(user.name_abbr {$sort_flag})";
        } elseif ($form['sort'] == 'object') {
            $selector .= ":sort(obj_abbr {$sort_flag})";
        } elseif ($form['sort'] == 'ctime') {
            $selector .= ":sort(ctime {$sort_flag})";
        } elseif ($form['sort'] == 'atime') {
            $selector .= ":sort(atime {$sort_flag})";
        } else {
            $selector .= ':sort(ctime D)';
        }

        $bans = Q($selector);

        $pagination = Lab::pagination($bans, (int) $form['st'], 30);

        $columns    = self::get_ban_group_field($form, $tag, $root);
        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['name', 'group', 'lab'], 'columns' => $columns]);

        $tabs->content = V('eq_ban:group/list', [
            'form'       => $form,
            'bans'       => $bans,
            'tag'        => $tag,
            'root'       => $root,
            'pagination' => $pagination,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
            // 'search_box' => $search_box,
            'columns'    => $columns,
        ]);
    }

    /**
     * 解封记录方法
     * 这里还是拆开写吧。不写在原来方法里里。万一需要扩展搜索条件，反而代码更加乱
     */
    public function _index_group_content_record($tabs)
    {
        $me   = L('ME');
        $form = Lab::form();

        $selector     = 'eq_banned_record[object_name=tag_group]';
        $pre_selector = [];

        $root = Tag_Model::root('group');
        if ($form['group_id'] && $form['group_id'] != $root->id) {
            $tag = O('tag_group', $form['group_id']);
            $pre_selector['tag_user'] = "$tag user";
        }

        if (!$me->is_allowed_to('查看全局', 'eq_banned')) {
            $tag                 = $me->group;
            $pre_selector['tag'] = "$tag<object";
        }

        if ($form['name']) {
            $name                 = Q::quote(trim($form['name']));
            $pre_selector['user'] = "user[name*=$name]";
        }
        if ($form['unsealing_user']) {
            if ('系统' == $form['unsealing_user']) {
                $selector .= '[!unsealing_user]';
            } else {
                $name = Q::quote($form['unsealing_user']);
                $pre_selector['unsealing_user'] = "user[name*=$name]<unsealing_user";
            }
        }
        if ($form['lab']) {
            $lab = O('lab', (int) $form['lab']);
            $labId = Q::quote($form['lab']);
            $users = Q("{$lab} user")->to_assoc('id', 'id');
            $user_ids = implode(',', $users);
            $selector .= "[lab_id={$labId}|user_id={$user_ids}]";
        }
        if ($form['dtstart']) {
            $dtstart = Date::get_day_start($form['dtstart']);
            $selector .= "[atime>=$dtstart]";
        }
        if ($form['dtend']) {
            $dtend = Date::get_day_end($form['dtend']);
            $selector .= "[atime<=$dtend]";
        }
        if ($form['ctstart']) {
            $ctstart = Date::get_day_start($form['ctstart']);
            $selector .= "[ctime>=$ctstart]";
        }
        if ($form['ctend']) {
            $ctend = Date::get_day_end($form['ctend']);
            $selector .= "[ctime<=$ctend]";
        }

        if (!$pre_selector['user'] && !$pre_selector['tag_user'] && $form['sort'] == 'name') {
            $pre_selector['user'] = 'user';
        }

        if (count($pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }

        $new_selector = Event::trigger('eq_ban.list.selector', $form, $selector, $pre_selector);

        if ($new_selector) {
            $selector = $new_selector;
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        if ($form['sort'] == 'name') {
            $selector .= ":sort(user.name_abbr {$sort_flag})";
        } elseif ($form['sort'] == 'object') {
            $selector .= ":sort(obj_abbr {$sort_flag})";
        } elseif ($form['sort'] == 'ctime') {
            $selector .= ":sort(ctime {$sort_flag})";
        } elseif ($form['sort'] == 'atime') {
            $selector .= ":sort(atime {$sort_flag})";
        } elseif ($form['sort'] == 'unsealing_ctime') {
            $selector .= ":sort(unsealing_ctime {$sort_flag})";
        } else {
            $selector .= ':sort(ctime D)';
        }

        $bans = Q($selector);

        $panel_buttons = [];
        if ($me->is_allowed_to('添加全局', 'eq_banned')) {
            if (Config::get('eq_ban.show_export')) {
                $form_token            = Session::temp_token('eq_ban_list_', 300);
                $form['form_token']    = $form_token;
                $_SESSION[$form_token] = $selector;
                $panel_buttons += [
                    'export' => [
                        'text' => I18N::T('eq_ban', '导出封禁用户'),
                        'extra' => 'class="button button_save" q-object="export" q-event="click" q-src="' . H(URI::url('!eq_ban/index', ['form_token' => $form_token, 'type' => 'export_ban_unseal'])) . '"',
                        'url'   => "#",
                    ],
                ];
            }
        }
        $columns = [
            'group'=>[
                'weight'=>10,
                'title'=>I18N::T('eq_ban', '用户机构'),
                'invisible' => TRUE,
            ],
            'name'=>[
                'weight'=>20,
                'title'=>I18N::T('eq_ban', '姓名'),
                'align'=>'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                'filter'=> [
                    'form' => V('eq_ban:eq_ban_table/filters/text', ['value' => $form['name']]),
                    'value' => $form['name'] ? H($form['name']) : NULL
                ],
            ],
            'unsealing_user'=>[
                'weight'=>20,
                'title'=>I18N::T('eq_ban', '解封操作人'),
                'align'=>'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                'filter'=> [
                    'form' => V('eq_ban:eq_ban_table/filters/text', ['name' => 'unsealing_user', 'value'=>$form['unsealing_user']]),
                    'value' => $form['unsealing_user'] ? H($form['unsealing_user']) : NULL
                ],
            ],
            'unsealing_ctime' => [
                'weight' => 20,
                'title'=>I18N::T('eq_ban', '解封时间'),
                'align' => 'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
            ],
            'object'=>[
                'weight'=>25,
                'title'=>I18N::T('eq_ban', '组织机构'),
                'align' => 'left',
                'sortable'=>TRUE,
                'nowrap'=>TRUE,
            ],
            'lab'=>[
                'weight'=>25,
                'title'=>I18N::T('labs', '实验室'),
                'invisible'=>TRUE,
                'filter' => [
                    'form'=>Widget::factory('labs:lab_selector', [
                        'name'=>'lab',
                        'selected_lab'=> $lab,
                        'all_labs'=>TRUE,
                        'no_lab'=>TRUE,
                        'size'=>30,
                    ]),
                    'value'=> $lab->id ? H($lab->name) : NULL,
                ]
            ],
            'reason'=>[
                'weight'=>30,
                'title'=>I18N::T('eq_ban', '封禁原因'),
                'align' => 'left',
                'nowrap'=>TRUE,
            ],
            'ctime' => [
                'weight' => 40,
                'title'=>I18N::T('eq_ban', '封禁时间'),
                'align' => 'left',
                'sortable'=>TRUE,
                'nowrap'=>TRUE,
                'filter' => [
                    'form' => V('eq_ban:eq_ban_table/filters/ctime', [
                            'form'=>$form,
                            'field_title' => '封禁时间',
                        ]),
                        'field' => 'ctstart,ctend',
                        'value' => $form['ctstart'] || $form['ctend']
                ]
            ],
            'atime'=>[
                'weight'=>50,
                'title'=>I18N::T('eq_ban', '到期时间'),
                'align' => 'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                'filter' => [
                    'form' => V('eq_ban:eq_ban_table/filters/time', [
                        'form'=>$form,
                    ]),
                    'field'=>'dtstart,dtend,dtstart_check,dtend_check',
                    'value' => $form['dtstart'] || $form['dtend']
                ]
            ],
            'rest'=>[
                'weight'=>60,
                'align'=>'left',
                'nowrap'=>TRUE,
            ]
        ];
        if ($me->is_allowed_to('查看全局', 'eq_banned')) {
            $columns['group']['filter'] = [
                'form' => V('eq_ban:eq_ban_table/filters/user_group', [
                    'name'=>'group_id',
                    'tag'=> $tag,
                    'root'=>$root,
                ]),
                'value' => $tag->id ? H($tag->name) : NULL,
                'field' => 'group_id'
            ];
        }

        $tabs->search_box = V('application:search_box',
            ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['name'], 'columns' => $columns]);


        $per_page = Config::get('per_page.eq_banned', 25);
        $pagination = Lab::pagination($bans, (int) $form['st'], $per_page);
        $tabs->content = V('eq_ban:group/record', [
            'columns'       => $columns,
            'form'          => $form,
            'bans'          => $bans,
            'tag'           => $tag,
            'root'          => $root,
            'panel_buttons' => $panel_buttons,
            'pagination'    => $pagination,
            'sort_by'       => $sort_by,
            'sort_asc'      => $sort_asc,
        ]);
    }

    public function get_ban_group_field($form, $tag, $root)
    {

        if ($form['dtstart'] || $form['dtend']) {
            $form['time'] = true;
        }

        $lab     = is_object($form['lab']) ? $form['lab'] : O('lab', $form['lab']);
        $columns = [
            'group'  => [
                'weight'    => 10,
                'title'     => I18N::T('eq_ban', '用户机构'),
                'invisible' => true,
            ],
            'name'   => [
                'weight'   => 20,
                'title'    => I18N::T('eq_ban', '姓名'),
                'align'    => 'left',
                'nowrap'   => true,
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_ban:eq_ban_table/filters/text', ['value' => $form['name']]),
                    'value' => $form['name'] ? H($form['name']) : null,
                ],
            ],
            /* 'unsealing_user'=>[
                'weight'=>20,
                'title'=>I18N::T('eq_ban', '解封操作人'),
                'align'=>'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                'filter'=> [
                    'form' => V('eq_ban:eq_ban_table/filters/text', ['name'=>'unsealing_user', 'value'=>$form['unsealing_user']]),
                    'value' => $form['unsealing_user'] ? H($form['unsealing_user']) : NULL
                ],
            ], */
            /* 'unsealing_ctime' => [
                'weight' => 20,
                'title'=>I18N::T('eq_ban', '解封时间'),
                'align' => 'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
            ], */
            'object' => [
                'weight'   => 25,
                'title'    => I18N::T('eq_ban', '组织机构'),
                'align'    => 'left',
                'sortable' => true,
                'nowrap'   => true,
            ],
            'lab'    => [
                'weight'    => 30,
                'title'     => I18N::T('labs', '实验室'),
                'invisible' => true,
                'filter'    => [
                    'form'  => Widget::factory('labs:lab_selector', [
                        'name'         => 'lab',
                        'selected_lab' => $lab,
                        'all_labs'     => true,
                        'no_lab'       => true,
                        'size'         => 30,
                    ]),
                    'value' => $lab->id ? H($lab->name) : null,
                ],
                'input_type' => 'select'
            ],
            'reason' => [
                'weight' => 35,
                'title'  => I18N::T('eq_ban', '封禁原因'),
                'align'  => 'left',
                'nowrap' => true,
            ],
            'ctime'  => [
                'weight'   => 40,
                'title'    => I18N::T('eq_ban', '封禁时间'),
                'align'    => 'left',
                'sortable' => true,
                'nowrap'   => true,
                'filter' => [
                    'form' => V('eq_ban:eq_ban_table/filters/ctime', [
                            'form'=>$form,
                            'field_title' => '封禁时间',
                        ]),
                        'field' => 'ctstart,ctend',
                        'value' => $form['ctstart'] || $form['ctend'],
                ]
            ],
            'atime'  => [
                'weight'   => 50,
                'title'    => I18N::T('eq_ban', '到期时间'),
                'align'    => 'left',
                'nowrap'   => true,
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_ban:eq_ban_table/filters/time', [
                        'form' => $form,
                        'field_title' => '到期时间',
                    ]),
                    'field' => 'dtstart,dtend',
                    'value' => $form['time'] ? H($form['time']) : null,
                ],
                'input_type' => 'select'
            ],
            'rest'   => [
                'title'  => '操作',
                'weight' => 60,
                'align'  => 'right',
                'nowrap' => true,
            ],
        ];

        $me = L('ME');
        if ($me->is_allowed_to('查看全局', 'eq_banned')) {
            $columns['group']['filter'] = [
                'form'  => V('eq_ban:eq_ban_table/filters/user_group', [
                    'name' => 'group_id',
                    'tag'  => $tag,
                    'root' => $root,
                    'field_title' => I18N::T('eq_ban', '请选择组织机构'),
                ]),
                'value' => $tag->id ? H($tag->name) : null,
                'field' => 'group_id',
            ];
            $columns['group']['input_type'] = 'select';
        }

        $columns = new ArrayIterator($columns);
        Event::trigger('eq_ban.list.columns', $form, $columns);

        return (array) $columns;
    }

    public function _index_eq_content($e, $tabs)
    {

        $me   = L('ME');
        $form = Lab::form();

        if (isset($form['unsealing']) && $form['unsealing']) {
            $this->_index_eq_content_record($tabs);
            return true;
        }

        $panel_buttons = new ArrayIterator;

        if ($me->is_allowed_to('添加仪器', 'eq_banned')) {
            $panel_buttons = [
                'add' => [
                    'text' => I18N::T('eq_ban', '添加封禁用户'),
                    'tip'   => I18N::T('eq_ban', '添加封禁用户'),
                    'extra' => 'class="button button_add" q-object="add_ban_eq" q-event="click" q-src="' . H(URI::url('!eq_ban/index')) . '"',
                    'url'   => "",
                ],
            ];
        }

        $selector     = 'eq_banned[object_name=equipment]';
        $pre_selector = [];

        $root = Tag_Model::root('group');
        /* if ($form['group_id'] && $form['group_id'] != $root->id) {
        $tag = O('tag', $form['group_id']);
        $pre_selector[] = "$tag user";
        } */
        if (!$me->is_allowed_to('查看全局', 'eq_banned')) {
            if (!$me->is_allowed_to('查看机构', 'eq_banned')) {
                $pre_selector['me_equipment'] = "$me<@(incharge|contact) equipment<object";
            } else {
                $tag                           = $me->group;
                $pre_selector['tag_equipment'] = "$tag equipment<object";
            }
        }
        if ($form['name']) {
            $name                 = Q::quote($form['name']);
            $pre_selector['user'] = "user[name*=$name]";
        }
        if ($form['eq_name']) {
            $eq_name                   = Q::quote($form['eq_name']);
            $pre_selector['equipment'] = "equipment[name*=$eq_name]<object";
        }
        /* if ($form['dtstart']) {
        $dtstart = Date::get_day_start($form['dtstart']);
        $selector .= "[atime>={$form['dtstart']}]";
        }
        if ($form['dtend']) {
        $dtend = Date::get_day_end($form['dtend']);
        $selector .= "[atime<={$form['dtend']}]";
        } */

        if ($form['ctstart']) {
            $ctstart = Date::get_day_start($form['ctstart']);
            $selector .= "[ctime>=$ctstart]";
        }
        if ($form['ctend']) {
            $dtend = Date::get_day_end($form['ctend']);
            $selector .= "[ctime<=$dtend]";
        }

        if (!$pre_selector['user'] && $form['sort'] == 'name') {
            $pre_selector['user'] = 'user';
        }

        if (count($pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        if ($form['sort'] == 'name') {
            $selector .= ":sort(user.name_abbr {$sort_flag})";
        } elseif ($form['sort'] == 'eq_name') {
            $selector .= ":sort(obj_abbr {$sort_flag})";
        } elseif ($form['sort'] == 'ctime') {
            $selector .= ":sort(ctime {$sort_flag})";
        } elseif ($form['sort'] == 'atime') {
            $selector .= ":sort(atime {$sort_flag})";
        } else {
            $selector .= ':sort(ctime D)';
        }

        $bans = Q($selector);

        $columns = self::get_ban_eq_filed($form, $tag, $root);

        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['name', 'eq_name'], 'columns' => $columns]);

        $pagination    = Lab::pagination($bans, (int) $form['st'], 30);
        $tabs->content = V('eq_ban:equipment/list', [
            'form'       => $form,
            'bans'       => $bans,
            'tag'        => $tag,
            'root'       => $root,
            'pagination' => $pagination,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
            'search_box' => $search_box,
            'columns'    => $columns,
        ]);
    }

    public function get_ban_eq_filed($form, $tag, $root)
    {
        $me = L('ME');

        if ($form['dtstart'] || $form['dtend']) {
            $form['time'] = true;
        }

        $columns = [
            'name'    => [
                'weight'   => 10,
                'title'    => I18N::T('eq_ban', '姓名'),
                'align'    => 'left',
                'nowrap'   => true,
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_ban:eq_ban_table/filters/text', ['value' => $form['name']]),
                    'value' => $form['name'] ? H($form['name']) : null,
                ],
            ],
            'eq_name' => [
                'weight'   => 20,
                'title'    => I18N::T('eq_ban', '封禁仪器'),
                'align'    => 'left',
                'nowrap'   => true,
                'sortable' => true,
                'filter'   => [
                    'form'  => V('eq_ban:eq_ban_table/filters/text', ['name' => 'eq_name', 'value' => $form['eq_name']]),
                    'value' => $form['eq_name'] ? H($form['eq_name']) : null,
                ],
            ],
            'reason'  => [
                'weight' => 30,
                'title'  => I18N::T('eq_ban', '封禁原因'),
                'align'  => 'left',
                'nowrap' => true,
            ],
            'ctime'   => [
                'weight'   => 40,
                'title'    => I18N::T('eq_ban', '封禁时间'),
                'align'    => 'left',
                'nowrap'   => true,
                'sortable' => true,
                'filter' => [
                    'form' => V('eq_ban:eq_ban_table/filters/ctime', [
                        'form'=>$form,
                    ]),
                    'field' => 'ctstart,ctend',
                    'value' => $form['ctstart'] || $form['ctend'],
                ]
            ],
            'atime'   => [
                'weight'   => 50,
                'title'    => I18N::T('eq_ban', '到期时间'),
                'align'    => 'left',
                'nowrap'   => true,
                'sortable' => true,
            ],
            'rest'    => [
                'title'  => '操作',
                'weight' => 60,
                'align'  => 'right',
                'nowrap' => true,
            ],
        ];

        return $columns;
    }

    public function get_violation_columns($form, $params = [])
    {
        extract($params);
        $columns = [
            // '@' => NULL,
            'user_name'   => [
                'title'    => I18N::T('eq_ban', '姓名'),
                'filter'   => [
                    'form'  => V('eq_ban:violation_table/filters/user_name', ['user_name' => $form['user_name']]),
                    'value' => $form['user_name'] ? H($form['user_name']) : null,
                ],
                'align'    => 'left',
                'weight'   => 30,
                'nowrap'   => true,
                'sortable' => true,
            ],
            'total'       => [
                'title'    => I18N::T('eq_ban', '违规总次数'),
                'align'    => 'center',
                'weight'   => 40,
                'nowrap'   => true,
                'sortable' => true,
            ],
            'late'        => [
                'title'    => I18N::T('eq_ban', '迟到次数'),
                'align'    => 'center',
                'weight'   => 50,
                'nowrap'   => true,
                'sortable' => true,
            ],
            'leave_early' => [
                'title'    => I18N::T('eq_ban', '早退次数'),
                'align'    => 'center',
                'weight'   => 60,
                'nowrap'   => true,
                'sortable' => true,
            ],
            'overtime'    => [
                'title'    => I18N::T('eq_ban', '超时次数'),
                'align'    => 'center',
                'weight'   => 70,
                'nowrap'   => true,
                'sortable' => true,
            ],
            'miss'        => [
                'title'    => I18N::T('eq_ban', '爽约次数'),
                'align'    => 'center',
                'weight'   => 80,
                'nowrap'   => true,
                'sortable' => true,
            ],
            'violate' => [
                'title' => I18N::T('eq_ban', '违规行为'),
                'align' => 'center',
                'weight' => 80,
                'nowrap' => TRUE,
                'sortable' => TRUE
            ],
        ];

        $me = L('ME');

        if ($me->access('管理所有内容')) {
            $columns['group'] = [
                'title'     => I18N::T('eq_ban', '用户机构'),
                'filter'    => [
                    'form'  => V('eq_ban:violation_table/filters/group', [
                        'tag'  => $group,
                        'root' => $root,
                        'field_title' => '请选择用户机构'
                    ]),
                    'value' => $group->id ? H($group->name) : null,
                    'field' => 'group_id',
                ],
                'weight'    => 10,
                'invisible' => true,
                'input_type' => 'select',
            ];
        }

        if ($me->is_allowed_to('查看下属机构的违规记录', 'eq_banned')) {
            $columns['lab'] = [
                'title'     => I18N::T('eq_ban', '课题组'),
                'filter'    => [
                    'form'  => V('eq_ban:violation_table/filters/lab', ['lab' => $form['lab']]),
                    'value' => $form['lab'] ? H($lab->name) : null,
                ],
                'weight'    => 20,
                'invisible' => true,
                'input_type' => 'select',
            ];
        }

        return $columns;
    }

    public function _index_violation_content($e, $tabs)
    {

        $me = L('ME');

        $form = Lab::form();

        $root = Tag_Model::root('group');

        if ($me->access('管理所有内容') || $me->is_allowed_to('编辑全局', 'eq_banned')) {

        } else if ($me->is_allowed_to('查看下属机构的违规记录', 'eq_banned')) {
            $form['group_id'] = $me->group->id;
        } else if (Q("$me<pi lab")->total_count()) {
            $form['lab'] = Q("$me lab")->current()->id;
        }

        $group_id = Q::quote($form['group_id']);

        $group = O('tag_group', $group_id);

        $lab_id = Q::quote($form['lab']);

        $lab = O('lab', $lab_id);

        $pre_selector = [];

        $selector = "user_violation[total_count>0]";

        if ($form['group_id'] && ($group->root->id == $root->id)) {
            $pre_selector['group_user'] = "{$group} user";
        } else {
            $group = null;
        }

        if ($form['lab']) {
            $pre_selector['lab_user'] = "{$lab} user";
        } else {
            $lab = null;
        }

        if ($form['user_name']) {
            $user_name            = Q::quote($form['user_name']);
            $pre_selector['user'] = "user[name*=$user_name]";
        }

        if (!$pre_selector['group_user'] && !$pre_selector['lab_user'] && !$pre_selector['user'] && $form['sort'] == 'user_name') {
            $pre_selector['user'] = "user";
        }
        if (count($pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        if ($form['sort'] == 'user_name') {
            $selector .= ":sort(user.name_abbr {$sort_flag})";
        } elseif ($form['sort'] == 'total') {
            $selector .= ":sort(total_count {$sort_flag})";
        } elseif ($form['sort']) {
            $selector .= ":sort(eq_{$sort_by}_count {$sort_flag})";
        }

        $users = Q($selector);

        $pagination = Lab::pagination($users, (int) $form['st'], 15);
        $params     = ['group' => $group, 'root' => $root, 'lab' => $lab, 'form' => $form];
        $columns    = $this->get_violation_columns($form, $params);
        $top_input_arr = ['user_name'];
        if (isset($columns['group'])) $top_input_arr[]='group';
        if (isset($columns['lab'])) $top_input_arr[]='lab';
        $tabs->search_box = V('application:search_box', ['panel_buttons' => $panel_buttons, 'top_input_arr' => $top_input_arr, 'columns' => $columns]);

        $tabs->content = V('eq_ban:violation/list', [
            'form'       => $form,
            'users'      => $users,
            'root'       => $root,
            'group'      => $group,
            'lab'        => $lab,
            'columns'    => $columns,
            'search_box' => $search_box,
            'pagination' => $pagination,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
        ]);
    }

    public function _index_eq_violation_content($e, $tabs)
    {
        $me = L('ME');
        $form = Lab::form(function (&$old_form, &$form) {
        });

        $selector = 'user_violation_record';
        $pre_selector = [];

        if (!$me->is_allowed_to('查看全局', 'eq_banned')) {
            if (!$me->is_allowed_to('查看机构', 'eq_banned')) {
                $pre_selector['me_equipment'] = "$me<@(incharge|contact) equipment";
            } else {
                $tag = $me->group;
                $pre_selector['tag_equipment'] = "$tag equipment";
            }
        }
        if ($form['name']) {
            $name = Q::quote($form['name']);
            $pre_selector['user'] = "user[name*=$name]";
        }
        if ($form['eq_name']) {
            $eq_name = Q::quote($form['eq_name']);
            $pre_selector['equipment'] = "equipment[name*=$eq_name]";
        }

        if (!$pre_selector['user'] && $form['sort'] == 'name') {
            $pre_selector['user'] = 'user';
        }

        if (count($pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }

        $violations = Q($selector);

        if ($me->is_allowed_to('编辑仪器违规记录', 'eq_banned')) {
            $panel_buttons = [
                'add' => [
                    'text' => I18N::T('eq_ban', '添加违规信息'),
                    'extra' => 'class="button button_add" q-object="add_eq_violate" q-event="click" q-src="' . H(URI::url('!eq_ban/index')) . '"',
                    'url' => "#",
                ],
            ];
        }

        $pagination = Lab::pagination($violations, (int) $form['st'], 15);
        $tabs->content = V('eq_ban:equipment/violate_list', [
            'form' => $form,
            'violations' => $violations,
            'tag' => $tag,
            'root' => $root,
            'pagination' => $pagination,
            'panel_buttons' => $panel_buttons,
        ]);
    }


    /**
     * 解封记录方法
     * 这里还是拆开写吧。不写在原来方法里里。万一需要扩展搜索条件，反而代码更加乱
     */
    public function _index_eq_content_record($tabs)
    {

        $me           = L('ME');
        $form         = Lab::form();
        $selector     = 'eq_banned_record[object_name=equipment]';
        $pre_selector = [];

        $root = Tag_Model::root('group');
        /* if ($form['group_id'] && $form['group_id'] != $root->id){
        $tag = O('tag', $form['group_id']);
        $pre_selector[] = "$tag user";
        } */
        if (!$me->is_allowed_to('查看全局', 'eq_banned')) {
            if (!$me->is_allowed_to('查看机构', 'eq_banned')) {
                $pre_selector['me_equipment'] = "$me<@(incharge|contact) equipment<object";
            } else {
                $tag                           = $me->group;
                $pre_selector['tag_equipment'] = "$tag equipment<object";
            }
        }
        if ($form['name']) {
            $name                 = Q::quote(trim($form['name']));
            $pre_selector['user'] = "user[name*=$name]";
        }
        if ($form['unsealing_user']) {
            if ('系统' == $form['unsealing_user']) {
                $selector .= '[!unsealing_user]';
            } else {
                $name                           = Q::quote(trim($form['unsealing_user']));
                $pre_selector['unsealing_user'] = "user[name*=$name]<unsealing_user";
            }
        }
        if ($form['eq_name']) {
            $eq_name                   = Q::quote($form['eq_name']);
            $pre_selector['equipment'] = "equipment[name*=$eq_name]<object";
        }
        if (!$pre_selector['user'] && $form['sort'] == 'name') {
            $pre_selector['user'] = 'user';
        }

        if ($form['ctstart']) {
            $ctstart = Date::get_day_start($form['ctstart']);
            $selector .= "[ctime>=$ctstart]";
        }
        
        if ($form['ctend']) {
            $dtend = Date::get_day_end($form['ctend']);
            $selector .= "[ctime<=$dtend]";
        }

        if (count($pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }

        $new_selector = Event::trigger('eq_ban.list.selector', $form, $selector, $pre_selector);

        if ($new_selector) {
            $selector = $new_selector;
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        if ($form['sort'] == 'name') {
            $selector .= ":sort(user.name_abbr {$sort_flag})";
        } elseif ($form['sort'] == 'eq_name') {
            $selector .= ":sort(obj_abbr {$sort_flag})";
        } elseif ($form['sort'] == 'ctime') {
            $selector .= ":sort(ctime {$sort_flag})";
        } elseif ($form['sort'] == 'atime') {
            $selector .= ":sort(atime {$sort_flag})";
        } elseif ($form['sort'] == 'unsealing_ctime') {
            $selector .= ":sort(unsealing_ctime {$sort_flag})";
        } else {
            $selector .= ':sort(ctime D)';
        }

        $bans = Q($selector);

        $panel_buttons = [];
        if ($me->is_allowed_to('添加全局', 'eq_banned')) {
            if (Config::get('eq_ban.show_export')) {
                $form_token            = Session::temp_token('eq_ban_list_', 300);
                $form['form_token']    = $form_token;
                $_SESSION[$form_token] = $selector;
                $panel_buttons += [
                    'export' => [
                        'text' => I18N::T('eq_ban', '导出封禁用户'),
                        'extra' => 'class="button button_save" q-object="export" q-event="click" q-src="' . H(URI::url('!eq_ban/index', ['form_token' => $form_token, 'type' => 'export_ban_unseal'])) . '"',
                        'url'   => "#",
                    ],
                ];
            }
        }

        $columns = [
            'group'=>[
                'weight'=>10,
                'title'=>I18N::T('eq_ban', '用户机构'),
                'invisible' => TRUE,
            ],
            'name'=>[
                'weight'=>20,
                'title'=>I18N::T('eq_ban', '姓名'),
                'align'=>'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                'filter'=> [
                    'form' => V('eq_ban:eq_ban_table/filters/text', ['value'=>$form['name']]),
                    'value' => $form['name'] ? H($form['name']) : NULL
                ],
            ],
            'unsealing_user'=>[
                'weight'=>20,
                'title'=>I18N::T('eq_ban', '解封操作人'),
                'align'=>'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                'filter'=> [
                    'form' => V('eq_ban:eq_ban_table/filters/text', ['name'=>'unsealing_user', 'value'=>$form['unsealing_user']]),
                    'value' => $form['unsealing_user'] ? H($form['unsealing_user']) : NULL
                ],
            ],
            'unsealing_ctime' => [
                'weight' => 20,
                'title'=>I18N::T('eq_ban', '解封时间'),
                'align' => 'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
            ],
            'eq_name'=>[
                'weight'=>25,
                'title'=>I18N::T('eq_ban', '封禁仪器'),
                'align' => 'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                'filter'=> [
                    'form' => V('eq_ban:eq_ban_table/filters/text', ['name'=>'eq_name', 'value'=>$form['eq_name']]),
                    'value' => $form['eq_name'] ? H($form['eq_name']) : NULL
                ],
            ],
            'reason'=>[
                'weight'=>30,
                'title'=>I18N::T('eq_ban', '封禁原因'),
                'align' => 'left',
                'nowrap'=>TRUE,
            ],
            'ctime' => [
                'weight'   => 40,
                'title'    => I18N::T('eq_ban', '封禁时间'),
                'align'    => 'left',
                'sortable' => true,
                'nowrap'   => true,
                'filter' => [
                    'form' => V('eq_ban:eq_ban_table/filters/ctime', [
                        'form'=>$form,
                        'field_title' => '封禁时间',
                    ]),
                    'field' => 'ctstart,ctend',
                    'value' => $form['ctstart'] || $form['ctend']
                ]
            ],
            'atime'=>[
                'weight'=>50,
                'title'=>I18N::T('eq_ban', '到期时间'),
                'align' => 'left',
                'nowrap'=>TRUE,
                'sortable'=>TRUE,
                // 'filter' => [
                // 	'form' => V('eq_ban:eq_ban_table/filters/time', [
                // 		'form'=>$form,
                // 	]),
                // 	'field'=>'dtstart,dtend,dtstart_check,dtend_check',
                // 	'value' => $form['time'] ? H($form['time']) : NULL
                // ]
            ],
            'rest'=>[
                'weight'=>60,
                'align'=>'right',
                'nowrap'=>TRUE,
            ]
        ];

        $tabs->search_box = V('application:search_box',
            ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['name'], 'columns' => $columns]);

        $per_page = Config::get('per_page.eq_banned', 25);
        $pagination = Lab::pagination($bans, (int) $form['st'], $per_page);
        $tabs->content = V('eq_ban:equipment/record', [
            'columns'    => $columns,
            'form'       => $form,
            'bans'       => $bans,
            'tag'        => $tag,
            'root'       => $root,
            'pagination' => $pagination,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
        ]);
    }
}

class Index_AJAX_Controller extends AJAX_Controller
{

    public function index_add_ban_admin_click()
    {
        if (!L('ME')->is_allowed_to('编辑全局', 'eq_banned')) {
            return;
        }

        JS::dialog(V('eq_ban:admin/add', ['form' => $form]), ['title' => I18N::T('eq_ban', '添加封禁用户')]);
    }

    public function index_add_ban_admin_submit()
    {
        $form = Form::filter(Input::form());
        $me   = L('ME');
        if (!$me->is_allowed_to('编辑全局', 'eq_banned')) {
            return;
        }

        if ($form['submit']) {
            $type = $form['type'];

            /* validation */
            if ($type == 'user') {
                $form->validate('user_id', 'number(>0)', I18N::T('eq_ban', '请选择封禁用户!'));
            } elseif ($type == 'lab') {
                $form->validate('lab_id', 'number(>0)', I18N::T('eq_ban', '请选择封禁实验室!'));
            } else {
                return;
            }

            $form->validate('reason', 'not_empty', I18N::T('eq_ban', '请填写封禁原因!'))
                ->validate('atime', 'not_empty', I18N::T('eq_ban', '请填写解禁时间！'));

            if ($form->no_error) {
                if ($type == 'user') {
                    $user  = O('user', $form['user_id']);
                    $users = [$user];
                    if (!$GLOBALS['preload']['people.multi_lab']) {
                        $lab = Q("$user lab")->current();
                    }
                } elseif ($type == 'lab') {
                    $lab   = O('lab', $form['lab_id']);
                    $users = Q("$lab user");
                }

                foreach ($users as $user) {
                    $filter = ['user' => $user, 'object_id' => 0];
                    if ($lab->id) {
                        $filter['lab'] = $lab;
                    } else {
                        $filter['lab_id'] = 0;
                    }
                    $eq_banned = O('eq_banned', $filter);

                    if ($lab->id) {
                        $eq_banned->lab = $lab;
                    }
                    $eq_banned->user   = $user;
                    $eq_banned->reason = $form['reason'];
                    $eq_banned->atime  = $form['atime'];
                    $eq_banned->banned_type = $type;
                    $eq_banned->save();

                    Eq_Ban_Message::add($eq_banned);
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '添加封禁成功!'));
                }

                JS::refresh();
            }

            // 如果form表单中提交验证失败，则将信息返回提示给用户
            else {
                JS::dialog(V('eq_ban:admin/add', ['form' => $form]), ['title' => I18N::T('eq_ban', '添加封禁用户')]);
            }
        }
    }

    public function index_edit_ban_admin_click()
    {
        $form   = Input::form();
        $id     = $form['banned_id'];
        $eq_ban = O('eq_banned', $id);

        if (!L('ME')->is_allowed_to('编辑全局', 'eq_banned') || !$eq_ban->id) {
            return;
        }

        JS::dialog(V('eq_ban:admin/edit', [
            'form' => $form,
            'ban'  => $eq_ban,
        ])
            , ['title' => I18N::T('eq_ban', '编辑封禁用户')]
        );
    }

    public function index_edit_ban_admin_submit()
    {
        $form   = Form::filter(Input::form());
        $eq_ban = O('eq_banned', $form['id']);
        $me     = L('ME');
        if (!$eq_ban->id || !$me->is_allowed_to('编辑全局', $eq_ban)) {
            return;
        }

        if ($form['submit']) {
            $form->validate('reason', 'not_empty', I18N::T('eq_ban', '请填写封禁原因!'))
                ->validate('atime', 'not_empty', I18N::T('eq_ban', '请填写解禁时间！'));

            if ($form->no_error) {
                $eq_ban->reason = $form['reason'];
                $eq_ban->atime  = $form['atime'];
                $eq_ban->save();
                Eq_Ban_Message::add($eq_ban);

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '修改封禁成功!'));

                JS::refresh();
            }
            //如果form表单中提交验证失败，则将信息返回提示给用户
            else {
                JS::dialog(V('eq_ban:admin/edit', ['form' => $form]), ['title' => I18N::T('eq_ban', '修改封禁用户')]);
            }
        }
    }

    public function index_del_ban_admin_click()
    {
        if (!JS::confirm(I18N::T('eq_ban', '你确定要解除封禁吗?'))) {
            return;
        }
        $form   = Input::form();
        $id     = $form['banned_id'];
        $eq_ban = O('eq_banned', $id);

        if (!L('ME')->is_allowed_to('编辑全局', 'eq_banned') || !$eq_ban->id) {
            return;
        }

        $eq_ban->delete();
        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '解除封禁成功!'));
        JS::refresh();
    }

    public function index_add_ban_group_click()
    {
        if (!L('ME')->is_allowed_to('添加机构', 'eq_banned')) {
            return;
        }

        JS::dialog(V('eq_ban:group/add', ['form' => $form]), ['title' => I18N::T('eq_ban', '添加封禁用户')]);
    }

    public function index_add_ban_group_submit()
    {
        $form = Form::filter(Input::form());
        $me   = L('ME');
        if (!$me->is_allowed_to('添加机构', 'eq_banned')) {
            return;
        }

        if ($form['submit']) {
            $type = $form['type'];

            /* validation */
            if ($type == 'user') {
                $form->validate('user_id', 'number(>0)', I18N::T('eq_ban', '请选择封禁用户!'));
            } elseif ($type == 'lab') {
                $form->validate('lab_id', 'number(>0)', I18N::T('eq_ban', '请选择封禁实验室!'));
            } else {
                return;
            }

            if ($me->is_allowed_to('添加全局', 'eq_banned')) {
                $groupIds = join(',', array_keys(json_decode($form['group_id'], true)));
                $groups = Q("tag_group[id=$groupIds]");
                if (!$groups->total_count()) {
                    $form->set_error('group_id', I18N::T('eq_ban', '请选择组织机构!'));
                }
            } else {
                $groups = [$me->group];
            }

            $form->validate('reason', 'not_empty', I18N::T('eq_ban', '请填写封禁原因!'))
                ->validate('atime', 'not_empty', I18N::T('eq_ban', '请填写解禁时间！'));

            if ($form->no_error) {
                if ($type == 'user') {
                    $user  = O('user', $form['user_id']);
                    $users = [$user];
                    if (!$GLOBALS['preload']['people.multi_lab']) {
                        $lab = Q("$user lab")->current();
                    }
                } elseif ($type == 'lab') {
                    $lab   = O('lab', $form['lab_id']);
                    $users = Q("$lab user");
                }

                foreach ($groups as $group) {
                    foreach ($users as $user) {
                        $filter = ['user' => $user, 'object' => $group];
                        if ($lab->id) {
                            $filter['lab'] = $lab;
                        } else {
                            $filter['lab_id'] = 0;
                        }
                        
                        $eq_banned = O('eq_banned', $filter);

                        if ($lab->id) {
                            $eq_banned->lab = $lab;
                        }
                        $eq_banned->user   = $user;
                        $eq_banned->object = $group;
                        $eq_banned->reason = $form['reason'];
                        $eq_banned->atime  = $form['atime'];
                        $eq_banned->banned_type = $type;
                        $eq_banned->save();
                        Eq_Ban_Message::add($eq_banned);
                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '添加封禁成功!'));
                    }
                }

                JS::refresh();
            }
            //如果form表单中提交验证失败，则将信息返回提示给用户
            else {
                JS::dialog(V('eq_ban:group/add', ['form' => $form, 'tag' => $group]), ['title' => I18N::T('eq_ban', '添加封禁用户')]);
            }
        }
    }

    public function index_edit_ban_group_click()
    {
        $form   = Input::form();
        $id     = $form['banned_id'];
        $eq_ban = O('eq_banned', $id);

        if (!$eq_ban->id || !L('ME')->is_allowed_to('编辑机构', 'eq_banned')) {
            return;
        }

        JS::dialog(V('eq_ban:group/edit', [
            'form' => $form,
            'ban'  => $eq_ban,
        ])
            , ['title' => I18N::T('eq_ban', '编辑封禁用户')]
        );
    }

    public function index_edit_ban_group_submit()
    {
        $form   = Form::filter(Input::form());
        $eq_ban = O('eq_banned', $form['id']);

        $me = L('ME');
        if (!$eq_ban->id || !$me->is_allowed_to('编辑机构', $eq_ban)) {
            return;
        }

        if ($form['submit']) {
            $form->validate('reason', 'not_empty', I18N::T('eq_ban', '请填写封禁原因!'))
                ->validate('atime', 'not_empty', I18N::T('eq_ban', '请填写解禁时间！'));

            if ($form->no_error) {
                if ($form['group_id']) {
                    $group = O('tag_group', $form['group_id']);
                    $eq_ban->object = $group;
                }

                $eq_ban->reason = $form['reason'];
                $eq_ban->atime  = $form['atime'];
                $eq_ban->save();
                Eq_Ban_Message::add($eq_ban);

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '修改封禁成功!'));

                JS::refresh();
            }
            //如果form表单中提交验证失败，则将信息返回提示给用户
            else {
                JS::dialog(V('eq_ban:group/edit', ['form' => $form]), ['title' => I18N::T('eq_ban', '修改封禁用户')]);
            }
        }
    }

    public function index_del_ban_group_click()
    {
        if (!JS::confirm(I18N::T('eq_ban', '你确定要解除封禁吗?'))) {
            return;
        }
        $form   = Input::form();
        $id     = $form['banned_id'];
        $eq_ban = O('eq_banned', $id);

        if (!$eq_ban->id || !L('ME')->is_allowed_to('编辑机构', $eq_ban)) {
            return;
        }

        $eq_ban->delete();
        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '解除封禁成功!'));
        JS::refresh();
    }

    public function index_add_ban_eq_click()
    {
        if (!L('ME')->is_allowed_to('添加仪器', 'eq_banned')) {
            return;
        }

        JS::dialog(V('eq_ban:equipment/add', ['form' => $form]), ['title' => I18N::T('eq_ban', '添加封禁用户')]);
    }

    public function index_add_ban_eq_submit()
    {
        $form = Form::filter(Input::form());
        $me   = L('ME');
        if (!$me->is_allowed_to('添加仪器', 'eq_banned')) {
            return;
        }

        if ($form['submit']) {
            $type    = $form['type'];
            $eq_type = $form['eq_type'];

            /* validation */
            if ($type == 'user') {
                $form->validate('user_id', 'number(>0)', I18N::T('eq_ban', '请选择封禁用户!'));
            } elseif ($type == 'lab') {
                $form->validate('lab_id', 'number(>0)', I18N::T('eq_ban', '请选择封禁实验室!'));
            } else {
                return;
            }

            if ($eq_type == 'select') {
                $eqs = @json_decode($form['eqs'], true);
                if (!count($eqs)) {
                    $form->set_error('eqs', I18N::T('eq_ban', '请选择封禁仪器!'));
                }
            }

            $form->validate('reason', 'not_empty', I18N::T('eq_ban', '请填写封禁原因!'))
                ->validate('atime', 'not_empty', I18N::T('eq_ban', '请填写解禁时间！'));

            if ($form->no_error) {
                if ($type == 'user') {
                    $user  = O('user', $form['user_id']);
                    $users = [$user];
                    if (!$GLOBALS['preload']['people.multi_lab']) {
                        $lab = Q("$user lab")->current();
                    }
                } elseif ($type == 'lab') {
                    $lab   = O('lab', $form['lab_id']);
                    $users = Q("$lab user");
                }
                if ($eq_type == 'select') {
                    $ids = join(',', array_keys($eqs));
                    $eqs = Q("equipment[id=$ids]");
                } else {
                    $eqs = Q("$me<@(incharge|contact) equipment");
                }

                if (count($eqs) <= 0) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '没有负责仪器,添加封禁失败!'));
                    JS::refresh();
                    return false;
                }

                foreach ($users as $user) {
                    foreach ($eqs as $eq) {
                        if (!$eq->id) {
                            continue;
                        }

                        $filter = ['object' => $eq, 'user' => $user];
                        if ($lab->id) {
                            $filter['lab'] = $lab;
                        } else {
                            $filter['lab_id'] = 0;
                        }
                        $eq_banned = O('eq_banned', $filter);
                        if ($lab->id) {
                            $eq_banned->lab = $lab;
                        }
                        $eq_banned->user   = $user;
                        $eq_banned->object = $eq;
                        $eq_banned->reason = $form['reason'];
                        $eq_banned->atime  = $form['atime'];
                        $eq_banned->banned_type = $type;
                        $eq_banned->save();

                        Eq_Ban_Message::add($eq_banned);
                    }
                }

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '添加封禁成功!'));

                JS::refresh();
            }
            // 如果form表单中提交验证失败，则将信息返回提示给用户
            else {
                JS::dialog(V('eq_ban:equipment/add', ['form' => $form]), ['title' => I18N::T('eq_ban', '添加封禁用户')]);
            }
        }
    }

    public function index_edit_ban_eq_click()
    {
        $form = Input::form();
        $id = $form['banned_id'];
        $eq_ban = O('eq_banned', $id);

        if (!$eq_ban->id || !L('ME')->is_allowed_to('编辑仪器', 'eq_banned')) {
            return;
        }

        JS::dialog(V('eq_ban:equipment/edit', [
            'form' => $form,
            'ban' => $eq_ban,
        ])
            , ['title' => I18N::T('eq_ban', '编辑封禁用户')]
        );
    }

    public function index_edit_ban_eq_submit()
    {
        $form   = Form::filter(Input::form());
        $eq_ban = O('eq_banned', $form['id']);

        $me = L('ME');
        if (!$eq_ban->id || !$me->is_allowed_to('编辑仪器', $eq_ban)) {
            return;
        }

        if ($form['submit']) {
            $form->validate('reason', 'not_empty', I18N::T('eq_ban', '请填写封禁原因!'))
                ->validate('atime', 'not_empty', I18N::T('eq_ban', '请填写解禁时间！'));

            if ($form->no_error) {
                if ($form['eq_id']) {
                    $equipment      = O('equipment', $form['eq_id']);
                    $eq_ban->object = $equipment;
                }

                $eq_ban->reason = $form['reason'];
                $eq_ban->atime  = $form['atime'];
                $eq_ban->save();
                Eq_Ban_Message::add($eq_ban);

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '修改封禁成功!'));

                JS::refresh();
            }
            //如果form表单中提交验证失败，则将信息返回提示给用户
            else {
                JS::dialog(V('eq_ban:equipment/edit', ['form' => $form]), ['title' => I18N::T('eq_ban', '修改封禁用户')]);
            }
        }
    }

    public function index_del_ban_eq_click()
    {
        if (!JS::confirm(I18N::T('eq_ban', '你确定要解除封禁吗?'))) {
            return;
        }
        $form   = Input::form();
        $id     = $form['banned_id'];
        $eq_ban = O('eq_banned', $id);

        if (!$eq_ban->id || !L('ME')->is_allowed_to('编辑仪器', $eq_ban)) {
            return;
        }

        $eq_ban->delete();
        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '解除封禁成功!'));
        JS::refresh();
    }

    public function index_del_violate_eq_click()
    {
        if (!JS::confirm(I18N::T('eq_ban', '你确定要删除违规吗?'))) {
            return;
        }
        $form = Input::form();
        $id = $form['violate_id'];
        $eq_violate = O('user_violation_record', $id);

        $user = $eq_violate->user;

        if (!$eq_violate->id || !L('ME')->is_allowed_to('编辑仪器违规记录', 'eq_banned')) {
            return;
        }

        $eq_violate->delete();

        $user_v = O('user_violation', ['user' => $user]);
        $user_v->eq_violate_count = $user_v->eq_violate_count - 1;
        $user_v->save();

        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '解除封禁成功!'));
        JS::refresh();
    }

    public function index_add_eq_violate_click()
    {
        if (!L('ME')->is_allowed_to('编辑仪器违规记录', 'eq_banned')) {
            return;
        }

        JS::dialog(V('eq_ban:equipment/add_violate', [
            'form' => $form,
        ])
            , ['title' => I18N::T('eq_ban', '添加用户违规记录')]
        );
    }
    public function index_add_eq_violate_submit()
    {

        $form = Form::filter(Input::form());
        $me = L('ME');
        if (!$me->is_allowed_to('编辑仪器违规记录', 'eq_banned')) {
            return;
        }

        if ($form['submit']) {
            $eq_type = $form['eq_type'];

            $form->validate('user_id', 'number(>0)', I18N::T('eq_ban', '请选择封禁用户!'));

            if ($eq_type == 'select') {
                $eqs = @json_decode($form['eqs'], true);
                if (!count($eqs)) {
                    $form->set_error('eqs', I18N::T('eq_ban', '请选择封禁仪器!'));
                }
            }

            $form->validate('reason', 'not_empty', I18N::T('eq_ban', '请填写违规原因!'))
                ->validate('ctime', 'not_empty', I18N::T('eq_ban', '请填写违规时间！'));

            if ($form->no_error) {
                $user = O('user', $form['user_id']);
                $users = [$user];

                if ($eq_type == 'select') {
                    $ids = join(',', array_keys($eqs));
                    $eqs = Q("equipment[id=$ids]");
                } else {
                    $eqs = Q("$me<@(incharge|contact) equipment");
                }

                if (count($eqs) <= 0) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '没有负责仪器,添加违规失败!'));
                    JS::refresh();
                    return false;
                }

                foreach ($users as $user) {
                    foreach ($eqs as $eq) {
                        if (!$eq->id) continue;
                        $user_violation = O('user_violation_record');
                        $user_violation->user = $user;
                        $user_violation->equipment = $eq;
                        $user_violation->reason = $form['reason'];
                        $user_violation->ctime = $form['ctime'];

                        if($user_violation->save()){
                            $user_v = O('user_violation', ['user' => $user]);
                            $user_v->user = $user;
                            $user_v->eq_violate_count = $user_v->eq_violate_count?($user_v->eq_violate_count+1):1;
                            $user_v->save();

                            $max_eq_allowed_violate_times = Lab::get('eq.max_allowed_violate_times', Config::get('eq.max_allowed_violate_times'), $eq->group->name, TRUE);

                            if($max_eq_allowed_violate_times > 0 && $user_v->eq_violate_count >= $max_eq_allowed_violate_times){
                    
                                if (!$GLOBALS['preload']['people.multi_lab']) {
                                    $lab = Q("$user lab")->current();
                                }
                                $filter = ['user' => $user, 'object' => $eq->group];
                                if ($lab->id) {
                                    $filter['lab'] = $lab;
                                } else {
                                    $filter['lab_id'] = 0;
                                }
                                
                                $eq_banned = O('eq_banned', $filter);

                                if(!$eq_banned->id){
                                    if ($lab->id) {
                                        $eq_banned->lab = $lab;
                                    }
                                    $eq_banned->user = $user;
                                    $eq_banned->object = $eq->group;
                                    $eq_banned->reason = I18N::T('eq_ban', '使用设备违规行为超过系统预定义上限!');;
                                    $eq_banned->atime = 0;
                                    $eq_banned->save();
                                    Eq_Ban_Message::add($eq_banned);
                                } 
                            }

                            $max_eq_allowed_total_count_times = Lab::get('eq.max_allowed_total_count_times', Config::get('eq.max_allowed_total_count_times'), $eq->group->name, TRUE);

                            if($max_eq_allowed_total_count_times > 0 && $user_v->total_count >= $max_eq_allowed_total_count_times){
                    
                                if (!$GLOBALS['preload']['people.multi_lab']) {
                                    $lab = Q("$user lab")->current();
                                }
                    
                                $filter = ['user' => $user, 'object' => $eq->group];
                                if ($lab->id) {
                                    $filter['lab'] = $lab;
                                } else {
                                    $filter['lab_id'] = 0;
                                }
                                
                                $eq_banned = O('eq_banned', $filter);

                                if(!$eq_banned->id){
                                    if ($lab->id) {
                                        $eq_banned->lab = $lab;
                                    }
                                    $eq_banned->user = $user;
                                    $eq_banned->object = $eq->group;
                                    $eq_banned->reason = I18N::T('eq_ban', '使用设备违规总次数超过系统预定义上限!');
                                    $eq_banned->atime = 0;
                                    $eq_banned->save();
                                }  
                            }
                        }
                        
                    }
                }

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '添加违规成功!'));

                JS::refresh();
            }
            //如果form表单中提交验证失败，则将信息返回提示给用户
            else {
                JS::dialog(V('eq_ban:equipment/add_violate', ['form' => $form]), ['title' => I18N::T('eq_ban', '添加用户违规记录')]);
            }
        }
    }

    public function index_edit_violate_eq_click()
    {
        $form = Input::form();
        $id = $form['violate_id'];
        $eq_violate = O('user_violation_record', $id);

        if (!$eq_violate->id || !L('ME')->is_allowed_to('编辑仪器违规记录', 'eq_banned')) {
            return;
        }

        JS::dialog(V('eq_ban:equipment/edit_violate', [
            'form' => $form,
            'violate' => $eq_violate,
        ])
            , ['title' => I18N::T('eq_ban', '编辑违规记录')]
        );
    }

    public function index_edit_violate_eq_submit()
    {

        $form = Form::filter(Input::form());
        $eq_violate = O('user_violation_record', $form['id']);

        $me = L('ME');
        if (!$eq_violate->id || !$me->is_allowed_to('编辑仪器违规记录', 'eq_banned')) {
            return;
        }

        if ($form['submit']) {
            $form->validate('reason', 'not_empty', I18N::T('eq_ban', '请填写违规原因!'))
                ->validate('ctime', 'not_empty', I18N::T('eq_ban', '请填写违规时间！'));

            if ($form->no_error) {

                $eq_violate->reason = $form['reason'];
                $eq_violate->ctime = $form['ctime'];
                $eq_violate->save();

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_ban', '修改违规成功!'));

                JS::refresh();
            }
            //如果form表单中提交验证失败，则将信息返回提示给用户
            else {
                JS::dialog(V('eq_ban:equipment/edit', ['form' => $form]), ['title' => I18N::T('eq_ban', '修改违规用户')]);
            }
        }
    }
}
