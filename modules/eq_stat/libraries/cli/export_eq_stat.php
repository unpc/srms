<?php

class CLI_Export_Eq_Stat {

    static function export() {
        $params = func_get_args();
        $selector = $params[0];
        $valid_columns_flip = json_decode($params[6], true);
        $equipments = Q($selector);
        $stat_list = json_decode($params[7], true);
        $raw_dtstart = $params[1];
        $raw_dtend = $params[2];
        $dtstart = $params[3];
        $dtend = $params[4];
        $start = 0;
        $per_page = 100;

        $excel = new Excel($params[5]);
        $excel->write(array_values(json_decode($params[8], true)));

        foreach($equipments as $equipment) {
			$project_values = Event::trigger('stat.equipment.project_statistic_values', $equipment, $raw_dtstart, $raw_dtend);
            $stat_content = [];

            foreach($stat_list as $key => $value) {
            	if (in_array($key, $valid_columns_flip)) {
                    $stat_opts = Config::get('eq_stat.stat_opts');
                    if ( strpos($key, 'project') !== false ) {
					  	$stat_content[] = trim(V("eq_stat:eq_stat/export_value/$key", ['value' => $project_values[$key], 'type' => 'csv', ]));
						continue;
					}
                    if (array_key_exists($key,$stat_opts)) {
                       $stat_content[] = trim(V("eq_stat:eq_stat/export_value/$key", ['value'=> EQ_Stat::data_point($key, $equipment, $dtstart, $dtend), 'type'=> 'csv']));
                    }
                    else {
                       $stat_content[] = trim(V("eq_stat:eq_stat/export_value/$key", ['value'=> $equipment, 'type'=> 'csv']));
                    }
            	}
            }
            $excel->write($stat_content);
        }

        $excel->save();
    }
}
