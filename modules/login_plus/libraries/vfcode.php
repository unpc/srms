<?php
class VfCode {
    static function buttons($mode = 'login') {
        $panel_buttons = new ArrayIterator;
        $panel_buttons = [
            'sync' => [
                'url' => '#',
                'text' => I18N::T('nfs_share', '刷新'),
                'extra' => 'class="button button_refresh"
                    q-object="verification_code" q-event="click"
                    q-src="'. URI::url('!login_plus/vfcode/index') . '"
                    q-static="' . H(['mode'=>$mode]) . '"',
            ]
        ];

        return (array) $panel_buttons;
    }

    static function login_form_extra($e, $form) {
        if (Config::get('vfcode.switch', false)) {
            if (!$_SESSION['vfcode']) {
                $vfcode_id = 'vfcode_' . uniqid();
                $_SESSION['vfcode'] = [
                    'id' => $vfcode_id,
                ];
            }
            $code = VfCode::generate();
            $_SESSION['vfcode']['code'] = $code;
            $image_size = Config::get('vfcode.config')['image_size'] ?: 22;
            $extra = 'style="height: ' . $image_size . 'px"';

            $e->return_value = V('login_plus:vfcode/login', [
                'extra' => 'style="height: 22px;"',
                'form' => $form
            ]);
        }
    }


    static function recovery_form_extra($e) {
        if (!$_SESSION['vfcode']) {
            $vfcode_id = 'vfcode_' . uniqid();
            $_SESSION['vfcode'] = [
                'id' => $vfcode_id,
            ];
        }
        $code = VfCode::generate();
        $_SESSION['vfcode']['code'] = $code;
        $image_size = Config::get('vfcode.config')['image_size'] ?: 22;
        $extra = 'style="height: ' . $image_size . 'px"';

        $e->return_value .= V('login_plus:vfcode/recovery', [
                'extra' => 'style="height: 22px;"',
            ]);
    }

    static function login_form_submit($e, $form) {
        if (Config::get('vfcode.switch') && !self::_check_vfcode($form['vfcode'])) {
            $form->set_error('vfcode', I18N::T('vfcode', '验证码不正确！'));
        }
    }

    private static function _check_vfcode($code) {
        return strtoupper($_SESSION['vfcode']['code']) == strtoupper($code);
    }

    static function signup_form_extra($e, $requires, $form, $user)
    {
        if (Config::get('vfcode.signup_email_switch') && isset($form['email_vfcode'])) {
            if ($_SESSION['SIGNUP_EMAIL_VFCODE']['email'] != $form['email']) {
                $form->set_error('email', I18N::T('login_plus', '电子邮箱与接收验证码的电子邮箱不一致！'));
            }
            if (!self::_check_signup_email_vfcode($form['email_vfcode'])) {
                $form->set_error('email_vfcode', I18N::T('login_plus', '电子邮箱验证码不正确！'));
            }
        }
        if (Config::get('vfcode.signup_phone_switch') && isset($form['phone_vfcode']) && !Event::trigger('login_plus.not.vfcode.phone', $user, $form)) {
            if ($_SESSION['SIGNUP_PHONE_VFCODE']['phone'] != $form['phone']) {
                $form->set_error('phone', I18N::T('login_plus', '联系电话与接收验证码的联系电话不一致！'));
            }
            if (!self::_check_signup_phone_vfcode($form['phone_vfcode'])) {
                $form->set_error('phone_vfcode', I18N::T('login_plus', '联系电话验证码不正确！'));
            }
        }
    }

    static function signup_lab_form_extra($e, $requires, $form, $user)
    {
        if (Config::get('vfcode.signup_email_switch') && isset($form['email_vfcode'])) {
            if ($_SESSION['SIGNUP_EMAIL_VFCODE']['email'] != $form['pi_email']) {
                $form->set_error('pi_email', I18N::T('login_plus', '电子邮箱与接收验证码的电子邮箱不一致！'));
            }
            if (!self::_check_signup_email_vfcode($form['email_vfcode'])) {
                $form->set_error('email_vfcode', I18N::T('login_plus', '电子邮箱验证码不正确！'));
            }
        }
        if (Config::get('vfcode.signup_phone_switch') && isset($form['phone_vfcode']) && !Event::trigger('login_plus.not.vfcode.phone', $user, $form)) {
            if ($_SESSION['SIGNUP_PHONE_VFCODE']['phone'] != $form['pi_phone']) {
                $form->set_error('pi_phone', I18N::T('login_plus', '联系电话与接收验证码的联系电话不一致！'));
            }
            if (!self::_check_signup_phone_vfcode($form['phone_vfcode'])) {
                $form->set_error('phone_vfcode', I18N::T('login_plus', '联系电话验证码不正确！'));
            }
        }
    }

