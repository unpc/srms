<?php
class Profile_Controller extends Base_Controller
{
    private static function _reg_logapper_user($user, $passwd)
    {
        $logapperConfig = Config::get('logapper');
        $logapper = new LoGapper();
        $result = $logapper->post('auth/app-token', [
            'client_id'=> $logapperConfig['client_id'],
            'client_secret'=> $logapperConfig['client_secret']
        ]);

        if (!isset($result['access_token'])) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '用户注册失败，请重试.'));
            return;
        }

        $logapper = new LoGapper();
        $result = $logapper->setAccessToken($result['access_token'])->post('user', [
            'name'=> $user->name,
            'email'=> $user->email,
            'phone'=> $user->phone,
            'ref_no'=> $user->ref_no,
            'username'=> $user->token,
            'password'=> $passwd
        ]);
        if (!$result || !isset($result['id']) || !$result['id']) {
            Lab::message(Lab::MESSAGE_ERROR, @$result['error']['message'] ?: I18N::T('labs', '用户注册失败，请重试'));
            return;
        }
        return $result['id'];
    }

    public function index($id = 0, $tab = '')
    {

        $user = O('user', $id);
        if (!$user->id) {
            URI::redirect('error/404');
        }

        if (!L('ME')->is_allowed_to('查看', $user)) {
            URI::redirect('error/401');
        }

        $reserv                   = Event::trigger('eq_reserv.pending.count', $user);
        $approval                 = Event::trigger('approval.pending.count', $user);
        $sample                   = Event::trigger('eq_sample.pending.count', $user);
        $training_status_applied  = UE_Training_Model::STATUS_APPLIED;
        $training_status_approved = UE_Training_Model::STATUS_APPROVED;
        $training = Event::trigger('eq_training.pending.count', $user) ? : 0;
        $todo                     = $reserv + $approval + $sample + $training;

        $job = Q("ue_training[user=$user][status=$training_status_approved]")->total_count();

        $research = Event::trigger('achievements.author.count', $user);

        /* if (Module::is_installed('eq_ban')) {
            $user_v      = O('user_violation', ['user' => $user]);
            $late        = (int) $user_v->eq_late_count;
            $leave_early = (int) $user_v->eq_leave_early_count;
            $overtime    = (int) $user_v->eq_overtime_count;
            $miss        = (int) $user_v->eq_miss_count;
            $violation   = $late + $leave_early + $overtime + $miss;
        } else {
            $violation = 0;
        } */

        if (Module::is_installed('credit')) {
            $credit = O('credit', ['user' => $user]);
            $score = (int)$credit->total;
        } else {
            $score = 0;
        }

		$stat = [
			'todo' => $todo,
			'job' => $job,
			'research' => $research,
            'violation' => $violation,
            'credit_score' => $score,
		];

        $stat = [
            'todo'      => $todo,
            'job'       => $job,
            'research'  => $research,
            'credit_score' => $score,
        ];

        $content = V('profile/view', ['user' => $user, 'stat' => $stat]);

        // $this->layout->body->primary_tabs
        //     ->add_tab('profile', [
        //         'url'   => $user->url(),
        //         'title' => H($user->name),
        //     ])
        //     ->set('content', $content)
        //     ->select('profile');

        Event::bind('profile.view.tab', [$this, '_index_follow_tab'], -10, 'follow');
        Event::bind('profile.view.content', [$this, '_index_follow_content'], -10, 'follow');

        $this->layout->body->primary_tabs = Widget::factory('tabs');

        $sections = new ArrayIterator;

        Event::trigger('user.view.general.sections', $user, $sections);

        if (count($sections)) {
            Event::bind('profile.view.tab', [$this, '_index_general_tab'], -200, 'general');
            $this->layout->body->primary_tabs->set('sections', $sections);
        }

        $this->layout->body->primary_tabs
            //->set('class', 'secondary_tabs')
            ->set('user', $user)
            ->tab_event('profile.view.tab')
            ->content_event('profile.view.content')
            ->tool_event('profile.view.tool_box')
            ->select($tab);

        $breadcrumbs = [
                [
                    'url' => '!people/list',
                    'title' => I18N::T('people', '成员目录'),
                ],
                [
                    'title' => $user->name,
                ]
        ];
    
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);
        $this->layout->header_content = V('profile/header_content', ['user' => $user, 'stat' => $stat]);
        $this->layout->title = I18N::T('people', '');

        $this->add_css('people:stat');

    }

    public function activate($id = 0)
    {

        $user = O('user', $id);

        if (!$user->id || !L('ME')->is_allowed_to('添加', $user)) { //管理用户 => 添加/修改成员信息
            URI::redirect('error/401');
        }

        if ($user->is_active()) {
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户 %name 已经激活', ['%name' => $user->name]));
        } else {
            $user->atime = time();
            $user->save();

            Notification::send('people.activate', $user, [
                '%user'  => Markup::encode_Q($user),
                '%login' => $user->token,
                '%link'  => URI::url('/'),
            ]);
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户 %name 激活成功', ['%name' => $user->name]));

        }

        URI::redirect(URI::url($_SESSION['system.current_layout_url']));

    }

    public function edit($id = 0, $tab = 'info')
    {
        $user = O('user', $id);
        if (!$user->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');

        if (!$me->is_allowed_to('修改', $user)) {
            URI::redirect('error/401');
        }
        /*
        NO.TASK#274(guoping.zhang@2010.11.24)
        应用权限判断新规则后，controller中不再有access
         */
        if ($me->is_allowed_to('修改', $user, ['@ignore' => '自己'])) {
            Event::bind('profile.edit.content', [$this, '_admin_edit_info'], 0, 'info');
        } else {
            Event::bind('profile.edit.content', [$this, '_edit_info'], 0, 'info');
        }

        // Event::bind('profile.edit.content', [$this, '_edit_photo'], 0, 'photo');
        Event::bind('profile.edit.content', [$this, '_edit_message'], 0, 'message');

        $content                 = V('profile/edit');
        $this->layout->body->primary_tabs = Widget::factory('tabs');

        if(!People::perm_in_uno()){
            $this->layout->body->primary_tabs->add_tab('info', [
                'url'   => $user->url('info', null, null, 'edit'),
                'title' => I18N::T('people', '基本'),
            ])->add_tab('message', [
                'weight' => 70,
                'url'    => $user->url('message', null, null, 'edit'),
                'title'  => I18N::T('people', '消息通知'),
            ]);
        }

        if ($yiqikong_lab_name = Config::get('people.yiqikong_lab_name')
            && !Q("$user lab[name={$yiqikong_lab_name}]")->total_count() && !People::perm_in_uno()) {
            Event::bind('profile.edit.content', [$this, '_edit_role'], 0, 'role');
            $this->layout->body->primary_tabs
                ->add_tab('role', [
                    'url'   => $user->url('role', null, null, 'edit'),
                    'title' => I18N::T('people', '角色'),
                ]);
        }

        list(, $backend) = Auth::parse_token($user->token);
        if (!People::perm_in_uno() && ($user->token && $backend != 'yiqikong' || Event::trigger('people.allow_tmpuser_add_token',$user)) && !config::get('people.link_gapper_system')) {
            Event::bind('profile.edit.content', [$this, '_edit_account'], 0, 'account');
            $this->layout->body->primary_tabs
                ->add_tab('account', [
                    'url'   => URI::url('!people/profile/edit.' . $user->id . '.account'),
                    'title' => I18N::T('people', '帐号'),
                ]);
        }

        $this->layout->body->primary_tabs
            ->set('user', $user)
            ->tab_event('profile.edit.tab')
            ->content_event('profile.edit.content')
            ->select($tab);

        $this->layout->title = H($user->name);
        $breadcrumbs = [
            [
                'url' => '!people/list/index',
                'title' => I18N::T('equipments', '成员目录'),
            ],
            [
                'url' => $user->url(),
                'title' => $user->name,
            ],
            [
                'title' => '修改',
            ],
        ];
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);

    }

    public function delete($id = 0)
    {

        $form = Input::form();
        $user = O('user', $id);
        $me   = L('ME');
        /*
        NO.BUG#099
        2010.11.04
        张国平
        添加对传入参数id的逻辑判断
         */
        if (!$user->id || !$me->is_allowed_to('删除', $user)) { //管理用户 => 添加/修改成员信息
            URI::redirect('error/401');
        }

        try {

            $message = Event::trigger('user.before_delete_message', $user);
            if ($message) {
                if ($user->atime) {
                    $user->atime = 0;
                    $user->save();
                    $msg_suf = I18N::T('people', '已将该用户设置成未激活用户!');
                    Lab::message(Lab::MESSAGE_NORMAL, $message . ' ' . $msg_suf);
                    URI::redirect($form['referer_url'] ?: '!people');
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, $message);
                    throw new Error_Exception;
                }
            }

            if ($user->undeletable) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '该用户已经被管理员设置成不可以删除。'));
                throw new Error_Exception;
            }

            if ($user->id == $me->id) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '警告：您在尝试删除自己的帐号！'));
                throw new Error_Exception;
            }
            $link_gapper_system = Config::get('people.link_gapper_system');
            if ($link_gapper_system) {
                $user->atime = 0;
                $user->save();
            } else {
                if ($user->delete()) {

                    Log::add(strtr('[people] %admin_name[%admin_id]删除了用户%user_name[%user_id]', ['%admin_name' => $me->name, '%admin_id' => $me->id, '%user_name' => $user->name, '%user_id' => $user->id]), 'journal');

                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '删除用户失败!'));
                    throw new Error_Exception;
                }
            }

            $auth = new Auth($user->token);
            $auth->remove();
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '删除用户成功！'));

        } catch (Error_Exception $e) {
            URI::redirect($user->url(null, null, null, 'edit'));
        }

        URI::redirect(URI::url('!people'));
    }

    public function delete_photo($id = 0)
    {
        $user = O('user', $id);
        $me   = L('ME');

        if (!$me->is_allowed_to('修改', $user)) {
            URI::redirect('error/401');
        }

        $user->delete_icon();

        /* 记录日志 */
        Log::add(strtr('[people] %admin_name[%admin_id]修改了用户%user_name[%user_id]的头像', ['%admin_name' => $me->name, '%admin_id' => $me->id, '%user_name' => $user->name, '%user_id' => $user->id]), 'journal');

        URI::redirect('!people/profile/edit.' . $user->id . '.photo');
    }

    public function sync_card($id = 0)
    {
        $user = O('user', $id);
        $me   = L('ME');

        if (!$me->is_allowed_to('修改', $user) && Module::is_installed('entrance')) {
            URI::redirect('error/401');
        }

        $user->sync_card();

        URI::redirect('!people/profile/edit.' . $user->id . '.photo');
    }

    public function _edit_role($e, $tabs)
    {
        $user = $tabs->user;
        $me   = L('ME');
        if (!$me->is_allowed_to('管理角色', $user)) {
            $uneditable = true;
        }

        if (!$uneditable && Input::form('submit')) {

            $form = Form::filter(Input::form());

            $user_roles = $user->roles();
            if ($form->no_error) {
                $form_roles     = (array) $form['roles'];
                $subtract_roles = array_keys(array_diff_key($user_roles, $form_roles));
                $add_roles      = [];
                $my_perms       = $me->perms();

                $legal_perms = (array) Q("perm")->to_assoc('name', 'id');
                $roles       = L('ROLES');
                $is_admin    = $me->access('管理所有内容') || $me->access('管理分组');
                foreach (array_diff_key($form_roles, $user_roles) as $rid => $foo) {
                    if ($roles[$rid]) {
                        if ($is_admin) {
                            $add_roles[] = $rid;
                        } else {
                            $perms = array_intersect_key((array) $roles[$rid]->perms, $legal_perms);
                            if (count(array_diff_key($perms, $my_perms)) == 0) {
                                $add_roles[] = $rid;
                            }
                        }
                    }
                }

                if (count($add_roles) > 0) {
                    $user->connect(['role', $add_roles]);
                }

                if (count($subtract_roles) > 0) {
                    $user->disconnect(['role', $subtract_roles]);
                }

                Event::trigger('user.after_role_change', $user, $add_roles, $subtract_roles);
                /* 记录日志 */
                Log::add(strtr('[people] %admin_name[%admin_id]修改了用户%user_name[%user_id]的角色', [
                    '%admin_name' => $me->name,
                    '%admin_id'   => $me->id,
                    '%user_name'  => $user->name,
                    '%user_id'    => $user->id,
                ]), 'journal');

                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户分组信息修改成功！'));
            }
        }

        $tabs->content = V('profile/edit.role', ['user' => $user, 'uneditable' => $uneditable]);
    }

    public function _admin_edit_info($e, $tabs)
    {
        $user = $tabs->user;
        $me   = L('ME');

        $group_root = Tag_Model::root('group');

        $form = Form::filter(Input::form());
        $form['link_gapper_system'] = Config::get('people.link_gapper_system');

        if (Input::form('submit') == '上传图标') {
            $this->_edit_photo($e, $tabs);
            return;
        }

        if (Input::form('submit')) {
            if (!$form['token'] || $form['token'] != $_SESSION['people.admin_edit_info']) {
                URI::redirect('error/401');
            }

            $form['name'] = strip_tags($form['name']);
            //if ($form['link_gapper_system']) $form['email'] = $user->email;

            $requires = Lab_Model::edit_require_fields();
            $data = Event::trigger("user_signup_requires", $requires, $user);
			$requires = is_null($data) ? $requires : $data;
            Event::trigger('signup.validate_extra_field', $requires, $form, $user);

            array_walk($requires, function ($v, $k) use ($me, $form, $user, $group_root) {
                if ($v) {
                    switch ($k) {
                        case 'name':
                            $form->validate('name', 'not_empty', I18N::T('people', '请填写用户姓名!'));
                            break;
                        case 'email':
                            if ($yiqikong_lab_name = Config::get('people.yiqikong_lab_name')
                                && Q("$user lab[name!={$yiqikong_lab_name}]")->total_count()) {
                                if (!$form['email']) {
                                    $form->set_error('email', I18N::T('people', 'Email不能为空!'));
                                } else {
                                    $form->validate('email', 'is_email', I18N::T('people', 'Email填写有误!'));

                                    $exist_user = O('user', ['email' => $form['email']]);
                                    if ($exist_user->id && $exist_user->id != $user->id) {
                                        $form->set_error('email', I18N::T('people', '您填写的电子邮箱在系统中已经存在!'));
                                    }
                                }
                            }
                            break;
                        case 'phone':
                            $form->validate('phone', 'not_empty', I18N::T('people', '请填写联系电话!'));
                            break;
                        case 'member_type':
                            if ($form['member_type'] < 0) {
                                $form->set_error('member_type', I18N::T('people', '请选择人员类型!'));
                            }

                            break;
                        case 'ref_no':
                            $form->validate('ref_no', 'not_empty', I18N::T('people', '请填写学号/工号!'));
                            if (trim($form['ref_no'])) {
                                $ref_user = O('user', ['ref_no' => trim($form['ref_no'])]);
                                if ($ref_user->id && $ref_user->id != $user->id) {
                                    $form->set_error('ref_no', I18N::T('people', '您填写的学号/工号在系统中已经存在!'));
                                }
                            }
                            break;
                        case 'card_no':
                            $form->validate('card_no', 'not_empty', I18N::T('people', '请填写IC卡卡号!'));
                            if ($form['card_no']) {
                                $exist_user = O('user', ['card_no' => $form['card_no']]);
                                if ($exist_user->id && $exist_user->id != $user->id) {
                                    $form->set_error('card_no', I18N::T('people', '您填写的IC卡卡号在系统中已经存在!'));
                                }
                            }
                            break;
                        case 'time':
                            if ($GLOBALS['preload']['people.enable_member_date']) {
                                if (!$form['dto'] && !$form['dfrom']) {
                                    $form->set_error('dto', I18N::T('people', '请填写所在时间!'));
                                }
                            }
                            break;
                        case 'gender':
                            $form->validate('gender', 'is_numeric', I18N::T('people', '请选择性别!'));
                            break;
                        case 'group_id':
                            if ($me->is_allowed_to('修改组织机构', $user)) {
                                $group = O('tag_group', $form['group_id']);
                                if (!$group->id || $group->root->id != $group_root->id) {
                                    $form->set_error('group_id', I18N::T('people', '请选择组织机构!'));
                                }
                            }
                            break;
                        case 'organization':
                            $form->validate('organization', 'not_empty', I18N::T('people', '请填写单位名称!'));
                            break;
                        case 'mentor_name':
                            if (Config::get('people.show_mentor_name', false)) {
                                $form->validate('mentor_name', 'not_empty', I18N::T('people', '请填写导师姓名!'));
                            }
                            break;
                        case 'major':
                            $form->validate('major', 'not_empty', I18N::T('people', '请填写专业!'));
                            break;
                        case 'personal_phone':
                            if (Config::get('people.show_personal_phone', false)) {
                                $form->validate('personal_phone', 'not_empty', I18N::T('people', '请填写个人手机!'));
                            }
                            break;
                        case 'address':
                            $form->validate('address', 'not_empty', I18N::T('people', '请填写地址!'));
                        default:
                            break;
                    }
                } else {
                    switch ($k) {
                        case 'ref_no':
                            if (trim($form['ref_no'])) {
                                $ref_user = O('user', ['ref_no' => trim($form['ref_no'])]);
                                if ($ref_user->id && $ref_user->id != $user->id) {
                                    $form->set_error('ref_no', I18N::T('people', '您填写的学号/工号在系统中已经存在!'));
                                }
                            }
                            break;
                        case 'card_no':
                            if ($form['card_no']) {
                                $exist_user = O('user', ['card_no' => $form['card_no']]);
                                if ($exist_user->id && $exist_user->id != $user->id) {
                                    $form->set_error('card_no', I18N::T('people', '您填写的IC卡卡号在系统中已经存在!'));
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            });

            if ($form['card_no']) {
                $card_no_start = Config::get('form.validate.card_no.start', 6);
                $card_no_end   = Config::get('form.validate.card_no.end', 10);
                $form->validate('card_no', 'is_numeric', I18N::T('labs', '请填写合法的IC卡卡号!'));
                $form->validate('card_no', "length({$card_no_start},{$card_no_end})", I18N::T('people', "填写的IC卡卡号不能小于%card_no_start位, 最长不能大于%card_no_end位!", ['%card_no_start' => $card_no_start, '%card_no_end' => $card_no_end]));
                //[case]#20201571 武汉大学药学院马宏敏老师卡号无法跟新到系统里。
                if(str_pad(0, 10, '0', STR_PAD_LEFT) !== $form['card_no']) {
                    $exist_user = O('user', ['card_no'=>$form['card_no']]);
                    if ($exist_user->id && $exist_user->id != $user->id) {
                        $form->set_error('card_no', I18N::T('people', '您填写的IC卡卡号在系统中已经存在!'));
                    }
                }
			}

            //临时课题组的用户可对税务登记号进行修正
            //如果为必填
			//如果没填写税务登记号
			$default_lab = Equipments::default_lab();
            if (Module::is_installed('equipments') 
                && $default_lab
                && Q("$user $default_lab")->total_count()
                && Config::get('people.temp_user.tax_no.required', false)
                && !$form['tax_no']) {
                $form->set_error('tax_no', I18N::T('people', '请填写税务登记号!'));
            }

            if ($form->no_error) {

                if (Module::is_installed('equipments')
                    && $default_lab = Equipments::default_lab()
                    && Q("$user $default_lab")->total_count()) {
                    Event::trigger('equipments.record.create_user_before_save', $user, $form);
                    $user->tax_no = $form['tax_no'];
                }

                // $user->ref_no = $form['ref_no'];
                $user->name         = $form['name'];
                $user->organization = $form['organization'];

                // 仪器控课题组用户的邮箱不可以更改
                if ($yiqikong_lab_name = Config::get('people.yiqikong_lab_name')
                    && !Q("$user lab[name=$yiqikong_lab_name]")->total_count()) {
                    $user->email = $form['email'];
                }
                $user->member_type = $form['member_type'];
                $user->gender      = $form['gender'];
                $user->ref_no      = trim($form['ref_no']) ?: null;

                $user->major = $form['major'];
                if (Config::get('people.show_mentor_name', false)) {
                    $user->mentor_name = $form['mentor_name'];
                }

                if (Config::get('people.show_personal_phone', false)) {
                    $user->personal_phone = $form['personal_phone'];
                }
                if ($me->is_allowed_to('修改', $user)) {
                    $user->undeletable = $form['undeletable'];
                }

                if ($me->is_allowed_to('激活', $user)) {
                    if (!$form['activate']) {
                        $user->atime = 0;
                    } elseif ($user->atime == 0) {
                        $user->atime = time();
                        $send_mail   = true;
                    }
                }

                if (isset($form['hidden']) && $me->is_allowed_to('隐藏', $user)) { // form 中可能无 hidden 项, 会使 hidden 为 null, 而 schema 中 hidden 不能为 null(xiaopei.li@2012-02-09)
                    $user->hidden = $form['hidden'];
                }

                $user->phone   = $form['phone'];
                $user->address = $form['address'];
                /*
                Hongjie.Zhu@2010.11.11
                $card_no = intval($form['card_no']);
                32位的服务器，由于intval的取值范围时－214748368～214748367；可能出现溢出的情形：
                如果数值大于2147483637，则返回2147483648
                 */
                $card_no = $form['card_no'] + 0; //添加 +0 可强制转换成无符号数

                if ($card_no) {
                    $user->card_no   = $card_no;
                    $user->card_no_s = $card_no & 0xffffff;
                }
                /*
                本来是想注释掉else的，
                管理员修改用户信息时应隐藏用户的IC卡号，
                提交时若未设置新的IC卡号，则用户卡号不变，
                但以这样的逻辑无法再将卡号置为空。
                (xiaopei.li@2011.03.25)
                 */
                elseif (empty($form['card_no']) && $form['card_no'] != '0') {
                    $user->card_no   = null;
                    $user->card_no_s = null;
                }

                if ($GLOBALS['preload']['people.enable_member_date']) {
                    if ($form['dfrom'] && $form['dto']) {
                        if ($form['dfrom'] > $form['dto']) {
                            list($form['dto'], $form['dfrom']) = [$form['dfrom'], $form['dto']];
                        }
                    }
                    $user->dfrom = $form['dfrom'] ? Date::get_day_start($form['dfrom']) : 0;
                    $user->dto   = $form['dto'] ? Date::get_day_end($form['dto']) : 0;
                    // echo '<pre>';print_r($user);echo '</pre>';exit(0);
                }

                $group = O('tag_group', $form['group_id']);
                try {
                    if ($me->is_allowed_to('修改组织机构', $user)) {
                        if (!$me->is_allowed_to('修改组织机构', $user, ['@ignore' => '修改下属机构成员'])
                            && $me->group->id && $group->id && !$me->group->is_itself_or_ancestor_of($group)) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您设置的组织机构必须是您下属机构！'));
                            throw new Error_Exception;
                        }

                        $group_root->disconnect($user);
                        $user->group = O('tag_group');
                        if ($group->root->id == $group_root->id) {
                            $group->connect($user);
                            $user->group = $group;
                        }
                    }
                    Event::trigger('signup.save_extra_field', $user, $form);
                    if ($user->save()) {
                        unset($form['group_id']);
                        // 记录日志
                        Log::add(strtr('[people] %admin_name[%admin_id]修改了用户%user_name[%user_id]的基本信息', ['%admin_name' => $me->name, '%admin_id' => $me->id, '%user_name' => $user->name, '%user_id' => $user->id]), 'journal');

                        if ($send_mail) {
                            Notification::send('people.activate', $user, [
                                '%user'  => Markup::encode_Q($user),
                                '%login' => $user->token,
                                '%link'  => URI::url('/'),
                            ]);
                        }
                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户信息已更新'));
                    } else {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '用户信息更新失败'));
                        throw new Error_Exception;
                    }
                } catch (Error_Exception $e) {
                }
            }

        }

        $tabs->content             = V('profile/admin/edit.info');
        $tabs->content->form       = $form;
        $tabs->content->group_root = $group_root;

        $this->add_js('enter_to_tab');
    }

    public function _edit_info($e, $tabs)
    {
        $me   = L('ME');
        $user = $tabs->user;

        $form = Form::filter(Input::form());
        $form['link_gapper_system'] = Config::get('people.link_gapper_system');

        if (Input::form('submit') == '上传图标') {
            $result = $this->_edit_photo($e, $tabs);
            return $result;
        }

        if (Input::form('submit')) {
            if (!$form['token'] || $form['token'] != $_SESSION['people.edit_info']) {
                URI::redirect('error/401');
            }

            $requires = Lab_Model::add_require_fields();

            $data = Event::trigger("user_signup_requires", $requires, $user);
			$requires = is_null($data) ? $requires : $data;
            
            array_walk($requires, function ($v, $k) use ($form, $user, $group_root) {
                if ($v) {
                    switch ($k) {
                        case 'email':
                            if (!$form['email']) {
                                $form->set_error('email', I18N::T('people', 'Email不能为空!'));
                            } else {
                                $form->validate('email', 'is_email', I18N::T('people', 'Email填写有误!'));
                                $exist_user = O('user', ['email' => $form['email']]);
                                if ($exist_user->id && $exist_user->id != $user->id) {
                                    $form->set_error('email', I18N::T('people', '您填写的电子邮箱在系统中已经存在!'));
                                }
                            }

                            break;
                        case 'phone':
                            $form->validate('phone', 'not_empty', I18N::T('people', '请填写联系电话!'));
                            break;
                        case 'member_type':
                            if ($form['member_type'] < 0) {
                                $form->set_error('member_type', I18N::T('people', '请选择人员类型!'));
                            }

                            break;
                        case 'ref_no':
                            $form->validate('ref_no', 'not_empty', I18N::T('people', '请填写学号/工号!'));
                            if (trim($form['ref_no'])) {
                                $ref_user = O('user', ['ref_no' => trim($form['ref_no'])]);
                                if ($ref_user->id && $ref_user->id != $user->id) {
                                    $form->set_error('ref_no', I18N::T('people', '您填写的学号/工号在系统中已经存在!'));
                                }
                            }
                            break;
                        case 'gender':
                            $form->validate('gender', 'is_numeric', I18N::T('people', '请选择性别!'));
                            break;
                        case 'organization':
                            $form->validate('organization', 'not_empty', I18N::T('people', '请填写单位名称!'));
                            break;
                        case 'mentor_name':
                            if (Config::get('people.show_mentor_name', false)) {
                                $form->validate('mentor_name', 'not_empty', I18N::T('people', '请填写导师姓名!'));
                            }
                            break;
                        case 'major':
                            $form->validate('major', 'not_empty', I18N::T('people', '请填写专业!'));
                            break;
                        case 'personal_phone':
                            if (Config::get('people.show_personal_phone', false)) {
                                $form->validate('personal_phone', 'not_empty', I18N::T('people', '请填写个人手机!'));
                            }
                            break;
                        case 'address':
                            $form->validate('address', 'not_empty', I18N::T('people', '请填写地址!'));
                        default:
                            break;
                    }
                } else {
                    switch ($k) {
                        case 'ref_no':
                            if (trim($form['ref_no'])) {
                                $ref_user = O('user', ['ref_no' => trim($form['ref_no'])]);
                                if ($ref_user->id && $ref_user->id != $user->id) {
                                    $form->set_error('ref_no', I18N::T('people', '您填写的学号/工号在系统中已经存在!'));
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            });

            if ($form->no_error) {

                $user->email   = $form['email'];
                $user->phone   = $form['phone'];
                $user->address = $form['address'];
                if (Config::get('people.show_mentor_name', false)) {
                    $user->mentor_name = $form['mentor_name'];
                }

                if (Config::get('people.show_personal_phone', false)) {
                    $user->personal_phone = $form['personal_phone'];
                }

                // 普通用户仅能修改自己所在子类的人员类型
                foreach (User_Model::get_members() as $k => $v) {
                    if (isset($v[$user->member_type])) {
                        if (isset($v[$form['member_type']])) {
                            $user->member_type = $form['member_type'];
                        }
                        break;
                    }
                }
                $user->ref_no = trim($form['ref_no']) ?: null;
                $user->gender = $form['gender'];

                $user->major        = $form['major'];
                $user->organization = $form['organization'];

                if ($user->save()) {

                    Log::add(strtr('[people] %admin_name[%admin_id]修改了用户%user_name[%user_id]的基本信息', ['%admin_name' => $me->name, '%admin_id' => $me->id, '%user_name' => $user->name, '%user_id' => $user->id]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户信息已更新'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '用户信息更新失败'));
                }
            }

        }

        $tabs->content       = V('profile/edit.info');
        $tabs->content->form = $form;

    }

    public function _edit_photo($e, $tabs)
    {

        $user = $tabs->user;
        if (Input::form('submit')) {
            $file = Input::file('file');

            if ($file['tmp_name']) {
                $ext = File::extension($file['name']);
                try {
                    $image = Image::load($file['tmp_name'], $ext);

                    if ($user->save_icon($image)) {

                        // 同步头像到仪器控
                        if ($user->gapper_id) {
                            $icon_file = Core::file_exists(PRIVATE_BASE . 'icons/user/128/' . $user->id . '.png', '*');
                            if ($icon_file) {
                                $icon_url = Config::get('system.base_url') . 'icon/user.' . $user->id . '.128';
                            }

                            $data = [
                                'jsonrpc' => '2.0',
                                'method'  => 'YiQiKong/User/UpdateInfo',
                                'params'  => [
                                    'user' => $this->gapper_id,
                                    'icon' => $icon_url ?: '',
                                ],
                            ];

                            Debade_Queue::of('YiQiKong')->push($data, 'user');
                        }

                        /* 记录日志 */
                        Log::add(strtr('[people] %admin_name[%admin_id]修改了用户%user_name[%user_id]的头像', ['%admin_name' => L('ME')->name, '%admin_id' => L('ME')->id, '%user_name' => $user->name, '%user_id' => $user->id]), 'journal');

                        return 1;
                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户头像已更新'));
                    }
                } catch (Error_Exception $e) {
                    return 2;
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '用户头像更新失败, 可能是图像格式不支持。'));
                }
            } else {
                return 3;
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '请上传头像文件'));
            }
        }

        $tabs->content = V('profile/edit.photo');
    }

    public function _edit_account($e, $tabs)
    {
        if (config::get('people.link_gapper_system')) {
            URI::redirect('error/401');
        }
        $user                  = $tabs->user;
        $me                    = L('ME');
        list($token, $backend) = Auth::parse_token($user->token);
        if (Input::form('submit')) {
            $auth_backends = Config::get('auth.backends');
            $auth          = new Auth($user->token);
            $form          = Form::filter(Input::form());

            try {
                /*
                 * 具备管理所有内容权限能更改用户帐号
                 */

                /*
                (xiaopei.li@2011.05.29)
                if (改了用户名 || 改了验证后台) {
                validate token;
                validate backend;
                报错;
                }
                if 改了验证后台 {
                require/validate新密码;
                Auth::add(new_auth, new_password); // 创建 auth 时需要密码
                if 要求删原 auth {
                Auth::remove(old_auth);
                }
                }
                else if 只改了用户名 {
                Auth::change_token;
                }
                }
                 */
                if ($form['new_pass']) {
                    $form->validate('confirm_pass', 'compare(==new_pass)', I18N::T('people', '两次填写的密码不一致!'));
                    $form->validate('new_pass', 'length(8, 24)', I18N::T('people', '填写的密码不能小于8位, 最长不能大于24位!'));
                    if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['new_pass'])) {
                        $form->set_error('new_pass', I18N::T('people', '密码必须包含大写字母、小写字母和数字!'));
                    }

                    if ($me->id == $user->id) {
                        // 本人修改要求填写原始密码
                        $form
                            ->validate('old_pass', 'not_empty', I18N::T('people', '原始密码不能为空!'));

                        if ($form->no_error) {
                            if (!$auth->verify($form['old_pass'])) {
                                $form->set_error('old_pass', I18N::T('people', '原始密码验证有误!'));
                            }
                        }
                    }
                } elseif ($form['backend'] == 'database' && !$form['new_pass'] && !$form['confirm_pass']) {
                    $form->set_error('new_pass', I18N::T('people', '请填写新密码!'));
                }

                if (!$form->no_error) {
                    throw new Exception;
                }

                if ($me->access('管理所有内容')
                    && ((string) $token !== (string) $form['token'] || $backend != $form['backend'])) {

                    Event::trigger('people.profile.add.validate', $form);

                    $form->validate('token', 'is_token', I18N::T('people', '登录帐号不符合要求!'));
                    if (!$this->_validate_token_backend($form['backend'])) {
                        $form->set_error('backend', '验证后台不合法');
                    }

                    $new_token = $form['token'];
                    $new_token = preg_replace('/\|.*$/', '', $new_token);

                    /*
                    BUG#446(xiaopei.li@2011.03.25)
                    去除token中|后的内容，以防误修改了backend；
                    但更应该在is_token中修改规则，不容许有竖线 |
                     */
                    $new_backend = $form['backend'];

                    if (!$form->no_error) {
                        throw new Exception;
                    }

                    $new_full_token = Auth::make_token($new_token, $new_backend);
                    if (User_Model::is_reserved_token($form['token']) || User_Model::is_reserved_token($new_full_token)) {
                        throw new Exception(I18N::T('people', '您填写的帐号已被管理员保留。'));
                    }

                    $name_token = O('user', ['token' => $new_full_token]);
                    if ($name_token->id && $name_token->id != $user->id) {
                        $form->set_error('token', I18N::T('people', '您填写的帐号在系统中已存在!'));
                        throw new Exception;
                    }
                    if ($backend != $new_backend) {

                        $new_auth = new Auth($new_full_token);
                        //设定临时auth
                        if ($new_auth->create(uniqid())) {
                            $user->token = $new_full_token;
                            if ($user->save()) {
                                //修改自己token
                                if ($user->id == $me->id) {
                                    Auth::login($user->token);
                                }

                                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户登录帐号已更新'));

                                Log::add(strtr('[people] %admin_name[%admin_id]修改了用户%user_name[%user_id]的帐号', ['%admin_name' => L('ME')->name, '%admin_id' => L('ME')->id, '%user_name' => $user->name, '%user_id' => $user->id]), 'journal');

                                if ($form['remove_former_auth'] == 'on') {
                                    $ret = $auth->remove();
                                    if ($ret) {
                                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '旧token已删除'));
                                    } else {
                                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '旧token删除失败'));
                                    }
                                }
                            } else {
                                throw new Exception(I18N::T('people', '用户登录帐号更新失败!'));
                            }
                        } else {
                            throw new Exception(I18N::T('people', '新帐号创建失败'));
                        }
                    } else if ((string) $token !== (string) $new_token) {
                        $old_token = $user->token;

                        if ($auth->change_token($new_token)) {
                            $user->token = strtolower($new_full_token);
                            if ($user->save()) {
                                //修改自己token
                                if ($user->id == $me->id) {
                                    Auth::login($user->token);
                                }

                                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户登录帐号已更新'));

                                Log::add(strtr('[people] %admin_name[%admin_id]修改了用户%user_name[%user_id]的帐号', ['%admin_name' => L('ME')->name, '%admin_id' => L('ME')->id, '%user_name' => $user->name, '%user_id' => $user->id]), 'journal');

                            } else {
                                throw new Exception(I18N::T('people', '用户登录帐号更新失败!'));
                            }
                        } else {
                            throw new Exception(I18N::T('people', 'token 更新失败!'));
                        }
                    }
                }

                if ($form['new_pass']) {
                    //如果设定了新密码
                    //并且选择了新的backend
                    //有可能为new_auth
                    //当为new_auth时候,需要设定new_auth change_password
                    if ($new_auth) {
                        $auth = $new_auth;
                    }

                    //由于复旦高分子文件系统需要lims用户账号，所以在此存入用户密码，用来和文件系统账号同步
                    if (Module::is_installed('nfs_windows')) {
                        $_SESSION['fs_usertoken']['password'] = $form['new_pass'];
                    }

                    if ($auth->change_password($form['new_pass'])) {
                        $user->must_change_password = $form['must_change_password'];
                        $user->save();

                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户密码已更新'));

                        /* 记录日志 */
                        Log::add(strtr('[people] %admin_name[%admin_id]修改了用户%user_name[%user_id]的密码', ['%admin_name' => L('ME')->name, '%admin_id' => L('ME')->id, '%user_name' => $user->name, '%user_id' => $user->id]), 'journal');

                    } else {
                        throw new Exception(I18N::T('people', '用户密码更新失败!'));
                    }
                }

                /* else { */
                /*     throw new Exception(I18N::T('people', '用户密码没有修改')); */
                /* } */

            } catch (Exception $e) {
                $message = $e->getMessage();
                if ($message) {
                    Lab::message(Lab::MESSAGE_ERROR, $message);
                }

            }

        }
        $this->layout->form = $form;
        $tabs->content      = V('profile/edit.account', [
            'token'   => isset($new_token) ? strtolower($new_token) : $token,
            'backend' => isset($new_backend) ? $new_backend : $backend]
        );
    }

    // 添加“我的关注项”
    public function _index_follow_tab($e, $tabs)
    {
        $user = $tabs->user;
        /*
        NO.TASK#274(guoping@2010.11.24)
        应用权限判断新规则
         */
        $me = L('ME');
        if ($me->is_allowed_to('列表关注', $user)) {
            $tabs->add_tab('follow', [
                'url'   => $tabs->user->url('follow'),
                'title' => I18N::T('people', "关注"),
            ]);
        }

    }

    public function _index_follow_content($e, $tabs)
    {
        $tabs->content = V('follow/follow');

        Event::bind('profile.follow.tab', [$this, '_index_follow_users_tab'], -100, 'user');
        Event::bind('profile.follow.content', [$this, '_index_follow_users_content'], -100, 'user');

        $params = Config::get('system.controller_params');

        $tabs->content->secondary_tabs = Widget::factory('tabs')
            //->set('class', 'third_tabs')
            ->set('class', 'secondary_tabs')
            ->set('user', $tabs->user)
            ->tab_event('profile.follow.tab')
            ->content_event('profile.follow.content')
            ->select($params[2]);
    }

    /*
    NO.TASK#274(guoping@2010.11.24)
    应用权限判断新规则
     */
    public function _index_follow_users_tab($e, $tabs)
    {
        $me   = L('ME');
        $user = $tabs->user;
        if ($me->is_allowed_to('列表关注的用户', $user)) {
            $count = $user->get_follows_count('user');
            $tabs
                ->add_tab('user', [
                    'url'    => $tabs->user->url('follow.user'),
                    'title'  => I18N::T('people', '成员 [%count]', ['%count' => $count]),
                    'weight' => 99,
                ]);
        }
    }

    public function _index_follow_users_content($e, $tabs)
    {
        $user    = $tabs->user;
        $follows = $user->followings('user');

        $start      = (int) Input::form('st');
        $per_page   = 20;
        $pagination = Lab::pagination($follows, $start, $per_page);

        $tabs->content = V('follow/users', ['follows' => $follows, 'pagination' => $pagination]);
    }

    /**
     * 检查backend是否合法；
     * 更应该放到Form中。
     * (xiaopei.li@2011.05.29)
     *
     * @param backend
     *
     * @return boolean
     */
    private function _validate_token_backend($backend)
    {
        $backends = Config::get('auth.backends');
        return in_array(trim($backend), array_keys($backends));
    }

    public function _index_general_tab($e, $tabs)
    {

        Event::bind('profile.view.content', [$this, '_index_general_content'], -200, 'general');
        $tabs->add_tab('general', [
            'url'   => $tabs->user->url('general'),
            'title' => I18N::T('people', '简介'),
        ]);
    }

    public function _index_general_content($e, $tabs)
    {

        $sections = $tabs->sections;

        $tabs->content = V('people:profile/general', ['sections' => $sections]);
    }

    public function _edit_message($e, $tabs)
    {
        $form = Form::filter(Input::form());
        $user = $tabs->user;

        if ($form['submit']) {

            if (!trim($form['binding_email'])) {
                $form->set_error('binding_email', I18N::T('people', '电子邮箱不能为空!'));
            } else {
                $form->validate('binding_email', 'is_email', I18N::T('people', '电子邮箱填写有误!'));
            }

            $user->binding_email = $form['binding_email'];
            Event::trigger('people.profile.edit.message.submit', $user, $form);
            if ($form->no_error) {
                if ($user->save()) {
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '绑定成功！'));
                }
            }
        }
        $tabs->content = V('people:profile/edit.message', ['user' => $user, 'form' => $form]);
    }
}

