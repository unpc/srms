#!/usr/bin/env php
<?php

/**
 * 替换字符串工作，在站点下的对应模块里生成需要替换的语言包
 * 读取站点下i18nreplace.php
 */
require "base.php";

class I18N_Special_Replace
{

    public $errors = 0;

    private $path;
    private $mids;
    private $result = [];

    private static $valid_roots = ['modules', 'sites'];

    function __construct($path)
    {
        $this->path = $path;
        $mids = array_values(Core::module_paths());
        $mids = array_combine($mids, $mids);
        foreach ($mids as &$mid) {
            if (!Module::is_installed($mid)) {
                unset($mids[$mid]);
            }
        }
        $this->mids = $mids;
    }

    function _scan_file($search, $target)
    {
        $modules = $this->mids;
        foreach ($modules as $m) {
            $abpath = $this->path . 'modules' . DIRECTORY_SEPARATOR . $m . DIRECTORY_SEPARATOR . 'i18n';
            if (!is_dir($abpath))
                continue;
            $zhFile = $abpath . DIRECTORY_SEPARATOR . 'zh_CN.php';
            if (!file_exists($zhFile))
                continue;
            $lang = [];
            include $zhFile;
            foreach ($lang as $k => $v) {
                if (strpos($v, $search) !== false) {
                    $this->result[$m][$k] = str_replace($search, $target, $v);
                }
            }
        }
    }

    public function update_site_lang($module, $lang)
    {
        $basePath = LAB_PATH . 'modules' . DIRECTORY_SEPARATOR;
        $modulePath = $basePath . $module . DIRECTORY_SEPARATOR;
        $langPath = $modulePath . 'i18n' . DIRECTORY_SEPARATOR;
        if (!is_dir($langPath)) {
            mkdir($langPath, 0777, true);
        }

        $filePath = $langPath . 'zh_CN.php';
        if (!file_exists($filePath)) {
            touch($filePath);
            $origin = "<?php\n";
        } else {
            $origin = file_get_contents($filePath);
        }
        foreach ($lang as $k => $v) {
            $origin .= "\n\$lang['$k'] = '$v';";
        }
        file_put_contents($filePath, $origin);
    }

    public function scan()
    {
        $this->errors = 0;

        printf("开始扫描%s中的I18N字符串...\n", $this->path);

        $replaces = Config::get('i18nreplace');
        if (empty($replaces)) {
            printf("无需处理\n");
            exit(1);
        }
        foreach ($replaces as $search => $target) {
            $this->_scan_file($search, $target);
        }

        foreach ($this->result as $module => $langs) {
            printf("扫描模块{$module}，\n");
            $this->update_site_lang($module, $langs);
        }
    }
}


$scanner = new I18N_Special_Replace(ROOT_PATH);
$scanner->scan();

if ($scanner->errors > 0) {
    printf("发现 %d 处错误\n", $scanner->errors);
    exit(1);
}
