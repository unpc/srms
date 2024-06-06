<?php

use \Pheanstalk\Pheanstalk;
class Yiqikong_User {
    // 用于扩展 user_links
    static function user_links($e, $user, $links, $mode) {
        $me = L('ME');

        switch ($mode) {
            case 'view' :
                if ($me->id == $user->id && Config::get('lab.modules')['app'] && !$me->is_expired()&& !$user->has_bind_app()) {
                    $links['app'] = [
                        'html'=> (string) V('yiqikong:user/links/app_bind', ['user' => $user]),
                    ];
                }
                if ($me->id == $user->id && Config::get('lab.modules')['app'] && !$me->is_expired() && $user->has_bind_app()) {
                    $links['app'] = [
                        'html'=> (string) V('yiqikong:user/links/app_unbind', ['user' => $user]),
                    ];
                }
        }
    }

    public static function make_user($info) {
        $info[user_info][user_local]
        and $user = O('user', ['id' => $info[user_info][user_local]]);

        !$user->id
        and $info[user] and $user = O('user', ['gapper_id' => $info[user]]);

        !$user->id
        and $user = O('user', ['yiqikong_id' => $info['user_info']['yiqikong_id']]);

        !$user->id
        and $user = O('user', ['gapper_id' => $info[user_info][gapper_id]]);

        !$user->id
        and $user = O('user', ['email' => $info[user_info][email]]);

        if (isset($info['user_info']['user_local']) && $info['user_info']['user_local']){
            $user = O('user',$info['user_info']['user_local']);
        }

        $_createYiQiKongUser = function() use ($info) {
            $token = Auth::make_token($info['user_info']['email'], 'yiqikong');

            $user = O('user');
            $user->token = $token;
            $user->name = $info[user_info][username];
            $user->email = $info[user_info][email];
            $user->gapper_id = $info[user_info][gapper_id];
            $user->yiqikong_id = $info['user_info']['yiqikong_id'];
            $user->outside = 1;
            $user->hidden = 1;
            $user->atime = Date::time();
            $user->save();
            $lab = YiQiKong_Lab::default_lab($info[equipment]);
            $user->connect($lab);

            // 获取人员类型数组
            foreach (User_Model::get_members() as $key => $value) foreach ($value as $k => $v) {
                if ($k != $user->member_type) continue;
                $user_role = $key;
                $user_member = $v;
                break;
            }

            // 获取人员组织机构数组
            $root = Tag_Model::root('group');
            
            $config = Config::get('rest.yiqikong_user'); // ctrl层没有做完全就这样吧
            $client = new \GuzzleHttp\Client([
                'base_uri' => $config['url'],
                'http_errors' => FALSE,
                'timeout' => $config['timeout']
            ]);

            // TODO: 有待完善 真的这么写就行么
            $response = $client->post("v2/user/node", [
                'headers' => [
                    'x-yiqikong-notify' => true,
                ],
                'form_params' => [
                    'id' => $user->id,
                    'lab' => LAB_ID,
                    'lab_name' => Config::get('page.title_default'),
                    'user' => $user->yiqikong_id,
                    'email' => $user->email,
                    'role' => Q("$user role")->to_assoc('id', 'name'),
                    'type' => [$user_role, $user_member],
                    'group' => Q("{$user} tag_group[root={$root}]")->to_assoc('id', 'name'),
                    'phone' => $user->phone,
                ]
            ])->getBody()->getContents();
            return $user;
        };

        !$user->id
        and $user = $_createYiQiKongUser();

        return $user;
    }

    static function on_follow_saved($e, $follow, $old_data, $new_data) {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $payload = [
            'method' => 'post',
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => "user/follow",
            'body' => [
                'user' => $follow->user->yiqikong_id,
                'source_name' => $follow->object_name,
                'source_id' => $follow->object_id,
                'uuid' => $follow->object->yiqikong_id,
                'user_local' => $follow->user->id
            ]
        ];
        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    static function on_follow_deleted($e, $follow) {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $payload = [
            'method' => 'delete',
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => "user/follow",
            'body' => [
                'user' => $follow->user->yiqikong_id,
                'source_name' => $follow->object_name,
                'source_id' => $follow->object_id,
                'uuid' => $follow->object->yiqikong_id,
                'user_local' => $follow->user->id
            ]
        ];
        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));
            
        return TRUE;
    }

    static function on_user_role_saved($e, $user, $add_roles, $substract_roles) {
        self::post_tag($user);
        return TRUE;
    }

    static function on_user_role_perm_saved($e, $role) {
        $users = Q("$role user");
        foreach ($users as $user) {
            if ($user->yiqikong_id != '') {
                self::post_tag($user);
            }
        }
    }

    static function post_tag($user) {
        if (!$user->yiqikong_id) return;
        list($token, $backend) = explode('|', $user->token);
        $tag_name = [];
        if ($token == 'genee') $tag_name[] = 'genee';
        if ($user->access('管理所有内容')) $tag_name[] = 'admin';
        if (count($tag_name) > 0 ) {
            $config = Config::get('beanstalkd.opts');
            $mq = new Pheanstalk($config['host'], $config['port']);
            $payload = [
                'method' => "post",
                'header' => ['x-yiqikong-notify' => TRUE],
                'path' => "tag",
                'body' => [
                    'user' => $user->yiqikong_id,
                    'tag_name' => json_encode($tag_name),
                    'tag_type' => 'role'
                ]
            ];
            $mq
                ->useTube('user')
                ->put(json_encode($payload, TRUE));
        } else {
            $config = Config::get('beanstalkd.opts');
            $mq = new Pheanstalk($config['host'], $config['port']);
            $payload = [
                'method' => "delete",
                'header' => ['x-yiqikong-notify' => TRUE],
                'path' => "tag/{$user->yiqikong_id}",
                'body' => [
                    'tag_type' => 'role',
                ],
            ];
            $mq
                ->useTube('user')
                ->put(json_encode($payload, TRUE));
        }
    }

    static function user_extra_keys($e, $user, $info)
    {
        $info['yiqikong_id'] = $user->yiqikong_id;

        $token = uniqid();
        $cache = Cache::factory('redis');
        $cache->set("qrcode_{$user->id}", $token, 300);
        $info['code'] = $token;
    }

    static function user_has_bind_app($e, $user)
    {
        // PD230501 APP1.6.6全面测试
        $e->return_value = false;
        return;
        // 目前是通过yiqikong_id判读，也许后面是直接访问control的接口
        if ($user->yiqikong_id) {
            $e->return_value = true;
        } else {
            $e->return_value = false;
            $server = Config::get('app.control_user');
            $rest = new REST($server['url']);
            $response = $rest->get("v2/user/node?lab_id=".LAB_ID."&source_id=$user->id");
            if (isset($response['data']) && count($response['data'])) {
                $e->return_value = true;
            }
        }
    }
}