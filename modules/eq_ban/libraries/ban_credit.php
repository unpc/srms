<?php
class Ban_Credit {

    static function _index_admin_content($e, $tabs)
    {

        $me = L('ME');
        $form = Lab::form(function (&$old_form, &$form) {
            if (isset($form['date_filter'])) {
                if (!$form['dtstart_check']) {
                    unset($old_form['dtstart_check']);
                }
                if (!$form['dtend_check']) {
                    unset($old_form['dtend_check']);
                } else {
                    $form['dtstart'] = Date::get_day_start($form['dtstart']);
                    $form['dtend'] = Date::get_day_end($form['dtend']);
                }
                if (!$form['ctstart_check']) {
                    unset($old_form['ctstart_check']);
                }
                if (!$form['ctend_check']) {
                    unset($old_form['ctend_check']);
                } else {
                    $form['ctstart'] = Date::get_day_start($form['ctstart']);
                    $form['ctend'] = Date::get_day_end($form['ctend']);
                }
                unset($form['date_filter']);
            }
            if (!isset($form['unsealing'])) {
                //unset($old_form['unsealing']);
            }
        });

        if (isset($form['unsealing']) && $form['unsealing']) {
            self::_index_admin_content_record($tabs);
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
            $name = Q::quote($form['name']);
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
            $dtstart = Date::get_day_start($form['dtstart']);
            $selector .= "[atime>={$dtstart}]";
        }
        if ($form['dtend']) {
            $dtend = Date::get_day_end($form['dtend']);
            $selector .= "[atime<=$dtend]";
        }
        if ($form['dtstart'] || $form['dtend']) {
            $form['time'] = true;
        }

        if($form['ctstart']){
            $ctstart = Date::get_day_start($form['ctstart']);
            $selector .= "[ctime>=$ctstart]";
        }
        if($form['ctend']){
            $ctend = Date::get_day_end($form['ctend']);
            $selector .= "[ctime<=$ctend]";
        }
        if ($form['ctstart'] || $form['ctend']) {
            $form['ctime'] = true;
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
        $tabs->search_box = V('application:search_box',
            ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['name', 'atime'], 'columns' => $columns]);

        $per_page = Config::get('per_page.eq_banned', 25);
        $pagination = Lab::pagination($bans, (int) $form['st'], $per_page);

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
    static function _index_admin_content_record($tabs)
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
            $name = Q::quote($form['name']);
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
        if ($form['dtstart'] || $form['dtend']) {
            $form['time'] = true;
        }

        if($form['ctstart']){
            $ctstart = Date::get_day_start($form['ctstart']);
            $selector .= "[ctime>=$ctstart]";
        }
        if($form['ctend']){
            $ctend = Date::get_day_end($form['ctend']);
            $selector .= "[ctime<=$ctend]";
        }
        if ($form['ctstart'] || $form['ctend']) {
            $form['ctime'] = true;
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
                        'text'  => null,
                        'tip'  => I18N::T('eq_ban', '导出封禁用户'),
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
                        'value' => $form['ctime'] ? H($form['ctime']) : NULL
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
                    'value' => $form['time'] ? H($form['time']) : NULL
                ]
            ],
            'rest'=>[
                'weight'=>60,
                'align'=>'right',
                'nowrap'=>TRUE,
            ]
        ];


        $tabs->search_box = V('application:search_box',
            ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['name'], 'columns' => $columns]);

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
        return true;
    }

    static function get_ban_field($form, $tag, $root)
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
                        'value' => $form['ctime'] ? H($form['ctime']) : NULL
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
                'align'  => 'left',
                'nowrap' => true,
            ],
        ];

        return $columns;
    }

    public function _index_group_content($e, $tabs)
    {

        $me   = L('ME');
        $form = Lab::form();

        if (isset($form['unsealing']) && $form['unsealing']) {
            self::_index_group_content_record($tabs);
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
            $tag = $me->group;
            $pre_selector['tag'] = "$tag<object";
        }

        if ($form['name']) {
            $name = Q::quote($form['name']);
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
            $dtend = $form['dtend'] + 86399;
            $selector .= "[atime<={$dtend}]";
        }
        if ($form['dtstart'] || $form['dtend']) {
            $form['time'] = true;
        }

        if($form['ctstart']){
            $ctstart = Date::get_day_start($form['ctstart']);
            $selector .= "[ctime>=$ctstart]";
        }
        if($form['ctend']){
            $ctend = Date::get_day_end($form['ctend']);
            $selector .= "[ctime<=$ctend]";
        }
        if ($form['ctstart'] || $form['ctend']) {
            $form['ctime'] = true;
        }

        if (!$pre_selector['user'] && !$pre_selector['tag_user'] && $form['sort'] == 'name') {
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

        if ($me->is_allowed_to('添加机构', 'eq_banned')) {
            $panel_buttons = [
                'add' => [
                    'text' => I18N::T('eq_ban', '添加封禁用户'),
                    'tip' => I18N::T('eq_ban', '添加封禁用户'),
                    'extra' => 'class="button button_add" q-object="add_ban_group" q-event="click" q-src="' . H(URI::url('!eq_ban/index')) . '"',
                    'url' => "#",
                ],
            ];
            if (Config::get('eq_ban.show_export')) {
                $form_token = Session::temp_token('eq_ban_list_', 300);
                $form['form_token'] = $form_token;
                $_SESSION[$form_token] = $selector;
                $panel_buttons += [
                    'export' => [
                        'text' => null,
                        'tip' => I18N::T('eq_ban', '导出封禁用户'),
                        'extra' => 'class="button button_save" q-object="export" q-event="click" q-src="' . H(URI::url('!eq_ban/index', ['form_token' => $form_token, 'type' => 'export_ban'])) . '"',
                        'url' => "#",
                    ],
                ];
            }
        }

        $per_page = Config::get('per_page.eq_banned', 25);
        $pagination = Lab::pagination($bans, (int) $form['st'], $per_page);

        $columns    = self::get_ban_group_field($form, $tag, $root);
        $tabs->search_box = V('application:search_box',
            ['panel_buttons' => $panel_buttons, 'top_input_arr' => ['name', 'atime'], 'columns' => $columns]);

        $tabs->content = V('eq_ban:group/list', [
            'form'       => $form,
            'bans'       => $bans,
            'tag'        => $tag,
            'root'       => $root,
            'pagination' => $pagination,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
            //'search_box' => $search_box,
            'columns'    => $columns,
        ]);
    }

    /**
     * 解封记录方法
     * 这里还是拆开写吧。不写在原来方法里里。万一需要扩展搜索条件，反而代码更加乱
     */
    static function _index_group_content_record($tabs)
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
            $tag = $me->group;
            $pre_selector['tag'] = "$tag<object";
        }

        if ($form['name']) {
            $name = Q::quote($form['name']);
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
        if ($form['dtstart'] || $form['dtend']) {
            $form['time'] = true;
        }

        if($form['ctstart']){
            $ctstart = Date::get_day_start($form['ctstart']);
            $selector .= "[ctime>=$ctstart]";
        }
        if($form['ctend']){
            $ctend = Date::get_day_end($form['ctend']);
            $selector .= "[ctime<=$ctend]";
        }
        if ($form['ctstart'] || $form['ctend']) {
            $form['ctime'] = true;
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
                        'text'  => null,
                        'tip'  => I18N::T('eq_ban', '导出封禁用户'),
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
                        'value' => $form['ctime'] ? H($form['ctime']) : NULL
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
                    'value' => $form['time'] ? H($form['time']) : NULL
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
                        'value' => $form['ctime'] ? H($form['ctime']) : NULL
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
                'align'  => 'left',
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

        return $columns;
    }

    public static function _index_eq_content($e, $tabs)
    {

        $me   = L('ME');
        $form = Lab::form();

        if (isset($form['unsealing']) && $form['unsealing']) {
            self::_index_eq_content_record($tabs);
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
                $tag = $me->group;
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

        if($form['ctstart']){
            $ctstart = Date::get_day_start($form['ctstart']);
            $selector .= "[ctime>=$ctstart]";
        }
        if($form['ctend']){
            $ctend = Date::get_day_end($form['ctend']);
            $selector .= "[ctime<=$ctend]";
        }
        if ($form['ctstart'] || $form['ctend']) {
            $form['ctime'] = true;
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

        $per_page = Config::get('per_page.eq_banned', 25);
        $pagination = Lab::pagination($bans, (int) $form['st'], $per_page);
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

    public static function get_ban_eq_filed($form, $tag, $root)
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
                    'value' => $form['ctime'] ? H($form['ctime']) : NULL
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
                'align'  => 'left',
                'nowrap' => true,
            ],
        ];

        return $columns;
    }

    static function _index_eq_content_record($tabs)
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
                $tag = $me->group;
                $pre_selector['tag_equipment'] = "$tag equipment<object";
            }
        }
        if ($form['name']) {
            $name = Q::quote($form['name']);
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
        if ($form['eq_name']) {
            $eq_name = Q::quote($form['eq_name']);
            $pre_selector['equipment'] = "equipment[name*=$eq_name]<object";
        }

        if($form['ctstart']){
            $ctstart = Date::get_day_start($form['ctstart']);
            $selector .= "[ctime>=$ctstart]";
        }
        if($form['ctend']){
            $ctend = Date::get_day_end($form['ctend']);
            $selector .= "[ctime<=$ctend]";
        }
        if ($form['ctstart'] || $form['ctend']) {
            $form['ctime'] = true;
        }

        if (!$pre_selector['user'] && $form['sort'] == 'name') {
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
                        'text'  => null,
                        'tip'  => I18N::T('eq_ban', '导出封禁用户'),
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
                    'value' => $form['ctime'] ? H($form['ctime']) : NULL
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