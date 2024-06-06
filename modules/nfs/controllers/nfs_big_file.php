<?php

class NFS_Big_File_Controller extends Controller {

    // 上传文件
    function upload($oname, $id = '0', $path_type) {
        $form = Form::filter(Input::form());

        if (!$form['file_md5']) return false;
        if (!$form['file_name']) return false;

        // 获取文件信息
        $file = $_FILES;
        $file = array_shift($file);

        $file_full_path = $this->full_path($oname, $id, $path_type, $form['file_md5'], $form['file_name']);

        // 缓存存储目录
        $cache_path = $file_full_path['cache_full_path'];

        // 如果没有目录-建立缓存目录
        if (!file_exists($cache_path)) {
            @mkdir ($cache_path,0777,true);
        }

        File::check_path($cache_path);

        move_uploaded_file($file['tmp_name'], $cache_path.'/'.$form['chunk']);

        if (file_exists($cache_path.'/'.$form['chunk'])) {
            echo json_encode(['status' => true]);
        }
    }

    // 上传前检查-文件大小
    function check_disk($oname, $id = '0', $path_type) {

        $form = Form::filter(Input::form());

        // 定义分片大小-10M
        $file_big_size = 1024 * 1024 * 10;

        if (!$form['file_size'] || !$form['file_type']) {
            echo json_encode(['status' => false, 'msg' => '文件添加有误,请尝试重新添加!']);
            return false;
        }

        $file_full_path = $this->full_path($oname, $id, $path_type, $form['file_md5'], $form['file_name']);

        // 缓存存储目录
        $cache_path = $file_full_path['cache_full_path'];

        // 检查文件类型-暂未定义规则,空缺
        $type = $form['type'];

        // 检查根目录可用字节
        $all_byte = disk_free_space('/');

        // 计算目前文件已占用字节
        foreach (Q("file_cache") as $f) {
            $occupy_byte += $f->file_size;
        }

        // 可用字节为
        $use_byte = $all_byte - $occupy_byte;

        // 文件字节总大小
        $file_size = $form['file_size'];

        // 如果已上传文件,则已经验证,则通过
        $orm_file = O("file_cache", ['file_path' => $cache_path]);

        if ($orm_file->id) {
            // 更新时间
            $orm_file->dctime = time();
            $orm_file->save();

            echo json_encode(['status' => true]);
            return true;
        }
        // 如果文件小于最小分片,取消上传
        if ($file_size < $file_big_size) {
            echo json_encode(['status' => false, 'msg' => '文件过小,请使用正常上传功能上传文件!']);
        }
        elseif($file_size > Config::get('nfs.big_file_max_size')) {
            echo json_encode(['status' => false, 'msg' => '文件过大,上传失败！']);
        }
        // 如果有二倍空间储存,则返回true
        elseif ($file_size * 2 < $use_byte) {
            // 如果为第一次上传
            $orm_file->file_path = $cache_path;
            $orm_file->file_size = $file_size * 2;
            $orm_file->dctime = time();
            $orm_file->save();

            echo json_encode(['status' => true]);
        }
        // 如果空间不足,取消上传
        else {
            echo json_encode(['status' => false, 'msg' => '磁盘空间不足,上传失败,请联系管理员.']);
        }
    }

    // 上传完成，合并文件
    function merge($oname, $id = '0', $path_type) {

        $form = Form::filter(Input::form());

        $file_full_path = $this->full_path($oname, $id, $path_type, $form['file_md5'], $form['file_name']);

        // 缓存存储目录
        $cache_path = $file_full_path['cache_full_path'];

        // 文件存储路径
        $save_path = $file_full_path['file_full_path'];

        // 检查文件是否上传完成
        $size = $form['file_size'];

        // 因为是从0开始,所以-1
        if ($this->check_chuck_num($cache_path, $size)) {

            $exec = sprintf('SITE_ID=%s LAB_ID=%s %s nfs_big_file merge_file %s %s %s', 
                    SITE_ID,
                    LAB_ID,
                    ROOT_PATH.'cli/cli.php',
                    $cache_path,
                    $save_path,
                    $form['file_md5']
                );

            exec($exec, $out);

            if ($out) {
                echo json_encode(['status' => true]);
                return false;
            }
        }

        echo json_encode(['status' => false, 'msg' => '文件有破损,请尝试重新上传!']); 
    }

    // 上传前检查有无分片
    function check_chuck($oname, $id = '0', $path_type) {

        $form = Form::filter(Input::form());

        $checks = [];

        $file_full_path = $this->full_path($oname, $id, $path_type, $form['file_md5'], $form['file_name']);

        $cache_path = $file_full_path['cache_full_path'];

        if (file_exists($cache_path)) {
            $checks = scandir($cache_path);

            // 除去无用文件
            foreach ($checks as $key => $block) {
                if ($block == '.' || $block == '..') unset($checks[$key]);
            }
        }

        echo json_encode($checks);
    }

    // 整理缓存和文件路径
    function full_path($oname, $id = '0', $path_type , $file_md5, $file_name) {

        // 初始定义cache目录位置
        $cache_base = '/home/disk/'.$_SERVER['SITE_ID'].'/'.$_SERVER['LAB_ID'].'/cache';

        $object = O($oname, $id);
        $user = L('ME');

        // 实际存储目录
        $path = NFS::fix_path(Input::form('path'));

        // 定义缓存目录
        $file_cache_path = ($path ? $path . '/': '').$file_md5;

        // 定义保存文件
        setlocale(LC_CTYPE, "en_US.UTF-8"); // for escapeshellarg
        $file_real_path = ($path ? $path . '/': '').escapeshellarg($file_name);

        // 缓存存储目录
        $cache_full_path = $cache_base . NFS::get_path($object, $file_cache_path, $path_type);

        // 文件存储目录
        $file_full_path = NFS::get_path($object, $file_real_path, $path_type);

        if (!NFS::user_access($user, '上传文件', $object, ['path'=>$path.'/foo', 'type'=>$path_type])) {
            return false;
        }

        $file_full_path = [
            'cache_full_path' => $cache_full_path,
            'file_full_path' => $file_full_path
        ];

        return $file_full_path;
    }

    // 检查是否成功上传所有文件
    function check_chuck_num($cache_path, $size) {

        // 总块数-因为是从0开始,所以-1
        $block_num = ceil($size / (1024 * 1024 * 10)) - 1;

        $block_num = range(0, $block_num);

        $block_info = scandir($cache_path);

        // 检查差异
        $f = array_diff($block_num, $block_info);

        if (!$f) {
            return true;
        }
    }

    // 取消上传-删除文件
    function delete($oname, $id = '0', $path_type) {
        $form = Form::filter(Input::form());

        $checks = [];

        $file_full_path = $this->full_path($oname, $id, $path_type, $form['file_md5'], $form['file_name']);

        $cache_path = $file_full_path['cache_full_path'];

        // 给CLI来执行
        // 删除
        if (file_exists($cache_path)) {
            if ($cache_path == '') return false;

            $block_info = scandir($cache_path);

            foreach ($block_info as $b) {
                @unlink($cache_path.'/'.$b);
            }
            @rmdir($cache_path);
        }

        if (!file_exists($cache_path)) {
            echo json_encode(['status' => true]);
        }
        else {
            echo json_encode(['status' => false, 'msg' => '删除失败']);
        }
    }
}