class Profile_AJAX_Controller extends AJAX_Controller
{

    public function index_apply_lab_click()
    {
        JS::dialog((string) V('labs:lab/apply'), [
            'title' => I18N::T('lab', '申请加入课题组'),
        ]);
    }

    public function index_apply_lab_submit()
    {
        $me                    = L('ME');
        $user                  = O('user', $me->id);
        list($token, $backend) = Auth::parse_token($user->token);

        if (!$user->is_active()
            || ($temp_lab_id = Lab::get('equipment.temp_lab_id') && !Q("$user lab[id={$temp_lab_id}]")->total_count())
            || $backend != Config::get('jiangnan.auth_backend')
        ) {
            URI::redirect('error/401');
        }

        $form = Form::filter(Input::form());

        if ($form['submit']) {

            if ($form['lab'] != null && (!is_numeric($form['lab']) || $form['lab'] == 0)) {
                $form->set_error('lab', I18N::T('labs', '请选择正确的课题组！'));
            } else if ($form['lab'] == null) {
                $form->set_error('lab', I18N::T('labs', '请选择正确的课题组！'));
            }

            if ($form->no_error) {

                $lab = O('lab', $form['lab']);

                if (!$lab->id) {
                    $lab = Lab_Model::default_lab();
                }

                if (!Q("$user $lab")->total_count()) {
                    $user->apply_lab = $lab;
                }

                JS::close_dialog();

                if ($user->save()) {
                    JS::alert(I18N::T('lab', '申请成功'));
                } else {
                    JS::alert(I18N::T('lab', '申请失败'));
                }

                JS::refresh();
            } else {
                JS::dialog((string) V('labs:lab/apply', ['form' => $form]), [
                    'title' => I18N::T('lab', '申请加入课题组'),
                ]);
            }
        }
    }

