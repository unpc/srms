<?php

class Signup_Controller extends Layout_Controller {

    function _before_call($method, &$params){

        if (Config::get('lab.disable_signup', FALSE)) {
            URI::redirect('error/404');
        }

        $me = L('ME');
        if ($me->id && $me->is_active()) {
            URI::redirect('/');
        }

        parent::_before_call($method, $params);
    }

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
        $ret = (array)$logapper->setAccessToken($result['access_token'])->get('user', ['email'=>$user->email]);
        $gapper_id = current($ret['items'])['id'];
        if ($gapper_id) {
            return $gapper_id;
        }
        else {
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
    }

    function index(){
        $me = L('ME');
        
        if ($me->id && !$me->is_active() ){
            URI::redirect('!labs/signup/edit');
        }

        if (Input::form('logout')) {
            Auth::logout();
            URI::redirect('/');
        }

        $group_root = Tag_Model::root('group');

        $form = Form::filter(Input::form());

        if (isset($_SESSION['has_read_registeration_announcement']) && !$_SESSION['has_read_registeration_announcement']) $form->no_error = 0;

        if (Input::form('submit')) {
            if (H($form['verify_token']) != md5($_SESSION['verify_token'])) {
                unset($_SESSION['verify_token']);
                URI::redirect('error/401');
            }

            $verified_token = Auth::token();

            $token = $verified_token ? : Auth::make_token(trim($form['token']), trim($form['token_backend']));
            $auth = new Auth($token);

            //获取requires
            //返回结果
            //array(
                //'token'=> TRUE,
                //xxx=> TRUE,
            //);

            $requires = Lab_Model::register_require_fields();

            if (isset($form['register_type']) && $form['register_type'] == 2) $requires['organization'] = TRUE;

            if (Module::is_installed('eq_glogon')) {
                $requires['glogon_pass'] = TRUE;
            }
            $requires = new ArrayIterator($requires);
            Event::trigger('signup.validate_requires', $requires, $form);

            array_walk($requires, function($v, $k) use($form, $user, $group_root, $auth, $token) {
                if ($v) switch ($k) {
                    case 'token':
                        if ($form['token']) {
                            $form->validate('token', 'is_token', I18N::T('people', '请填写符合规则的登录帐号!'));

                            if(O('user', ['token'=>$token])->id) {
                                $form->set_error('token', I18N::T('labs', '您填写的登录帐号在系统中已存在!'));
                            }

                            if (User_Model::is_reserved_token($form['token']) || User_Model::is_reserved_token($token)) {
                                $form->set_error('token', I18N::T('people', '您填写的帐号已被管理员保留。'));
                            }
                        }
                        elseif (!$form['token']) {
                            $form->set_error('token', I18N::T('labs', '请填写登录帐号!'));
                        }
                        break;
                    case 'backend':
                        $form->validate('backend', 'not_empty', I18N::T('people', '请选择验证后台!'));
                        if ($form['backend'] && !$this->_validate_token_backend($form['backend'])) {
                            $form->set_error('backend', '验证后台不合法, 若多次出现该错误请尝试清除浏览器缓存');
                        }
                        $auth_backends = Config::get('auth.backends');
                        if ($form['backend'] && !$auth_backends[$form['backend']]['readonly']) {
                            $form
                                ->validate('passwd', 'not_empty', I18N::T('people', '密码不能为空！'))
                                ->validate('passwd', 'compare(==confirm_passwd)', I18N::T('people', '两次填写密码不一致!'))
                                ->validate('passwd', 'length(8, 24)', I18N::T('people', '填写的密码不能小于8位, 最长不能大于24位!'));
                        }
                        break;
                    case 'token_backend':
                        $form->validate('token_backend', 'not_empty', I18N::T('people', '请选择验证后台!'));
                        if ($form['token_backend'] && !$this->_validate_token_backend($form['token_backend'])) {
                            $form->set_error('token_backend', '验证后台不合法, 若多次出现该错误请尝试清除浏览器缓存');
                        }
                        $auth_backends = Config::get('auth.backends');
                        if ($form['token_backend'] && !$auth_backends[$form['token_backend']]['readonly']) {
                            $form
                                ->validate('passwd', 'not_empty', I18N::T('people', '密码不能为空！'))
                                ->validate('passwd', 'compare(==confirm_passwd)', I18N::T('people', '两次填写密码不一致!'))
                                ->validate('passwd', 'length(8, 24)', I18N::T('people', '填写的密码不能小于8位, 最长不能大于24位!'));
                        }
                        break;
                    case 'passwd':
                        if ( !$auth->is_readonly() ) {
                            $form->validate('passwd', 'not_empty', I18N::T('people', '密码不能为空!'));
                            $require_special = Config::get('labs.require_password_special');
                            if ($require_special) {
                                if (!preg_match('/(?=(?:.*?\d){1})(?=.*[a-z])(?=.*[!@#.,$%*()_+^&])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['passwd'])) {
                                    $form->set_error('passwd', I18N::T('people', '密码必须包含大写字母、小写字母、数字和特殊字符!'));
                                }
                            } else {
                                if (!preg_match('/(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['passwd'])) {
                                    $form->set_error('passwd', I18N::T('people', '密码必须包含大写字母、小写字母、数字!'));
                                }
                            }
                            $form->validate('passwd', 'length(8,24)', I18N::T('people', '填写的密码不能小于8位, 最长不能大于24位!'));
                        }
                        break;
                    case 'confirm_passwd':
                        if ( !$auth->is_readonly()) {
                            $form->validate('confirm_passwd', 'compare(==passwd)', I18N::T('people', '请填写有效密码并确保两次填写的密码一致!'));
                        }
                        break;
                    case 'name':
                        $form->validate('name', 'not_empty', I18N::T('people', '请填写用户姓名!'));
                        break;
                    case 'email':
                        if ($form['email']) {
                            $form->validate('email', 'is_email', I18N::T('people', 'Email填写有误!'));
                            $exist_user = O('user', ['email'=>$form['email']]);
                            if ($exist_user->id && $exist_user->id != $user->id) {
                                $form->set_error('email', I18N::T('people', '您填写的电子邮箱在系统中已经存在!'));
                            }
                        }
                        else {
                            $form->validate('email', 'not_empty', I18N::T('people', 'Email不能为空!'));
                        }
                        break;
                    case 'phone':
                        $form->validate('phone', 'not_empty', I18N::T('people', '请填写联系电话!'));
                        break;
                    case 'member_type':
                        if ($form['member_type'] < 0) $form->set_error('member_type', I18N::T('people', '请选择人员类型!'));
                        break;
                    case 'ref_no':
                        $form->validate('ref_no', 'not_empty', I18N::T('people', '请填写学号/工号!'));
                        if (trim($form['ref_no'])) {
                            $ref_user = O('user', ['ref_no'=>trim($form['ref_no'])]);
                            if ($ref_user->id && $ref_user->id != $user->id) {
                                $form->set_error('ref_no', I18N::T('people', '您填写的学号/工号在系统中已经存在!'));
                            }
                        }
                        break;
                    case 'card_no':
                        $card_no_start = Config::get('form.validate.card_no.start', 6);
                        $card_no_end = Config::get('form.validate.card_no.end', 10);
                        $form->validate('card_no', 'not_empty', I18N::T('people', '请填写IC卡卡号!'));
                        $form->validate('card_no', 'is_numeric', I18N::T('labs', '请填写合法的IC卡卡号!'));
                        $form->validate('card_no', "length({$card_no_start},{$card_no_end})", I18N::T('people', "填写的IC卡卡号不能小于%card_no_start位, 最长不能大于%card_no_end位!", ['%card_no_start' => $card_no_start, '%card_no_end' => $card_no_end]));
                        if ($form['card_no']) {
                            $exist_user = O('user', ['card_no'=>$form['card_no']]);
                            if ($exist_user->id && $exist_user->id != $user->id) {
                                $form->set_error('card_no', I18N::T('people', '您填写的IC卡卡号在系统中已经存在!'));
                            }
                        }
                        break;
                    case 'gender':
                        $form->validate('gender', 'is_numeric', I18N::T('people', '请选择性别!'));
                        break;
                    case 'group_id':
                        if($form['local_remote_group'] != 'remote_group') {
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
                        if ( Config::get('people.show_mentor_name', false) ) {
                            $form->validate('mentor_name', 'not_empty', I18N::T('people', '请填写导师姓名!'));
                        }
                        break;
                    case 'major':
                        $form->validate('major', 'not_empty', I18N::T('people', '请填写专业!'));
                        break;
                    case 'personal_phone':
                        if ( Config::get('people.show_personal_phone', false) ) {
                            $form->validate('personal_phone', 'not_empty', I18N::T('people', '请填写个人手机!'));
                        }
                        break;
                    case 'address':
                        $form->validate('address', 'not_empty', I18N::T('people', '请填写地址!'));
                        break;
                    case 'lab_id':
                        if ( !O('lab',$form['lab_id'])->id) {
                            $form->set_error('lab_id', I18N::T('labs', '请填写实验室!|:signup'));
                        }
                        break;
                    case 'time' :
                        if (!$form['dfrom'] && !$form['dto']) {
                            $form->set_error('dto', I18N::T('labs', '请填写所在时间!'));
                        }
                        break;
                    case 'glogon_pass' :
                        //案例20192545重庆科技学院不需要客户端
                        if (Module::is_installed('eq_glogon') && Config::get('lab.signup_pass', TRUE)) {
                            $form->validate('glogon_pass', 'not_empty', I18N::T('people', '请填写客户端密码!'));
                            $form->validate('glogon_pass', 'length(6, 24)', I18N::T('eq_glogon', '客户端密码不能小于6位, 最长不能大于24位!'));
                        }
                        break;
                    default:
                        break;
                }
                else {
                    switch ($k) {
                        case 'ref_no':
                            if (trim($form['ref_no'])) {
                                $ref_user = O('user', ['ref_no'=>trim($form['ref_no'])]);
                                if ($ref_user->id && $ref_user->id != $user->id) {
                                    $form->set_error('ref_no', I18N::T('people', '您填写的学号/工号在系统中已经存在!'));
                                }
                            }
                            break;
                        case 'card_no':
                            if ($form['card_no']) {
                                $exist_user = O('user', ['card_no'=>$form['card_no']]);
                                if ($exist_user->id && $exist_user->id != $user->id) {
                                    $form->set_error('card_no', I18N::T('people', '您填写的IC卡卡号在系统中已经存在!'));
                                }
                            }
                            break;
                        case 'email':
                            if ($form['email']) {
                                $exist_user = O('user', ['email'=>$form['email']]);
                                if ($exist_user->id && $exist_user->id != $user->id) {
                                    $form->set_error('email', I18N::T('people', '您填写的电子邮箱在系统中已经存在!'));
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            });
            //额外自定义字段验证
            Event::trigger('signup.validate_extra_field', $requires, $form,O('user'));

            if($form->no_error) {
                try {
                    $user = O('user');

                    $user->token = strtolower($token);
                    $user->email = $form['email'];

                    /*
                    if (Q("user[email={$user->email}|token={$user->token}]")->length() > 0) {
                        //如果token或者email不是唯一的跳转到注册页面.
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '您填写的学号或电子邮箱在系统中已存在！'));
                        throw new Error_Exception;
                    }
                    */
                    $user->name = H($form['name']);
                    $user->member_type = $form['member_type'];
                    $user->organization = $form['organization'];
                    $user->gender = $form['gender'];
                    $user->major = $form['major'];
                    $user->phone = $form['phone'];
                    Event::trigger('signup.save_extra_field', $user, $form);
                    
                    if ( Config::get('people.show_personal_phone', false) ) {
                        $user->personal_phone = $form['personal_phone'];
                    }
                    if ( Config::get('people.show_mentor_name', false) ) {
                        $user->mentor_name = $form['mentor_name'];
                    }

                    if($form['local_remote_group'] == 'remote_group'){
                        //同步远程课题组
                        $remote_user_group = $_SESSION['remote_user_group'];

                        $parent_tag = $group_root;
                        foreach ($remote_user_group as $g) {
                            $g_tag = O('tag_group', ['parent'=>$parent_tag, 'root'=>$group_root, 'name'=>$g]);
                            if(!$g_tag->id){
                                $g_tag->parent = $parent_tag;
                                $g_tag->root = $group_root;
                                $g_tag->name = $g;
                                $g_tag->save();
                            }
                            $parent_tag = $g_tag;
                        }

                        $group = $parent_tag;
                    }
                    else{
                        $group = O('tag_group', $form['group_id']);
                    }

                    if ($group->id && $group->root->id == $group_root->id) {
                        $user->group = $group;
                    }

                    $user->address = $form['address'];
                    $user->ref_no = trim($form['ref_no']) ? : NULL;

                    if ($form['dto']
                        &&
                        $form['dfrom']
                        &&
                        $form['dfrom'] > $form['dto']
                        ) {
                        list($form['dto'], $form['dfrom']) = [$form['dfrom'], $form['dto']];
                    }

                    if ($form['dfrom']) {
                        $user->dfrom = Date::get_day_start($form['dfrom']);
                    }

                    if ($form['dto']) {
                        $user->dto = Date::get_day_end($form['dto']);
                    }

					if (!$verified_token) {
						$password = $form['passwd'];

						if ( !$auth->is_readonly() ) {
							if (!$auth->create($password)) {
								Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '用户注册失败, 请您重试.'));
								throw new Error_Exception;
							}
							else {
								if (Module::is_installed('nfs_windows')) {
									$_SESSION['fs_usertoken']['password'] = $form['password'];
									NFS_Windows::fs_usertoken_saved(new stdClass(), $user, [], []);
								}
							}
						}
						else {
							// 2016-01-27 Unpc 因为目前外界方式不仅仅只有sso, 存在cas,ids,E江南多方验证，
							// 但是无法进行verify密码的操作，故取消掉远程verify的操作
							/*
							if (!$auth->verify($password)) {
								Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '用户名与密码不匹配, 请您重试.'));
								throw new Error_Exception;
							}
							*/
						}
					}

                    if ($_SESSION['signup_register_user_type'] == 2) {
                        $user->signup_type = 'other';   
                    }

                    if (Config::get('people.link_gapper_system')) {
                        $logapper_id = self::_reg_logapper_user($user, $form['passwd']);
                        if (!$logapper_id) {
                            throw new Error_Exception;
                        }
                        $user->gapper_id = $logapper_id;
                    }
                    $user->save();
                    $user_lab = O('lab', $form['lab_id']);
                    $user->connect($user_lab);
                    self::_groupConnect($user, $group);
                    /*
                        TODO
                        用户注册失败的原因有好几种，比如邮箱重复，帐号重复等，应该优化提示错误信息
                    */
                    if (!$user->id) {
                        if (!$verified_token) {
                            !$auth->is_readonly() and $auth->remove(); //添加新成员失败，去掉已添加的 token
                        }
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '用户注册失败, 请您重试.'));
                        throw new Error_Exception;
                    }

                    //原始站点的roles名称合集
                    $user->original_roles = array_values((array)$_SESSION['user_roles']);

                    //远程能直接赋予card_no 就直接赋予了
                    if ($_SESSION['card_no']) {
                        $card_no = $_SESSION['card_no'] + 0;    //添加 +0 可强制转换成无符号数
                        $user->card_no = $card_no;
                        $user->card_no_s = $card_no & 0xffffff;
                        unset($_SESSION['card_no']);
                    }
                    
                    //如果远程给予用户激活的能力，则激活
                    if ($_SESSION['user_atime']) {
                        $user->atime = Config::get('people.signup.activation', TRUE) ? $_SESSION['user_atime'] : 0;
                        unset($_SESSION['user_atime']);
                    }

                    $user->save();
                    //当用户保存后，设置课题组PI(课题组是自动生成的)
                    Event::trigger('user_signup.set_lab_owner', $user);
                    //注册完之后需要做的事
                    Event::trigger('after_user_signup', $user);

                    Log::add(strtr('[labs] %user_name[%user_id]成功注册了个人帐号', [
                        '%user_name' => $user->name,
                        '%user_id' => $user->id,
                    ]), 'journal');

                    /**
                     * 注册账号默认激活的站点，无需再进行审核提示
                     */
                    if (!$user->atime) {
                        $success_message = Config::get('people.signup.success');
                        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', $success_message));
                    }
                    Auth::login($user->token);

                    Event::trigger('people.signup.notifications', $user, $user_lab);

                    //中心管理员和院级管理员
                    // TODO: People::perm_in_uno() 需处理
                    $admins = Q("perm[name=添加/修改所有成员信息|name=添加/修改下属机构成员的信息] role user");
                    foreach ($admins as $admin) {
                        Notification::send('people.signup.admin', $admin, [
                            '%admin' => Markup::encode_Q($admin),
                            '%user' => Markup::encode_Q($user),
                        ]);
                    }
                    //pi
                    Notification::send('people.signup.admin', $user_lab->owner, [
                        '%admin' => Markup::encode_Q($user_lab->owner),
                        '%user' => Markup::encode_Q($user),
                    ]);

                    if (!$user->atime) {
                        Notification::send('people.signup', $user, [
                            '%time'=>Date::format($user->ctime, T('Y年m月d日')),
                            '%user' => Markup::encode_Q($user),
                            '%lab' => H($user_lab->name),
                            '%lab_contact' => H($user_lab->contact),
                            '%pi' => H($user_lab->owner->name),
                            '%pi_phone' => H($user_lab->owner->phone),
                            '%pi_email' => H($user_lab->owner->email)
                        ]);
                    } else {
                        Notification::send('people.signup.auto.active', $user, [
                            '%time'=>Date::format($user->ctime, T('Y年m月d日')),
                            '%user' => Markup::encode_Q($user),
                        ]);
                    }

                    Event::trigger('people.signup.redirect.url', $user);
                    URI::redirect('/');
                }
                catch (Error_Exception $e) {
                }
            }
        }
        $this->layout->form = $form;

        $this->layout->body = V('signup/signup',['group_root'=>$group_root]);
    }

    private function _validate_token_backend($backend) {
        $backends = Config::get('auth.backends');
        return in_array(trim($backend), array_keys($backends));
    }

    function introduction(){
        $this->layout = V('signup/introduction');
    }

    function edit($uid=0){
        $this->tab = 'edit';
        $user = L('ME');
        if (!$user->id) {
            URI::redirect('!labs/signup');
        }
        $group_root = Tag_Model::root('group');
        if(Input::form('submit')){
            $form = Form::filter(Input::form());

            $form_config = Config::get('form.user_signup');
            $requires = (array)$form_config['requires'];
            if (Config::get('people.link_gapper_system')) {
                $requires['email'] = false;
                $form['email'] = $user->email;
            }

            $requires = new ArrayIterator($requires);
            Event::trigger('signup.validate_requires', $requires, $form);


            if ($requires['name']) {
                $form->validate('name', 'not_empty', I18N::T('labs', '请填写真实姓名!'));
            }

            if ($requires['gender']) {
                $form->validate('gender', 'is_numeric', I18N::T('labs', '请选择性别!'));
            }

            if ($requires['member_type']) {
                if ($form['member_type'] < 0) $form->set_error('member_type', I18N::T('people', '请选择人员类型!'));
            }

            if ($requires['group_id']) {
                if($form['local_remote_group'] != 'remote_group'){
                    $group = O('tag_group', $form['group_id']);
                    if (!$group->id || $group->root->id != $group_root->id) {
                        $form->set_error('group', I18N::T('labs', '请选择组织机构!'));
                    }
                }
            }

            if ($requires['ref_no']) {
                if (!trim($form['ref_no'])) {
                    //未填写ref_no
                    $form->set_error('ref_no', I18N::T('labs', '请填写学号/工号!'));
                }
                else {
                    //填写ref_no
                    $ref_user = O('user', ['ref_no'=> trim($form['ref_no'])]);
                    if ($ref_user->id && $user->id != $ref_user->id) $form->set_error('ref_no', I18N::T('labs', '您填写的学号/工号在系统中已存在!'));
                }
            }

            if ($requires['organization']) {
                $form->validate('organization', 'not_empty', I18N::T('labs', '请填写单位名称!'));
            }

            if ($requires['mentor_name']) {
                $form->validate('mentor_name', 'not_empty', I18N::T('labs', '请填写导师姓名!'));
            }

            if ($requires['major']) {
                $form->validate('major', 'not_empty', I18N::T('labs', '请填写专业!'));
            }

            if ($requires['email'] && !$form['email']) {
                $form->set_error('email', I18N::T('labs', '请填写电子邮箱!'));
            }

            if ($requires['phone']) {
                $form->validate('phone', 'not_empty', I18N::T('labs', '请填写联系电话!'));
            }

            if ( $requires['personal_phone'] ) {
                $form->validate('personal_phone', 'not_empty', I18N::T('labs', '请填写个人手机!'));
            }

            if ($requires['lab_id'] && !O('lab',$form['lab_id'])->id) {
                $form->set_error('lab_id', I18N::T('labs', '请填写实验室!|:signup'));
            }

            if ($requires['address']) {
                $form->validate('address', 'not_empty', I18N::T('labs', '请填写地址!'));
            }

            if ($form['passwd']) {
                if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['passwd'])) {
                    $form->set_error('passwd', I18N::T('people', '密码必须包含大写字母、小写字母和数字!'));
                }
                
                $form
                    ->validate('passwd', 'length(6,24)', I18N::T('labs', '填写的密码不能小于6位, 最长不能大于24位!'))
                    ->validate('confirm_passwd', 'compare(==passwd)', I18N::T('labs', '请填写有效密码并确保两次填写的密码一致！'));
            }
            Event::trigger('signup.validate_extra_field', $requires, $form, $user);

            if ($form->no_error) {

                try {

                    if ($form['passwd']) {
                        $auth = new Auth($user->token);
                        if( ! $auth->change_password($form['passwd'] )) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '密码修改失败, 请您重试.'));
                            throw new Error_Exception;
                        }
                    }

                    $email_user = O('user', ['email'=> $form['email']]);
                    if ($email_user->id && $user->id != $email_user->id) {
                        //如果email不是唯一的报错.
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '您填写的电子邮箱在系统中已存在!'));
                        throw new Error_Exception;
                    }

