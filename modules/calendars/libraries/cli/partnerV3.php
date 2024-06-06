<?php
require __DIR__ . '/../../../../vendor/autoload.php';
require __DIR__ . '/lib/bsmanager.php';
$beanstalkdHost = $argv[1] ?: '172.17.42.1';
$beanstalkdPort = $argv[2] ?: '11300';

const WORKER_NUM = 4;

$pid = posix_getpid();
echo "pid: {$pid} main progress start!\n";

$manager = new BSManager(WORKER_NUM, $beanstalkdHost, $beanstalkdPort);
$tube_groups = $manager->dispatch();

$clients = [];

while (true) {
    foreach ($tube_groups as $key => $tube_group) {
        $pipePath = $temp_file = sys_get_temp_dir() . "/reserv.pipe";
        if (!file_exists($pipePath)) {
            if (!posix_mkfifo($pipePath, 0666)) {
                exit('make pipe false!' . PHP_EOL);
            }
        }

        $pid = pcntl_fork();
        if ($pid == -1) {
            // 创建失败
            exit("fork progress error!\n");
        } elseif ($pid == 0) {
            require_once __DIR__ . '/lib/bsworker.php';

            // 子进程执行程序
            $pid = posix_getpid();
            echo "pid: {$pid} child progress start!\n";

            $worker = new BSWorker($pipePath, $beanstalkdHost, $beanstalkdPort);
            $worker->watch($tube_group, function ($data) use ($tube_group, $manager) {
                $job = json_decode($data, true);
                $_SERVER['SITE_ID'] = $job['SITE_ID'];
                $_SERVER['LAB_ID'] = $job['LAB_ID'];
                require_once __DIR__ . '/../../../../cli/base.php';
                Log::add(strtr('Tube Reserved Data: %job', ['%job' => $data]), 'reserv');

                // 由于大数据并发会出现投错数据或者抓错数据的现象，这个地方进行简单的自我判断
                if (!in_array($job['tube'], $tube_group)) {
                    Log::add(strtr('Tube Returned Data: 获取到数据 tube  %tube1 与监控 tube %tube2 不同，抛弃数据。', [
                        '%tube1' => $job['tube'],
                        '%tube2' => join(', ', $tube_group)
                    ]), 'reserv');
                    return;
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
                    /**
                     * 临时这样处理
                     * */
                    $untro = O('yiqikong_approval_uncontrol',['equipment'=>$reserv->equipment,'approval_type' => 'eq_reserv']);
                    if(Module::is_installed('yiqikong_approval') &&
                        Approval_Access::check_user($untro, $reserv->user)){
                        $reserv->approval = Approval_Model::RESERV_APPROVAL_PASS;
                    }
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

                    $form_params = $params ? array_merge($params,$form_params) : $form_params;
                    $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
                    $mq = new \Pheanstalk\Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

                    if ($res['success']) {
                        $form_params['state'] = $params['lims_id'] ? Common_Base::STATE_UPDATE : Common_Base::STATE_SUCCESS;
                        $form_params['source_id'] = $reserv->id;
                        $form_params['user_local'] = $reserv->user->id;

                        $lab = Q("{$reserv->user} lab")->current();
                        $form_params['lab_name'] = $lab->name ?? '';
                        $form_params['phone'] = $reserv->user->phone;
                        $form_params['project'] = $reserv->project_id;
                        $form_params['lab_id'] = $lab->id;
                    } else {
                        if ($res['error_msg']) {
                            $form_params['description'] = $res['error_msg'];
                            $form_params['error'] = true;
                            $form_params['state'] = $params['lims_id'] ? Common_Base::STATE_UPDATE_FAILED : Common_Base::STATE_FAILED;
                            // 获取人的信息
                            if (isset($params['user_info']['user_local'])
                                && !$info['user_info']['user_local']
                                && isset($params['user_info']['yiqikong_id'])
                                && $info['user_info']['yiqikong_id']){
                                $user = O('user',['yiqikong_id' => $info['user_info']['yiqikong_id']]);
                                $form_params['user_local'] = $user->id;
                                $lab = Q("{$user} lab")->current();
                                $form_params['lab_name'] = $lab->name ?? '';
                                $form_params['phone'] = $user->phone;
                                $form_params['lab_id'] = $lab->id;
                            }
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
                        ->useTube('stark')
                        ->put(json_encode($payload, TRUE));
                } else {
                    // 通知reserv-server
                    $manager->notify($job, $res);
                }

                // 关于仪器预约设置的属性会被 ORM 缓存，所以就会引发，此 process 不销毁，相关属性的更改不生效
                ORM_Pool::$cleanup_timeout = 0;
                ORM_Pool::cleanup();

                Log::add(strtr('Child Process Returned Data: %result', ['%result' => print_r($res, 1)]), 'reserv');
            });
            exit("pid: {$pid} child progress end!\n");
        } else {
            //父进程读管道
            $file = fopen($pipePath, 'r');
            $content = '';
            while (true) {
                $content .= fread($file, 1024);
                if (substr($content, -1) == "\n") {
                    $done_tubes = json_decode($content, true);
                    $manager->add_done($done_tubes);
                    $content = '';
                    break;
                }
            }
        }
    }

    pcntl_wait($status, WNOHANG);
    sleep(5);
    $tube_groups = $manager->dispatch();
}

echo "pid: {$pid} main progress end!\n";
