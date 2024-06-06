#!/usr/bin/env php
<?php
    /*
     * file create_cache_file.php
     * author Cheng Liu <cheng.liu@geneegroup.com>
     * date  2015/01/13
     *
     * useage SITE_ID=cf LAB_ID=test php create_cache_file.php
     * 1、生成class_map的json文件
     * 2、生成view_map的json文件
     */

require 'base.php';

$parms = $argv;

$cli_path = $argv[1];

$modules = Core::$PATHS;

$walk = function ($root, $prefix, $callback) use (&$walk) {
    $dir = $root . '/' . $prefix;
    if (!is_dir($dir)) return;
    $dh = opendir($dir);
    if ($dh) {
        while (false !== ($name = readdir($dh))) {
            if ($name[0] == '.') continue;

            $file = $prefix ? $prefix . '/' . $name : $name;
            $full_path = $root . '/' . $file;

            if (is_dir($full_path)) {
                $walk($root, $file, $callback);
                continue;
            }

            if ($callback) {
                $callback($file);
            }
        }
        closedir($dh);
    }
};

function J($v, $opt = 0) {
    $opt |= JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES;
    return @json_encode($v, $opt);
}


$class_map = [];
$view_map = [];
$config_map = [];

$modules = array_reverse($modules);

foreach ($modules as $base => $module) {
    //model
    $class_dir = $base.'models';
    $isCore = $module == 'system';
    $walk($class_dir, '', function ($file) use ($class_dir, &$class_map, $isCore) {
        if (preg_match('/^([@\/]?)([^@]+)\.php$/', $file, $parts)) {
            $class_name = trim(strtolower($parts[2]), '/');
            $class_name = strtr($class_name, '/', '_');
            $class_name .= '_model';
            if ($isCore && !$parts[1]) {
                $class_name = '_'.$class_name;
            }
            $class_map[$class_name] = $class_dir . '/' . $file;
        }
    });

    //libraries
    $class_dir = $base.'libraries';
    $walk($class_dir, '', function ($file) use ($class_dir, &$class_map, $isCore) {
        if (preg_match('/^([@\/]?)([^@]+)\.php$/', $file, $parts)) {
            if ( !$parts[1] ) {
                $class_name = trim(strtolower($parts[2]), '/');
                $class_name = strtr($class_name, '/', '_');
                $class_map[$class_name] = $class_dir . '/' . $file;
            }
        }
    });
    $walk($class_dir, '', function ($file) use ($class_dir, &$class_map, $isCore) {
        if (preg_match('/^([@\/]?)([^@]+)\.php$/', $file, $parts)) {
            if ( $parts[1] ) {
                $class_name = trim(strtolower($parts[2]), '/');
                $class_name = strtr($class_name, '/', '_');
                if (isset($class_map[$class_name])) {
                    $class_map['_'.$class_name] = $class_map[$class_name];
                    $class_map[$class_name] = $class_dir . '/' . $file;
                }
            }
        }
    });

    //widgets
    $class_dir = $base.'widgets';
    $walk($class_dir, '', function ($file) use ($class_dir, &$class_map, $isCore) {
        if (preg_match('/^([@\/]?)([^@]+)\.php$/', $file, $parts)) {
            if ( !$parts[1] ) {
                $class_name = trim(strtolower($parts[2]), '/');
                $class_name = strtr($class_name, '/', '_');
                $class_name .= '_widget';
                $class_map[$class_name] = $class_dir . '/' . $file;
            }
        }
    });
    $walk($class_dir, '', function ($file) use ($class_dir, &$class_map, $isCore) {
        if (preg_match('/^([@\/]?)([^@]+)\.php$/', $file, $parts)) {
            if ( $parts[1] ) {
                $class_name = trim(strtolower($parts[2]), '/');
                $class_name = strtr($class_name, '/', '_');
                $class_name .= '_widget';
                if (isset($class_map[$class_name])) {
                    $class_map['_'.$class_name] = $class_map[$class_name];
                    $class_map[$class_name] = $class_dir . '/' . $file;
                }
            }
        }
    });

    //views
    $class_dir = $base.'views';
    $walk($class_dir, '', function ($file) use ($class_dir, &$view_map, $module) {
        if (preg_match('/^(.+)\.phtml$/', $file, $parts)) {
            $view_name = trim($parts[1], '/');
            if (strstr($module, '@')) {
                $category = substr($module, 1);
                $view_name = "$category:".$view_name;
            }
            if ($module == 'application') {
                $view_map["application:".$view_name] = $class_dir . '/' . $file;
            }

            $view_map[$view_name] = $class_dir . '/' . $file;
        }
    });


    //config
    $class_dir = $base.'config';
    $walk($class_dir, '', function ($file) use ($class_dir, &$config_map) {
        if (preg_match('/^(.+)\.php$/', $file, $parts)) {
            $config_name = trim(strtolower($parts[1]), '/');
            $config_file = $class_dir . '/' . $file;

            if (!isset($config_map[$config_name])) $config_map[$config_name] = [];

            $config = & $config_map[$config_name];
            $config['#ROOT'] = & $config_map;
            include($config_file);
            unset($config['#ROOT']);
        }
    });
}

$config_map['#CLI']['system']['base_url'] = $cli_path;
$config_map['#CLI']['system']['script_url'] = $cli_path;

file_put_contents(LAB_PATH . 'class_map', J($class_map));
file_put_contents(LAB_PATH . 'view_map', J($view_map));
file_put_contents(LAB_PATH . 'config_map', J($config_map));





