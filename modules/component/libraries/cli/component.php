<?php
require ROOT_PATH . 'vendor/autoload.php';
use GuzzleHttp\Client;

class CLI_Component {

    static function get () {
        $rest = Config::get('rest.dashboard');
        $client = new Client(['base_uri' => $rest['url'], 'timeout' => $rest['timeout']]);
        $response = $client->get('application');
        $body = $response->getBody();
        $content = $body->getContents();
        echo print_r(json_decode($content), true) . "\n";
    }

    static function register () {
        $rest = Config::get('rest.dashboard');
        $client = new Client(['base_uri' => $rest['url'], 'timeout' => $rest['timeout']]);
        $response = $client->post('application', [
            'form_params' => [
                'name' => '大型仪器管理系统',
                'url' => 'http://192.168.5.50:5010/',
                'api' => 'http://192.168.5.50:5010/api',
                'settings' => 'component/settings',
                'view' => 'component/view',
                'key' => 'lims',
                'type' => 'rpc',
                'description' => '大型仪器管理系统',
            ]
        ]);
        $body = $response->getBody();
        $content = $body->getContents();
        echo print_r(json_decode($content), true) . "\n";
    }

}
