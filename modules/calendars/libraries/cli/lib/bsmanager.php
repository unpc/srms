<?php
require __DIR__.'/consistenthashing.php';

class BSManager
{
    private $_bh;
    private $_worker_num;
    // 还在working的子进程个数
    private $_working_cnt = 0;

    // 记录worker工作过程中各个tube 的状态, 
    // 一来防止同一个tube被分配到不同worker, 
    // 二来多个worker同时工作其中一个worker结束时, ignore的tube可以进入下次的dispatch

    // tube状态    无人预约    预约且worker开始watch   worker退出
    // working     x           √                       x
    // done        x           x                       √
    private $_working_tubes = [];
    private $_done_tubes = [];
    static $SHA1_PATTERN = '/\b[0-9a-f]{5,40}\b/';

    public function __construct($worker_num, $host, $port)
    {
        $this->_bh = new \Pheanstalk\Pheanstalk($host, $port);
        $this->_worker_num = $worker_num;
    }

    public function add_done($tubes) {
        foreach ($tubes as $tube) {
            unset($this->_working_tubes[$tube]);
            $this->_done_tubes[$tube] = $tube;
        }
        $this->_working_cnt--;
    }

    public function dispatch()
    {
        if ($this->_worker_num <= $this->_working_cnt) {
            throw new Exception('worker 数量不足, 主进程直接重启重新分配资源');
        }
        $tube_groups = [];
        $con = new ConsistentHashing();
        for ($i = 0; $i < $this->_worker_num - $this->_working_cnt; $i++) {
            $con->addNode($i);
        }

        foreach ($this->_bh->listTubes() as $tube) {
            if (preg_match(self::$SHA1_PATTERN, $tube)) {
                if (in_array($tube, $this->_working_tubes)
                    && !in_array($tube, $this->_done_tubes)) {
                    continue;
                }
                $key = $con->lookup($tube);
                if (!isset($tube_groups[$key])) {
                    $tube_groups[$key] = [];
                }
                $tube_groups[$key][$tube] = $tube;
                $this->_working_tubes[$tube] = $tube;
                unset($this->_done_tubes[$tube]);
            }
        }

        $this->_working_cnt += count($tube_groups);
        return $tube_groups;

    }

    public function notify($job, $res) {
        if (!$res['uuid']) {
            // 添加异常或者500会走到这里来，此时是没有uuid，消息投递进队列消费端也不能识别，先直接阻断
            return true;
        }

        $source = explode('_', $res['uuid'])[1];

        switch ($source) {
            case 'reserv-server':
                $payload = [
                    'id' => $job['session'],
                    'tube' => $job['tube'],
                    'key' => 'yiqikong-reserv-reback',
                    'value' => $res
                ];
                break;
            default:
                $payload = $res;
                break;
        }
        // error_log(print_r($payload,1));

        $this->_bh->useTube('reserv-server-message')->put(json_encode($payload));
    }
}
