<?php

class I18N extends _I18N {

    static function convert($orig, $options=NULL) {
        $str = parent::convert($orig, $options);
		if (Config::get('debug.i18n_ipe')) {
			$domain = $options['domain'] ?: 'application';
			$str = sprintf('{[%s:%s//%s]}%s', $domain, str_replace('%', '@/', $orig), str_replace('%', '@/', $str), $str);
		}
        return $str;
    }

    static function set_locale($locale=NULL, $domain=NULL) {
        self::$locale = $locale;
        self::clear_cache($domain);
    }

    static function clear_cache($domain=NULL) {
        if($domain){
            self::$items[$domain] = NULL;
        }
        else{
            self::$items = NULL;
        }
    }

    //在最开始的时候记住系统的语言设置
    private static $system_locale = 'zh_CN';
    static function remember_system_locale(){
        self::$system_locale = Config::get('system.locale');
    }

    static function get_system_locale(){
        return self::$system_locale;
    }

}