    public function index_apply_lab_allow_click()
    {
        $form = Input::form();
        $user = O('user', $form['uid']);

        if (JS::confirm(I18N::T('lab', '您确定要通过该申请吗?'))) {
            $apply_lab       = $user->apply_lab;
            $user->apply_lab = 0;

            if ($user->save()) {
                $user->connect($apply_lab);

                Notification::send('people.allow_apply', $user, [
                    '%user' => Markup::encode_Q($user),
                    '%lab'  => Markup::encode_Q($apply_lab),
                ]);
            }

            JS::refresh();
        }
    }

    public function index_apply_lab_refuse_click()
    {
        $form = Input::form();
        $user = O('user', $form['uid']);
        $lab  = $user->apply_lab;

        if (JS::confirm(I18N::T('lab', '您确定要拒绝该申请吗?'))) {
            $user->apply_lab = 0;

            if ($user->save()) {

                Notification::send('people.refuse_apply', $user, [
                    '%user'      => Markup::encode_Q($user),
                    '%lab'       => Markup::encode_Q($lab),
                    '%lab_phone' => $lab->owner->phone,
                    '%lab_email' => $lab->owner->email,
                ]);
            }

            JS::refresh();
        }
    }

    public function index_add_user_record_click()
    {
        $me = L('ME');
        if (!$me->is_allowed_to('添加', 'user')) { // 管理用户 => 添加/修改成员信息
            URI::redirect('error/401');
        }

        $group_root = Tag_Model::root('group');

        $roles    = [];
        $my_perms = $me->perms();
        $is_admin = $me->access('管理所有内容');

        $roles = Event::trigger('add.user.roles.filter');

        if (!count($roles)) {
            foreach (L('ROLES') as $r) {
                if ($r->weight < 0) {
                    continue;
                }

                if (!$is_admin) {
                    $r_perms = Q("$r perm")->to_assoc('name', 'name');
                    $diff = array_diff_key($r_perms, $my_perms);
                    if ($diff) {
                        continue;
                    }
                }
                $roles[$r->id] = $r;
            }
        }

        $form = Form::filter(Input::form());
        if (empty($form['tab'])) {
            $tab = 'all';
        } else {
            $tab = $form['tab'];
        }

        $view = V('profile/add', [
            'form'       => $form,
            'group_root' => $group_root,
            'roles'      => $roles,
        ]);

        JS::dialog($view, ['title' => I18N::T('people', '添加用户')]);
    }

