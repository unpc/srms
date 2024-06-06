<?php

use \Pheanstalk\Pheanstalk;

$_SERVER['SITE_ID'] = getenv('SITE_ID');
$_SERVER['LAB_ID'] = getenv('LAB_ID');

require __DIR__ . '/../../../../cli/base.php';
require APP_PATH . LIBRARY_BASE . 'api.php';


class Tube
{
    private $mutex_file;
    private $fp;
    private $mq;
    private $gatewayConfig = [];

    public function __construct()
    {
        $this->gatewayConfig = YiQiKong::getYiqikongConfig($_SERVER['SITE_ID'], $_SERVER['LAB_ID']);
        $config['host'] = $this->gatewayConfig['mq']['host'];
        $config['port'] = $this->gatewayConfig['mq']['port'];

        $this->mutex_file = sys_get_temp_dir() . '/contorl_stark';
        $this->fp = fopen($this->mutex_file, 'w+');
        if ($this->fp) {
            if (flock($this->fp, LOCK_EX)) {
                $this->mq = new Pheanstalk($config['host'], $config['port']);
            }
        }
    }

    public function task()
    {
        while (true) {
            $job = $this->mq
                ->watch('cf_stark')
                ->ignore('default')
                ->ignore('stark')
                ->reserve(60);

            if ($job && $job->getData()) {

                $this->mq->delete($job);

                $data = json_decode($job->getData(), true);

                $body = is_string($data['body']) ? json_decode($data['body'], true) : $data['body'];
                $header = $data['header'];

                try {

                    $method = $data['method'];
                    $path = $data['path'];

                    Common_Dispatch::dispatch($path, $method, $body, $header);

                } catch (\Exception $e) {
                    error_log('发生错误' . $e->getMessage());
                }
            }
        }
    }

    public function __destruct()
    {
        flock($this->fp, LOCK_UN);
        fclose($this->fp);
        @unlink($this->mutex_file);
    }
}

$obj = new Tube();
$obj->task();
unset($obj);