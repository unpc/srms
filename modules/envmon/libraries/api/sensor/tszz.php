<?php

class API_Sensor_TSZZ {

    public static $errors = [
        1001 => '请求来源非法!',
        1002 => '找不到对应的传感器!',
    ];

    private function _ready() {

        $whitelist = Config::get('api.white_list_tszz', []);
        $whitelist[] = $_SERVER['SERVER_ADDR'];

        if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            return;
        }

        // whitelist 支持ip段配置 形如 192.168.*.*
        foreach ($whitelist as $white) {
            if (strpos($white, '*')) {
                $reg = str_replace('*', '(25[0-5]|2[0-4][0-9]|[0-1]?[0-9]?[0-9])', $white);
                if (preg_match('/^'.$reg.'$/', $_SERVER['REMOTE_ADDR'])) {
                    return;
                }
            }
        }
        throw new API_Exception(self::$errors[1001], 1001);

    }

	public function get_addresses($channel) {

        $this->_ready();

        $prefix = 'tszz://'.$channel.'/';
        $db = ORM_Model::db('env_sensor');
        $query = $db->query("SELECT * FROM env_sensor WHERE address LIKE '%s%%'", $prefix);

        $ret = [];

        if ($query) while ($sensor = $query->row()) {

            $ret[$sensor->id] = trim(strtr($sensor->address, [$prefix => '']));

        }

        return $ret;
	}

}
