<?php

interface Client_Handler {
    function __construct($equipment);
    function monitor_notice($form);
    function monitor_upload($form, $capture_file): bool;
    function monitor_able(): bool;
}

class Equipment_Client {
    private $hander;

	function __construct($equipment) {
        $control_mode = $equipment->control_mode;
        if (!$equipment->server || !$control_mode) return;
        
        $class = 'Client_' . ucwords($control_mode);
        if (!class_exists($class)) return;

		$this->handler = new $class($equipment);
    }

    function monitor_able(): bool {
        if (!$this->handler) return false;
        return $this->handler->monitor_able();
    }
    
    function monitor_notice($form = []) {
        if (!$this->handler) return false;
        return $this->handler->monitor_notice($form);
    }

    function monitor_upload($form, $capture_file): bool {
        if (!$this->handler) return false;
        return $this->handler->monitor_upload($form, $capture_file);
    }
}
