<?php
$_SERVER['SITE_ID'] = $argv[1];
$_SERVER['LAB_ID'] = $argv[2];
$tube = $argv[3]; // 仪器uuid

require __DIR__.'/../../../../cli/base.php';

class Tube {

    private $mutex_file;
    private $fp;
    private $mq;
    private $client;
    private $config;

    public function __construct($tube) {

        $this->mutex_file = '/tmp/'.$tube;
        $this->fp = fopen($this->mutex_file, 'w+');
        if ($this->fp) {
            if (flock($this->fp, LOCK_EX)) {
                $this->mq = new MQ('beanstalkd');
                $this->mq->set_tube($tube);

                $this->config = Config::get('rpc.servers')['yiqikong'];
                $client_config = Config::get('calendar.server', []);
                $this->client = new \GuzzleHttp\Client([
                    'base_uri' => $client_config['url'] ? : "http://172.17.42.1:9898",
                    'http_errors' => FALSE,
                    'timeout' => $client_config['timeout'] ? : 5,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'client_id' => $this->config['client_id'],
                        'client_secret' => $this->config['client_secret'],
                    ]
                ]);
            }
        }
    }

    public function task() {
        $job = $this->mq->get();
        if ($job) {
            Log::add(strtr('Tube Reserved Data: %job', ['%job' => $job]), 'reserv');

            $job = json_decode($job, true);
            $job['clientId'] = $this->config['client_id'];
            $job['clientSecret'] = $this->config['client_secret'];
            $data = json_encode($job);
            $res = API_YiQiKong::actionAddComponent($data);

            $this->mq->delete();
            if ($job['socket'] == 'ctrl-reserve') {
                // 来自ctrl的消息都从此处通知ctrl
                // 来自lims自身的通过orm.save 通知ctrl-reserve
                // (API_YiQiKong::actionAddComponent中YiQiKongReservAction控制)
                $config = Config::get('rest.ctrl_reserv');
                $client = new \GuzzleHttp\Client([
                    'base_uri' => $config['url'],
                    'http_errors' => FALSE,
                    'timeout' => $config['timeout']
                ]);

                if ($res['success']) {
                    $reserv = O('eq_reserv', $res['params']['params']['lims_id']);
                    $response = $client->put("reserve/{$job['id']}", [
                        'headers' => [
                            'x-yiqikong-notify' => TRUE,
                        ],
                        'form_params' => [
                            'title' => $reserv->component->name,
                            'user' => $reserv->user->yiqikong_id,
                            'user_local' => $reserv->user->id,
                            'user_name' => $reserv->user->name,
                            'lab_name' => Q("$reserv->user lab")->current()->name,
                            'project_name' => $reserv->project->name,
                            'phone' => $reserv->user->phone,
                            'address' => $reserv->user->address,
                            'equipment' => $reserv->equipment->yiqikong_id,
                            'equipment_local' => $reserv->equipment->id,
                            'start_time' => $reserv->dtstart,
                            'end_time' => $reserv->dtend,
                            'ctime' => $reserv->ctime,
                            'mtime' => $reserv->mtime,
                            'project' => $reserv->project_id,
                            'description' => $reserv->component->description,
                            'status' => $reserv->status,
                            'source_name' => LAB_ID,
                            'source_id' => $reserv->id,
                            'component_id' => $reserv->component_id,
                            'token' => $reserv->component->token,
                            'approval' => $reserv->approval
                        ]
                    ])->getBody()->getContents();
                } else {
                    $response = $client->put("reserve/{$job['id']}", [
                        'headers' => [
                            'x-yiqikong-notify' => TRUE,
                        ],
                        'form_params' => [
                            'description' => $res['error_msg']
                        ]
                    ])->getBody()->getContents();
                }
            } else {
                // 通知reserv-server
                $result = @$this->client->post('', [
                    'form_params' => [
                        'id' => $job['session'],
                        'tube' => $job['tube'],
                        'key' => 'yiqikong-reserv-reback',
                        'value' => $res
                    ]
                ])->getBody()->getContents();
            }

            // 关于仪器预约设置的属性会被 ORM 缓存，所以就会引发，此 process 不销毁，相关属性的更改不生效
            ORM_Pool::$cleanup_timeout = 0;
            ORM_Pool::cleanup();

            Log::add(strtr('Tube Returned Data: %result', ['%result' => $result]), 'reserv');
        }

        $this->task();
    }

    public function __destruct() {
        flock($this->fp, LOCK_UN);
        fclose($this->fp);
        @unlink($this->mutex_file);
    }
}

$obj = new Tube($tube);
$obj->task();
