<?php

abstract class _I18N
{

    protected static $locale;
    protected static $items;
    protected static $bases;

    public static function setup()
    {
        self::$locale = Config::get('system.locale', 'zh_CN');
    }

    public static function get_items()
    {
        return self::$items;
    }

    public static function remove_domain($domain)
    {
        self::$items[$domain] = null;
    }

    public static function shutdown()
    {
        self::$items = null;
    }

    public static function HT($domain = null, $str, $args = null, $options = null, $convert_return = false)
    {
        return Output::H(self::T($domain, $str, $args, $options), $convert_return);
    }

    public static function T($domain = null, $str, $args = null, $options = null)
    {
        if (is_array($str)) {
            foreach ($str as &$s) {
                $s = self::T($domain, $s, $args, $options);
            }
            return $str;
        }
        $options['domain'] = $domain;
        return T($str, $args, $options);
    }

    public static function _convert_str($domain, $str)
    {
        return self::$items[$domain][$str] ?: self::$items['application'][$str] ?: self::$items['system'][$str];
    }

    public static function convert($str, $options = null)
    {
        $options = (array) $options;

        $domain = $options['domain'] ?: 'application';

        self::load_domain($domain);

        $converted_str = self::_convert_str($domain, $str);
        if (isset($converted_str)) {
            return (string) $converted_str;
        } else {
            list($str, $sub) = explode("|:", $str, 2);
            $converted_str   = self::_convert_str($domain, $str);
            if (isset($converted_str)) {
                return (string) $converted_str;
            }

        }

        return $str;
    }

    public static function load_domain($domain)
    {
        if (!isset(self::$items[$domain])) {
            $cache     = Cache::factory();
            $locale    = self::$locale;
            $cache_key = "i18n/$locale/$domain";
            $lang      = Config::get('debug.i18n_nocache') ? false : $cache->get($cache_key);
            if ($lang === false) {
                $lang = array();
                foreach (array_reverse(Core::file_paths(I18N_BASE . $locale . EXT, $domain)) as $path) {
                    !file_exists($path) or @include $path;
                }
                $cache->set($cache_key, $lang);
            }
            self::$items[$domain] = (array) $lang;
        }
    }

    public static function add_base($base)
    {
        self::$bases[$base] = $base;
    }

}
