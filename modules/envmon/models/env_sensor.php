<?php

class Env_Sensor_Model extends Presentable_Model {

    const IN_SERVICE = 0;
    const OUT_OF_SERVICE = 1;

    static $STATUS_ARRAY = [
        self::IN_SERVICE,
        self::OUT_OF_SERVICE 
    ];

	function unit() {
		return $this->unit ? : '°C';
	}

	function normalize_value($v) {
		if (!$v) {
			$v = rand($this->vfrom, $this->vto);
		}
		return (double)$v;
	}
	
	function delete() {
		
		$ret = parent::delete();
        $sensor_id = $this->id;

		if ($ret) {
            $db = Database::factory();
            $db->query('delete from env_datapoint where sensor_id=%d', $sensor_id);
            $db->query('delete from env_actual_datapoint where sensor_id=%d', $sensor_id);
		}
		
		return $ret;
	}

}
