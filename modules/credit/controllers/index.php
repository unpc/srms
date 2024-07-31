<?php

class Index_Controller extends Base_Controller
{
    public function index($tab = 'all')
    {
        $me = L('ME');
        if (!$me->is_allowed_to('查看列表', 'credit')) {
            URI::redirect('error/401');
        }

        $form = Lab::form(function (&$old_form, &$form) {
            if ($form['role'][0] == -1) unset($form['role'][0]);
        });

        $selector     = 'credit';
        $pre_selector = [];

        if ($me->access('管理所有成员信用分')) {
        } elseif ($me->access('管理下属机构成员的信用分')) {
            /* if ($me->group_id) {
        $pre_selector['group'] = "{$me->group} user";
        } */
        } elseif (Q("{$me}<incharge equipment")->total_count()) {
            // $equipments = Q("{$me}<incharge equipment");
            // $ids = [];
            // foreach ($equipments as $equipment) {
            //     $ids[] = $equipment->id;
            // }
            // $pre_selector['pre_equipment'] = "equipment[id=" . implode(',', $ids) . "]";
            // $pre_selector['pre_user'] = "{$user}";
        }

        $root = Tag_Model::root('group');
        if ($form['group_id'] && $form['group_id'] != $root->id) {
            $tag = O('tag_group', $form['group_id']);
            $pre_selector['tag_user'] = "{$tag} user";
        }

        if ($form['name']) {
            $name                 = Q::quote($form['name']);
            $pre_selector['user'] = "user[name*=$name]";
        }

        $role_selector = [];
        if ($form['role'] && L('ME')->is_allowed_to('查看角色', 'user')) {
            //开启事务,这段事务的目的是将pi和机主的关系插入到role_user中
            $db = Database::factory();
            $db->begin_transaction();
           
            foreach($form['role'] as $id) {
                $role = O('role', $id);
                if ($role->id && $role->weight == ROLE_LAB_PI) {
                    $db->query("delete from _r_user_role where id2 = $role->id");
                    $db->query("insert  into _r_user_role(id1,id2) select distinct(id1),$role->id as id2 from _r_user_lab where type = 'pi'");
                }
                else if ($role->id && $role->weight == ROLE_EQUIPMENT_CHARGE) {

                    $db->query("delete from _r_user_role where id2 = $role->id");
                    $db->query("insert  into _r_user_role(id1,id2) select distinct(id1),$role->id as id2 from _r_user_equipment where type = 'incharge'");
                }

                if($role->id) {
                    $role_ids[] = $role->id;
                }
            }
            $db->commit();
            $role_ids = implode(',',$role_ids);
            $pre_selector['role'] = "role[id=$role_ids] user";
        }

        if ($form['lab']) {
            $labId               = Q::quote($form['lab']);
            $pre_selector['lab'] = "lab#{$labId} user";
        }

        if ($form['level']) {
            $level                        = (int) $form['level'];
            $pre_selector['credit_level'] = "credit_level[level={$level}]";
        }

        if (!count($pre_selector) && $form['sort'] == 'name') {
            $pre_selector['user'] = 'user';
        }

        $score_start = (int) $form['score_start'];
        $score_end   = (int) $form['score_end'];
        if ($score_start && $score_end) {
            $selector .= "[total={$score_start}~{$score_end}]";
            $form['credit_score'] = "{$score_start}~{$score_end}";
        } elseif ($score_start) {
            $selector .= "[total>={$score_start}]";
            $form['credit_score'] = "大于等于{$score_start}";
        } elseif ($score_end) {
            $selector .= "[total<={$score_end}]";
            $form['credit_score'] = "小于等于{$score_end}";
        }

        $this->layout->body->primary_tabs = Widget::factory('tabs');
        $this->layout->body->primary_tabs
            ->add_tab('all', [
                'url'   => URI::url("!credit/index.all"),
                'title' => I18N::T('credit', '所有成员 [' . Q("user credit")->total_count() . ']'),
            ])
            ->add_tab('normal', [
                'url'   => URI::url("!credit/index.normal"),
                'title' => I18N::T('credit', '正常成员 [' . Q("user[atime] credit")->total_count() . ']'),
            ])
            ->add_tab('banned', [
                'url'   => URI::url("!credit/index.banned"),
                'title' => I18N::T('credit', '封禁成员 [' . Q("user[!atime] credit")->total_count() . ']'),
            ]);

        switch ($tab) {
            case 'normal':
                $this->layout->body->primary_tabs->select($tab);
                $pre_selector['user'] = $pre_selector['user'] ? $pre_selector['user'] . "[atime]" : "user[atime]";
                break;
            case 'banned':
                $this->layout->body->primary_tabs->select($tab);
                $pre_selector['user'] = $pre_selector['user'] ? $pre_selector['user'] . "[!atime]" : "user[!atime]";
                break;
            default:
                $this->layout->body->primary_tabs->select($tab);
                break;
        }

        if (count((array) $pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }
        

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        if ($form['sort'] == 'level') {
            $selector .= ":sort(credit_level.level {$sort_flag})";
        } elseif ($form['sort'] == 'credit_score') {
            $selector .= ":sort(total {$sort_flag})";
        } else {
            $selector .= ':sort(total D)';
        }
        
        $credits               = Q($selector);
        
        $form_token            = Session::temp_token('credit_list_', 300);
        $form['selector']      = $selector;
        $_SESSION[$form_token] = $form;

        $panel_buttons = [];
        if ($me->is_allowed_to('添加计分明细', 'credit_record')) {
            $panel_buttons['add'] = [
            'text' => I18N::T('credit', '添加明细'),
            'tip'   => I18N::T('credit', '添加明细'),
            'extra' => 'class="button button_add" q-object="credit_record_add" q-event="click" q-src="' . H(URI::url('!credit/index')) . '"',
            'url'   => "#",
            ];
        }

        if ($me->is_allowed_to('打印', 'credit')) {
            $panel_buttons['print'] = [
                'text' => I18N::T('credit', '打印'),
                'tip'   => I18N::T('credit', '打印'),
                'extra' => 'class="button button_print" q-object="output" q-event="click" q-static="' . H(['form_token' => $form_token, 'type' => 'print']) . '" q-src="' . H(URI::url('!credit/index')) . '"',
                'url'   => "#",
            ];
        }
        if ($me->is_allowed_to('导出', 'credit')) {
            $panel_buttons['export'] = [
                'text' => I18N::T('credit', '导出'),
                'tip'   => I18N::T('credit', '导出Excel'),
                'extra' => 'class="button button_save top" q-object="output" q-event="click" q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) . '" q-src="' . H(URI::url('!credit/index')) . '"',
                'url'   => "#",
            ];
        }

        $per_page   = Config::get('per_page.credit', 30);
        $pagination = Lab::pagination($credits, (int) $form['st'], $per_page);

        $columns = self::_get_credit_index_field($form, $tag, $root);
        $params  = [
            'panel_buttons'   => $panel_buttons,
            'top_input_arr'   => ['name'],
            'advanced_search' => true,
            'columns'         => (array) $columns,
            'step'         => 3,
        ];

        $this->layout->body->primary_tabs->search_box = V('application:search_box', $params);

        $this->layout->body->primary_tabs->content = V('credit:list', [
            'form'       => $form,
            'form_token' => $form_token,
            'credits'    => $credits,
            'tag'        => $tag,
            'root'       => $root,
            'pagination' => $pagination,
            'columns'    => $columns,
            // 'panel_buttons' => $panel_buttons,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
        ]);

        $this->layout->title = I18N::T('credit', '成员信用');

//        $primary_tabs          = $this->layout->body->primary_tabs->select($tab);
//        $primary_tabs->content = V('view', [
//            'pagination'     => $pagination,
//            'secondary_tabs' => $secondary_tabs,
//            'search_box'     => $search_box,
//        ]);
    }

    public static function _get_credit_index_field($form, $tag, $root)
    {
        $lab = is_object($form['lab']) ? $form['lab'] : O('lab', $form['lab']);

        $columns = [
            // '@'            => null,
            'name'         => [
                'weight' => 10,
                'title'  => I18N::T('credit', '姓名'),
                'align'  => 'left',
                'nowrap' => true,
                'filter' => [
                    'form'  => V('credit:credit_table/filters/text', ['value' => $form['name']]),
                    'value' => $form['name'] ? H($form['name']) : null,
                ],
            ],
            'role'         => [
                'title'        => I18N::T('roles', '用户角色'),
                'invisible'    => true,
                'suppressible' => true,
                'filter'       => [
                    'form'  => Widget::factory('roles:role_selector_new', [
                        'active_role'=> $form['role'],
                    ]),
                    'value' => empty($form['role']) ? null : $form['role'],
                ],
                'weight'       => 20,
            ],
            'lab'          => [
                'weight' => 20,
                'title'  => I18N::T('labs', '所属课题组'),
                'filter' => [
                    'form'  => Widget::factory('labs:lab_selector', [
                        'name'         => 'lab',
                        'selected_lab' => $lab,
                        'all_labs'     => true,
                        'no_lab'       => true,
                    ]),
                    'value' => $lab->id ? H($lab->name) : null,
                ],
            ],
            'group'        => [
                'weight' => 10,
                'title'  => I18N::T('credit', '所属组织机构'),
                'filter' => [
                    'form'  => V('credit:credit_table/filters/user_group', [
                        'name' => 'group_id',
                        'tag'  => $tag,
                        'root' => $root,
                    ]),
                    'value' => $tag->id ? H($tag->name) : null,
                    'field' => 'group_id',
                ],
            ],
            'level'        => [
                'weight'   => 40,
                'title'    => I18N::T('credit', '信用等级'),
                'align'    => 'left',
                'filter'   => [
                    'form'  => V('credit:credit_table/filters/level', [
                        'level' => $form['level'],
                    ]),
                    'value' => $form['level'] ? H(str_repeat('A', $form['level'])) : null,
                ],
                'sortable' => true,
                'nowrap'   => true,
            ],
            'credit_score' => [
                'weight'   => 50,
                'title'    => I18N::T('credit', '信用分'),
                'align'    => 'left',
                'nowrap'   => true,
                'sortable' => true,
                'filter'   => [
                    'form'  => V('credit:credit_table/filters/credit_score', [
                        'score_start' => $form['score_start'],
                        'score_end'   => $form['score_end'],
                    ]),
                    'field' => 'score_start,score_end',
                    'value' => $form['credit_score'] ? H($form['credit_score']) : null,
                ],
            ],
            'rest'         => [
                'weight' => 60,
                // 'title'  => I18N::T('credit', '操作'),
                'align'  => 'right',
                'nowrap' => true,
            ],
        ];

        return $columns;
    }

    public function credit_record()
    {
        $this->layout->body->primary_tabs->set_tab('credit', null);
        $this->layout->body->primary_tabs->set_tab('ban', null);

        Event::bind('credit_record.index.content', [$this, '_index_credit_record_content'], 0, 'credit_record');

        $this->layout->body->primary_tabs
            ->tab_event('credit_record.index.tab')
            ->content_event('credit_record.index.content')
            ->select('credit_record');
    }

    public function ban($tab = 'admin')
    {
        Event::bind('ban.index.content', [$this, '_index_ban'], 0, 'ban');

        $this->layout->body->primary_tabs
            ->tab_event('ban.index.tab')
            ->content_event('ban.index.content')
            ->select('ban');
    }

    // primary tab 信用列表
    public static function _index_credit_content($e, $tabs)
    {
        $me = L('ME');
        if (!$me->is_allowed_to('查看列表', 'credit')) {
            URI::redirect('error/401');
        }
        $form = Lab::form(function (&$old_form, &$form) {
        });

        $selector     = 'credit';
        $pre_selector = [];

        if ($me->access('管理所有成员信用分')) {
        } elseif ($me->access('管理下属机构成员的信用分')) {
            /* if ($me->group_id) {
        $pre_selector['group'] = "{$me->group} user";
        } */
        } elseif (Q("{$me}<incharge equipment")->total_count()) {
            // $equipments = Q("{$me}<incharge equipment");
            // $ids = [];
            // foreach ($equipments as $equipment) {
            //     $ids[] = $equipment->id;
            // }
            // $pre_selector['pre_equipment'] = "equipment[id=" . implode(',', $ids) . "]";
            // $pre_selector['pre_user'] = "{$user}";
        }

        $root = Tag_Model::root('group');
        if ($form['group_id'] && $form['group_id'] != $root->id) {
            $tag                      = O('tag', $form['group_id']);
            $pre_selector['tag_user'] = "{$tag} user";
        }

        if ($form['name']) {
            $name                 = Q::quote($form['name']);
            $pre_selector['user'] = "user[name*=$name]";
        }

        if ($form['role']) {
            $roles = json_decode($form['role'], true);
            if (count($roles)) {
                $arr = [];
                foreach ($roles as $id => $name) {
                    $arr[] = "role#{$id}";
                }
                $pre                  = implode(' ', $arr);
                $pre_selector['role'] = "{$pre} user";
            }
        }

        if ($form['lab']) {
            $labId               = Q::quote($form['lab']);
            $pre_selector['lab'] = "lab#{$labId} user";
        }

        if ($form['level']) {
            $level                        = (int) $form['level'];
            $pre_selector['credit_level'] = "credit_level[level={$level}]";
        }

        if (!count($pre_selector) && $form['sort'] == 'name') {
            $pre_selector['user'] = 'user';
        }

        $score_start = (int) $form['score_start'];
        $score_end   = (int) $form['score_end'];
        if ($score_start && $score_end) {
            $selector .= "[total={$score_start}~{$score_end}]";
            $form['credit_score'] = "{$score_start}~{$score_end}";
        } elseif ($score_start) {
            $selector .= "[total>={$score_start}]";
            $form['credit_score'] = "大于等于{$score_start}";
        } elseif ($score_end) {
            $selector .= "[total<={$score_end}]";
            $form['credit_score'] = "小于等于{$score_end}";
        }

        if (count((array) $pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        if ($form['sort'] == 'level') {
            $selector .= ":sort(credit_level.level {$sort_flag})";
        } elseif ($form['sort'] == 'credit_score') {
            $selector .= ":sort(total {$sort_flag})";
        } else {
            $selector .= ':sort(total D)';
        }

        $credits               = Q($selector);
        $form_token            = Session::temp_token('credit_list_', 300);
        $form['selector']      = $selector;
        $_SESSION[$form_token] = $form;

        $panel_buttons = [];
        if ($me->is_allowed_to('添加计分明细', 'credit_record')) {
            $panel_buttons['add'] = [
                'text' => I18N::T('credit', '添加明细'),
                'tip'   => I18N::T('credit', '添加明细'),
                'extra' => 'class="button button_add" q-object="credit_record_add" q-event="click" q-src="' . H(URI::url('!credit/index')) . '"',
                'url'   => "#",
            ];
        }

        if ($me->is_allowed_to('导出', 'credit')) {
            $panel_buttons['print'] = [
                'text' => I18N::T('credit', '打印'),
                'tip'   => I18N::T('credit', '打印'),
                'extra' => 'class="button button_print" q-object="output" q-event="click" q-static="' . H(['form_token' => $form_token, 'type' => 'print']) . '" q-src="' . H(URI::url('!credit/index')) . '"',
                'url'   => "#",
            ];
        }
        if ($me->is_allowed_to('打印', 'credit')) {
            $panel_buttons['export'] = [
                'text' => I18N::T('credit', '导出Excel'),
                'tip'   => I18N::T('credit', '导出Excel'),
                'extra' => 'class="button button_save top" q-object="output" q-event="click" q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) . '" q-src="' . H(URI::url('!credit/index')) . '"',
                'url'   => "#",
            ];
        }

        $per_page      = Config::get('per_page.credit', 30);
        $pagination    = Lab::pagination($credits, (int) $form['st'], $per_page);
        $tabs->content = V('credit:list', [
            'form'          => $form,
            'form_token'    => $form_token,
            'credits'       => $credits,
            'tag'           => $tag,
            'root'          => $root,
            'pagination'    => $pagination,
            'panel_buttons' => $panel_buttons,
            'sort_by'       => $sort_by,
            'sort_asc'      => $sort_asc,
        ]);
    }

    // primary tab 信用明细
    public static function _index_credit_record_content($e, $tabs)
    {
        $me   = L('ME');
        $form = Lab::form(function (&$old_form, &$form) {
            if ($form['ctstart']) {
                $ctstart         = getdate($form['ctstart']);
                $form['ctstart'] = mktime(0, 0, 0, $ctstart['mon'], $ctstart['mday'], $ctstart['year']);
            }

            if ($form['ctend']) {
                $ctend         = getdate($form['ctend']);
                $form['ctend'] = mktime(23, 59, 59, $ctend['mon'], $ctend['mday'], $ctend['year']);
            }
        });

        $selector     = 'credit_record';
        $pre_selector = [];

        if ($me->access('管理所有成员信用分')) {
        } elseif ($me->access('管理下属机构成员的信用分')) {
            if ($me->group_id) {
                $pre_selector['group'] = "{$me->group} user";
            }
        } elseif (Q("{$me}<incharge equipment")->total_count()) {
            $equipments = Q("{$me}<incharge equipment");
            $ids        = [];
            foreach ($equipments as $equipment) {
                $ids[] = $equipment->id;
            }
            $pre_selector['pre_equipment'] = "equipment[id=" . implode(',', $ids) . "]";
        }

        $root = Tag_Model::root('group');
        if ($form['group_id'] && $form['group_id'] != $root->id) {
            $tag                      = O('tag_group', $form['group_id']);
            $pre_selector['tag_user'] = "{$tag} user";
        }

        if ($form['id']) {
            $id       = Q::quote($form['id']);
            $selector = $selector . "[id={$id}]";
        }

        if ($form['name']) {
            $name                 = Q::quote($form['name']);
            $pre_selector['user'] = "user[name*=$name]";
        }

        if ($form['lab']) {
            $labId               = Q::quote($form['lab']);
            $pre_selector['lab'] = "lab#{$labId} user";
        }

        if ($form['ctstart']) {
            $ctstart  = Q::quote($form['ctstart']);
            $selector = $selector . "[ctime>=$ctstart]";
        }

        if ($form['ctend']) {
            $ctend    = Q::quote($form['ctend']);
            $selector = $selector . "[ctime<=$ctend]";
        }

        if ($form['event']) {
            $credit_rule = O('credit_rule', (int) ($form['event']));
            $selector .= "[credit_rule={$credit_rule}]";
        }

        if ($form['equipment']) {
            $equipment = O('equipment', (int) $form['equipment']);
            $selector .= "[equipment={$equipment}]";
        }

        $score_start = (int) $form['score_start'];
        $score_end   = (int) $form['score_end'];
        if ($score_start && $score_end) {
            $selector .= "[score={$score_start}~{$score_end}]";
            $form['credit_score'] = "{$score_start}~{$score_end}";
        } elseif ($score_start) {
            $selector .= "[score>={$score_start}]";
            $form['credit_score'] = "大于等于{$score_start}";
        } elseif ($score_end) {
            $selector .= "[score<={$score_end}]";
            $form['credit_score'] = "小于等于{$score_end}";
        }

        if (isset($form['violate_type']) && $form['violate_type'] != '-1') {
            $violate_type = Q::quote($form['violate_type']);
            if ($form['violate_type'] == 'other') {
                $pre_selector['rule'] = "credit_rule[type=" . Credit_Rule_Model::STATUS_CUT . "][ref_no!=late][ref_no!=early][ref_no!=timeout][ref_no!=miss]";
            } else {
                $pre_selector['rule'] = "credit_rule[type=" . Credit_Rule_Model::STATUS_CUT . "][ref_no={$violate_type}]";
            }
        }

        if (isset($form['type']) && $form['type'] != '-1') {
            $type = (int) $form['type'];
            if ($pre_selector['rule']) {
                $pre_selector['rule'] .= "[type={$type}]";
            } else {
                $pre_selector['rule'] = "credit_rule[type={$type}]";
            }
        }

        $prev_selector = $selector;
        if (count((array) $pre_selector)) {
            $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        }

        // 统计各项违规次数
        $pre_selector['rule'] = $pre_selector['rule'] ? $pre_selector['rule'] . "[type=" . Credit_Rule_Model::STATUS_CUT . "]" : "credit_rule[type=" . Credit_Rule_Model::STATUS_CUT . "]";
        $s                    = '(' . join(',', $pre_selector) . ') ' . $prev_selector;
        $stat['total']        = Q($s)->total_count();

        $rule = $pre_selector['rule'];
        $pre_selector['rule'] = $rule . "[ref_no=late]";
        $s            = '(' . join(',', $pre_selector) . ') ' . $prev_selector;
        $stat['late'] = Q($s)->total_count();

        $pre_selector['rule'] = $rule . "[ref_no=early]";
        $s                    = '(' . join(',', $pre_selector) . ') ' . $prev_selector;
        $stat['early']        = Q($s)->total_count();

        $pre_selector['rule'] = $rule . "[ref_no=timeout]";
        $s                    = '(' . join(',', $pre_selector) . ') ' . $prev_selector;
        $stat['timeout']      = Q($s)->total_count();

        $pre_selector['rule'] = $rule . "[ref_no=miss]";
        $s                    = '(' . join(',', $pre_selector) . ') ' . $prev_selector;
        $stat['miss']         = Q($s)->total_count();

        $stat['other'] = $stat['total'] - $stat['late'] - $stat['early'] - $stat['timeout'] - $stat['miss'];
        unset($pre_selector['rule']);
        unset($s);

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        if ($form['sort'] == 'ctime') {
            $selector .= ":sort(ctime {$sort_flag})";
        } else {
            $selector .= ':sort(ctime D)';
        }

        $credit_records        = Q($selector);
        $form_token            = Session::temp_token('credit_record_list_', 300);
        $form['selector']      = $selector;
        $_SESSION[$form_token] = $form;

        if ($me->is_allowed_to('添加计分明细', 'credit_record')) {
            $panel_buttons['add'] = [
                'text' => I18N::T('credit', '添加明细'),
                'tip'   => I18N::T('credit', '添加明细'),
                'extra' => 'class="button button_add" q-object="credit_record_add" q-event="click" q-src="' . H(URI::url('!credit/index')) . '"',
                'url'   => "#",
            ];
        }

        if ($me->is_allowed_to('打印', 'credit_record')) {
            $panel_buttons['print'] = [
                'text' => I18N::T('credit', '打印'),
                'tip'   => I18N::T('credit', '打印'),
                'extra' => 'class="button button_print" q-object="record_output" q-event="click" q-static="' . H(['form_token' => $form_token, 'type' => 'print']) . '" q-src="' . H(URI::url('!credit/index')) . '"',
                'url'   => "#",
            ];
        }

        if ($me->is_allowed_to('导出', 'credit_record')) {
            $panel_buttons['export'] = [
                'text' => I18N::T('credit', '导出'),
                'tip'   => I18N::T('credit', '导出Excel'),
                'extra' => 'class="button button_save" q-object="record_output" q-event="click" q-static="' . H(['form_token' => $form_token, 'type' => 'csv']) . '" q-src="' . H(URI::url('!credit/index')) . '"',
                'url'   => "#",
            ];
        }

        $per_page      = Config::get('per_page.credit_record', 30);
        $pagination    = Lab::pagination($credit_records, (int) $form['st'], $per_page);
        $tabs->content = V('credit:credit_records', [
            'form'           => $form,
            'form_token'     => $form_token,
            'credit_records' => $credit_records,
            'stat'           => $stat,
            'tag'            => $tag,
            'root'           => $root,
            'pagination'     => $pagination,
            'panel_buttons'  => $panel_buttons,
            'sort_by'        => $sort_by,
            'sort_asc'       => $sort_asc,
        ]);
    }

    public function _index_ban($e, $tabs)
    {
        $me            = L('ME');
        $tabs->content = V('credit:ban');
        $this->add_js('collapse')->add_css('collapse');

        $this->layout->title = I18N::T('credit','黑名单');
        $this->layout->body->primary_tabs = Widget::factory('tabs');

        if ($me->is_allowed_to('查看全局', 'eq_banned')) {
            Event::bind('credit.ban.content', 'Ban_Credit::_index_admin_content', 10, 'admin');
            $this->layout->body->primary_tabs->add_tab('admin', [
                'url'   => URI::url('!credit/ban.admin'),
                'title' => I18N::T('credit', '全局黑名单'),
            ]);
        }

        if ($me->is_allowed_to('查看平台', 'eq_banned')) {
            Event::bind('credit.ban.content', 'Ban_Credit::_index_group_content', 20, 'group');
            $this->layout->body->primary_tabs->add_tab('group', [
                'title' => I18N::T('credit','平台黑名单'),
                'url'   => URI::url('!credit/ban.group'),
            ]);
        }
        if ($me->is_allowed_to('查看仪器', 'eq_banned')) {
            Event::bind('credit.ban.content', 'Ban_Credit::_index_eq_content', 30, 'eqs');
            $this->layout->body->primary_tabs->add_tab('eqs', [
                'title' => I18N::T('credit', '仪器黑名单'),
                'url'   => URI::url('!credit/ban.eqs'),
            ]);
        }

        $params = Config::get('system.controller_params');

        $this->layout->body->primary_tabs
            ->tab_event('credit.ban.tab')
            ->content_event('credit.ban.content');

        $this->layout->body->primary_tabs->select($params[0]);
    }
}

class Index_AJAX_Controller extends AJAX_Controller
{
    public function index_output_click()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            JS::alert(I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            JS::redirect($_SESSION['system.current_layout_url']);
            return false;
        }
        $type    = $form['type'];
        $columns = Config::get('credit.export_columns.credit');
        switch ($type) {
            case 'csv':
                $title       = I18N::T('equipments', '请选择要导出Excel的列');
                $query       = $_SESSION[$form_token]['selector'];
                $total_count = Q($query)->total_count();
                if ($total_count > 8000) {
                    $description = I18N::T('equipments', '数据量过多, 可能导致导出失败, 请缩小搜索范围!');
                }
                break;
            case 'print':
                $title = I18N::T('equipments', '请选择要打印的列');
                break;
        }
        JS::dialog(V('credit:report/output_form', [
            'form_token'  => $form_token,
            'columns'     => $columns,
            'type'        => $type,
            'description' => $description,
        ]), [
            'title' => $title,
        ]);
    }

    public function index_record_output_click()
    {
        $form       = Input::form();
        $form_token = $form['form_token'];
        if (!$_SESSION[$form_token]) {
            JS::alert(I18N::T('equipments', '操作超时, 请刷新页面后重试!'));
            JS::redirect($_SESSION['system.current_layout_url']);
            return false;
        }
        $type    = $form['type'];
        $columns = Config::get('credit.export_credit_record_columns.credit');
        switch ($type) {
            case 'csv':
                $title       = I18N::T('equipments', '请选择要导出Excel的列');
                $query       = $_SESSION[$form_token]['selector'];
                $total_count = Q($query)->total_count();
                if ($total_count > 8000) {
                    $description = I18N::T('equipments', '数据量过多, 可能导致导出失败, 请缩小搜索范围!');
                }
                break;
            case 'print':
                $title = I18N::T('equipments', '请选择要打印的列');
                break;
        }
        JS::dialog(V('credit:report/record_output_form', [
            'form_token'  => $form_token,
            'columns'     => $columns,
            'type'        => $type,
            'description' => $description,
        ]), [
            'title' => $title,
        ]);
    }

    public static function index_update_submit()
    {
        // 历史违规记录信用分刷新
        $form = Form::filter(Input::form());

        $selector = "eq_reserv";

        // 上线之后的数据不进行信用分的刷新
        $online_time = Q("credit:sort(ctime A):limit(1)")->current()->ctime;

        if ($form['dtstart']) {
            $dtstart = Q::quote($form['time_s']);
            if ($online_time < $dtstart) {
                $dtstart = $online_time;
            }
            $selector .= "[dtstart>=$dtstart]";
        }

        if ($form['dtend']) {
            $dtend = Q::quote($form['time_e']);
            if ($online_time < $dtend) {
                $dtend = $online_time;
            }
            $selector .= "[dtstart>0][dtstart<=$dtend]";
        }
        $reservs = Q($selector);

        $credit_rule = Q('credit_rule[is_disabled=0]')->to_assoc('id', 'ref_no');

        if ($reservs->total_count()) {
            foreach ($reservs as $r) {
                if ($r->credit_count) {
                    continue;
                }

                $user  = $r->user;
                $is_on = false;
                if ($r->status == EQ_Reserv_Model::NORMAL && in_array('reserv', $credit_rule)) {
                    Event::trigger('trigger_scoring_rule', $user, 'reserv', $r->equipment, $r);
                    $is_on = true;
                }

                if ($r->status == EQ_Reserv_Model::MISSED && in_array('miss', $credit_rule)) {
                    Event::trigger('trigger_scoring_rule', $user, 'miss', $r->equipment, $r);
                    $is_on = true;
                }

                if ($r->status == EQ_Reserv_Model::OVERTIME && in_array('timeout', $credit_rule)) {
                    Event::trigger('trigger_scoring_rule', $user, 'timeout', $r->equipment, $r);
                    $is_on = true;
                }

                if ($r->status == EQ_Reserv_Model::LATE_OVERTIME) {
                    if (in_array('late', $credit_rule)) {
                        Event::trigger('trigger_scoring_rule', $user, 'late', $r->equipment, $r);
                        $is_on = true;
                    }

                    if (in_array('timeout', $credit_rule)) {
                        Event::trigger('trigger_scoring_rule', $user, 'timeout', $r->equipment, $r);
                        $is_on = true;
                    }
                }

                if ($r->status == EQ_Reserv_Model::LATE && in_array('miss', $credit_rule)) {
                    Event::trigger('trigger_scoring_rule', $user, 'late', $r->equipment, $r);
                    $is_on = true;
                }

                if ($r->status == EQ_Reserv_Model::LATE_LEAVE_EARLY) {
                    if (in_array('late', $credit_rule)) {
                        Event::trigger('trigger_scoring_rule', $user, 'late', $r->equipment, $r);
                        $is_on = true;
                    }

                    if (in_array('early', $credit_rule)) {
                        Event::trigger('trigger_scoring_rule', $user, 'early', $r->equipment, $r);
                        $is_on = true;
                    }
                }

                if ($r->status == EQ_Reserv_Model::LEAVE_EARLY && in_array('early', $credit_rule)) {
                    Event::trigger('trigger_scoring_rule', $user, 'early', $r->equipment, $r);
                    $is_on = true;
                }

                if ($is_on) {
                    $r->credit_count = 1;
                    $r->save();
                }
            }
        }

        Lab::message(Lab::MESSAGE_NORMAL, I18N::HT('credit', '历史违规记录刷新成功!'));
        JS::refresh();
    }

    public function index_credit_record_add_click()
    {
        $me = L('ME');
        if ($me->is_allowed_to('添加信用明细', 'credit')) {
        }

        $form = Input::form();

        $view = V('credit:add_credit_record', [
            'form' => $form,
        ]);
        JS::dialog($view, ['title' => '添加用户信用记录']);
    }

    public function index_credit_record_add_submit()
    {
        $me = L('ME');
        if ($me->is_allowed_to('添加信用明细', 'credit')) {
        }

        $form = Form::filter(Input::form());
        if ($form['submit']) {
            $form->validate('user', 'number(>0)', I18N::T('credit', '用户不能为空!'));
            $form->validate('type', 'number(>=0)', I18N::T('credit', '计分类型不能为空!'));

            if ($form['type'] == 1) {
                $credit_rule = O('credit_rule', (int) $form['cut']);
                if (!$credit_rule->id) {
                    $form->set_error('cut', I18N::T('credit', '计分项不能为空!'));
                } else {
                    if ($credit_rule->ref_no == Credit_Rule_Model::CUSTOM_CUT) {
                        $form->validate('custom_cut_score', 'not_empty', I18N::T('credit', '计分分值不能为空!'));
                        $form->validate('custom_cut_description', 'not_empty', I18N::T('credit', '计分说明不能为空!'));
                    }
                }
            } else {
                $credit_rule = O('credit_rule', (int) $form['add']);
                if (!$credit_rule->id) {
                    $form->set_error('add', I18N::T('credit', '计分项不能为空!'));
                } else {
                    if ($credit_rule->ref_no == Credit_Rule_Model::CUSTOM_ADD) {
                        $form->validate('custom_add_score', 'not_empty', I18N::T('credit', '计分分值不能为空!'));
                        $form->validate('custom_add_description', 'not_empty', I18N::T('credit', '计分说明不能为空!'));
                    }
                }
            }

            $equipment = O('equipment', (int) $form['equipment']);
            if (!$me->access('管理所有成员信用分')) {
                $form->validate('equipment', 'number(>0)', I18N::T('credit', '添加的信用记录关联仪器不能为空!'));

                if ($me->access('管理下属机构成员的信用分') && !$me->group->is_itself_or_ancestor_of($equipment->group)) {
                    $form->set_error('equipment', I18N::T('credit', '需选择下属组织机构的仪器!'));
                }elseif (!$me->access('管理下属机构成员的信用分') && !Q("{$me}<incharge {$equipment}")->total_count()) {
                    $form->set_error('equipment', I18N::T('credit', '只能为自己管理的仪器添加信用记录!'));
                }
            }

            if ($form->no_error) {
                $credit_record = O('credit_record');
                $user = O('user', (int) $form['user']);
                $credit = O('credit', ['user' => $user]);
                if ($form['type'] == 1 && $credit_rule->ref_no == Credit_Rule_Model::CUSTOM_CUT) {
                    //自定义减分项
                    $credit_record->score = -1 * abs(intval($form['custom_cut_score']));
                    $credit_record->description = $form['custom_cut_description'];
                }elseif ($form['type'] == 0 && $credit_rule->ref_no == Credit_Rule_Model::CUSTOM_ADD) {
                    //自定义加分项
                    $credit_record->score = abs(intval($form['custom_add_score']));
                    $credit_record->description = $form['custom_add_description'];
                }else {
                    //系统设置计分项
                    $credit_record->score = $credit_rule->type ? -1 * $credit_rule->score : $credit_rule->score;
                    $credit_record->description = $credit_rule->name . ' (管理员手动计分)';
                }
                $total = $credit->id ? ($credit->total + $credit_record->score) : $credit_record->score;
                $credit_record->user        = $user; // 触发计分用户
                $credit_record->is_auto     = 0; // 自动计分项这里也被强行手动计分
                $credit_record->equipment   = $equipment;
                $credit_record->total       = $total;
                $credit_record->credit_rule = $credit_rule; // 关联计分规则
                $credit_record->ctime       = $form['ctime'] ? mktime(date('H'), date('i'), date('s'), date('m', $form['ctime']), date('d', $form['ctime']), date('Y', $form['ctime']))
                    : Date::time();
                $credit_record->operator    = $me;
                if ($credit_record->save()) {
                    Log::add(strtr('[credit] %user_name[%user_id]为用户%u[%u_id]手动添加计费明细%rule_name[%rule_id]', [
                        '%user_name' => L('ME')->name,
                        '%user_id'   => L('ME')->id,
                        '%u'         => $user->name,
                        '%u_id'      => $user->id,
                        '%rule_name' => $credit_rule->name,
                        '%rule_id'   => $credit_rule->id,
                    ]), 'credit');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::HT('credit', '计分明细添加成功!'));
                    JS::refresh();
                }
            } else {
                $view = V('credit:add_credit_record', [
                    'form' => $form,
                ]);
                JS::dialog($view, ['title' => '添加用户信用记录']);
            }
        }
    }

    public function index_thaw_click()
    {
        $form = Input::form();
        if (!$form['id']) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '信用记录项错误, 请联系管理员!'));
            JS::refresh();
            exit(0);
        }
        $credit = O('credit', (int) $form['id']);

        JS::dialog(V('credit:credit/user_thaw_confirm', [
            'form'   => $form,
            'credit' => $credit,
        ]), [
            'title' => '用户解禁确认',
        ]);
    }

    public function index_thaw_submit()
    {
        $form = Input::form();
        if (!$form['id']) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '信用记录项错误, 请联系管理员!'));
            JS::refresh();
            exit(0);
        }
        $credit      = O('credit', (int) $form['id']);
        $user        = $credit->user;
        $user->atime = Date::time();
        if ($user->save()) {
            Event::trigger('notification.thaw', $credit);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', "用户 {$user->name} 解禁成功!"));
            JS::refresh();
        } else {
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', "用户 {$user->name} 解禁失败, 若多次失败请联系管理员!"));
            JS::refresh();
        }
    }
}
