<?php

class Public_Controller extends _Controller {

    function index(){

        $file = Input::form('f');

        // 检测到目标服务器上存在可访问任意目录下文件的漏洞的修复工作，过滤掉目录遍历深入的可能性
        $file = preg_replace('/\.+\//', '', $file);

        list($category, $file) = explode(':', $file, 2);
        if (!$file) {
            $file = $category;
            $category = NULL;
        }

        //检查 !module/path 格式
        if (preg_match ('/^\!(.*?)(?:\/(.+))?$/', $file, $matches)) {
            $category = $matches[1] ?: NULL;
            $file = $matches[2];
        }

        //PUBLIC_BASE
        $path = Core::file_exists(PUBLIC_BASE.$file, $category);
        if (!$path) {
            $path = ROOT_PATH.PUBLIC_BASE.$file;
        }

        if (is_file($path)) {
            Downloader::download($path);
            exit;
        }
        else {
            while(@ob_end_clean()); //清空之前的所有显示
            header('HTTP/1.1 500 Internal Server Error');
            die;
        }
    }
}
