<?php
require dirname(dirname(dirname(dirname(__DIR__)))) . '/cli/base.php';

$config = Config::get('beanstalkd.opts');

// 通用功能，不同版本的docker装的swoole版本不一样，启动方式也不一样，这里先暂时注释掉不用swoole托管了
// $pool   = new Swoole\Process\Pool(1);

// $pool->on("WorkerStart", function ($pool, $workerId) {
    global $config;
    echo "Worker#{$workerId} is started\n";
    $mq = new \Pheanstalk\Pheanstalk($config['host'], $config['port'], $connectTimeout = null, $connectPersistent = true);
    while (1) {
        $job  = $mq->watchOnly('upload')->reserve();
        $data = $job->getData();
        if (!$data) {continue;}
        Log::add(strtr('Clamscan Reserved Data: %job', ['%job' => $data]), 'journal');
        $mq->delete($job);
        $job = json_decode($data, true);
        $quarantine_path = $job['quarantine_path'];

        $user = O('user', $job['user']);

        setlocale(LC_CTYPE, "UTF8", "en_US.UTF-8");
        @exec('clamscan --quiet ' . escapeshellarg($job['quarantine_path']), $output, $ret);
        if ($ret != 0) {
            echo "calamscan not pass\n";
            /**
             * 日志记录
             * 给用户发消息
             */
	        Notification::send('nfs.delete_files.to_people', $user, [
		        '%user' => Markup::encode_Q($user),
		        '%name' => H($job['file_name']),
		        '%time' => Date::format(time()),
            ]);
            unlink($quarantine_path);
        } else {
            echo "calamscan pass\n";
            /**
             * 日志记录
             * 移入share文件夹
             * 建立sphinx搜索索引
             */

            $full_path = $job['real_path'];
            $file_path = $job['file_path'];
            $file_name = $job['file_name'];
            $path_type = $job['path_type'];
            $path = $job['path'];
            $object = O($job['oname'], $job['oid']);

            if (file_exists($full_path)) {
                $dirname = dirname($file_path).'/';
                $full_dirname = dirname($full_path).'/';
    
                $info = NFS::pathinfo($full_path);
                $extension = $info['extension'] ? '.'.$info['extension'] : '';
                $name = substr($file_name, 0, strrpos($file_name,'.') ? : strlen($file_name));
                /* BUG #839::重复上传.开头的文件后文件名丢失 */
    
                $suffix_count = 2;
    
                do {
                    $file_name = $name.'('.$suffix_count.')'.$extension;
                    $file_path = $dirname . $file_name;
                    $full_path = $full_dirname . $file_name;
                    $suffix_count++;
                }
                while (file_exists($full_path));
    
            }
    
            File::check_path($full_path);

            copy($quarantine_path,$full_path); //拷贝到新目录
            unlink($quarantine_path); //删除旧目录下的文件
    
            Event::trigger('nfs.stat', $object, $path, $full_path, $path_type, 'upload');

            $cache = Cache::factory('redis');
            $k = "{$user->id}_uploading_list";
            $exists = $cache->get($k);
            $exists = $exists ? json_decode($exists, true) : [];
            $fk = md5($job['file_name']);
            if (array_key_exists($fk, $exists)) unset($exists[$fk]);
            $cache->set($k, json_encode($exists), 3600);

            Event::trigger('nfs.files.saved', $user, $full_path);

                /* 记录日志 */
            Search_NFS::update_nfs_indexes($object, $path, $path_type, TRUE); // 移到正确位置时刷新索引

            Notification::send('nfs.save_files.to_people', $user, [
                '%user' => Markup::encode_Q($user),
                '%name' => H($job['file_name']),
                '%time' => Date::format(time()),
            ]);
            
            Log::add(strtr('[nfs] %user_name[%user_id] 上传了 %path', [
                '%user_name' => $user->name,
                    '%user_id' => $user->id,
                    '%path' => $full_path,
                    ]),'journal');
        }

    }
// });

// $pool->on("WorkerStop", function ($pool, $workerId) {
//     echo "Worker#{$workerId} is stopped\n";
// });

// $pool->start();