    private static function _check_signup_email_vfcode($code) {
        return strtoupper($_SESSION['SIGNUP_EMAIL_VFCODE']['code']) == strtoupper($code);
    }

    private static function _check_signup_phone_vfcode($code) {
        return strtoupper($_SESSION['SIGNUP_PHONE_VFCODE']['code']) == strtoupper($code);
    }

    static function generate() {
        $captcha_config = Config::get('vfcode.config');
        // Generate CAPTCHA code
        $length = mt_rand($captcha_config['min_length'], $captcha_config['max_length']);
        while( strlen($code) < $length ) {
            $code .= substr($captcha_config['characters'], mt_rand() % (strlen($captcha_config['characters'])), 1);
        }
        return $code;
    }

    static function draw($code) {
        $captcha_config = Config::get('vfcode.config');
        $background = $captcha_config['backgrounds'][mt_rand(0, count($captcha_config['backgrounds']) -1)];
        list($bg_width, $bg_height, $bg_type, $bg_attr) = getimagesize($background);

        $captcha = imagecreatefrompng($background);

        $color = self::_hex2rgb($captcha_config['color']);
        $color = imagecolorallocate($captcha, $color['r'], $color['g'], $color['b']);

        // Determine text angle
        $angle = mt_rand( $captcha_config['angle_min'], $captcha_config['angle_max'] ) * (mt_rand(0, 1) == 1 ? -1 : 1);

        // Select font randomly
        $font = $captcha_config['fonts'][mt_rand(0, count($captcha_config['fonts']) - 1)];

        // Verify font file exists
        if( !file_exists($font) ) throw new Exception('Font file not found: ' . $font);

        //Set the font size.
        $font_size = mt_rand($captcha_config['min_font_size'], $captcha_config['max_font_size']);
        $text_box_size = imagettfbbox($font_size, $angle, $font, $code);

        // Determine text position
        $box_width = abs($text_box_size[6] - $text_box_size[2]);
        $box_height = abs($text_box_size[5] - $text_box_size[1]);
        $text_pos_x_min = 0;
        $text_pos_x_max = ($bg_width) - ($box_width);
        $text_pos_x = mt_rand($text_pos_x_min, $text_pos_x_max);
        $text_pos_y_min = $box_height;
        $text_pos_y_max = ($bg_height) - ($box_height / 2);
        if ($text_pos_y_min > $text_pos_y_max) {
            $temp_text_pos_y = $text_pos_y_min;
            $text_pos_y_min = $text_pos_y_max;
            $text_pos_y_max = $temp_text_pos_y;
        }
        $text_pos_y = mt_rand($text_pos_y_min, $text_pos_y_max);

        // Draw shadow
        if( $captcha_config['shadow'] ){
            $shadow_color = self::_hex2rgb($captcha_config['shadow_color']);
            $shadow_color = imagecolorallocate($captcha, $shadow_color['r'], $shadow_color['g'], $shadow_color['b']);
            imagettftext($captcha, $font_size, $angle, $text_pos_x + $captcha_config['shadow_offset_x'], $text_pos_y + $captcha_config['shadow_offset_y'], $shadow_color, $font, $code);
        }

        // Draw text
        imagettftext($captcha, $font_size, $angle, $text_pos_x, $text_pos_y, $color, $font, $code);

        return $captcha;
    }

    public static function _hex2rgb($hex_str, $return_string = false, $separator = ',') {
        $hex_str = preg_replace("/[^0-9A-Fa-f]/", '', $hex_str); // Gets a proper hex string
        $rgb_array = array();
        if( strlen($hex_str) == 6 ) {
            $color_val = hexdec($hex_str);
            $rgb_array['r'] = 0xFF & ($color_val >> 0x10);
            $rgb_array['g'] = 0xFF & ($color_val >> 0x8);
            $rgb_array['b'] = 0xFF & $color_val;
        } elseif( strlen($hex_str) == 3 ) {
            $rgb_array['r'] = hexdec(str_repeat(substr($hex_str, 0, 1), 2));
            $rgb_array['g'] = hexdec(str_repeat(substr($hex_str, 1, 1), 2));
            $rgb_array['b'] = hexdec(str_repeat(substr($hex_str, 2, 1), 2));
        } else {
            return false;
        }
        return $return_string ? implode($separator, $rgb_array) : $rgb_array;
    }
}