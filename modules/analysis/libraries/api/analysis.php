<?php
require ROOT_PATH . 'vendor/autoload.php';
use GuzzleHttp\Client;

class API_Analysis {

    function criteria($string, $role) {
        // 所有的数据分析数据集成请求都应该对这个接口做请求
        // 带身份信息过来
        // 根据身份信息强势返回相应的固定条件 限制各个身份的用户对数据聚合的查询
        list($application, $origin, $field) = explode('.', $string);
        $config = Config::get('analysis.application');
        if ($application != $config['key']) return FALSE;
        $user = Event::trigger('analysis.get.user') ? : $this->user();
        if (!$user->id) return FALSE;
        if (is_array($role)) {
            $result = [];
            foreach ($role as $item) {
                $result[$item] = Event::trigger("analysis.limit.{$origin}", $user, $item, $config, $origin);
            }
            return $result;
        }
        else return Event::trigger("analysis.limit.{$origin}", $user, $role, $config, $origin);
    }

    protected function user() {
        $token = $_SERVER['HTTP_G_ACCESS_TOKEN'];
        $rest = Config::get('rest.analysis')['godiva'];

        // 用给予的token再去获取用户
        $client = new Client(['base_uri' => $rest['url'], 'timeout' => $rest['timeout']]);
        $response = $client->get('user', [
            'headers' => [
                'HTTP_G_ACCESS_TOKEN' => $token
            ]
        ]);
        $body = $response->getBody();
        $content = $body->getContents();
        $user = json_decode($content);

        return O('user', ['ref_no' => $user['ref_no']]);
    }

}
