<?php
$config['switch'] = FALSE;
$bg_path = ROOT_PATH . 'modules/login_plus/public/vfcode/backgrounds/';
$font_path = ROOT_PATH . 'modules/login_plus/public/vfcode/fonts/';

$config['bg_path'] = $bg_path;
$config['font_path'] = $font_path;
$config['config'] = [
    'min_length' => 4,
    'max_length' => 4,
    'backgrounds' => [
        $bg_path . '45-degree-fabric.png',
        $bg_path . 'cloth-alike.png',
        $bg_path . 'grey-sandbag.png',
        $bg_path . 'kinda-jean.png',
        $bg_path . 'polyester-lite.png',
        $bg_path . 'stitched-wool.png',
        $bg_path . 'white-carbon.png',
        $bg_path . 'white-wave.png'
    ],
    'fonts' => [
        $font_path . 'times_new_yorker.ttf'
    ],
    'characters' => 'ABCDEFGHJKLMNPRSTUVWXYZabcdefghjkmnprstuvwxyz23456789',
    'min_font_size' => 35,
    'max_font_size' => 35,
    'color' => '#666',
    'angle_min' => 0,
    'angle_max' => 10,
    'shadow' => true,
    'shadow_color' => '#fff',
    'shadow_offset_x' => -1,
    'shadow_offset_y' => 1
];

$config['signup_email_switch'] = FALSE;
$config['signup_email_title'] = '在%system注册的电子邮箱验证码';
$config['signup_email_body'] = '您好!\n\n\t您在%system注册的电子邮箱验证码是%code, 有效期15分钟. \n\n\t%system %system_url\n\n\t(这是一封自动产生的 Email, 请勿回复. )\n';

$config['signup_phone_switch'] = FALSE;
