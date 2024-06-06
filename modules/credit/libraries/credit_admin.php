<?php

class Credit_Admin
{
    public static function setup()
    {
        if (L('ME')->access('管理所有内容')) {
            Event::bind('admin.index.tab', ['Credit_Admin', '_primary_tab'], 0, 'credit');
        }
    }

    public static function _primary_tab($e, $tabs)
    {
        Event::bind('admin.index.content', 'Credit_Admin::_primary_content', 0, 'credit');

        $tabs->add_tab('credit', [
            'title' => T('信用设置'),
            'url'   => URI::url('admin/credit'),
        ]);
    }

    public static function _primary_content($e, $tabs)
    {
        $tabs->content = V('admin/view');

        Event::bind('admin.credit.content', ['Credit_Admin', '_secondary_credit_rule'], 0, 'rule');
        Event::bind('admin.credit.content', ['Credit_Admin', '_secondary_credit_limit'], 0, 'limit');
        Event::bind('admin.credit.content', ['Credit_Admin', '_secondary_credit_level'], 0, 'level');

        $secondary_tabs = Widget::factory('tabs');

        $tabs->content->secondary_tabs = $secondary_tabs
            ->set('class', 'secondary_tabs')
            ->add_tab('rule', [
                'url'   => URI::url('admin/credit.rule'),
                'title' => I18N::T('credit', '计分规则'),
            ])
            ->add_tab('limit', [
                'url'   => URI::url('admin/credit.limit'),
                'title' => I18N::T('credit', '资格限制'),
            ])
            ->add_tab('level', [
                'url'   => URI::url('admin/credit.level'),
                'title' => I18N::T('credit', '等级设置'),
            ])
            ->tab_event('admin.credit.tab')
            ->content_event('admin.credit.content');

        Event::trigger('admin.credit.secondary_tabs', $secondary_tabs);

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs->select($params[1]);
    }

    public static function _secondary_credit_rule($e, $tabs)
    {
        $form          = Input::form();
        $tabs->content = V('credit:admin/credit/types', ['types' => Credit_Rule_Model::$status]);
    }

    public static function _secondary_credit_level($e, $tabs)
    {
        $form = Form::filter(Input::form());

        if (Input::form('submit')) {
            $rank_start = $form['rank_start'];
            $rank_end   = $form['rank_end'];
            $name       = $form['name'];
            foreach ($rank_start as $level => $value) {
                if ($rank_start[$level] > $rank_end[$level]) {
                    $form->set_error('rank_start', I18N::T('credit', '信用分排名占比之前不可相互交叉!'));
                }
            }

            if ($form->no_error) {
                foreach ($rank_start as $level => $value) {
                    $credit_level             = O('credit_level', ['level' => $level]);
                    $credit_level->rank_start = $rank_start[$level];
                    $credit_level->rank_end   = $rank_end[$level];
                    $credit_level->name       = $name[$level];
                    $credit_level->save();
                }
                Lab::message(Lab::MESSAGE_NORMAL, T('更新信用等级成功!'));
            }
        } elseif (Input::form('restore')) {
            Credit_Init::create_credit_default_level(true);
            unset($form['rank_start']);
            unset($form['rank_end']);
            unset($form['name']);
            Lab::message(Lab::MESSAGE_NORMAL, T('恢复默认信用等级成功!'));
        }

        $credit_levels = Q("credit_level:sort(level D)");
        $tabs->content = V('credit:admin/credit/level', ['credit_levels' => $credit_levels, 'form' => $form]);
    }

    public static function _commonuse_credit_notice_content($e, $tabs)
    {
        $configs = [
            'notification.credit.credit_deduction',
            'notification.credit.credit_increase',
            'notification.credit.unactive_user',
            'notification.credit.can_not_reserv',
            'notification.credit.ban',
            'notification.credit.send_msg',
            'notification.credit.eq_ban',
            'notification.credit.lab_ban',
        ];
        $vars = [];
        $form = Form::filter(Input::form());
        if (in_array($form['type'], $configs)) {
            if ($form['submit']) {
                $form
                    ->validate('title', 'not_empty', T('消息标题不能为空！'))
                    ->validate('body', 'not_empty', T('消息内容不能为空！'));
                $vars['form'] = $form;

                if ($form->no_error) {
                    $config = Lab::get($form['type'], Config::get($form['type']));
                    $tmp    = [
                        'description' => $config['description'],
                        'strtr'       => $config['strtr'],
                        'title'       => $form['title'],
                        'body'        => $form['body'],
                    ];

                    foreach ((array) Lab::get('notification.handlers') as $k => $v) {
                        if (isset($form['send_by_' . $k])) {
                            $value = $form['send_by_' . $k];
                        } else {
                            $value = 0;
                        }
                        $tmp['send_by'][$k] = $value;
                    }

                    Lab::set($form['type'], $tmp);
                }
                if ($form->no_error) {
                    Lab::message(Lab::MESSAGE_NORMAL, T('内容修改成功'));
                }
            } elseif ($form['restore']) {
                Lab::set($form['type'], null);
                Lab::message(Lab::MESSAGE_NORMAL, T('恢复系统默认设置成功'));
            }
        }
        $views         = Notification::preference_views($configs, $vars, '');
        $tabs->content = $views;
    }