    public function index_add_user_record_submit()
    {
        $me = L('ME');

        // 管理用户 => 添加/修改成员信息
        if (!$me->is_allowed_to('添加', 'user')) { 
            URI::redirect('error/401');
        }

        $group_root = Tag_Model::root('group');

        $roles    = [];
        $my_perms = $me->perms();
        $is_admin = $me->access('管理所有内容');

        $roles = Event::trigger('add.user.roles.filter');

        if (!count($roles)) {
            foreach (L('ROLES') as $r) {
                if ($r->weight < 0) {
                    continue;
                }

                if (!$is_admin) {
                    $diff = array_diff_key((array) $r->perms, $my_perms);
                    if ($diff) {
                        continue;
                    }
                }
                $roles[$r->id] = $r;
            }
        }

        $form = Form::filter(Input::form());

        if (empty($form['tab'])) {
            $tab = 'all';
        } else {
            $tab = $form['tab'];
        }

        if ($form['submit']) {

            $user = O('user');
            //shulei.li@20140508 复旦高分子文件系统
            //添加一个数组用来保存用户密码信息，在用户保存成功后保存到fs_usertoken数据库，用来与文件系统账户进行同步
            //由于复旦高分子文件系统需要lims用户账号，所以在此存入用户密码，用来和文件系统账号同步
            if (Module::is_installed('nfs_windows')) {
                $_SESSION['fs_usertoken']['password'] = $form['password'];
            }

            $token         = Auth::make_token(trim($form['token']), trim($form['backend']));
            $auth          = new Auth($token);
            $requires      = Lab_Model::add_require_fields();

            $data = Event::trigger("user_signup_requires", $requires, 'new_user');
            $requires = is_null($data) ? $requires : $data;

            $auth_backends = Config::get('auth.backends');

            Event::trigger('people.profile.add.validate', $form);

            array_walk($requires, function ($v, $k) use ($form, $user, $group_root, $auth, $auth_backends) {
                if ($v) {
                    switch ($k) {
                        case 'token':
                            if ($form['token']) {
                                $form->validate('token', 'is_token', I18N::T('people', '请填写符合规则的登录帐号!'));

                                $token = Auth::make_token(trim($form['token']), trim($form['backend']));
                                if (O('user', ['token' => $token])->id) {
                                    $form->set_error('token', I18N::T('people', '您填写的登录帐号在系统中已存在!'));
                                }

                                if (User_Model::is_reserved_token($form['token']) || User_Model::is_reserved_token($token)) {
                                    $form->set_error('token', I18N::T('people', '您填写的帐号已被管理员保留。'));
                                }
                            } else {
                                $form->set_error('token', I18N::T('people', '请填写登录帐号!'));
                            }
                            break;
                        case 'backend':
                            $form->validate('backend', 'not_empty', I18N::T('people', '请选择验证后台!'));
                            if ($form['backend'] && !$this->_validate_token_backend($form['backend'])) {
                                $form->set_error('backend', '验证后台不合法');
                            }
                            break;
                        case 'passwd':
                            if ($auth->is_creatable() && !$auth->is_readonly()) {
                                if (!$form['password']) {
                                    $form->validate('password', 'not_empty', I18N::T('people', '密码不能为空!'));
                                } elseif ($auth->is_creatable()) {
                                    if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['password'])) {
                                        $form->set_error('password', I18N::T('people', '密码必须包含大写字母、小写字母和数字!'));
                                    }
                                    $form->validate('password', 'length(8,24)', I18N::T('people', '填写的密码不能小于8位, 最长不能大于24位!'));
                                }
                            }
                            break;
                        case 'confirm_passwd':
                            if ($auth->is_creatable() && !$auth->is_readonly()) {
                                if ($form['confirm_password'] != $form['password']) {
                                    $form->set_error('password', null);
                                    $form->set_error('confirm_password', I18N::T('people', '请填写有效密码并确保两次填写的密码一致!'));
                                }
                            }
                            break;
                        case 'name':
                            $form->validate('name', 'not_empty', I18N::T('people', '请填写用户姓名!'));
                            break;
                        case 'member_type':
                            if ($form['member_type'] < 0) {
                                $form->set_error('member_type', I18N::T('people', '请选择人员类型!'));
                            }

                            break;
                        case 'ref_no':
                            $form->validate('ref_no', 'not_empty', I18N::T('people', '请填写学号/工号!'));
                            if (trim($form['ref_no'])) {
                                $ref_user = O('user', ['ref_no' => trim($form['ref_no'])]);
                                if ($ref_user->id && $ref_user->id != $user->id) {
                                    $form->set_error('ref_no', I18N::T('people', '您填写的学号/工号在系统中已经存在!'));
                                }
                            }
                            break;
                        case 'card_no':
                            $form->validate('card_no', 'not_empty', I18N::T('people', '请填写IC卡卡号!'));
                            if ($form['card_no']) {
                                $exist_user = O('user', ['card_no' => $form['card_no']]);
                                if ($exist_user->id && $exist_user->id != $user->id) {
                                    $form->set_error('card_no', I18N::T('people', '您填写的IC卡卡号在系统中已经存在!'));
                                }
                            }
                            break;
                        case 'lab':
                            //installed再进行判断
                            if (Module::is_installed('labs')) {
                                $lab = O('lab', $form['lab']);
                                if (!$lab->id) {
                                    $form->set_error('lab', I18N::T('people', '请选择实验室!'));
                                }
                            }
                            break;
                        case 'time':
                            if ($GLOBALS['preload']['people.enable_member_date']) {
                                if (!$form['dfrom'] && !$form['dto']) {
                                    $form->set_error('dto', I18N::T('people', '请填写所在时间!'));
                                }
                            }
                            break;
                        case 'gender':
                            $form->validate('gender', 'is_numeric', I18N::T('people', '请选择性别!'));
                            break;
                        case 'group_id':
                            $group = O('tag_group', $form['group_id']);
                            if (!$group->id || $group->root->id != $group_root->id) {
                                $form->set_error('group_id', I18N::T('people', '请选择组织机构!'));
                            }
                            break;
                        case 'organization':
                            $form->validate('organization', 'not_empty', I18N::T('people', '请填写单位名称!'));
                            break;
                        case 'mentor_name':
                            if (Config::get('people.show_mentor_name', false)) {
                                $form->validate('mentor_name', 'not_empty', I18N::T('people', '请填写导师姓名!'));
                            }
                            break;
                        case 'major':
                            $form->validate('major', 'not_empty', I18N::T('people', '请填写专业!'));
                            break;
                        case 'personal_phone':
                            if (Config::get('people.show_personal_phone', false)) {
                                $form->validate('personal_phone', 'not_empty', I18N::T('people', '请填写个人手机!'));
                            }
                            break;
                        case 'email':
                            if (!$form['email']) {
                                $form->set_error('email', I18N::T('people', 'Email不能为空!'));
                            } else {
                                $form->validate('email', 'is_email', I18N::T('people', 'Email填写有误!'));
                                $exist_user = O('user', ['email' => $form['email']]);
                                if ($exist_user->id && $exist_user->id != $user->id) {
                                    $form->set_error('email', I18N::T('people', '您填写的电子邮箱在系统中已经存在!'));
                                }
                            }
                            break;
                        case 'phone':
                            $form->validate('phone', 'not_empty', I18N::T('people', '请填写联系电话!'));
                            break;
                        case 'address':
                            $form->validate('address', 'not_empty', I18N::T('people', '请填写地址!'));
                        default:
                            break;
                    }
                } else {
                    switch ($k) {
                        case 'ref_no':
                            if (trim($form['ref_no'])) {
                                $ref_user = O('user', ['ref_no' => trim($form['ref_no'])]);
                                if ($ref_user->id && $ref_user->id != $user->id) {
                                    $form->set_error('ref_no', I18N::T('people', '您填写的学号/工号在系统中已经存在!'));
                                }
                            }
                            break;
                        case 'card_no':
                            if ($form['card_no']) {
                                $exist_user = O('user', ['card_no' => $form['card_no']]);
                                if ($exist_user->id && $exist_user->id != $user->id) {
                                    $form->set_error('card_no', I18N::T('people', '您填写的IC卡卡号在系统中已经存在!'));
                                }
                            }
                            break;
                        case 'backend':
                            if ($form['backend'] && !$this->_validate_token_backend($form['backend'])) {
                                $form->set_error('backend', '验证后台不合法');
                            }
                            break;
                        default:
                            break;
                    }
                }
            });

