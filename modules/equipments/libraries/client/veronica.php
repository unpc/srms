<?php

class Client_Veronica implements Client_Handler {
    private $client;
    private $server;
    private $equipment;
    private $control_address;
	
	function __construct($equipment) {
        $this->equipment = $equipment;
        $this->server = $equipment->server;
        $this->control_address = $equipment->control_address;
    }

    private function client() {
        if (!$this->client) {
            $this->client = new \GuzzleHttp\Client([
                'base_uri' => $this->server, 
                'http_errors' => FALSE, 
                'timeout' => Config::get('device.computer.timeout', 5)
            ]);
        }
        return $this->client;
    }

    private function monitor_key() {
        return "client_monitor_key_{$this->equipment->id}";
    }

    function monitor_able(): bool {
        return true;
    }
    
    function monitor_notice($form) {
        // 为了安全起见 签发一个10分钟的key
        $cache = Cache::factory('redis');
        if (!$cache->get($this->monitor_key())) {
            $cache->set($this->monitor_key(), uniqid(), 600);
        }
        $key = $cache->get($this->monitor_key());
        
        return json_decode($this->client()->post('capture', [
            'form_params' => [
                'uuid' => $this->control_address,
                'key' => $key,
                'url'=> URI::url("!eq_mon/capture/upload.{$this->equipment->id}"),
            ]
        ])->getBody()->getContents(), true);
    }

    function monitor_upload($form, $capture_file): bool {
        
        $cache = Cache::factory('redis');
        if (!$cache->get($this->monitor_key()) || $cache->get($this->monitor_key()) != $form['key']) throw new Error_Exception;

        list(, $data) = explode(',', $form['imgData']);
        if ($data) {
            File::check_path($capture_file);
            file_put_contents($capture_file, base64_decode($data));//返回的是字节数
        }
        
        return true;
    }

}
