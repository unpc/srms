<?php
$_SERVER['SITE_ID'] = $argv[1];
$_SERVER['LAB_ID'] = $argv[2];
$tube = $argv[3];

require __DIR__.'/../../../../cli/base.php';

class Tube {

    private $mutex_file;
    private $fp;
    private $mq;
    private $client;
    private $tube;

    public function __construct($tube) {
        $config = Config::get('beanstalkd.opts');
        $host = $config['host'] ?: '172.17.42.1';
        $port = $config['port'] ?: 9509;

        $this->mutex_file = sys_get_temp_dir().'/'.$tube;
        $this->fp = fopen($this->mutex_file, 'w+');
        if ($this->fp) {
            if (flock($this->fp, LOCK_EX)) {
                $this->mq = new MQ('beanstalkd', $config);
                $this->mq->set_tube($tube);
                $this->tube = $tube;
                $this->client = new swoole_client(SWOOLE_SOCK_TCP);
                if (!$this->client->connect($host, $port, -1)) {
                    Log::add("Tube Connected: {$host}:{$port}", 'reserv');
                    exit();
                }
            }
        }
    }

    public function task() {

        while (1) {
            $job = $this->mq->get();
            if (!$job) {  break; }
            Log::add(strtr('Tube Reserved Data: %job', ['%job' => $job]), 'reserv');
            $this->mq->delete();
            $job = json_decode($job, true);
            
            // 由于大数据并发会出现投错数据或者抓错数据的现象，这个地方进行简单的自我判断
            if ($job['tube'] != $this->tube) {
                Log::add(strtr('Tube Returned Data: 获取到数据 tube  %tube1 与监控 tube %tube2 不同，抛弃数据。', [
                    '%tube1' => $job['tube'],
                    '%tube2' => $this->tube
                ]), 'reserv');
                continue;
            }

            $config = Config::get('rpc.servers')['yiqikong'];
            $job['clientId'] = $config['client_id'];
            $job['clientSecret'] = $config['client_secret'];
            $tube = $job['tube'];
            $data = json_encode($job);
            $res = API_YiQiKong::actionAddComponent($data);

            $result = json_encode(['id' => $job['socket'], 'tube' => $tube, 'key' => 'yiqikong-reserv-reback', 'value' => $res]);

            $this->client->send($result);
            // 关于仪器预约设置的属性会被 ORM 缓存，所以就会引发，此 process 不销毁，相关属性的更改不生效
            ORM_Pool::$cleanup_timeout = 0;
            ORM_Pool::cleanup();

            Log::add(strtr('Tube Returned Data: %result', ['%result' => $result]), 'reserv');
        }
    }

    public function __destruct() {
        $this->client->close();
        Log::add("Tube Connection Closed: {$config['host']}:{$config['port']}", 'reserv');
        flock($this->fp, LOCK_UN);
        fclose($this->fp);
        @unlink($this->mutex_file);
    }
}

$obj = new Tube($tube);

$obj->task();
