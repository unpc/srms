<?php
require ROOT_PATH . 'vendor/autoload.php';
use GuzzleHttp\Client;

class Nrii_Address {

    static function get_provinces () {
        return Q('address[level=province]')->to_assoc('adcode', 'name');
    }

    static function get_citys ($code) {
        if (!$code) return [];

        $prefix = substr($code, 0, 2);
        return Q("address[level=city][adcode^={$prefix}]")->to_assoc('adcode', 'name');
    }

    static function get_areas ($code) {
        if (!$code) return [];

        $prefix = substr($code, 0, 4);
        return Q("address[level=area][adcode^={$prefix}]")->to_assoc('adcode', 'name');
    }

    static function get_name ($code) {
        if (!$code) return [];

        $code_p = str_pad(substr($code, 0, 2), 6, '0');
        $name_p = O('address', ['adcode' => $code_p])->name;
        $code_c = str_pad(substr($code, 0, 4), 6, '0');
        $name_c = O('address', ['adcode' => $code_c])->name;
        $name = O('address', ['adcode' => $code])->name;
        return [
            'province' => $name_p,
            'city' => $name_c,
            'county' => $name,
        ];
    }

	static function sync_address() {
        $response = self::rest()->get('', [
            'query' => [
                'key' => Config::get('rest.amap')['key'],
                'subdistrict' => 3,
                'v' => 1.3
            ]
        ]);
        $body = $response->getBody();
        $content = $body->getContents();
        $result = json_decode($content, true);
        self::_update_address($result);
    }

    private static function _update_address ($content) {
        if ($content['status'] != 1) return;
        $country = $content['districts'][0];
        foreach ($country['districts'] as $province) {
            if ($province['level'] != 'province') continue;
            $address = O('address', ['adcode' => $province['adcode']]);
            if (!$address->id) {
                $address = O('address');
                $address->adcode = $province['adcode'];
                $address->level = 'province';
            }
            $address->name = $province['name'];
            $address->save();
            foreach ($province['districts'] as $city) {
                if ($city['level'] != 'city') continue;
                $address = O('address', ['adcode' => $city['adcode']]);
                if (!$address->id) {
                    $address = O('address');
                    $address->adcode = $city['adcode'];
                    $address->level = 'city';
                }
                $address->name = $city['name'];
                if ($address->name == '上海城区') {
                    $address->name = '上海市';
                } elseif ($address->name == '重庆城区') {
                    $address->name = '重庆市';
                } elseif ($address->name == '天津城区') {
                    $address->name = '天津市';
                } elseif ($address->name == '北京城区') {
                    $address->name = '北京市';
                }
                $address->save();
                foreach ($city['districts'] as $district) {
                    if ($district['level'] != 'district') continue;
                    $address = O('address', ['adcode' => $district['adcode']]);
                    if (!$address->id) {
                        $address = O('address');
                        $address->adcode = $district['adcode'];
                        $address->level = 'area';
                    }
                    $address->name = $district['name'];
                    $address->save();
                }
            }
        }
    }
    private static function rest () {
        $rest = Config::get('rest.amap');
        $client = new Client(['base_uri' => $rest['url'], 'timeout' => $rest['timeout']]);
        return $client;
    }
}