<?php

class Lab_Controller extends Base_Controller
{

    public function index($id = 0, $tab = 'members', $account_type = 'list')
    {

        $lab = O('lab', $id);
        $me = L('ME');

        if (!$lab->id) {
            URI::redirect('error/404');
        }

        if (!$me->is_allowed_to('查看', $lab)) {
            URI::redirect('error/401');
        }

        $content = V('lab/view', ['lab' => $lab]);

        $this->layout->body->primary_tabs->set_tab('all',null)
            ->set('content', $content);

        Event::bind('lab.view.content', [$this, '_index_members_content'], 0, 'members');
        Event::bind('lab.view.tool_box', [$this, '_index_members_tool'], 0, 'members');

        $this->layout->body->primary_tabs = Widget::factory('tabs');

        if ($tab == 'add_member' && L('ME')->is_allowed_to('添加成员', $lab)) {
            $this->layout->body->primary_tabs->add_tab('add_member', [
                'url' => $lab->url('add_member'),
                'title' => I18N::T('labs', '添加成员'),
                'weight' => 99,
            ]);
            Event::bind('lab.view.content', [$this, '_index_add_member'], 0, 'add_member');
        }

        if(!People::perm_in_uno()){
            $this->layout->body->primary_tabs
            ->add_tab('members', [
                'url' => $lab->url('members'),
                'title' => I18N::T('labs', '实验室成员'),
            ]);
        }

        $this->layout->body->primary_tabs
            ->set('lab', $lab)
            ->set('account_type', $account_type)
            ->tab_event('lab.view.tab')
            ->content_event('lab.view.content')
            ->tool_event('lab.view.tool_box')
            ->select($tab);

        $breadcrumbs = [
            [
                'url' => '!labs/list',
                'title'=>I18N::T('labs', '实验室目录'),
            ],
            [
                'title' => $lab->name,
            ]
        ];
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);
        $this->layout->header_content = V('lab/header_content', ['lab' => $lab]);
        $this->layout->title = I18N::T('labs', '');

