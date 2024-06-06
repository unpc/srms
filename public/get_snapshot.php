<?php

define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);

class _Redis {

    private $redis;

    function __construct() {
        $redis = new Redis;
        $redis->connect(REDIS_HOST, REDIS_PORT);

        $this->redis = $redis;
    }

    function get($key) {
        return $this->redis->get($key);
    }
}

$r = new _Redis;

$return = $r->get('vidcam_url');

$callback = $_GET['callback'];

echo strtr('%callback(%return)', [
    '%callback'=> $_GET['callback'],
    '%return'=> $return,
]);
