<?php
//登录、找回密码页面加验证码
$config['login.form.extra'][] = 'VfCode::login_form_extra';
$config['recovery.form.extra'][] = 'VfCode::recovery_form_extra';
$config['login.form.submit'][] = 'VfCode::login_form_submit';
$config['recovery.form.submit'][] = 'VfCode::login_form_submit';
//登录错误次数限制
$config['before.auth.verify'][] = 'Login_Attempt::before_auth_verify';
$config['login.field.attempt'][] = 'Login_Attempt::login_field_submit';
$config['login.success.attempt'][] = 'Login_Attempt::login_success_submit';

// RQ163729登录安全提示，当同一账户在同时间登录系统时，前一个使用系统的用户会被登出
$config['auth.login'][] = 'Single_Login::auth_login';
$config['layout_controller_after_call'][] = 'Single_Login::layout_after_call';

// “账号密码明文传输问题”，从前端js加密
$config['login.form.extra'][] = 'Login_Form::login_form_extra';
$config['login.form.submit'][] = 'Login_Form::login_form_submit';
$config['layout_controller_after_call'][] = 'Login_Form::layout_after_call';

// 切换账号
$config['controller[admin/index].ready'][] = 'ManyFaced_God::setup';

// 注册验证邮箱
$config['signup.validate_extra_field'][] = 'VfCode::signup_form_extra';
$config['signup_lab.validate_extra_field'][] = 'VfCode::signup_lab_form_extra';
