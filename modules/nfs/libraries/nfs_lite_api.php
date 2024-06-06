<?php

use \Pheanstalk\Pheanstalk;

class Nfs_Lite_API
{
    public static function nfs_lite_get($e, $params, $data, $query)
    {
        list($oname, $id, $path_type) = $params;

        $path = $query['path'];
        $object = O($oname, $id);
        $user = L("gapperUser");
        $full_path = NFS::get_path($object, $path, $path_type, TRUE);

        if (!file_exists($full_path)) {
            $path = NFS::fix_path($path, FALSE);
            $full_path = NFS::get_path($object, $path, $path_type, TRUE);
        }

        if (NFS::user_access($user, '下载文件', $object, ['path' => $path . '/foo', 'type' => $path_type])) {
            if (is_file($full_path)) {

                Downloader::download($full_path, TRUE);

                /* 记录日志 */
                Log::add(strtr('[nfs api] %user_name[%user_id] 下载了 %path', [
                    '%user_name' => $user->name,
                    '%user_id' => $user->id,
                    '%path' => $full_path,
                ]), 'journal');

                exit;
            }
        }

        if ((!file_exists($full_path) || is_dir($full_path)) && NFS::user_access($user, '列表文件', $object, ['path' => $path . '/foo', 'type' => $path_type])) {
            $files = NFS::file_list($full_path, $path);

            $ret = ['items' => $files];
            $ret['total'] = count($files);
            $e->return_value = $ret;
        }
    }

    public static function nfs_lite_post($e, $params, $data, $query)
    {
        list($oname, $id, $path_type) = $params;

        $object = O($oname, $id);
        $user = L("gapperUser");
        $path = '';
        $path = NFS::fix_path($path);

        /* 判断权限 */
        if (!NFS::user_access($user, '上传文件', $object, ['path' => $path . '/foo', 'type' => $path_type])) {
            throw new Exception('Forbidden', 403);
        }
        $file = Input::file('file');

        if (!$file || !$file['tmp_name']) {
            throw new Exception('请选择上传的文件', 400);
        }

        $post_size = ini_get('post_max_size');

        if ($file['error']) {
            throw new Exception(I18N::T('nfs', '您上传的文件发生异常错误或大于%size!', ['%size' => $post_size]), 400);
        }

        $file_name = $file['name'];
        $file_name = NFS::fix_name($file_name, TRUE);
        $file_path = ($path ? $path . '/' : '') . $file_name;
        $real_path = NFS::get_path($object, $file_path, $path_type);
        $full_path = NFS_Share::get_quarantine_path($object, $file_path, $path_type);

        if (file_exists($full_path)) {
            $dirname = dirname($file_path) . '/';
            $full_dirname = dirname($full_path) . '/';

            $info = NFS::pathinfo($full_path);
            $extension = $info['extension'] ? '.' . $info['extension'] : '';
            $name = substr($file_name, 0, strrpos($file_name, '.') ?: strlen($file_name));
            /* BUG #839::重复上传.开头的文件后文件名丢失 */

            $suffix_count = 2;

            do {
                $file_name = $name . '(' . $suffix_count . ')' . $extension;
                $file_path = $dirname . $file_name;
                $full_path = $full_dirname . $file_name;
                $suffix_count++;
            } while (file_exists($full_path));
        }

        File::check_path($full_path);
        move_uploaded_file($file['tmp_name'], $full_path);
        /* 记录日志 */
        Log::add(strtr('[nfs api] %user_name[%user_id] 上传了 %path', [
            '%user_name' => $user->name,
            '%user_id' => $user->id,
            '%path' => $full_path,
        ]), 'journal');

        $file = NFS::file_info($full_path);
        if (!$file) {
            throw new Exception(I18N::T('nfs', '上传失败!'), 500);
        }

        $file += ['name' => $file_name, 'path' => $file_path];

        $config = Config::get('beanstalkd.opts');
        $mq = new Pheanstalk($config['host'], $config['port']);
        $data = [
            'file_name' => $file_name,
            'path' => $path,
            'file_path' => $file_path,
            'path_type' => $path_type,
            'user' => $user->id,
            'real_path' => $real_path,
            'quarantine_path' => $full_path,
            'oname' => $oname,
            'oid' => $id,
        ];

        $cache = Cache::factory('redis');
        $k = "{$user->id}_uploading_list";
        $exists = $cache->get($k);
        $exists = $exists ? json_decode($exists, true) : [];
        $exists[md5($file_name)] = $data;
        $exists = json_encode($exists);
        $cache->set($k, $exists, 3600);

        $mq->useTube('upload')->put(json_encode($data, TRUE));
        $e->return_value = [
            'code' => 200,
            'message' => I18N::T('nfs', '文件已上传, 正在排队等待病毒检测, 结束后会发消息至您的<信息中心>, 请注意查收! 成功后可在当前分区进行查看!')
        ];
    }

    public static function nfs_lite_delete($e, $params, $data, $query)
    {
        list($oname, $id, $path_type) = $params;
        $path = $query['path'];
        $object = O($oname, $id);
        $user = L("gapperUser");
        $path = NFS::fix_path($path);
        // $path = NFS::fix_path(rawurldecode($path));
        $path = explode('/', $path);
        $path = end($path);
        if (!NFS::user_access($user, '删除文件', $object, ['path' => $path . '/foo', 'type' => $path_type])) {
            throw new Exception('Forbidden', 403);
        }

        $full_path = NFS::get_path($object, $path, $path_type);
        if (is_dir($full_path)) {
            // File::rmdir($full_path);
        } else {
            File::delete($full_path);
        }
        Event::trigger('nfs.stat', $object, $path, $full_path, $path_type, 'delete');
        /* 记录日志 */
        Log::add(strtr('[nfs api] %user_name[%user_id] 删除了 %path', [
            '%user_name' => $user->name,
            '%user_id' => $user->id,
            '%path' => $full_path,
        ]), 'journal');

        $e->return_value = [
            'code' => 200,
        ];
    }
}
