<?php
$scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?: ($_SERVER['HTTPS'] ? 'https' : 'http');
$host = $scheme . '://' . $_SERVER['HTTP_HOST'];

$config['consumers']['summary.' . LAB_ID] = [
    'title'          => '大数据体系',
    'key'            => '6Y0BH4QE-9XQC-QBBT-MZ9F-CYYODWM4VUJL',
    'secret'         => '3GK357U3-Z3J0-QCZK-E6TG-Z0E2CPHCDEYV',
    'redirect_uri'   => $host . "/summary/oauth/client/auth?source=summary." . LAB_ID,
    'auto_authorise' => true,
];

//各站点需申请oauth对接账号, 申请地址: https://nrii.org.cn/instru_war/oauth2/plat/register.ins
$config['providers']['nrii'] = [
    'title' => '重大科研基础设施和大型科研仪器国家网络管理平台',
    'provider' => 'nrii',
    'client_class' => 'oauth2_nrii',
    'auth_url' => 'http://nrii.org.cn/instru_war/oauth2/authorize.ins',
    'token_url' => 'https://nrii.org.cn/instru_war/oauth2/access_token.ins',
    'user_url' => 'https://nrii.org.cn/instru_war/oauth2/resource/userinfo.ins',
    'key' => '96a6937a-490f-4616-b6b0-25cf8e93094e',//联系 oauth 相关负责人, 取得 clientId 和 clientSecret
    'secret' => 'ZFxI4kkupLvTEwf5O6',
    'hidden'=> TRUE
];