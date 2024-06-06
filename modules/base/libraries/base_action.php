<?php

class Base_Action
{
    public static function action($e, $controller, $method, $params)
    {
        if (in_array($method, Config::get('action.shield_methods'))) {
            return;
        } else {
            $base_action = O('base_action');
            $point = O('base_point', ['sid' => session_id()]);
            $module = MODULE_ID;
            $base_action->ctime = time();
            $base_action->base_point = $point;
            $base_action->module = $module;
            $base_action->action = $method;
            if (Base_Action::not_fresh()) {
                $action = Q("base_action[base_point={$point}][module={$module}][action={$method}]")->current();
                if ($action->id) {
                    if (time() - $action->ctime > 60) {
                        $base_action->save();
                    } else {
                        return;
                    }
                } else {
                    $base_action->save();
                }
            }
        }
    }

    public static function get_location($ip)
    {
        $url = Config::get('service.location')['url'];
        $ak = Config::get('service.location')['ak'];
        $sk = Config::get('service.location')['sk'];
        $params = [
            'ak' => $ak,
            'ip' => $ip,
            'coor' => 'bd09ll'
        ];
        $params['sn'] = self::caculateAKSN($params);
        $url = $url.'?'.http_build_query($params);
        // $url = $url.'?ak='.$ak.'&ip='.$ip;

        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            $output = curl_exec($curl);
            if (curl_errno($curl)) {
                echo 'Errno: '.curl_error($curl);
            }
            curl_close($curl);
            return json_decode($output, true);
        } catch (Exception $e) {
            return false;
        }
    }

    private static function not_fresh()
    {
        $pageWasRefreshed = isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'];
        if ($pageWasRefreshed) {
            return false;
        } else {
            return true;
        }
    }

    private static function caculateAKSN($querystring_arrays, $method = 'GET')
    {
        $sk = Config::get('service.location')['sk'];
        $url = Config::get('service.location')['url_c'];
        if ($method === 'POST') {
            ksort($querystring_arrays);
        }
        $querystring = http_build_query($querystring_arrays);
        return md5(urlencode($url.'?'.$querystring.$sk));
    }
}