        $this->add_css('labs:common');
    }

    public function _index_add_member($e, $tabs)
    {
        $lab = $tabs->lab;
        $me = L('ME');

        if (!$me->is_allowed_to('添加成员', $lab)) {
            return;
        }

        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        if (Input::form('submit')) {
            $user = O('user');
            $form = Form::filter(Input::form());

            Event::trigger('before.index_add_member.submit', $form);

            $token = Auth::make_token(trim($form['token']), trim($form['backend']));
            $auth = new Auth($token);
            $requires = Lab_Model::add_require_fields();

            if ($form['add_from'] == 'new') {
                $token = Auth::make_token(trim($form['token']), trim($form['backend']));
                $auth = new Auth($token);
                $requires = Lab_Model::add_require_fields();


                array_walk($requires, function ($v, $k) use ($form, $user, $group_root, $auth) {
                    if ($v) {
                        switch ($k) {
                            case 'token':
                                $form->validate('token', 'is_token', I18N::T('labs', '请填写符合规则的用户帐号!'));
                                if (User_Model::is_reserved_token($form['token'])) {
                                    $form->set_error('token', I18N::T('labs', '您填写的帐号已被管理员保留。'));
                                }

                                $token = Auth::make_token(trim($form['token']), trim($form['token_backend']));
                                if (O('user', ['token' => $token])->id) {
                                    $form->set_error('token', I18N::T('labs', '您填写的登录帐号在系统中已存在!'));
                                }

                            case 'backend':
                                $form->validate('backend', 'not_empty', I18N::T('labs', '请选择验证后台!'));
                                if ($form['backend'] && !$this->_validate_token_backend($form['backend'])) {
                                    $form->set_error('backend', I18N::T('labs', '验证后台不合法'));
                                }
                                $auth_backends = Config::get('auth.backends');
                                if (!$auth->is_readonly() && $form['backend'] && !$auth_backends[$form['backend']]['readonly']) {
                                    if (!$form['password']) {
                                        $form->validate('password', 'not_empty', I18N::T('labs', '密码不能为空!'));
                                    } else {
                                        $form
                                            ->validate('password', 'compare(==confirm_password)', I18N::T('labs', '两次填写密码不一致!'))
                                            ->validate('password', 'length(8, 24)', I18N::T('labs', '填写的密码不能小于8位, 最长不能大于24位!'));
                                    }
                                }

                                break;
                            case 'passwd':
                                if (!$auth->is_readonly()) {
                                    if (!preg_match('/(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['password'])) {
                                        $form->set_error('password', I18N::T('people', '密码必须包含大写字母、小写字母和数字!'));
                                    }
                                    $form->validate('password', 'length(8,24)', I18N::T('labs', '填写的密码不能小于8位, 最长不能大于24位!'));
                                }
                                break;
                            case 'confirm_passwd':
                                if (!$auth->is_readonly()) {
                                    $form->validate('confirm_password', 'compare(==password)', I18N::T('labs', '请填写有效密码并确保两次填写的密码一致!'));
                                }
                                break;
                            case 'name':
                                $form->validate('name', 'not_empty', I18N::T('labs', '请填写用户姓名!'));
                                break;
                            case 'email':
                                if ($form['email']) {
                                    $form->validate('email', 'is_email', I18N::T('labs', 'Email填写有误!'));
                                    $exist_user = O('user', ['email' => $form['email']]);
                                    if ($exist_user->id && $exist_user->id != $user->id) {
                                        $form->set_error('email', I18N::T('labs', '您填写的电子邮箱在系统中已经存在!'));
                                    }
                                } else {
                                    $form->validate('email', 'not_empty', I18N::T('people', 'Email不能为空!'));
                                }
                                break;
                            case 'phone':
                                $form->validate('phone', 'not_empty', I18N::T('labs', '请填写联系电话!'));
                                break;
                            case 'member_type':
                                if ($form['member_type'] < 0) {
                                    $form->set_error('member_type', I18N::T('labs', '请选择人员类型!'));
                                }

                                break;
                            case 'ref_no':
                                $form->validate('ref_no', 'not_empty', I18N::T('labs', '请填写学号/工号!'));
                                if (trim($form['ref_no'])) {
                                    $ref_user = O('user', ['ref_no' => trim($form['ref_no'])]);
                                    if ($ref_user->id && $ref_user->id != $user->id) {
                                        $form->set_error('ref_no', I18N::T('labs', '您填写的学号/工号在系统中已经存在!'));
                                    }
                                }
                                break;
                            case 'card_no':
                                $card_no_start = Config::get('form.validate.card_no.start', 6);
                                $card_no_end = Config::get('form.validate.card_no.end', 10);
                                $form->validate('card_no', 'not_empty', I18N::T('labs', '请填写IC卡卡号!'));
                                $form->validate('card_no', 'is_numeric', I18N::T('labs', '请填写合法的IC卡卡号!'));
                                $form->validate('card_no', "length({$card_no_start},{$card_no_end})", I18N::T('people', "填写的IC卡卡号不能小于%card_no_start位, 最长不能大于%card_no_end位!", ['%card_no_start' => $card_no_start, '%card_no_end' => $card_no_end]));
                                if ($form['card_no']) {
                                    $exist_user = O('user', ['card_no' => $form['card_no']]);
                                    if ($exist_user->id && $exist_user->id != $user->id) {
                                        $form->set_error('card_no', I18N::T('labs', '您填写的IC卡卡号在系统中已经存在!'));
                                    }
                                }
                                break;
                            case 'time':
                                if ($GLOBALS['preload']['people.enable_member_date']) {
                                    if (!$form['dfrom'] && !$form['dto']) {
                                        $form->set_error('dto', I18N::T('labs', '请填写所在时间!'));
                                    }
                                }
                                break;
                            case 'gender':
                                $form->validate('gender', 'is_numeric', I18N::T('labs', '请选择性别!'));
                                break;
                            case 'organization':
                                $form->validate('organization', 'not_empty', I18N::T('labs', '请填写单位名称!'));
                                break;
                            case 'mentor_name':
                                if (Config::get('people.show_mentor_name', false)) {
                                    $form->validate('mentor_name', 'not_empty', I18N::T('labs', '请填写导师姓名!'));
                                }
                                break;
                            case 'major':
                                $form->validate('major', 'not_empty', I18N::T('labs', '请填写专业!'));
                                break;
                            case 'personal_phone':
                                if (Config::get('people.show_personal_phone', false)) {
                                    $form->validate('personal_phone', 'not_empty', I18N::T('labs', '请填写个人手机!'));
                                }
                                break;
                            case 'address':
                                $form->validate('address', 'not_empty', I18N::T('labs', '请填写地址!'));
                            default:
                                break;
                        }
                    } else {
                        switch ($k) {
                            case 'ref_no':
                                if (trim($form['ref_no'])) {
                                    $ref_user = O('user', ['ref_no' => trim($form['ref_no'])]);
                                    if ($ref_user->id && $ref_user->id != $user->id) {
                                        $form->set_error('ref_no', I18N::T('labs', '您填写的学号/工号在系统中已经存在!'));
                                    }
                                }
                                break;
                            case 'card_no':
                                if ($form['card_no']) {
                                    $exist_user = O('user', ['card_no' => $form['card_no']]);
                                    if ($exist_user->id && $exist_user->id != $user->id) {
                                        $form->set_error('card_no', I18N::T('labs', '您填写的IC卡卡号在系统中已经存在!'));
                                    }
                                }
                                break;
                            case 'backend':

                                if ($form['backend'] && !$this->_validate_token_backend($form['backend'])) {
                                    $form->set_error('backend', I18N::T('labs', '验证后台不合法'));
                                }
                                break;
                            default:
                                break;
                        }
                    }
                });

                //如果为临时使用实验室
                //并且tax_no必填
                //并且没填写tax_no
                //则set_error
                if (Module::is_installed('equipments') && $lab->id == Equipments::default_lab()->id && Config::get('people.temp_user.tax_no.required', false) && !$form['tax_no']) {
                    $form->set_error('tax_no', I18N::T('equipments', '请填写税务登记号'));
                }

                if ($form->no_error) {
                    try {
                        if (O('user', ['email' => $form['email']])->id) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您输入的电子邮箱在系统中已存在！'));
                            throw new Error_Exception;
                        }

                        $form['ref_no'] = trim($form['ref_no']);
                        if ($form['ref_no'] && O('user', ['ref_no' => $form['ref_no']])->id) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您输入的学号/工号在系统中已经存在！'));
                            throw new Error_Exception;
                        }

                        // make auth
                        $auth = new Auth($token);

                        if ($auth->is_creatable()) {
                            if (!$auth->create($form['password'])) {
                                Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '添加新成员失败! 请与系统管理员联系。'));
                                throw new Error_Exception;
                            }
                        }

                        if ($form['must_change_password'] && $form['must_change_password']!='null') {
                            $user->must_change_password = true;
                        }

                        // assignment
                        //设定税务登记号
                        if (Module::is_installed('equipments') && $lab->id == Equipments::default_lab()->id) {
                            $user->tax_no = $form['tax_no'];
                            Event::trigger('equipments.record.create_user_before_save', $user, $form);
                        }

                        $user->token = $token;
                        $user->name = $form['name'];
                        $user->gender = $form['gender'];
                        $user->ref_no = $form['ref_no'] ?: null;
                        $user->member_type = $form['member_type'];
                        $user->organization = $form['organization'];
                        $user->major = $form['major'];
                        $user->email = $form['email'];
                        $user->phone = $form['phone'];
                        $user->address = $form['address'];

                        if ($form['dto'] && $form['dfrom']
                            && $form['dfrom'] > $form['dto']) {
                            list($form['dto'], $form['dfrom']) = [$form['dfrom'], $form['dto']];
                        }

                        if ($form['dfrom']) {
                            $user->dfrom = Date::get_day_start($form['dfrom']);
                        }

                        if ($form['dto']) {
                            $user->dto = Date::get_day_end($form['dto']);
                        }

                        if (Config::get('people.show_mentor_name', false)) {
                            $user->mentor_name = $form['mentor_name'];
                        }
                        if (Config::get('people.show_personal_phone', false)) {
                            $user->personal_phone = $form['personal_phone'];
                        }

                        $user->atime = Config::get('lab.cannot_active') ? 0 : Date::time();
                        if ($me->id == $lab->owner->id && Config::get('lab.cannot_active')) {
                            $user->atime = 0;
                        } else {
                            $user->atime = Date::time();
                        }

                        // add lab and group
                        $user->group = $lab->group;
                        //由于复旦高分子文件系统需要lims用户账号，所以在此存入用户密码，用来和文件系统账号同步
                        if (Module::is_installed('nfs_windows')) {
                            $_SESSION['fs_usertoken']['password'] = $form['password'];
                        }

                        Event::trigger("lab.add_member.{$form['add_from']}.submit", $user, $form);
                        if ($user->save()) {
                            //用户保存成功，则关联课题组
                            $user->connect($lab);
                            $user->group->connect($user);

                            Log::add(strtr('[labs] %user_name[%user_id]添加了实验室%lab_name[%lab_id]的成员%member_name[%member_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%lab_name' => $lab->name, '%lab_id' => $lab->id, '%member_name' => $user->name, '%member_id' => $user->id]), 'journal');
                        } else {
                            $auth->is_creatable() and $auth->remove(); //添加新成员失败，去掉已添加的 token
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '添加新成员失败! 请与系统管理员联系。'));
                            throw new Error_Exception;
                        }

                        $arr_user_token = explode('|', $user->token);
                        $token_backend_title = $auth_backends[$arr_user_token['1']]['title'];
                        $user_token = implode(' | ', [$arr_user_token['0'], $token_backend_title]);

                        Notification::send('people.add', $user, [
                            '%login' => $user_token,
                            '%user' => H($user->name),
                            '%password' => $form['password'],
                        ]);

                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '新成员已添加。'));

                        Log::add(strtr('[Labs] %user_name[%user_id] 添加 %member_name[%member_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%member_name' => $user->name, '%member_id' => $user->id]), 'admin');

                        URI::redirect($lab->url());
                    } catch (Error_Exception $e) {
                    }
                }
            } else {
                Event::trigger("lab.add_member.{$form['add_from']}", $lab, $form);
            }
        }

        $tabs->content = V('lab/add_member', [
            'lab' => $lab,
            'form' => $form,
        ]);
    }

    private function _validate_token_backend($backend)
    {


        $backends = Config::get('auth.backends');
        return in_array(trim($backend), array_keys($backends));
    }

    public function _index_members_content($e, $tabs)
    {
        $me = L('ME');
        $lab = $tabs->lab;
        $form = Lab::form();

        $sort_by = $form['sort'];
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        $selector = "$tabs->lab user";
        if (!$me->is_allowed_to('管理', $lab)) {
            $selector .= '[!hidden]';
        }

        if ($form['name']) {
            $name = Q::quote(trim($form['name']));
            $selector .= "[name*=$name|name_abbr*=$name]";
        }
        if ($form['active_status']) {
            if((int)$form['active_status'] == 1){
                $selector .= '[atime]';
            }
            if((int)$form['active_status'] == 2){
                $selector .= '[!atime]';
            }
        }
        if ($form['email']) {
            $email = Q::quote(trim($form['email']));
            $selector .= "[email*=$email]";
        }
        if ($form['phone']) {
            $phone = Q::quote(trim($form['phone']));
            $selector .= "[phone*=$phone]";
        }
        if ($form['address']) {
            $address = Q::quote(trim($form['address']));
            $selector .= "[address*=$address]";
        }

        $query = $form['query'];
        if ($query) {
            $selector .= '[name*=' . Q::quote($query) . ']';
        }

        switch ($sort_by) {
            case 'name':
                $selector .= ":sort(name_abbr $sort_flag)";
                break;
            case 'date':
                $selector .= ":sort(dto $sort_flag)";
                break;
            default:
                break;
        }

        $users = Q($selector);

        $pagination = Lab::pagination($users, $form['st'], 30);

        $tabs->content = V('lab/index.members', [
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
            'form' => $form,
            'users' => $users,
            'lab' => $lab,
            'pagination' => $pagination,
        ]);
//        $lab = $tabs->lab;
//
//        $content = V('labs:lab/members_view');
//
//        $content->secondary_tabs = Widget::factory('tabs');
//
//        Event::bind('lab.members.content', [$this, '_index_current_content'], 0, 'current');
//        Event::bind('lab.members.content', [$this, '_index_noactive_content'], 0, 'noactive');
//
//        $content->secondary_tabs
//            ->set('class', 'third_tabs')
//            ->set('lab', $lab)
//            ->tab_event('lab.members.tab')
//            ->content_event('lab.members.content')
//            ->add_tab('current', [
//                'title' => I18N::T('labs', '目前成员'),
//                'url' => $lab->url('members.current'),
//            ])
//            ->add_tab('noactive', [
//                'title' => I18N::T('labs', '未激活成员'),
//                'url' => $lab->url('members.noactive'),
//            ])
//            ->select($tabs->account_type);
//
//        $tabs->content = $content;
    }

    public function _index_current_content($e, $tabs)
    {

        $me = L('ME');
        $lab = $tabs->lab;
        $form = Lab::form();

        $sort_by = $form['sort'];
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        $selector = "$tabs->lab user[atime]";
        if (!$me->is_allowed_to('管理', $lab)) {
            $selector .= '[!hidden]';
        }

        if ($form['name']) {
            $name = Q::quote(trim($form['name']));
            $selector .= "[name*=$name|name_abbr*=$name]";
        }
        if ($form['email']) {
            $email = Q::quote(trim($form['email']));
            $selector .= "[email*=$email]";
        }
        if ($form['phone']) {
            $phone = Q::quote(trim($form['phone']));
            $selector .= "[phone*=$phone]";
        }
        if ($form['address']) {
            $address = Q::quote(trim($form['address']));
            $selector .= "[address*=$address]";
        }

        $query = $form['query'];
        if ($query) {
            $selector .= '[name*=' . Q::quote($query) . ']';
        }

        switch ($sort_by) {
            case 'name':
                $selector .= ":sort(name_abbr $sort_flag)";
                break;
            case 'date':
                $selector .= ":sort(dto $sort_flag)";
                break;
            default:
                break;
        }

        $users = Q($selector);

        $pagination = Lab::pagination($users, $form['st'], 30);

        $tabs->content = V('lab/index.members', [
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
            'form' => $form,
            'users' => $users,
            'lab' => $lab,
            'pagination' => $pagination,
        ]);
    }

    public function _index_noactive_content($e, $tabs)
    {

        $lab = $tabs->lab;
        $form = Lab::form();

        $sort_by = $form['sort'];
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        $selector = "$lab user[!hidden][!atime]";

        if ($form['name']) {
            $name = Q::quote($form['name']);
            $selector .= "[name*=$name]";
        }
        if ($form['email']) {
            $email = Q::quote($form['email']);
            $selector .= "[email*=$email]";
        }
        if ($form['phone']) {
            $phone = Q::quote($form['phone']);
            $selector .= "[phone*=$phone]";
        }
        if ($form['address']) {
            $address = Q::quote($form['address']);
            $selector .= "[address*=$address]";
        }

        switch ($sort_by) {
            case 'name':
                $selector .= ":sort(name_abbr $sort_flag)";
                break;
            case 'date':
                $selector .= ":sort(dto $sort_flag)";
                break;
            default:
                break;
        }

        $users = Q($selector);

        $pagination = Lab::pagination($users, $form['st'], 30);

        $tabs->content = V('lab/index.members.noactive', [
            'sort_by' => $sort_by,
            'sort_asc' => $sort_asc,
            'form' => $form,
            'lab' => $lab,
            'pagination' => $pagination,
            'users' => $users,
        ]);
    }

    public function _index_members_tool($e, $tabs)
    {
        $me = L('ME');
        $sort_fields = Config::get('labs.people.sortable_columns');
        $lab = $tabs->lab;
        $form = Lab::form();

        if ($me->is_allowed_to('添加成员', $lab) && $lab->name != Config::get('people.yiqikong_lab_name')) {
            $panel_buttons = new ArrayIterator;
            if (!People::perm_in_uno()){
                $panel_buttons[] = [
                    'url' => $lab->url(),
                    'text' => I18N::T('labs', '添加成员'),
                    'tip' => I18N::T('labs', '添加成员'),
                    'extra' => 'class="button button_add " q-event="click" q-object="add_lab_member"' .
                        ' q-src="' . $lab->url()
                        .'"',
                ];
            }
        }

        $fields = [
            /* 'avatar' => [
                'title' => I18N::T('labs', '头像'),
            ], */
            'name' => [
                'title' => I18N::T('labs', '姓名'),
                'filter' => [
                    'form' => V('labs:users_table/filters/name', ['name' => $form['name']]),
                    'value' => $form['name'] ? H($form['name']) : null,
                ],
                'nowrap' => true,
                'sortable' => in_array('name', $sort_fields),
            ],
            'contact_info' => [
                'title' => I18N::T('labs', '联系方式'),
                'nowrap' => true,
            ],
        ];

        if ($me->is_allowed_to('查看联系方式', 'user')) {
            $table_add_column = [
                'email' => [
                    'title' => I18N::T('labs', '邮箱'),
                    'filter' => [
                        'form' => V('labs:users_table/filters/email', ['email' => $form['email']]),
                        'value' => $form['email'] ? H($form['email']) : null,
                    ],
                    'nowrap' => true,
                    'invisible' => true,
                ],
                'phone' => [
                    'title' => I18N::T('labs', '联系电话'),
                    'filter' => [
                        'form' => V('labs:users_table/filters/phone', ['phone' => $form['phone']]),
                        'value' => $form['phone'] ? H($form['phone']) : null,
                    ],
                    'nowrap' => true,
                    'invisible' => true,
                ],
            ];
            $result = array_merge($fields, $table_add_column);
        }

        if ($GLOBALS['preload']['people.enable_member_date']) {
            $table_add_date = [
                'date' => [
                    'title' => I18N::T('labs', '所在时间'),
                    'nowrap' => true,
                    'sortable' => in_array('date', $sort_fields),
                ],
            ];
            $result = array_merge($result ? : $fields, $table_add_date);
        }

        $table_column = [
            'address' => [
                'title' => I18N::T('labs', '地址'),
                'filter' => [
                    'form' => V('labs:users_table/filters/address', ['address' => $form['address']]),
                    'value' => $form['address'] ? H($form['address']) : null,
                ],
                'nowrap' => true,
            ],
            'rest' => [
                'title' => I18N::T('labs', '操作'),
                'align' => 'left',
                'nowrap' => true,
            ],
            'active_status' => [
                'title' => I18N::T('labs', '激活状态'),
                'invisible' => true,
                'filter'    => [
                    'form'  => V('labs:users_table/filters/active_status', ['active_status' => $form['active_status']]),
                    'value' => $form['active_status'] ? H($form['active_status']) : null,
                ]
            ],
        ];
        $columns = array_merge($result ? : $fields, $table_column);

        $tabs->search_box = V('application:search_box', ['is_offset' => true, 'top_input_arr' => ['name'], 'columns' => $columns, 'panel_buttons' => $panel_buttons]);
        $tabs->field = $columns;
    }

    private function _transaction_delete($transaction)
    {
        if ($transaction->status == Transaction_Model::STATUS_PENDING) {
            $transaction->delete();
        }
    }

    public function edit($id = 0, $tab = 'info')
    {

        $lab = O('lab', $id);
        $me = L('ME');

        if (!$lab->id) {
            URI::redirect('error/404');
        }

        if ($me->is_allowed_to('修改', $lab)) {
            $has_edit_perm = true;
        }

        if (!$has_edit_perm) {
            URI::redirect('error/401');
        }

        if (!People::perm_in_uno()){
            Event::bind('lab.edit.content', [$this, '_edit_info'], 0, 'info');
            Event::bind('lab.edit.content', [$this, '_edit_photo'], 0, 'photo');
        }

        Event::bind('lab.edit.content', [$this, '_edit_notifications'], 0, 'notifications');

        Event::bind('lab.edit.content', [$this, '_edit_projects'], 0, 'project');

        if ($has_edit_perm) {
            $this->layout->body->primary_tabs
                = Widget::factory('tabs');
            if (!People::perm_in_uno()) {
                $this->layout->body->primary_tabs->add_tab('info', [
                    'url' => $lab->url('info', null, null, 'edit'),
                    'title' => I18N::T('labs', '基本信息'),
                ]);
            }
            $this->layout->body->primary_tabs->add_tab('project', [
                'url' => $lab->url('project', '', '', 'edit'),
                'title' => I18N::T('labs', '项目管理'),
            ])->add_tab('notifications', [
                'url' => $lab->url('notifications', '', '', 'edit'),
                'title' => I18N::T('labs', '消息提醒'),
            ]);
        }

        $this->layout->title = H($lab->name);
        $breadcrumbs = [
            [
                'url' => '!labs/index',
                'title' => I18N::T('equipments', '课题组'),
            ],
            [
                'url' => $lab->url(),
                'title' => $lab->name,
            ],
            [
                'title' => '修改',
            ],
        ];
        $this->layout->breadcrumb = V('application:breadcrumbs', ["breadcrumbs" => $breadcrumbs]);
        $this->layout->body->primary_tabs
            ->set('lab', $lab)
            ->tab_event('lab.edit.tab')
            ->content_event('lab.edit.content');
        Event::trigger('lab.edit.secondary_tabs', $this->layout->body->primary_tabs);
        $this->layout->body->primary_tabs->select($tab);
    }

    public function _edit_notifications($e, $tabs)
    {
        $sections = new ArrayIterator;
        Event::trigger('lab.notifications.edit', $tabs->lab, $sections);
        $tabs->content = V('lab/edit.notifications', ['sections' => $sections, 'form' => $form]);
    }

    public function _edit_projects($e, $tabs)
    {

        $lab = $tabs->lab;
        $status = Lab_Project_Model::STATUS_ACTIVED;
        $projects = Q("lab_project[lab=$lab][status={$status}]");

        $form = Form::filter(Input::form());

        if ($form['submit']) {

            Event::trigger('lab.project.format', $form);

            Event::trigger('lab.project.validate', $form);
            if ($form->no_error) {
                $project_forms = (array)Input::form()['project'];

                $ids_remove = $projects->to_assoc('id', 'id');

            //相同name
            $same_names = [];
            $has_error = false;

            foreach ($project_forms as $type => $sub_project_forms) {

                    $names = [];

                foreach ($sub_project_forms as $formid => $project_form) {

                    if ($project_form['name']) {
                        //填写了名称后, 对名称进行判断
                        //如果已存在相同的name
                        if (in_array($project_form['name'], (array) $names[$type])) {
                            $same_names[$type][] = [
                                'formid'=> $formid,
                                'name'=> $project_form['name'],
                            ];
                            $has_error = true;
                            continue;
                        }

                            $names[$type][] = $project_form['name'];

                            //如果无错误
                            if (!$has_error) {

                                $id = $project_form['id'];

                            $project = $projects[$id];
                            if ($project) {
                                unset($ids_remove[$project->id]); //根据传入的ID值逐一从｛$ids_remove要删除的集合对象｝中消除键值
                            } else {
                                $project = O('lab_project');
                                $project->lab = $lab;
                            }

                                $project->type = (int) $type;
                                $project->name = $project_form['name'];
                                $project->dtstart = min($project_form['dtstart'], $project_form['dtend']) ? : 0;
                                $project->dtend = max($project_form['dtstart'], $project_form['dtend']) ? : 0;

                            $project->ptype = $project_form['ptype'];
                            $project->grant = $project_form['grant']; //经费
                            $project->incharge = $project_form['incharge']; //负责人

                                $project->textbook = $project_form['textbook'];

                                $project->student_count = (int) $project_form['student_count'];
                                $project->book_type = $project_form['book_type'];
                                $project->description = $project_form['description'];

                                Event::trigger('lab.project.extra.fields', $project, $project_form);

                                $project->save();

                                if (!isset($projects[$project->id])) {
                                    $projects->append($project);
                                }
                            }
                        }
                    }
                }

            if ($has_error) {
                foreach ($same_names as $type => $value) {
                    foreach ($value as $k => $v) {
                        $foo = $v['formid'];
                        $form->set_error("project[$type][$foo][name]", null);

                        //获取对应的相同的name的
                        //统一set_error
                        $name = $v['name'];
                        foreach ($form["project"][$type] as $id => $bar) {
                            if ($bar['name'] == $name) {
                                $form->set_error("project[$type][$id][name]", null);
                            }
                        }
                    }
                    $form->set_error(NULL, I18N::T('labs', '同一类项目的名称不允许相同!'));
                }
                $form->set_error(null, I18N::T('labs', '同一类项目的名称不允许相同!'));
            } else {
                if ($ids_remove) { //根据传入的$ids_remove要删除的集合对象id来逐一删除

                        foreach ($ids_remove as $id) {
                            unset($projects[$id]);
                        }

                        $ids_format = Event::trigger('lab.project.delete', $ids_remove);
                        if (is_array($ids_format)) {
                            $ids_remove = $ids_format;
                        }

                        $ids = implode(',', $ids_remove);
                        Q("lab_project[id=$ids]")->delete_all();
                    }

                    /* 记录日志 */
                    $me = L('ME');

                Log::add(strtr('[labs] %user_name[%user_id]修改了实验室%lab_name[%lab_id]的项目信息', ['%user_name' => $me->name, '%user_id' => $me->id, '%lab_name' => $lab->name, '%lab_id' => $lab->id]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '实验室项目更新成功！'));
                }
            }
        }

        $projects = Q("lab_project[lab=$lab][status={$status}]");
        $tabs->content = V('lab/edit.projects', ['projects'=>$projects, 'form'=> $form]);
    }

    public function delete($id = 0)
    {

        $lab = O('lab', $id);
        /*
        NO.TASK#274(guoping.zhang@2010.11.26)
        应用权限判断新规则
         */
        if (!L('ME')->is_allowed_to('删除', $lab) || !$lab->id) {
            URI::redirect('error/401');
        }
        $user = $lab->owner;

        if ($lab->delete()) {
            if ($user->id) {
                $user->disconnect($lab, 'pi');
                $user->disconnect($lab);
            }
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '实验室删除成功！'));
        } else {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室删除失败！'));
        }
        URI::redirect('!labs');
    }

    public function delete_photo($id = 0)
    {
        $lab = O('lab', $id);
        /*
        NO.TASK#274(guoping.zhang@2010.11.26)
        应用权限判断新规则
         */
        if (!L('ME')->is_allowed_to('修改', $lab)) {
            URI::redirect('error/401');
        }
        if (!$lab->id) {
            URI::redirect('error/401');
        }

        $lab->delete_icon();
        $me = L('ME');

        Log::add(strtr('[labs] %user_name[%user_id]修改了实验室%lab_name[%lab_id]的图标', ['%user_name' => $me->name, '%user_id' => $me->id, '%lab_name' => $lab->name, '%lab_id' => $lab->id]), 'journal');

        URI::redirect('!labs/lab/edit.' . $lab->id . '.photo');
    }

    public function add()
    {
        $me = L('ME');
        /*
        NO.TASK#274(guoping.zhang@2010.11.26)
        应用权限判断新规则
         */
        if (!$me->is_allowed_to('添加', 'lab')) {
            URI::redirect('error/401');
        }

        $this->layout->body->primary_tabs
            ->add_tab('add', [
                'url' => URI::url('!labs/lab/add'),
                'title' => I18N::T('labs', '添加实验室'),
            ]);

        $lab = O('lab');

        $group_root = Tag_Model::root('group');
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        /*
        NO.BUG#118（guoping.zhang@2010.11.11)
        当没输入密码时，提示密码不能为空；
        输入了密码，没密码确认时，提示两次输入密码不一致；

        NO.BUG#111（guoping.zhang@2010.11.12)
        用户账户密码长度的限制（最小不小于6位，最长不大于24位）
         */
        if (Input::form('submit')) {
            $form = Form::filter(Input::form())
                ->validate('lab_name', 'not_empty', I18N::T('labs', '请填写实验室名称！'))
                ->validate('lab_contact', 'not_empty', I18N::T('labs', '请填写实验室联系电话！'));
            if ($form['owner_get'] == 'create') {
                $form->validate('name', 'not_empty', I18N::T('people', '请填写用户姓名!'))
                    ->validate('email', 'is_email', I18N::T('people', '电子邮箱输入出错！'))
                    ->validate('token', 'is_token', I18N::T('people', '请填写符合规则的用户帐号!'))
                    ->validate('phone', 'not_empty', I18N::T('people', '请填写用户联系方式！'))
                    ->validate('password', 'not_empty', I18N::T('people', '密码不能为空！'))
                    ->validate('password', 'compare(==confirm_password)', I18N::T('people', '两次输入密码不一致！'))
                    ->validate('password', 'length(8, 24)', I18N::T('people', '输入的密码不能小于8位，最长24位！'));

                if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['password'])) {
                    $form->set_error('password', I18N::T('people', '密码必须包含大写字母、小写字母和数字!'));
                }

                if (User_Model::is_reserved_token($form['token'])) {
                    $form->set_error('token', I18N::T('people', '您填写的帐号已被管理员保留。'));
                }
            } else {
                // 如果为空，提示‘实验室负责人不能为空’，如果不为空再判定是否可添加
                if ($form['owner_id']) {
                    $form->validate('owner_id', 'is_numeric', I18N::T('labs', '实验室负责人添加失败！'));
                } else {
                    $form->set_error('owner_id', I18N::T('labs', '实验室负责人不能为空，请选择负责人！'));
                }

                $labname_check = Event::trigger('fudan_gao.check_lab_name', $form['lab_name'], 0);
                if ($labname_check) {
                    if ($labname_check > 0) {
                        $form->set_error('lab_name', I18N::T('labs', '课题组有重名，请重新填写！'));
                    }
                }

            }
            if ($form['util_area'] && !is_numeric($form['util_area'])) {
                $form->set_error('util_area', I18N::T('labs', '实验室使用面积输入有误！'));
            }

            Event::trigger('lab.form.validate', $lab, 'add', $form);

            if ($form->no_error) {
                $create_pi_role = $form['owner_get'] == 'create';
                if ($create_pi_role) {
                    $owner = O('user');
                    try {
                        $backends = Config::get('auth.backends');
                        $default_backend = Config::get('auth.default_backend');
                        //如果默认添加协议存在，并且允许添加。则使用默认添加。否则添加至database
                        if ($backends["$default_backend"]['handler'] && $backends["$default"]['allow_create']) {
                            $backend = $default_bakcend;
                        } else {
                            $backend = 'database';
                        }
                        $token = Auth::normalize($form['token'], $backend);
                        /* 添加实验室时，新建PI不容许选backend */

                        if (User_Model::is_reserved_token($token)) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室负责人的登录帐号已被保留。'));
                            throw new Error_Exception;
                        }

                        if (O('user', ['token' => $token])->id) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室负责人的登录帐号在系统中已存在！'));
                            throw new Error_Exception;
                        }

                        if (O('user', ['email' => $form['email']])->id) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室负责人的电子邮箱在系统中已存在！'));
                            throw new Error_Exception;
                        }

                        $auth = new Auth($token);

                        if (!$auth->create($form['password'])) {
                            throw new Error_Exception;
                        }

                        $owner->token = $token;
                        $owner->name = $form['name'];
                        $owner->email = $form['email'];
                        $owner->phone = $form['phone'];
                        $owner->ref_no = null;

                        if ($form['must_change_password'] && $form['must_change_password']!='null') {
                            $owner->must_change_password = true;
                        }

