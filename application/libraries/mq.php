<?php
class MQ_Exception extends Exception {}

interface MQ_Handler {
    public function __construct($config);
}

class MQ {

    private $handler;

    public function __construct($handler, $config) {
        $class = 'MQ_'.ucwords($handler);
		if (!class_exists($class)) return;

		$this->handler = new $class($config);
    }

    public function __call($method, $params) {
        if (!$this->handler) return FALSE;
		return $this->handler->$method($params);
    }

    public function set() {
        if (!$this->handler) return FALSE;
		return $this->handler->set();
    }

    public function get() {
        if (!$this->handler) return FALSE;
		return $this->handler->get();
    }

    public function delete() {
        if (!$this->handler) return FALSE;
		return $this->handler->delete();
    }
}