<?php

class Gateway
{
    public static function setup()
    {
        Event::bind('profile.edit.tab', "Gapper_User::edit_gapper_user");
    }

    public static function exec($url, $method = 'GET', $options = [])
    {
        try {
            $client   = new \GuzzleHttp\Client(['timeout' => 30, 'verify' => false]);
            $response = $client->request($method, $url, $options);
            if ($response->getStatusCode() == 200) {
                $response = json_decode($response->getBody()->getContents(), true);
                return $response;
            }
        } catch (Exception $e) {
            // $info = json_encode([
            //     'url'     => $url,
            //     'message' => $e->getMessage(),
            //     'code'    => $e->getCode(),
            // ], JSON_UNESCAPED_SLASHES);
            // Log::add($info, 'gateway');
            // if (PHP_SAPI == 'cli') {
            //     Upgrader::echo_fail($e->getMessage());
            //     $cache = Cache::factory();
            //     $cache->remove('access_token');
            // }
            // if ($e->getCode() == 401) {
            //     Auth::logout();
            //     URI::redirect('/');
            //     exit(0);
            // } else if ($e->getCode() == 404) {
            //     // URI::redirect('error/404',['msg'=>'该资源在gapper不存在']);
            // } else {
            //     throw new GapperException("<h2>gapper 服务不可用</h2>");
            // }
        }
        return false;
    }

    public static function __callStatic($function, $args = [])
    {
        $args = $args[0] ?: []; // 参数作用数组传入，第一个元素即所有参数

        // 接口不存在
        $func = Config::get('gateway.' . $function);
        if (!$func) {
            return false;
        }

        // 生成缓存key $args目前为一维数组，直接implode即可
        $cache_key = $function . '-' . implode('-', $args);
        $Cache     = Cache::factory();

        // 这里通过静态变量保证一次访问过程中同一个接口只调用一次
        $data = L($cache_key);
        if ($data) {
            return $data;
        }
        // 如果配置文件中存在expires_in则走redis
        if (isset($func['expires_in'])) {
            $data = $Cache->get($cache_key);
            if ($data) {
                return $data;
            }
        }
        // 替换path中的参数 例：/group/{GROUP_ID}/ => /group/1/
        if ($args) {
            foreach ($args as $key => $value) {
                $paramName = '{' . strtoupper($key) . '}';
                if (strpos($func['path'], $paramName) != false) {
                    $func['path'] = str_replace($paramName, $value, $func['path']);
                    unset($args[$key]);
                }
            }
        }

        $server = Config::get('gateway.server');

        $access_token = static::getToken();
        $remote_url   = $server['url'];
        $url          = $remote_url . $func['path'];
        $response     = [];
        $options      = ['headers' => ['X-Gapper-OAuth-Token' => $access_token]];
        $params       = $args;

        // TODO: 上线后可删除该行，现用于调试 request_url.log中的链接直接可用
        // $params['gapper-oauth-token'] = $access_token;

        switch (strtoupper($func['method'])) {
            case 'GET':
                $url .= '?' . static::build_query($params);
                break;
            case 'POST':
                $options += ['form_params' => $params];
                break;
            case 'DELETE':
                $options += ['form_params' => $params];
                break;
        }
        $response = static::exec($url, $func['method'], $options);
        Cache::L($cache_key, $response);
        if (isset($func['expires_in'])) {
            $Cache->set($cache_key, $response, $func['expires_in'] * 60);
        }
        return $response;
    }

    public static function build_query($params)
    {
        $str    = '';
        $i      = 0;
        $length = count($params);
        foreach ($params as $key => $value) {
            $str .= "$key=$value";
            if ($i < $length - 1) {
                $str .= '&';
            }
            $i++;
        }
        return $str;
    }

    public static function refreshToken($refreshToken)
    {
        $server  = Config::get('gateway.server');
        $options = [
            'json' => [
                'client_id'     => $server['params']['client_id'],
                'client_secret' => $server['params']['client_secret'],
                // 'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ];
        $request_url = $server['url'] . 'auth/refresh-token';
        $response = static::exec($request_url, 'POST', $options);
        if (!$response['access_token']) {
            $cache = Cache::factory();
            $cache->remove('refresh_token');
            if (PHP_SAPI != 'cli') {
                Auth::logout();
                URI::redirect('/');
            }
            return null;
        }

        return $response;
    }

    public static function getToken()
    {
        $cache        = Cache::factory();
        $access_token = $cache->get('access_token');

        if ($access_token) {
            return $access_token;
        }

        $refreshToken = $cache->get('refresh_token');
        if ($refreshToken) {
            $data = self::refreshToken($refreshToken);
            if ($data['access_token'] && $data['expires_in']) {
                $cache->set('access_token', $data['access_token'], $data['expires_in'] - 100);
                return $data['access_token'];
            }
        }

        $server  = Config::get('gateway.server');
        $options = [
            'json' => $server['params'],
        ];
        $request_url = $server['url'] . 'auth/app-token';
        $auth        = static::exec($request_url, 'POST', $options);
        $cache->set('access_token', $auth['access_token'], $auth['expires_in'] - 100);
        // refresh_token默认有效期为7天
        $cache->set('refresh_token', $auth['refresh_token'], 86400 * 7 - 100);
        return $auth['access_token'];
    }

    /**
     * 验证用户名密码是否正确
     */
    public static function verify($username, $password)
    {
        $request_url = Config::get('gateway.domain') . Config::get('gateway.api')['verify'];
        $rpc      = new RPC($request_url);
        $response = $rpc->gateway->auth->verify($username, $password);
        return $response;
    }

    public static function getRemoteGroupRootCached()
    {
        $conf_id_name = 'gapper_system_group_id';
        $group = O('tag_group', ['id' => Lab::get($conf_id_name)]);
        if (!$group->id) {
            $info = Gateway::getRemoteGroupRoot();
            $group = O('tag_group', ['gapper_id' => $info['id']]);
            $group->gapper_id = $info['id'];
            $group->name = $info['name'];
            $group->type = $info['type'];
            $group->root = $root;
            if ($info['code']) $group->code = $info['code'];
            if ($info['description']) $group->description = $info['description'];
            $group->parent = Tag_Model::root('group');
            $group->save();
            Lab::set($conf_id_name, (int) $group->id);
        }
        return $group;
    }

    static function operate_lab_is_allowed($e, $user, $perm, $object, $options) {
        switch($perm) {
            case '添加':
                if (People::perm_in_uno()){
                    $e->return_value = false;
                    return FALSE;
                }
                break;
        }
    }

    public static function user_ACL($e, $user, $perm, $object, $options)
    {
        switch ($perm) {
            case '添加':
                if (People::perm_in_uno()) {
                    $e->return_value = false;
                    return false;
                }
                break;
        }
    }

}