    public static function _secondary_credit_limit($e, $tabs)
    {
        $form = Form::filter(Input::form());

        //获取禁止预约设置
        $can_not_measures     = O('credit_measures', ['ref_no' => 'can_not_reserv']);
        $can_not_reserv_limit = O('credit_limit', ['measures' => $can_not_measures, 'is_custom' => 0]);
        //获取加入黑名单设置
        $ban_measures = O('credit_measures', ['ref_no' => 'ban']);
        $ban_limit    = O('credit_limit', ['measures' => $ban_measures, 'is_custom' => 0]);
        //获取用户账号变为未激活设置
        $unactive_user_measures = O('credit_measures', ['ref_no' => 'unactive_user']);
        $unactive_user_limit    = O('credit_limit', ['measures' => $unactive_user_measures, 'is_custom' => 0]);
        //获取发送阀值消息设置
        $send_msg_measures = O('credit_measures', ['ref_no' => 'send_msg']);
        $send_msg_limit    = O('credit_limit', ['measures' => $send_msg_measures, 'is_custom' => 0]);
        //获取发送阀值消息设置
        $eq_ban_measures = O('credit_measures', ['ref_no' => 'eq_ban']);
        $eq_ban_limit    = O('credit_limit', ['measures' => $eq_ban_measures, 'is_custom' => 0]);
        $system_ban_measures = O('credit_measures', ['ref_no' => 'system_ban']);
        $system_ban_limit    = O('credit_limit', ['measures' => $system_ban_measures, 'is_custom' => 0]);
        $lab_ban_measures = O('credit_measures', ['ref_no' => 'lab_ban']);
        $lab_ban_limit    = O('credit_limit', ['measures' => $lab_ban_measures, 'is_custom' => 0]);
        
        //获取个别设置
        $specials = Q('credit_limit[is_custom]');

        if ($form['submit']) {
            //验证
            if ($form['send_msg'] == 'on') {
                $score = [];
                if ($form['can_not_reserv'] == 'on') {
                    $score[] = intval($form['can_not_reserv_score']);
                }
                if ($form['ban'] == 'on') {
                    $score[] = intval($form['ban_score']);
                }
                if ($form['unactive_user'] == 'on') {
                    $score[] = intval($form['unactive_user_score']);
                }
                if (max($score) >= $form['send_msg_score']) {
                    $form->set_error('send_msg_score', I18N::T('credit', '阈值应大于最高限制值!'));
                }
            }

            if($form['auto_eq_ban'] == 'on' && $form['auto_eq_ban_score'] < 0){
                $form->set_error('auto_eq_ban_score', I18N::T('credit', '请填写不小于0的数!'));
            }
            if($form['auto_system_ban'] == 'on' && $form['system_ban_score'] < 0){
                $form->set_error('system_ban_socre', I18N::T('credit', '请填写不小于0的数!'));
            }
            if($form['auto_lab_ban'] == 'on' && $form['auto_lab_ban_score'] < 0){
                $form->set_error('auto_lab_ban_score', I18N::T('credit', '请填写不小于0的数!'));
            }

            $special_limits = $form['special'];
            foreach ($special_limits as $k => $special_limit) {
                if ($special_limit['send_msg'] == 'on') {
                    $special_score = [];
                    if ($special_limit['can_not_reserv'] == 'on') {
                        $special_score[] = intval($special_limit['can_not_reserv_score']);
                    }
                    if ($special_limit['ban'] == 'on') {
                        $special_score[] = intval($special_limit['ban_score']);
                    }
                    if ($special_limit['unactive_user'] == 'on') {
                        $special_score[] = intval($special_limit['unactive_user_score']);
                    }
                    if (max($special_score) >= $special_limit['send_msg_score']) {
                        $form->set_error("special[{$k}][send_msg_score]", I18N::T('credit', '阈值应大于最高限制值!'));
                    }
                }
            }

            if ($form->no_error) {
                //保存禁止预约设置
                $can_not_reserv_limit->enable    = $form['can_not_reserv'] == 'on';
                $can_not_reserv_limit->score     = $form['can_not_reserv_score'];
                $can_not_reserv_limit->save();
                //保存加入黑名单设置
                $ban_limit->enable    = $form['ban'] == 'on';
                $ban_limit->score     = $form['ban_score'];
                $ban_limit->ban_day   = $form['ban_day'];
                $ban_limit->save();
                //保存用户账号变为未激活的设置
                $unactive_user_limit->enable    = $form['unactive_user'] == 'on';
                $unactive_user_limit->score     = $form['unactive_user_score'];
                $unactive_user_limit->save();
                //保存发送阀值消息的设置
                $send_msg_limit->enable    = $form['send_msg'] == 'on';
                $send_msg_limit->score     = $form['send_msg_score'];
                $send_msg_limit->save();

                $eq_ban_limit->enable    = (int)($form['auto_eq_ban'] == 'on');
                $eq_ban_limit->score     = $form['auto_eq_ban_score'];
                $eq_ban_limit->save();
                $system_ban_limit->enable    = (int)($form['auto_system_ban'] == 'on');
                $system_ban_limit->score     = $form['auto_system_ban_score'];
                $system_ban_limit->save();
                $lab_ban_limit->enable    = (int)($form['auto_lab_ban'] == 'on');
                $lab_ban_limit->score     = $form['auto_lab_ban_score'];
                $lab_ban_limit->save();

                //保存个别限制设置
                self::special_limits_connect($special_limits);

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('credit', '资格限制更新成功!'));
            }
        }

