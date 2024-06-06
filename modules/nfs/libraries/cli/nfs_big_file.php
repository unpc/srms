<?php

class CLI_Nfs_Big_File
{

    // 删除超时的cache文件
    public static function delete_cache()
    {
        $files = Q('file_cache');

        foreach ($files as $file) {
            $time = time() - $file->dctime;

            //计算小时数
            $remain = $time % 86400;
            $hours = intval($remain / 3600);

            if ($hours > 24) {
                if (file_exists($file->file_path)) {
                    self::delete_dir($file->file_path);
                }
                $file->delete();
            }
        }
    }

    // 删除文件夹全部内容
    private static function delete_dir($dir = '')
    {
        if ($dir == '') {
            return false;
        }

        $block_info = scandir($dir);

        foreach ($block_info as $b) {
            @unlink($dir.'/'.$b);
        }
        @rmdir($dir);
    }

    // 整合文件-拆分到CLI
    public static function merge_file($cache_path, $save_path, $file_md5)
    {
        // 获取分片文件内容
        $block_info = scandir($cache_path);

        // 除去无用文件
        foreach ($block_info as $key => $block) {
            if ($block == '.' || $block == '..') {
                unset($block_info[$key]);
            }
        }

        // 数组按照正常规则排序
        natsort($block_info);

        // 没有？建立
        if (!file_exists($save_path)) {
            fopen($post['file_name'], "w");
        }

        // 开始写入
        $out = @fopen($save_path, "wb");

        // 增加文件锁
        if (flock($out, LOCK_EX)) {
            foreach ($block_info as $b) {
                // 读取文件
                $in = @fopen($cache_path.'/'.$b, "rb");
                if (!$in) {
                    break;
                }

                // 写入文件
                while ($buff = fread($in, 4096)) {
                    fwrite($out, $buff);
                }

                @fclose($in);
                @unlink($cache_path.'/'.$b);
            }
            flock($out, LOCK_UN);
        }
        @fclose($out);
        @rmdir($cache_path);

        $orm_file = O("file_cache", ['file_path' => $cache_path]);
        $orm_file->delete();

        if (md5_file($save_path) == $file_md5) {
            echo 'ok';
        } else {
            error_log($save_path.'-文件写入失败.');
            @unlink($save_path);
        }
    }
}
