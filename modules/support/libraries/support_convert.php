<?php

class Support_Convert {

    static function convert($e, $module, $key, $value = null) {
        if (Config::get("hooks.admin.support.convert.{$module}.{$key}")) {
            $e->return_value = Event::trigger("admin.support.convert.{$module}.{$key}", $value);
            return FALSE;
        }
        $e->return_value = Config::get("{$module}.{$key}");
        return FALSE;
    }

    static function feedback_deadline ($e) {
        $config = Config::get('equipment.feedback_deadline', 1);
        $e->return_value = $config > 0;
        return FALSE;
    }

    static function feedback_show_samples ($e) {
        $config = Config::get('equipment.feedback_show_samples', 0);
        $e->return_value = $config;
        return FALSE;
    }

    static function eq_charge_incharges_fee ($e) {
        $e->return_value = Lab::get('eq_charge.incharges_fee');
        return FALSE;
    }

    static function eq_record_must_connect_lab_project ($e) {
        $e->return_value = $GLOBALS['preload']['people.multi_lab'] || Config::get('eq_record.must_connect_lab_project');
        return FALSE;
    }

    static function eq_sample_must_connect_lab_project ($e) {
        $e->return_value = $GLOBALS['preload']['people.multi_lab'] || Config::get('eq_sample.must_connect_lab_project');
        return FALSE;
    }

    static function eq_sample_response_time ($e) {
        $e->return_value = Lab::get('eq_sample.response_time');
        return FALSE;
    }

    static function eq_reserv_glogon_arrival ($e) {
        $e->return_value = Lab::get('eq_reserv.glogon_arrival');
        return FALSE;
    }

    static function eq_reserv_glogon_safe ($e) {
        $e->return_value = Lab::get('eq_reserv.glogon_safe');
        return FALSE;
    }

    static function eq_reserv_must_connect_lab_project ($e) {
        $e->return_value = $GLOBALS['preload']['people.multi_lab'] || Config::get('eq_reserv.must_connect_lab_project');
        return FALSE;
    }

    static function transaction_locked_deadline ($e) {
        $e->return_value = Lab::get('transaction_locked_deadline');
        return FALSE;
    }

    static function system_footer_email ($e) {
        $e->return_value = Config::get('lab.help.email');
        return FALSE;
    }

    static function system_header_phone ($e) {
        $e->return_value = Config::get('system.customer_service_tel_text');
        return FALSE;
    }

    static function system_header_phone2 ($e) {
        $e->return_value = Config::get('system.customer_service_tel');
        return FALSE;
    }

    static function system_page_title ($e) {
        $e->return_value = Config::get('page.title_default');
        return FALSE;
    }

    static function preferences_sbmenu_mode ($e, $k) {
        $e->return_value = (Config::get('page.sbmenu_mode', 'icon') == $k);
        return FALSE;
    }

    static function system_base_url ($e) {
        define('CLI_MODE', TRUE);
        Config::load(LAB_PATH, 'system');
        Config::load(LAB_PATH . 'support/', 'system');
        $config = Config::get('system.base_url');
        define('CLI_MODE', FALSE);
        $e->return_value = $config;
        return FALSE;
    }

    static function vidmon_capture_duration ($e) {
        $arr = Date::format_interval(Config::get('vidmon.capture_duration'));
        $e->return_value = $arr;
        return FALSE;
    }

    static function vidmon_alarmed_capture_duration ($e) {
        $arr = Date::format_interval(Config::get('vidmon.alarmed_capture_duration'));
        $e->return_value = $arr;
        return FALSE;
    }

    static function vidmon_capture_max_live_time ($e) {
        $arr = Date::format_interval(Config::get('vidmon.capture_max_live_time'));
        $e->return_value = $arr;
        return FALSE;
    }

    static function login_single_login ($e) {
        $e->return_value = Lab::get('login.single_login');
        return FALSE;
    }

    static function online_kf5 ($e) {
        $e->return_value = Lab::get('online.kf5');
        return FALSE;
    }

    static function sidebar_public ($e) {
        $e->return_value = Config::get('layout.public_url');
        return FALSE;
    }

    static function eq_sample_i18n ($e) {
        $e->return_value = I18N::T('eq_sample', '送样');
        return FALSE;
    }

    static function eq_sample_r_i18n ($e) {
        $e->return_value = I18N::T('eq_sample', '送样预约');
        return FALSE;
    }

    static function eq_reserv_i18n ($e) {
        $e->return_value = I18N::T('eq_reserv', '预约');
        return FALSE;
    }

    static function eq_reserv_r_i18n ($e) {
        $e->return_value = I18N::T('eq_reserv', '仪器预约');
        return FALSE;
    }

    static function undergraduate_i18n ($e, $value) {
        $e->return_value = I18N::T('people', '本科生');
        return FALSE;
    }

    static function graduate_i18n ($e, $value) {
        $e->return_value = I18N::T('people', '硕士研究生');
        return FALSE;
    }

    static function doctor_i18n ($e, $value) {
        $e->return_value = I18N::T('people', '博士研究生');
        return FALSE;
    }

    static function pi_i18n ($e, $value) {
        $e->return_value = I18N::T('people', '课题负责人(PI)');
        return FALSE;
    }

    static function assistant_i18n ($e, $value) {
        $e->return_value = I18N::T('people', '科研助理');
        return FALSE;
    }

    static function labadmin_i18n ($e, $value) {
        $e->return_value = I18N::T('people', 'PI助理/实验室管理员');
        return FALSE;
    }

    static function technician_i18n ($e, $value) {
        $e->return_value = I18N::T('people', '技术员');
        return FALSE;
    }

    static function postdoctoral_i18n ($e, $value) {
        $e->return_value = I18N::T('people', '博士后');
        return FALSE;
    }
}