        $tabs->content = V('credit:admin/credit/limit', [
            'form'                 => $form,
            'can_not_reserv_limit' => $can_not_reserv_limit,
            'ban_limit'            => $ban_limit,
            'unactive_user_limit'  => $unactive_user_limit,
            'send_msg_limit'       => $send_msg_limit,
            'eq_ban_limit'       => $eq_ban_limit,
            'system_ban_limit'       => $system_ban_limit,
            'lab_ban_limit'       => $lab_ban_limit,
            'specials'             => $specials,
        ]);
    }

    private static function special_limits_connect($specials)
    {
        $exists_limit_ids = Q('credit_limit[is_custom]')->to_assoc('id', 'id');
        foreach ($specials as $special) {
            $users  = $special['users'] ? json_decode($special['users'], true) : [];
            $labs   = $special['labs'] ? json_decode($special['labs'], true) : [];
            $groups = $special['groups'] ? json_decode($special['groups'], true) : [];
            foreach (Q('credit_measures') as $measures) {
                if ($special[$measures->ref_no . '_id']) {
                    $limit = O('credit_limit', $special[$measures->ref_no . '_id']);
                    unset($exists_limit_ids[$special[$measures->ref_no . '_id']]);
                } else {
                    $limit            = O('credit_limit');
                    $limit->measures  = O('credit_measures', ['ref_no' => $measures->ref_no]);
                    $limit->is_custom = 1;
                }
                $limit->enable    = $special[$measures->ref_no] == 'on';
                $limit->score     = $special[$measures->ref_no . '_score'];
                if ($measures->ref_no == 'ban') {
                    $limit->ban_day = $special['ban_day'];
                }

                $limit->save();

                if ($special[$measures->ref_no] == 'on') {
                    self::_limit_connect_users($limit, $users);
                    self::_limit_connect_labs($limit, $labs);
                    if (Config::get('equipment.enable_group_specs')) {
                        self::_limit_connect_groups($limit, $groups);
                    }
                } else {
                    self::_limit_connect_users($limit, $users, false);
                    self::_limit_connect_labs($limit, $labs, false);
                    self::_limit_connect_groups($limit, $groups, false);
                }
            }
        }
        //删除个别设置
        foreach ($exists_limit_ids as $exists_limit_id) {
            $exists_limit = O('credit_limit', $exists_limit_id);
            self::_limit_connect_users($exists_limit, [], false);
            self::_limit_connect_labs($exists_limit, [], false);
            self::_limit_connect_groups($exists_limit, [], false);
            $exists_limit->delete();
        }
    }

    private static function _limit_connect_users($limit, $users, $connect = true)
    {
        $connected_users = Q("{$limit} user");
        if ($connect) {
            foreach ($users as $uid => $name) {
                //给标签关联新的user,并删除不存在的user
                $user = O('user', $uid);
                if ($user->id) {
                    if (!isset($connected_users[$user->id])) {
                        $limit->connect($user);
                    } else {
                        unset($connected_users[$user->id]);
                    }
                }
            }
        }
        if (count($connected_users)) {
            foreach ($connected_users as $user) {
                $limit->disconnect($user);
            }
        }
    }

    private static function _limit_connect_labs($limit, $labs, $connect = true)
    {
        $connected_labs = Q("{$limit} lab");
        if ($connect) {
            foreach ($labs as $lid => $name) {
                //给标签关联新的lab,并删除不存在的lab
                $lab = O('lab', $lid);
                if ($lab->id) {
                    if (!isset($connected_labs[$lab->id])) {
                        $limit->connect($lab);
                    } else {
                        unset($connected_labs[$lab->id]);
                    }
                }
            }
        }
        if (count($connected_labs)) {
            foreach ($connected_labs as $lab) {
                $limit->disconnect($lab);
            }
        }
    }

    private static function _limit_connect_groups($limit, $groups, $connect = true)
    {
        $root             = Tag_Model::root('group');
        $connected_groups = Q("{$limit} tag_group[root={$root}]");
        if ($connect) {
            foreach ($groups as $gid => $name) {
                //给标签关联新的lab,并删除不存在的lab
                $group = O('tag_group', $gid);
                if ($group->id) {
                    if (!isset($connected_groups[$gid])) {
                        $limit->connect($group);
                    } else {
                        unset($connected_groups[$gid]);
                    }
                }
            }
        }
        if (count($connected_groups)) {
            foreach ($connected_groups as $group) {
                $limit->disconnect($group);
            }
        }
    }
}
