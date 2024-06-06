<?php

class Envmon_sum {
	
	static function get_suv_data($sensor) {
		
		$dtstart = strtotime('2011-10-19');
		$dtend = strtotime('2011-10-27');
		$interval = 800;
		$num =0;

		for ($i = $dtstart ; $i <= $dtend; $i += $interval) {
			$point = O('env_datapoint');
			$point->sensor = $sensor;
			$point->ctime = $i;
			$point->value = self::num_add($sensor, rand(1, 4));
			$point->save();
		}
		
	} 
	
	static function num_add($sensor, $step) {
		$value = round($sensor->vfrom + ($sensor->vto - $sensor->vfrom)/2, 3);
		switch (rand(1, 10)) {
			case 1:
			case 4:
				$value += $step * 0.17;
				break;
			case 3:
			case 6:
				$value -= $step * 0.17;
				break;
			case 2:
			case 7:
				$value += $step * 0.24;
				break;
			case 5:
			case 9:
				$value -= $step * 0.24;
				break;
			default:
				$value += $step * 0.09;
				break;
		}

		return $value;
	}
}
