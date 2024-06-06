<?php

class Client_Vidmon implements Vidmon_Client_Handler
{
    private $client;
    private $server;
    private $equipment;
    private $control_address;
    
    public function __construct($vidcam)
    {
        $this->vidcam = $vidcam;
        $this->server = $vidcam->server;
        $this->control_address = $vidcam->control_address;
        $this->key = $vidcam->capture_key;
    }

    private function client()
    {
        $client_config = Config::get('vidmon.vidmon_server');
        if (!$this->client) {
            $this->client = new \GuzzleHttp\Client([
                'base_uri' => $this->server,
                'http_errors' => false,
                'timeout' => Config::get('device.vidmon.timeout', 5),
                'headers' => [
                    'client_id' => $client_config['client_id'],
                    'client_secret' => $client_config['client_secret'],
                ]
            ]);
        }
        return $this->client;
    }

    public function restart(): bool
    {
        $res = $this->client()->post('restart', [
            'form_params' => [
                // 'address' => $this->control_address,
                'key' => $this->key
            ]
        ])->getStatusCode();
        return $res == 200;
    }

    /**
     * 实时监控页面，需要增加upload capture频率
     *
     * @param [array] $opts
     * @return boolean
     */
    public function online_capture($opts): bool
    {
        $res = $this->client()->post('online_capture', [
            'form_params' => [
                'key' => $this->key,
                'opts' => $opts
            ]
        ])->getStatusCode();
        return $res == 200;
    }
    public function history_capture($opts): bool
    {
        $res = $this->client()->post('history_capture', [
            'form_params' => [
                'key' => $this->key,
                'opts' => $opts
            ]
        ])->getStatusCode();
        return $res == 200;
    }
}