                    if (trim($form['ref_no'])) {
                        $ref_user = O('user', ['ref_no'=>trim($form['ref_no'])]);
                        if ($ref_user->id && $ref_user->id != $user->id) {
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '您填写的学号/工号在系统中已存在!'));
                            throw new Error_Exception;
                        }
                    }


                    if($form['local_remote_group'] == 'remote_group'){
                        //同步远程课题组
                        $remote_user_group = $_SESSION['remote_user_group'];

                        $parent_tag = $group_root;
                        foreach ($remote_user_group as $g) {
                            $g_tag = O('tag_group', ['parent'=>$parent_tag, 'root'=>$group_root, 'name'=>$g]);
                            if(!$g_tag->id){
                                $g_tag->parent = $parent_tag;
                                $g_tag->root = $group_root;
                                $g_tag->name = $g;
                                $g_tag->save();
                            }
                            $parent_tag = $g_tag;
                        }

                        $group = $parent_tag;
                    }
                    else{
                        $group = O('tag_group', $form['group_id']);
                    }

                    $user->email = $form['email'];
                    $user->name = $form['name'];
                    $user->member_type = $form['member_type'];
                    if ($group->id && $group->root->id == $group_root->id) {
                        $user->group = $group;
                    }
                    $user->organization = $form['organization'];
                    $user->gender = $form['gender'];
                    $user->major = $form['major'];
                    $user->mentor_name = $form['mentor_name'];
                    // $user->department = $form['department'];
                    $user->phone = $form['phone'];
                    $user->personal_phone = $form['personal_phone'];
                    $user->address = $form['address'];
                    $user->ref_no = trim($form['ref_no']) ? : NULL;
                    if ($GLOBALS['preload']['people.enable_member_date']) {
                        if ($form['dfrom'] && $form['dto']) {
                            if ($form['dfrom'] > $form['dto']) {
                                list($form['dto'], $form['dfrom']) = [$form['dfrom'], $form['dto']];
                            }
                        }
                        $user->dfrom = $form['dfrom'] ? Date::get_day_start($form['dfrom']) : 0;
                        $user->dto   = $form['dto'] ? Date::get_day_end($form['dto']) : 0;
                    }

                    $user->save();

                    $old_lab = Q("{$user} lab")->current();

                    $lab = O('lab', $form['lab_id']);

                    if ($form['lab_id'] != $old_lab->id) {
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
                            $new_user->connect($lab);
    
                            if (count($roles)) {
                                $new_user->connect(['role', $roles]);
                            }
    
                            $user->move_img_to($new_user);
                            $user->move_training($user, $new_user);
                            $user->delete_reserv($old_lab);

                            if ($lab->owner->id == $new_user->id) {
                                $new_user->connect($lab, 'pi');
                            }
                            self::_groupConnect($new_user, $group);

                            URI::redirect('!labs/signup/edit');
                        }
                    } else {
                        $user->connect($lab);

                        if ($lab->owner->id == $user->id) {
                            $user->connect($lab, 'pi');
                        }
                        self::_groupConnect($user, $group);
                    }

                    Lab::message(Lab::MESSAGE_NORMAL, T('注册信息已更新'));

                }
                catch (Error_Exception $e) {
                }

            }

        }

        $this->layout->body = V('signup/edit',['user'=>$user, 'form'=>$form, 'group_root'=>$group_root]);

    }

    function lab() {
        $me = L('ME');
        /* (xiaopei.li@2011.01.04) */
        if ($me->id && !$me->is_active() ){
            URI::redirect('!labs/signup/edit');
        }

        $group_root = Tag_Model::root('group');
        $form = Input::form();
        if ($new_lab_session = $_SESSION['signup_lab_info']) {
			$form['pi_token'] = $new_lab_session['token'];
			$form['pi_token_backend'] = $new_lab_session['token_backend'];
			$form['pi_name'] = $new_lab_session['pi_name'];
			$form['pi_email'] = $new_lab_session['email'];
			$form['pi_phone'] = $new_lab_session['phone'];
			$form['group_id'] = $new_lab_session['group_id'];
			unset($_SESSION['signup_lab_info']);
        }
        
        $form = Form::filter($form);

        if (!$_SESSION['has_read_registeration_announcement']) $form->no_error = 1;

        if (Input::form('submit')) {
            if (H($form['verify_token']) != md5($_SESSION['verify_token'])) {
                unset($_SESSION['verify_token']);
                URI::redirect('error/401');
            }

            $form_config = Config::get('form.lab_signup');
            $requires = (array)$form_config['requires'];

            if ($requires['name']) {
                $form->validate('name', 'not_empty', I18N::T('labs', '请填写实验室名称！'));
            }

            if ($requires['lab_contact']) {
                $form->validate('lab_contact', 'not_empty', I18N::T('labs', '请填写联系方式!'));
            }

            if ($requires['group_id']) {
                if($form['local_remote_group'] != 'remote_group'){
                    $group = O('tag_group', $form['group_id']);
                    if (!$group->id || $group->root->id != $group_root->id) {
                        $form->set_error('group', I18N::T('labs', '请选择组织机构！'));
                    }
                }
            }

            if ($requires['pi_name']) {
                $form->validate('pi_name', 'not_empty' , I18N::T('labs', '请填写管理员姓名!'));
            }

            if (!Auth::token()) {
                if ($requires['pi_token']) {
                    $form->validate('pi_token', 'not_empty', I18N::T('labs', '请填写管理员账号!'));
                }
                if ($form['pi_token']) {
                    $form->validate('pi_token', 'is_token', I18N::T('labs', '请填写符合规则的登录帐号!'));
                }
                
                $form->validate('pi_token_backend', 'not_empty', I18N::T('labs', '请选择验证后台!'));
                if ($form['pi_token_backend'] && !$this->_validate_token_backend($form['pi_token_backend'])) {
                    $form->set_error('pi_token_backend', '验证后台不合法, 若多次出现该错误请尝试清除浏览器缓存');
                }

                $token = $form['pi_token'] . '|' . $form['pi_token_backend'];
                if ($form['pi_token'] && O('user', ['token' => $token])->id) {
                    $form->set_error('pi_token', I18N::T('labs', '您填写的管理员账号系统中已存在!'));
                }

                $backends = (array) Config::get('auth.backends');
                $backend = $form['pi_token_backend'] ?: Config::get('auth.default_backend');

                if (!$backends[$backend]['readonly']) {
                    if ( $requires['passwd'] && !$form['passwd'] ) {
                        $form->set_error('passwd', I18N::T('labs', '请填写管理员密码!'));
                    }
                    
                    if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['passwd'])) {
                        $form->set_error('passwd', I18N::T('labs', '密码必须包含大写字母、小写字母和数字!'));
                    }
                    
                    $form->validate('passwd', 'length(8,24)', I18N::T('labs', '填写的密码不能小于8位, 最长不能大于24位!'));
                }
            }

            if ($requires['pi_phone']) {
                $form->validate('pi_phone', 'not_empty', I18N::T('labs', '请填写管理员联系电话!'));
                if ($form['pi_phone']) {
                    $isTel="/^(\d+)-?(\d+)$/";
                    if (!preg_match($isTel, $form['pi_phone'])) {
                        $form->set_error('pi_phone', I18N::T('labs', '管理员电话格式不正确，请填写正确的电话格式!'));
                    }
                }
            }

            if ($requires['pi_email']) {
                $form->validate('pi_email', 'not_empty', I18N::T('labs', '请填写管理员邮箱!'));
            }

            if ($requires['project'] && count($form['project']) == 0) {
                $form->set_error('project', I18N::T('labs', '请填写实验室项目信息!'));
            }

            if ($form['pi_email']) {
                $form->validate('pi_email', 'is_email', I18N::T('labs', '管理员Email填写有误!'));
            }

            if ($form['pi_email'] && O('user', ['email' => $form['pi_email']])->id) {
                $form->set_error('pi_email', I18N::T('labs', '您填写的管理员邮箱系统中已存在!'));
            }

            $types = Lab_Project_Model::$types;
            $show_name = $has_count = false;
            $count = $project_count = 0;
            foreach ($types as $type => $name) {

                $projects = (array)$form['project'][$type];
                foreach ($projects as $key => $item) {
                    $project_count++;
                    $name = H($item['name']);

                    if (!$name || $name == NULL) {
                        $count++;
//                        if (!$show_name) {
//                            $form->set_error("project[$type][$key][name]", I18N::T('labs', '请填写项目名称!'));
//                            $show_name = true;
//                        }
//                        else $form->set_error("project[$type][$key][name]");
                    }
                    switch($type){
                        case Lab_Project_Model::TYPE_EDUCATION:
                            $student_count = H($item['student_count']);
                            if ($student_count && !is_numeric($student_count))    {
                                if (!$has_count) {
                                     $form->set_error("project[$type][$key][student_count]", I18N::T('labs', '请填写正确的人数!'));
                                     $has_count = true;
                                 }
                                 else $form->set_error("project[$type][$key][student_count]");
                            }
                            break;
                        case Lab_Project_Model::TYPE_RESEARCH:
                        case Lab_Project_Model::TYPE_SERVICE:
                            break;
                    }
                }
            }
            Event::trigger('signup_lab.validate_extra_field', $requires, $form, $user);

            if ($requires['project'] && count($form['project']) == 0 && $count == $project_count){
                $form->set_error("project",I18N::T('labs', '请填写以下三类项目中的至少一种!'));
            }

            if ($form->no_error) {
                try {
                    if (!Auth::token()) {
                        $token = Auth::make_token(H($form['pi_token']), $form['pi_token_backend']);

                        $auth = new Auth($token);
                        if (!$auth->is_readonly() && !$auth->create($form['passwd'] ? : 'Genee83719730')) {
                            if (User_Model::is_reserved_token($token)) {
                                Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '您填写的登录帐号已被保留。'));
                                throw new Error_Exception;
                            }
                            Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '添加实验室管理员失败! 请与系统管理员联系。'));
                            throw new Error_Exception;
                        }
                    }
                    else {
                        $token = Auth::token();
                        list(, $backend) = Auth::parse_token($token);
                        if (!trim($backend)) {
                            Auth::logout();
                            throw new Error_Exception;
                        }
                    }

                    $owner = O('user');
                    $owner->token = strtolower($token);
                    $owner->name = H($form['pi_name']);
                    $owner->email = H($form['pi_email']);
                    $owner->phone = H($form['pi_phone']);

                    if($signup_user_info = $_SESSION['signup_user_info']){
                        $owner->member_type = $signup_user_info['member_type'];
                        $owner->organization = $signup_user_info['organization'];
                        $owner->gender = $signup_user_info['gender'];
                        $owner->major = $signup_user_info['major'];
                        $signup_user_info['phone'] && $owner->phone = $signup_user_info['phone'];

                        $signup_user_info['address'] && $owner->address = $signup_user_info['address'];
                        $owner->ref_no = trim($signup_user_info['ref_no']) ? : NULL;
                        unset($_SESSION['signup_user_info']);
                    } else {
                        $owner->member_type = array_search('课题负责人(PI)', User_Model::get_members()['教师']);
                    }

                    if(Auth::token()){
                        $owner->must_change_password = TRUE;
                    }

                    if ($owner->save()) {
                        $lab = O('lab');
                        $lab->name = H($form['name']);
                        $lab->contact = H($form['lab_contact']);
                        $lab->owner = $owner;
                        if ($lab->save()) {
                            $owner->save();
                            $owner->connect($lab);
                            $owner->connect($lab, 'pi');
                            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('labs', '实验室信息注册成功!请耐心等待管理员审核!'));
                        }
                    }
                    else {
                        !$auth->is_readonly() and $auth->remove();
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('labs', '添加实验室管理员失败! 请与系统管理员联系。'));
                        throw new Error_Exception;
                    }


                    //同步远程课题组
                    if($form['local_remote_group'] == 'remote_group'){

                        $remote_lab_group = $_SESSION['remote_lab_group'];

                        $parent_tag = $group_root;
                        foreach ($remote_lab_group as $g) {
                            $g_tag = O('tag_group', ['parent'=>$parent_tag, 'root'=>$group_root, 'name'=>$g]);
                            if(!$g_tag->id){
                                $g_tag->parent = $parent_tag;
                                $g_tag->root = $group_root;
                                $g_tag->name = $g;
                                $g_tag->save();
                            }
                            $parent_tag = $g_tag;
                        }

                        $group = $parent_tag;
                    }
                    else {
                        $group = O('tag_group', $form['group_id']);
                    }


                    if ($group->root->id && $group->root->id == $group_root->id) {
                        $lab->group = $group;
                        $lab->save();
                        self::_groupConnect($lab, $group);
                        $owner->group = $group;
                        $owner->save();
                        self::_groupConnect($owner, $group);
                    }
                    else {
                        $lab->group = $group_root;
                        $lab->save();
                        $lab->connect($group_root);
                    }
                    $types = Lab_Project_Model::$types;
                    foreach ($types as $type => $name) {
                        $projects = (array)$form['project'][$type];
                        foreach ($projects as $key => $item) {
                            if (empty($item['name'])){
                                continue;
                            }
                            $project = O('lab_project');
                            $project->lab = $lab;
                            $project->type = (int)$type;
                            $project->name = H($item['name']);
                            $project->dtstart = min($item['dtstart'], $item['dtend']);
                            $project->dtend = max($item['dtstart'], $item['dtend']);
                            switch($type){
                                case Lab_Project_Model::TYPE_EDUCATION:
                                    $project->student_count = H($item['student_count']);
                                    $project->textbook = H($item['textbook']);
                                    $project->book_type = H($item['book_type']);
                                    break;
                                case Lab_Project_Model::TYPE_RESEARCH:
                                case Lab_Project_Model::TYPE_SERVICE:
                                    $project->description = H($item['description']);
                                    break;
                            }
                            $project->save();
                        }
                    }

                    Auth::login($owner->token);
                    URI::redirect('/');

                }
                catch (Error_Exception $e) {
                }
            }

        }

        $this->layout->body = V('labs:signup/signup_lab', ['form' => $form]);
    }

    private static function _groupConnect($object, $group) {
        while ($group->root->id && $group->parent->id != $group->root->id) {
            $object->connect($group);
            $group = $group->parent;
        }
        if ($group->root->id && $group->parent->id == $group->root->id) {
            $object->connect($group);
        }
    }
}


