<?php
class CLI_Servant extends CLI_Frame{

    static function run($url=null, $platform_id=null, $action=null) {
        if (!$action || !$platform_id) {
            die("Usage:\nphp cli.php servant run 'http://g.labscout.cn/demo/' <platform_id> [disable,enable,delete,create] \n");
        }

        $steps = Config::get('servant.steps', [])[$action];
        if (count($steps)) {
            $cache = Cache::factory();
            $servant = new CLI_Servant($url, $platform_id);
            $servant->show_message("platform action: servant start {$action} an platform");
            foreach ($steps as $step) {
                in_array($action, ['create', 'delete']) && sleep(2);
                $process = $cache->get('servant.' . $action . '.' .$servant->platform->id);
                $process['step'] = $step;
                $cache->set('servant.' . $action . '.' . $servant->platform->id, $process, 60);
                $servant->show_message("platform action: {$step}");
                $servant->$step();
            }
        }
        else {
            die("Usage:\nphp cli.php servant run 'http://g.labscout.cn/demo/' <platform_id> [close,backup,delete,open,create] \n");
        }
    }

    private $platform;
    private $site_id;
    private $source_lab_id; //主站点LABID
    private $lab_id;//子站点LABID
    private $lab_name;
    private $url;

    private $source_lab_path;//主站点site目录
    private $lab_path;//子站点site目录

    private $db_backup;
    private $dir_backup;

    private $modules = [];
    function __construct($url, $platform_id) {

        $platform = O('platform', $platform_id);
        if (!$platform->id) {
            $this->fatal_error('servant 的 platform 不存在');
        }

        if (!$platform->source_lab
        || !in_array($platform->source_lab, Config::get('servant.enable_labs', []))) {
            $this->fatal_error('不允许此站点作为主站建站');
        }

        if (!$url) {
            $url = Config::get('servant.base_url') . $this->code;
        }

        $this->platform = $platform;

        $this->site_id = $platform->source_site;
        $this->source_lab_id = $platform->source_lab;
        $this->lab_id = $platform->code;
        $this->lab_name = $platform->name;
        $this->url = $url;

        $this->source_lab_path = ROOT_PATH . 'sites/'. $this->site_id . '/labs/' . $this->source_lab_id;
        $this->lab_path = ROOT_PATH . 'sites/'. $this->site_id . '/labs/' . $this->lab_id;
    }

    function do_prepare_dir() {

        $labs_base_path = $this->_check_labs_base_path();

        $lab_path = $labs_base_path . DIRECTORY_SEPARATOR . $this->lab_id;

        $this->show_message($lab_path);

        if (is_dir($lab_path)) {
            unset($this->lab_path);
            $this->fatal_error($lab_path . '已存在');
        }
        @mkdir($lab_path);

        $this->lab_path = $lab_path;
    }

    function undo_prepare_dir() {
        if (is_dir($this->lab_path)) {
            File::rmdir($this->lab_path);
        }
    }

    function do_link_fastcgi_script() {
        $target = '.';
        $link = ROOT_PATH . 'public/'. $this->lab_id;
        @symlink($target, $link);
    }

    function undo_link_fastcgi_script() {
        $link = ROOT_PATH . 'public/'. $this->lab_id;
        @unlink($link);
    }

    function do_link_site() {
        $this->touch_disable_file();
        $dirs = glob($this->source_lab_path. '/*');
        if ($dirs) foreach ($dirs as $dir) {
            $this->_deep_link($dir);
        }
    }

    private function _deep_link($target) {
        $link = str_replace($this->source_lab_path, $this->lab_path, $target);

        $target_arr = explode('/', $target);
        // 仅对 ***/config/layout.php 做continue处理
        if (is_dir($target)) {
            if (end($target_arr) == 'config' || end($target_arr) == 'modules' || prev($target_arr) == 'modules') {
                // error_log("mkdir: {$link}");
                @mkdir($link);
                $contents = glob($target. '/*');
                if ($contents) foreach ($contents as $content) {
                    $this->_deep_link($content);
                }
            }
            elseif (end($target_arr) == 'logs') {
                // error_log("excape: {$target}");
            }
            else {
                // error_log("link: {$target}->{$link}");
                @symlink($target, $link);
            }
        }
        else {
            if (is_file($target) && (end(explode('/', $target)) == 'system.php' || end(explode('/', $target)) == 'lab.php' || end(explode('/', $target)) == 'page.php' || end(explode('/', $target)) == 'database.php')) {
                // error_log("excape: {$target}");
            }
            else {
                // error_log("link: {$target}->{$link}");
                @symlink($target, $link);
            }
        }
    }

