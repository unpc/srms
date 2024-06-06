<?php

if (!defined('REDIS_HOST')) define('REDIS_HOST', '127.0.0.1');
if (!defined('REDIS_PORT')) define('REDIS_PORT', 6379);
if (!defined('REDIS_DB'))   define('REDIS_DB', 0);

class Cache_Redis implements Cache_Handler {

    private $redis;

    function setup() {

        $redis = new Redis;
        $redis->connect(REDIS_HOST, REDIS_PORT);
        $redis->select(REDIS_DB);
        $this->redis = $redis;

    }

    function set($key, $value, $ttl = 600) {
        return $this->redis->set($key, @json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $ttl);
    }

    function get($key) {

        $data = $this->redis->get($key);

        if ($data !== FALSE) {
            return @json_decode($data, true);
        }

        return FALSE;
    }

    function remove($key) {
        return $this->redis->delete($key);
    }

    function flush() {
        return $this->redis->flushDB();
    }
}
