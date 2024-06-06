<?php

define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);

$config = [
    'vidmon_dir'=> '/tmp/lims2/cf/test/vidmon/',    //vidcam上传的存储地址
    'url'=> 'http://cf.labscout.cn/test/',          //公网访问当前站点的url
    'web_dir'=> '/usr/share/lims2/public/',         //当前站点对应的实际文件路径
];

class _Redis {

    private $redis;

    function __construct() {
        $redis = new Redis;
        $redis->connect(REDIS_HOST, REDIS_PORT);

        $this->redis = $redis;
    }

    //发号
    function set($key, $value) {

        return $this->redis->set($key, $value);
    }
}

$r = new _Redis();

while(TRUE) {

    $now = time();

    $data = [];

    //进行过期文件清除
	foreach(glob($config['web_dir']. '/vidmon/*') as $cache) {
		if (filectime($cache) + 5 < $now) {
			unlink($cache);
		}
	}

    //遍历上传的图片
    foreach(glob($config['vidmon_dir']. '*') as $file) {

        $id = basename($file, '.jpg');

        //如果为数值
        //正确上传
        if (is_numeric($id)) {

            $cache_file = md5($now.$id). '.jpg';

            $vidcam_url = strtr('%url/vidmon/%file', [
                '%url'=> $config['url'],
                '%file'=> $cache_file,
            ]);

            $vidcam_path = strtr('%path/vidmon/%file', [
                '%path'=> $config['web_dir'],
                '%file'=> $cache_file,
            ]);

            if (!is_dir(dirname($vidcam_path))) {
                mkdir(dirname($vidcam_path, 0755, TRUE));
            }

            @symlink($file, $vidcam_path);


            $data[$id] = $vidcam_url;
        }
    }

    $r->set('vidcam_url', @json_encode($data));

    //5秒刷新一次
    sleep(5);
}
