#!/usr/bin/env php
<?php
/*
 * file log_scan.php
 * author Rui Ma <rui.ma@geneegroup.com>
 * date 2013-10-31
 *
 * useage SITE_ID=xx LAB_ID=xx php
 * brief 用于对系统中所有的log进行扫描，并进行输出
 */

if (isset($argv[1])) {

    //获取path
    define('ROOT_PATH', realpath($argv[1]));

    //初始化scanner
    $scanner = new Log_Scanner(ROOT_PATH);
}
else {
    //提示usage
    die("Usage: php log_scan.php PATH [output_type(yml, cli)] \n");
}

if (isset($argv[2])) {
    $option = $argv[2];
    if (in_array($option, Log_Scanner::$output_types)) {
        $scanner->output = $option;
    }
}

//执行scan
$scanner->scan();
$scanner->show_result();

class Log_Scanner {

    public $output = 'cli';
    public $result = [];
    static $output_types = [
        'cli',
        'yml',
    ];

    public function __construct($path, $output = NULL) {
        $this->path = $path;
    }

    public function scan() {
        //callback进行处理
        File::traverse($this->path, [$this, '_scan_file']);
    }

    public function show_result() {
        $f = '_show_' . $this->output;

        $this->$f();
    }

    private function _show_yml() {
        //yaml_emit_file在yml 1.1.1版本时修正了该问题, 待服务器php的yaml扩展升级到1.1.1 stable版本后可进行修正
        //yaml_emit_file('log.yml', $this->result, YAML_UTF8_ENCODING);
        file_put_contents('log.yml', yaml_emit($this->result, YAML_UTF8_ENCODING));
    }

    private function _show_cli() {
        $result = $this->result;
        foreach($result as $message => $paths) {
            echo $message;
            echo "\n";
            foreach($paths as $path) {
                echo "\t";
                echo $path;
                echo "\n";
            }
        }
    }

    function _scan_file($path) {

        $bool = is_file($path) && preg_match('/\.(php|phtml)$/', $path);

        if ($bool) {
            $relative_path = File::relative_path($path);
            $fh = @fopen($path, 'r');
            while (($line = @fgets($fh)) !== false) {
                //如果匹配到了Log::add后''内的结果
                if (preg_match("/Log::add\(.*'(\[.*]\s+[^']+)'/", $line, $matches)) {
                    $message = $matches[1];
                    $this->result[$message][] = $relative_path;
                }
            }
            @fclose($fh);
        }
    }
}

class File {

    static function traverse($path, $callback, $params=NULL, $parent=NULL) {
        if (FALSE === call_user_func($callback, $path, $params)) return;
        if (!is_link($path) && is_dir($path)) {
            $path = preg_replace('/[^\/]$/', '$0/', $path);
            $dh = opendir($path);
            if ($dh) {
                $files = [];
                while ($file = readdir($dh)) {
                    if ($file[0] == '.') continue;
                    $files[] = $file;
                }
                sort($files);
                foreach($files as $file) {
                    self::traverse($path.$file, $callback, $params, $path);
                }
                closedir($dh);
            }
        }
    }

    static function relative_path($path, $base=NULL) {
        if(!$base) $base = ROOT_PATH;
        return preg_replace('|^'.preg_quote($base, '|').'/?(.*)$|', '$1', $path);
    }
}
