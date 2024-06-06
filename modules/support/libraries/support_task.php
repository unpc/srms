<?php

class Support_Task
{

    public static function submit($e, $module, $key, $value, $form)
    {
        if (Config::get("hooks.admin.support.submit.{$module}.{$key}")) {
            Event::trigger("admin.support.submit.{$module}.{$key}", $value, $form);
            return false;
        }
        $result = $value == 'on' ? 'TRUE' : 'FALSE';
        self::support_config_set($module, $key, $value == 'on', $result);
    }

    public static function feedback_deadline($e, $value)
    {
        if ($value == 'on') {
            self::support_config_set('equipment', 'feedback_deadline', 1, "1");
        }
        else {
            self::support_config_set('equipment', 'feedback_deadline', -1, "-1");
        }
    }

    public static function feedback_show_samples($e, $value)
    {
        if ($value == 'on') {
            self::support_config_set('equipment', 'feedback_show_samples', true, "TRUE");
        }
        else {
            self::support_config_set('equipment', 'feedback_show_samples', false, "FALSE");
        }
    }

    public static function eq_charge_incharges_fee($e, $value)
    {
        Lab::set('eq_charge.incharges_fee', ($value == 'on' ? true : false));
    }

    static function eq_record_must_connect_lab_project ($e, $value) {
        if ($value == 'on' || $GLOBALS['preload']['people.multi_lab']) {
            self::support_config_set('eq_record', 'must_connect_lab_project', TRUE, "TRUE");
        }
        else {
            self::support_config_set('eq_record', 'must_connect_lab_project', FALSE, "FALSE");
        }
    }

    static function eq_sample_must_connect_lab_project ($e, $value) {
        if ($value == 'on' || $GLOBALS['preload']['people.multi_lab']) {
            self::support_config_set('eq_sample', 'must_connect_lab_project', TRUE, "TRUE");
        }
        else {
            self::support_config_set('eq_sample', 'must_connect_lab_project', FALSE, "FALSE");
        }
    }

    public static function eq_sample_response_time($e, $value)
    {
        Lab::set('eq_sample.response_time', ($value == 'on' ? true : false));
    }

    public static function eq_reserv_glogon_arrival($e, $value)
    {
        Lab::set('eq_reserv.glogon_arrival', ($value == 'on' ? true : false));
    }

    public static function eq_reserv_glogon_safe($e, $value)
    {
        Lab::set('eq_reserv.glogon_safe', ($value == 'on' ? true : false));
    }

    static function eq_reserv_must_connect_lab_project ($e, $value) {
        if ($value == 'on' || $GLOBALS['preload']['people.multi_lab']) {
            self::support_config_set('eq_reserv', 'must_connect_lab_project', TRUE, "TRUE");
        }
        else {
            self::support_config_set('eq_reserv', 'must_connect_lab_project', FALSE, "FALSE");
        }
    }

    public static function transaction_locked_deadline($e, $value)
    {
        $time = date("Y-m-d", $value);
        // 虽然CLI中会set-transaction_locked_deadline.
        // 但为保证页面显示transaction_locked_deadline的时间正常，先在CGI中设置一次transaction_locked_deadline，再起异步CLI处理
		$value = Event::trigger('transaction_locked_deadline.modify', $value) ? : $value;
        Lab::set('transaction_locked_deadline', $value);
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd     = 'echo "' . $time . '" | SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/modify_transaction_locked_deadline.php > /dev/null 2>&1 &';
        $process = proc_open($cmd, [], $pipes);
        $var     = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        return $pid;
    }