class Signup_AJAX_Controller extends AJAX_Controller {
    function index_get_remote_lab_click(){
        $token = Auth::token();
        if (!$token) {
            $form = Input::form();
            $token = $form['token'];
        }

        //进行trigger, 获取不同的用户信息
        $lab_info = Event::trigger('labs.get_remote_lab', $token);
        $lab_group = $lab_info['group'];
        $_SESSION['remote_lab_group'] = $lab_group;
        $user_info = Event::trigger('labs.get_remote_user', $token);
        $_SESSION['signup_user_info'] = $_SESSION['signup_user_info'] ?: $user_info;

        if (!$lab_info) {
            preg_match('/(.*)%([^%].*)$/', $token, $parts);

            $username = $parts[1];    // genee|database
            $source = $parts[2];    // yiqi.tju

            $servers = Config::get('rpc.servers');
            $url = $servers[$source]['api'];
            $root_tag = Tag_Model::root('group');

            if($username && $url) {
                $rpc = new RPC($url);

                $local_privkey = Config::get('rpc.private_key');
                $remote_pubkey = $servers[$source]['public_key'];
                $local_server = Config::get('rpc.hostname');
                $SSL = new OpenSSL();

                //获取随机数
                $random = @openssl_random_pseudo_bytes('20');
                //随机数用远程服务器公钥加密
                $encrypted_by_remote_pubkey = $SSL->encrypt($random, $remote_pubkey, 'public');
                //随机数使用本地私钥签名
                $signed_by_local_prikey = $SSL->sign($random, $local_privkey);


                if ($rpc->server->auth($local_server, base64_encode($signed_by_local_prikey), base64_encode($encrypted_by_remote_pubkey))) {
                    $lab_info = $rpc->lab->get_lab($username);
                    $user_info = $rpc->people->get_user($username, ['name', 'email', 'phone']);
                    $projects = $lab_info['projects'];
                    $lab_group = $lab_info['group'];
                    $_SESSION['remote_lab_group'] = $lab_group;

                    //如果是用户在注册个人信息页面跳转过来，则应该显示用户在个人注册信息页面填写的信息
                    if($signup_user_info = $_SESSION['signup_user_info']){
                        $user_info['name'] = $signup_user_info['name'] ?: $user_info['name'];
                        $user_info['email'] = $signup_user_info['email'] ?: $user_info['email'];
                        $user_info['phone'] = $signup_user_info['phone'] ?: $user_info['phone'];

                    }
                }
            }
        }

        if(count($projects)){
            Output::$AJAX['div.projects'] = ['data'=>(string)V('labs:signup/remote_info/lab_projects', [
                                                        'projects'=> $projects,
                                                    ]),
                                                    'mode'=>'replace'];
        }


        //组织机构
        if($lab_info){
            $form_config = Config::get('form.lab_signup');
            Output::$AJAX['tr.lab_group'] = ['data'=>(string)V('labs:signup/remote_info/lab_group', ['group'=>$lab_group, 'form_config'=>$form_config]),
                                                    'mode'=>'replace'];

        }
        else{
            Output::$AJAX['div.remote_group'] =  ['data'=>(string)Widget::factory('application:tag_selector', [
                                    'root'=>$root_tag,
                                    'name'=>'group_id',
                                    'ajax'=>true,
                                    ]),
                                    'mode'=>'replace'];
        }


        Output::$AJAX['lab_info'] = $lab_info;
        Output::$AJAX['user_info'] = $user_info;

    }