//                        $owner->atime = Date::time();

                        //由于复旦高分子文件系统需要lims用户账号，所以在此存入用户密码，用来和文件系统账号同步
                        if (Module::is_installed('nfs_windows')) {
                            $_SESSION['fs_usertoken']['password'] = $form['password'];
                        }

                        if ($owner->save()) {
                            Log::add(strtr('[labs] %user_name[%user_id]添加实验室时添加了用户%member_name[%member_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%member_name' => $owner->name, '%member_id' => $owner->id]), 'journal');
                        } else {
                            $auth->remove(); //添加新成员失败，去掉已添加的 token
                            throw new Error_Exception;
                        }

                        $owner_token = T('%token|%backend', ['%token' => $form['token'], '%backend' => $backends[$backend]['title']]);

                        Notification::send('people.add', $owner, [
                            '%login' => $owner_token,
                            '%user' => $owner->name,
                            '%password' => $form['password'],
                        ]);
                    } catch (Error_Exception $e) {
                    }
                } else {
                    $owner = O('user', $form['owner_id']);
                }

                if (!$owner->id) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室负责人添加失败!'));
                } else {
                    if ($me->is_allowed_to('激活', $lab)) {
                        $lab->atime = $form['activate'] ? Date::time() : 0;
                    }
                    $lab->owner = $owner;
                    $lab->name = $form['lab_name'];
                    $lab->contact = $form['lab_contact'];

                    /*
                     * BUG #1072::实验室没有“楼”，但有“房间号”
                     * 解决：删除原来的room属性，新增location和location2分别存储“楼层”和“房间号“ (kai.wu@2011.08.31)
                     */
                    $lab->location = $form['location'];
                    $lab->location2 = $form['location2'];
                    $lab->description = $form['description'];
                    $lab->type = $form['type'];
                    $lab->ref_no = trim($form['ref_no']);
                    $lab->util_area = $form['util_area'];
                    $lab->subject = $form['subject'];
                    $lab->creator = $me;
                    if ($me->is_allowed_to('修改组织机构', 'lab')) {
                        $group = O('tag', $form['group_id']);
                        if ($me->group->id
                            && !$me->is_allowed_to('修改组织机构', 'lab', ['@ignore' => '修改下属机构实验室'])
                            && !$me->group->is_itself_or_ancestor_of($group)) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '您设置的组织机构必须是您下属机构！'));
                            URI::redirect(URI::url(''));
                        }
                        if ($group->root->id == $group_root->id) {
                            $lab->group = $group;
                        }
                    }

                    $lab->save();

                    if ($lab->id) {

                        if ($group->root->id == $group_root->id) {
                            $group_root->disconnect($lab);
                            $group->connect($lab);
                        }

                        if (!$create_pi_role) {
                            if ($multi_lab) {
                                $owner->connect($lab);
                                $owner->connect($lab, 'pi');
                                Event::trigger('lab.multi_lab.add_member', $lab, $owner);
                            } else {
                                $roles = [];
                                foreach ($owner->roles() as $r) {
                                    if ($r <= 0) {
                                        continue;
                                    }
                                    $roles[] = $r;
                                }

                                $new_user = $owner->replacement();
                                $owner->remove_unique()->save();
                                if (!$owner->group->id) {
                                    $new_user->group = $lab->group;
                                }
                                if ($new_user->save()) {
                                    $new_user->connect($lab);
                                    if (count($roles)) {
                                        $new_user->connect(['role', $roles]);
                                    }
                                    $owner->group->connect($new_user);
                                    $owner->move_img_to($new_user);
                                    $lab->owner = $new_user;
                                    $lab->save();
                                }
                            }
                        } else {
                            if (!$owner->group->id) {
                                $owner->group = $lab->group;
                            }
                            $owner->save();
                            $owner->connect($lab);
                            $owner->connect($lab, 'pi');
                        }

                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '实验室添加成功!'));
                        URI::redirect($lab->url(null, null, null, 'edit'));
                    } else {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室添加失败! 请与系统管理员联系。'));
                    }
                }
            }
        }

        $this->layout->form = $form;
        $this->layout->body->primary_tabs
            ->select('add')
            ->set('content', V('lab/add',
                ['lab' => $lab, 'form' => $form, 'group_root' => $group_root, 'group' => $group]));

        JS::dialog(V('lab/add', ['lab' => $lab, 'form' => $form, 'group_root' => $group_root, 'group' => $group]), [
            'title' => I18N::T('labs', '添加课题组'),
        ]);
    }

    public function _edit_info($e, $tabs)
    { //负责人只能修改实验室说明
        $lab = $tabs->lab;

        $group_root = Tag_Model::root('group');

        // 2018-11-21 Clh 更新设备图标
        if (Input::form('submit') == '上传图标') {
            $this->_edit_photo($e, $tabs);
            return;
        }

        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        if (Input::form('submit')) {

            $form = Form::filter(Input::form());
            /*
            NO.TASK#274(guoping.zhang@2010.11.26)
            应用权限判断新规则
             */
            $me = L('ME');
            if ($me->is_allowed_to('修改', $lab)) {
                //管理实验室 => 添加/修改实验室
                $form->validate('name', 'not_empty', I18N::T('labs', '请填写实验室名称！'))
                    ->validate('lab_contact', 'not_empty', I18N::T('labs', '请填写实验室联系方式!'))
                    ->validate('owner_id', 'compare(>0)', I18N::T('labs', '请填写实验室负责人!'));

                $requires = Config::get('form.lab_signup')['requires'];
                $group = O('tag_group', (int) Input::form()['group_id']);
                if ($requires['group_id'] && (!$group->id || $group->root->id != $group_root->id)) {
                    $form->set_error('group_id', I18N::T('people', '请选择组织机构!'));
                }

                $user = O('user', $form['owner_id']);

                Event::trigger('lab.info.edit', $form, $lab);

                $labname_check = Event::trigger('fudan_gao.check_lab_name', $form['name'], $lab->id);
                if ($labname_check) {
                    if ($labname_check > 0) {
                        $form->set_error('lab_name', I18N::T('labs', '课题组有重名，请重新填写！'));
                    }
                }

                if ($form['owner_id'] && !$user->id) {
                    $form->set_error('owner_id', I18N::T('labs', '负责人不存在, 请重新填写！'));
                }

                Event::trigger('lab.form.validate', $lab, 'edit', $form);

                if ($form->no_error) {
                    $lab->name = $form['name'];

                    /*
                     * BUG #1072::实验室没有“楼”，但有“房间号”
                     * 解决：删除原来的room属性，新增location和location2分别存储“楼层”和“房间号“ (kai.wu@2011.08.31)
                     */
                    $lab->location = $form['location'];
                    $lab->location2 = $form['location2'];
                    $lab->type = $form['type'];
                    $lab->ref_no = trim($form['ref_no']);
                    $lab->util_area = $form['util_area'];
                    $lab->subject = $form['subject'];
                    $lab->contact = $form['lab_contact'];
                    isset($form['secretary_id']) ? $lab->secretary = O('user', $form['secretary_id']) : '';//pi助理，可能会通用，所以加到通用代码

                    if ($user->id != $lab->owner->id) {
                        $pi_changed = true;
                    }

                    // 单课题组，变换已存在人员的课题组时，需拷贝新生成一个人员，将旧人员作废
                    if (!$multi_lab && $user->id && !Q("$user $lab")->total_count()) {
                        $roles = [];
                        foreach ($user->roles() as $r) {
                            if ($r <= 0) {
                                continue;
                            }

                            $roles[] = $r;
                        }

                        $new_user = $user->replacement();
                        $user->remove_unique()->save();

                        if ($new_user->save()) {
                            $user->disconnect($lab);
                            $user->disconnect($lab, 'pi');
                            $new_user->connect($lab);
                            $new_user->connect($lab, 'pi');
                            if (count($roles)) {
                                $new_user->connect(['role', $roles]);
                            }

                            $new_user->group->connect($new_user, 'pi');
                            $user->move_img_to($new_user);
                        }

                        $user = $new_user;
                    }
                    // 多课题组，变换已存在人员的课题组时，disconnect旧PI，connect新PI
                    else {
                        $owner = $lab->owner;
                        $owner->disconnect($lab, 'pi');
                        if (!Q("$user $lab")->total_count()) $user->connect($lab);
                        $user->connect($lab, 'pi');
                    }

                    $lab->owner = $user;
                    if (!Q("$user $lab")->total_count()) $user->connect($lab);
                    if (!Q("$user<pi $lab")->total_count()) $user->connect($lab, 'pi');

                    if ($me->is_allowed_to('修改组织机构', $lab)) {
                        $group = O('tag_group', $form['group_id']);
                        if ($me->group->id
                            && !$me->is_allowed_to('修改组织机构', $lab, ['@ignore' => '修改下属机构实验室'])
                            && !$me->group->is_itself_or_ancestor_of($group)) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '您设置的组织机构必须是您下属机构！'));
                            URI::redirect(URI::url(''));
                        }
                        $group_root->disconnect($lab);
                        $lab->group = null;
                        if ($group->root->id == $group_root->id) {
                            $group_root->disconnect($lab);
                            $group->connect($lab);
                            $lab->group = $group;
                        }
                    }
                    /*
                cheng.liu@2011.2.24
                移除用户标签功能，之前已被删除
                 */
                }
            }

            if ($form->no_error) {
                //管理实验室 => 添加/修改实验室
                $lab->description = trim($form['description']);
                if ($me->is_allowed_to('激活', $lab)) {
                    $lab->atime = $form['activate'] ? Date::time() : 0;
                }
                Event::trigger('lab.edit.post_submit', $lab, 'edit', $form);
                if ($lab->save()) {
                    Log::add(strtr('[labs] %user_name[%user_id]修改了实验室%lab_name[%lab_id]的基本信息', ['%user_name' => $me->name, '%user_id' => $me->id, '%lab_name' => $lab->name, '%lab_id' => $lab->id]), 'journal');

                    if ($pi_changed) {
                        Log::add(strtr('[labs] %user_name[%user_id]修改了实验室%lab_name[%lab_id]的pi', ['%user_name' => $me->name, '%user_id' => $me->id, '%lab_name' => $lab->name, '%lab_id' => $lab->id]), 'journal');
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '实验室信息已更新'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室信息更新失败'));
                }
            }
        }

        $extra_view = Event::trigger('lab.edit.extra_view', $lab, $form);
        $tabs->content = V('lab/edit.info', ['group_root'=>$group_root, 'form'=>$form, 'lab'=>$lab, 'extra_view'=>$extra_view]);
        // $tabs->content .= Event::trigger('db_sync.slave_disable_input', Controller::$CURRENT, ['tag']);
    }

    public function _edit_photo($e, $tabs)
    {
        $lab = $tabs->lab;

        if (Input::form('submit')) {
            $file = Input::file('file');
            if ($file['tmp_name']) {
                try {
                    $ext = File::extension($file['name']);
                    $image = Image::load($file['tmp_name'], $ext);
                    $lab->save_icon($image);
                    $me = L('ME');
                    Log::add(strtr('[labs] %user_name[%user_id]修改了实验室%lab_name[%lab_id]的图标', ['%user_name' => $me->name, '%user_id' => $me->id, '%lab_name' => $lab->name, '%lab_id' => $lab->id]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '实验室图标已更新'));
                } catch (Error_Exception $e) {

                    /* BUG #1023::实验室更新图标，如果上传非图片文件，错误提示的背景应该为红色(kai.wu@2011.08.22) */
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室图标更新失败!'));
                }
            } else {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '请选择您要上传的实验室图标文件!'));
            }
        }
        $tabs->content = V('lab/edit.photo');
    }
}

