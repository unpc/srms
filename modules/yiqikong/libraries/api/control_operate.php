<?php

class API_Control_Operate extends API_Common {

    public function door($data) {
        
        $this->_ready();
       
        $me = O('user', ['id'=>$data['user_local']]);
        $door = O('door',$data['door_id']);
        if(!$door->id || !$me->id)  throw new API_Exception;
        Cache::L('ME', $me);
        $direction = $data['ac'] == 'door_in' ? 'in' : 'out';

        if ($door->open_by_remote()) {
            return true;
        }

        $type = explode(':', $door->device['uuid'])[0];

        try {
            if ($type == 'cacs' || $type == 'icco') {
                $agent = new Device_Agent($door, false, $direction);
                if (!$agent->call('open')) {
                    throw new Exception;
                }

                if (Event::trigger('door.'.$direction, $door)) {
                    return true;
                }
            } else {
                $client = new \GuzzleHttp\Client([
                    'base_uri'    => $door->server,
                    'http_errors' => false,
                    'timeout'     => Config::get('device.gdoor.timeout', 5),
                ]);

                $success = (bool) $client->post('open', [
                    'form_params' => [
                        'uuid' => $door->device['uuid'],
                        'user' => [
                            'username' => $me->token,
                            'name'     => $me->name,
                        ],
                    ],
                ])->getBody()->getContents();

                if ($success && Event::trigger('door.'.$direction, $door)) {
                    return true;
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

}