    function index_get_remote_user_click(){

        $token = Auth::token();
        $root_tag = Tag_Model::root('group');

        //进行trigger, 获取不同的用户信息
        $user_info = Event::trigger('labs.get_remote_user', $token);

        if (!$user_info) {

            preg_match('/(.*)%([^%].*)$/', $token, $parts);

            $username = $parts[1];    // genee|database
            $source = $parts[2];    // yiqi.tju

            $servers = Config::get('rpc.servers');
            $url = $servers[$source]['api'];

            if($username && $url) {
                $rpc = new RPC($url);

                $local_privkey = Config::get('rpc.private_key');
                $remote_pubkey = $servers[$source]['public_key'];
                $local_server = Config::get('rpc.hostname');
                $SSL = new OpenSSL();

                //获取随机数
                $random = @openssl_random_pseudo_bytes('20');
                //随机数用远程服务器公钥加密
                $encrypted_by_remote_pubkey = $SSL->encrypt($random, $remote_pubkey, 'public');
                //随机数使用本地私钥签名
                $signed_by_local_prikey = $SSL->sign($random, $local_privkey);


                if ($rpc->server->auth($local_server, base64_encode($signed_by_local_prikey), base64_encode($encrypted_by_remote_pubkey))) {

                    $user_info = $rpc->people->get_user($username);
                }
            }
        }

        $user_group = $user_info['group'];

        // 西交利物浦要求不显示远程组织机构
        if (Config::get('people.show_remote_group', true)) {
            $_SESSION['remote_user_group'] = $user_group;
        }

		$members_type = [];
		foreach(User_Model::get_members() as $key => $value){
			$members_type[I18N::T('people', $key)] = $value;
		}

		//性别
		Output::$AJAX['span.gender'] = ['data'=>(string)V('labs:signup/remote_info/gender', ['gender'=>$user_info['gender']]),
													'mode'=>'replace'];
		//成员类型
		Output::$AJAX['span.member_type'] = ['data'=>(string)V('labs:signup/remote_info/member_type', ['member_type'=>$user_info['member_type']]),
													'mode'=>'replace'];
		/**
         * 组织机构 
         * 用户信息中远程组织机构为空，就从本地组织机构中选 for 山东农业大学
         * 接入统一身份认证但是不想更新远程组织机构 (层级不对或保持组织树干净) 加个配置 for 西交利物浦大学
         */
		if($user_info && $user_group && Config::get('people.show_remote_group', true)){
			$user_group_id = Input::form('user_group_id');
			$form_config = Config::get('form.user_signup');
			Output::$AJAX['tr.group'] = [
                'data' => (string)V('labs:signup/remote_info/user_group', [
                    'group'=>$user_group, 
                    'user_group_id'=>$user_group_id, 
                    'form_config'=>$form_config
                ]),
                'mode'=>'replace'
            ];
		}
		else{
			Output::$AJAX['div.remote_group'] =  ['data'=>(string)Widget::factory('application:tag_selector', [
									'root'=>$root_tag,
									'name'=>'group_id',
									'ajax'=>true,
									]),
									'mode'=>'replace'];
		}

		//课题组
		if($user_info['lab_owner']) {
			Output::$AJAX['tr.lab'] = ['data'=>(string)V('labs:signup/remote_info/lab'),
												'mode'=>'replace'];
		}
		elseif($user_info['lab_local']) {
			Output::$AJAX['tr.lab'] = ['data'=>(string)V('labs:signup/remote_info/lab_local', ['lab_local' => $user_info['lab_local']]),
												'mode'=>'replace'];

		}
		$_SESSION['user_is_lab_owner'] = $user_info['lab_owner'];
		$_SESSION['user_roles'] = $user_info['roles'];
        $_SESSION['user_atime'] = $user_info['atime'];
        $_SESSION['card_no'] = $user_info['card_no'];

        Output::$AJAX['user_info'] = $user_info;
    }