            if ($form['card_no']) {
                $card_no_start = Config::get('form.validate.card_no.start', 6);
                $card_no_end   = Config::get('form.validate.card_no.end', 10);
                $form->validate('card_no', 'is_numeric', I18N::T('labs', '请填写合法的IC卡卡号!'));
                $form->validate('card_no', "length({$card_no_start},{$card_no_end})", I18N::T('people', "填写的IC卡卡号不能小于%card_no_start位, 最长不能大于%card_no_end位!", ['%card_no_start' => $card_no_start, '%card_no_end' => $card_no_end]));
            }

            if ($form->no_error) {
                try {
                    $token = Auth::make_token($form['token'], $form['backend']);
                    // $token = Auth::normalize(trim($form['token']));

                    if (User_Model::is_reserved_token($form['token']) || User_Model::is_reserved_token($token)) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您填写的登录帐号已被保留。'));
                        throw new Error_Exception;
                    }

                    /*
                    BUG #506(cheng.liu@2011.04.26)
                    将帐号和电子邮箱错误提示的信息分开处理，让用户更直接的获取到提示
                     */
                    if (O('user', ['token' => $token])->id) {
                        //如果token不是唯一的跳转到注册页面.
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您填写的帐号在系统中已存在!'));
                        throw new Error_Exception;
                    }

                    if (O('user', ['email' => $form['email']])->id) {
                        //如果email不是唯一的跳转到注册页面.
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您填写的电子邮箱在系统中已存在！'));
                        throw new Error_Exception;
                    }

                    $ref_user = O('user', ['ref_no' => trim($form['ref_no'])]);
                    if ($form['ref_no'] && $ref_user->id) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您填写的学号/工号在系统中已存在！'));
                        throw new Error_Exception;
                    }

