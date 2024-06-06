<?php

class MQ_Beanstalkd implements MQ_Handler {

    private $config;
    private $beanstalkd;
    private $job;
    public $tube;

    public function __construct($config) {
        $this->config = $config;
        $this->beanstalkd = new \Pheanstalk\Pheanstalk($this->config['host'].':'.$this->config['port']);
    }

    public function set_tube($tube) {
        $this->tube = $tube[0];
    }

    public function get(){
        if (!$this->beanstalkd) return FALSE;

        try {
            if (!$this->tube) $this->tube = $this->config['default_tube'];

            $this->job = $this->beanstalkd->watch($this->tube)->reserve($this->config['timeout']);

            if ($this->job) {
                return $this->job->getData();
            } else {
                return false;
            }
        } catch (\Pheanstalk\Exception $e) {

        }
    }

    public function delete() {
        $this->beanstalkd->delete($this->job);
    }
}