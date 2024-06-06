<?php

namespace OAuth2\Util;

interface RequestInterface
{

    public static function buildFromGlobals();

    public function __construct(array $get = [], array $post = [], array $cookies = [], array $files = [], array $server = [], $headers = []);

    public function get($index = null);

    public function post($index = null);

    public function cookie($index = null);

    public function file($index = null);

    public function server($index = null);

    public function header($index = null);

}
