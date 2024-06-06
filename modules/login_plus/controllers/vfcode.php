<?php
class VfCode_Controller extends Controller
{
    function index()
    {
        Event::trigger('login.form.extra');
        $captcha = VfCode::draw($_SESSION['vfcode']['code']);
        header('Content-type: image/png');
        imagepng($captcha);
        exit;
    }
}
class VfCode_AJAX_Controller extends AJAX_Controller
{
    function index_verification_code_click()
    {
        $vfcode_id = $_SESSION['vfcode']['id'];
        if ($vfcode_id) {
            $mode = Form::filter(Input::form())['mode'];
            $code = VfCode::generate();
            $_SESSION['vfcode']['code'] = $code;
            $image_size = Config::get('vfcode.config')['image_size'] ?: 22;
            $extra = 'style="height: ' . $image_size . 'px"';
            switch ($mode) {
                case 'recovery':
                    $data = (string) V('login_plus:vfcode/recovery_form', [
                        'extra' => $extra,
                    ]);
                    break;
                case 'login':
                default:
                    $data = (string) V('login_plus:vfcode/login', [
                        'extra' => $extra,
                    ]);
                    break;
            }
            Output::$AJAX["#" . $vfcode_id] = [
                'data' => $data,
                'mode' => 'replace',
            ];
        } elseif (JS::confirm(I18N::T('equipments', '操作超时, 请刷新页面后重试!'))) {
            JS::refresh();
        }
    }

    public function index_email_vfcode_btn_click()
    {
        $form = Form::filter(Input::form());
        $uniqid = $form['email_vfcode_uniqid'];
        if (!$form['email_vfcode_uniqid']) {
            $uniqid = uniqid();
        }

        $form->email_vfcode = true;

        $form->validate($form['key'], 'not_empty', I18N::T('people', 'Email不能为空!'));
        $form->validate($form['key'], 'is_email', I18N::T('labs', 'Email填写有误!'));
        if ($form->no_error) {

            $_SESSION['SIGNUP_EMAIL_VFCODE'] = [
                'code' => VfCode::generate(),
                'email' => $form[$form['key']],
                'expireat' => strtotime("+15 min"),
                'resend_timeout' => strtotime("+1 min"),
            ];

            $mail = new Email();
            $mail->to($form[$form['key']]);
            $mail->subject(I18N::T('login_plus', Config::get("vfcode.signup_email_title"), ['%system' => Config::get('system.email_name')]));
            $mail->body(I18N::T('people', Config::get('vfcode.signup_email_body'), [
                '%system' => Config::get('system.email_name'),
                '%system_url' => URI::url("/"),
                '%code' => $_SESSION['SIGNUP_EMAIL_VFCODE']['code']
            ]));
            if ($mail->send()) {
                Log::add(strtr('[signup] 向%email 发送了注册验证电子邮箱邮件, 验证码%code', ['%email' => $form[$form['key']], '%code' => $_SESSION['SIGNUP_EMAIL_VFCODE']['code']]), 'journal');
            }
        }
        Output::$AJAX["#{$uniqid}"] = [
            'data' => (string)V('login_plus:vfcode/signup/email', [
                'form' => $form,
                'key' => $form['key'],
                'uniqid' => H($uniqid)
            ]),
            'mode' => 'replace',
        ];
    }

    public function index_phone_vfcode_btn_click()
    {
        $form = Form::filter(Input::form());
        $uniqid = $form['phone_vfcode_uniqid'];
        if (!$form['phone_vfcode_uniqid']) {
            $uniqid = uniqid();
        }

        $form->phone_vfcode = true;

        $form->validate('phone', 'not_empty', I18N::T('people', '联系电话不能为空!'));
        $form->validate('phone', 'is_phone', I18N::T('labs', '联系电话填写有误!'));
        if ($form->no_error) {

            if (!$_SESSION['SIGNUP_PHONE_VFCODE']) {
                $_SESSION['SIGNUP_PHONE_VFCODE'] = [
                    'code' => VfCode::generate(),
                    'phone' => $form['phone'],
                    'expireat' => strtotime("+15 min"),
                    'resend_timeout' => strtotime("+1 min")
                ];
            } else {
                $_SESSION['SIGNUP_PHONE_VFCODE']['expireat'] = strtotime("+15 min");
                $_SESSION['SIGNUP_PHONE_VFCODE']['resend_timeout'] = strtotime("+1 min");
                $_SESSION['SIGNUP_PHONE_VFCODE']['phone'] = $form['phone'];
            }
            $sms_content = "尊敬的用户, 您正在进行登录注册操作, 验证码是 ". $_SESSION['SIGNUP_PHONE_VFCODE']['code']." （15分钟内有效）, 如非本人操作,请忽略本短信";
            $config = Config::get('sms');
            $handlerName = 'Provider_Sms_' . ($config['provider'] ?? 'common');
            $handler = new $handlerName($config);
            $handler->send($form['phone'], $sms_content);
            Log::add(strtr('[signup] 向%phone 发送了注册验证联系电话短信, 验证码%code', ['%phone' => $form['phone'], '%code' => $_SESSION['SIGNUP_PHONE_VFCODE']['code']]), 'journal');
        }
        Output::$AJAX["#{$uniqid}"] = [
            'data' => (string)V('login_plus:vfcode/signup/phone', [
                'form' => $form,
                'key' => $key,
                'uniqid' => H($uniqid)
            ]),
            'mode' => 'replace',
        ];
    }
}
