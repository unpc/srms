<?php
class Servant {
    static function disable_code ($code) {
        $disable_code = Config::get('servant.disable_code');
        if ( in_array($code, $disable_code) ) {
            return I18N::T('servant', '您填写的机构代码已被系统保留。');
        }
        if (!preg_match('/^\w+$/', $code)) {
            return I18N::T('servant', '您填写的机构代码格式不正确。');
        }
        $exsit_platform = O('platform', ['code' => $code]);
        if ($exsit_platform->id) {
            return I18N::T('servant', '您填写的机构代码已存在。');
        }
    }

    static function optional_modules () {
        $optional_modules = Config::get('servant.optional_modules');
        $modules = [];
        foreach ($optional_modules as $key => $value) {
            $modules[$key] = ['title' => $value['title']];
            if ($value['default']) $modules[$key]['checked'] = TRUE;
        }
        return $modules;
    }

    static $step_str = [
        'prepare_dir' => '创建目录',
        'link_fastcgi_script' => '配置访问URL',
        'link_site' => '创建配置文件',
        'init_config' => '配置站点',
        'create_done_msg' => '创建完成',
        'touch_disable_file' => '关闭站点',
        'undo_link_fastcgi_script' => '释放URL',
        'undo_prepare_dir' => '删除配置文件',
        'delete_platform' => '删除数据库条目',
        'delete_done_msg' => '删除完成'
    ];

    static function disable_platform ($platform) {
        $cmd = sprintf('SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php servant run %s %s disable >/dev/null 2>&1 &', 'null', $platform->id);
        Log::add(strtr('[servant] %user_name[%user_id]将子站点%platform_name[%platform_id]设置为未激活', [
            '%user_name'=> L('ME')->name,
            '%user_id'=> L('ME')->id,
            '%platform_name'=> $platform->name,
            '%platform_id'=> $platform->id
        ]), 'journal');
        $process = proc_open($cmd, [], $pipes);
        proc_close($process);
    }

    static function enable_platform ($platform) {
        $cmd = sprintf('SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php servant run %s %s enable >/dev/null 2>&1 &', 'null', $platform->id);
        Log::add(strtr('[servant] %user_name[%user_id]激活了子站点%platform_name[%platform_id]', [
            '%user_name'=> L('ME')->name,
            '%user_id'=> L('ME')->id,
            '%platform_name'=> $platform->name,
            '%platform_id'=> $platform->id
        ]), 'journal');
        $process = proc_open($cmd, [], $pipes);
        proc_close($process);
    }

    // servant run 'null' 2 delete
    static function delete_platform ($platform) {
        $cache = Cache::factory();

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = sprintf('SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php servant run %s %s delete >/dev/null 2>&1 &', 'null', $platform->id);
        Log::add(strtr('[servant] %user_name[%user_id]删除了子站点%platform_name[%platform_id]', [
            '%user_name'=> L('ME')->name,
            '%user_id'=> L('ME')->id,
            '%platform_name'=> $platform->name,
            '%platform_id'=> $platform->id
        ]), 'journal');

        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        $cache->set('servant.delete.'.$platform->id, ['id' => $platform->id, 'pid' => $pid], 60);
        return $pid;
    }

    // servant run 'http://g.labscout.cn/demo/' 2 create
    static function create_platform ($platform) {
        $cache = Cache::factory();
        $base_url = Config::get('servant.base_url');
        $url = $base_url . $platform->code . '/';
        $old_platform = $cache->get('servant.create.'.$platform->id);
        if (isset($old_platform) && $old_platform['id'] == $platform->id) {
            $cache->set('servant.create.'.$platform->id, NULL);
            proc_close(proc_open('kill -9 '.$old_platform['pid'], [], $pipes));
        }
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = sprintf('SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php servant run %s %s create >/dev/null 2>&1 &', $url, $platform->id);
        Log::add(strtr('[servant] %user_name[%user_id]开通了子站点%platform_name[%platform_id]', [
            '%user_name'=> L('ME')->name,
            '%user_id'=> L('ME')->id,
            '%platform_name'=> $platform->name,
            '%platform_id'=> $platform->id
        ]), 'journal');

        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        $cache->set('servant.create.'.$platform->id, ['id' => $platform->id, 'pid' => $pid], 60);
        return $pid;
    }

    static function is_accessible($e, $module)
    {
        $me = L('ME');
        if ($me->id && Input::arg(0) == 'autocomplete') {
            $e->return_value = TRUE;
            return FALSE;
        }

        if ($GLOBALS['preload']['servant.single_platform']) {
            $e->return_value = FALSE;
            return FALSE;
        }
        else {
            $platform = O('platform', ['code' => LAB_ID]);
            if (!$platform->id) {
                $e->return_value = TRUE;
                return FALSE;
            }
        }

        $e->return_value = $is_accessible;
        return FALSE;
    }
}