    function do_init_config() {

        $lab_path = $this->lab_path;

        if (!is_writable($lab_path)) {
            $this->fatal_error("{$lab_path}课题组目录缺少写权限");
        }

        $config_path = $lab_path . DIRECTORY_SEPARATOR . 'config/';

        $lab_config_file = $config_path . 'lab.php';
        $lab_config_content = "<?php\n" .
            "\$config['name'] = '%lab_name';\n";

        $lab_config_content .= "\n\$config['modules']['servant'] = TRUE;";

        $modules = $this->platform->modules;
        if (count($modules)) {
            $optional_modules = Config::get('servant.optional_modules');
            $_m = [];
            foreach($modules as $name) {
                //如果该模块开通包含多个模块
                //则均开通
                if ($optional_modules[$name]['modules']) {
                    foreach($optional_modules[$name]['modules'] as $m) {
                        $lab_config_content .= "\n\$config['modules']['" . $m . "'] = TRUE;";
                    }
                }
                else {
                    $lab_config_content .= "\n\$config['modules']['" . $name . "'] = TRUE;";
                }
            }
            $lab_config_content .= "\n";
        }

        $lab_config_content = strtr($lab_config_content, [
                                        '%lab_name' => $this->lab_name,
                                        ]);
        file_put_contents($lab_config_file, $lab_config_content);

//         // 增加 equipment config key (xiaopei.li@2012-02-04)
//         $eq_config_file = $config_path . 'equipment.php';
//         $eq_config_content = "<?php
// \$config['private_key'] = '
// %key
// ';
// ";

//         $key = shell_exec('openssl genrsa 2048 2>/dev/null'); // shell_exec return the complete output as a string
//         $eq_config_content = strtr($eq_config_content, [
//                                '%key' => $key,
//                                ]);
//         file_put_contents($eq_config_file, $eq_config_content);


        $system_config_file = $config_path . 'system.php';
        $system_config_content = "<?php\n" .
            "if (defined('CLI_MODE')) {\n" .
            "\$config['base_url']  = \$config['script_url'] = '%url';\n" .
            "}\n";
        // $system_config_content .= "\$config['locale'] = '%language';\r\$config['timezone'] = '%timezone';"

        $system_config_content = strtr($system_config_content, [
                               // '%language'=>$this->servant->language,
                               // '%timezone'=>$this->servant->timezone,
                               '%url' => $this->url,
                               ]);
        file_put_contents($system_config_file, $system_config_content);

        $system_config_file = $config_path . 'database.php';
        $system_config_content = "<?php\n" .
            "\$config['%lab_name.db']='lims2_%source_lab';\n";

        $system_config_content = strtr($system_config_content, [
                               '%lab_name' => $this->lab_id,
                               '%source_lab' => $this->source_lab_id
                               ]);
        file_put_contents($system_config_file, $system_config_content);

        $system_config_file = $config_path . 'page.php';
        $system_config_content = "<?php\n" .
            "\$config['title_default']='%name';\n" .
            "\$config['title_pattern']='%title | %name';\n";

        $system_config_content = strtr($system_config_content, [
                               '%name' => $this->lab_name,
                               ]);
        file_put_contents($system_config_file, $system_config_content);

    }

    function do_init_db() {
        $db = Database::factory();

        $SQL_TEMPLATES['create_database'] = "CREATE DATABASE IF NOT EXISTS `lims2_%lab_id` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

        $SQL = strtr($SQL_TEMPLATES['create_database'],
                     ['%lab_id' => $this->lab_id]);

        // $this->show_message($SQL);
        $db->query($SQL);
    }

    function undo_init_db() {
        $db = Database::factory();
        $SQL_TEMPLATES['drop_database'] = "DROP DATABASE `lims2_%lab_id`;";
        $SQL = strtr($SQL_TEMPLATES['drop_database'], ['%lab_id' => $this->lab_id]);
        // $this->show_message($SQL);
        $db->query($SQL);
    }

    function add_to_proj_list() {

        $proj_list = '/etc/lims2/proj_list';
        $this_proj = "{$this->site_id}\t{$this->lab_id}";

        $grep_cmd = "grep -qs '^$this_proj$' $proj_list";
        exec($grep_cmd, $foo, $return_var);

        if ($return_var !== 0) {
            file_put_contents($proj_list, "\n" . $this_proj, FILE_APPEND);
        }
    }

    function do_create_done_msg () {
        if ($this->platform->atime) {
            $this->delete_disable_file();
        }
    }

    function touch_disable_file() {
        return touch($this->lab_path . DIRECTORY_SEPARATOR . 'disable');
    }

    function delete_disable_file() {
        return File::delete($this->lab_path . DIRECTORY_SEPARATOR . 'disable');
    }

    function delete_platform() {
        $platform = $this->platform;
        $owners = Q("{$platform} user.owner");
        foreach ($owners as $user) {
            $platform->disconnect($user, 'owner');
        }

        // disconnect user、equipment、lab
        foreach (Q("{$platform} user") as $user) {
            $platform->disconnect($user);
        }
        foreach (Q("{$platform} equipment") as $eq) {
            $platform->disconnect($eq);
        }
        foreach (Q("{$platform} lab") as $lab) {
            $platform->disconnect($lab);
        }

        $platform->delete();
    }

    function delete_done_msg () {
    }

    private function _check_labs_base_path() {

        $q_root_path = $_SERVER['Q_ROOT_PATH'] ? : ROOT_PATH;
        $labs_base_path = $q_root_path . join(DIRECTORY_SEPARATOR, ['sites', $this->site_id, 'labs']);

        if (!is_readable($labs_base_path)) {
            $this->fatal_error("对{$labs_base_path}缺少读权限");
        }
        if (!is_writable($labs_base_path)) {
            $this->fatal_error("对{$labs_base_path}缺少写权限");
        }

        return $labs_base_path;
    }
}