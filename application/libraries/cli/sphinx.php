<?php
class CLI_Sphinx
{
    public static function get_config()
    {
        /*
        输出指定 lab 的 sphinx 配置

        usage: SITE_ID=cf LAB_ID=test php cli.php sphinx get_config
        */

        echo '# lims2 sphinx conf for SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . "\n";

        $sphinx_confs = Config::get('sphinx');

        if ($sphinx_confs) {
            foreach ($sphinx_confs as $conf_key => $conf_content) {
                if ($conf_content) {
                    self::generate_sphinx_conf($conf_key, $conf_content);
                }
            }
        }

        echo "\n# -- EOF --\n";
    }

    private static function generate_sphinx_conf($conf_key, $conf_content)
    {
        echo "# " . $conf_key . "\n";

        echo strtr(strtr("index %site_id_%lab_id_%index: rt_default\n", [
                        '%site_id' => SITE_ID,
                        '%lab_id' => LAB_ID,
                        '%index' => $conf_key,
                        ]), '-', '_');

        echo "{\n";

        echo strtr("path = /var/lib/sphinxsearch/data/lims2/%site_id_%lab_id_%index\n", [
                        '%site_id' => SITE_ID,
                        '%lab_id' => LAB_ID,
                        '%index' => $conf_key,
                        ]);

        foreach ((array)$conf_content['options'] as $key => $opts) {
            echo strtr("%key = %value\n", [
                            '%key' => $key,
                            '%value' => $opts['value'],
                            ]);
        }

        foreach ((array)$conf_content['fields'] as $field => $opts) {
            echo strtr("%type = %field\n", [
                            '%type' => $opts['type'],
                            '%field' => $field,
                            ]);
        }

        echo "}\n";
    }

    public static function get_all_config($path=null)
    {
        if (!$path) {
            echo "遍历生成 sphinx conf\n";
            echo "命令格式: php cli.php sphinx get_all_config /usr/share/lims2\n";
            exit;
        }

        if (is_dir($path)) {
            define('ROOT_PATH', realpath($path).'/');
        } else {
            define('ROOT_PATH', dirname(__FILE__).'/');
        }

        // die(ROOT_PATH . "\n");

        /// run
        $labs = glob(ROOT_PATH.'sites/*/labs/*');

        $sphinx_confs = [];

        $get_sphinx = ROOT_PATH . 'cli/cli.php sphinx get_config';

        foreach ($labs as $lab) {
            if (!preg_match('|sites/([^/]+)/labs/([^/]+)|', $lab, $matches)) {
                continue;
            }

            $site_id = $matches[1];
            $lab_id = $matches[2];
            $cmd = strtr('SITE_ID=%site_id LAB_ID=%lab_id %script 2>/dev/null %then', [
                             '%site_id' => $site_id,
                             '%lab_id' => $lab_id,
                             '%script' => "php $get_sphinx",
                             '%then' => " | grep -v '^Warning'",
                             // 当有目录但无 DB 时, 会产生警告, 如:
                             // Warning: mysqli::mysqli(): (42000/1049): Unknown database 'lims2_test' ...
                             // 所以 grep -v 消除之
                             ]);

            $content_grabbed = '';

            ob_start();
            // passthru("SITE_ID=$site_id LAB_ID=$lab_id php $get_sphinx 2>/dev/null", $ret);
            passthru($cmd, $ret); // 用 grep 去除非 sphinx 或 注释 的行
            if ($ret == 0) {
                $content_grabbed = ob_get_contents();
            }
            ob_end_clean();

            // exec() returns **the last line from the result of the command**
            // If you need to execute a command and have all the data from the command passed directly back without any interference, use the passthru() function.

            echo $content_grabbed;
            // $sphinxtabs[$site_id . '_' . $lab_id] = $content_grabbed;
        }


        // echo join($sphinxtabs, "\n");
    }

    public static function refresh_indexes()
    {
        $sphinx_confs = Config::get('sphinx', []);

        foreach ($sphinx_confs as $conf_key => $conf_content) {
            if ($conf_key == 'nfs') {
                continue;
            }
            foreach (Q("{$conf_key}") as $object) {
                Sphinx_Search::update_object_indexes($object);
            }
        }
        Upgrader::echo_success("Done.");
    }

    public static function truncate_indexes()
    {
        $sphinx_confs = Config::get('sphinx', []);

        foreach ($sphinx_confs as $conf_key => $conf_content) {
            if ($conf_key == 'nfs') {
                continue;
            }
            Sphinx_Search::truncate_indexes($conf_key);
        }
        Upgrader::echo_success("Done.");
    }

    public static function test_search()
    {
        $ret = Sphinx_Search::search([
            'module_name' => 'eq_sample',
            'filter' => [
                'search_text' => '攻毒'
            ]
        ]);
        var_dump($ret);
    }
}
