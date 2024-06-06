<?php

class API_Component {

    function src ($id = '', $query = '') {
        $user = $this->user();
        if (!$user->id) return FALSE;
        Cache::L('ME', $user);

        // 如果传递了组件的标志过来直接去获取组件
        if ($id) return Event::trigger("application.component.view.{$id}", $query);
        
        // 否则就去拿组件列表
        $components = new ArrayIterator();
        Event::trigger('application.component.views', $components);
        return (array)$components;
    }

    function setting ($view, $query, $ticket) {
        $user = $this->user();
        if (!$user->id) return FALSE;
        Cache::L('ME', $user);

        $settings = Event::trigger("application.component.settings.{$view}", $query, $ticket);
        if (!$settings) $settings = [
            'type' => false,
            'error' => false,
            'template' => ''
        ];
        return $settings;
    }

    protected function user () {
        $token = $_SERVER['HTTP_X_GINI_TOKEN'];
        $rest = Config::get('rest.dashboard');
        $client = new \GuzzleHttp\Client(['base_uri' => $rest['url'], 'timeout' => $rest['timeout'] ? : 5000]);

        $response = $client->get('user', [
            'headers' => [
                'X_GINI_TOKEN' => $token
            ]
        ]);
        $body = $response->getBody();
        $content = json_decode($body->getContents(), true);
        
        return O('user', ['email' => $content['email']]);
    }

    /*
    * url:http://192.168.32.48/lims/api
    * type:post
    * body:{"jsonrpc": "2.0", "method": "component/get_user_from_token","params": {"token":"5c6e6a4900173"}}
    */
    public function get_user_from_token($token) {
        if (!$token) throw new API_Exception('Invalid Param', 500);

        $cache = Cache::factory('redis');
        $user_id = $cache->get($token);

        if (!$user_id) {
            throw new API_Exception('Invalid Token', 500);
        } else {
            $user = O('user', $user_id);
            $group = O('tag_group', $user->group->id);

            $perms = ['管理所有内容', '管理组织机构', '查看所属机构统计汇总信息'];

            $data = [];
            foreach ($perms as $perm) {
                if ($user->access($perm, true)) {
                    $data[] = $perm;
                }
            }

            $res = [
                'name' => $user->name,
                'ref_no' => $user->ref_no,
                'group' => $group->path,
                'perms' => $data,
            ];

            return $res;
        }
    }
}
