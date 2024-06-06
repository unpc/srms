<?php
require_once __DIR__.'/../../config/rpc.php';

class Server
{
    protected $host;
    protected $port;
    protected $server;
    protected $config;

    public function __construct($host, $port)
    {
        global $config;
        $this->host = $host;
        $this->port = $port;
        $this->config = $config['servers']['reserv-partner'];
        $server = new swoole_http_server($host, $port);
        $server->set([
            'daemonize' => 0,
            'worker_num' => 1, // worker process num
            'task_worker_num' => 1, //
            'backlog' => 128, // listen backlog
            'max_request' => 50,
            'dispatch_mode' => 3, // 抢占模式，主进程会根据Worker的忙闲状态选择投递，只会投递给处于闲置状态的Worker
            'user' => 'www-data',
            'group' => 'www-data',
            // for upgrading swoole 4.3+
            'enable_coroutine' => false
        ]);
        $server->on('request', [$this, 'request']);
        $server->on('workerStart', [$this, 'work']);
        $server->on('task', [$this, 'task']);
        $server->on('finish', [$this, 'finish']);

        $this->server = $server;
    }

    /**
     * 将请求进行分发 符合Gini框架要求
     *
     * @param [swoole_http_request] $req http请求对象
     * @param [swoole_http_response] $res http应答对象
     * @return void
     */
    public function request($req, $res) {
        // $_SERVER = array_merge($_SERVER, $req->server);
        $header = $req->header;
        $data = $req->rawContent();
        echo date('Y-m-d H:i:s')." Partner Received: $data\n";

        $data = json_decode($data, true);
        if (isset($header['clientid']) && $header['clientsecret']
            && $header['clientid'] == $this->config['client_id']
            && $header['clientsecret'] == $this->config['client_secret']
            && isset($data['site_id']) && $data['site_id']
            && isset($data['lab_id']) && $data['lab_id']
            && isset($data['tube']) && $data['tube']
            && isset($data['beanstalkd']) && $data['beanstalkd']
        ) {

            // 每一台仪器(tube唯一)起一个子进程进行预约处理
            // tube中采用task递归，不停在队列中取数据，而同时beanstalkd实例timeout时间内未reserve到数据则子进程销毁
            exec('ps -ef | grep tubeV2.php | grep '.$data['tube'], $output);

            if (!strpos($output[0], $data['site_id'].' '.$data['lab_id'].' '.$data['tube'])) {
                $process = new swoole_process(function(swoole_process $w) use ($data) {
                    $command = __DIR__.'/tubeV2.php';
                    $w->exec('/usr/bin/php', [
                        $command,
                        $data['site_id'],
                        $data['lab_id'],
                        $data['tube'],
                        $data['beanstalkd']['url'],
                    ]);
                }, TRUE, TRUE);

                $pid = $process->start();

                echo date('Y-m-d H:i:s'), ' Child Process(', $pid, ') Started: ', $data['site_id'], ' ', $data['lab_id'], ' ',
                    $data['tube'], ' ', $data['beanstalkd']['url'], "\n";
            }

            // 回收结束运行的子进程
            swoole_process::wait(FALSE);
        }
        $res->end(true);
    }

    public function work($server, $work) {
        echo date('Y-m-d H:i:s')." Partner Server Start: {$this->host}:{$this->port} \n";
    }

    /**
     * 投递一个异步任务到task_worker池中。
     *
     * @param [swoole_server] $server 当前任务执行的server对象 主要用于进行finish操作
     * @param [int] $task 当前任务的id
     * @param [int] $from 启动该task的worker的id
     * @param [mixed] $data 投递进该任务的数据 不能为资源数据
     * @return void
     */
    public function task($server, $task, $from, $data) {
        $server->finish($result);
    }

    /**
     * 用于在task进程中通知worker进程，投递的任务已完成。
     *
     * @param [swoole_server] $server
     * @param [int] $task
     * @param [mixed] $data
     * @return void
     */
    public function finish($server, $task, $data) {
        echo date('Y-m-d H:i:s')." Partner Connection Closed: {$this->host}:{$this->port} \n";
    }

    public function run() {
        $this->server->start();
    }
}
$params = getopt('', [
    'host:',
    'port:'
]);
$host = array_key_exists('host', $params) ? $params['host'] : '0.0.0.0';
$port = array_key_exists('port', $params) ? $params['port'] : '9510';
$server = new Server($host, $port);
$server->run();