    //尝试登录
    function index_try_login_click(){
        $token = Input::form('token');
        $passwd = Input::form('passwd');

        if (Auth::logged_in()) {
            URI::redirect('/');
        }

        $auth = new Auth($token);
        if($auth->verify($passwd)){
            Auth::login($token);
            JS::refresh();
        }

        Output::$AJAX = false;
    }

    function index_info_to_session_click(){
        $data = Input::form('data');
        $_SESSION['signup_user_info'] = $data;
    }

    function index_lab_to_register_click() {
        $data = Input::form('data');
        $_SESSION['signup_lab_info'] = $data;
    }

    //when register new user, people should read the announcement first
    function index_new_user_register_click() {
        JS::dialog(V('labs:signup/introduction', [ 'signup_object' => Input::form('signup_object') ]), [
            'width' => '600px',
            'title' => I18N::T('people', Lab::get('people.signup.title', Config::get('people.signup.title'))),
        ]);
    }

    function index_new_lab_register_click() {
        JS::dialog(V('labs:signup/lab_introduction', [ 'signup_object' => Input::form('signup_object') ]), [
            'width' => '600px',
        ]);
    }

    //submit after user finishes reading registeration announcement
    function index_new_user_register_submit() {
        $form = Input::form();

        $_SESSION['signup_register_user_type'] = intval($form['register_user_type']);

        if ($form['has_read'] == 'on' || $_SESSION['has_read_registeration_announcement']) {

            $_SESSION['has_read_registeration_announcement'] = TRUE;

            $current_url = $_SESSION['system.current_layout_url'];
            if ($current_url == '!labs/signup' || $form['signup_object'] == 'lab') {
                JS::close_dialog();
            }
            else {
                JS::redirect('!labs/signup');
            }
        }
    }

