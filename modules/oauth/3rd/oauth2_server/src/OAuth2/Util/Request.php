<?php

namespace OAuth2\Util;

use OutOfBoundsException;
use InvalidMethodCallException;
use InvalidArgumentException;

class Request implements RequestInterface
{
    protected $get = [];
    protected $post = [];
    protected $cookies = [];
    protected $files = [];
    protected $server = [];
    protected $headers = [];

    public static function buildFromGlobals()
    {
        return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }

    public function __construct(array $get = [], array $post = [], array $cookies = [], array $files = [], array $server = [], $headers = [])
    {
        $this->get = $get;
        $this->post = $post;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = $server;

        if (empty($headers)) {
            $this->headers = $this->readHeaders();
        }
    }

    public function get($index = null, $default = null)
    {
        return $this->getPropertyValue('get', $index, $default);
    }

    public function post($index = null, $default = null)
    {
        return $this->getPropertyValue('post', $index, $default);
    }

    public function file($index = null, $default = null)
    {
        return $this->getPropertyValue('files', $index, $default);
    }

    public function cookie($index = null, $default = null)
    {
        return $this->getPropertyValue('cookies', $index, $default);
    }

    public function server($index = null, $default = null)
    {
        return $this->getPropertyValue('server', $index, $default);
    }

    public function header($index = null, $default = null)
    {
        return $this->getPropertyValue('headers', $index, $default);
    }

    protected function readHeaders()
    {
        if (function_exists('getallheaders')) {
            // @codeCoverageIgnoreStart
            $headers = getallheaders();
        } else {
            // @codeCoverageIgnoreEnd
            $headers = [];
            foreach ($this->server() as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$name] = $value;
                }
            }
        }

        return $headers;
   }

    protected function getPropertyValue($property, $index = null, $default = null)
    {
        if ( ! isset($this->{$property})) {
            throw new InvalidArgumentException("Property '$property' does not exist.");
        }
        if (is_null($index)) {
            return $this->{$property};
        }

        if ( ! array_key_exists($index, $this->{$property})) {
            return $default;
        }

        return $this->{$property}[$index];
    }
}