<?php
class CLI_Hik_Door {
    static function sync_doors() {
        $config = Config::get('hik.door');
        $client = new \GuzzleHttp\Client([
            'base_uri' => $config['url'],
            'http_errors' => FALSE,
            'timeout' => 5
        ]);

        $start = 0;
        $step = 20;

        while (true) {
            $response = $client->post("getDoorList", [
                'form_params' => [
                    'start' => $start,
                    'step' => $step,
                ]
            ])->getBody()->getContents();

            $response = json_decode($response, true);
            $data = $response['data'];
            $total = $response['total'];

            foreach ($data as $d) {
                $door = O('door', ['name' => $d['doorName']]);
                if (!$door->id) {
                    continue;
                }

                $door->in_addr = 'hdoor://' . $d['doorUuid'];
                if ($door->save()) {
                    Log::add(strtr('[hik_door] 门禁 %name [%id] 更新成功, ctrl-address: %uuid', [
                        '%name' => $door->name,
                        '%id' => $door->id,
                        '%uuid' => $door->in_addr,
                    ]), 'devices');
                }
            }
            $start += 1;
            if ($total < $start * $step) {
                break;
            }
        }
    }
}
