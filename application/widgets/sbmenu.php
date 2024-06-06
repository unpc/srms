<?php

class SBMenu_Widget extends View
{

    public function __construct($vars)
    {
        parent::__construct('sidebar/menu', $vars);
    }

    public static function make_categories($form)
    {
        $categories = (array) $form['categories'];
        $mc         = [];
        foreach ($categories as $index => $category) {
            $name = $category['name'];
            if (!$name) {
                continue;
            }

            $mc[$name] = [];
            foreach ((array) $category['items'] as $item) {
                $mc[$name][$item['id']] = $item['checked'] == 'on' ? true : false;
            }
        }
        return $mc;
    }

    /*
    private static function cache_key($user, $skip_empty) {
    return Misc::key('sbmenu_categories', $user, $skip_empty ? 'less' : '');
    }

    static function cache_get($user, $skip_empty) {
    $cache_key = self::cache_key($user, $skip_empty);
    $cacher = Cache::factory();
    return $cacher->get($cache_key);
    }

    static function cache_set($user, $skip_empty, $items) {
    $cache_key = self::cache_key($user, $skip_empty);
    $cacher = Cache::factory();
    $cacher->set($cache_key, $items);
    }

     */

    public static function categorized_items($user, $skip_empty = true)
    {

        $categories = $user->sbmenu_categories;

        if (!$categories) {
            $categories = Lab::get('sbmenu_categories');
        }

        $items             = (array) Config::get('layout.sidebar.menu');
        $categorized_items = [];
        if (!$categories || count($categories) == 1) {
            $categories_t = [];
            foreach ($items as $id => $item) {
                if(isset($item['category'])) {
                    $categories_t[$item['category_weight']][$item['category']][$id] = TRUE;
                } else {
                    $categories_t[-1]['@others'][$id] = TRUE;
                }
            }
            krsort($categories_t);
            $categories = [];
            foreach ($categories_t as $values) {
                foreach ($values as $key => $value) {
                    $categories[$key] = $value;
                }
            }
        }
        foreach ((array) $categories as $c_name => $category) {
            if (!$skip_empty) {
                $categorized_items[$c_name] = [];
            }

            foreach ((array) $category as $id => $visible) {
                $item = &$items[$id];
                unset($items[$id]);
                if (!isset($item)) {
                    continue;
                }
                $item['#checked']                = $visible;
                $categorized_items[$c_name][$id] = $item;
            }
        }

        $others = (array) $categorized_items['@others'];
        foreach ($items as $id => &$item) {
            $item['#checked'] = true;
            $others[$id]      = $item;
        }
        $categorized_items['@others'] = $others;

        return $categorized_items;

    }

    // static function cache_clean($user) {
    //     $cacher = Cache::factory();
    //     $cacher->remove(self::cache_key($user, FALSE));
    //     $cacher->remove(self::cache_key($user, TRUE));
    // }

    //进行传入validate
    //return true为有error false为无error
    public static function validate_form($form)
    {
        $categories = (array) $form['categories'];
        $mc         = [];

        foreach ((array) $form['categories'] as $index => $category) {
            if ($category['name'] === '') {
                return true;
            }

        }
    }
}
