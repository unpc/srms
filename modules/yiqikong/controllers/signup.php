<?php

class Signup_Controller extends Layout_Controller {

	function index($uuid = 0) {
        $cache = Cache::factory('redis');
        $token = $cache->get($uuid);
        Auth::login($token);
        URI::redirect('/');
    }

    function register () {
        $rpc_conf = Config::get('rpc.servers')['yiqikong'];
        $url = $rpc_conf['url'];
        $rpc = new RPC($url);
        if (!$rpc->YiQiKong->authorize($rpc_conf['client_id'], $rpc_conf['client_secret'])) {
            throw new RPC_Exception;
        }

        $data = [];
        $data['site'] = SITE_ID;
        $data['lab'] = LAB_ID;
        $data['labs'] = Q('lab[!hidden]')->to_assoc('id', 'name');
        $data['title'] = Config::get('page.title_default');
        $data['color'] = Config::get('page.title_color');
        $data['redirect'] = Config::get('yiqikong_user.redirect', $_SERVER['HTTP_HOST'] . '/lims');

        $uuid = $rpc->YiQiKong->User->access($data);
        URI::redirect($rpc_conf['signup'] . "/{$uuid}");
    }

    // Source的用户，委托进入LIMS并且抓取相对应的用户信息
    function accredit() {
        $form = Input::form();
        $client = LIMS_Accredit::factory($form['source']);

        if (!$client) {
            URI::redirect('error/401');
        }

        $info = $client->get_user_info($form);
        if (!$info) {
            URI::redirect('error/404');
        }

        if ($id = Input::form('equipment_id')) {
            $equipment = O('equipment', $id);
            if (!$equipment->id) {
                $equipment = O('equipment', ['ref_no' => $id]);
            }
            if ($equipment->id) {
                $_SESSION['#LOGIN_REFERER'] = $equipment->url('reserv');
            }
        }

        $user = $client->find_user_by_info($info);

        if ($user->id) {
            Auth::login($user->token);
            if ($user->is_active() && isset($_SESSION['#LOGIN_REFERER'])) {
                $http_referer = $_SESSION['#LOGIN_REFERER'] ?: null;
                unset($_SESSION['#LOGIN_REFERER']);
                if ($http_referer) {
                    URI::redirect($http_referer);
                }
            }
            URI::redirect('/');
        }

        $_SESSION['#ACCREDIT_INFO'] = $info;
        URI::redirect('!yiqikong/signup/bind');
    }

    function bind() {
        if (!$_SESSION['#ACCREDIT_INFO']) {
            URI::redirect('error/401');
        }

        $user = (object)$_SESSION['#ACCREDIT_INFO'];

        $form = Form::filter(Input::form());

        try {
            if ($form['bind']) {
                $token = Auth::make_token(trim($form['bind_token']), trim($form['bind_token_backend']));
                if (!O('user', ['token' => $token])->id) {
                    $form->set_error('bind_token', I18N::T('yiqikong', '账号不存在!'));
                    throw new Error_Exception;
                }
                $auth = new Auth($token);
                if (!$auth->verify($form['bind_password'])) {
                    $form->set_error('bind_password', I18N::T('yiqikong', '密码不匹配!'));
                    throw new Error_Exception;
                }
                if ($form->no_error) {
                    $bind_user = O('user', ['token' => $token]);
                }
            }

            if ($form['submit']) {

                $form
                    ->validate('name', 'not_empty', I18N::T('yiqikong', '姓名不能为空！'))
                    ->validate('phone', 'not_empty', I18N::T('yiqikong', '电话不能为空！'));

                if ($form['token']) {
                    $form->validate('token', 'is_token', I18N::T('yiqikong', '请填写符合规则的登录帐号!'));

                    $token = Auth::make_token(H($form['token']), 'database');

                    if(O('user', ['token'=>$token])->id) {
                        $form->set_error('token', I18N::T('yiqikong', '您填写的登录帐号在系统中已存在!'));
                    }

                    if (User_Model::is_reserved_token($form['token']) || User_Model::is_reserved_token($token)) {
                        $form->set_error('token', I18N::T('yiqikong', '您填写的帐号已被管理员保留。'));
                    }
                }
                elseif (!$form['token']) {
                    $form->set_error('token', I18N::T('yiqikong', '请填写登录帐号!'));
                }

                if ($form['email']) {
                    $form->validate('email', 'is_email', I18N::T('yiqikong', 'Email填写有误!'));
                    $exist_user = O('user', ['email'=>$form['email']]);
                    if ($exist_user->id ) {
                        $form->set_error('email', I18N::T('yiqikong', '您填写的电子邮箱在系统中已经存在!'));
                    }
                }
                else {
                    $form->validate('email', 'not_empty', I18N::T('people', 'Email不能为空!'));
                }

                if ($form->no_error) {
                    $user = O('user');
                    $user->token = $token;
                    $user->email = H($form['email']);
                    $user->name = H($form['name']);
                    $user->organization = $form['organization'];
                    $user->gender = $form['gender'];
                    $user->phone = H($form['phone']);
                    $user->address = H($form['address']);

                    $group = O('tag_group', $form['group_id']);
                    $user->group = $group;
                    $user->atime = $_SESSION['atime'] ? Date::time() : 0;

                    if ($user->save()) {
                        $group->connect($user);
                        $bind_user = $user;
                    }
                }
            }

            if ($bind_user->id) {
                $bind_user->gapper_id = $_SESSION['#ACCREDIT_INFO']['gapper_id'];
                $bind_user->remote_id = $_SESSION['#ACCREDIT_INFO']['remote_id'];
                $bind_user->save();
                Auth::login($bind_user->token);
                
                unset($_SESSION['#ACCREDIT_INFO']);
                Log::add(strtr('[application] %user_name[%user_id]登录系统成功', [
                            '%user_name' => $bind_user->name,
                            '%user_id' => $bind_user->id,
                ]), 'logon');

                Log::add(strtr('[application] %user_name[%user_id]登录系统成功', [
                            '%user_name' => $bind_user->name,
                            '%user_id' => $bind_user->id,
                ]), 'journal');

                if ($bind_user->is_active() && isset($_SESSION['#LOGIN_REFERER'])) {
                    $http_referer = $_SESSION['#LOGIN_REFERER'] ?: null;
                    unset($_SESSION['#LOGIN_REFERER']);
                    if ($http_referer) {
                        URI::redirect($http_referer);
                    }
                }

                URI::redirect('/');
            }
        }
        catch (Error_Exception $e) {
        }

        $this->layout = V('layout_plain', [
            'body' => V('signup/bind', [
                'form' => $form,
                'user' => $user
            ])
        ]);
    }
}
