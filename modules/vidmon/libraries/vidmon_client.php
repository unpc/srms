<?php
/**
 * LIMS 向 vidmon-server 发送消息接口
 */
interface Vidmon_Client_Handler
{
    public function __construct($vidcam);
    public function restart(): bool;
    public function online_capture($opts): bool;
    public function history_capture($opts): bool;
}

class Vidmon_Client
{
    private $hander;

    public function __construct($vidcam)
    {
        if (!$vidcam->server) {
            return;
        }
        
        // TODO: dependency injection? 当有其他[vidmon]-server引入时
        $class = 'Client_Vidmon';
        if (!class_exists($class)) {
            return;
        }

        $this->handler = new $class($vidcam);
    }

    public function restart(): bool
    {
        if (!$this->handler) {
            return false;
        }
        return $this->handler->restart($opts);
    }
    
    public function online_capture($opts = [])
    {
        if (!$this->handler) {
            return false;
        }
        return $this->handler->online_capture($opts);
    }

    public function history_capture($opts): bool
    {
        if (!$this->handler) {
            return false;
        }
        return $this->handler->history_capture($opts);
    }
}