    public static function system_logo($e, $value)
    {
        $file = Input::file('system_logo');
        if ($file['tmp_name']) {
            try {
                $ext   = File::extension($file['name']);
                $image = Image::load($file['tmp_name'], $ext);

                $path = LAB_PATH . 'support/public/images/logo.png';
                File::check_path($path);
                $image->save('png', $path);
                Cache::cache_file($path, true);

            } catch (Error_Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '图标更新失败!'));
            }
        }
    }

    public static function system_login_background_image($e, $value)
    {
        $file = Input::file('system_login_background_image');
        if ($file['tmp_name']) {
            try {
                $ext   = File::extension($file['name']);
                $image = Image::load($file['tmp_name'], $ext);

                $path = LAB_PATH . 'support/public/images/lbg.png';
                File::check_path($path);
                $image->save('png', $path);
                Cache::cache_file($path, true);
            } catch (Error_Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '图标更新失败!'));
            }
        }
    }

    public static function system_login_logo($e, $value)
    {
        $file = Input::file('system_login_logo');
        if ($file['tmp_name']) {
            try {
                $ext   = File::extension($file['name']);
                $image = Image::load($file['tmp_name'], $ext);

                $path = LAB_PATH . 'support/public/images/login_logo.png';
                File::check_path($path);
                $image->save('png', $path);
                Cache::cache_file($path, true);

            } catch (Error_Exception $e) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '图标更新失败!'));
            }
        }
    }

    public static function system_header_color($e, $value, $form)
    {
        if ($value == '') {
            return;
        }

        if (preg_match('/[0-9a-fA-F]{6}/', $value)) {
            $content = "#header .header_content { background-color: #" . $value . ";}";
            self::support_css_set('theme', 'header_color', $content);
        } else {
            $form->set_error('system_header_font_color', I18N::T('support', '顶部颜色仅能为000000-ffffff的16进制数！'));
        }
    }

    public static function system_header_phone_color($e, $value, $form)
    {
        if ($value == '') {
            return;
        }

        if (preg_match('/[0-9a-fA-F]{6}/', $value)) {
            $content = "#top_menu .top_menu_contact span.phone {color: #" . $value . ";}";
            $content .= "#top_menu .top_menu_contact strong {color: #" . $value . ";}";
            self::support_css_set('theme', 'header_phone_color', $content);
        } else {
            $form->set_error('system_header_font_color', I18N::T('support', '顶部颜色仅能为000000-ffffff的16进制数！'));
        }
    }

    public static function system_header_height($e, $value, $form)
    {
        if ($value == '') {
            return;
        }

        if (preg_match('/[1-9][0-9]/', $value)) {
            $content = "#header .header_content {height:" . $value . "px;}#top_menu{height:" . $value . "px;}#top_menu .separator{height:" . $value . "px;}";
            self::support_css_set('theme', 'header_height', $content);
        } else {
            $form->set_error('system_header_height', I18N::T('support', '顶部高度仅能为10-99的整数！'));
        }
    }

    public static function system_footer_email($e, $value, $form)
    {
        if ($value == '') {
            return;
        }

        $form = $form->validate('system_footer_email', 'is_email', I18N::T('support', '联系邮箱填写有误！'));
        if ($form->no_error) {
            self::support_config_set('lab', 'help.email', $value, "'" . $value . "'");
        }
    }

    public static function system_header_phone($e, $value)
    {
        self::support_config_set('system', 'customer_service_tel_text', $value, "'" . $value . "'");
    }

    public static function system_header_phone2($e, $value)
    {
        self::support_config_set('system', 'customer_service_tel', $value, "'" . $value . "'");
    }

    public static function system_page_title($e, $value)
    {
        self::support_config_set('page', 'title_default', $value, "'" . $value . "'");
        self::support_config_set('page', 'title_pattern', '%title | ' . $value, "'%title | " . $value . "'");
    }

    public static function preferences_sbmenu_mode($e, $value)
    {
        $value = in_array($value, ['list', 'icon']) ? $value : 'icon';
        self::support_config_set('page', 'sbmenu_mode', $value, "'" . $value . "'");

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php support preferences_sbmenu_mode ' . $value . ' > /dev/null 2>&1 &';

        exec($cmd);
    }

    public static function system_base_url($e, $value)
    {
        if (!$value) {
            return;
        }

        $value_str = "if (defined('CLI_MODE')) { \$config['base_url'] = \$config['script_url'] = '" . $value . "';}\n";

        $config_file = LAB_PATH . 'support/' . CONFIG_BASE . 'system' . EXT;
        File::check_path($config_file);

        // 如果此时配置文件已经存在，则需在此文件基础上修改
        if ($config_content = file_get_contents($config_file)) {
            $configs = explode("\n", $config_content);
            //将旧配置文件中的$value配置一行删除
            foreach ($configs as $k => $v) {
                if (strpos($v, "['base_url']")) {
                    unset($configs[$k]);
                }
            }
            $config_content = join("\n", $configs);
        }
        // 如果此时配置文件不存在，则touch一个新的，注意"<?php"
        else {
            $config_content = "<?php \n";
        }
        $config_content .= $value_str;
        file_put_contents($config_file, $config_content);

    }

    public static function vidmon_capture_duration($e, $value)
    {
        $value = Date::convert_interval($value['value'], $value['format']);
        self::support_config_set('vidmon', 'capture_duration', $value, "'" . $value . "'");
        self::support_config_set('vidmon', 'upload_duration', $value * 3, "'" . ($value * 3) . "'");
    }

    public static function vidmon_alarmed_capture_duration($e, $value)
    {
        $value = Date::convert_interval($value['value'], $value['format']);
        self::support_config_set('vidmon', 'alarmed_capture_duration', $value, "'" . $value . "'");
        self::support_config_set('vidmon', 'alarmed_capture_timeout', $value * 6, "'" . ($value * 6) . "'");
        self::support_config_set('vidmon', 'alarm_capture_time', $value * 6, "'" . ($value * 6) . "'");
    }

    public static function vidmon_capture_max_live_time($e, $value)
    {
        $value = Date::convert_interval($value['value'], $value['format']);
        self::support_config_set('vidmon', 'capture_max_live_time', $value, "'" . $value . "'");
    }

    public static function login_single_login($e, $value)
    {
        Lab::set('login.single_login', ($value == 'on' ? true : false));
    }

    public static function online_kf5($e, $value)
    {
        Lab::set('online.kf5', ($value == 'on' ? true : false));
    }

    public static function sidebar_public($e, $value)
    {
        self::support_config_set('layout', 'public_url', $value, "'" . $value . "'");
    }

    public static function eq_sample_i18n($e, $value)
    {
        self::support_i18n_set('eq_sample', '送样', H($value));
    }

    public static function eq_sample_r_i18n($e, $value)
    {
        self::support_i18n_set('eq_sample', '送样预约', H($value));
    }

    public static function eq_reserv_i18n($e, $value)
    {
        self::support_i18n_set('eq_reserv', '预约', H($value));
    }

    public static function eq_reserv_r_i18n($e, $value)
    {
        self::support_i18n_set('eq_reserv', '仪器预约', H($value));
    }

    public static function undergraduate_i18n($e, $value)
    {
        self::support_i18n_set('people', '本科生', H($value));
    }

    public static function graduate_i18n($e, $value)
    {
        self::support_i18n_set('people', '硕士研究生', H($value));
    }

    public static function doctor_i18n($e, $value)
    {
        self::support_i18n_set('people', '博士研究生', H($value));
    }

    public static function pi_i18n($e, $value)
    {
        self::support_i18n_set('people', '课题负责人(PI)', H($value));
    }

    public static function assistant_i18n($e, $value)
    {
        self::support_i18n_set('people', '科研助理', H($value));
    }

    public static function labadmin_i18n($e, $value)
    {
        self::support_i18n_set('people', 'PI助理/实验室管理员', H($value));
    }

    public static function technician_i18n($e, $value)
    {
        self::support_i18n_set('people', '技术员', H($value));
    }

    public static function postdoctoral_i18n($e, $value)
    {
        self::support_i18n_set('people', '博士后', H($value));
    }

    public static function support_config_set($filename, $key, $value = null, $value_str = null)
    {
        $config_file = LAB_PATH . 'support/' . CONFIG_BASE . $filename . EXT;
        File::check_path($config_file);

        // 如果此时配置文件已经存在，则需在此文件基础上修改
        if ($config_content = file_get_contents($config_file)) {
            $configs = explode("\n", $config_content);
            //将旧配置文件中的$value配置一行删除
            foreach ($configs as $k => $v) {
                if (strpos($v, "['" . $key . "']")) {
                    unset($configs[$k]);
                }
            }
            $config_content = join("\n", $configs);
        }
        // 如果此时配置文件不存在，则touch一个新的，注意"<?php"
        else {
            $config_content = "<?php \n";
        }

        // 根据传入value，写入配置文件
        if ($value !== null) {
            $config_content .= "\$config['" . $key . "'] = " . $value_str . ";\n";
        }
        file_put_contents($config_file, $config_content);

        // 因为Config::setup在此处之前加载，所以做完设置之后Config::get得到的仍然是设置之前的，所以手动set一次
        // Config::load(LAB_PATH . 'support/', $filename);
        Config::set($filename . '.' . $key, $value);
    }

    public static function support_css_set($filename, $key, $value)
    {
        $path = LAB_PATH . 'support/private/css/' . $filename . '.css';
        File::check_path($path);

        if ($content = file_get_contents($path)) {
            $configs = explode("\n", $content);
            foreach ($configs as $k => $v) {
                if (strpos($v, "*" . $key . "*/")) {
                    unset($configs[$k]);
                }
            }
            $content = join("\n", $configs);
        }
        $content .= "/*" . $key . "*/" . $value . "\n";
        file_put_contents($path, $content);
        CSS::cache_content('theme');
    }

    public static function support_i18n_set($modulename, $key, $value = null)
    {

        $config_file = LAB_PATH . 'modules/' . $modulename . '/' . I18N_BASE . Config::get('system.locale', 'zh_CN') . EXT;
        File::check_path($config_file);

        // 如果此时配置文件已经存在，则需在此文件基础上修改
        if ($config_content = file_get_contents($config_file)) {
            $configs = explode("\n", $config_content);
            // 将旧配置文件中的$value配置一行删除
            foreach ($configs as $k => $v) {
                if (strpos($v, "['" . $key . "']")) {
                    unset($configs[$k]);
                }
            }
            $config_content = join("\n", $configs);
        }
        // 如果此时配置文件不存在，则touch一个新的，注意"<?php"
        else {
            $config_content = "<?php \n";
        }

        // 根据传入value，写入配置文件
        if ($value) {
            $config_content .= "\$lang['" . $key . "'] = '" . $value . "';\n";
        }
        file_put_contents($config_file, $config_content);
    }
}
