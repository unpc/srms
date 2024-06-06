<?php

class BSWorker
{
    public $reserveTimeout = 1;
    public $maxProcessedJobs = 5;
    public $tubes = [];

    private $_bh;
    private $_pipePath;
    private $_tubeCounter = [];

    public function __construct($pipe, $host, $port)
    {
        $this->_bh = new \Pheanstalk\Pheanstalk($host, $port);
        $this->_pipePath = $pipe;
    }

    public function watch($tubes, $jobCallback)
    {
        $this->tubes = $tubes;
        $this->_tubeCounter = array_fill_keys($tubes, 0);
        array_walk($this->tubes, [$this->_bh, 'watch']);

        $pauseTubes = [];
        while (true) {
            $job = $this->_bh->reserve($this->reserveTimeout);
            if ($job === false) {
                if (count($pauseTubes) == 0) {
                    $file = fopen($this->_pipePath, 'w');
                    fwrite($file, json_encode($this->tubes)."\n");
                    break;
                }
                // reserveTimeout内没有可用的JOB的话, 把之前暂时禁用的TUBE都继续监听起来
                foreach ($pauseTubes as $tube) {
                    $this->_tubeCounter[$tube] = 0;
                    $this->_bh->watch($tube);
                }
                $pauseTubes = [];
                continue;
            }

            $jobStat = $this->_bh->statsJob($job);
            $jobTube = $jobStat->tube;
            $this->_bh->delete($job);
            $jobCallback($job->getData());

            $this->_tubeCounter[$jobTube]++;
            if ($this->_tubeCounter[$jobTube] > $this->maxProcessedJobs) {
                // 一个TUBE如果挤进来的任务太多了, 先暂时忽略掉这个TUBE, 看看其他TUBE有没有值
                $this->_bh->ignore($jobTube);
                $pauseTubes[] = $jobTube;
            }
        }
    }
}
