<?php

class Debade_Queue_Courier implements Debade_Queue_Driver {

    private $_name;
    private $_dsn;
    private $_sock;
    private $_queue;

    public function __construct($name, array $options = []) {

        try {
            $this->_name = $name;
            $this->_dsn = $options['dsn'];

            $sock = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_PUSH);
            $sock->connect($this->_dsn);

            $this->_sock = $sock;
            $this->_queue = $options['queue'];
        } catch (Exception $e) {
        }
    }

    public function push($rmsg, $routing_key = null)
    {
        if (!$this->_sock) {
            return;
        }

        $msg = [
            'queue' => $this->_queue,
            'data' => $rmsg,
        ];

        if ($routing_key) {
            $msg['routing'] = $routing_key;
        }

        $this->_sock->send(json_encode($msg), ZMQ::MODE_DONTWAIT);
    }

    public function __destruct()
    {
        if ($this->_sock) {
            // wait only 1000ms if disconnected
            $this->_sock->setSockOpt(ZMQ::SOCKOPT_LINGER, 1000);
            $this->_sock->disconnect($this->_dsn);
        }
    }
}
