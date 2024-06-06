<?php

class Client_Computer implements Client_Handler {
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

    function monitor_able(): bool {
       return EQ_Mon::support_capture($this->equipment);
    }
    
    function monitor_notice($form) {
        $channel = $form['channel'];
        $width = isset($form['width'])
        ? max(min($form['width'], 1280), 320)
        : Config::get('equipment.default_capture_size', 640);
        
        return json_decode($this->client()->post('cam_capture', [
            'form_params' => [
                'uuid' => $this->control_address,
                'width' => $width,
                'channel' => $channel,
                'url'=> URI::url("!eq_mon/capture/upload.{$this->equipment->id}"),
            ]
        ])->getBody()->getContents(), true);
    }

    function monitor_upload($form, $capture_file): bool {
        $device = $form['machine'];
        $now = time();
        if ($this->equipment->control_address != $device) throw new Error_Exception;
        if ($this->equipment->capture_key_mtime - 5 > $now) throw new Error_Exception;

        $key = $this->equipment->capture_key;
        if ($form['key'] && $key !== $form['key']) throw new Error_Exception;

        if(!$form['key']){
            //老版本 协议
            $private_key = openssl_get_privatekey(Config::get('equipment.private_key'));

            if (!openssl_private_decrypt(@base64_decode($form['signature']), $tmp_key, $private_key) || $key !== $tmp_key) {
                throw new Error_Exception;
            }
        }

        $tmp_file = $_FILES['image']['tmp_name'] ?: $_FILES['screenshot']['tmp_name'];
        
        if ($tmp_file) {
            File::check_path($capture_file);
            @move_uploaded_file($tmp_file, $capture_file);
        }
        return true;
    }

}
