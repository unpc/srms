<?php

interface Debade_Queue_Driver {

    public function __construct($name, array $options);
    public function push($message, $routing_key);

}

class Debade_Queue {

    private $_name;
    private $_h;

    private static $_QUEUES = [];

    public function __construct($name) {

        $this->_name = $name;
        $conf = Config::get('debade.queues')[$name];
        if ($conf) {
            $class_name = 'Debade_Queue_'.$conf['driver'];
            class_exists($class_name)
                and $this->_h = new $class_name($name, $conf['options']);
        }
    }

    public static function of($name) {

        if (!isset(self::$_QUEUES[$name])) {
            self::$_QUEUES[$name] = new Debade_Queue($name);
        }

        return self::$_QUEUES[$name];
    }

    public function push($message = null, $routing_key = '') {

        if ($this->_h instanceof Debade_Queue_Driver) {
            $this->_h->push($message, $routing_key);
        }

        return $this;
    }
}