    function index_new_lab_register_submit() {
        $form = Input::form();

        if ($form['has_read'] == 'on' || $_SESSION['has_read_registeration_announcement']) {

            $_SESSION['has_read_registeration_announcement'] = TRUE;

            $current_url = $_SESSION['system.current_layout_url'];
            if ($current_url == '!labs/signup/lab' || $form['signup_object'] == 'lab') {
                JS::close_dialog();
            }
            else {
                JS::redirect('!labs/signup/lab');
            }
        }
    }

    function index_backend_change() {
        $form = Input::form();
        $backends = Config::get('auth.backends');
        $backend = $form['backend'];
        $scope = $backends[$backend]['rpc.scope'];
        if (in_array('lab', (array)$scope)) {
            Output::$AJAX['tr.info_sync'] = ['data'=>(string)V('labs:signup/remote_info/info_sync_btn'),
                                        'mode'=>'replace'];
        }
        else {
            Output::$AJAX['tr.info_sync'] = ['data'=>(string)'<tr class="info_sync"></tr>',
                                        'mode'=>'replace'];
        }
    }

    function index_check_register_lab_values_click(){
        $form = Form::filter(Input::form());

        $form_config = Config::get('form.lab_signup');
        $requires = (array)$form_config['requires'];

        switch ($form['current_step']) {
            case '1':
                if (!Auth::token()) {
                    if ($requires['pi_token']) {
                        $form->validate('pi_token', 'not_empty', I18N::T('labs', '请填写管理员账号!'));
                    }
                    if ($form['pi_token']) {
                        $form->validate('pi_token', 'is_token', I18N::T('labs', '请填写符合规则的登录帐号!'));
                    }

                    $form->validate('pi_token_backend', 'not_empty', I18N::T('labs', '请选择验证后台!'));
                    if ($form['pi_token_backend'] && !$this->_validate_token_backend($form['pi_token_backend'])) {
                        $form->set_error('pi_token_backend', '验证后台不合法, 若多次出现该错误请尝试清除浏览器缓存');
                    }

                    $token = $form['pi_token'] . '|' . $form['pi_token_backend'];
                    if ($form['pi_token'] && O('user', ['token' => $token])->id) {
                        $form->set_error('pi_token', I18N::T('labs', '您填写的管理员账号系统中已存在!'));
                    }

                    $backends = (array) Config::get('auth.backends');
                    $backend = $form['pi_token_backend'] ?: Config::get('auth.default_backend');

                    if (!$backends[$backend]['readonly']) {
                        if ( $requires['passwd'] && !$form['passwd'] ) {
                            $form->set_error('passwd', I18N::T('labs', '请填写管理员密码!'));
                        }

                        if (!preg_match('/(?=^.{8,24}$)(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['passwd'])) {
                            $form->set_error('passwd', I18N::T('labs', '密码必须包含大写字母、小写字母和数字!'));
                        }

                        $form->validate('passwd', 'length(8,24)', I18N::T('labs', '填写的密码不能小于8位, 最长不能大于24位!'));
                    }
                }
                if ($requires['pi_name']) {
                    $form->validate('pi_name', 'not_empty' , I18N::T('labs', '请填写管理员姓名!'));
                }

                if ($requires['pi_phone']) {
                    $form->validate('pi_phone', 'not_empty', I18N::T('labs', '请填写管理员联系电话!'));
                }

                if ($requires['pi_email']) {
                    $form->validate('pi_email', 'not_empty', I18N::T('labs', '请填写管理员邮箱!'));
                }

                if ($form['pi_email']) {
                    $form->validate('pi_email', 'is_email', I18N::T('labs', '管理员Email填写有误!'));
                }

                if ($form['pi_email'] && O('user', ['email' => $form['pi_email']])->id) {
                    $form->set_error('pi_email', I18N::T('labs', '您填写的管理员邮箱系统中已存在!'));
                }
                break;
            case '2':
                if ($requires['name']) {
                    $form->validate('name', 'not_empty', I18N::T('labs', '请填写实验室名称！'));
                }
                if ($requires['lab_contact']) {
                    $form->validate('lab_contact', 'not_empty', I18N::T('labs', '请填写联系方式!'));
                }
                if ($requires['group_id']) {
                    $group_root = Tag_Model::root('group');
                    if($form['local_remote_group'] != 'remote_group'){
                        $group = O('tag_group', $form['group_id']);
                        if (!$group->id || $group->root->id != $group_root->id) {
                            $form->set_error('group_id', I18N::T('labs', '请选择组织机构！'));
                        }
                    }
                }
                break;
            default:
                $form->set_error('other', I18N::T('labs', '违规操作！'));
                break;
        }

        Output::$AJAX['no_error'] = $form->no_error;
        if (!$form->no_error) Output::$AJAX['result'] = (string)V('labs:signup/signup_lab', ['form' => $form]);

    }

    function index_check_register_user_values_click(){
        $form = Form::filter(Input::form());

        $requires = Lab_Model::register_require_fields();

        $requires = new ArrayIterator($requires);
        Event::trigger('signup.validate_requires', $requires, $form);
        $group_root = Tag_Model::root('group');

        switch ($form['current_step']) {
            case '1':
                $verified_token = Auth::token();

                $token = $verified_token ? : Auth::make_token(trim($form['token']), trim($form['token_backend']));
                $auth = new Auth($token);
                array_walk($requires, function($v, $k) use($form, $user, $group_root, $auth, $token) {
                    if ($v) switch ($k) {
                        case 'token':
                            if ($form['token']) {
                                $form->validate('token', 'is_token', I18N::T('people', '请填写符合规则的登录帐号!'));

                                if(O('user', ['token'=>$token])->id) {
                                    $form->set_error('token', I18N::T('labs', '您填写的登录帐号在系统中已存在!'));
                                }

                                if (User_Model::is_reserved_token($form['token']) || User_Model::is_reserved_token($token)) {
                                    $form->set_error('token', I18N::T('people', '您填写的帐号已被管理员保留。'));
                                }
                            }
                            elseif (!$form['token']) {
                                $form->set_error('token', I18N::T('labs', '请填写登录帐号!'));
                            }
                            break;
                        case 'backend':
                            $form->validate('backend', 'not_empty', I18N::T('people', '请选择验证后台!'));
                            if ($form['backend'] && !$this->_validate_token_backend($form['backend'])) {
                                $form->set_error('backend', '验证后台不合法, 若多次出现该错误请尝试清除浏览器缓存');
                            }
                            $auth_backends = Config::get('auth.backends');
                            if ($form['backend'] && !$auth_backends[$form['backend']]['readonly']) {
                                $form
                                    ->validate('passwd', 'not_empty', I18N::T('people', '密码不能为空！'))
                                    ->validate('passwd', 'compare(==confirm_passwd)', I18N::T('people', '两次填写密码不一致!'))
                                    ->validate('passwd', 'length(8, 24)', I18N::T('people', '填写的密码不能小于8位, 最长不能大于24位!'));
                            }
                            break;
                        case 'token_backend':
                            $form->validate('token_backend', 'not_empty', I18N::T('people', '请选择验证后台!'));
                            if ($form['token_backend'] && !$this->_validate_token_backend($form['token_backend'])) {
                                $form->set_error('token_backend', '验证后台不合法, 若多次出现该错误请尝试清除浏览器缓存');
                            }
                            $auth_backends = Config::get('auth.backends');
                            if ($form['token_backend'] && !$auth_backends[$form['token_backend']]['readonly']) {
                                $form
                                    ->validate('passwd', 'not_empty', I18N::T('people', '密码不能为空！'))
                                    ->validate('passwd', 'compare(==confirm_passwd)', I18N::T('people', '两次填写密码不一致!'))
                                    ->validate('passwd', 'length(8, 24)', I18N::T('people', '填写的密码不能小于8位, 最长不能大于24位!'));
                            }
                            break;
                        case 'passwd':
                            if ( !$auth->is_readonly() ) {
                                $form->validate('passwd', 'not_empty', I18N::T('people', '密码不能为空!'));
                                if (!preg_match('/(?=(?:.*?\d){1})(?=.*[a-z])(?=(?:.*?[A-Z]){1})(?!.*\s)[0-9a-zA-Z!@#.,$%*()_+^&]*$/', $form['passwd'])) {
                                    $form->set_error('passwd', I18N::T('people', '密码必须包含大写字母、小写字母和数字!'));
                                }
                                $form->validate('passwd', 'length(8,24)', I18N::T('people', '填写的密码不能小于8位, 最长不能大于24位!'));
                            }
                            break;
                        case 'confirm_passwd':
                            if ( !$auth->is_readonly()) {
                                $form->validate('confirm_passwd', 'compare(==passwd)', I18N::T('people', '请填写有效密码并确保两次填写的密码一致!'));
                            }
                            break;
                        default:
                            break;
                    }
                });
                break;
            case '2':
                array_walk($requires, function($v, $k) use($form, $user, $group_root, $auth, $token) {
                    if ($v) switch ($k) {
                        case 'name':
                            $form->validate('name', 'not_empty', I18N::T('people', '请填写用户姓名!'));
                            break;
                        case 'member_type':
                            if ($form['member_type'] < 0) $form->set_error('member_type', I18N::T('people', '请选择人员类型!'));
                            break;
                        case 'lab_id':
                            if ( !O('lab',$form['lab_id'])->id) {
                                $form->set_error('lab_id', I18N::T('labs', '请填写实验室!|:signup'));
                            }
                            break;
                        case 'group_id':
                            if(!isset($form['local_remote_group']) || $form['local_remote_group'] != 'remote_group') {
                                $group = O('tag_group', $form['group_id']);
                                if (!$group->id || $group->root->id != $group_root->id) {
                                    $form->set_error('group_id', I18N::T('people', '请选择组织机构!'));
                                }
                            }
                            break;
                        default:
                            break;
                    }
                });
                Event::trigger('signup.validate_extra_field', $requires, $form, $user);
                break;
            default:
                $form->set_error('other', I18N::T('labs', '违规操作！'));
                break;
        }

        Output::$AJAX['no_error'] = $form->no_error;
        if (!$form->no_error) Output::$AJAX['result'] = (string)V('signup/signup',['group_root'=>$group_root, 'form'=>$form]);

    }

    private function _validate_token_backend($backend) {
        $backends = Config::get('auth.backends');
        return in_array(trim($backend), array_keys($backends));
    }
}
