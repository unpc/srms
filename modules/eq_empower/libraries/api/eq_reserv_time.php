<?php

class API_EQ_Reserv_Time {
	
	public static $errors = [
        401 => 'Access Denied',
        500 => 'Internal Error'
    ];

    private function _ready() {
        // TODO config-able whitelist
        $whitelist = Config::get('api.security_ip');
        $whitelist[] = '127.0.0.1';
        $whitelist[] = '172.17.42.1';
        $whitelist[] = $_SERVER["SERVER_ADDR"];

        if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist) && false) {
            throw new API_Exception(self::$errors[401], 401);
		}
		return;
    }

    static function get($id) {
        $equipment = O('equipment', $id);
        if (!$equipment->id) return [];

        $end = Date::time();
        $times = Q("eq_reserv_time[equipment={$equipment}][ltend>{$end}]");
        $response = [];
        if ($times->total_count()) foreach ($times as $time) {
            $data['ltstart'] = $time->ltstart;
            $data['ltend'] = $time->ltend;
            $data['dtstart'] = $time->dtstart;
            $data['dtend'] = $time->dtend;
            $data['type'] = $time->type;
            $data['num'] = $time->num;
            $data['days'] = $time->days;
            $response[] = $data;
        }
        return $response;
    }
 
}