                    $group = O('tag_group', $form['group_id']);
                    if (!$me->is_allowed_to('修改组织机构', 'user', ['@ignore' => '修改下属机构成员'])
                        && $me->group->id && $group->id
                        && !$me->group->is_itself_or_ancestor_of($group)) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您设置的组织机构必须是您下属机构！'));
                        throw new Error_Exception;
                    }

                    $auth = new Auth($token);
                    if (!$auth->is_readonly() && !$auth->create($form['password'])) {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '添加新成员失败! 请与系统管理员联系。'));
                        throw new Error_Exception;
                    }

                    $user->name  = $form['name'];
                    $user->token = strtolower($token);
                    // $user->ref_no = trim($form['ref_no']);

                    $user->email        = $form['email'];
                    $user->organization = $form['organization'];
                    $user->phone        = $form['phone'];
                    $user->member_type  = $form['member_type'];
                    $user->gender       = $form['gender'];
                    $user->major        = $form['major'];
                    // $user->department = $form['department'];
                    if (Config::get('people.show_mentor_name', false)) {
                        $user->mentor_name = $form['mentor_name'];
                    }
                    if (Config::get('people.show_personal_phone', false)) {
                        $user->personal_phone = $form['personal_phone'];
                    }
                    $user->address     = $form['address'];
                    $user->undeletable = $form['undeletable'];
                    $user->ref_no      = trim($form['ref_no']) ?: null;

                    $user->creator      = $me;
                    $user->creator_abbr = PinYin::code($me->name);

                    if ($form['dfrom']
                        &&
                        $form['dto']
                        &&
                        $form['dfrom'] > $form['dto']
                    ) {
                        list($form['dto'], $form['dfrom']) = [$form['dfrom'], $form['dto']];
                    }

                    if ($form['dto']) {
                        $user->dto = Date::get_day_end(strtotime($form['dto']));
                    }

                    if ($form['dfrom']) {
                        $user->dfrom = Date::get_day_start(strtotime($form['dfrom']));
                    }

                    if (isset($form['hidden']) && $me->is_allowed_to('隐藏', $user)) {
                        $user->hidden = $form['hidden'];
                    }

                    if ($form['activate'] && $me->is_allowed_to('激活', $user)) {
                        $user->atime = Date::time();
                    }

                    if ($form['must_change_password'] && $form['must_change_password']!='null' && !$auth_backends[$form['backend']]['readonly']) {
                        $user->must_change_password = true;
                    }
                    if ($user->save()) {
                        if (Module::is_installed('labs')) {
                            $lab = O('lab', $form['lab']);
                            $user->connect($lab);
                        }

                        /* 记录日志 */
                        Log::add(strtr('[people] %admin_name[%admin_id]添加了用户%user_name[%user_id]', ['%admin_name' => $me->name, '%admin_id' => $me->id, '%user_name' => $user->name, '%user_id' => $user->id]), 'journal');

                    } else {
                        $auth->remove(); //添加新成员失败，去掉已添加的 token
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '添加新成员失败! 请与系统管理员联系。'));
                        throw new Error_Exception;
                    }

                    if ($me->is_allowed_to('修改组织机构', 'user')) {
                        if ($group->root->id == $group_root->id) {
                            $group_root->disconnect($user);
                            $group->connect($user);
                            $user->group = $group;
                            $user->save();
                        }
                    }

                    if ($form['activate']) {
                        $connect_role = [];
                        
                        foreach ((array) $form['roles'] as $key => $rid) {
                            if (!isset($roles[$key]) || $rid=='null') {
                                continue;
                            }

                            $connect_role[] = $key;
                        }
                        if (count($connect_role)) {
                            $user->connect(['role', $connect_role]);
                        }

                        Event::trigger('user.after_role_change', $user, $connect_role, []);
                    }

                    $arr_user_token      = explode('|', $user->token);
                    $token_backend_title = $auth_backends[$arr_user_token['1']]['title'];
                    $user_token          = implode(' | ', [$arr_user_token['0'], T($token_backend_title)]);

                    try
                    {
                        Log::add("发送成员添加消息", 'journal');
                        Notification::send('people.add', $user, [
                            '%login'    => $user_token,
                            '%user'     => H($user->name),
                            '%password' => H($form['password']),
                            '%link'     => URI::url('/'),
                        ]);
                        Log::add("发送成员添加消息完成", 'journal');
                    }
                    catch(Exception $e)
                    {
                        Log::add("发送成员添加消息失败", 'journal');
 
                    }
                    
                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '用户添加成功！'));
                    Log::add(strtr('[people] %admin_name[%admin_id]添加了用户%user_name[%user_id]', ['%admin_name' => L('ME')->name, '%admin_id' => L('ME')->id, '%user_name' => $$user->name, '%user_id' => $user->id]), 'admin');
                    Js::redirect('!people/profile/edit.' . $user->id);
                } catch (Error_Exception $e) {
                }
            }
        } else {
            if (preg_match('/^role_(-?\d+)$/', $tab, $matches)) {
                $form['roles'][$matches[1]] = 'on';
            } else {
                $default_roles = (array) Config::get('roles.default_roles');
                foreach ($default_roles as $r) {
                    if ($tab == $r['key']) {
                        $mt_key              = $r['member_type_key'] ?: $r['name'];
                        $form['member_type'] = key(User_Model::get_members()[$mt_key]);
                        break;
                    }
                }
            }
        }

        $view = V('profile/add', [
            'form'       => $form,
            'group_root' => $group_root,
            'roles'      => $roles,
        ]);

        JS::dialog($view, ['title' => I18N::T('people', '添加用户')]);
    }

    function index_todo_click () {
        $form = Input::form();
        $user = O('user', $form['uid']);
        if(!$user->id) {
            URI::redirect('error/404');
        }

        if (!L('ME')->is_allowed_to('查看', $user)) {
            URI::redirect('error/401');
        }

        $reserv = Event::trigger('eq_reserv.pending.count', $user) ? : 0;
        $approval = Event::trigger('approval.pending.count', $user) ? : 0;
        $sample = Event::trigger('eq_sample.pending.count', $user) ? : 0;
        $training = Event::trigger('eq_training.pending.count', $user) ? : 0;
        JS::dialog((string)V('profile/todo',[
            'reserv' => $reserv,
            'approval' => $approval,
            'sample' => $sample,
            'training' => $training,
            'user' => $user,
        ]), [
            'title' => I18N::T('people', '点击查看各项详细待办事宜')
        ]);
    }
}
