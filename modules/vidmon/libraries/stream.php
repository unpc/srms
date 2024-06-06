<?php

use GuzzleHttp\Client;

class Stream {

    static function refresh_list() {
        $config = Config::get('stream');

        if ($config['use_stream']) {
            $me = L('ME');

            $client = new Client([
                'base_uri' => $config['rtsp.url'].'/sources',
                'timeout' => $config['rtsp.timeout'],
                'http_errors' => FALSE,             
            ]);

            $res = $client->get('');
            $list = json_decode($res->getBody()->getContents(), TRUE);

            if ($list) foreach ($list as $key => $value) {
                $vidcam = O('vidcam', ['uuid' => $key]);
                if (!$vidcam->id) {
                    continue;
                }
                $vidcam->name = $vidcam->name ? : $value['name'];
                $vidcam->type = Vidcam_Model::TYPE_STREAM;
                $vidcam->stream_address = $config['ctrl.addr.prefix'].'/'.trim($key).'/index.m3u8';
                $vidcam->ip_address = $value['ip'];
                $vidcam->uuid = $key;

                $vidcam->save();
            }

            Log::add(strtr('[vidmon] %user_name[%user_id] 刷新了视频监控流媒体的列表', [
                '%user_name'=> $me->name, 
                '%user_id'=> $me->id
            ]), 'journal');

            return TRUE;
        }

        return FALSE;
    }
}
