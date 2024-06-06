<?php
$_SERVER['SITE_ID'] = $argv[1];
$_SERVER['LAB_ID'] = $argv[2];
// 仪器uuid
$tube = $argv[3];
// beanstalkd连接相关
$beanstalkdUrl = $argv[4];

require __DIR__ . '/../../../../cli/base.php';

class Tube
{
    private $mutex_file;
    private $fp;
    private $mq;
    private $clients = [];
    private $tube;

    public function __construct($tube, $beanstalkdUrl = '')
    {
        $config = Config::get('beanstalkd.opts');
        if ($beanstalkdUrl) {
            list($beanstalkdHost, $beanstalkdPort) = explode(':', $beanstalkdUrl, 2);
        }
        if ($beanstalkdHost) {
            $config['host'] = $beanstalkdHost;
        }
        if ($beanstalkdPort) {
            $config['port'] = $beanstalkdPort;
        }
        $this->mutex_file = sys_get_temp_dir() . '/' . $tube;
        $this->fp = fopen($this->mutex_file, 'w+');
        if ($this->fp) {
            if (flock($this->fp, LOCK_EX)) {
                $this->mq = new MQ('beanstalkd', $config);
                $this->mq->set_tube($tube);
                $this->tube = $tube;
            }
        }
    }

    public function task()
    {
        while (true) {
            $job = $this->mq->get();
            if (!$job) {
                break;
            }
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

            if ($job['session'] == 'ctrl-reserve') {
                $client_config = Config::get('rpc.servers')['yiqikong'];
            } else {
                $client_config = Config::get('rpc.servers')['reserv-server'];
            }
            $job['clientId'] = $client_config['client_id'];
            $job['clientSecret'] = $client_config['client_secret'];
            $data = json_encode($job);
            $params = json_decode($data, true);

            try {
                $res = API_YiQiKong::actionAddComponent($data);
            } catch (Exception $e) {
                $res['error_msg'] = $e->getMessage() ?: '操作失败';
                //error_log(print_r($e->getMessage(),true));
            }
            // 获取原始reserv对象
            $reserv = $params['lims_id'] ? O('eq_reserv', $params['lims_id']) : O('eq_reserv', $res['params']['params']['lims_id']);
            if ($reserv->id) {
                $form_params = [
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
                    'description' => $reserv->description,
                    'status' => $reserv->status,
                    'source_name' => LAB_ID,
                    'source_id' => $reserv->id,
                    'component_id' => $reserv->component_id,
                    'token' => $reserv->component->token,
                    'approval' => $reserv->approval,
                ];
            } else {
                $form_params['description'] = $res['error_msg'];
            }

            if ($job['session'] == 'ctrl-reserve') {

                $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
                $mq = new \Pheanstalk\Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

                if ($res['success']) {
                } else {
                    if ($res['error_msg']) {
                        $form_params['description'] = $res['error_msg'];
                        $form_params['error'] = true;
                    }
                }

                $payload = [
                    'method' => 'patch',
                    'path' => 'reserve/' . $job['yiqikong_id'],
                    'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                    'header' => [
                        'x-yiqikong-notify' => TRUE,
                    ],
                    'body' => $form_params
                ];
                $mq
                    ->useTube('cs_reserve')
                    ->put(json_encode($payload, TRUE));

            } else {
                $returnUrl = $job['server_url'] ?: "http://172.17.42.1:9898";
                if (!$this->clients[$returnUrl]) {
                    $this->clients[$returnUrl] = new \GuzzleHttp\Client([
                        'base_uri' => $returnUrl,
                        'http_errors' => false,
                        'timeout' => $client_config['timeout'] ?: 5,
                        'headers' => [
                            'Content-Type' => 'application/x-www-form-urlencoded',
                            'client_id' => $client_config['client_id'],
                            'client_secret' => $client_config['client_secret'],
                        ]
                    ]);
                }
                // 通知reserv-server
                $result = @$this->clients[$returnUrl]->post('/notify', [
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
    }

    public function __destruct()
    {
        flock($this->fp, LOCK_UN);
        fclose($this->fp);
        @unlink($this->mutex_file);
    }
}

$obj = new Tube($tube, $beanstalkdUrl);
$obj->task();
unset($obj);
