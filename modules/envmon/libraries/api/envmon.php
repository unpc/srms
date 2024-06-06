<?php

/**
 * 修改程序时, 需同步修改文档: http://dev.genee.cn/doku.php/software/node/gstation/gt/
 *
 * 应用级别错误代码:
 * 1001: 请求来源非法!
 * 1002: 找不到对应的传感器!
 **/

use GuzzleHttp\Client;

class API_Envmon {

    public static $errors = [
        1001 => '请求来源非法!',
        1002 => '找不到对应的传感器!',
    ];

    private function _ready()
    {

        $whitelist   = Config::get('api.white_list_envmon', []);
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

    private function log()
    {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            if ($args) {
                $str = vsprintf($format, $args);
            } else {
                $str = $format;
            }
            Log::add("[envmon api] {$str}", 'devices');
        }
    }

    public function update($addr, $value, $time)
    {

        $sensor = O('env_sensor', ['address' => $addr]);

        if (!$sensor->id) {
            throw new API_Exception(self::$errors[1002], 1002);
        }

        // 20184732 中国农业科学院哈尔滨兽医研究所环境监控多台仪器无曲线
        // 从env-server上传的时间不能大于当前时间，否则更新很大的一个last_datapoint_time之后，后续正常记录将无法上传
        // 之所以不直接使用当前时间，是由于考虑历史记录上传
        $time = min(Date::time(), $time);

        $this->log("更新传感器 %s[%d] %s 的数值为 %s", $sensor->name, $sensor->id,
            Date::format($time, 'Y/m/d H:i:s'), $value);

        $this->_update_sensor_value($sensor, $value, $time);

        return true;
    }

    private function _update_sensor_value($sensor, $value, $time)
    {

        // 保留两位小数
        $value = round($value, 2);

        // actual datapoint
        $actual_point           = O('env_actual_datapoint');
        $actual_point->sensor   = $sensor;
        $actual_point->ctime    = $time;
        $actual_point->exp_time = $time + $sensor->interval;
        $actual_point->value    = $value;
        $actual_point->save();
        ORM_Pool::release($actual_point);

        // datapoint
        // if time > (last insert + interval): insert datapoint
        if (($time - $sensor->last_datapoint_time) > $sensor->interval) {

            $sensor->last_datapoint_time = $time;

            // clean up
            $db    = ORM_Model::db('env_actual_datapoint');
            $query = sprintf("DELETE FROM env_actual_datapoint WHERE exp_time < %d", $time);
            $db->query($query);

            // save datapoint
            $point = O('env_datapoint');
            $point->sensor = $sensor;
            $point->ctime  = $time;
            $point->value  = $value;
            $point->save();
            ORM_Pool::release($point);
        }

        // sensor
        $sensor->value = $value;
        $sensor->save();

    }

}