class Lab_AJAX_Controller extends AJAX_Controller
{
    private function _validate_token_backend($backend)
    {
        $backends = Config::get('auth.backends');
        return in_array(trim($backend), array_keys($backends));
    }

    public function index_add_lab_click()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('添加', 'lab')) {
            URI::redirect('error/401');
        }

        $lab = O('lab');

        $form = Form::filter(Input::form());
        $group_root = Tag_Model::root('group');
        $group = O('tag', $form['group_id']);
        $group_root = Tag_Model::root('group');
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        /*
        NO.BUG#118（guoping.zhang@2010.11.11)
        当没输入密码时，提示密码不能为空；
        输入了密码，没密码确认时，提示两次输入密码不一致；

        NO.BUG#111（guoping.zhang@2010.11.12)
        用户账户密码长度的限制（最小不小于6位，最长不大于24位）
         */

        JS::dialog(V('lab/add', ['lab' => $lab, 'form' => $form, 'group_root' => $group_root, 'group' => $group]), [
            'title' => I18N::T('labs', '添加课题组'),
        ]);
    }

    public function index_add_lab_submit()
    {
        $me = L('ME');

        if (!$me->is_allowed_to('添加', 'lab')) {
            URI::redirect('error/401');
        }
        $lab = O('lab');

        $group_root = Tag_Model::root('group');
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        /*
        NO.BUG#118（guoping.zhang@2010.11.11)
        当没输入密码时，提示密码不能为空；
        输入了密码，没密码确认时，提示两次输入密码不一致；

        NO.BUG#111（guoping.zhang@2010.11.12)
        用户账户密码长度的限制（最小不小于6位，最长不大于24位）
         */

        if (Input::form('submit')) {
            $requires = Config::get('form.lab_signup')['requires'];
            $group_root = Tag_Model::root('group');
            $group = O('tag', (int) Input::form()['group_id']);
            
            $form = Form::filter(Input::form())
                    ->validate('lab_name', 'not_empty', I18N::T('labs', '请填写实验室名称！'))
                    ->validate('lab_contact', 'not_empty', I18N::T('labs', '请填写实验室联系电话！'));
                
            /**
             * @todo
             * 额外的扩展条件，就不用下面lab.form.validate这个trigger了
             * 应该把这个地方优化成array_walk requires，与注册项相同
             */
            if ($requires['group_id'] && (!$group->id || $group->root->id != $group_root->id)) {
                $form->set_error('group_id', I18N::T('people', '请选择组织机构!'));
            }
            
            if ($form['owner_get'] == 'create') {
                $form->validate('name', 'not_empty', I18N::T('people', '请填写用户姓名!'))
                    ->validate('email', 'is_email', I18N::T('people', '电子邮箱输入出错！'))
                    ->validate('token', 'is_token', I18N::T('people', '请填写符合规则的用户帐号!'))
                    ->validate('phone', 'not_empty', I18N::T('people', '请填写用户联系方式！'))
                    ->validate('backend', 'not_empty', I18N::T('people', '请选择账号类型！'));
                
                $backend = trim($form['backend']) ? : 'database';

                // 本地用户需要输入密码
                if ($backend == 'database') {
                    $form->validate('password', 'not_empty', I18N::T('people', '密码不能为空！'))
                        ->validate('password', 'length(8, 24)', I18N::T('people', '输入的密码不能小于8位，最长24位！'))
                        ->validate('confirm_password', 'compare(==password)', I18N::T('people', '两次输入密码不一致！'));

                    if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['password'])) {
                        $form->set_error('password', I18N::T('people', '密码必须包含大写字母、小写字母和数字!'));
                    }
                }

                if ($form['member_type'] == -1) {
                    $form->set_error('member_type', I18N::T('people', '请填写人员类型!'));
                }

                if (User_Model::is_reserved_token($form['token'])) {
                    $form->set_error('token', I18N::T('people', '您填写的帐号已被管理员保留。'));
                }

                $search_token = "{$form['token']}|{$backend}";
                $search_token_res = Database::factory()->query("SELECT count(1) as cn FROM `user` WHERE `token`='{$search_token}'")->rows();
                if ($search_token_res[0]->cn){
                    $form->set_error('token', I18N::T('people', '您填写的帐号已存在。'));
                }
            } else {
                // 如果为空，提示‘实验室负责人不能为空’，如果不为空再判定是否可添加
                if ($form['owner_id']) {
                    $form->validate('owner_id', 'is_numeric', I18N::T('labs', '实验室负责人添加失败！'));
                } else {
                    $form->set_error('owner_id', I18N::T('labs', '实验室负责人不能为空，请选择负责人！'));
                }

                $labname_check = Event::trigger('fudan_gao.check_lab_name', $form['lab_name'], 0);
                if ($labname_check) {
                    if ($labname_check > 0) {
                        $form->set_error('lab_name', I18N::T('labs', '课题组有重名，请重新填写！'));
                    }
                }

            }

            if ($form['util_area'] && !is_numeric($form['util_area'])) {
                $form->set_error('util_area', I18N::T('labs', '实验室使用面积输入有误！'));
            }

            Event::trigger('lab.form.validate', $lab, 'add', $form);

            if ($form->no_error) {

                $create_pi_role = $form['owner_get'] == 'create';

                if ($create_pi_role) {

                    $owner = O('user');
                    try {
                        $backends = Config::get('auth.backends');
                        $default_backend = Config::get('auth.default_backend');
                        $backend = trim($form['backend']) ? : 'database';
                        $token = Auth::normalize($form['token'], $backend);
                        if (User_Model::is_reserved_token($token)) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室负责人的登录帐号已被保留。'));
                            throw new Error_Exception;
                        }

                        if (O('user', ['token' => $token])->id) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室负责人的登录帐号在系统中已存在！'));
                            throw new Error_Exception;
                        }

                        if (O('user', ['email' => $form['email']])->id) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室负责人的电子邮箱在系统中已存在！'));
                            throw new Error_Exception;
                        }


                        
                        $auth = new Auth($token);

                        // 本地用户需要输入密码
                        if ($backend == 'database') {
                            if (!$auth->create($form['password'])) {
                                throw new Error_Exception;
                            }
                        }

                        $owner->token = $token;
                        $owner->name = $form['name'];
                        $owner->email = $form['email'];
                        $owner->phone = $form['phone'];
                        $owner->member_type = $form['member_type'];
                        $owner->ref_no = NULL;

                        if ($form['must_change_password'] && $form['must_change_password']!='null') {
                            $owner->must_change_password = true;
                        }

                        //$owner->atime = Date::time();

                        //由于复旦高分子文件系统需要lims用户账号，所以在此存入用户密码，用来和文件系统账号同步
                        if (Module::is_installed('nfs_windows')) {
                            $_SESSION['fs_usertoken']['password'] = $form['password'];
                        }

                        if ($owner->save()) {
                            Log::add(strtr('[labs] %user_name[%user_id]添加实验室时添加了用户%member_name[%member_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%member_name' => $owner->name, '%member_id' => $owner->id]), 'journal');
                        } else {
                            if ($backend == 'database') $auth->remove(); //添加新成员失败，去掉已添加的 token
                            throw new Error_Exception;
                        }

                        $owner_token = T('%token|%backend', ['%token' => $form['token'], '%backend' => $backends[$backend]['title']]);

                        Notification::send('people.add', $owner, [
                            '%login' => $owner_token,
                            '%user' => $owner->name,
                            '%password' => $form['password'],
                        ]);
                    } catch (Error_Exception $e) {
                    }
                } else {
                    $owner = O('user', $form['owner_id']);

                }

                if (!$owner->id) {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室负责人添加失败!'));
                } else {

                    if ($me->is_allowed_to('激活', $lab)) {
                        $lab->atime = $form['activate'] ? Date::time() : 0;
                    }

                    $lab->owner = $owner;
                    $lab->name = $form['lab_name'];
                    $lab->contact = $form['lab_contact'];

                    /*
                     * BUG #1072::实验室没有“楼”，但有“房间号”
                     * 解决：删除原来的room属性，新增location和location2分别存储“楼层”和“房间号“ (kai.wu@2011.08.31)
                     */

                    $lab->location = $form['location'];
                    $lab->location2 = $form['location2'];
                    $lab->description = $form['description'];
                    $lab->type = $form['type'];
                    $lab->ref_no = trim($form['ref_no']);
                    $lab->util_area = $form['util_area'];
                    $lab->subject = $form['subject'];
                    $lab->creator = $me;
                    isset($form['secretary_id']) ? $lab->secretary = O('user', $form['secretary_id']) : '';//pi助理

                    if ($me->is_allowed_to('修改组织机构', 'lab')) {
                        $group = O('tag_group', $form['group_id']);
                        if ($me->group->id
                            && !$me->is_allowed_to('修改组织机构', 'lab', ['@ignore' => '修改下属机构实验室'])
                            && !$me->group->is_itself_or_ancestor_of($group)) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '您设置的组织机构必须是您下属机构！'));
                        }
                        if ($group->root->id == $group_root->id) {
                            $lab->group = $group;
                        }
                    }
                    $lab->save();

                    if ($lab->id) {

                        if ($group->root->id == $group_root->id) {
                            $group_root->disconnect($lab);
                            $group->connect($lab);
                        }

                        if (!$create_pi_role) { // 从已有成员选择
                            if ($multi_lab) {
                                $owner->connect($lab);
                                $owner->connect($lab, 'pi');
                                Event::trigger('lab.multi_lab.add_member', $lab, $owner);
                            } else {
                                $roles = [];
                                foreach ($owner->roles() as $r) {
                                    if ($r <= 0) continue;
                                    $roles[] = $r;
                                }

                                $new_user = $owner->replacement();
                                $owner->remove_unique()->save();
                                if (!$owner->group->id) {
                                    $new_user->group = $lab->group;
                                }
                                if ($new_user->save()) {
                                    $new_user->connect($lab);
                                    $new_user->connect($lab, 'pi');

                                    if (count($roles)) $new_user->connect(['role', $roles]);

                                    $owner->group->connect($new_user);
                                    $owner->move_img_to($new_user);
                                    $lab->owner = $new_user;
                                    $lab->save();
                                }
                            }
                        } else {
                            if (!$owner->group->id) {
                                $owner->group = $lab->group;
                            }
                            $owner->save();
                            $owner->connect($lab);
                            $owner->connect($lab, 'pi');
                        }

                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '实验室添加成功!'));
                        // JS::refresh();
                        // JS::redirect('!announces/all');

                        JS::redirect($lab->url(null, null, null, 'edit'));
                    } else {
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '实验室添加失败! 请与系统管理员联系。'));
                    }
                }
            }
        }

        JS::dialog(V('lab/add', ['lab' => $lab, 'form' => $form, 'group_root' => $group_root, 'group' => $group]), [
            'title' => I18N::T('labs', '添加课题组'),
        ]);
    }

    public function index_add_lab_member_click($id)
    {
        $lab = O('lab', $id);
        $me = L('ME');

        if (!$me->is_allowed_to('添加成员', $lab)) {
            return;
        }
        JS::dialog(V('lab/add_member', ['lab' => $lab]), [
            'title' => I18N::T('labs', '添加课题组成员'),
        ]);
    }

    public function index_add_lab_member_submit($id)
    {
        $lab = O('lab', $id);
        $me = L('ME');
        if (!$me->is_allowed_to('添加成员', $lab)) {
            return;
        }
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        if (Input::form('submit')) {
            $user = O('user');
            $form = Form::filter(Input::form());

            $token = Auth::make_token(trim($form['token']), trim($form['backend']));
            $auth = new Auth($token);
            $requires = Lab_Model::add_require_fields();

            if ($form['add_from'] == 'new') {
                $token = Auth::make_token(trim($form['token']), trim($form['backend']));
                $auth = new Auth($token);
                $requires = Lab_Model::add_require_fields();

                array_walk($requires, function ($v, $k) use ($form, $user, $group_root, $auth) {
                    if ($v) {
                        switch ($k) {
                            case 'token':
                                $form->validate('token', 'is_token', I18N::T('labs', '请填写符合规则的用户帐号!'));
                                if (User_Model::is_reserved_token($form['token'])) {
                                    $form->set_error('token', I18N::T('labs', '您填写的帐号已被管理员保留。'));
                                }

                                $token = Auth::make_token(trim($form['token']), trim($form['token_backend']));
                                if (O('user', ['token' => $token])->id) {
                                    $form->set_error('token', I18N::T('labs', '您填写的登录帐号在系统中已存在!'));
                                }

                            case 'backend':
                                $form->validate('backend', 'not_empty', I18N::T('labs', '请选择验证后台!'));
                                if ($form['backend'] && !$this->_validate_token_backend($form['backend'])) {
                                    //zl
                                    $form->set_error('backend', I18N::T('labs', '验证后台不合法'));
                                }
                                $auth_backends = Config::get('auth.backends');
                                if (!$auth->is_readonly() && $form['backend'] && !$auth_backends[$form['backend']]['readonly']) {
                                    if (!$form['password']) {
                                        $form->validate('password', 'not_empty', I18N::T('labs', '密码不能为空!'));
                                    } else {
                                        $form
                                            ->validate('password', 'compare(==confirm_password)', I18N::T('labs', '两次填写密码不一致!'))
                                            ->validate('password', 'length(8, 24)', I18N::T('labs', '填写的密码不能小于8位, 最长不能大于24位!'));
                                    }
                                }

                                break;
                            case 'passwd':
                                if (!$auth->is_readonly()) {
                                    if (!preg_match('/(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['password'])) {
                                        $form->set_error('password', I18N::T('people', '密码必须包含大写字母、小写字母和数字!'));
                                    }
                                    $form->validate('password', 'length(8,24)', I18N::T('labs', '填写的密码不能小于8位, 最长不能大于24位!'));
                                }
                                break;
                            case 'confirm_passwd':
                                if (!$auth->is_readonly()) {
                                    $form->validate('confirm_password', 'compare(==password)', I18N::T('labs', '请填写有效密码并确保两次填写的密码一致!'));
                                }
                                break;
                            case 'name':
                                $form->validate('name', 'not_empty', I18N::T('labs', '请填写用户姓名!'));
                                break;
                            case 'email':
                                if ($form['email']) {
                                    $form->validate('email', 'is_email', I18N::T('labs', 'Email填写有误!'));
                                    $exist_user = O('user', ['email' => $form['email']]);
                                    if ($exist_user->id && $exist_user->id != $user->id) {
                                        $form->set_error('email', I18N::T('labs', '您填写的电子邮箱在系统中已经存在!'));
                                    }
                                } else {
                                    $form->validate('email', 'not_empty', I18N::T('people', 'Email不能为空!'));
                                }
                                break;
                            case 'phone':
                                $form->validate('phone', 'not_empty', I18N::T('labs', '请填写联系电话!'));
                                break;
                            case 'member_type':
                                if ($form['member_type'] < 0) {
                                    $form->set_error('member_type', I18N::T('labs', '请选择人员类型!'));
                                }

                                break;
                            case 'ref_no':
                                $form->validate('ref_no', 'not_empty', I18N::T('labs', '请填写学号/工号!'));
                                if (trim($form['ref_no'])) {
                                    $ref_user = O('user', ['ref_no' => trim($form['ref_no'])]);
                                    if ($ref_user->id && $ref_user->id != $user->id) {
                                        $form->set_error('ref_no', I18N::T('labs', '您填写的学号/工号在系统中已经存在!'));
                                    }
                                }
                                break;
                            case 'card_no':
                                $card_no_start = Config::get('form.validate.card_no.start', 6);
                                $card_no_end = Config::get('form.validate.card_no.end', 10);
                                $form->validate('card_no', 'not_empty', I18N::T('labs', '请填写IC卡卡号!'));
                                $form->validate('card_no', 'is_numeric', I18N::T('labs', '请填写合法的IC卡卡号!'));
                                $form->validate('card_no', "length({$card_no_start},{$card_no_end})", I18N::T('people', "填写的IC卡卡号不能小于%card_no_start位, 最长不能大于%card_no_end位!", ['%card_no_start' => $card_no_start, '%card_no_end' => $card_no_end]));
                                if ($form['card_no']) {
                                    $exist_user = O('user', ['card_no' => $form['card_no']]);
                                    if ($exist_user->id && $exist_user->id != $user->id) {
                                        $form->set_error('card_no', I18N::T('labs', '您填写的IC卡卡号在系统中已经存在!'));
                                    }
                                }
                                break;
                            case 'time':
                                if ($GLOBALS['preload']['people.enable_member_date']) {
                                    if (!$form['dfrom'] && !$form['dto']) {
                                        $form->set_error('dto', I18N::T('labs', '请填写所在时间!'));
                                    }
                                }
                                break;
                            case 'gender':
                                $form->validate('gender', 'is_numeric', I18N::T('labs', '请选择性别!'));
                                break;
                            case 'organization':
                                $form->validate('organization', 'not_empty', I18N::T('labs', '请填写单位名称!'));
                                break;
                            case 'mentor_name':
                                if (Config::get('people.show_mentor_name', false)) {
                                    $form->validate('mentor_name', 'not_empty', I18N::T('labs', '请填写导师姓名!'));
                                }
                                break;
                            case 'major':
                                $form->validate('major', 'not_empty', I18N::T('labs', '请填写专业!'));
                                break;
                            case 'personal_phone':
                                if (Config::get('people.show_personal_phone', false)) {
                                    $form->validate('personal_phone', 'not_empty', I18N::T('labs', '请填写个人手机!'));
                                }
                                break;
                            case 'address':
                                $form->validate('address', 'not_empty', I18N::T('labs', '请填写地址!'));
                            default:
                                break;
                        }
                    } else {

                        switch ($k) {

                            case 'ref_no':
                                if (trim($form['ref_no'])) {
                                    $ref_user = O('user', ['ref_no' => trim($form['ref_no'])]);
                                    if ($ref_user->id && $ref_user->id != $user->id) {
                                        $form->set_error('ref_no', I18N::T('labs', '您填写的学号/工号在系统中已经存在!'));
                                    }
                                }
                                break;
                            case 'card_no':
                                if ($form['card_no']) {
                                    $exist_user = O('user', ['card_no' => $form['card_no']]);
                                    if ($exist_user->id && $exist_user->id != $user->id) {
                                        $form->set_error('card_no', I18N::T('labs', '您填写的IC卡卡号在系统中已经存在!'));
                                    }
                                }
                                break;
                            case 'backend':

                                if ($form['backend'] && !$this->_validate_token_backend($form['backend'])) {

                                    $form->set_error('backend', I18N::T('labs', '验证后台不合法'));
                                }
                                break;
                            default:
                                break;
                        }

                    }
                });

                //如果为临时使用实验室
                //并且tax_no必填
                //并且没填写tax_no
                //则set_error
                if (Module::is_installed('equipments') && $lab->id == Equipments::default_lab()->id && Config::get('people.temp_user.tax_no.required', false) && !$form['tax_no']) {
                    $form->set_error('tax_no', I18N::T('equipments', '请填写税务登记号'));
                }

                if ($form->no_error) {
                    try {
                        if (O('user', ['email' => $form['email']])->id) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您输入的电子邮箱在系统中已存在！'));
                            throw new Error_Exception;
                        }

                        $form['ref_no'] = trim($form['ref_no']);
                        if ($form['ref_no'] && O('user', ['ref_no' => $form['ref_no']])->id) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '您输入的学号/工号在系统中已经存在！'));
                            throw new Error_Exception;
                        }

                        // make auth
                        $auth = new Auth($token);

                        if ($auth->is_creatable()) {
                            if (!$auth->create($form['password'])) {
                                Lab::message(Lab::MESSAGE_ERROR, I18N::T('people', '添加新成员失败! 请与系统管理员联系。'));
                                throw new Error_Exception;
                            }
                        }

                        if ($form['must_change_password'] && $form['must_change_password']!='null' ) {
                            $user->must_change_password = true;
                        }

                        // assignment
                        //设定税务登记号
                        if (Module::is_installed('equipments') && $lab->id == Equipments::default_lab()->id) {
                            $user->tax_no = $form['tax_no'];
                            Event::trigger('equipments.record.create_user_before_save', $user, $form);
                        }

                        $user->token = $token;
                        $user->name = $form['name'];
                        $user->gender = $form['gender'];
                        $user->ref_no = $form['ref_no'] ?: null;
                        $user->member_type = $form['member_type'];
                        $user->organization = $form['organization'];
                        $user->major = $form['major'];
                        $user->email = $form['email'];
                        $user->phone = $form['phone'];
                        $user->address = $form['address'];
                        $user->lab_id=$id;

                        if ($form['dto'] && $form['dfrom']
                            && $form['dfrom'] > $form['dto']) {
                            list($form['dto'], $form['dfrom']) = [$form['dfrom'], $form['dto']];
                        }

                        if ($form['dfrom']) {
                            $user->dfrom = Date::get_day_start($form['dfrom']);
                        }

                        if ($form['dto']) {
                            $user->dto = Date::get_day_end($form['dto']);
                        }

                        if (Config::get('people.show_mentor_name', false)) {
                            $user->mentor_name = $form['mentor_name'];
                        }
                        if (Config::get('people.show_personal_phone', false)) {
                            $user->personal_phone = $form['personal_phone'];
                        }

                        $user->atime = Config::get('lab.cannot_active') ? 0 : Date::time();
                        if ($me->id == $lab->owner->id && Config::get('lab.cannot_active')) {
                            $user->atime = 0;
                        } else {
                            $user->atime = Date::time();
                        }

                        // add lab and group
                        $user->group = $lab->group;
                        //由于复旦高分子文件系统需要lims用户账号，所以在此存入用户密码，用来和文件系统账号同步
                        if (Module::is_installed('nfs_windows')) {
                            $_SESSION['fs_usertoken']['password'] = $form['password'];
                        }

                        if ($user->save()) {
                            //用户保存成功，则关联课题组
                            $user->connect($lab);
                            $user->group->connect($user);

                            Log::add(strtr('[labs] %user_name[%user_id]添加了实验室%lab_name[%lab_id]的成员%member_name[%member_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%lab_name' => $lab->name, '%lab_id' => $lab->id, '%member_name' => $user->name, '%member_id' => $user->id]), 'journal');
                        } else {
                            $auth->is_creatable() and $auth->remove(); //添加新成员失败，去掉已添加的 token
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '添加新成员失败! 请与系统管理员联系。'));
                            throw new Error_Exception;
                        }

                        $arr_user_token = explode('|', $user->token);
                        $token_backend_title = $auth_backends[$arr_user_token['1']]['title'];
                        $user_token = implode(' | ', [$arr_user_token['0'], $token_backend_title]);

                        Notification::send('people.add', $user, [
                            '%login' => $user_token,
                            '%user' => H($user->name),
                            '%password' => $form['password'],
                        ]);

                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('people', '新成员已添加。'));

                        Log::add(strtr('[Labs] %user_name[%user_id] 添加 %member_name[%member_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%member_name' => $user->name, '%member_id' => $user->id]), 'admin');
                        JS::redirect($lab->url());
                    } catch (Error_Exception $e) {
                    }
                }
            } else {
                $form['ajax'] = true;
                Event::trigger("lab.add_member.{$form['add_from']}", $lab, $form);
            }
        }
        JS::dialog(V('lab/add_member', ['lab' => $lab, 'form' => $form]), [
            'title' => I18N::T('labs', '添加课题组成员'),
        ]);
    }

    public function index_export_click()
    {
        $form = Input::form();
        $form_token = $form['form_token'];
        $type = $form['type'];
        $columns = Config::get('labs.export_columns.labs');

        if ($type == 'csv') {
            $title = I18N::T('labs', '请选择要导出CSV的列');
        } elseif ($type == 'print') {
            $title = I18N::T('labs', '请选择要打印的列');
        }
        JS::dialog(V('export_form', [
            'form_token' => $form_token,
            'columns' => $columns,
            'type' => $type,
        ]), [
            'title' => I18N::T('labs', 'title'.$title),
        ]);

    }

    public function index_remove_member_click()
    {
        $me = L('ME');
        $form = Input::form();
        $user = O('user', $form['uid']);
        $lab = O('lab', $form['id']);
        if (Q("$user<pi $lab")->total_count()) {
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '不可移除课题组PI'));
        } elseif (Q("$user lab")->total_count() > 1) {
            if ($me->is_allowed_to('删除成员', $lab) && JS::confirm(I18N::T('lab', '确定移除该课题组成员吗？'))) {
                $user->disconnect($lab);
                Log::add(strtr('[labs] %user_name[%user_id]移除了实验室%lab_name[%lab_id]的成员%member_name[%member_id]', ['%user_name' => $me->name, '%user_id' => $me->id, '%lab_name' => $lab->name, '%lab_id' => $lab->id, '%member_name' => $user->name, '%member_id' => $user->id]), 'journal');
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '移除成员成功!'));
            }
        }
        JS::refresh();
    }

    public function index_add_member_token_change()
    {
        $form = Input::form();
        $user = O('user', $form['user_id']);
        $info = [];
        if ($user->id) {
            $member_types = [];
            foreach (User_Model::get_members() as $key => $value) {
                $member_types += $value;
            }
            $info['token'] = explode('|', $user->token)[0];
            $info['name'] = $user->name;
            $info['gender'] = User_Model::$genders[$user->gender];
            $info['member_type'] = $member_types[$user->member_type];
            $info['ref_no'] = $user->ref_no;
            $info['major'] = $user->major;
            $info['organization'] = $user->organization;
            $user->dfrom > 0 && $info['dfrom'] = Date::format($user->dfrom, 'Y/m/d');
            $user->dto > 0 && $info['dto'] = Date::format($user->dto, 'Y/m/d');
            $info['phone'] = $user->phone;
            $info['email'] = $user->email;
            $info['address'] = $user->address;
        }
        Output::$AJAX['data'] = [
            'data' => $info,
            'mode' => 'replace',
        ];
    }

}
